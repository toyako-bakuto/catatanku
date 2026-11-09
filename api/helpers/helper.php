<?php

declare(strict_types=1);

/**
 * Mengirim response JSON.
 */
function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Mengirim response sukses.
 */
function jsonSuccess(array $data = [], string $message = 'Berhasil.'): never
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data'    => $data,
    ]);
}

/**
 * Mengirim response gagal.
 */
function jsonError(string $message, int $statusCode = 400): never
{
    jsonResponse([
        'success' => false,
        'message' => $message,
    ], $statusCode);
}

/**
 * Membaca JSON dari request.
 *
 * FIX: Body kosong sekarang mengembalikan array kosong,
 * bukan melempar error "Format JSON tidak valid".
 */
function getJsonInput(): array
{
    $input = file_get_contents('php://input');

    if ($input === '' || $input === false) {
        return [];
    }

    $data = json_decode($input, true);

    if (!is_array($data)) {
        jsonError('Format JSON tidak valid.');
    }

    return $data;
}

/**
 * Memulai session dengan pengaturan keamanan dasar.
 */
function bootSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Memastikan pengguna sudah login.
 */
function requireLogin(): int
{
    bootSession();

    if (empty($_SESSION['user_id'])) {
        jsonError('Silakan login terlebih dahulu.', 401);
    }

    return (int) $_SESSION['user_id'];
}

/**
 * FIX: Helper baru — memastikan kategori milik user.
 * Dipakai di notes/create.php & notes/update.php (menghilangkan duplikasi).
 */
function assertCategoryOwned(PDO $pdo, ?int $categoryId, int $userId): ?int
{
    if ($categoryId === null) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id
         FROM categories
         WHERE id = :id
           AND user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute([
        'id'      => $categoryId,
        'user_id' => $userId,
    ]);

    if (!$stmt->fetch()) {
        jsonError('Kategori tidak ditemukan.', 404);
    }

    return $categoryId;
}