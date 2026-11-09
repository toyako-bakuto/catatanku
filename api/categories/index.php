<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method tidak diizinkan.', 405);
}

$userId = requireLogin();

$stmt = $pdo->prepare(
    'SELECT id, name, color, created_at
     FROM categories
     WHERE user_id = :user_id
     ORDER BY name ASC'
);

$stmt->execute(['user_id' => $userId]);
$categories = $stmt->fetchAll();

jsonSuccess([
    'categories' => $categories,
], 'Kategori berhasil diambil.');