<?php
session_start();

if (!isset($_SESSION['cedula'])) {
    header('Location: public/login.php');
    exit;
}

header('Location: public/index.php');
exit;
