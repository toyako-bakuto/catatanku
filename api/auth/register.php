<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$input = getJsonInput();

$name     = trim((string) ($input['name'] ?? ''));
/* FIX: Email di-lowercase agar konsisten dengan login. */
$email    = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    jsonError('Semua field wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Format email tidak valid.');
}

/* FIX: mb_strlen agar konsisten untuk password multibyte. */
if (mb_strlen($password) < 8) {
    jsonError('Password minimal 8 karakter.');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    jsonError('Email sudah terdaftar.');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash)
     VALUES (:name, :email, :password_hash)'
);

try {
    $stmt->execute([
        'name'          => $name,
        'email'         => $email,
        'password_hash' => $passwordHash,
    ]);
} catch (PDOException $e) {
    /* FIX: Handle race condition duplikasi email. */
    if ($e->getCode() === '23000') {
        jsonError('Email sudah terdaftar.', 409);
    }
    throw $e;
}

jsonSuccess([], 'Registrasi berhasil.');