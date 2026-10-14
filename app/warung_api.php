<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Mengirim respons JSON dan menghentikan proses.
 */
function api_json(array $data, int $status = 200): never
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/**
 * Mengambil data JSON dari request.
 */
function api_json_input(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    try {
        $data = json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        api_json([
            'success' => false,
            'message' => 'Format JSON tidak valid.',
        ], 400);
    }

    if (!is_array($data)) {
        api_json([
            'success' => false,
            'message' => 'Data permintaan tidak valid.',
        ], 400);
    }

    return $data;
}

/**
 * Memvalidasi autentikasi dan mengambil warung pemilik.
 *
 * store_id selalu berasal dari session dan database,
 * bukan dari input browser.
 */
function api_store_owner(): array
{
    $user = auth_user();

    if ($user === null) {
        api_json([
            'success' => false,
            'message' => 'Sesi login telah berakhir.',
        ], 401);
    }

    if (($user['role'] ?? '') !== 'warung') {
        api_json([
            'success' => false,
            'message' => 'Akses hanya tersedia untuk pemilik warung.',
        ], 403);
    }

    $stmt = database()->prepare(
        'SELECT
            u.id AS user_id,
            u.username,
            u.is_active AS user_is_active,

            s.id AS store_id,
            s.name,
            s.slug,
            s.owner,
            s.phone,
            s.address,
            s.description,
            s.is_open,
            s.free_delivery,
            s.is_active AS store_is_active

        FROM users u

        INNER JOIN stores s
            ON s.user_id = u.id

        WHERE u.id = :user_id
          AND u.role = :role

        LIMIT 1'
    );

    $stmt->execute([
        'user_id' => (int) $user['id'],
        'role' => 'warung',
    ]);

    $store = $stmt->fetch();

    if (
        !$store
        || !(bool) $store['user_is_active']
        || !(bool) $store['store_is_active']
    ) {
        api_json([
            'success' => false,
            'message' => 'Akun atau warung tidak aktif.',
        ], 403);
    }

    return $store;
}

/**
 * Validasi token CSRF untuk permintaan perubahan data.
 */
function api_verify_csrf(): void
{
    require_method('POST', 'PUT', 'PATCH', 'DELETE');

    $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    $stored = $_SESSION['_csrf_token'] ?? '';

    if (
        !is_string($submitted)
        || !is_string($stored)
        || $stored === ''
        || !hash_equals($stored, $submitted)
    ) {
        api_json([
            'success' => false,
            'message' => 'Sesi permintaan tidak valid. Muat ulang halaman.',
        ], 419);
    }
}

/**
 * Memvalidasi ID positif.
 */
function api_positive_id(mixed $value): int
{
    $id = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
            ],
        ]
    );

    if ($id === false) {
        api_json([
            'success' => false,
            'message' => 'ID tidak valid.',
        ], 422);
    }

    return $id;
}

/**
 * Memastikan teks wajib tidak kosong dan sesuai panjang.
 */
function api_required_text(
    mixed $value,
    string $label,
    int $maxLength
): string {
    if (!is_string($value)) {
        api_json([
            'success' => false,
            'message' => $label . ' wajib berupa teks.',
        ], 422);
    }

    $value = trim($value);

    if ($value === '' || mb_strlen($value, 'UTF-8') > $maxLength) {
        api_json([
            'success' => false,
            'message' => $label . ' wajib diisi dan maksimal '
                . $maxLength . ' karakter.',
        ], 422);
    }

    return $value;
}