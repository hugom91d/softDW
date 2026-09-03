<?php

if (session_status() === PHP_SESSION_NONE) {
    $duracionSesion = 28800;

    ini_set('session.gc_maxlifetime', (string) $duracionSesion);
    session_set_cookie_params([
        'lifetime' => $duracionSesion,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    setcookie(session_name(), session_id(), [
        'expires' => time() + $duracionSesion,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}