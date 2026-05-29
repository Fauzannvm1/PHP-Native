<?php
// transaksi.php — Transaksi Baru (Kasir / POS)
require_once 'config.php';
requireLogin();

// Inisialisasi keranjang di session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// HANDLER FORM POST (Keranjang & Checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Tambah ke keranjang ---
    if ($action === 'tambah') {
        $produkId = (int)($_POST['produk_id'] ?? 0);
        $qty      = max(1, (int)($_POST['qty'] ?? 1));

        $stmt = mysqli_prepare($conn, 'SELECT id, nama_produk, harga, stok FROM produk WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $produkId);
        mysqli_stmt_execute($stmt);
        $res    = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$produk) {
            flashError('Produk tidak ditemukan.');
        } elseif ($produk['stok'] <= 0) {
            flashError('Stok produk habis.');
        } else {
            $currentQty = isset($_SESSION['cart'][$produkId]) ? $_SESSION['cart'][$produkId]['qty'] : 0;
            $newQty     = $currentQty + $qty;

            if ($newQty > $produk['stok']) {
                flashError("Stok tersedia hanya {$produk['stok']} unit.");
            } else {
                $_SESSION['cart'][$produkId] = [
                    'id'       => $produk['id'],
                    'nama'     => $produk['nama_produk'],
                    'harga'    => (float)$produk['harga'],
                    'stok_max' => (int)$produk['stok'],
                    'qty'      => $newQty,
                    'subtotal' => (float)$produk['harga'] * $newQty,
                ];
                flashSuccess("Produk ditambahkan ke keranjang.");
            }
        }
        header('Location: transaksi.php');
        exit;
    }

    // --- Update qty di keranjang ---
    if ($action === 'update_qty') {
        $produkId = (int)($_POST['produk_id'] ?? 0);
        $qty      = (int)($_POST['qty'] ?? 1);

        if ($qty <= 0) {
            unset($_SESSION['cart'][$produkId]);
        } elseif (isset($_SESSION['cart'][$produkId])) {
            $maxStok = $_SESSION['cart'][$produkId]['stok_max'];
            if ($qty > $maxStok) {
                flashError("Stok tersedia hanya $maxStok unit.");
            } else {
                $_SESSION['cart'][$produkId]['qty']      = $qty;
                $_SESSION['cart'][$produkId]['subtotal']  = $_SESSION['cart'][$produkId]['harga'] * $qty;
            }
        }
        header('Location: transaksi.php');
        exit;
    }

    // --- Hapus item dari keranjang ---
    if ($action === 'hapus') {
        $produkId = (int)($_POST['produk_id'] ?? 0);
        unset($_SESSION['cart'][$produkId]);
        header('Location: transaksi.php');
        exit;
    }

    // --- Kosongkan seluruh keranjang ---
    if ($action === 'kosongkan') {
        $_SESSION['cart'] = [];
        header('Location: transaksi.php');
        exit;
    }

    // --- Checkout ---
    $action = $_POST['action'] ?? '';

    if ($action === 'checkout') {
        $bayar = (float)str_replace(['.', ','], ['', '.'], $_POST['bayar'] ?? '0');

        if (empty($_SESSION['cart'])) {
            flashError('Keranjang kosong. Tambahkan produk terlebih dahulu.');
            header('Location: transaksi.php');
            exit;
        }

        // Hitung total dari session (jangan percaya angka dari client)
        $total = array_sum(array_column($_SESSION['cart'], 'subtotal'));

        if ($bayar < $total) {
            flashError('Jumlah bayar kurang dari total harga.');
            header('Location: transaksi.php');
            exit;
        }

        $kembalian   = $bayar - $total;
        $noTransaksi = generateNoTransaksi();
        $namaKasir   = $_SESSION['nama'];
        $idUser      = (int)$_SESSION['user_id'];

        // === Simpan header transaksi ===
        $stmtTrx = mysqli_prepare($conn,
            'INSERT INTO transaksi (no_transaksi, id_user, nama_kasir, total, bayar, kembalian)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmtTrx, 'sisddd',
            $noTransaksi, $idUser, $namaKasir, $total, $bayar, $kembalian
        );

        if (!mysqli_stmt_execute($stmtTrx)) {
            flashError('Gagal menyimpan transaksi: ' . mysqli_error($conn));
            header('Location: transaksi.php');
            exit;
        }

        $idTransaksi = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtTrx);

        // === Simpan detail + update stok (per item) ===
        foreach ($_SESSION['cart'] as $item) {
            $idProduk   = (int)$item['id'];
            $namaProduk = $item['nama'];
            $harga      = (float)$item['harga'];
            $qty        = (int)$item['qty'];
            $subtotal   = (float)$item['subtotal'];

            // Insert detail
            $stmtDet = mysqli_prepare($conn,
                'INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga, qty, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmtDet, 'iisdid',
                $idTransaksi, $idProduk, $namaProduk, $harga, $qty, $subtotal
            );
            mysqli_stmt_execute($stmtDet);
            mysqli_stmt_close($stmtDet);

            // Kurangi stok
            $stmtStok = mysqli_prepare($conn,
                'UPDATE produk SET stok = stok - ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($stmtStok, 'ii', $qty, $idProduk);
            mysqli_stmt_execute($stmtStok);
            mysqli_stmt_close($stmtStok);
        }

        // Kosongkan keranjang
        $_SESSION['cart'] = [];

        // Redirect ke struk
        header("Location: cetak_struk.php?id=$idTransaksi&baru=1");
        exit;
    }
}

// AMBIL DATA PRODUK UNTUK DITAMPILKAN
$searchProduk = trim($_GET['q'] ?? '');
$sqlProduk = 'SELECT id, kode_produk, nama_produk, kategori, harga, stok FROM produk
              WHERE stok > 0';

if ($searchProduk !== '') {
    $stmt = mysqli_prepare($conn,
        "$sqlProduk AND (nama_produk LIKE ? OR kode_produk LIKE ?) ORDER BY nama_produk ASC"
    );
    $like = "%$searchProduk%";
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $produkResult = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $produkResult = mysqli_query($conn, "$sqlProduk ORDER BY nama_produk ASC");
}

// Kategori untuk filter
$katResult = mysqli_query($conn,
    'SELECT DISTINCT kategori FROM produk WHERE stok > 0 ORDER BY kategori'
);

// Hitung total keranjang
$cartTotal = array_sum(array_column($_SESSION['cart'], 'subtotal'));
$cartCount = array_sum(array_column($_SESSION['cart'], 'qty'));

$pageTitle = 'Transaksi Baru';
include 'includes/header.php';
?>

<div class="flex gap-6 h-[calc(100vh-180px)]">

         <!-- KOLOM KIRI — Produk -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Search produk -->
        <form method="GET" action="transaksi.php" class="flex gap-3 mb-4 flex-shrink-0">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="q"
                       value="<?= e($searchProduk) ?>"
                       placeholder="Cari produk (nama / kode)…"
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm
                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
                <i class="fas fa-search mr-1"></i> Cari
            </button>
        </form>

        <!-- Grid Produk -->
        <div class="flex-1 overflow-y-auto pr-1">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3" id="produkGrid">
                <?php if (mysqli_num_rows($produkResult) === 0): ?>
                <div class="col-span-4 py-16 text-center text-gray-400">
                    <i class="fas fa-search text-3xl block mb-2 opacity-30"></i>
                    <p class="font-medium">Produk tidak ditemukan</p>
                </div>
                <?php else: ?>
                <?php while ($p = mysqli_fetch_assoc($produkResult)): ?>
                <form method="POST" action="transaksi.php" class="bg-white border border-gray-100 rounded-2xl p-4 hover:shadow-md hover:border-indigo-200
                            transition group select-none relative"
                     title="Klik untuk tambah ke keranjang">
                    <input type="hidden" name="action" value="tambah">
                    <input type="hidden" name="produk_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="absolute inset-0 w-full h-full cursor-pointer z-10 opacity-0"></button>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-3
                                group-hover:bg-indigo-100 transition relative z-0">
                        <i class="fas fa-box text-indigo-500 text-sm"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-xs leading-snug mb-1 line-clamp-2 relative z-0">
                        <?= e($p['nama_produk']) ?>
                    </p>
                    <p class="text-indigo-600 font-bold text-sm mb-2 relative z-0">
                        <?= formatRupiah($p['harga']) ?>
                    </p>
                    <div class="flex items-center justify-between relative z-0">
                        <span class="text-[10px] text-gray-400">Stok: <?= $p['stok'] ?></span>
                        <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">
                            <?= e($p['kategori']) ?>
                        </span>
                    </div>
                </form>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>


         <!-- KOLOM KANAN — Keranjang -->
    <div class="w-80 xl:w-96 flex-shrink-0 flex flex-col bg-white rounded-2xl
                border border-gray-100 shadow-sm overflow-hidden">

        <!-- Header Keranjang -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-cart-shopping text-indigo-500"></i>
                Keranjang
                <span id="cartBadge"
                      class="<?= $cartCount > 0 ? '' : 'hidden' ?> bg-indigo-600 text-white text-xs
                             font-bold w-5 h-5 rounded-full flex items-center justify-center">
                    <?= $cartCount ?>
                </span>
            </h3>
            <form method="POST" action="transaksi.php" class="inline m-0">
                <input type="hidden" name="action" value="kosongkan">
                <button type="submit"
                        class="text-xs text-red-400 hover:text-red-600 transition font-medium">
                    <i class="fas fa-trash-can mr-1"></i>Kosongkan
                </button>
            </form>
        </div>

        <!-- Daftar Item Keranjang -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2" id="cartItems">
            <?php if (empty($_SESSION['cart'])): ?>
            <div id="cartEmpty" class="flex flex-col items-center justify-center h-full text-center py-10">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-shopping-cart text-gray-300 text-2xl"></i>
                </div>
                <p class="text-gray-400 font-medium text-sm">Keranjang kosong</p>
                <p class="text-gray-300 text-xs mt-1">Klik produk untuk menambahkan</p>
            </div>
            <?php else: ?>
            <div id="cartEmpty" class="hidden flex flex-col items-center justify-center h-full text-center py-10">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-shopping-cart text-gray-300 text-2xl"></i>
                </div>
                <p class="text-gray-400 font-medium text-sm">Keranjang kosong</p>
                <p class="text-gray-300 text-xs mt-1">Klik produk untuk menambahkan</p>
            </div>
            <?php foreach ($_SESSION['cart'] as $item): ?>
            <div class="cart-item bg-gray-50 rounded-xl p-3" id="cartItem-<?= $item['id'] ?>">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <p class="text-xs font-semibold text-gray-800 leading-snug flex-1">
                        <?= e($item['nama']) ?>
                    </p>
                    <form method="POST" action="transaksi.php" class="inline m-0">
                        <input type="hidden" name="action" value="hapus">
                        <input type="hidden" name="produk_id" value="<?= $item['id'] ?>">
                        <button type="submit"
                                class="text-gray-300 hover:text-red-500 transition flex-shrink-0 -mt-0.5">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </form>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <form method="POST" action="transaksi.php" class="inline m-0 flex">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="produk_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="qty" value="<?= $item['qty'] - 1 ?>">
                            <button type="submit"
                                    class="w-6 h-6 rounded-lg bg-white border border-gray-200
                                           hover:bg-red-50 hover:border-red-200 text-gray-600
                                           flex items-center justify-center transition text-xs">
                                <i class="fas fa-minus"></i>
                            </button>
                        </form>
                        <span class="w-8 text-center text-sm font-bold text-gray-800"
                              id="qty-<?= $item['id'] ?>"><?= $item['qty'] ?></span>
                        <form method="POST" action="transaksi.php" class="inline m-0 flex">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="produk_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="qty" value="<?= $item['qty'] + 1 ?>">
                            <button type="submit"
                                    class="w-6 h-6 rounded-lg bg-white border border-gray-200
                                           hover:bg-green-50 hover:border-green-200 text-gray-600
                                           flex items-center justify-center transition text-xs">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                    <span class="text-sm font-bold text-indigo-600" id="subtotal-<?= $item['id'] ?>">
                        <?= formatRupiah($item['subtotal']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer Keranjang — Total + Bayar -->
        <div class="border-t border-gray-100 p-5 flex-shrink-0 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Subtotal</span>
                <span class="text-lg font-bold text-gray-800" id="grandTotal">
                    <?= formatRupiah($cartTotal) ?>
                </span>
            </div>

            <!-- Form Checkout -->
            <form method="POST" action="transaksi.php" id="formCheckout" class="flex flex-col space-y-3">
                <input type="hidden" name="action" value="checkout">
                
                <div>
                    <label class="block text-xs text-gray-500 font-medium mb-1.5 uppercase tracking-wide">
                        Jumlah Bayar (Minimal: <?= formatRupiah($cartTotal) ?>)
                    </label>
                    <input type="number" name="bayar" id="inputBayar"
                           placeholder="Masukkan nominal…"
                           min="<?= $cartTotal ?>" required
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 text-right
                                  font-semibold text-gray-800">
                </div>

                <button type="submit"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl
                               font-bold text-sm shadow-lg shadow-indigo-200 transition
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        <?= $cartCount == 0 ? 'disabled' : '' ?>
                        id="btnCheckout">
                    <i class="fas fa-check-circle mr-2"></i>
                    Bayar & Cetak Struk
                </button>
            </form>
        </div>
    </div>
</div>



<?php include 'includes/footer.php'; ?>