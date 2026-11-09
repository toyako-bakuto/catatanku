<?php

declare(strict_types=1);

require __DIR__ . '/../helpers/helper.php';

bootSession();

if (empty($_SESSION['user_id'])) {
    jsonSuccess([
        'logged_in' => false,
    ], 'Pengguna belum login.');
}

$userId    = (int) $_SESSION['user_id'];
$userName  = (string) ($_SESSION['user_name'] ?? '');
/* FIX: Kirim email agar dashboard bisa menampilkannya. */
$userEmail = (string) ($_SESSION['user_email'] ?? '');

jsonSuccess([
    'logged_in' => true,
    'user' => [
        'id'    => $userId,
        'name'  => $userName,
        'email' => $userEmail,
    ],
], 'Session aktif.');