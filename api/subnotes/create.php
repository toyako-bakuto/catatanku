<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metode request tidak diizinkan.', 405);
}

$userId = requireLogin();
$input  = getJsonInput();

$noteId = filter_var($input['note_id'] ?? null, FILTER_VALIDATE_INT);
$content = $input['content'] ?? null;

if ($noteId === false || $noteId === null || $noteId < 1) {
    jsonError('ID catatan tidak valid.');
}

if (!is_string($content)) {
    jsonError('Isi subcatatan harus berupa teks.');
}

$content = trim($content);

if ($content === '') {
    jsonError('Isi subcatatan tidak boleh kosong.');
}

if (mb_strlen($content) > 5000) {
    jsonError('Isi subcatatan maksimal 5000 karakter.');
}

$checkNote = $pdo->prepare(
    'SELECT id
     FROM notes
     WHERE id = :note_id
       AND user_id = :user_id
     LIMIT 1'
);
$checkNote->execute([
    'note_id' => $noteId,
    'user_id' => $userId,
]);

if (!$checkNote->fetch()) {
    jsonError('Catatan tidak ditemukan.', 404);
}

$stmt = $pdo->prepare(
    'INSERT INTO subnotes (note_id, content)
     VALUES (:note_id, :content)'
);
$stmt->execute([
    'note_id' => $noteId,
    'content' => $content,
]);

jsonSuccess([
    'id'      => (int) $pdo->lastInsertId(),
    'note_id' => (int) $noteId,
    'content' => $content,
], 'Subcatatan berhasil ditambahkan.');