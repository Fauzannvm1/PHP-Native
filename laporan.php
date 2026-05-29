<?php
// laporan.php — Laporan Transaksi
require_once 'config.php';
requireLogin();

$pageTitle = 'Laporan Transaksi';

// Filter parameter (No JS)
$tanggalMulai = $_GET['mulai'] ?? date('Y-m-01');
$tanggalAkhir = $_GET['akhir'] ?? date('Y-m-d');
$kasir = trim($_GET['kasir'] ?? '');

// Base Query
$whereSql = "WHERE DATE(t.created_at) >= ? AND DATE(t.created_at) <= ?";
$params = [$tanggalMulai, $tanggalAkhir];
$types = "ss";

if ($kasir !== '') {
    $whereSql .= " AND t.nama_kasir LIKE ?";
    $params[] = "%$kasir%";
    $types .= "s";
}

// Summary Query
$sqlSummary = "SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total), 0) as total_pendapatan 
               FROM transaksi t $whereSql";
$stmtSum = mysqli_prepare($conn, $sqlSummary);
mysqli_stmt_bind_param($stmtSum, $types, ...$params);
mysqli_stmt_execute($stmtSum);
$summaryResult = mysqli_stmt_get_result($stmtSum);
$summary = mysqli_fetch_assoc($summaryResult);
mysqli_stmt_close($stmtSum);

// Main Table Query
$sql = "SELECT t.id, t.no_transaksi, t.nama_kasir, t.total, t.bayar, t.kembalian, t.created_at,
               COUNT(d.id) AS jumlah_item
        FROM transaksi t
        LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id
        $whereSql
        GROUP BY t.id
        ORDER BY t.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Kumpulkan data ke array
$transaksiList = [];
while ($row = mysqli_fetch_assoc($result)) {
    $transaksiList[] = $row;
}
mysqli_stmt_close($stmt);

include 'includes/header.php';
?>

<div class="space-y-6">

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wide">Filter Laporan</h3>
        <form method="GET" action="laporan.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1.5">Tanggal Mulai</label>
                <input type="date" name="mulai" value="<?= e($tanggalMulai) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1.5">Tanggal Akhir</label>
                <input type="date" name="akhir" value="<?= e($tanggalAkhir) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1.5">Nama Kasir (Opsional)</label>
                <input type="text" name="kasir" value="<?= e($kasir) ?>" placeholder="Cari nama kasir..." class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-200 transition text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-6 text-white shadow-xl shadow-indigo-200 flex items-center gap-5">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-receipt text-2xl"></i>
            </div>
            <div>
                <p class="text-indigo-100 text-sm font-medium">Total Transaksi</p>
                <p class="text-3xl font-bold"><?= number_format($summary['total_transaksi']) ?></p>
            </div>
        </div>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-xl shadow-teal-200 flex items-center gap-5">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-800">Total Pendapatan</p>
                <p class="text-xl font-bold text-gray-800"><?= formatRupiah($summary['total_pendapatan']) ?></p>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Daftar Transaksi</h3>
            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-medium">
                <?= count($transaksiList) ?> data ditemukan
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <th class="px-6 py-4 text-left font-semibold">No. Transaksi</th>
                        <th class="px-6 py-4 text-left font-semibold">Waktu</th>
                        <th class="px-6 py-4 text-left font-semibold">Kasir</th>
                        <th class="px-6 py-4 text-center font-semibold">Item</th>
                        <th class="px-6 py-4 text-right font-semibold">Total</th>
                        <th class="px-6 py-4 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($transaksiList)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-folder-open text-4xl block mb-3 opacity-20"></i>
                            Tidak ada data transaksi pada periode ini.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transaksiList as $row): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg text-xs">
                                <?= e($row['no_transaksi']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-800 font-medium">
                            <?= e($row['nama_kasir']) ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600">
                            <?= $row['jumlah_item'] ?> item
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">
                            <?= formatRupiah($row['total']) ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="cetak_struk.php?id=<?= $row['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 text-indigo-500 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 transition" title="Cetak Struk">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
