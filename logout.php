<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

logoutUser();
redirect('login.php');
