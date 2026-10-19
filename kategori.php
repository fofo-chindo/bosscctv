<?php
session_start();

require_once 'koneksi.php';

include 'header.php';

/* =========================================================
   FILTER KATEGORI, MEREK & PAGINASI
========================================================= */

$filterAktif = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
$merekAktif  = isset($_GET['merek']) ? $_GET['merek'] : 'semua';

$kategoriTerpilih = null;
$merekTerpilih    = null;
$produkList       = [];

// Konfigurasi Paginasi
$batas_produk = 10; // Jumlah produk per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $batas_produk;

/* =========================================================
   AMBIL SEMUA KATEGORI
========================================================= */

$queryKategori = "
    SELECT
        c.id,
        c.nama_kategori,
        c.slug,
        COUNT(
            DISTINCT CASE
                WHEN p.is_active = 1
                AND p.is_featured = 0
                THEN p.id
            END
        ) AS jumlah_produk
    FROM categories c
    LEFT JOIN product_categories pc
        ON pc.category_id = c.id
    LEFT JOIN products p
        ON p.id = pc.product_id
    GROUP BY c.id, c.nama_kategori, c.slug
    ORDER BY c.id ASC
";

$resultKategori = mysqli_query($koneksi, $queryKategori);
$kategoriList = [];

if ($resultKategori) {
    while ($row = mysqli_fetch_assoc($resultKategori)) {
        $kategoriList[] = $row;
    }
}

/* =========================================================
   AMBIL SEMUA MEREK AKTIF
========================================================= */

$queryMerek = "
    SELECT id, nama_merek, slug
    FROM brands
    WHERE is_active = 1
    ORDER BY nama_merek ASC
";

$resultMerek = mysqli_query($koneksi, $queryMerek);
$merekList = [];

if ($resultMerek) {
    while ($row = mysqli_fetch_assoc($resultMerek)) {
        $merekList[] = $row;
    }
}

/* =========================================================
   KATEGORI TERPILIH
========================================================= */

if ($filterAktif !== 'semua') {
    $stmtKategori = mysqli_prepare(
        $koneksi,
        "SELECT id, nama_kategori, slug
         FROM categories
         WHERE slug = ?
         LIMIT 1"
    );

    if ($stmtKategori) {
        mysqli_stmt_bind_param($stmtKategori, 's', $filterAktif);
        mysqli_stmt_execute($stmtKategori);
        $resultTerpilih = mysqli_stmt_get_result($stmtKategori);
        $kategoriTerpilih = mysqli_fetch_assoc($resultTerpilih);
        mysqli_stmt_close($stmtKategori);
    }
}

/* =========================================================
   MEREK TERPILIH
========================================================= */

if ($merekAktif !== 'semua') {
    $stmtMerek = mysqli_prepare(
        $koneksi,
        "SELECT id, nama_merek, slug
         FROM brands
         WHERE slug = ?
         AND is_active = 1
         LIMIT 1"
    );

    if ($stmtMerek) {
        mysqli_stmt_bind_param($stmtMerek, 's', $merekAktif);
        mysqli_stmt_execute($stmtMerek);
        $resultMerekTerpilih = mysqli_stmt_get_result($stmtMerek);
        $merekTerpilih = mysqli_fetch_assoc($resultMerekTerpilih);
        mysqli_stmt_close($stmtMerek);
    }
}

/* =========================================================
   HITUNG TOTAL PRODUK (UNTUK PAGINASI)
========================================================= */
$sqlCount = "
    SELECT COUNT(DISTINCT p.id) AS total
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    WHERE p.is_active = 1 AND p.is_featured = 0
";

$typesCount = '';
$paramsCount = [];

if ($kategoriTerpilih) {
    $sqlCount .= " AND EXISTS (
        SELECT 1 FROM product_categories pc_filter 
        WHERE pc_filter.product_id = p.id AND pc_filter.category_id = ?
    )";
    $typesCount .= 'i';
    $paramsCount[] = (int)$kategoriTerpilih['id'];
}

if ($merekTerpilih) {
    $sqlCount .= " AND p.brand_id = ? ";
    $typesCount .= 'i';
    $paramsCount[] = (int)$merekTerpilih['id'];
}

$stmtCount = mysqli_prepare($koneksi, $sqlCount);
if (!empty($paramsCount)) {
    mysqli_stmt_bind_param($stmtCount, $typesCount, ...$paramsCount);
}
mysqli_stmt_execute($stmtCount);
$resultCount = mysqli_stmt_get_result($stmtCount);
$total_data = mysqli_fetch_assoc($resultCount)['total'];
mysqli_stmt_close($stmtCount);

$total_halaman = ceil($total_data / $batas_produk);


/* =========================================================
   AMBIL PRODUK MULTI-KATEGORI (DENGAN LIMIT)
========================================================= */

$sqlProduk = "
    SELECT
        p.id,
        p.category_id,
        p.brand_id,
        p.kode_sku,
        p.nama_produk,
        p.deskripsi,
        p.harga,
        p.harga_pemasangan,
        p.stok,
        p.gambar,
        GROUP_CONCAT(
            DISTINCT c.nama_kategori
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS nama_kategori,
        GROUP_CONCAT(
            DISTINCT c.slug
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS kategori_slug,
        b.nama_merek,
        b.slug AS merek_slug
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    LEFT JOIN categories c ON c.id = pc.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.is_active = 1 AND p.is_featured = 0
";

$types = '';
$params = [];

if ($kategoriTerpilih) {
    $sqlProduk .= "
        AND EXISTS (
            SELECT 1
            FROM product_categories pc_filter
            WHERE pc_filter.product_id = p.id
            AND pc_filter.category_id = ?
        )
    ";
    $types .= 'i';
    $params[] = (int)$kategoriTerpilih['id'];
}

if ($merekTerpilih) {
    $sqlProduk .= " AND p.brand_id = ? ";
    $types .= 'i';
    $params[] = (int)$merekTerpilih['id'];
}

// Tambahkan LIMIT untuk paginasi
$sqlProduk .= "
    GROUP BY
        p.id, p.category_id, p.brand_id, p.kode_sku, p.nama_produk, p.deskripsi, p.harga, p.harga_pemasangan, p.stok, p.gambar, b.nama_merek, b.slug
    ORDER BY p.id DESC
    LIMIT ?, ?
";
$types .= 'ii';
$params[] = $offset;
$params[] = $batas_produk;


$stmtProduk = mysqli_prepare($koneksi, $sqlProduk);
if (!$stmtProduk) {
    die('Query produk gagal: ' . htmlspecialchars(mysqli_error($koneksi)));
}
if (!empty($params)) {
    mysqli_stmt_bind_param($stmtProduk, $types, ...$params);
}
mysqli_stmt_execute($stmtProduk);
$resultProduk = mysqli_stmt_get_result($stmtProduk);

while ($row = mysqli_fetch_assoc($resultProduk)) {
    $produkList[] = $row;
}
mysqli_stmt_close($stmtProduk);

/* =========================================================
   NAMA FILTER
========================================================= */

if ($kategoriTerpilih && $merekTerpilih) {
    $namaFilter = $kategoriTerpilih['nama_kategori'] . ' • ' . $merekTerpilih['nama_merek'];
} elseif ($kategoriTerpilih) {
    $namaFilter = $kategoriTerpilih['nama_kategori'];
} elseif ($merekTerpilih) {
    $namaFilter = $merekTerpilih['nama_merek'];
} else {
    $namaFilter = 'Semua Produk';
}
?>

<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative min-h-screen">

    <!-- WATERMARK -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-15 text-white">
        <i class="fa-solid fa-video absolute top-6 left-12 text-6xl transform -rotate-12"></i>
        <i class="fa-solid fa-camera absolute top-10 right-16 text-7xl transform rotate-12"></i>
        <i class="fa-solid fa-shield-halved absolute bottom-8 left-1/4 text-7xl transform rotate-6"></i>
        <i class="fa-solid fa-network-wired absolute bottom-10 right-1/3 text-6xl transform -rotate-12"></i>
        <i class="fa-solid fa-server absolute top-1/2 left-16 text-5xl transform rotate-45"></i>
        <i class="fa-solid fa-lock absolute top-1/3 right-20 text-5xl transform -rotate-45"></i>
        <i class="fa-solid fa-plug absolute bottom-12 left-20 text-4xl transform rotate-12"></i>
        <i class="fa-solid fa-eye absolute top-8 left-1/3 text-5xl transform -rotate-6"></i>
    </div>

    <!-- AREA KATEGORI -->
    <section id="area-kategori" class="relative z-10 pt-10 pb-4 scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl sm:text-5xl font-black text-white drop-shadow-md">
                Pilih Kategori Produk
            </h1>
            <p class="mt-2 text-purple-100 text-sm sm:text-base max-w-2xl mx-auto">
                Temukan berbagai produk CCTV dan perlengkapan keamanan sesuai dengan kebutuhan Anda.
            </p>
        </div>
    </section>

    <!-- =====================================================
         MENU KATEGORI + MEREK (SAMA PERSIS DENGAN PRODUK.PHP)
    ====================================================== -->
    <div class="sticky top-20 z-50 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 mb-4 transition-all space-y-2">
        
        <!-- BARIS 1: FILTER KATEGORI -->
        <div class="flex justify-center">
            <div class="bg-[#111126]/95 backdrop-blur-xl border border-white/20 rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.4)] p-1.5 pb-2.5 max-w-full overflow-x-auto custom-scrollbar">
                <div class="flex items-center gap-1 min-w-max">
                    <a href="kategori.php?kategori=semua&merek=<?= urlencode($merekAktif) ?>#area-kategori"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $filterAktif === 'semua' ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                    >
                        <i class="fa-solid fa-list text-[11px] <?= $filterAktif === 'semua' ? 'text-white' : 'text-blue-400' ?>"></i>
                        Semua Kategori
                    </a>

                    <?php foreach ($kategoriList as $item): ?>
                        <?php $kategoriTerpilihItem = ($filterAktif === $item['slug']); ?>
                        <a href="kategori.php?kategori=<?= urlencode($item['slug']) ?>&merek=<?= urlencode($merekAktif) ?>#area-kategori"
                           class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $kategoriTerpilihItem ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                        >
                            <?= htmlspecialchars($item['nama_kategori']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- BARIS 2: FILTER MEREK -->
        <div class="flex justify-center">
            <div class="bg-[#111126]/95 backdrop-blur-xl border border-white/20 rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.4)] p-1.5 pb-2.5 max-w-full overflow-x-auto custom-scrollbar">
                <div class="flex items-center gap-1 min-w-max">
                    <a href="kategori.php?kategori=<?= urlencode($filterAktif) ?>&merek=semua#area-kategori"
                       class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $merekAktif === 'semua' ? 'bg-gradient-to-r from-orange-500 to-orange-400 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                    >
                        <i class="fa-solid fa-layer-group text-[11px] <?= $merekAktif === 'semua' ? 'text-white' : 'text-orange-400' ?>"></i>
                        Semua Merek
                    </a>

                    <?php foreach ($merekList as $merek): ?>
                        <?php $merekTerpilihItem = ($merekAktif === $merek['slug']); ?>
                        <a href="kategori.php?kategori=<?= urlencode($filterAktif) ?>&merek=<?= urlencode($merek['slug']) ?>#area-kategori"
                           class="flex items-center gap-2 px-3.5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 <?= $merekTerpilihItem ? 'bg-gradient-to-r from-orange-500 to-orange-400 text-white shadow-md' : 'text-gray-200 hover:bg-white/10 hover:text-white' ?>"
                        >
                            <i class="fa-solid fa-tag text-[10px] <?= $merekTerpilihItem ? 'text-white' : 'text-orange-400' ?>"></i>
                            <?= htmlspecialchars($merek['nama_merek']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DAFTAR PRODUK -->
    <section class="relative z-10 pb-20 pt-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- PENCARIAN PRODUK -->
            <div class="mb-6">
                <div class="relative max-w-2xl mx-auto">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    <input type="text" id="searchProduk" placeholder="Cari produk di halaman ini..." autocomplete="off" class="w-full bg-white border border-gray-200 rounded-xl pl-11 pr-11 py-3.5 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="button" id="clearSearchProduk" class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition items-center justify-center" aria-label="Hapus pencarian">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <p id="hasilPencarianProduk" class="text-xs text-purple-200 text-center mt-2 hidden"></p>
            </div>

            <!-- INFORMASI FILTER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <p class="text-sm text-purple-200">
                        <?php if ($merekTerpilih && $kategoriTerpilih): ?>
                            Kategori & Merek
                        <?php elseif ($merekTerpilih): ?>
                            Merek
                        <?php else: ?>
                            Kategori
                        <?php endif; ?>
                    </p>
                    <h2 class="text-xl font-bold text-white">
                        <?= htmlspecialchars($namaFilter) ?>
                    </h2>
                </div>

                <div class="text-sm text-purple-200">
                    Menampilkan <span class="font-semibold text-white"><?= count($produkList) ?></span> dari <span class="font-semibold text-white"><?= $total_data ?></span> produk (Hal <?= $halaman_aktif ?>)
                </div>
            </div>

            <?php if (!empty($produkList)): ?>
                <!-- GRID DIUBAH MENJADI 2 KOLOM DI MOBILE (grid-cols-2) -->
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">

                    <?php foreach ($produkList as $produk): ?>
                        <?php
                        $daftarKategori = [];

                        if (!empty($produk['nama_kategori'])) {
                            $daftarKategori = array_filter(
                                array_map('trim', explode('|||', $produk['nama_kategori']))
                            );
                        }

                        $gambarPath = 'uploads/' . $produk['gambar'];

                        $deskripsi = !empty($produk['deskripsi'])
                            ? $produk['deskripsi']
                            : 'Belum ada deskripsi untuk produk ini.';

                        $deskripsiPendek = mb_strlen($deskripsi) > 60
                            ? mb_substr($deskripsi, 0, 60) . '...'
                            : $deskripsi;

                        $kategoriModal = !empty($daftarKategori)
                            ? implode(', ', $daftarKategori)
                            : 'Tanpa Kategori';

                        $hargaPemasanganData =
                            ($produk['harga_pemasangan'] !== null &&
                             $produk['harga_pemasangan'] !== '' &&
                             (float)$produk['harga_pemasangan'] > 0)
                                ? (float)$produk['harga_pemasangan']
                                : null;

                        $dataProduk = [
                            'id' => (int)$produk['id'],
                            'nama' => $produk['nama_produk'],
                            'kategori' => $kategoriModal,
                            'merek' => !empty($produk['nama_merek']) ? $produk['nama_merek'] : 'Tanpa Merek',
                            'sku' => $produk['kode_sku'],
                            'harga' => (float)$produk['harga'],
                            'harga_pemasangan' => $hargaPemasanganData,
                            'stok' => (int)$produk['stok'],
                            'deskripsi' => $deskripsi,
                            'gambar' => $produk['gambar']
                        ];
                        ?>

                        <!-- CARD PRODUK -->
                        <div
                            class="produk-card bg-white rounded-xl sm:rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col"
                            data-search="<?= htmlspecialchars(strtolower($produk['nama_produk'] . ' ' . $produk['kode_sku'] . ' ' . (!empty($produk['nama_merek']) ? $produk['nama_merek'] : '') . ' ' . $kategoriModal . ' ' . $deskripsi), ENT_QUOTES, 'UTF-8') ?>"
                        >

                            <!-- GAMBAR (Ukuran disesuaikan untuk HP) -->
                            <div class="h-32 sm:h-56 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                                <?php if (!empty($produk['gambar']) && file_exists('uploads/' . $produk['gambar'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($gambarPath) ?>"
                                        alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                                        class="w-full h-full object-contain p-2 sm:p-4 hover:scale-105 transition-transform duration-300"
                                    >
                                <?php else: ?>
                                    <div class="text-gray-300 text-center">
                                        <i class="fa-solid fa-image text-3xl sm:text-5xl"></i>
                                        <p class="text-[10px] sm:text-xs mt-1 sm:mt-2">Tidak ada gambar</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- INFORMASI PRODUK -->
                            <div class="p-3 sm:p-5 flex flex-col flex-grow">

                                <!-- KATEGORI & MEREK -->
                                <div class="flex flex-wrap gap-1 sm:gap-2 mb-2 sm:mb-3">
                                    <?php if (!empty($daftarKategori)): ?>
                                        <?php foreach (array_slice($daftarKategori, 0, 1) as $namaKategori): // Ambil 1 kategori saja agar tidak penuh di HP ?>
                                            <span class="inline-block w-fit bg-indigo-100 text-indigo-700 text-[9px] sm:text-xs font-semibold px-2 py-1 sm:px-3 sm:py-1.5 rounded-full">
                                                <?= htmlspecialchars($namaKategori) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="inline-block w-fit bg-gray-100 text-gray-500 text-[9px] sm:text-xs font-semibold px-2 py-1 sm:px-3 sm:py-1.5 rounded-full">
                                            Tanpa Kategori
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($produk['nama_merek'])): ?>
                                        <span class="inline-block w-fit bg-purple-100 text-purple-700 text-[9px] sm:text-xs font-semibold px-2 py-1 sm:px-3 sm:py-1.5 rounded-full">
                                            <?= htmlspecialchars($produk['nama_merek']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- NAMA PRODUK -->
                                <h3 class="text-sm sm:text-lg font-bold text-gray-900 mb-1 sm:mb-2 line-clamp-2 leading-snug">
                                    <?= htmlspecialchars($produk['nama_produk']) ?>
                                </h3>

                                <!-- SKU -->
                                <p class="text-[10px] sm:text-xs text-gray-400 mb-2 sm:mb-3">
                                    SKU: <?= htmlspecialchars($produk['kode_sku']) ?>
                                </p>

                                <!-- HARGA -->
                                <div class="mb-3 sm:mb-4 mt-auto">
                                    <p class="text-[10px] sm:text-xs text-gray-500">Harga</p>
                                    <p class="text-base sm:text-xl font-bold text-indigo-600">
                                        Rp <?= number_format((float)$produk['harga'], 0, ',', '.') ?>
                                    </p>
                                </div>

                                <!-- BUTTON DETAIL -->
                                <button
                                    type="button"
                                    onclick='bukaDetailProduk(<?= htmlspecialchars(json_encode($dataProduk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8") ?>)'
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-2 sm:px-4 sm:py-3 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-1 sm:gap-2 shadow-md shadow-indigo-600/20"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- =====================================================
                     KOTAK PAGINASI (HALAMAN) DI BAWAH GRID
                ====================================================== -->
                <?php if ($total_halaman > 1): ?>
                    <div class="flex justify-center items-center gap-1 sm:gap-2 mt-10 sm:mt-12 mb-4">
                        
                        <!-- Tombol Prev -->
                        <?php if ($halaman_aktif > 1): ?>
                            <a href="?kategori=<?= urlencode($filterAktif) ?>&merek=<?= urlencode($merekAktif) ?>&halaman=<?= $halaman_aktif - 1 ?>#area-kategori" 
                               class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg sm:rounded-xl bg-white/10 hover:bg-indigo-600 text-white font-bold transition shadow-sm border border-white/20 text-xs sm:text-base">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Nomor Halaman -->
                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <?php if ($i == $halaman_aktif): ?>
                                <span class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg sm:rounded-xl bg-indigo-600 text-white font-black shadow-md border border-indigo-500 text-xs sm:text-base">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?kategori=<?= urlencode($filterAktif) ?>&merek=<?= urlencode($merekAktif) ?>&halaman=<?= $i ?>#area-kategori" 
                                   class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg sm:rounded-xl bg-white/10 hover:bg-white/30 text-white font-bold transition shadow-sm border border-white/20 text-xs sm:text-base">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Tombol Next -->
                        <?php if ($halaman_aktif < $total_halaman): ?>
                            <a href="?kategori=<?= urlencode($filterAktif) ?>&merek=<?= urlencode($merekAktif) ?>&halaman=<?= $halaman_aktif + 1 ?>#area-kategori" 
                               class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg sm:rounded-xl bg-white/10 hover:bg-indigo-600 text-white font-bold transition shadow-sm border border-white/20 text-xs sm:text-base">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- PRODUK KOSONG -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16 px-6">
                    <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-box-open text-3xl text-gray-400"></i>
                    </div>

                    <h3 class="text-xl font-bold text-gray-800">
                        Produk Tidak Ditemukan
                    </h3>

                    <p class="text-gray-500 mt-2 max-w-md mx-auto">
                        Belum ada produk untuk kombinasi Kategori dan Merek ini di halaman ini.
                    </p>

                    <a
                        href="kategori.php?kategori=semua&merek=semua#area-kategori"
                        class="inline-flex items-center gap-2 mt-6 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl font-semibold transition"
                    >
                        <i class="fa-solid fa-layer-group"></i>
                        Kembali ke Semua Produk
                    </a>
                </div>
            <?php endif; ?>

            <!-- BAGIAN BAWAH -->
            <div class="mt-14 text-center">
                <p class="text-purple-200 text-sm mb-2">
                    Belum menemukan produk yang sesuai?
                </p>

                <a
                    href="https://wa.me/6281271165500?text=Halo%20Admin,%20saya%20ingin%20mengonsultasikan%20kebutuhan%20CCTV%20saya."
                    target="_blank"
                    class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 font-bold text-base sm:text-lg transition"
                >
                    Konsultasikan kebutuhan CCTV Anda
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- =========================================================
     MODAL DETAIL PRODUK
========================================================= -->

<div
    id="modalDetailProduk"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4"
>
    <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl">

        <!-- CLOSE -->
        <button
            type="button"
            onclick="tutupDetailProduk()"
            class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-white text-gray-600 hover:text-red-600 shadow-md flex items-center justify-center transition"
        >
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>

        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- GAMBAR -->
            <div class="bg-gray-100 flex items-center justify-center p-6 min-h-[280px]">
                <img
                    id="detailGambar"
                    src=""
                    alt="Produk"
                    class="w-full h-64 object-contain rounded-xl"
                >
            </div>

            <!-- INFORMASI -->
            <div class="p-6 md:p-8">

                <!-- KATEGORI & MEREK -->
                <div class="flex flex-wrap gap-2 mb-3">
                    <span
                        id="detailKategori"
                        class="inline-block w-fit bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1.5 rounded-full"
                    ></span>

                    <span
                        id="detailMerek"
                        class="inline-block w-fit bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1.5 rounded-full"
                    ></span>
                </div>

                <!-- NAMA -->
                <h2 id="detailNama" class="text-2xl font-bold text-gray-900 mb-2"></h2>

                <!-- SKU -->
                <p id="detailSKU" class="text-sm text-gray-500 mb-5"></p>

                <!-- HARGA -->
                <div class="mb-5">
                    <p class="text-sm text-gray-500 mb-1">
                        Harga
                    </p>

                    <p
                        id="detailHarga"
                        class="text-2xl font-bold text-indigo-600"
                    ></p>

                    <!-- HARGA + PEMASANGAN -->
                    <div id="detailHargaPemasanganContainer" class="mt-3 hidden">
                        <p class="text-sm text-gray-500 mb-1">
                            Harga + Pemasangan
                        </p>

                        <p
                            id="detailHargaPemasangan"
                            class="text-xl font-bold text-orange-500"
                        ></p>
                    </div>
                </div>

                <!-- STOK -->
                <div class="mb-5">
                    <p class="text-sm text-gray-500 mb-1">
                        Ketersediaan
                    </p>

                    <p id="detailStok" class="font-semibold text-gray-800"></p>
                </div>

                <!-- DESKRIPSI -->
                <div class="mb-6">
                    <p class="text-sm font-semibold text-gray-800 mb-2">
                        Deskripsi Produk
                    </p>

                    <p id="detailDeskripsi" class="text-gray-600 text-sm leading-relaxed"></p>
                </div>

                <!-- BUTTON -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        onclick="tutupDetailProduk()"
                        class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-3 rounded-xl font-semibold transition"
                    >
                        Tutup
                    </button>

                    <form
                        id="formKeranjangDetail"
                        action="tambah_keranjang.php"
                        method="POST"
                        class="flex-1"
                    >
                        <input type="hidden" name="id" id="idProdukDetail" value="">
                        <input type="hidden" name="paket" value="cctv">

                        <button
                            type="submit"
                            id="btnKeranjangDetail"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2 shadow-md shadow-indigo-600/20"
                        >
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>Keranjang</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   SEARCH PRODUK REAL-TIME
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
            if (cocok) jumlahDitemukan++;
        });

        if (kataKunci !== '') {
            clearButton.classList.remove('hidden');
            clearButton.classList.add('flex');
            info.classList.remove('hidden');
            info.textContent = 'Menampilkan ' + jumlahDitemukan + ' dari ' + totalProduk + ' produk di halaman ini untuk "' + input.value.trim() + '"';
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
   BUKA DETAIL PRODUK
========================================================= */
function bukaDetailProduk(produk) {

    document.getElementById('detailNama').textContent = produk.nama;

    document.getElementById('detailKategori').textContent =
        produk.kategori;

    document.getElementById('detailMerek').textContent =
        produk.merek || 'Tanpa Merek';

    document.getElementById('detailSKU').textContent =
        'SKU: ' + produk.sku;

    /* =====================================================
       HARGA
    ===================================================== */
    document.getElementById('detailHarga').textContent =
        'Rp ' + Number(produk.harga).toLocaleString('id-ID');

    /* =====================================================
       HARGA + PEMASANGAN
       Hanya tampil jika memang ada harga pemasangan.
    ===================================================== */
    const hargaPemasanganContainer =
        document.getElementById('detailHargaPemasanganContainer');

    const hargaPemasangan =
        document.getElementById('detailHargaPemasangan');

    if (
        produk.harga_pemasangan !== null &&
        produk.harga_pemasangan !== '' &&
        Number(produk.harga_pemasangan) > 0
    ) {
        hargaPemasangan.textContent =
            'Rp ' + Number(produk.harga_pemasangan).toLocaleString('id-ID');

        hargaPemasanganContainer.classList.remove('hidden');
    } else {
        hargaPemasangan.textContent = '';
        hargaPemasanganContainer.classList.add('hidden');
    }

    /* =====================================================
       STOK
    ===================================================== */
    const detailStok = document.getElementById('detailStok');
    const tombolKeranjang = document.getElementById('btnKeranjangDetail');
    const idProdukDetail = document.getElementById('idProdukDetail');

    if (Number(produk.stok) > 0) {
        detailStok.textContent = produk.stok + ' unit tersedia';

        idProdukDetail.value = produk.id;

        tombolKeranjang.disabled = false;
        tombolKeranjang.classList.remove('opacity-50', 'cursor-not-allowed');

        tombolKeranjang.innerHTML =
            '<i class="fa-solid fa-cart-shopping"></i>' +
            '<span>Keranjang</span>';
    } else {
        detailStok.textContent = 'Stok habis';

        idProdukDetail.value = '';

        tombolKeranjang.disabled = true;
        tombolKeranjang.classList.add('opacity-50', 'cursor-not-allowed');

        tombolKeranjang.innerHTML =
            '<i class="fa-solid fa-ban"></i>' +
            '<span>Stok Habis</span>';
    }

    /* =====================================================
       DESKRIPSI
    ===================================================== */
    document.getElementById('detailDeskripsi').textContent =
        produk.deskripsi
            ? produk.deskripsi
            : 'Belum ada deskripsi untuk produk ini.';

    /* =====================================================
       GAMBAR
    ===================================================== */
    const gambar = document.getElementById('detailGambar');

    gambar.src = produk.gambar
        ? 'uploads/' + produk.gambar
        : '';

    gambar.alt = produk.nama;

    /* =====================================================
       BUKA MODAL
    ===================================================== */
    const modal = document.getElementById('modalDetailProduk');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}

/* =========================================================
   TUTUP DETAIL
========================================================= */
function tutupDetailProduk() {
    const modal = document.getElementById('modalDetailProduk');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.classList.remove('overflow-hidden');
}

/* =========================================================
   KLIK BACKDROP
========================================================= */
document
    .getElementById('modalDetailProduk')
    .addEventListener('click', function(event) {
        if (event.target === this) {
            tutupDetailProduk();
        }
    });

/* =========================================================
   ESCAPE
========================================================= */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        tutupDetailProduk();
    }
});
</script>

<!-- =========================================================
     STYLE
========================================================= -->

<style>
/* Mencegah layar tergeser di mobile (Menghilangkan garis putih di kanan) */
html, body {
    overflow-x: hidden;
    width: 100%;
    position: relative;
}

/* Mencegah highlight kotak biru saat tap tombol di HP */
* {
    -webkit-tap-highlight-color: transparent;
}

/* Garis Indikator Scroll Kustom */
.custom-scrollbar {
    /* Untuk Firefox */
    scrollbar-width: thin;
    scrollbar-color: rgba(249, 115, 22, 0.8) transparent;
}

/* Untuk Chrome, Edge, Safari */
.custom-scrollbar::-webkit-scrollbar {
    height: 4px; /* Ketebalan garis indikator */
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05); /* Warna jalur latar belakang transparan */
    border-radius: 10px;
    margin: 0 10px; /* Jarak agar garis tidak mentok ke ujung kiri/kanan */
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(to right, #3b82f6, #f97316); /* Gradasi warna garis (biru ke oranye) */
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #ea580c;
}
</style>

<?php include 'footer.php'; ?>