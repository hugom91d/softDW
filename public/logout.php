<?php
session_start(['cookie_path' => '/']);
$_SESSION = [];
session_destroy();
header('Location: ../public/login.php');
exit;