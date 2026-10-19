<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

require_method('POST');
verify_csrf();

driver_logout();

redirect('driver/login.php');
