<?php
// seeder.php — Buat akun admin default
// Akses sekali di browser: http://localhost/kasir/seeder.php
// HAPUS file ini setelah dijalankan (alasan keamanan)

require_once 'config.php';

$users = [
    [
        'username' => 'Fauzan',
        'password' => 'Fauzan123',
        'nama'     => 'Administrator',
        'role'     => 'admin',
    ],
    [
        'username' => 'Farel',
        'password' => 'Farel123',
        'nama'     => 'Kasir Satu',
        'role'     => 'kasir',
    ],
    [
        'username' => 'Akbar',
        'password' => 'Akbar123',
        'nama'     => 'Kasir Dua',
        'role'     => 'kasir',
    ],
];

$inserted = 0;
$skipped  = 0;

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'ssss', $u['username'], $hash, $u['nama'], $u['role']);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $inserted++;
    } else {
        $skipped++;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Seeder - Sistem Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .bg-indigo-950 { background-color: #1e1b4b; }
        .from-indigo-950 { --tw-gradient-from: #1e1b4b; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(30, 27, 75, 0)); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full text-center">
        <div class="text-5xl mb-4">🌱</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Seeder Berhasil!</h1>
        <p class="text-gray-500 mb-6">
            <?= $inserted ?> akun baru dibuat, <?= $skipped ?> akun sudah ada (dilewati).
        </p>
        <table class="w-full text-sm text-left border rounded-lg overflow-hidden mb-6">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-2">Username</th>
                    <th class="px-4 py-2">Password</th>
                    <th class="px-4 py-2">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="border-t">
                    <td class="px-4 py-2 font-mono font-medium"><?= e($u['username']) ?></td>
                    <td class="px-4 py-2 font-mono"><?= e($u['password']) ?></td>
                    <td class="px-4 py-2 capitalize"><?= e($u['role']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="login.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition">
            Pergi ke Halaman Login →
        </a>
    </div>
</body>
</html>