<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

$store = api_store_owner();

if (request_method() === 'GET') {
    api_json([
        'success' => true,

        'settings' => [
            'name' => $store['name'],
            'owner' => $store['owner'],
            'phone' => $store['phone'],
            'address' => $store['address'],
            'description' => $store['description'],

            'icon_image' => $store['icon_image'] ?? null,

            'is_open' => (bool) $store['is_open'],
            'free_delivery' => (bool) $store['free_delivery'],
        ],
    ]);
}

api_verify_csrf();

if (request_method() !== 'POST') {
    header('Allow: GET, POST');

    api_json([
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.',
    ], 405);
}

$input = api_json_input();

$storeId = (int) $store['store_id'];

/*
 * Permintaan khusus untuk status operasional.
 */
if (
    array_key_exists('is_open', $input)
    || array_key_exists('free_delivery', $input)
) {
    $allowedKeys = ['is_open', 'free_delivery'];

    foreach (array_keys($input) as $key) {
        if (!in_array($key, $allowedKeys, true)) {
            api_json([
                'success' => false,
                'message' => 'Permintaan status tidak valid.',
            ], 422);
        }
    }

    if (count($input) !== 1) {
        api_json([
            'success' => false,
            'message' => 'Ubah satu status dalam setiap permintaan.',
        ], 422);
    }

    $key = array_key_first($input);

    $value = filter_var(
        $input[$key],
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($value === null) {
        api_json([
            'success' => false,
            'message' => 'Nilai status tidak valid.',
        ], 422);
    }

    $column = $key === 'is_open'
        ? 'is_open'
        : 'free_delivery';

    /*
     * Nama kolom berasal dari daftar yang diizinkan,
     * bukan langsung dari input SQL.
     */
    $stmt = database()->prepare(
        "UPDATE stores
         SET {$column} = :value
         WHERE id = :store_id"
    );

    $stmt->execute([
        'value' => $value ? 1 : 0,
        'store_id' => $storeId,
    ]);

    api_json([
        'success' => true,
        'message' => $key === 'is_open'
            ? 'Status warung berhasil diperbarui.'
            : 'Pengaturan gratis ongkir berhasil diperbarui.',
        'value' => $value,
    ]);
}

/*
 * Selain status, permintaan POST harus berupa profil.
 */
$name = api_required_text(
    $input['name'] ?? null,
    'Nama warung',
    80
);

$owner = api_required_text(
    $input['owner'] ?? null,
    'Nama pemilik',
    80
);

$address = api_required_text(
    $input['address'] ?? null,
    'Alamat warung',
    180
);

$phone = trim(
    is_string($input['phone'] ?? null)
        ? $input['phone']
        : ''
);

$description = trim(
    is_string($input['description'] ?? null)
        ? $input['description']
        : ''
);

if (
    mb_strlen($phone, 'UTF-8') > 25
    || mb_strlen($description, 'UTF-8') > 500
) {
    api_json([
        'success' => false,
        'message' => 'Panjang nomor telepon atau deskripsi melebihi batas.',
    ], 422);
}

$stmt = database()->prepare(
    'UPDATE stores

    SET
        name = :name,
        owner = :owner,
        phone = :phone,
        address = :address,
        description = :description

    WHERE id = :store_id'
);

$stmt->execute([
    'name' => $name,
    'owner' => $owner,
    'phone' => $phone !== '' ? $phone : null,
    'address' => $address,
    'description' => $description !== '' ? $description : null,
    'store_id' => $storeId,
]);

api_json([
    'success' => true,
    'message' => 'Profil warung berhasil disimpan.',
]);