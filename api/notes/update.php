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

$content  = $input['content'] ?? null;
$noteDate = $input['date'] ?? null;

if (!is_string($content) || trim($content) === '') {
    jsonError('Isi catatan wajib diisi.');
}

$content = trim($content);

/* FIX: Batas panjang di backend. */
if (mb_strlen($content) > 100000) {
    jsonError('Catatan maksimal 100000 karakter.');
}

if (!is_string($noteDate)) {
    jsonError('Tanggal tidak valid.');
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $noteDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $noteDate) {
    jsonError('Format tanggal tidak valid.');
}

$checkNote = $pdo->prepare(
    'SELECT id FROM notes WHERE id = :id AND user_id = :user_id'
);
$checkNote->execute([
    'id'      => $noteId,
    'user_id' => $userId,
]);

if (!$checkNote->fetch()) {
    jsonError('Catatan tidak ditemukan.', 404);
}

/* FIX: Pakai helper agar tidak duplikasi kode validasi kategori. */
$categoryId = null;
if (isset($input['category_id']) && $input['category_id'] !== '' && $input['category_id'] !== null) {
    $categoryId = filter_var($input['category_id'], FILTER_VALIDATE_INT);
    if ($categoryId === false || $categoryId < 1) {
        jsonError('Kategori tidak valid.');
    }
    $categoryId = assertCategoryOwned($pdo, (int) $categoryId, $userId);
}

$stmt = $pdo->prepare(
    'UPDATE notes
     SET content = :content,
         category_id = :category_id,
         note_date = :note_date
     WHERE id = :id
       AND user_id = :user_id'
);

$stmt->execute([
    'content'     => $content,
    'category_id' => $categoryId,
    'note_date'   => $noteDate,
    'id'          => $noteId,
    'user_id'     => $userId,
]);

jsonSuccess([], 'Catatan berhasil diperbarui.');