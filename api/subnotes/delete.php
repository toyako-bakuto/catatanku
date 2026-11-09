<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metode request tidak diizinkan.', 405);
}

$userId = requireLogin();
$input  = getJsonInput();

$subnoteId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
if ($subnoteId === false || $subnoteId === null || $subnoteId < 1) {
    jsonError('ID subcatatan tidak valid.');
}

$stmt = $pdo->prepare(
    'DELETE subnotes
     FROM subnotes
     INNER JOIN notes ON notes.id = subnotes.note_id
     WHERE subnotes.id = :id
       AND notes.user_id = :user_id'
);

$stmt->execute([
    'id'      => $subnoteId,
    'user_id' => $userId,
]);

if ($stmt->rowCount() === 0) {
    jsonError('Subcatatan tidak ditemukan.', 404);
}

jsonSuccess([], 'Subcatatan berhasil dihapus.');