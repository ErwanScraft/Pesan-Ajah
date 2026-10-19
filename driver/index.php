<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

$driver = require_driver();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">

    <title>Dashboard Driver — Anter Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .driver-shell {
            width: min(100% - 32px, 900px);
            margin: 0 auto;
            padding: 32px 0;
        }

        .driver-card {
            padding: 24px;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: #fff;
        }

        .driver-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <main class="driver-shell">
        <header class="driver-header">
            <div>
                <p class="muted">Anter Ajah</p>
                <h1>Dashboard Driver</h1>
                <p>
                    Selamat datang, <?= e($driver['name']) ?>.
                </p>
            </div>

            <form method="post" action="/driver/logout.php">
                <?= csrf_field() ?>

                <button
                    class="button button-outline"
                    type="submit"
                >
                    Keluar
                </button>
            </form>
        </header>


        <section class="driver-card">
            <h2>Operasional pengantaran</h2>
        
            <p>
                Lihat pesanan yang tersedia dan pantau pesanan
                yang sudah kamu ambil.
            </p>
        
            <a class="button button-primary" href="/driver/pesanan.php">
                Buka antrean pesanan
            </a>
        </section>

    </main>
</body>
</html>
