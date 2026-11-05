<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('GET');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function buyer_profile_json(
    array $data,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

$deviceToken = $_COOKIE['pa_buyer_device'] ?? '';

if (
    !is_string($deviceToken)
    || !preg_match('/^[a-f0-9]{64}$/i', $deviceToken)
) {
    buyer_profile_json([
        'success' => true,
        'profile' => null,
    ]);
}

try {
    $db = database();

    $stmt = $db->prepare(
        'SELECT
            b.name,
            b.phone,
            b.delivery_address,
            b.delivery_maps_url,
            b.delivery_latitude,
            b.delivery_longitude

        FROM buyer_devices bd

        INNER JOIN buyers b
            ON b.id = bd.buyer_id

        WHERE bd.token_hash = :token_hash

        LIMIT 1'
    );

    $stmt->execute([
        'token_hash' => hash('sha256', $deviceToken),
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        buyer_profile_json([
            'success' => true,
            'profile' => null,
        ]);
    }

    buyer_profile_json([
        'success' => true,
        'profile' => [
            'name' => (string) $profile['name'],
            'phone' => (string) ($profile['phone'] ?? ''),
            'delivery_address' => (string) (
                $profile['delivery_address'] ?? ''
            ),
            'delivery_maps_url' => (string) (
                $profile['delivery_maps_url'] ?? ''
            ),
            'delivery_latitude' => $profile['delivery_latitude'],
            'delivery_longitude' => $profile['delivery_longitude'],
        ],
    ]);

} catch (Throwable $exception) {
    error_log(
        '[Pesan Ajah] Buyer profile gagal dimuat: '
        . $exception->getMessage()
    );

    buyer_profile_json([
        'success' => false,
        'message' => 'Profil pembeli tidak dapat dimuat.',
    ], 500);
}