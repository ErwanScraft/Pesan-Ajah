<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$pdo = database();

$totalStores = (int) $pdo->query(
    'SELECT COUNT(*) FROM stores'
)->fetchColumn();

$activeStores = (int) $pdo->query(
    'SELECT COUNT(*) FROM stores WHERE is_active = 1'
)->fetchColumn();

$inactiveStores = $totalStores - $activeStores;

$totalOwners = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'warung'"
)->fetchColumn();

$recentStores = $pdo->query(
    'SELECT
        s.id,
        s.name,
        s.slug,
        s.owner_name,
        s.is_active,
        s.created_at
     FROM stores s
     ORDER BY s.created_at DESC
     LIMIT 8'
)->fetchAll();

$success = flash('success');
$error = flash('error');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#176b45">

    <title>Admin Panel — Pesan Ajah</title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .admin-shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .admin-header,
        .admin-toolbar,
        .admin-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .admin-header {
            margin-bottom: 32px;
        }

        .admin-header h1,
        .admin-header p {
            margin-top: 0;
        }

        .admin-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .admin-stat,
        .admin-card {
            padding: 22px;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: #fff;
        }

        .admin-stat span,
        .admin-stat strong {
            display: block;
        }

        .admin-stat strong {
            margin-top: 12px;
            font-size: 28px;
        }

        .admin-card {
            overflow: hidden;
        }

        .admin-table-wrap {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0ee;
            text-align: left;
            white-space: nowrap;
        }

        .admin-table th {
            font-size: 13px;
            color: #617067;
        }

        .admin-table tr:last-child td {
            border-bottom: 0;
        }

        .admin-status {
            display: inline-flex;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .admin-status.active {
            color: #176b45;
            background: #e8f6ee;
        }

        .admin-status.inactive {
            color: #8b4b13;
            background: #fff1dc;
        }

        .admin-message {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 10px;
        }

        .admin-message.success {
            color: #176b45;
            background: #e8f6ee;
        }

        .admin-message.error {
            color: #a32323;
            background: #fff0f0;
        }

        @media (max-width: 760px) {
            .admin-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 420px) {
            .admin-stats {
                grid-template-columns: 1fr;
            }

            .admin-stat,
            .admin-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <main class="admin-shell">
        <header class="admin-header">
            <div>
                <a href="/">
                    <img
                        src="/assets/images/logo.webp"
                        width="44"
                        height="44"
                        alt="Pesan Ajah"
                    >
                </a>

                <h1>Admin Panel</h1>

                <p class="muted">
                    Kelola akun dan profil warung yang terdaftar.
                </p>
            </div>

            <div class="admin-actions">
                <a
                    class="button button-primary"
                    href="/admin/tambah-warung.php"
                >
                    + Tambah warung
                </a>

                <form method="post" action="/admin/logout.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <button
                        class="button button-outline"
                        type="submit"
                    >
                        Keluar
                    </button>
                </form>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="admin-message success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-message error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <section class="admin-stats">
            <article class="admin-stat">
                <span>Total warung</span>
                <strong><?= $totalStores ?></strong>
            </article>

            <article class="admin-stat">
                <span>Warung aktif</span>
                <strong><?= $activeStores ?></strong>
            </article>

            <article class="admin-stat">
                <span>Warung nonaktif</span>
                <strong><?= $inactiveStores ?></strong>
            </article>

            <article class="admin-stat">
                <span>Akun pemilik</span>
                <strong><?= $totalOwners ?></strong>
            </article>
        </section>

        <section class="admin-card">
            <div class="admin-toolbar">
                <div>
                    <h2>Warung terbaru</h2>

                    <p class="muted">
                        Daftar warung yang terakhir didaftarkan.
                    </p>
                </div>

                <a
                    class="button button-outline"
                    href="/admin/warung.php"
                >
                    Lihat semua
                </a>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Warung</th>
                            <th>Pemilik</th>
                            <th>URL publik</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$recentStores): ?>
                            <tr>
                                <td colspan="5">
                                    Belum ada warung yang terdaftar.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($recentStores as $store): ?>
                            <tr>
                                <td><?= e($store['name']) ?></td>

                                <td><?= e($store['owner_name']) ?></td>

                                <td>
                                    <a
                                        href="/<?= rawurlencode($store['slug']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        /<?= e($store['slug']) ?>
                                    </a>
                                </td>

                                <td>
                                    <?php if ($store['is_active']): ?>
                                        <span class="admin-status active">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="admin-status inactive">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a
                                        class="button button-outline"
                                        href="/admin/edit-warung.php?id=<?= (int) $store['id'] ?>"
                                    >
                                        Kelola
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>