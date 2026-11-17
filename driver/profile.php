<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

$driver = require_driver();
$db = database();

$errors = [];

$stmt = $db->prepare(
    'SELECT id, username, name, phone
     FROM drivers
     WHERE id = :id'
);

$stmt->execute(['id' => $driver['id']]);

$profile = $stmt->fetch();

if (request_is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));

    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = 'Nama wajib diisi, maksimal 150 karakter.';
    }

    if (mb_strlen($phone) > 25) {
        $errors[] = 'Nomor WhatsApp maksimal 25 karakter.';
    }

    if (!$errors) {
        $update = $db->prepare(
            'UPDATE drivers
             SET name = :name,
                 phone = :phone
             WHERE id = :id'
        );

        $update->execute([
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'id' => $driver['id'],
        ]);

        flash('success', 'Profil berhasil diperbarui.');

        redirect('driver/profile.php');
    }

    $profile['name'] = $name;
    $profile['phone'] = $phone;
}

$success = flash('success');

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Driver — Antar Ajah</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/pages/driver.css?v=<?= asset_version('assets/css/pages/driver.css') ?>">
</head>
<body class="driver-page">

<main class="driver-shell">

    <header class="driver-topbar">
        <div class="driver-brand">
            <span class="driver-brand-icon">A</span>
            <div>
                <strong>Antar Ajah</strong>
                <span>Profil Driver</span>
            </div>
        </div>

        <a class="driver-logout" href="/driver/index.php">Dashboard</a>
    </header>

    <h1>Profil Saya</h1>

    <?php if ($success): ?>
        <p class="delivery-message success"><?= e($success) ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p class="delivery-message error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="post" class="driver-active-card">
        <?= csrf_field() ?>

        <label class="field">
            Username
            <input value="<?= e($profile['username']) ?>" disabled>
        </label>

        <label class="field">
            Nama lengkap
            <input
                name="name"
                maxlength="150"
                value="<?= e($profile['name']) ?>"
                required
            >
        </label>

        <label class="field">
            Nomor WhatsApp
            <input
                name="phone"
                maxlength="25"
                value="<?= e($profile['phone']) ?>"
            >
        </label>

        <button class="driver-button driver-button-primary" type="submit">
            Simpan profil
        </button>
    </form>

</main>

</body>
</html>
