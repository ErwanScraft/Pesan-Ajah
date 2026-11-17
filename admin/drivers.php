<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$db = database();
$errors = [];

if (request_is_post()) {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save') {
            $id = filter_var(
                $_POST['id'] ?? 0,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            );

            $username = trim((string) ($_POST['username'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (
                $id === false
                || $username === ''
                || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)
                || $name === ''
                || mb_strlen($name) > 150
                || mb_strlen($phone) > 25
            ) {
                throw new DomainException('Data driver tidak valid.');
            }

            if ($id === 0 && strlen($password) < 12) {
                throw new DomainException(
                    'Password awal minimal 12 karakter.'
                );
            }

            if ($password !== '' && strlen($password) < 12) {
                throw new DomainException(
                    'Password minimal 12 karakter.'
                );
            }

            if ($id === 0) {
                $stmt = $db->prepare(
                    'INSERT INTO drivers (
                        username,
                        password_hash,
                        name,
                        phone,
                        is_active,
                        is_online
                    ) VALUES (
                        :username,
                        :password_hash,
                        :name,
                        :phone,
                        1,
                        0
                    )'
                );

                $stmt->execute([
                    'username' => $username,
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    'name' => $name,
                    'phone' => $phone !== '' ? $phone : null,
                ]);

                flash('success', 'Akun driver berhasil dibuat.');
            } else {
                $check = $db->prepare(
                    'SELECT id
                     FROM drivers
                     WHERE id = :id'
                );

                $check->execute(['id' => $id]);

                if (!$check->fetch()) {
                    throw new DomainException(
                        'Driver tidak ditemukan.'
                    );
                }

                // Username sengaja tidak dapat diubah karena
                // delivery_assignments menyimpan driver_username.
                $stmt = $db->prepare(
                    'UPDATE drivers
                     SET name = :name,
                         phone = :phone
                     WHERE id = :id'
                );

                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone !== '' ? $phone : null,
                    'id' => $id,
                ]);

                if ($password !== '') {
                    $passwordStmt = $db->prepare(
                        'UPDATE drivers
                         SET password_hash = :password
                         WHERE id = :id'
                    );

                    $passwordStmt->execute([
                        'password' => password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),
                        'id' => $id,
                    ]);
                }

                flash('success', 'Profil driver berhasil diperbarui.');
            }
        } elseif ($action === 'toggle') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($id === false) {
                throw new DomainException('ID driver tidak valid.');
            }

            $stmt = $db->prepare(
                'UPDATE drivers
                 SET is_active = IF(is_active = 1, 0, 1),
                     is_online = IF(is_active = 1, 0, is_online)
                 WHERE id = :id'
            );

            $stmt->execute(['id' => $id]);

            flash('success', 'Status akun driver diperbarui.');
        } elseif ($action === 'global_delivery') {
            $enabled = isset($_POST['is_enabled']) ? 1 : 0;

            $stmt = $db->prepare(
                'UPDATE delivery_settings
                 SET is_enabled = :enabled
                 WHERE id = 1'
            );

            $stmt->execute(['enabled' => $enabled]);

            flash('success', 'Pengaturan Delivery global diperbarui.');
        } elseif ($action === 'delete') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($id === false) {
                throw new DomainException('ID driver tidak valid.');
            }

            $check = $db->prepare(
                'SELECT COUNT(*)
                 FROM delivery_assignments
                 WHERE driver_username = (
                     SELECT username FROM drivers WHERE id = :id
                 )'
            );

            $check->execute(['id' => $id]);

            if ((int) $check->fetchColumn() > 0) {
                throw new DomainException(
                    'Driver memiliki riwayat pengantaran. Nonaktifkan akun, jangan hapus.'
                );
            }

            $delete = $db->prepare(
                'DELETE FROM drivers WHERE id = :id'
            );

            $delete->execute(['id' => $id]);

            flash('success', 'Akun driver berhasil dihapus.');
        } else {
            throw new DomainException('Aksi tidak dikenal.');
        }

        redirect('admin/drivers.php');
    } catch (DomainException $exception) {
        $errors[] = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('[Admin Driver] ' . $exception->getMessage());

        $errors[] = (string) $exception->getCode() === '23000'
            ? 'Username sudah digunakan.'
            : 'Perubahan gagal disimpan.';
    } catch (Throwable $exception) {
        error_log('[Admin Driver] ' . $exception->getMessage());

        $errors[] = 'Terjadi kesalahan saat memproses data.';
    }
}

$drivers = $db->query(
    'SELECT id, username, name, phone, is_online, is_active, last_seen_at
     FROM drivers
     ORDER BY created_at DESC'
)->fetchAll();

$deliverySettings = $db->query(
    'SELECT is_enabled FROM delivery_settings WHERE id = 1'
)->fetch();

$onlineCount = (int) $db->query(
    'SELECT COUNT(*)
     FROM drivers
     WHERE is_active = 1 AND is_online = 1'
)->fetchColumn();

$deliveryAvailable = $deliverySettings
    && (bool) $deliverySettings['is_enabled']
    && $onlineCount > 0;

$success = flash('success');

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Driver Antar Ajah — Admin</title>

    <link rel="stylesheet" href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>">
</head>
<body>

<main style="width:min(1000px,calc(100% - 32px));margin:32px auto">

    <header style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1>Driver Antar Ajah</h1>
            <p class="muted">Kelola akun dan ketersediaan layanan Delivery.</p>
        </div>

        <a class="button button-outline" href="/admin/">Kembali ke Admin</a>
    </header>

    <?php if ($success): ?>
        <p role="status"><?= e($success) ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section style="padding:20px;border:1px solid #e3e9e2;border-radius:16px;background:white;margin:24px 0">

        <h2>Kontrol Delivery Global</h2>

        <p>
            Status saat ini:
            <strong><?= $deliveryAvailable ? 'ONLINE' : 'OFFLINE' ?></strong>
        </p>

        <p><?= $onlineCount ?> driver sedang ON.</p>

        <form method="post">
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="global_delivery">

            <label>
                <input
                    type="checkbox"
                    name="is_enabled"
                    value="1"
                    <?= !empty($deliverySettings['is_enabled']) ? 'checked' : '' ?>
                >
                Aktifkan layanan Delivery secara global
            </label>

            <button class="button button-primary" type="submit">
                Simpan pengaturan
            </button>
        </form>
    </section>

    <section style="padding:20px;border:1px solid #e3e9e2;border-radius:16px;background:white">

        <h2>Tambah Driver</h2>

        <form method="post" style="display:grid;gap:12px">
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="0">

            <label>Username
                <input name="username" maxlength="50" required>
            </label>

            <label>Nama lengkap
                <input name="name" maxlength="150" required>
            </label>

            <label>Nomor WhatsApp
                <input name="phone" maxlength="25">
            </label>

            <label>Password awal
                <input type="password" name="password" minlength="12" required>
            </label>

            <button class="button button-primary" type="submit">
                Tambah driver
            </button>
        </form>
    </section>

    <section style="margin-top:24px">
        <h2>Daftar Driver (<?= count($drivers) ?>)</h2>

        <?php foreach ($drivers as $driver): ?>

            <article style="padding:18px;margin:12px 0;border:1px solid #e3e9e2;border-radius:14px;background:white">

                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <strong><?= e($driver['name']) ?></strong>
                        <p class="muted">@<?= e($driver['username']) ?></p>
                        <p><?= e($driver['phone'] ?: 'Nomor belum diisi') ?></p>
                    </div>

                    <div>
                        <strong>
                            <?= !$driver['is_active']
                                ? 'Akun nonaktif'
                                : ($driver['is_online'] ? 'ONLINE' : 'OFFLINE') ?>
                        </strong>
                    </div>
                </div>

                <form method="post" style="display:grid;gap:10px;margin-top:14px">
                    <?= csrf_field() ?>

                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $driver['id'] ?>">
                    <input type="hidden" name="username" value="<?= e($driver['username']) ?>">

                    <label>Nama
                        <input name="name" value="<?= e($driver['name']) ?>" required>
                    </label>

                    <label>Nomor WhatsApp
                        <input name="phone" value="<?= e($driver['phone']) ?>">
                    </label>

                    <label>Password baru (kosongkan jika tidak diubah)
                        <input type="password" name="password" minlength="12">
                    </label>

                    <button class="button button-primary" type="submit">
                        Simpan perubahan
                    </button>
                </form>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">

                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $driver['id'] ?>">

                        <button class="button button-outline" type="submit">
                            <?= $driver['is_active'] ? 'Nonaktifkan akun' : 'Aktifkan akun' ?>
                        </button>
                    </form>

                    <?php if (!$driver['is_online']): ?>
                        <form method="post" onsubmit="return confirm('Hapus akun driver ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $driver['id'] ?>">

                            <button class="button button-outline" type="submit">
                                Hapus
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </section>

</main>
</body>
</html>
