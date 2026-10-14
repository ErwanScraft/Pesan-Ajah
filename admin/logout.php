<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!request_is_post()) {
    http_response_code(405);
    header('Allow: POST');

    exit('Metode tidak diizinkan.');
}

verify_csrf();

logout_user();

redirect('/admin/login.php');