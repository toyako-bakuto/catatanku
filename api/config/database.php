<?php

declare(strict_types=1);

/*
 * FIX: Tambahkan timezone konsisten agar date('Y-m-d') di server
 * sejalan dengan ekspektasi frontend.
 */
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$dbname = 'catatanku';
$username = 'catatanku';
$password = 'kawolz@!VPS';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    exit(json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal.',
    ], JSON_UNESCAPED_UNICODE));
}