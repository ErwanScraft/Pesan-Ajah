<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$stmt = database()->query(
    'SELECT
        s.id,
        s.name,
        s.slug,
        s.owner_name,
        s.phone,
        s.is_open,
        s.free_delivery,
        s.is_active,
        s.created_at,
        u.username
     FROM stores s
     INNER JOIN users u ON u.id = s.user_id
     ORDER BY s.created_at DESC'
);

$stores = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Daftar Warung — Pesan Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .page {
            width: min(1120px, calc(100% - 32px));
            margin: 32px auto;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #edf0ee;
            text-align: left;
            white-space: nowrap;
        }

        th {
            color: #617067;
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: 0;
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="toolbar">
            <div>
                <h1>Daftar warung</h1>

                <p class="muted">
                    Kelola akun dan informasi warung.
                </p>
            </div>

            <div>
                <a
                    class="button button-primary"
                    href="/admin/tambah-warung.php"
                >
                    + Tambah warung
                </a>

                <a class="button button-outline" href="/admin/">
                    Kembali
                </a>
            </div>
        </header>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama warung</th>
                        <th>Pemilik</th>
                        <th>Username</th>
                        <th>URL publik</th>
                        <th>Operasional</th>
                        <th>Status akun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$stores): ?>
                        <tr>
                            <td colspan="7">
                                Belum ada warung yang terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($stores as $store): ?>
                        <tr>
                            <td><?= e($store['name']) ?></td>

                            <td><?= e($store['owner_name']) ?></td>

                            <td><?= e($store['username']) ?></td>

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
                                <?= $store['is_open'] ? 'Buka' : 'Tutup' ?>
                            </td>

                            <td>
                                <?= $store['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </td>

                            <td>
                                <a
                                    class="button button-outline"
                                    href="/admin/edit-warung.php?id=<?= (int) $store['id'] ?>"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>