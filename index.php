<?php
// login.php — Halaman Login
require_once 'config.php';

// Sudah login? Langsung ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Ambil data user berdasarkan username (prepared statement)
        $stmt = mysqli_prepare($conn,
            'SELECT id, username, password, nama, role FROM users WHERE username = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerasi session ID untuk mencegah session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Polyfill for Tailwind v3 colors missing in v2 */
        .bg-indigo-950 { background-color: #1e1b4b; }
        .from-indigo-950 { --tw-gradient-from: #1e1b4b; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(30, 27, 75, 0)); }
    </style>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-800
             flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        <!-- Card Login -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

            <!-- Header Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-8 pt-10 pb-8 text-center">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cash-register text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= APP_NAME ?></h1>
                <p class="text-indigo-200 text-sm mt-1">Silakan masuk untuk melanjutkan</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8">

                <?php if ($error !== ''): ?>
                <div class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-200
                            text-red-700 px-4 py-3 rounded-xl text-sm">
                    <i class="fas fa-circle-exclamation mt-0.5 flex-shrink-0"></i>
                    <span><?= e($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>

                    <!-- Username -->
                    <div class="mb-5">
                        <label for="username"
                               class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Username
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= e($_POST['username'] ?? '') ?>"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                autofocus
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                       transition bg-gray-50 hover:bg-white"
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-7">
                        <label for="password"
                               class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl text-sm
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                       transition bg-gray-50 hover:bg-white"
                            >
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
                                   text-white font-semibold py-3 px-4 rounded-xl transition
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                                   shadow-lg shadow-indigo-200 text-sm">
                        <i class="fas fa-right-to-bracket mr-2"></i>
                        Masuk
                    </button>

                </form>

                <!-- Info akun default
                <div class="mt-6 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700 text-center">
                    <i class="fas fa-circle-info mr-1"></i>
                    Akun demo: <strong>admin</strong> / <strong>admin123</strong>
                    &nbsp;|&nbsp; Jalankan <code>seeder.php</code> terlebih dahulu
                </div> -->
            </div>
        </div>

        <p class="text-center text-indigo-300 text-xs mt-6">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Hak cipta dilindungi.
        </p>
    </div>
</body>
</html>