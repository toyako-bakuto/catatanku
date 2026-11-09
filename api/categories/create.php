<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$userId = requireLogin();
$input  = getJsonInput();

$name  = trim((string) ($input['name'] ?? ''));
$color = trim((string) ($input['color'] ?? '#0D9488'));

if ($name === '') {
    jsonError('Nama kategori wajib diisi.');
}

/* FIX: mb_strlen agar hitungan karakter multibyte benar. */
if (mb_strlen($name) > 100) {
    jsonError('Nama kategori maksimal 100 karakter.');
}

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    jsonError('Format warna tidak valid.');
}

$stmt = $pdo->prepare(
    'SELECT id
     FROM categories
     WHERE user_id = :user_id
       AND name = :name
     LIMIT 1'
);

$stmt->execute([
    'user_id' => $userId,
    'name'    => $name,
]);

if ($stmt->fetch()) {
    jsonError('Kategori tersebut sudah tersedia.');
}

$stmt = $pdo->prepare(
    'INSERT INTO categories (user_id, name, color)
     VALUES (:user_id, :name, :color)'
);

try {
    $stmt->execute([
        'user_id' => $userId,
        'name'    => $name,
        'color'   => $color,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonError('Kategori tersebut sudah tersedia.', 409);
    }
    throw $e;
}

$categoryId = (int) $pdo->lastInsertId();

jsonSuccess([
    'id'    => $categoryId,
    'name'  => $name,
    'color' => $color,
], 'Kategori berhasil ditambahkan.');