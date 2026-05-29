<?php
// cetak_struk.php — Cetak Struk Transaksi
require_once 'config.php';
requireLogin();

$idTransaksi = (int)($_GET['id'] ?? 0);

if ($idTransaksi <= 0) {
    die("ID Transaksi tidak valid.");
}

// Ambil data transaksi
$stmt = mysqli_prepare($conn,
    'SELECT t.no_transaksi, t.nama_kasir, t.total, t.bayar, t.kembalian, t.created_at, u.nama AS nama_lengkap
     FROM transaksi t
     LEFT JOIN users u ON t.id_user = u.id
     WHERE t.id = ? LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'i', $idTransaksi);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$transaksi) {
    die("Data transaksi tidak ditemukan.");
}

// Ambil detail item
$stmtDet = mysqli_prepare($conn,
    'SELECT nama_produk, harga, qty, subtotal
     FROM detail_transaksi
     WHERE id_transaksi = ?'
);
mysqli_stmt_bind_param($stmtDet, 'i', $idTransaksi);
mysqli_stmt_execute($stmtDet);
$detailResult = mysqli_stmt_get_result($stmtDet);
mysqli_stmt_close($stmtDet);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?= e($transaksi['no_transaksi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        /* Gaya khusus untuk print */
        @media print {
            body { background: white; font-size: 12pt; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-area { box-shadow: none !important; max-width: 100% !important; border: none !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center py-10 min-h-screen text-gray-800 font-mono">

    <!-- Tombol Aksi (Tidak diprint) -->
    <div class="fixed bottom-6 right-6 flex flex-col gap-3 no-print">
        <!-- Untuk print, disarankan menekan Ctrl+P karena tidak menggunakan JS -->
        <a href="transaksi.php" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-3 rounded-xl shadow-xl flex items-center gap-2 transition">
            <i class="fas fa-arrow-left"></i> Kembali ke Kasir
        </a>
    </div>
    
    <div class="fixed bottom-24 right-6 flex flex-col gap-3 no-print text-sm bg-white p-3 rounded-lg shadow-lg border border-gray-200">
        <p><i class="fas fa-info-circle text-indigo-500 mr-1"></i> Tekan <strong>Ctrl + P</strong> untuk mencetak</p>
    </div>

    <!-- Area Struk -->
    <div class="print-area bg-white w-full max-w-[350px] sm:max-w-[400px] p-6 shadow-xl border border-gray-200">
        
        <!-- Header Struk -->
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase mb-1"><?= APP_NAME ?></h1>
            <p class="text-xs text-gray-500">Jl. Contoh Alamat No. 123, Kota</p>
            <p class="text-xs text-gray-500">Telp: 0812-3456-7890</p>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Info Transaksi -->
        <div class="text-xs space-y-1 mb-4">
            <div class="flex justify-between">
                <span>No. Nota</span>
                <span><?= e($transaksi['no_transaksi']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tanggal</span>
                <span><?= date('d/m/Y H:i', strtotime($transaksi['created_at'])) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Kasir</span>
                <span><?= e($transaksi['nama_kasir']) ?></span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Detail Item -->
        <div class="text-xs mb-4">
            <table class="w-full">
                <?php while ($item = mysqli_fetch_assoc($detailResult)): ?>
                <tr>
                    <td colspan="3" class="pt-2 font-semibold"><?= e($item['nama_produk']) ?></td>
                </tr>
                <tr>
                    <td class="pb-2 text-gray-600"><?= $item['qty'] ?> x <?= number_format($item['harga'], 0, ',', '.') ?></td>
                    <td class="pb-2 text-right" colspan="2"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Total -->
        <div class="text-xs space-y-1.5 font-semibold">
            <div class="flex justify-between text-sm">
                <span>Total</span>
                <span>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between font-normal">
                <span>Tunai</span>
                <span>Rp <?= number_format($transaksi['bayar'], 0, ',', '.') ?></span>
            </div>
            <div class="flex justify-between font-normal">
                <span>Kembali</span>
                <span>Rp <?= number_format($transaksi['kembalian'], 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Footer Struk -->
        <div class="text-center text-xs text-gray-500 mt-6">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
        </div>

    </div>

</body>
</html>
