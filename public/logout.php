<?php
require_once __DIR__ . '/partials/bootstrap.php';

logout_user();
header('Location: /index.php');
exit;
