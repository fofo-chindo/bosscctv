<?php

session_start();

require_once 'koneksi.php';

include 'header.php';

/* =========================================================
   FILTER KATEGORI & MEREK
========================================================= */

$filterAktif = $_GET['kategori'] ?? 'semua';
$merekAktif = $_GET['merek'] ?? 'semua';

$kategoriTerpilih = null;
$merekTerpilih = null;

$produkList = [];


/* =========================================================
   AMBIL SEMUA KATEGORI DARI DATABASE
   MENGGUNAKAN product_categories
   PRODUK UNGGULAN TIDAK DIHITUNG
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

    GROUP BY
        c.id,
        c.nama_kategori,
        c.slug

    ORDER BY 
        CASE WHEN c.slug = 'lain-lainnya' THEN 1 ELSE 0 END, 
        c.id ASC
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
    SELECT
        id,
        nama_merek,
        slug
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
   JIKA MEMILIH KATEGORI TERTENTU
========================================================= */

if ($filterAktif !== 'semua') {

    $stmtKategori = mysqli_prepare(
        $koneksi,
        "
        SELECT
            id,
            nama_kategori,
            slug
        FROM categories
        WHERE slug = ?
        LIMIT 1
        "
    );

    mysqli_stmt_bind_param(
        $stmtKategori,
        "s",
        $filterAktif
    );

    mysqli_stmt_execute($stmtKategori);

    $resultTerpilih =
        mysqli_stmt_get_result($stmtKategori);

    $kategoriTerpilih =
        mysqli_fetch_assoc($resultTerpilih);

    mysqli_stmt_close($stmtKategori);
}


/* =========================================================
   JIKA MEMILIH MEREK
========================================================= */

if ($merekAktif !== 'semua') {

    $stmtMerek = mysqli_prepare(
        $koneksi,
        "
        SELECT
            id,
            nama_merek,
            slug
        FROM brands
        WHERE slug = ?
        AND is_active = 1
        LIMIT 1
        "
    );

    mysqli_stmt_bind_param(
        $stmtMerek,
        "s",
        $merekAktif
    );

    mysqli_stmt_execute($stmtMerek);

    $resultMerekTerpilih =
        mysqli_stmt_get_result($stmtMerek);

    $merekTerpilih =
        mysqli_fetch_assoc($resultMerekTerpilih);

    mysqli_stmt_close($stmtMerek);
}


/* =========================================================
   AMBIL PRODUK
   SISTEM MULTI-KATEGORI
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

    LEFT JOIN product_categories pc
        ON pc.product_id = p.id

    LEFT JOIN categories c
        ON c.id = pc.category_id

    LEFT JOIN brands b
        ON b.id = p.brand_id

    WHERE p.is_active = 1
    AND p.is_featured = 0
";


$types = "";
$params = [];


/* =========================================================
   FILTER KATEGORI
========================================================= */

if ($kategoriTerpilih) {

    $sqlProduk .= "
        AND EXISTS (
            SELECT 1
            FROM product_categories pc_filter
            WHERE pc_filter.product_id = p.id
            AND pc_filter.category_id = ?
        )
    ";

    $types .= "i";

    $params[] =
        (int)$kategoriTerpilih['id'];
}


/* =========================================================
   FILTER MEREK
========================================================= */

if ($merekTerpilih) {

    $sqlProduk .= "
        AND p.brand_id = ?
    ";

    $types .= "i";

    $params[] =
        (int)$merekTerpilih['id'];
}


/* =========================================================
   GROUP & ORDER
========================================================= */

$sqlProduk .= "
    GROUP BY
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
        b.nama_merek,
        b.slug

    ORDER BY p.id DESC
";


/* =========================================================
   EKSEKUSI QUERY PRODUK
========================================================= */

$stmtProduk = mysqli_prepare(
    $koneksi,
    $sqlProduk
);

if (!$stmtProduk) {

    die(
        "Query produk gagal: " .
        htmlspecialchars(mysqli_error($koneksi))
    );
}


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmtProduk,
        $types,
        ...$params
    );
}


mysqli_stmt_execute($stmtProduk);

$resultProduk =
    mysqli_stmt_get_result($stmtProduk);


while ($row = mysqli_fetch_assoc($resultProduk)) {

    $produkList[] = $row;

}

mysqli_stmt_close($stmtProduk);


/* =========================================================
   NAMA FILTER
========================================================= */

if ($kategoriTerpilih && $merekTerpilih) {

    $namaFilter =
        $kategoriTerpilih['nama_kategori'] .
        ' • ' .
        $merekTerpilih['nama_merek'];

} elseif ($kategoriTerpilih) {

    $namaFilter =
        $kategoriTerpilih['nama_kategori'];

} elseif ($merekTerpilih) {

    $namaFilter =
        $merekTerpilih['nama_merek'];

} else {

    $namaFilter = 'Semua Produk';

}

?>

<!-- =========================================================
     BACKGROUND UTAMA
========================================================= -->

<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative min-h-screen">

    <!-- Watermark -->

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


    <!-- =====================================================
         AREA KATEGORI
    ====================================================== -->

    <section
        id="area-kategori"
        class="relative z-10 pt-10 pb-4 scroll-mt-24"
    >

        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center"
        >

            <h1
                class="text-3xl sm:text-5xl font-black text-white drop-shadow-md"
            >
                Pilih Kategori Produk
            </h1>

            <p
                class="mt-2 text-purple-100 text-sm sm:text-base max-w-2xl mx-auto"
            >
                Temukan berbagai produk CCTV dan perlengkapan keamanan sesuai dengan kebutuhan Anda.
            </p>

        </div>

    </section>


    <!-- =====================================================
         MENU KATEGORI + MEREK
    ====================================================== -->

    <div
        class="sticky top-20 z-50 py-3 px-4 sm:px-6 lg:px-8 w-full
               bg-slate-950/60 backdrop-blur-xl
               border-b border-white/10 shadow-lg transition-all"
    >

        <div class="max-w-7xl mx-auto space-y-2">


            <!-- =================================================
                 BAR KATEGORI
            ================================================== -->

            <div class="flex justify-center">

                <div class="relative max-w-full w-full sm:w-auto">

                    <!-- Fade kiri -->

                    <div
                        class="absolute left-0 top-0 bottom-0 w-8
                               bg-gradient-to-r
                               from-slate-950/80 to-transparent
                               pointer-events-none z-10 rounded-l-xl"
                    ></div>


                    <!-- Fade kanan -->

                    <div
                        class="absolute right-0 top-0 bottom-0 w-8
                               bg-gradient-to-l
                               from-slate-950/80 to-transparent
                               pointer-events-none z-10 rounded-r-xl"
                    ></div>


                    <div
                        class="flex max-w-full overflow-x-auto
                               bg-white/10 backdrop-blur-md
                               px-4 py-2.5 rounded-xl
                               border border-white/15
                               gap-2.5 whitespace-nowrap
                               custom-scrollbar items-center shadow-sm"
                    >

                        <!-- SEMUA -->

                        <a
                            href="kategori.php?kategori=semua&merek=<?php echo urlencode($merekAktif); ?>#area-kategori"
                            class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold
                                   transition-all duration-200 flex-shrink-0
                                   <?php
                                   echo (
                                       $filterAktif === 'semua'
                                   )
                                       ? 'bg-blue-600 text-white shadow-md'
                                       : 'text-gray-200 hover:bg-white/15 hover:text-white';
                                   ?>"
                        >
                            Semua
                        </a>


                        <!-- PEMISAH -->

                        <div
                            class="w-px h-5 bg-white/20 mx-1 flex-shrink-0"
                        ></div>


                        <!-- KATEGORI -->

                        <?php foreach ($kategoriList as $item): ?>

                            <a
                                href="kategori.php?kategori=<?php echo urlencode($item['slug']); ?>&merek=<?php echo urlencode($merekAktif); ?>#area-kategori"
                                class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold
                                       transition-all duration-200 flex-shrink-0
                                       <?php
                                       echo (
                                           $filterAktif === $item['slug']
                                       )
                                           ? 'bg-blue-600 text-white shadow-md'
                                           : 'text-gray-200 hover:bg-white/15 hover:text-white';
                                       ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $item['nama_kategori']
                                );
                                ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 BAR MEREK
            ================================================== -->

            <div class="flex justify-center">

                <div class="relative max-w-full w-full sm:w-auto">

                    <!-- Fade kiri -->

                    <div
                        class="absolute left-0 top-0 bottom-0 w-8
                               bg-gradient-to-r
                               from-slate-950/80 to-transparent
                               pointer-events-none z-10 rounded-l-xl"
                    ></div>


                    <!-- Fade kanan -->

                    <div
                        class="absolute right-0 top-0 bottom-0 w-8
                               bg-gradient-to-l
                               from-slate-950/80 to-transparent
                               pointer-events-none z-10 rounded-r-xl"
                    ></div>


                    <div
                        class="flex max-w-full overflow-x-auto
                               bg-white/5 backdrop-blur-md
                               px-4 py-2 rounded-xl
                               border border-white/10
                               gap-2 whitespace-nowrap
                               hide-scrollbar items-center shadow-inner"
                    >

                        <!-- SEMUA MEREK -->

                        <a
                            href="kategori.php?kategori=<?php echo urlencode($filterAktif); ?>&merek=semua#area-kategori"
                            class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold
                                   transition-all duration-200 flex-shrink-0
                                   <?php
                                   echo $merekAktif === 'semua'
                                       ? 'bg-orange-500 text-white shadow-md font-bold'
                                       : 'text-gray-100 hover:bg-white/15 hover:text-white';
                                   ?>"
                        >

                            <i
                                class="fa-solid fa-layer-group mr-1.5 text-orange-400"
                            ></i>

                            Semua Merek

                        </a>


                        <!-- DAFTAR MEREK -->

                        <?php foreach ($merekList as $merek): ?>

                            <a
                                href="kategori.php?kategori=<?php echo urlencode($filterAktif); ?>&merek=<?php echo urlencode($merek['slug']); ?>#area-kategori"
                                class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold
                                       transition-all duration-200 flex-shrink-0
                                       <?php
                                       echo $merekAktif === $merek['slug']
                                           ? 'bg-orange-500 text-white shadow-md font-bold'
                                           : 'text-gray-100 hover:bg-white/15 hover:text-white';
                                       ?>"
                            >

                                <i
                                    class="fa-solid fa-tag mr-1.5 text-orange-400"
                                ></i>

                                <?php
                                echo htmlspecialchars(
                                    $merek['nama_merek']
                                );
                                ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         DAFTAR PRODUK
    ====================================================== -->

    <section class="relative z-10 pb-20 pt-6">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


            <!-- INFORMASI FILTER -->

            <div
                class="flex flex-col sm:flex-row
                       sm:items-center sm:justify-between
                       gap-3 mb-6"
            >

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

                        <?php
                        echo htmlspecialchars($namaFilter);
                        ?>

                    </h2>

                </div>


                <div class="text-sm text-purple-200">

                    Menampilkan

                    <span class="font-semibold text-white">

                        <?php echo count($produkList); ?>

                    </span>

                    produk

                </div>

            </div>


            <!-- =================================================
                 PRODUK
            ================================================== -->

            <?php if (!empty($produkList)): ?>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2
                           lg:grid-cols-3 xl:grid-cols-4 gap-6"
                >

                    <?php foreach ($produkList as $produk): ?>

                        <?php

                        /* =================================================
                           SIAPKAN KATEGORI PRODUK
                        ================================================= */

                        $daftarKategori = [];

                        if (!empty($produk['nama_kategori'])) {

                            $daftarKategori =
                                array_filter(
                                    array_map(
                                        'trim',
                                        explode(
                                            '|||',
                                            $produk['nama_kategori']
                                        )
                                    )
                                );
                        }


                        /* =================================================
                           GAMBAR
                        ================================================= */

                        $gambarPath =
                            'uploads/' .
                            $produk['gambar'];


                        /* =================================================
                           DESKRIPSI
                        ================================================= */

                        $deskripsi =
                            !empty($produk['deskripsi'])
                                ? $produk['deskripsi']
                                : 'Belum ada deskripsi untuk produk ini.';


                        $deskripsiPendek =
                            mb_strlen($deskripsi) > 100
                                ? mb_substr(
                                    $deskripsi,
                                    0,
                                    100
                                ) . '...'
                                : $deskripsi;


                        /* =================================================
                           KATEGORI UNTUK MODAL
                        ================================================= */

                        $kategoriModal =
                            !empty($daftarKategori)
                                ? implode(
                                    ', ',
                                    $daftarKategori
                                )
                                : 'Tanpa Kategori';


                        /* =================================================
                           DATA PRODUK
                        ================================================= */

                        $dataProduk = [

                            'id' =>
                                (int)$produk['id'],

                            'nama' =>
                                $produk['nama_produk'],

                            'kategori' =>
                                $kategoriModal,

                            'merek' =>
                                $produk['nama_merek']
                                ?? 'Tanpa Merek',

                            'sku' =>
                                $produk['kode_sku'],

                            'harga' =>
                                (float)$produk['harga'],

                            'harga_pemasangan' =>
                                (float)$produk['harga_pemasangan'],

                            'stok' =>
                                (int)$produk['stok'],

                            'deskripsi' =>
                                $deskripsi,

                            'gambar' =>
                                $produk['gambar']

                        ];

                        ?>


                        <!-- =================================================
                             CARD PRODUK
                        ================================================== -->

                        <div
                            class="bg-white rounded-2xl
                                   border border-gray-100
                                   shadow-sm overflow-hidden
                                   hover:shadow-xl hover:-translate-y-1
                                   transition-all duration-300
                                   flex flex-col"
                        >


                            <!-- GAMBAR -->

                            <div
                                class="h-56 bg-gray-100
                                       flex items-center justify-center
                                       overflow-hidden"
                            >

                                <?php if (
                                    !empty($produk['gambar']) &&
                                    file_exists(
                                        'uploads/' .
                                        $produk['gambar']
                                    )
                                ): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($gambarPath); ?>"
                                        alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>"
                                        class="w-full h-full
                                               object-contain p-4
                                               hover:scale-105
                                               transition-transform
                                               duration-300"
                                    >

                                <?php else: ?>

                                    <div
                                        class="text-gray-300 text-center"
                                    >

                                        <i
                                            class="fa-solid fa-image text-5xl"
                                        ></i>

                                        <p class="text-xs mt-2">
                                            Tidak ada gambar
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- INFORMASI PRODUK -->

                            <div
                                class="p-5 flex flex-col flex-grow"
                            >


                                <!-- KATEGORI & MEREK -->

                                <div
                                    class="flex flex-wrap gap-2 mb-3"
                                >

                                    <?php if (!empty($daftarKategori)): ?>

                                        <?php foreach ($daftarKategori as $namaKategori): ?>

                                            <span
                                                class="inline-block w-fit
                                                       bg-indigo-100
                                                       text-indigo-700
                                                       text-xs font-semibold
                                                       px-3 py-1.5
                                                       rounded-full"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $namaKategori
                                                );
                                                ?>

                                            </span>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <span
                                            class="inline-block w-fit
                                                   bg-gray-100
                                                   text-gray-500
                                                   text-xs font-semibold
                                                   px-3 py-1.5
                                                   rounded-full"
                                        >
                                            Tanpa Kategori
                                        </span>

                                    <?php endif; ?>


                                    <?php if (!empty($produk['nama_merek'])): ?>

                                        <span
                                            class="inline-block w-fit
                                                   bg-purple-100
                                                   text-purple-700
                                                   text-xs font-semibold
                                                   px-3 py-1.5
                                                   rounded-full"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $produk['nama_merek']
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- NAMA PRODUK -->

                                <h3
                                    class="text-lg font-bold
                                           text-gray-900 mb-2"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $produk['nama_produk']
                                    );
                                    ?>

                                </h3>


                                <!-- SKU -->

                                <p
                                    class="text-xs text-gray-400 mb-3"
                                >

                                    SKU:
                                    <?php
                                    echo htmlspecialchars(
                                        $produk['kode_sku']
                                    );
                                    ?>

                                </p>


                                <!-- DESKRIPSI -->

                                <p
                                    class="text-sm text-gray-600
                                           leading-relaxed mb-4
                                           flex-grow"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $deskripsiPendek
                                    );
                                    ?>

                                </p>


                                <!-- HARGA -->

                                <div class="mb-4">

                                    <p class="text-xs text-gray-500">
                                        Harga
                                    </p>

                                    <p
                                        class="text-xl font-bold
                                               text-indigo-600"
                                    >

                                        Rp
                                        <?php
                                        echo number_format(
                                            $produk['harga'],
                                            0,
                                            ',',
                                            '.'
                                        );
                                        ?>

                                    </p>

                                </div>


                                <!-- STOK -->

                                <div
                                    class="flex items-center
                                           justify-between mb-4"
                                >

                                    <span class="text-sm text-gray-500">
                                        Stok
                                    </span>


                                    <?php if ($produk['stok'] > 0): ?>

                                        <span
                                            class="text-sm font-semibold
                                                   text-green-600"
                                        >

                                            <?php
                                            echo $produk['stok'];
                                            ?>
                                            tersedia

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="text-sm font-semibold
                                                   text-red-600"
                                        >
                                            Stok habis
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- DETAIL -->

                                <button
                                    type="button"
                                    onclick='bukaDetailProduk(<?php echo htmlspecialchars(json_encode($dataProduk, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8"); ?>)'
                                    class="w-full bg-indigo-600
                                           hover:bg-indigo-700
                                           text-white px-4 py-3
                                           rounded-xl font-semibold
                                           transition flex items-center
                                           justify-center gap-2
                                           shadow-md
                                           shadow-indigo-600/20"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                    Lihat Detail

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <!-- =================================================
                     PRODUK KOSONG
                ================================================== -->

                <div
                    class="bg-white rounded-2xl
                           border border-gray-100
                           shadow-sm text-center
                           py-16 px-6"
                >

                    <div
                        class="w-20 h-20 mx-auto
                               rounded-full bg-gray-100
                               flex items-center justify-center mb-5"
                    >

                        <i
                            class="fa-solid fa-box-open
                                   text-3xl text-gray-400"
                        ></i>

                    </div>


                    <h3
                        class="text-xl font-bold text-gray-800"
                    >
                        Produk Tidak Ditemukan
                    </h3>


                    <p
                        class="text-gray-500 mt-2
                               max-w-md mx-auto"
                    >
                        Belum ada produk untuk kombinasi
                        Kategori dan Merek ini.
                    </p>


                    <a
                        href="kategori.php?kategori=semua&merek=semua#area-kategori"
                        class="inline-flex items-center
                               gap-2 mt-6
                               bg-indigo-600
                               hover:bg-indigo-700
                               text-white px-5 py-3
                               rounded-xl font-semibold
                               transition"
                    >

                        <i
                            class="fa-solid fa-layer-group"
                        ></i>

                        Kembali ke Semua Produk

                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 BAGIAN BAWAH
            ================================================== -->

            <div class="mt-14 text-center">

                <p
                    class="text-purple-200 text-sm mb-2"
                >
                    Belum menemukan produk yang sesuai?
                </p>


                <a
                    href="https://wa.me/6281271165500?text=Halo%20Admin,%20saya%20ingin%20mengonsultasikan%20kebutuhan%20CCTV%20saya."
                    target="_blank"
                    class="inline-flex items-center
                           gap-2 text-blue-400
                           hover:text-blue-300
                           font-bold text-base sm:text-lg
                           transition"
                >

                    Konsultasikan kebutuhan CCTV Anda

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

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
    class="fixed inset-0 z-[9999] hidden
           items-center justify-center
           bg-black/60 backdrop-blur-sm p-4"
>

    <div
        class="relative bg-white w-full max-w-2xl
               max-h-[90vh] overflow-y-auto
               rounded-2xl shadow-2xl"
    >


        <!-- CLOSE -->

        <button
            type="button"
            onclick="tutupDetailProduk()"
            class="absolute top-4 right-4 z-20
                   w-10 h-10 rounded-full
                   bg-white text-gray-600
                   hover:text-red-600
                   shadow-md flex items-center
                   justify-center transition"
        >

            <i
                class="fa-solid fa-xmark text-xl"
            ></i>

        </button>


        <div
            class="grid grid-cols-1 md:grid-cols-2"
        >


            <!-- GAMBAR -->

            <div
                class="bg-gray-100
                       flex items-center justify-center
                       p-6 min-h-[280px]"
            >

                <img
                    id="detailGambar"
                    src=""
                    alt="Produk"
                    class="w-full h-64
                           object-contain rounded-xl"
                >

            </div>


            <!-- INFORMASI -->

            <div
                class="p-6 md:p-8"
            >


                <!-- KATEGORI & MEREK -->

                <div
                    class="flex flex-wrap gap-2 mb-3"
                >

                    <span
                        id="detailKategori"
                        class="inline-block w-fit
                               bg-indigo-100
                               text-indigo-700
                               text-xs font-semibold
                               px-3 py-1.5 rounded-full"
                    ></span>


                    <span
                        id="detailMerek"
                        class="inline-block w-fit
                               bg-purple-100
                               text-purple-700
                               text-xs font-semibold
                               px-3 py-1.5 rounded-full"
                    ></span>

                </div>


                <!-- NAMA -->

                <h2
                    id="detailNama"
                    class="text-2xl font-bold
                           text-gray-900 mb-2"
                ></h2>


                <!-- SKU -->

                <p
                    id="detailSKU"
                    class="text-sm text-gray-500 mb-5"
                ></p>


                <!-- HARGA -->

                <div class="mb-5">

                    <p
                        class="text-sm text-gray-500 mb-1"
                    >
                        Harga
                    </p>

                    <p
                        id="detailHarga"
                        class="text-2xl font-bold
                               text-indigo-600"
                    ></p>

                </div>


                <!-- STOK -->

                <div class="mb-5">

                    <p
                        class="text-sm text-gray-500 mb-1"
                    >
                        Ketersediaan
                    </p>

                    <p
                        id="detailStok"
                        class="font-semibold
                               text-gray-800"
                    ></p>

                </div>


                <!-- DESKRIPSI -->

                <div class="mb-6">

                    <p
                        class="text-sm font-semibold
                               text-gray-800 mb-2"
                    >
                        Deskripsi Produk
                    </p>

                    <p
                        id="detailDeskripsi"
                        class="text-gray-600 text-sm
                               leading-relaxed"
                    ></p>

                </div>


                <!-- BUTTON -->

                <div class="flex gap-3">

                    <button
                        type="button"
                        onclick="tutupDetailProduk()"
                        class="flex-1 border
                               border-gray-300
                               text-gray-700
                               hover:bg-gray-100
                               px-4 py-3 rounded-xl
                               font-semibold transition"
                    >
                        Tutup
                    </button>


                    <form
                        id="formKeranjangDetail"
                        action="tambah_keranjang.php"
                        method="POST"
                        class="flex-1"
                    >

                        <input
                            type="hidden"
                            name="id"
                            id="idProdukDetail"
                            value=""
                        >


                        <input
                            type="hidden"
                            name="paket"
                            value="cctv"
                        >


                        <button
                            type="submit"
                            id="btnKeranjangDetail"
                            class="w-full bg-indigo-600
                                   hover:bg-indigo-700
                                   text-white px-4 py-3
                                   rounded-xl font-semibold
                                   transition flex items-center
                                   justify-center gap-2
                                   shadow-md
                                   shadow-indigo-600/20"
                        >

                            <i
                                class="fa-solid fa-cart-shopping"
                            ></i>

                            <span>
                                Keranjang
                            </span>

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
   BUKA DETAIL PRODUK
========================================================= */

function bukaDetailProduk(produk) {

    document.getElementById('detailNama').textContent =
        produk.nama;


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
        'Rp ' +
        Number(produk.harga).toLocaleString('id-ID');


    /* =====================================================
       STOK
    ===================================================== */

    const detailStok =
        document.getElementById('detailStok');

    const tombolKeranjang =
        document.getElementById('btnKeranjangDetail');

    const idProdukDetail =
        document.getElementById('idProdukDetail');


    if (Number(produk.stok) > 0) {

        detailStok.textContent =
            produk.stok + ' unit tersedia';


        idProdukDetail.value =
            produk.id;


        tombolKeranjang.disabled =
            false;


        tombolKeranjang.classList.remove(
            'opacity-50',
            'cursor-not-allowed'
        );


        tombolKeranjang.innerHTML =
            '<i class="fa-solid fa-cart-shopping"></i>' +
            '<span>Keranjang</span>';

    } else {

        detailStok.textContent =
            'Stok habis';


        idProdukDetail.value =
            '';


        tombolKeranjang.disabled =
            true;


        tombolKeranjang.classList.add(
            'opacity-50',
            'cursor-not-allowed'
        );


        tombolKeranjang.innerHTML =
            '<i class="fa-solid fa-ban"></i>' +
            '<span>Stok Habis</span>';

    }


    /* =====================================================
       DESKRIPSI
    ===================================================== */

    document.getElementById(
        'detailDeskripsi'
    ).textContent =
        produk.deskripsi
            ? produk.deskripsi
            : 'Belum ada deskripsi untuk produk ini.';


    /* =====================================================
       GAMBAR
    ===================================================== */

    const gambar =
        document.getElementById('detailGambar');


    gambar.src =
        produk.gambar
            ? 'uploads/' + produk.gambar
            : '';


    gambar.alt =
        produk.nama;


    /* =====================================================
       BUKA MODAL
    ===================================================== */

    const modal =
        document.getElementById(
            'modalDetailProduk'
        );


    modal.classList.remove('hidden');

    modal.classList.add('flex');

    document.body.classList.add(
        'overflow-hidden'
    );
}


/* =========================================================
   TUTUP DETAIL
========================================================= */

function tutupDetailProduk() {

    const modal =
        document.getElementById(
            'modalDetailProduk'
        );


    modal.classList.add('hidden');

    modal.classList.remove('flex');

    document.body.classList.remove(
        'overflow-hidden'
    );
}


/* =========================================================
   KLIK BACKDROP
========================================================= */

document
    .getElementById('modalDetailProduk')
    .addEventListener(
        'click',
        function(event) {

            if (event.target === this) {
                tutupDetailProduk();
            }

        }
    );


/* =========================================================
   ESCAPE
========================================================= */

document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {
            tutupDetailProduk();
        }

    }
);

</script>


<!-- =========================================================
     STYLE
========================================================= -->

<style>

/* =========================================================
   SCROLLBAR MEREK
========================================================= */

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}


/* =========================================================
   SCROLLBAR KATEGORI
========================================================= */

.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color:
        rgba(59, 130, 246, 0.5)
        transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    height: 3px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background:
        rgba(59, 130, 246, 0.5);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background:
        rgba(59, 130, 246, 0.8);
}

</style>


<?php include 'footer.php'; ?>