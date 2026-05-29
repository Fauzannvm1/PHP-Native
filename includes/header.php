<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Halaman') ?> — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        /* Polyfill for Tailwind v3 colors missing in v2 */
        .bg-indigo-950 { background-color: #1e1b4b; }
        .from-indigo-950 { --tw-gradient-from: #1e1b4b; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(30, 27, 75, 0)); }

        /* Animasi sederhana untuk flash message */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .flash-msg { animation: fadeInDown 0.3s ease; }

        /* Scrollbar tipis di sidebar */
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="flex h-screen overflow-hidden">

    <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-indigo-950 to-indigo-900
                  text-white flex flex-col shadow-2xl z-30">

        <!-- Brand -->
        <div class="px-5 py-5 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-cash-register text-white text-base"></i>
                </div>
                <div>
                    <p class="font-bold text-base leading-tight"><?= APP_NAME ?></p>
                    <p class="text-indigo-300 text-[11px]">Point of Sale</p>
                </div>
            </div>
        </div>

        <!-- Info User -->
        <div class="px-4 py-3 border-b border-white/10">
            <div class="flex items-center gap-3 bg-white/10 rounded-xl px-3 py-2.5">
                <div class="w-9 h-9 rounded-full bg-indigo-400 flex items-center justify-center
                            font-bold text-sm uppercase flex-shrink-0">
                    <?= substr($_SESSION['nama'] ?? 'U', 0, 1) ?>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm leading-tight truncate">
                        <?= e($_SESSION['nama'] ?? '') ?>
                    </p>
                    <p class="text-indigo-300 text-xs capitalize">
                        <?= e($_SESSION['role'] ?? '') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigasi -->
        <nav class="sidebar-nav flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <?php
            $currentFile = basename($_SERVER['PHP_SELF']);
            $navItems = [
                ['href' => 'dashboard.php',  'icon' => 'fa-house',         'label' => 'Dashboard'],
                ['href' => 'produk.php',      'icon' => 'fa-boxes-stacked', 'label' => 'Manajemen Produk'],
                ['href' => 'transaksi.php',   'icon' => 'fa-cart-shopping',  'label' => 'Transaksi Baru'],
                ['href' => 'laporan.php',     'icon' => 'fa-chart-bar',      'label' => 'Laporan'],
            ];
            foreach ($navItems as $nav):
                $isActive = ($currentFile === $nav['href']);
            ?>
            <a href="<?= $nav['href'] ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                      transition-all duration-150
                      <?= $isActive
                            ? 'bg-white/20 text-white shadow-sm'
                            : 'text-indigo-200 hover:bg-white/10 hover:text-white' ?>">
                <i class="fas <?= $nav['icon'] ?> w-4 text-center opacity-75"></i>
                <span><?= $nav['label'] ?></span>
                <?php if ($isActive): ?>
                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-indigo-300"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Logout -->
        <div class="px-3 py-4 border-t border-white/10">
            <a href="logout.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm
                      text-indigo-200 hover:bg-red-500 hover:text-white transition-all duration-150">
                <i class="fas fa-right-from-bracket w-4 text-center"></i>
                <span>Keluar</span>
            </a>
        </div>
    </aside>


    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-8 py-4
                       flex items-center justify-between flex-shrink-0 z-20 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-gray-900 leading-tight">
                    <?= e($pageTitle ?? 'Halaman') ?>
                </h1>
                <p class="text-xs text-gray-400 mt-0.5">
                    <?= date('l, d F Y', strtotime('now')) ?>
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50
                        px-4 py-2 rounded-full border border-gray-200">
                <i class="fas fa-clock text-indigo-500"></i>
                <span id="realtime-clock"><?= date('H:i:s') ?></span>
            </div>
        </header>

        <!-- Area Scroll untuk Konten -->
        <div class="flex-1 overflow-y-auto p-8">

            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash-msg mb-6 flex items-start gap-3 bg-green-50 border border-green-200
                        text-green-800 px-4 py-3.5 rounded-xl text-sm">
                <i class="fas fa-circle-check text-green-500 mt-0.5 flex-shrink-0"></i>
                <span><?= e($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash-msg mb-6 flex items-start gap-3 bg-red-50 border border-red-200
                        text-red-800 px-4 py-3.5 rounded-xl text-sm">
                <i class="fas fa-circle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
                <span><?= e($_SESSION['flash_error']) ?></span>
            </div>
            <?php unset($_SESSION['flash_error']); endif; ?>

