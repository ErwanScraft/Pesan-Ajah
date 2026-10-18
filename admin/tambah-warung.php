<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_admin();

$errors = [];

$name = '';
$owner = '';
$username = '';
$phone = '';
$address = '';
$description = '';
$slug = '';
$password = '';

/**
 * Membuat slug URL yang konsisten.
 */
function generate_store_slug(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));

    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';

    return trim($value, '-');
}

/**
 * Memproses formulir pembuatan akun warung.
 */
if (request_is_post()) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $owner = trim((string) ($_POST['owner_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $slug = generate_store_slug(
        $slugInput !== '' ? $slugInput : $name
    );

    /*
     * Validasi input.
     */
    if ($name === '' || mb_strlen($name, 'UTF-8') > 80) {
        $errors[] = 'Nama warung wajib diisi, maksimal 80 karakter.';
    }

    if ($owner === '' || mb_strlen($owner, 'UTF-8') > 80) {
        $errors[] = 'Nama pemilik wajib diisi, maksimal 80 karakter.';
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $errors[] = 'Username harus 3–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    }

    if ($address === '' || mb_strlen($address, 'UTF-8') > 180) {
        $errors[] = 'Alamat wajib diisi, maksimal 180 karakter.';
    }

    if (mb_strlen($phone, 'UTF-8') > 25) {
        $errors[] = 'Nomor telepon maksimal 25 karakter.';
    }

    if (mb_strlen($description, 'UTF-8') > 500) {
        $errors[] = 'Deskripsi maksimal 500 karakter.';
    }

    if (
        !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
        || mb_strlen($slug, 'UTF-8') > 80
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
        'index.php',
        'index.html',
    ];

    if (in_array($slug, $reservedSlugs, true)) {
        $errors[] = 'Slug tersebut dicadangkan oleh sistem.';
    }

    if (strlen($password) < 12) {
        $errors[] = 'Kata sandi awal minimal 12 karakter.';
    }

    if (strlen($password) > 4096) {
        $errors[] = 'Kata sandi terlalu panjang.';
    }

    /*
     * Proses database.
     *
     * database() sengaja dimasukkan ke dalam try agar kegagalan
     * koneksi database juga tercatat oleh sistem debug.
     */
    if (!$errors) {
        $pdo = null;

        try {
            $pdo = database();
            $pdo->beginTransaction();

            /*
             * Periksa username.
             */
            $checkUser = $pdo->prepare(
                'SELECT id
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );

            $checkUser->execute([
                'username' => $username,
            ]);

            if ($checkUser->fetch(PDO::FETCH_ASSOC)) {
                throw new DomainException(
                    'Username sudah digunakan.'
                );
            }

            /*
             * Periksa slug warung.
             */
            $checkSlug = $pdo->prepare(
                'SELECT id
                 FROM stores
                 WHERE slug = :slug
                 LIMIT 1'
            );

            $checkSlug->execute([
                'slug' => $slug,
            ]);

            if ($checkSlug->fetch(PDO::FETCH_ASSOC)) {
                throw new DomainException(
                    'Slug URL sudah digunakan.'
                );
            }

            /*
             * Buat akun pengguna dengan role warung.
             */
            $userStmt = $pdo->prepare(
                'INSERT INTO users (
                    username,
                    password_hash,
                    role,
                    is_active
                ) VALUES (
                    :username,
                    :password_hash,
                    :role,
                    1
                )'
            );

            $userStmt->execute([
                'username' => $username,
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                'role' => 'warung',
            ]);

            $userId = (int) $pdo->lastInsertId();

            if ($userId <= 0) {
                throw new RuntimeException(
                    'ID akun warung tidak berhasil dibuat.'
                );
            }

            /*
             * Buat profil warung.
             */
            $storeStmt = $pdo->prepare(
                'INSERT INTO stores (
                    user_id,
                    name,
                    slug,
                    owner_name,
                    phone,
                    address,
                    description,
                    is_open,
                    free_delivery,
                    is_active
                ) VALUES (
                    :user_id,
                    :name,
                    :slug,
                    :owner_name,
                    :phone,
                    :address,
                    :description,
                    0,
                    0,
                    1
                )'
            );

            $storeStmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'slug' => $slug,
                'owner_name' => $owner,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $address,
                'description' => $description !== ''
                    ? $description
                    : null,
            ]);

            /*
             * Simpan kedua data secara atomik.
             */
            $pdo->commit();

            /*
             * Jangan menyimpan kata sandi ke session atau log.
             */
            $password = '';

            flash('success', 'Akun warung berhasil dibuat.');

            redirect('/admin/warung.php');
        } catch (DomainException $exception) {
            /*
             * Kesalahan bisnis yang sudah diketahui, misalnya
             * username atau slug duplikat.
             */
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            /*
             * Rollback jika transaksi masih berjalan.
             */
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (Throwable $rollbackException) {
                    debug_report_error(
                        $rollbackException,
                        'Rollback gagal saat membuat akun warung'
                    );
                }
            }

            /*
             * Daftarkan exception ke sistem debug.
             * Popup detail hanya ditampilkan ketika APP_DEBUG=true,
             * sesuai implementasi app/debug.php.
             */
            debug_report_error(
                $exception,
                'Gagal membuat akun warung'
            );

            /*
             * Pesan umum tetap ditampilkan kepada pengguna.
             */
            $errors[] = 'Akun gagal dibuat. Periksa data dan coba kembali.';
        } finally {
            /*
             * Bersihkan referensi kata sandi dari variabel lokal.
             */
            $password = '';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tambah Warung — Pesan Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .page {
            width: min(760px, calc(100% - 32px));
            margin: 32px auto;
        }

        .form-card {
            padding: 24px;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: #fff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
            min-width: 0;
        }

        .field input,
        .field textarea {
            box-sizing: border-box;
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid #d8e0db;
            border-radius: 10px;
            font: inherit;
        }

        .field input:focus,
        .field textarea:focus {
            outline: 2px solid #b7d8c3;
            outline-offset: 1px;
            border-color: #528b66;
        }

        .field textarea {
            resize: vertical;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .errors {
            margin: 18px 0;
            padding: 14px 18px;
            border: 1px solid #f0caca;
            border-radius: 10px;
            color: #a32323;
            background: #fff0f0;
        }

        .errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .muted {
            color: #68756d;
            font-size: 0.9rem;
        }

        @media (max-width: 560px) {
            .page {
                margin: 20px auto;
            }

            .form-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .field.full {
                grid-column: auto;
            }

            .form-card {
                padding: 18px;
            }

            .form-actions > * {
                flex: 1 1 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header>
            <h1>Tambah warung</h1>

            <p class="muted">
                Buat akun pemilik dan alamat publik warung.
            </p>
        </header>

        <?php if ($errors): ?>
            <div class="errors" role="alert" aria-live="polite">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="form-card">
            <form
                method="post"
                action="/admin/tambah-warung.php"
                autocomplete="off"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="form-grid">
                    <label class="field">
                        <span>Nama warung *</span>

                        <input
                            name="name"
                            value="<?= e($name) ?>"
                            maxlength="80"
                            autocomplete="organization"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Nama pemilik *</span>

                        <input
                            name="owner_name"
                            value="<?= e($owner) ?>"
                            maxlength="80"
                            autocomplete="name"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Username login *</span>

                        <input
                            name="username"
                            value="<?= e($username) ?>"
                            minlength="3"
                            maxlength="50"
                            pattern="[a-zA-Z0-9_.-]+"
                            autocomplete="off"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                        >
                    </label>

                    <label class="field">
                        <span>Nomor WhatsApp</span>

                        <input
                            name="phone"
                            type="tel"
                            value="<?= e($phone) ?>"
                            maxlength="25"
                            autocomplete="tel"
                        >
                    </label>

                    <label class="field full">
                        <span>Alamat warung *</span>

                        <input
                            name="address"
                            value="<?= e($address) ?>"
                            maxlength="180"
                            autocomplete="street-address"
                            required
                        >
                    </label>

                    <label class="field full">
                        <span>Slug URL</span>

                        <input
                            name="slug"
                            value="<?= e($slug) ?>"
                            maxlength="80"
                            placeholder="Contoh: warung-erwan"
                            pattern="[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*"
                            autocapitalize="none"
                            spellcheck="false"
                        >

                        <small class="muted">
                            Kosongkan untuk membuat slug berdasarkan nama
                            warung. Slug akan dinormalisasi menjadi huruf kecil.
                        </small>
                    </label>

                    <label class="field full">
                        <span>Deskripsi</span>

                        <textarea
                            name="description"
                            maxlength="500"
                            rows="4"
                        ><?= e($description) ?></textarea>
                    </label>

                    <label class="field full">
                        <span>Kata sandi awal *</span>

                        <input
                            name="password"
                            type="password"
                            minlength="12"
                            maxlength="4096"
                            autocomplete="new-password"
                            required
                        >

                        <small class="muted">
                            Minimal 12 karakter. Kata sandi disimpan
                            dalam bentuk hash menggunakan password_hash().
                        </small>
                    </label>
                </div>

                <div class="form-actions">
                    <button
                        class="button button-primary"
                        type="submit"
                    >
                        Buat akun warung
                    </button>

                    <a
                        class="button button-outline"
                        href="/admin/warung.php"
                    >
                        Batal
                    </a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
