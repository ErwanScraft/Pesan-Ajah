<?php

declare(strict_types=1);

/**
 * Memastikan pengguna yang mengakses dashboard adalah
 * pemilik warung yang sudah login dan masih aktif.
 *
 * Mengembalikan informasi warung milik pengguna tersebut.
 */
function require_store_owner(): array
{
    $user = auth_user();

    // Pengguna belum login.
    if ($user === null) {
        redirect('/warung/login.php');
    }

    // Akun admin tidak boleh masuk ke dashboard warung.
    if (($user['role'] ?? '') !== 'warung') {
        http_response_code(403);

        exit('Anda tidak memiliki izin mengakses halaman warung.');
    }

    $stmt = database()->prepare(
        'SELECT
            u.id AS user_id,
            u.username,
            u.is_active AS user_is_active,

            s.id AS store_id,
            s.name,
            s.slug,
            s.owner_name,
            s.phone,
            s.address,
            s.description,
            s.icon_image,
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

    /*
     * Akun harus memiliki profil warung yang terhubung.
     * Akun pengguna dan warung juga harus sama-sama aktif.
     */
    if (
        !$store
        || !(bool) $store['user_is_active']
        || !(bool) $store['store_is_active']
    ) {
        logout_user();

        redirect('/warung/login.php');
    }

    return $store;
}