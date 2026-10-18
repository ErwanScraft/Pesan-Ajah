<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

$store = api_store_owner();

if (request_method() === 'GET') {
    api_json([
        'success' => true,

        'settings' => [
            'name' => $store['name'],
            'owner_name' => $store['owner_name'],
            'phone' => $store['phone'],
            'address' => $store['address'],
            'latitude' => $store['latitude'],
            'longitude' => $store['longitude'],
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
    $input['owner_name'] ?? null,
    'Nama pemilik',
    80
);

$address = api_required_text(
    $input['address'] ?? null,
    'Alamat warung',
    180
);

$latitudeInput = $input['latitude'] ?? null;
$longitudeInput = $input['longitude'] ?? null;

$latitude = null;
$longitude = null;

$hasLatitude = $latitudeInput !== null
    && $latitudeInput !== '';

$hasLongitude = $longitudeInput !== null
    && $longitudeInput !== '';

if ($hasLatitude !== $hasLongitude) {
    api_json([
        'success' => false,
        'message' => 'Latitude dan longitude harus diisi bersamaan.',
    ], 422);
}

if ($hasLatitude && $hasLongitude) {
    if (
        !is_numeric($latitudeInput)
        || !is_numeric($longitudeInput)
    ) {
        api_json([
            'success' => false,
            'message' => 'Koordinat lokasi harus berupa angka.',
        ], 422);
    }

    $latitude = (float) $latitudeInput;
    $longitude = (float) $longitudeInput;

    if (
        !is_finite($latitude)
        || !is_finite($longitude)
        || $latitude < -90
        || $latitude > 90
        || $longitude < -180
        || $longitude > 180
    ) {
        api_json([
            'success' => false,
            'message' => 'Koordinat lokasi berada di luar rentang yang valid.',
        ], 422);
    }
}

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
        owner_name = :owner,
        phone = :phone,
        address = :address,
        latitude = :latitude,
        longitude = :longitude,
        description = :description

    WHERE id = :store_id'
);

$stmt->execute([
    'name' => $name,
    'owner_name' => $owner,
    'phone' => $phone !== '' ? $phone : null,
    'address' => $address,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'description' => $description !== '' ? $description : null,
    'store_id' => $storeId,
]);

api_json([
    'success' => true,
    'message' => 'Profil warung berhasil disimpan.',
]);