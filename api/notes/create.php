<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$userId = requireLogin();
$input  = getJsonInput();

/* FIX: Validasi tipe data sebelum trim. */
if (!is_string($input['content'] ?? null)) {
    jsonError('Isi catatan tidak valid.');
}

$content = trim($input['content']);
$noteDate = (string) ($input['date'] ?? date('Y-m-d'));

if ($content === '') {
    jsonError('Isi catatan wajib diisi.');
}

/* FIX: Batas panjang di backend (sama dengan frontend). */
if (mb_strlen($content) > 100000) {
    jsonError('Catatan maksimal 100000 karakter.');
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $noteDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $noteDate) {
    jsonError('Format tanggal tidak valid.');
}

/* FIX: Gunakan helper agar konsisten dengan update.php. */
$categoryId = null;
if (isset($input['category_id']) && $input['category_id'] !== '' && $input['category_id'] !== null) {
    $categoryId = filter_var($input['category_id'], FILTER_VALIDATE_INT);
    if ($categoryId === false || $categoryId < 1) {
        jsonError('Kategori tidak valid.');
    }
    $categoryId = assertCategoryOwned($pdo, (int) $categoryId, $userId);
}

/* FIX: Sub-note saat create sekarang disimpan (dalam transaksi). */
$subnote = '';
if (isset($input['subnote']) && is_string($input['subnote'])) {
    $subnote = trim($input['subnote']);
    if (mb_strlen($subnote) > 500) {
        jsonError('Sub-note maksimal 500 karakter.');
    }
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        'INSERT INTO notes (user_id, category_id, content, note_date)
         VALUES (:user_id, :category_id, :content, :note_date)'
    );

    $stmt->execute([
        'user_id'     => $userId,
        'category_id' => $categoryId,
        'content'     => $content,
        'note_date'   => $noteDate,
    ]);

    $noteId = (int) $pdo->lastInsertId();

    if ($subnote !== '') {
        $sub = $pdo->prepare(
            'INSERT INTO subnotes (note_id, content)
             VALUES (:note_id, :content)'
        );
        $sub->execute([
            'note_id' => $noteId,
            'content' => $subnote,
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

jsonSuccess([
    'id' => $noteId,
], 'Catatan berhasil dibuat.');