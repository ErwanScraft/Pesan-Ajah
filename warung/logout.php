<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

verify_csrf();

$user = require_warung();

logout_user();

redirect('/warung/login.php');