<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);

    exit('Not Found');
}

require_once dirname(__DIR__) . '/config/database.php';

$username = trim((string) ($argv[1] ?? ''));
$password = (string) ($argv[2] ?? '');

if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
    fwrite(
        STDERR,
        "Username harus 3–50 karakter dan menggunakan huruf, angka, titik, garis bawah, atau tanda hubung.\n"
    );

    exit(1);
}

if (strlen($password) < 12 || strlen($password) > 4096) {
    fwrite(
        STDERR,
        "Kata sandi harus memiliki panjang 12–4096 karakter.\n"
    );

    exit(1);
}

$pdo = database();

$stmt = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE username = :username
     LIMIT 1'
);

$stmt->execute([
    'username' => $username,
]);

if ($stmt->fetch()) {
    fwrite(STDERR, "Username sudah digunakan.\n");

    exit(1);
}

$insert = $pdo->prepare(
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

$insert->execute([
    'username' => $username,
    'password_hash' => password_hash(
        $password,
        PASSWORD_DEFAULT
    ),
    'role' => 'admin',
]);

fwrite(STDOUT, "Akun admin berhasil dibuat.\n");