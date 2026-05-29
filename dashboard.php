<?php
// dashboard.php — Halaman Utama / Dashboard
require_once 'config.php';
requireLogin();

$pageTitle = 'Dashboard';

// --- Statistik Hari Ini ---
$today = date('Y-m-d');

// Total transaksi hari ini
$r = mysqli_query($conn,
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(total),0) AS pendapatan
     FROM transaksi WHERE DATE(created_at) = '$today'"
);
$statsHariIni = mysqli_fetch_assoc($r);

// Total produk aktif
$r2 = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM produk');
$totalProduk = mysqli_fetch_assoc($r2)['total'];

// Produk stok menipis (stok <= 10)
$r3 = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM produk WHERE stok <= 10');
$stokMenupis = mysqli_fetch_assoc($r3)['total'];

// Total transaksi sepanjang masa
$r4 = mysqli_query($conn, 'SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS grand FROM transaksi');
$statsTotal = mysqli_fetch_assoc($r4);

// --- Transaksi Terbaru (10 terakhir) ---
$recentResult = mysqli_query($conn,
    'SELECT t.no_transaksi, t.nama_kasir, t.total, t.bayar, t.kembalian, t.created_at,
            COUNT(d.id) AS jumlah_item
     FROM transaksi t
     LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id
     GROUP BY t.id
     ORDER BY t.created_at DESC
     LIMIT 10'
);

// --- Produk Stok Rendah ---
$lowStockResult = mysqli_query($conn,
    'SELECT kode_produk, nama_produk, stok, kategori
     FROM produk WHERE stok <= 10 ORDER BY stok ASC LIMIT 5'
);

include 'includes/header.php';
?>

<!-- ===== STAT CARDS ===== -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    <!-- Transaksi Hari Ini -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-receipt text-indigo-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Transaksi Hari Ini</p>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($statsHariIni['jumlah']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5">transaksi selesai</p>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-sack-dollar text-green-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pendapatan Hari Ini</p>
            <p class="text-xl font-bold text-gray-800"><?= formatRupiah($statsHariIni['pendapatan']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5"><?= date('d F Y') ?></p>
        </div>
    </div>

    <!-- Total Produk -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-boxes-stacked text-violet-600 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Produk</p>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($totalProduk) ?></p>
            <p class="text-xs text-gray-400 mt-0.5">jenis produk terdaftar</p>
        </div>
    </div>

    <!-- Stok Menipis -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-<?= $stokMenupis > 0 ? 'orange' : 'gray' ?>-100
                flex items-center gap-4 <?= $stokMenupis > 0 ? 'ring-1 ring-orange-200' : '' ?>">
        <div class="w-12 h-12 rounded-xl bg-<?= $stokMenupis > 0 ? 'orange' : 'gray' ?>-100
                    flex items-center justify-center flex-shrink-0">
            <i class="fas fa-triangle-exclamation text-<?= $stokMenupis > 0 ? 'orange' : 'gray' ?>-500 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Stok Menipis</p>
            <p class="text-2xl font-bold text-<?= $stokMenupis > 0 ? 'orange-600' : 'gray-800' ?>">
                <?= number_format($stokMenupis) ?>
            </p>
            <p class="text-xs text-gray-400 mt-0.5">produk (stok ≤ 10)</p>
        </div>
    </div>

</div>

<!-- ===== TABEL + SIDEBAR ===== -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- Transaksi Terbaru (2/3 lebar) -->
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Transaksi Terbaru</h3>
            <a href="laporan.php"
               class="text-xs text-indigo-600 hover:text-indigo-700 font-medium hover:underline">
                Lihat Semua →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <th class="px-5 py-3 text-left font-semibold">No. Transaksi</th>
                        <th class="px-5 py-3 text-left font-semibold">Kasir</th>
                        <th class="px-5 py-3 text-right font-semibold">Item</th>
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                        <th class="px-5 py-3 text-left font-semibold">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (mysqli_num_rows($recentResult) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl block mb-2 opacity-30"></i>
                            Belum ada transaksi
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($recentResult)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-indigo-700 font-medium text-xs
                                         bg-indigo-50 px-2 py-1 rounded-lg">
                                <?= e($row['no_transaksi']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-700"><?= e($row['nama_kasir']) ?></td>
                        <td class="px-5 py-3.5 text-right text-gray-600"><?= $row['jumlah_item'] ?> item</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-gray-800">
                            <?= formatRupiah($row['total']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs">
                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stok Menipis (1/3 lebar) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">⚠️ Stok Menipis</h3>
            <a href="produk.php"
               class="text-xs text-indigo-600 hover:text-indigo-700 font-medium hover:underline">
                Kelola →
            </a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (mysqli_num_rows($lowStockResult) === 0): ?>
            <div class="px-6 py-10 text-center text-gray-400">
                <i class="fas fa-circle-check text-3xl block mb-2 text-green-400"></i>
                <p class="text-sm">Semua stok aman!</p>
            </div>
            <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($lowStockResult)): ?>
            <div class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors">
                <div>
                    <p class="text-sm font-medium text-gray-800"><?= e($p['nama_produk']) ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e($p['kode_produk']) ?></p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                             <?= $p['stok'] == 0
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-orange-100 text-orange-700' ?>">
                    <?= $p['stok'] == 0 ? 'Habis' : $p['stok'] . ' tersisa' ?>
                </span>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ===== SHORTCUT AKSI ===== -->
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
    <a href="transaksi.php"
       class="group flex items-center gap-4 bg-indigo-600 hover:bg-indigo-700 text-white
              rounded-2xl p-5 shadow-lg shadow-indigo-200 transition">
        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <div>
            <p class="font-bold">Transaksi Baru</p>
            <p class="text-indigo-200 text-xs">Mulai penjualan baru</p>
        </div>
        <i class="fas fa-arrow-right ml-auto opacity-60 group-hover:translate-x-1 transition-transform"></i>
    </a>
    <a href="produk.php"
       class="group flex items-center gap-4 bg-white hover:bg-gray-50 border border-gray-200
              rounded-2xl p-5 shadow-sm transition">
        <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-boxes-stacked text-violet-600 text-xl"></i>
        </div>
        <div>
            <p class="font-bold text-gray-800">Kelola Produk</p>
            <p class="text-gray-400 text-xs">Tambah, edit, hapus produk</p>
        </div>
        <i class="fas fa-arrow-right ml-auto text-gray-300 group-hover:translate-x-1 transition-transform"></i>
    </a>
    <a href="laporan.php"
       class="group flex items-center gap-4 bg-white hover:bg-gray-50 border border-gray-200
              rounded-2xl p-5 shadow-sm transition">
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-chart-bar text-green-600 text-xl"></i>
        </div>
        <div>
            <p class="font-bold text-gray-800">Lihat Laporan</p>
            <p class="text-gray-400 text-xs">Riwayat dan rekap penjualan</p>
        </div>
        <i class="fas fa-arrow-right ml-auto text-gray-300 group-hover:translate-x-1 transition-transform"></i>
    </a>
</div>

<?php include 'includes/footer.php'; ?>