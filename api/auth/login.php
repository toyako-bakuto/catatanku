<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$input = getJsonInput();

$email    = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    jsonError('Email dan password wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Format email tidak valid.');
}

$stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash
     FROM users
     WHERE email = :email
     LIMIT 1'
);

$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Email atau password salah.', 401);
}

bootSession();
session_regenerate_id(true);

$_SESSION['user_id']    = (int) $user['id'];
$_SESSION['user_name']  = $user['name'];
/* FIX: Simpan email agar auth/me.php bisa menampilkannya di dashboard. */
$_SESSION['user_email'] = $user['email'];

/* FIX: Terapkan "Ingat saya" — perpanjang cookie session 30 hari. */
if (!empty($input['remember'])) {
    $params = session_get_cookie_params();

    setcookie(session_name(), session_id(), [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
}

jsonSuccess([
    'user' => [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
    ],
], 'Login berhasil.');