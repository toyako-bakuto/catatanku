<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$userId = requireLogin();
$input  = getJsonInput();

$noteId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
if ($noteId === false || $noteId === null || $noteId < 1) {
    jsonError('ID catatan tidak valid.');
}

$stmt = $pdo->prepare(
    'DELETE FROM notes
     WHERE id = :id
       AND user_id = :user_id'
);

$stmt->execute([
    'id'      => $noteId,
    'user_id' => $userId,
]);

if ($stmt->rowCount() === 0) {
    jsonError('Catatan tidak ditemukan.', 404);
}

jsonSuccess([], 'Catatan berhasil dihapus.');