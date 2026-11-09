<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../helpers/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Metode request tidak diizinkan.', 405);
}

$userId = requireLogin();

/* ==========================================
 * 1. PAGINATION
 * FIX: Hormati parameter "limit" dari frontend (dibatasi 50).
 * ========================================== */

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page !== false && $page !== null && $page > 0) ? $page : 1;

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
$limit = ($limit !== false && $limit !== null && $limit > 0 && $limit <= 50)
    ? $limit
    : 10;

$offset = ($page - 1) * $limit;

/* ==========================================
 * 2. FILTER & SORT
 * FIX: Baca "category" dan "sort" yang selama ini diabaikan.
 * ========================================== */

$category = $_GET['category'] ?? 'all';

$filterSql    = '';
$filterParams = [];

if ($category !== 'all' && ctype_digit((string) $category)) {
    $filterSql = ' AND notes.category_id = :category_id';
    $filterParams['category_id'] = (int) $category;
}

$sort = (strtolower((string) ($_GET['sort'] ?? 'desc')) === 'asc')
    ? 'ASC'
    : 'DESC';

/* ==========================================
 * 3. TOTAL CATATAN
 * ========================================== */

$countStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM notes
     WHERE user_id = :user_id' . $filterSql
);

$countStmt->execute(['user_id' => $userId] + $filterParams);
$totalNotes = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalNotes / $limit));

/* ==========================================
 * 4. AMBIL CATATAN
 * ========================================== */

$notesStmt = $pdo->prepare(
    "SELECT
        notes.id,
        notes.content,
        notes.note_date AS date,
        notes.category_id,
        notes.created_at,
        notes.updated_at,
        categories.id    AS joined_category_id,
        categories.name  AS category_name,
        categories.color AS category_color
     FROM notes
     LEFT JOIN categories
        ON categories.id = notes.category_id
       AND categories.user_id = notes.user_id
     WHERE notes.user_id = :user_id{$filterSql}
     ORDER BY notes.note_date {$sort}, notes.id {$sort}
     LIMIT :limit OFFSET :offset"
);

$notesStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

foreach ($filterParams as $key => $val) {
    $notesStmt->bindValue(':' . $key, $val, PDO::PARAM_INT);
}

$notesStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$notesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$notesStmt->execute();

$notes = $notesStmt->fetchAll();

/* ==========================================
 * 5. SUBNOTES UNTUK HALAMAN INI
 * ========================================== */

if (!empty($notes)) {
    $noteIds      = array_column($notes, 'id');
    $placeholders = implode(', ', array_fill(0, count($noteIds), '?'));

    $subnotesStmt = $pdo->prepare(
        "SELECT id, note_id, content, created_at
         FROM subnotes
         WHERE note_id IN ($placeholders)
         ORDER BY id ASC"
    );

    $subnotesStmt->execute($noteIds);
    $allSubnotes = $subnotesStmt->fetchAll();

    $subnotesByNote = [];
    foreach ($allSubnotes as $subnote) {
        $subnote['id']      = (int) $subnote['id'];
        $subnote['note_id'] = (int) $subnote['note_id'];
        $subnotesByNote[$subnote['note_id']][] = $subnote;
    }

    foreach ($notes as &$note) {
        $noteId = (int) $note['id'];
        $note['id'] = $noteId;

        $note['category'] = $note['joined_category_id'] !== null
            ? [
                'id'    => (int) $note['joined_category_id'],
                'name'  => $note['category_name'],
                'color' => $note['category_color'],
            ]
            : null;

        $note['subnotes'] = $subnotesByNote[$noteId] ?? [];

        unset(
            $note['joined_category_id'],
            $note['category_name'],
            $note['category_color']
        );
    }
    unset($note);
}

/* ==========================================
 * 6. STATISTIK DASHBOARD
 * ========================================== */

$startOfMonth     = date('Y-m-01');
$startOfNextMonth = date('Y-m-01', strtotime('first day of next month'));

$subnotesCountStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM subnotes
     INNER JOIN notes ON notes.id = subnotes.note_id
     WHERE notes.user_id = :user_id'
);
$subnotesCountStmt->execute(['user_id' => $userId]);
$totalSubnotes = (int) $subnotesCountStmt->fetchColumn();

$thisMonthStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM notes
     WHERE user_id = :user_id
       AND note_date >= :start_date
       AND note_date <  :end_date'
);
$thisMonthStmt->execute([
    'user_id'    => $userId,
    'start_date' => $startOfMonth,
    'end_date'   => $startOfNextMonth,
]);
$thisMonthNotes = (int) $thisMonthStmt->fetchColumn();

/* ==========================================
 * 7. RESPONS
 * ========================================== */

jsonSuccess([
    'notes' => $notes,
    'pagination' => [
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => $totalPages,
        'total'       => $totalNotes,
    ],
    'stats' => [
        'total_notes'    => $totalNotes,
        'total_subnotes' => $totalSubnotes,
        'this_month'     => $thisMonthNotes,
    ],
], 'Data catatan berhasil diambil.');