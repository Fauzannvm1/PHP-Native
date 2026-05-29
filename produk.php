<?php
// produk.php — Manajemen Produk (CRUD)
require_once 'config.php';
requireLogin();

$pageTitle = 'Manajemen Produk';

// PROSES POST — Tambah / Edit / Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- TAMBAH PRODUK ----
    if ($action === 'tambah') {
        $kode       = trim($_POST['kode_produk']  ?? '');
        $nama       = trim($_POST['nama_produk']  ?? '');
        $kategori   = trim($_POST['kategori']     ?? 'Umum');
        $harga      = (float)str_replace(['.', ','], ['', '.'], $_POST['harga'] ?? '0');
        $stok       = (int)($_POST['stok']        ?? 0);

        if ($kode === '' || $nama === '' || $harga <= 0) {
            flashError('Kode produk, nama, dan harga wajib diisi dengan benar.');
        } else {
            $stmt = mysqli_prepare($conn,
                'INSERT INTO produk (kode_produk, nama_produk, kategori, harga, stok)
                 VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'sssdi', $kode, $nama, $kategori, $harga, $stok);

            if (mysqli_stmt_execute($stmt)) {
                flashSuccess("Produk \"$nama\" berhasil ditambahkan.");
            } else {
                // Cek duplicate entry
                if (mysqli_errno($conn) === 1062) {
                    flashError("Kode produk \"$kode\" sudah digunakan. Gunakan kode lain.");
                } else {
                    flashError('Gagal menyimpan produk: ' . mysqli_error($conn));
                }
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: produk.php');
        exit;
    }

    // ---- EDIT PRODUK ----
    if ($action === 'edit') {
        $id         = (int)($_POST['id']           ?? 0);
        $kode       = trim($_POST['kode_produk']   ?? '');
        $nama       = trim($_POST['nama_produk']   ?? '');
        $kategori   = trim($_POST['kategori']      ?? 'Umum');
        $harga      = (float)str_replace(['.', ','], ['', '.'], $_POST['harga'] ?? '0');
        $stok       = (int)($_POST['stok']         ?? 0);

        if ($id <= 0 || $kode === '' || $nama === '' || $harga <= 0) {
            flashError('Data tidak valid. Periksa kembali isian form.');
        } else {
            $stmt = mysqli_prepare($conn,
                'UPDATE produk SET kode_produk=?, nama_produk=?, kategori=?, harga=?, stok=?
                 WHERE id=?'
            );
            mysqli_stmt_bind_param($stmt, 'sssdii', $kode, $nama, $kategori, $harga, $stok, $id);

            if (mysqli_stmt_execute($stmt)) {
                flashSuccess("Produk \"$nama\" berhasil diperbarui.");
            } else {
                if (mysqli_errno($conn) === 1062) {
                    flashError("Kode produk \"$kode\" sudah digunakan produk lain.");
                } else {
                    flashError('Gagal memperbarui produk: ' . mysqli_error($conn));
                }
            }
            mysqli_stmt_close($stmt);
        }
        header('Location: produk.php');
        exit;
    }

    // ---- HAPUS PRODUK ----
    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            flashError('ID produk tidak valid.');
        } else {
            // Ambil nama produk dulu
            $stmtCheck = mysqli_prepare($conn, 'SELECT nama_produk FROM produk WHERE id = ?');
            mysqli_stmt_bind_param($stmtCheck, 'i', $id);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            $produkDel = mysqli_fetch_assoc($resCheck);
            mysqli_stmt_close($stmtCheck);

            if (!$produkDel) {
                flashError('Produk tidak ditemukan.');
            } else {
                $stmt = mysqli_prepare($conn, 'DELETE FROM produk WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $id);
                try {
                    if (mysqli_stmt_execute($stmt)) {
                        flashSuccess("Produk \"{$produkDel['nama_produk']}\" berhasil dihapus.");
                    } else {
                        flashError('Gagal menghapus produk. Produk mungkin memiliki data transaksi terkait.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flashError('Gagal menghapus produk karena masih ada riwayat transaksi yang menggunakan produk ini.');
                }
                mysqli_stmt_close($stmt);
            }
        }
        header('Location: produk.php');
        exit;
    }
}

// AMBIL DATA PRODUK
$search  = trim($_GET['q'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');

$where = '1=1';
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= ' AND (kode_produk LIKE ? OR nama_produk LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($kategoriFilter !== '') {
    $where   .= ' AND kategori = ?';
    $params[] = $kategoriFilter;
    $types   .= 's';
}

$sql  = "SELECT * FROM produk WHERE $where ORDER BY nama_produk ASC";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$produkResult = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Daftar kategori unik untuk filter
$katResult = mysqli_query($conn, 'SELECT DISTINCT kategori FROM produk ORDER BY kategori');

// Data produk untuk form aksi
$actionForm = $_GET['action'] ?? '';
$targetProduk = null;
if (($actionForm === 'edit_form' || $actionForm === 'hapus_form') && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $stmtEdit = mysqli_prepare($conn, 'SELECT * FROM produk WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtEdit, 'i', $targetId);
    mysqli_stmt_execute($stmtEdit);
    $editResult = mysqli_stmt_get_result($stmtEdit);
    $targetProduk = mysqli_fetch_assoc($editResult);
    mysqli_stmt_close($stmtEdit);
}

include 'includes/header.php';
?>

<!-- ===== TOOLBAR ===== -->
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">

    <!-- Search & Filter -->
    <form method="GET" action="" class="flex flex-1 flex-wrap gap-2">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="<?= e($search) ?>"
                   placeholder="Cari kode atau nama produk…"
                   class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
        </div>
        <select name="kategori"
                class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-[140px]">
            <option value="">Semua Kategori</option>
            <?php while ($k = mysqli_fetch_assoc($katResult)): ?>
            <option value="<?= e($k['kategori']) ?>"
                    <?= $kategoriFilter === $k['kategori'] ? 'selected' : '' ?>>
                <?= e($k['kategori']) ?>
            </option>
            <?php endwhile; ?>
        </select>
        <button type="submit"
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition">
            Filter
        </button>
        <?php if ($search !== '' || $kategoriFilter !== ''): ?>
        <a href="produk.php"
           class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm transition">
            Hapus Filter
        </a>
        <?php endif; ?>
    </form>

    <!-- Tombol Tambah -->
    <a href="?action=tambah_form"
            class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700
                   text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-200 transition
                   flex-shrink-0">
        <i class="fas fa-plus"></i>
        Tambah Produk
    </a>
</div>

<!-- ===== TABEL PRODUK ===== -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">Daftar Produk</h3>
        <span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
            <?= mysqli_num_rows($produkResult) ?> produk
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <th class="px-5 py-3 text-left w-10 font-semibold">#</th>
                    <th class="px-5 py-3 text-left font-semibold">Kode</th>
                    <th class="px-5 py-3 text-left font-semibold">Nama Produk</th>
                    <th class="px-5 py-3 text-left font-semibold">Kategori</th>
                    <th class="px-5 py-3 text-right font-semibold">Harga</th>
                    <th class="px-5 py-3 text-center font-semibold">Stok</th>
                    <th class="px-5 py-3 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (mysqli_num_rows($produkResult) === 0): ?>
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center text-gray-400">
                        <i class="fas fa-box-open text-4xl block mb-3 opacity-30"></i>
                        <p class="font-medium">Tidak ada produk ditemukan</p>
                        <p class="text-xs mt-1">Coba ubah kata kunci pencarian atau tambah produk baru</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php $no = 1; while ($p = mysqli_fetch_assoc($produkResult)): ?>
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-5 py-3.5 text-gray-400 text-xs"><?= $no++ ?></td>
                    <td class="px-5 py-3.5">
                        <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-lg">
                            <?= e($p['kode_produk']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 font-medium text-gray-800"><?= e($p['nama_produk']) ?></td>
                    <td class="px-5 py-3.5">
                        <span class="bg-indigo-50 text-indigo-700 text-xs px-2.5 py-1 rounded-full font-medium">
                            <?= e($p['kategori']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right font-semibold text-gray-800">
                        <?= formatRupiah($p['harga']) ?>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center min-w-[56px] px-2.5 py-1 rounded-full text-xs font-semibold
                                     <?php
                                        if ($p['stok'] == 0)        echo 'bg-red-100 text-red-700';
                                        elseif ($p['stok'] <= 10)   echo 'bg-orange-100 text-orange-700';
                                        else                        echo 'bg-green-100 text-green-700';
                                     ?>">
                            <?= $p['stok'] == 0 ? 'Habis' : $p['stok'] ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Edit -->
                            <a href="?action=edit_form&id=<?= $p['id'] ?>"
                                    class="w-8 h-8 flex items-center justify-center bg-amber-50
                                           hover:bg-amber-100 text-amber-600 rounded-lg transition"
                                    title="Edit">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
                            <!-- Hapus -->
                            <a href="?action=hapus_form&id=<?= $p['id'] ?>"
                                    class="w-8 h-8 flex items-center justify-center bg-red-50
                                           hover:bg-red-100 text-red-500 rounded-lg transition"
                                    title="Hapus">
                                <i class="fas fa-trash-can text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

     <!-- MODAL TAMBAH PRODUK -->
<?php if ($actionForm === 'tambah_form'): ?>
<div id="modalTambah"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-lg">
                <i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Tambah Produk Baru
            </h3>
            <a href="produk.php"
                    class="w-8 h-8 flex items-center justify-center text-gray-400
                           hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-times"></i>
            </a>
        </div>
        <form method="POST" action="produk.php" class="px-6 py-5 space-y-4">
            <input type="hidden" name="action" value="tambah">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Kode Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="kode_produk" required
                           placeholder="cth: PRD-011"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Kategori
                    </label>
                    <input type="text" name="kategori" value="Umum"
                           placeholder="cth: Makanan"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_produk" required
                           placeholder="cth: Mie Goreng Spesial"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Harga (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="harga" required min="1000"
                           placeholder="0"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Stok Awal
                    </label>
                    <input type="number" name="stok" value="0" min="0"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <a href="produk.php"
                        class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm
                               hover:bg-gray-50 transition font-medium text-center inline-block">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white
                               rounded-xl text-sm font-semibold transition shadow-md shadow-indigo-100">
                    <i class="fas fa-save mr-2"></i>Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

     <!-- MODAL EDIT PRODUK -->
<?php if ($actionForm === 'edit_form' && $targetProduk): ?>
<div id="modalEdit"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-lg">
                <i class="fas fa-pen text-amber-500 mr-2"></i>Edit Produk
            </h3>
            <a href="produk.php"
                    class="w-8 h-8 flex items-center justify-center text-gray-400
                           hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-times"></i>
            </a>
        </div>
        <form method="POST" action="produk.php" class="px-6 py-5 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $targetProduk['id'] ?>">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Kode Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="kode_produk" required value="<?= e($targetProduk['kode_produk']) ?>"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Kategori
                    </label>
                    <input type="text" name="kategori" value="<?= e($targetProduk['kategori']) ?>"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_produk" required value="<?= e($targetProduk['nama_produk']) ?>"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Harga (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="harga" required min="1000" value="<?= $targetProduk['harga'] ?>"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Stok
                    </label>
                    <input type="number" name="stok" min="0" value="<?= $targetProduk['stok'] ?>"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <a href="produk.php"
                        class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm
                               hover:bg-gray-50 transition font-medium text-center inline-block">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm
                               hover:bg-gray-50 transition font-medium text-center inline-block">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

     <!-- MODAL KONFIRMASI HAPUS -->
<?php if ($actionForm === 'hapus_form' && $targetProduk): ?>
<div id="modalHapus"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm text-center p-8">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash-can text-red-500 text-2xl"></i>
        </div>
        <h3 class="font-bold text-gray-800 text-lg mb-2">Hapus Produk?</h3>
        <p class="text-gray-500 text-sm mb-6">
            Anda akan menghapus produk <strong class="text-gray-800"><?= e($targetProduk['nama_produk']) ?></strong>.
            Tindakan ini tidak dapat dibatalkan.
        </p>
        <form method="POST" action="produk.php">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" name="id" value="<?= $targetProduk['id'] ?>">
            <div class="flex gap-3">
                <a href="produk.php"
                        class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm
                               hover:bg-gray-50 transition font-medium text-center inline-block">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white
                               rounded-xl text-sm font-semibold transition">
                    Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>



<?php include 'includes/footer.php'; ?>