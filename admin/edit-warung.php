<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$pdo = database();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);

    exit('ID warung tidak valid.');
}

$stmt = $pdo->prepare(
    'SELECT
        s.*,
        u.username,
        u.is_active AS user_is_active
     FROM stores s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.id = :id
     LIMIT 1'
);

$stmt->execute([
    'id' => $id,
]);

$store = $stmt->fetch();

if (!$store) {
    http_response_code(404);

    exit('Warung tidak ditemukan.');
}

$errors = [];

if (request_is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $owner = trim((string) ($_POST['owner_name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));

    $slug = strtolower($slug);

    $slug = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $slug
    ) ?? '';

    $slug = trim($slug, '-');

    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $deliveryDiscount = filter_var(
        $_POST['delivery_discount'] ?? '0',
        FILTER_VALIDATE_INT
    );
    
    if (
        $deliveryDiscount === false
        || $deliveryDiscount < 0
        || $deliveryDiscount > 100000000
    ) {
        $errors[] = 'Diskon ongkir harus berupa nominal Rp0 sampai Rp100.000.000.';
    }

    $newPassword = (string) ($_POST['new_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 80) {
        $errors[] = 'Nama warung wajib diisi, maksimal 80 karakter.';
    }

    if ($owner === '' || mb_strlen($owner) > 80) {
        $errors[] = 'Nama pemilik wajib diisi, maksimal 80 karakter.';
    }

    if ($address === '' || mb_strlen($address) > 180) {
        $errors[] = 'Alamat wajib diisi, maksimal 180 karakter.';
    }

    if (mb_strlen($phone) > 25) {
        $errors[] = 'Nomor telepon maksimal 25 karakter.';
    }

    if (mb_strlen($description) > 500) {
        $errors[] = 'Deskripsi maksimal 500 karakter.';
    }

    if (
        !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
        || strlen($slug) > 80
    ) {
        $errors[] = 'Slug URL tidak valid.';
    }

    $reservedSlugs = [
        'admin',
        'user',
        'warung',
        'assets',
        'app',
        'config',
        'database',
        'scripts',
        'routes',
        'api',
    ];

    if (in_array($slug, $reservedSlugs, true)) {
        $errors[] = 'Slug tersebut dicadangkan oleh sistem.';
    }

    if ($newPassword !== '' && strlen($newPassword) < 12) {
        $errors[] = 'Kata sandi baru minimal 12 karakter.';
    }

    if (strlen($newPassword) > 4096) {
        $errors[] = 'Kata sandi terlalu panjang.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare(
                'SELECT id
                 FROM stores
                 WHERE slug = :slug
                   AND id <> :id
                 LIMIT 1'
            );

            $check->execute([
                'slug' => $slug,
                'id' => $id,
            ]);

            if ($check->fetch()) {
                throw new DomainException(
                    'Slug URL sudah digunakan warung lain.'
                );
            }

            $updateStore = $pdo->prepare(
                'UPDATE stores
                 SET
                    name = :name,
                    slug = :slug,
                    owner_name = :owner_name,
                    phone = :phone,
                    address = :address,
                    description = :description,
                    delivery_discount = :delivery_discount,
                    is_active = :is_active
                 WHERE id = :id'
            );

            $updateStore->execute([
                'name' => $name,
                'slug' => $slug,
                'owner_name' => $owner,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $address,
                'description' => $description !== ''
                    ? $description
                    : null,
                'delivery_discount' => $deliveryDiscount,
                'is_active' => $isActive,
                'id' => $id,
            ]);

            $updateUser = $pdo->prepare(
                'UPDATE users
                 SET is_active = :is_active
                 WHERE id = :user_id
                   AND role = :role'
            );

            $updateUser->execute([
                'is_active' => $isActive,
                'user_id' => (int) $store['user_id'],
                'role' => 'warung',
            ]);

            if ($newPassword !== '') {
                $passwordStmt = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash
                     WHERE id = :user_id
                       AND role = :role'
                );

                $passwordStmt->execute([
                    'password_hash' => password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    ),
                    'user_id' => (int) $store['user_id'],
                    'role' => 'warung',
                ]);
            }

            $pdo->commit();

            flash('success', 'Data warung berhasil diperbarui.');

            redirect('/admin/warung.php');
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Gagal memperbarui warung: '
                . $exception->getMessage()
            );

            $errors[] = 'Perubahan gagal disimpan.';
        }
    }

    $store = array_merge($store, [
        'name' => $name,
        'slug' => $slug,
        'owner_name' => $owner,
        'phone' => $phone,
        'address' => $address,
        'description' => $description,
        'delivery_discount' => $deliveryDiscount,
        'is_active' => $isActive,
    ]);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Edit Warung — Pesan Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .page {
            width: min(720px, calc(100% - 32px));
            margin: 32px auto;
        }

        .card {
            padding: 24px;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: white;
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field input,
        .field textarea {
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid #d8e0db;
            border-radius: 10px;
            font: inherit;
        }

        .errors {
            padding: 14px;
            border-radius: 10px;
            color: #a32323;
            background: #fff0f0;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .check-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>
    <main class="page">
        <header>
            <h1>Edit warung</h1>

            <p class="muted">
                Ubah informasi akun dan akses warung.
            </p>
        </header>

        <?php if ($errors): ?>
            <div class="errors" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="card">
            <form method="post">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="form-grid">
                    <label class="field">
                        <span>Nama warung</span>

                        <input
                            name="name"
                            maxlength="80"
                            value="<?= e($store['name']) ?>"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Nama pemilik</span>

                        <input
                            name="owner_name"
                            maxlength="80"
                            value="<?= e($store['owner_name']) ?>"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Username</span>

                        <input
                            value="<?= e($store['username']) ?>"
                            readonly
                        >
                    </label>

                    <label class="field">
                        <span>Nomor WhatsApp</span>

                        <input
                            name="phone"
                            type="tel"
                            maxlength="25"
                            value="<?= e($store['phone'] ?? '') ?>"
                        >
                    </label>

                    <label class="field">
                        <span>Alamat</span>

                        <input
                            name="address"
                            maxlength="180"
                            value="<?= e($store['address']) ?>"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Slug URL</span>

                        <input
                            name="slug"
                            maxlength="80"
                            value="<?= e($store['slug']) ?>"
                            pattern="[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*"
                            required
                        >

                        <small class="muted">
                            URL publik: /<?= e($store['slug']) ?>
                        </small>
                    </label>

                    <label class="field">
                        <span>Deskripsi</span>

                        <textarea
                            name="description"
                            maxlength="500"
                            rows="4"
                        ><?= e($store['description'] ?? '') ?></textarea>
                    </label>
                    
                    <label class="field">
                        <span>Diskon ongkir (Rp)</span>
                    
                        <input
                            name="delivery_discount"
                            type="number"
                            min="0"
                            max="100000000"
                            step="1000"
                            value="<?= e((string) ($store['delivery_discount'] ?? 0)) ?>"
                            required
                        >
                    
                        <small class="muted">
                            Diskon diterapkan pada tarif ongkir standar.
                            Nilai diskon tidak dapat melebihi ongkir.
                            Isi 0 jika tidak ada diskon.
                        </small>
                    </label>

                    <label class="field">
                        <span>Reset kata sandi</span>

                        <input
                            type="password"
                            name="new_password"
                            minlength="12"
                            maxlength="4096"
                            autocomplete="new-password"
                            placeholder="Kosongkan jika tidak diubah"
                        >
                    </label>

                    <label class="check-field">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?= $store['is_active'] ? 'checked' : '' ?>
                        >

                        <span>Akun warung aktif</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button
                        class="button button-primary"
                        type="submit"
                    >
                        Simpan perubahan
                    </button>

                    <a
                        class="button button-outline"
                        href="/admin/warung.php"
                    >
                        Kembali
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>