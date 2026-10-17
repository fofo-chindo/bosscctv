<?php
session_start();

require_once 'koneksi.php';

include 'header.php';


/* =========================================================
   FILTER MEREK & PAGINASI
========================================================= */

$merekAktif = $_GET['merek'] ?? 'semua';
$merekId = ($merekAktif !== 'semua') ? (int)$merekAktif : 0;

// Konfigurasi Paginasi
$batas_produk = 10; // Jumlah produk per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $batas_produk;


/* =========================================================
   AMBIL SEMUA MEREK AKTIF
========================================================= */

$query_merek = $koneksi->query("
    SELECT id, nama_merek
    FROM brands
    WHERE is_active = 1
    ORDER BY nama_merek ASC
");


/* =========================================================
   HITUNG TOTAL & AMBIL PAKET CCTV (DENGAN LIMIT)
========================================================= */

if ($merekId > 0) {

    // 1. Hitung total data untuk merek ini
    $stmtCount = $koneksi->prepare("
        SELECT COUNT(id) AS total 
        FROM products 
        WHERE is_active = 1 AND is_featured = 1 AND brand_id = ?
    ");
    $stmtCount->bind_param("i", $merekId);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $total_data = $resultCount->fetch_assoc()['total'];
    $stmtCount->close();

    // 2. Ambil data dengan LIMIT
    $stmt = $koneksi->prepare("
        SELECT p.*, b.nama_merek
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.is_active = 1 AND p.is_featured = 1 AND p.brand_id = ?
        ORDER BY p.id DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("iii", $merekId, $offset, $batas_produk);
    $stmt->execute();
    $query_produk = $stmt->get_result();

} else {

    // 1. Hitung total semua data
    $queryCount = $koneksi->query("
        SELECT COUNT(id) AS total 
        FROM products 
        WHERE is_active = 1 AND is_featured = 1
    ");
    $total_data = $queryCount->fetch_assoc()['total'];

    // 2. Ambil data dengan LIMIT
    $query_produk = $koneksi->query("
        SELECT p.*, b.nama_merek
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.is_active = 1 AND p.is_featured = 1
        ORDER BY p.id DESC
        LIMIT $offset, $batas_produk
    ");
}

/* =========================================================
   JUMLAH & TOTAL HALAMAN
========================================================= */
$total_halaman = ceil($total_data / $batas_produk);
$jumlah_produk_ditampilkan = ($query_produk) ? $query_produk->num_rows : 0;


/* =========================================================
   NAMA MEREK AKTIF
========================================================= */

$namaMerekAktif = '';

if ($merekId > 0) {
    $stmtNama = $koneksi->prepare("SELECT nama_merek FROM brands WHERE id = ? LIMIT 1");
    $stmtNama->bind_param("i", $merekId);
    $stmtNama->execute();
    $hasilNama = $stmtNama->get_result();

    if ($hasilNama && $hasilNama->num_rows > 0) {
        $dataNama = $hasilNama->fetch_assoc();
        $namaMerekAktif = $dataNama['nama_merek'];
    }
    $stmtNama->close();
}
?>

<main class="flex-grow bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 min-h-screen relative">


    <!-- =====================================================
         GLOW BACKGROUND
    ====================================================== -->
    <div class="absolute top-0 right-1/4 w-[400px] h-[400px] bg-violet-500/20 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/3 left-1/4 w-[350px] h-[350px] bg-cyan-500/20 rounded-full blur-[90px] pointer-events-none"></div>


    <!-- =====================================================
         WATERMARK ICON
    ====================================================== -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-10 text-white">
        <i class="fa-solid fa-video absolute top-12 left-12 text-6xl transform -rotate-12"></i>
        <i class="fa-solid fa-camera absolute top-20 right-16 text-7xl transform rotate-12"></i>
        <i class="fa-solid fa-shield-halved absolute bottom-20 left-1/4 text-7xl transform rotate-6"></i>
        <i class="fa-solid fa-network-wired absolute bottom-12 right-1/3 text-6xl transform -rotate-12"></i>
        <i class="fa-solid fa-server absolute top-1/2 left-16 text-5xl transform rotate-45"></i>
        <i class="fa-solid fa-lock absolute top-1/3 right-20 text-5xl transform -rotate-45"></i>
    </div>


    <!-- =====================================================
         HERO / JUDUL
    ====================================================== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-12 md:pt-16 pb-8 text-center">
        <!-- BADGE -->
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/20 border border-orange-500/40 text-orange-300 font-bold tracking-widest text-xs uppercase mb-3 backdrop-blur-md shadow-sm">
            <i class="fa-solid fa-star"></i> Pilihan Terbaik
        </span>

        <!-- JUDUL -->
        <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-3 drop-shadow-md">
            Paket CCTV
        </h2>

        <!-- DESKRIPSI -->
        <p class="text-purple-100 max-w-xl mx-auto text-sm md:text-base leading-relaxed font-light">
            Pilihan perangkat keamanan terbaik yang kami rekomendasikan untuk kebutuhan Anda.
        </p>
    </div>


    <!-- =====================================================
         FILTER MEREK
         KECIL + RATA TENGAH
    ====================================================== -->
    <div class="sticky top-20 z-50 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 mb-4 transition-all">
        <!-- RATA TENGAH -->
        <div class="flex justify-center">
            <!-- KOTAK FILTER -->
            <div class="bg-[#111126]/95 backdrop-blur-xl border border-white/20 rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.4)] p-1.5 max-w-full overflow-x-auto hide-scrollbar">
                <div class="flex items-center gap-1 min-w-max">

                    <!-- =================================================
                         SEMUA MEREK
                    ================================================== -->
                    <a href="produk.php"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $merekAktif === 'semua' ? 'bg-gradient-to-r from-orange-500 to-orange-400 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                    >
                        <i class="fa-solid fa-layer-group text-[11px] <?= $merekAktif === 'semua' ? 'text-white' : 'text-orange-400' ?>"></i>
                        Semua Merek
                    </a>

                    <!-- =================================================
                         DAFTAR MEREK
                    ================================================== -->
                    <?php if ($query_merek && $query_merek->num_rows > 0): ?>
                        <?php while ($merek = $query_merek->fetch_assoc()): ?>
                            <?php
                            $merekIdItem = (int)$merek['id'];
                            $merekTerpilih = ((string)$merekAktif === (string)$merekIdItem);
                            ?>
                            <a href="produk.php?merek=<?= $merekIdItem ?>"
                               class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $merekTerpilih ? 'bg-gradient-to-r from-orange-500 to-orange-400 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                            >
                                <i class="fa-solid fa-tag text-[10px] <?= $merekTerpilih ? 'text-white' : 'text-orange-400' ?>"></i>
                                <?= htmlspecialchars($merek['nama_merek']) ?>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>


    <!-- =====================================================
         SEARCH PRODUK
    ====================================================== -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-5">
        <div class="relative max-w-2xl mx-auto">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            <input type="text" id="searchProduk" placeholder="Cari nama produk di halaman ini..." autocomplete="off"
                class="w-full bg-white border border-gray-200 rounded-xl pl-11 pr-11 py-3.5 text-sm text-gray-800 shadow-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
            <button type="button" id="clearSearchProduk" class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition items-center justify-center" aria-label="Hapus pencarian">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p id="hasilPencarianProduk" class="text-xs text-purple-200 text-center mt-2 hidden"></p>
    </div>

    <!-- =====================================================
         INFORMASI PRODUK
    ====================================================== -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center justify-between gap-4">
            <!-- INFORMASI MEREK -->
            <div>
                <?php if ($merekAktif === 'semua'): ?>
                    <p class="text-sm text-purple-200">
                        Halaman <?= $halaman_aktif ?> dari <?= $total_halaman ?: 1 ?>
                    </p>
                <?php else: ?>
                    <p class="text-sm text-purple-200">
                        Merek: <span class="font-bold text-white"><?= htmlspecialchars($namaMerekAktif) ?></span> 
                        <span class="opacity-70">(Hal <?= $halaman_aktif ?>)</span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- JUMLAH PRODUK -->
            <div class="text-right">
                <p class="text-sm text-purple-200">
                    Total
                    <span class="font-black text-white text-base">
                        <?= $total_data ?>
                    </span>
                    produk
                </p>
            </div>
        </div>
    </div>


    <!-- =====================================================
         GRID PRODUK
    ====================================================== -->
    <div class="pb-10 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-8">
                <?php if ($query_produk && $query_produk->num_rows > 0): ?>

                    <!-- =================================================
                         PRODUK
                    ================================================== -->
                    <?php while ($produk = $query_produk->fetch_assoc()): ?>

                        <!-- CARD PRODUK -->
                        <div class="produk-card bg-white rounded-2xl sm:rounded-[2rem] shadow-xl hover:-translate-y-2 transition-all duration-300 border-none outline-none ring-0 overflow-hidden flex flex-col group relative"
                             data-search="<?= htmlspecialchars(strtolower($produk['nama_produk'] . ' ' . ($produk['kode_sku'] ?? '') . ' ' . ($produk['nama_merek'] ?? '') . ' ' . ($produk['deskripsi'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">

                            <!-- BADGE -->
                            <div class="absolute top-2 left-2 sm:top-4 sm:left-4 z-20 bg-gradient-to-r from-orange-500 to-red-500 text-white text-[8px] sm:text-[10px] uppercase font-extrabold tracking-wider px-2 py-1 sm:px-3 sm:py-1.5 rounded-full shadow-md">
                                <i class="fa-solid fa-star mr-1"></i> Paket CCTV
                            </div>

                            <!-- GAMBAR -->
                            <div class="relative overflow-hidden bg-white h-32 sm:h-56 flex items-center justify-center p-3 sm:p-6">
                                <div class="absolute inset-0 bg-blue-50 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                                <?php if (!empty($produk['gambar']) && file_exists('uploads/' . $produk['gambar'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                                         class="w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-500 relative z-10" loading="lazy">
                                <?php else: ?>
                                    <div class="text-center text-gray-300 relative z-10">
                                        <i class="fa-solid fa-image text-3xl sm:text-5xl mb-1 sm:mb-2"></i>
                                        <p class="text-[10px] sm:text-sm">Tidak ada gambar</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- INFORMASI -->
                            <div class="p-3 sm:p-6 flex flex-col flex-grow bg-white">
                                <!-- RATING -->
                                <div class="flex items-center gap-1 mb-2 sm:mb-3">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fa-solid fa-star text-yellow-400 text-[8px] sm:text-xs"></i>
                                    <?php endfor; ?>
                                    <span class="text-[10px] sm:text-xs text-gray-400 ml-1">(5.0)</span>
                                </div>

                                <!-- MEREK -->
                                <?php if (!empty($produk['nama_merek'])): ?>
                                    <div class="mb-1 sm:mb-2">
                                        <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[10px] sm:text-xs font-bold">
                                            <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($produk['nama_merek']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- NAMA PRODUK -->
                                <h3 class="font-bold text-xs sm:text-lg text-gray-900 leading-tight mb-1 sm:mb-2 line-clamp-2">
                                    <?= htmlspecialchars($produk['nama_produk']) ?>
                                </h3>

                                <!-- DESKRIPSI -->
                                <p class="text-[10px] sm:text-sm text-gray-500 mb-3 sm:mb-5 line-clamp-2">
                                    <?= htmlspecialchars($produk['deskripsi'] ?? '') ?>
                                </p>

                                <!-- HARGA -->
                                <div class="space-y-1 sm:space-y-2 mb-3 sm:mb-5 mt-auto">
                                    <!-- CCTV SAJA -->
                                    <div class="bg-blue-50 rounded-lg sm:rounded-xl p-2 sm:p-3">
                                        <p class="text-[10px] sm:text-xs text-gray-500">CCTV Saja</p>
                                        <p class="text-xs sm:text-lg font-black text-blue-700">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>
                                    </div>

                                    <!-- CCTV + PEMASANGAN -->
                                    <div class="bg-orange-50 rounded-lg sm:rounded-xl p-2 sm:p-3">
                                        <p class="text-[10px] sm:text-xs text-gray-500">CCTV + Pemasangan</p>
                                        <p class="text-xs sm:text-lg font-black text-orange-600">
                                            Rp <?= number_format($produk['harga_pemasangan'], 0, ',', '.') ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- TOMBOL DETAIL PRODUK -->
                                <button type="button" onclick="bukaDetailProduk(this)"
                                    data-id="<?= (int)$produk['id'] ?>"
                                    data-nama="<?= htmlspecialchars($produk['nama_produk'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-merek="<?= htmlspecialchars($produk['nama_merek'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                    data-sku="<?= htmlspecialchars($produk['kode_sku'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                    data-deskripsi="<?= htmlspecialchars($produk['deskripsi'] ?? 'Tidak ada deskripsi produk.', ENT_QUOTES, 'UTF-8') ?>"
                                    data-harga="<?= (float)$produk['harga'] ?>"
                                    data-harga-pemasangan="<?= $produk['harga_pemasangan'] !== null ? (float)$produk['harga_pemasangan'] : 0 ?>"
                                    data-stok="<?= (int)$produk['stok'] ?>"
                                    data-gambar="<?= !empty($produk['gambar']) && file_exists('uploads/' . $produk['gambar']) ? htmlspecialchars('uploads/' . $produk['gambar'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                    class="w-full mb-2 sm:mb-3 border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-bold py-2 sm:py-3 px-2 sm:px-4 rounded-lg sm:rounded-xl transition flex items-center justify-center gap-1 sm:gap-2 text-[10px] sm:text-sm outline-none focus:outline-none"
                                >
                                    <i class="fa-solid fa-circle-info"></i> Lihat Detail
                                </button>

                                <!-- KERANJANG -->
                                <form action="tambah_keranjang.php" method="POST">
                                    <input type="hidden" name="id" value="<?= (int)$produk['id'] ?>">
                                    <label class="block text-[10px] sm:text-sm font-semibold text-gray-700 mb-1 sm:mb-2">
                                        Pilih Paket
                                    </label>
                                    <select name="paket" required class="w-full border border-gray-300 rounded-lg sm:rounded-xl px-2 py-2 sm:px-3 sm:py-3 mb-2 sm:mb-3 focus:ring-2 focus:ring-blue-500 text-[10px] sm:text-sm outline-none focus:outline-none">
                                        <option value="cctv">CCTV Saja</option>
                                        <option value="pemasangan">CCTV + Pemasangan</option>
                                    </select>
                                    <button type="submit" class="w-full bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-800 hover:to-blue-700 text-white font-bold py-2 sm:py-3 px-2 sm:px-4 rounded-lg sm:rounded-xl transition shadow-md flex items-center justify-center gap-1 sm:gap-2 text-[10px] sm:text-sm outline-none focus:outline-none">
                                        <i class="fa-solid fa-cart-plus"></i> Keranjang
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <!-- =================================================
                         TIDAK ADA PRODUK
                    ================================================== -->
                    <div class="col-span-2 lg:col-span-4 text-center py-20 bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 shadow-xl text-white">
                        <i class="fa-solid fa-video text-5xl text-yellow-400 mb-4 block"></i>
                        <h3 class="text-xl font-bold">Tidak Ada Produk</h3>
                        <p class="text-purple-200 mt-2">
                            <?php if ($merekAktif === 'semua'): ?>
                                Belum ada Paket CCTV di halaman ini.
                            <?php else: ?>
                                Belum ada Paket CCTV untuk merek yang dipilih di halaman ini.
                            <?php endif; ?>
                        </p>
                        <?php if ($merekAktif !== 'semua' || $halaman_aktif > 1): ?>
                            <a href="produk.php" class="inline-flex items-center gap-2 mt-6 bg-orange-500 hover:bg-orange-600 text-white font-bold px-5 py-3 rounded-xl transition">
                                <i class="fa-solid fa-rotate-left"></i> Kembali ke Awal
                            </a>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            </div>

            <!-- =====================================================
                 KOTAK PAGINASI (HALAMAN) DI BAWAH GRID
            ====================================================== -->
            <?php if ($total_halaman > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-12 mb-8">
                    
                    <!-- Tombol Prev -->
                    <?php if ($halaman_aktif > 1): ?>
                        <a href="?merek=<?= $merekAktif ?>&halaman=<?= $halaman_aktif - 1 ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-orange-500 text-white font-bold transition shadow-sm border border-white/20">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Nomor Halaman -->
                    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                        <?php if ($i == $halaman_aktif): ?>
                            <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-orange-400 text-white font-black shadow-md border border-orange-400">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="?merek=<?= $merekAktif ?>&halaman=<?= $i ?>" 
                               class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-white/30 text-white font-bold transition shadow-sm border border-white/20">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Tombol Next -->
                    <?php if ($halaman_aktif < $total_halaman): ?>
                        <a href="?merek=<?= $merekAktif ?>&halaman=<?= $halaman_aktif + 1 ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-orange-500 text-white font-bold transition shadow-sm border border-white/20">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>
</main>


<!-- =========================================================
     MODAL DETAIL PRODUK
========================================================== -->
<div id="modalDetailProduk" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" aria-hidden="true">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="tutupDetailProduk()"></div>
    <div class="relative z-10 w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-3xl shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="detailNamaProduk">
        <div class="sticky top-0 z-20 flex items-center justify-between gap-4 px-6 py-4 bg-white border-b border-gray-100">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Detail Produk</p>
                <h3 id="detailNamaProduk" class="text-xl md:text-2xl font-black text-gray-900">-</h3>
            </div>
            <button type="button" onclick="tutupDetailProduk()" class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 hover:bg-red-50 text-gray-600 hover:text-red-600 transition flex items-center justify-center outline-none focus:outline-none" aria-label="Tutup detail produk">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="h-64 md:h-80 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-center overflow-hidden">
                    <img id="detailGambar" src="" alt="" class="w-full h-full object-contain p-5 hidden">
                    <div id="detailTanpaGambar" class="text-center text-gray-300">
                        <i class="fa-solid fa-image text-5xl mb-2"></i>
                        <p class="text-sm">Tidak ada gambar</p>
                    </div>
                </div>

                <div>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span id="detailMerek" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold">
                            <i class="fa-solid fa-tag"></i> -
                        </span>
                        <span id="detailStok" class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold">
                            <i class="fa-solid fa-box"></i> Stok: -
                        </span>
                    </div>
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">SKU</p>
                        <p id="detailSku" class="text-sm font-bold text-gray-800 mt-1">-</p>
                    </div>
                    <div class="mb-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Deskripsi</p>
                        <p id="detailDeskripsi" class="text-sm leading-relaxed text-gray-600">-</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="bg-blue-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500">CCTV Saja</p>
                            <p id="detailHarga" class="text-lg font-black text-blue-700 mt-1">Rp 0</p>
                        </div>
                        <div class="bg-orange-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500">CCTV + Pemasangan</p>
                            <p id="detailHargaPemasangan" class="text-lg font-black text-orange-600 mt-1">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button type="button" onclick="tutupDetailProduk()" class="px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-900 text-white font-bold text-sm transition outline-none focus:outline-none">Tutup</button>
        </div>
    </div>
</div>


<script>
/* =========================================================
   SEARCH PRODUK REAL-TIME
   (Catatan: Ini sekarang hanya memfilter produk di HALAMAN AKTIF)
========================================================= */
(function () {
    const input = document.getElementById('searchProduk');
    const clearButton = document.getElementById('clearSearchProduk');
    const info = document.getElementById('hasilPencarianProduk');
    const cards = document.querySelectorAll('.produk-card');
    const totalProduk = cards.length;

    if (!input) return;

    function jalankanPencarian() {
        const kataKunci = input.value.trim().toLowerCase();
        let jumlahDitemukan = 0;

        cards.forEach(function (card) {
            const dataSearch = (card.getAttribute('data-search') || '').toLowerCase();
            const cocok = kataKunci === '' || dataSearch.indexOf(kataKunci) !== -1;
            card.style.display = cocok ? '' : 'none';
            if (cocok) {
                jumlahDitemukan++;
            }
        });

        if (kataKunci !== '') {
            clearButton.classList.remove('hidden');
            clearButton.classList.add('flex');
            info.classList.remove('hidden');
            info.textContent = 'Ditemukan ' + jumlahDitemukan + ' dari ' + totalProduk + ' produk di halaman ini untuk "' + input.value.trim() + '"';
        } else {
            clearButton.classList.add('hidden');
            clearButton.classList.remove('flex');
            info.classList.add('hidden');
            info.textContent = '';
        }
    }

    input.addEventListener('input', jalankanPencarian);
    clearButton.addEventListener('click', function () {
        input.value = '';
        jalankanPencarian();
        input.focus();
    });
})();

/* =========================================================
   DETAIL PRODUK
========================================================= */
function formatHargaDetail(nilai) {
    const angka = Number(nilai) || 0;
    return 'Rp ' + angka.toLocaleString('id-ID');
}

function bukaDetailProduk(tombol) {
    const modal = document.getElementById('modalDetailProduk');
    if (!modal || !tombol) return;

    const nama = tombol.dataset.nama || '-';
    const merek = tombol.dataset.merek || '-';
    const sku = tombol.dataset.sku || '-';
    const deskripsi = tombol.dataset.deskripsi || 'Tidak ada deskripsi produk.';
    const harga = tombol.dataset.harga || 0;
    const hargaPemasangan = tombol.dataset.hargaPemasangan || 0;
    const stok = tombol.dataset.stok || 0;
    const gambar = tombol.dataset.gambar || '';

    document.getElementById('detailNamaProduk').textContent = nama;
    document.getElementById('detailMerek').innerHTML = '<i class="fa-solid fa-tag"></i> ' + escapeHtmlDetail(merek);
    document.getElementById('detailSku').textContent = sku;
    document.getElementById('detailDeskripsi').textContent = deskripsi;
    document.getElementById('detailStok').innerHTML = '<i class="fa-solid fa-box"></i> Stok: ' + escapeHtmlDetail(stok);
    document.getElementById('detailHarga').textContent = formatHargaDetail(harga);

    const hargaPemasanganEl = document.getElementById('detailHargaPemasangan');
    if (Number(hargaPemasangan) > 0) {
        hargaPemasanganEl.textContent = formatHargaDetail(hargaPemasangan);
    } else {
        hargaPemasanganEl.textContent = '-';
    }

    const gambarEl = document.getElementById('detailGambar');
    const tanpaGambarEl = document.getElementById('detailTanpaGambar');

    if (gambar) {
        gambarEl.src = gambar;
        gambarEl.alt = nama;
        gambarEl.classList.remove('hidden');
        tanpaGambarEl.classList.add('hidden');
    } else {
        gambarEl.src = '';
        gambarEl.alt = '';
        gambarEl.classList.add('hidden');
        tanpaGambarEl.classList.remove('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
}

function tutupDetailProduk() {
    const modal = document.getElementById('modalDetailProduk');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
}

function escapeHtmlDetail(teks) {
    return String(teks)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        tutupDetailProduk();
    }
});
</script>

<style>
/* 1. Mencegah layar tergeser ke samping (MENGHILANGKAN RUANG PUTIH DI KANAN LAYAR) */
html, body {
    overflow-x: hidden;
    width: 100%;
    position: relative;
}

/* 2. Mencegah highlight biru/abu-abu saat menekan tombol di HP */
* {
    -webkit-tap-highlight-color: transparent;
}

/* 3. Menyembunyikan scrollbar pada filter merek */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<?php include 'footer.php'; ?>