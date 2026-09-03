<?php
require_once __DIR__ . '/config/session.php';

if (!isset($_SESSION['cedula'])) {
    header('Location: public/login.php');
    exit;
}

header('Location: public/index.php');
exit;
