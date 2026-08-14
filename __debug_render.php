<?php
session_start();
$_SESSION['cedula'] = 1;
$_SESSION['nombre'] = 'Test';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include __DIR__ . '/views/reposiciones.php';
$html = ob_get_clean();
printf("%s", substr($html, 0, 2000));
file_put_contents(__DIR__ . '/reposiciones_rendered.html', $html);
