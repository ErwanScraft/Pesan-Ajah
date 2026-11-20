<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/warung_api.php';

require_method('GET', 'POST');

$db = database();

/*
|--------------------------------------------------------------------------
| GET — Daftar ulasan dan statistik
|--------------------------------------------------------------------------
*/

if (request_method() === 'GET') {
    try {
        $reviewStmt = $db->query(
            'SELECT
                id,
                name,
                rating,
                review,
                created_at
             FROM app_reviews
             ORDER BY created_at DESC, id DESC
             LIMIT 100'
        );

        $reviews = $reviewStmt->fetchAll();

        $statsStmt = $db->query(
            'SELECT
                COUNT(*) AS total_reviews,
                COALESCE(AVG(rating), 0) AS average_rating
             FROM app_reviews'
        );

        $stats = $statsStmt->fetch();

        api_json([
            'success' => true,
            'reviews' => $reviews,
            'stats' => [
                'total_reviews' => (int) (
                    $stats['total_reviews'] ?? 0
                ),
                'average_rating' => round(
                    (float) ($stats['average_rating'] ?? 0),
                    1
                ),
            ],
        ]);
    } catch (Throwable $exception) {
        error_log(
            '[Pesan Ajah] App reviews GET: '
            . $exception->getMessage()
        );

        api_json([
            'success' => false,
            'message' => 'Ulasan belum dapat dimuat.',
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| POST — Simpan ulasan
|--------------------------------------------------------------------------
*/

api_verify_csrf();

$input = api_json_input();

/*
 * Honeypot.
 * Bot yang mengisi field tersembunyi tidak akan menyimpan data.
 */
$website = $input['website'] ?? '';

if (is_string($website) && trim($website) !== '') {
    api_json([
        'success' => true,
        'message' => 'Ulasan berhasil dikirim.',
    ]);
}

/*
 * Pembatasan satu ulasan per 60 detik per session.
 */
$now = time();

$lastReview = (int) (
    $_SESSION['_app_review_last'] ?? 0
);

if ($now - $lastReview < 60) {
    api_json([
        'success' => false,
        'message' => 'Tunggu 60 detik sebelum mengirim ulasan berikutnya.',
    ], 429);
}

/*
 * Validasi nama.
 */
$name = $input['name'] ?? null;

if (!is_string($name)) {
    api_json([
        'success' => false,
        'message' => 'Nama tidak valid.',
    ], 422);
}

$name = trim($name);

$nameLength = function_exists('mb_strlen')
    ? mb_strlen($name, 'UTF-8')
    : strlen($name);

if ($name === '' || $nameLength > 100) {
    api_json([
        'success' => false,
        'message' => 'Nama wajib diisi dan maksimal 100 karakter.',
    ], 422);
}

/*
 * Validasi rating.
 */
$rating = filter_var(
    $input['rating'] ?? null,
    FILTER_VALIDATE_INT
);

if ($rating === false || $rating < 1 || $rating > 5) {
    api_json([
        'success' => false,
        'message' => 'Rating harus antara 1 sampai 5 bintang.',
    ], 422);
}

/*
 * Validasi isi ulasan.
 */
$review = $input['review'] ?? null;

if (!is_string($review)) {
    api_json([
        'success' => false,
        'message' => 'Ulasan tidak valid.',
    ], 422);
}

$review = trim($review);

$reviewLength = function_exists('mb_strlen')
    ? mb_strlen($review, 'UTF-8')
    : strlen($review);

if ($review === '' || $reviewLength > 1000) {
    api_json([
        'success' => false,
        'message' => 'Ulasan wajib diisi dan maksimal 1000 karakter.',
    ], 422);
}

/*
 * Simpan ke database.
 */
try {
    $stmt = $db->prepare(
        'INSERT INTO app_reviews (
            name,
            rating,
            review
        ) VALUES (
            :name,
            :rating,
            :review
        )'
    );

    $stmt->execute([
        'name' => $name,
        'rating' => $rating,
        'review' => $review,
    ]);

    $_SESSION['_app_review_last'] = $now;

    api_json([
        'success' => true,
        'message' => 'Terima kasih. Ulasan berhasil dikirim.',
        'review_id' => (int) $db->lastInsertId(),
    ]);
} catch (Throwable $exception) {
    error_log(
        '[Pesan Ajah] App reviews POST: '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Ulasan gagal disimpan. Silakan coba kembali.',
    ], 500);
}
