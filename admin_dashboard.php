<?php
session_start();
require_once 'koneksi.php';

// ======================================================
// PROTEKSI HALAMAN
// Hanya Admin & Owner yang boleh masuk
// ======================================================
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'owner'], true)
) {
    header("Location: login.php");
    exit;
}

// ======================================================
// DATA USER LOGIN
// ======================================================
$nama_user = $_SESSION['user_nama'] ?? 'Admin';
$role      = $_SESSION['role'] ?? 'admin';


// ======================================================
// STATISTIK PRODUK
// ======================================================

// ------------------------------------------------------
// Total Produk
// ------------------------------------------------------
$total_produk = 0;

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($query) {
    $data = $query->fetch_assoc();
    $total_produk = (int) ($data['total'] ?? 0);
}


// ------------------------------------------------------
// Produk Aktif
// ------------------------------------------------------
$produk_aktif = 0;

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE is_active = 1
");

if ($query) {
    $data = $query->fetch_assoc();
    $produk_aktif = (int) ($data['total'] ?? 0);
}


// ------------------------------------------------------
// Produk Stok Menipis
// ------------------------------------------------------
$stok_menipis = 0;

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stok <= 5
");

if ($query) {
    $data = $query->fetch_assoc();
    $stok_menipis = (int) ($data['total'] ?? 0);
}


// ------------------------------------------------------
// Total Stok
// ------------------------------------------------------
$total_stok = 0;

$query = $koneksi->query("
    SELECT COALESCE(SUM(stok), 0) AS total
    FROM products
");

if ($query) {
    $data = $query->fetch_assoc();
    $total_stok = (int) ($data['total'] ?? 0);
}


// ======================================================
// DATA PESANAN
// ======================================================

// ------------------------------------------------------
// Total Pesanan
// ------------------------------------------------------
$total_pesanan = 0;

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($query) {
    $data = $query->fetch_assoc();
    $total_pesanan = (int) ($data['total'] ?? 0);
}


// ------------------------------------------------------
// Pesanan Pending
// ------------------------------------------------------
$pesanan_pending = 0;

$query = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status IN ('pending', 'menunggu', 'baru')
");

if ($query) {
    $data = $query->fetch_assoc();
    $pesanan_pending = (int) ($data['total'] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Admin - BOSS CCTV</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e3a8a',
                        secondary: '#f97316'
                    }
                }
            }
        }
    </script>

</head>


<body class="bg-gray-100 flex h-screen overflow-hidden font-sans">


<!-- ======================================================
     SIDEBAR ADMIN
====================================================== -->

<aside
    class="w-64 bg-[#0f172a] text-gray-300 flex flex-col h-screen shrink-0"
>

    <!-- ==================================================
         LOGO
    ================================================== -->

    <div
        class="h-16 flex items-center px-6 text-white font-bold border-b border-gray-800 gap-3 shrink-0"
    >

        <i class="fa-solid fa-shield-halved text-blue-500"></i>

        <span>POS Admin CCTV</span>

    </div>


    <!-- ==================================================
         MENU UTAMA
    ================================================== -->

    <nav
        class="flex-1 px-4 py-6 space-y-2 overflow-y-auto"
    >

        <!-- Dashboard -->
        <a
            href="admin_dashboard.php"
            class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-lg border border-blue-500 shadow-sm"
        >

            <i class="fa-solid fa-chart-line w-5 text-center"></i>

            <span>Dashboard</span>

        </a>


        <!-- Kelola Produk -->
        <a
            href="admin_produk.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
        >

            <i class="fa-solid fa-box w-5 text-center"></i>

            <span>Kelola Stok & Produk</span>

        </a>


        <!-- Pesanan -->
        <a
            href="pesanan.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
        >

            <i class="fa-solid fa-cart-shopping w-5 text-center"></i>

            <span>Pesanan</span>

        </a>

    </nav>


    <!-- ==================================================
         MENU BAWAH
    ================================================== -->

    <div
        class="shrink-0 p-4 border-t border-gray-800 space-y-2 bg-[#0f172a]"
    >

        <!-- Lihat Website -->
        <a
            href="index.php"
            target="_blank"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
        >

            <i class="fa-solid fa-globe w-5 text-center"></i>

            <span>Lihat Website</span>

        </a>


        <!-- Logout -->
        <a
            href="logout.php"
            class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-500/10 hover:text-red-400 rounded-lg transition-colors"
        >

            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>

            <span>Keluar (Logout)</span>

        </a>

    </div>

</aside>



<!-- ======================================================
     MAIN CONTENT
====================================================== -->

<main
    class="flex-1 min-w-0 h-screen overflow-y-auto"
>


    <!-- ==================================================
         HEADER
    ================================================== -->

    <header
        class="bg-white border-b border-gray-200 px-8 py-5 sticky top-0 z-10"
    >

        <div
            class="flex items-center justify-between"
        >

            <!-- Judul -->
            <div>

                <h2
                    class="text-2xl font-bold text-gray-800"
                >
                    Dashboard
                </h2>

                <p
                    class="text-sm text-gray-500 mt-1"
                >

                    Selamat datang kembali,

                    <span
                        class="font-semibold text-gray-700"
                    >
                        <?= htmlspecialchars($nama_user); ?>
                    </span>

                </p>

            </div>


            <!-- Informasi User -->
            <div
                class="flex items-center gap-3"
            >

                <div
                    class="hidden sm:block text-right"
                >

                    <p
                        class="text-sm font-semibold text-gray-700"
                    >
                        <?= htmlspecialchars($nama_user); ?>
                    </p>

                    <p
                        class="text-xs text-gray-500 capitalize"
                    >
                        <?= htmlspecialchars($role); ?>
                    </p>

                </div>


                <div
                    class="w-10 h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center"
                >

                    <i class="fa-solid fa-user"></i>

                </div>

            </div>

        </div>

    </header>



    <!-- ==================================================
         ISI DASHBOARD
    ================================================== -->

    <section class="p-8">


        <!-- ==================================================
             KARTU STATISTIK
        ================================================== -->

        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
        >


            <!-- ==========================================
                 TOTAL PRODUK
            =========================================== -->

            <div
                class="bg-white rounded-xl p-6 shadow-sm border border-gray-100"
            >

                <div
                    class="flex items-center justify-between"
                >

                    <div>

                        <p
                            class="text-sm text-gray-500"
                        >
                            Total Produk
                        </p>

                        <h3
                            class="text-3xl font-bold text-gray-800 mt-2"
                        >
                            <?= $total_produk; ?>
                        </h3>

                    </div>


                    <div
                        class="w-12 h-12 bg-blue-100 text-blue-700 rounded-xl flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-box text-xl"
                        ></i>

                    </div>

                </div>


                <a
                    href="admin_produk.php"
                    class="inline-block mt-4 text-sm text-blue-700 hover:underline"
                >
                    Kelola produk →
                </a>

            </div>



            <!-- ==========================================
                 PRODUK AKTIF
            =========================================== -->

            <div
                class="bg-white rounded-xl p-6 shadow-sm border border-gray-100"
            >

                <div
                    class="flex items-center justify-between"
                >

                    <div>

                        <p
                            class="text-sm text-gray-500"
                        >
                            Produk Aktif
                        </p>

                        <h3
                            class="text-3xl font-bold text-gray-800 mt-2"
                        >
                            <?= $produk_aktif; ?>
                        </h3>

                    </div>


                    <div
                        class="w-12 h-12 bg-green-100 text-green-700 rounded-xl flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-circle-check text-xl"
                        ></i>

                    </div>

                </div>


                <p
                    class="text-sm text-gray-500 mt-4"
                >
                    Produk yang tampil di website
                </p>

            </div>



            <!-- ==========================================
                 TOTAL STOK
            =========================================== -->

            <div
                class="bg-white rounded-xl p-6 shadow-sm border border-gray-100"
            >

                <div
                    class="flex items-center justify-between"
                >

                    <div>

                        <p
                            class="text-sm text-gray-500"
                        >
                            Total Stok
                        </p>

                        <h3
                            class="text-3xl font-bold text-gray-800 mt-2"
                        >
                            <?= $total_stok; ?>
                        </h3>

                    </div>


                    <div
                        class="w-12 h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-cubes text-xl"
                        ></i>

                    </div>

                </div>


                <p
                    class="text-sm text-gray-500 mt-4"
                >
                    Jumlah unit tersedia
                </p>

            </div>



            <!-- ==========================================
                 TOTAL PESANAN
            =========================================== -->

            <div
                class="bg-white rounded-xl p-6 shadow-sm border border-gray-100"
            >

                <div
                    class="flex items-center justify-between"
                >

                    <div>

                        <p
                            class="text-sm text-gray-500"
                        >
                            Total Pesanan
                        </p>

                        <h3
                            class="text-3xl font-bold text-gray-800 mt-2"
                        >
                            <?= $total_pesanan; ?>
                        </h3>

                    </div>


                    <div
                        class="w-12 h-12 bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-cart-shopping text-xl"
                        ></i>

                    </div>

                </div>


                <a
                    href="pesanan.php"
                    class="inline-block mt-4 text-sm text-purple-700 hover:underline"
                >
                    Lihat pesanan →
                </a>

            </div>

        </div>



        <!-- ==================================================
             INFORMASI
        ================================================== -->

        <div
            class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8"
        >


            <!-- ==========================================
                 STOK MENIPIS
            =========================================== -->

            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100"
            >

                <div
                    class="p-6 border-b border-gray-100 flex items-center justify-between"
                >

                    <div>

                        <h3
                            class="text-lg font-bold text-gray-800"
                        >
                            Perhatian Stok
                        </h3>

                        <p
                            class="text-sm text-gray-500 mt-1"
                        >
                            Produk dengan stok 5 unit atau kurang
                        </p>

                    </div>


                    <div
                        class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-triangle-exclamation"
                        ></i>

                    </div>

                </div>


                <div class="p-6">

                    <?php if ($stok_menipis > 0): ?>

                        <div
                            class="flex items-center justify-between bg-red-50 border border-red-100 rounded-lg p-4"
                        >

                            <div
                                class="flex items-center gap-3"
                            >

                                <div
                                    class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center"
                                >

                                    <i
                                        class="fa-solid fa-box-open"
                                    ></i>

                                </div>


                                <div>

                                    <p
                                        class="font-semibold text-gray-800"
                                    >
                                        <?= $stok_menipis; ?> produk
                                    </p>

                                    <p
                                        class="text-sm text-gray-500"
                                    >
                                        Membutuhkan perhatian stok
                                    </p>

                                </div>

                            </div>


                            <a
                                href="admin_produk.php"
                                class="text-sm font-semibold text-red-600 hover:underline"
                            >
                                Periksa
                            </a>

                        </div>

                    <?php else: ?>

                        <div
                            class="text-center py-6"
                        >

                            <div
                                class="w-12 h-12 mx-auto bg-green-100 text-green-600 rounded-full flex items-center justify-center"
                            >

                                <i
                                    class="fa-solid fa-check"
                                ></i>

                            </div>


                            <p
                                class="font-semibold text-gray-700 mt-3"
                            >
                                Stok dalam kondisi baik
                            </p>

                            <p
                                class="text-sm text-gray-500 mt-1"
                            >
                                Tidak ada produk dengan stok menipis.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>



            <!-- ==========================================
                 PESANAN PENDING
            =========================================== -->

            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100"
            >

                <div
                    class="p-6 border-b border-gray-100 flex items-center justify-between"
                >

                    <div>

                        <h3
                            class="text-lg font-bold text-gray-800"
                        >
                            Pesanan Menunggu
                        </h3>

                        <p
                            class="text-sm text-gray-500 mt-1"
                        >
                            Pesanan yang perlu diproses
                        </p>

                    </div>


                    <div
                        class="w-10 h-10 bg-blue-100 text-blue-700 rounded-lg flex items-center justify-center"
                    >

                        <i
                            class="fa-solid fa-clock"
                        ></i>

                    </div>

                </div>


                <div class="p-6">

                    <?php if ($pesanan_pending > 0): ?>

                        <div
                            class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-lg p-4"
                        >

                            <div
                                class="flex items-center gap-3"
                            >

                                <div
                                    class="w-10 h-10 bg-blue-100 text-blue-700 rounded-lg flex items-center justify-center"
                                >

                                    <i
                                        class="fa-solid fa-cart-shopping"
                                    ></i>

                                </div>


                                <div>

                                    <p
                                        class="font-semibold text-gray-800"
                                    >
                                        <?= $pesanan_pending; ?> pesanan
                                    </p>

                                    <p
                                        class="text-sm text-gray-500"
                                    >
                                        Menunggu diproses
                                    </p>

                                </div>

                            </div>


                            <a
                                href="pesanan.php"
                                class="text-sm font-semibold text-blue-700 hover:underline"
                            >
                                Lihat
                            </a>

                        </div>

                    <?php else: ?>

                        <div
                            class="text-center py-6"
                        >

                            <div
                                class="w-12 h-12 mx-auto bg-gray-100 text-gray-500 rounded-full flex items-center justify-center"
                            >

                                <i
                                    class="fa-solid fa-inbox"
                                ></i>

                            </div>


                            <p
                                class="font-semibold text-gray-700 mt-3"
                            >
                                Tidak ada pesanan menunggu
                            </p>

                            <p
                                class="text-sm text-gray-500 mt-1"
                            >
                                Semua pesanan sudah diproses.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>



        <!-- ==================================================
             AKSI CEPAT
        ================================================== -->

        <div class="mt-8">

            <h3
                class="text-lg font-bold text-gray-800 mb-4"
            >
                Akses Cepat
            </h3>


            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-4"
            >


                <!-- ======================================
                     KELOLA PRODUK
                ======================================= -->

                <a
                    href="admin_produk.php"
                    class="bg-white border border-gray-200 rounded-xl p-5 hover:border-blue-400 hover:shadow-md transition group"
                >

                    <div
                        class="flex items-center gap-4"
                    >

                        <div
                            class="w-12 h-12 bg-blue-100 text-blue-700 rounded-lg flex items-center justify-center group-hover:bg-blue-700 group-hover:text-white transition"
                        >

                            <i
                                class="fa-solid fa-box text-lg"
                            ></i>

                        </div>


                        <div>

                            <h4
                                class="font-bold text-gray-800"
                            >
                                Kelola Produk
                            </h4>

                            <p
                                class="text-sm text-gray-500 mt-1"
                            >
                                Kelola produk dan stok
                            </p>

                        </div>

                    </div>

                </a>



                <!-- ======================================
                     KELOLA PESANAN
                ======================================= -->

                <a
                    href="pesanan.php"
                    class="bg-white border border-gray-200 rounded-xl p-5 hover:border-blue-400 hover:shadow-md transition group"
                >

                    <div
                        class="flex items-center gap-4"
                    >

                        <div
                            class="w-12 h-12 bg-purple-100 text-purple-700 rounded-lg flex items-center justify-center group-hover:bg-purple-700 group-hover:text-white transition"
                        >

                            <i
                                class="fa-solid fa-cart-shopping text-lg"
                            ></i>

                        </div>


                        <div>

                            <h4
                                class="font-bold text-gray-800"
                            >
                                Kelola Pesanan
                            </h4>

                            <p
                                class="text-sm text-gray-500 mt-1"
                            >
                                Lihat dan proses pesanan
                            </p>

                        </div>

                    </div>

                </a>



                <!-- ======================================
                     LIHAT WEBSITE
                ======================================= -->

                <a
                    href="index.php"
                    target="_blank"
                    class="bg-white border border-gray-200 rounded-xl p-5 hover:border-blue-400 hover:shadow-md transition group"
                >

                    <div
                        class="flex items-center gap-4"
                    >

                        <div
                            class="w-12 h-12 bg-green-100 text-green-700 rounded-lg flex items-center justify-center group-hover:bg-green-700 group-hover:text-white transition"
                        >

                            <i
                                class="fa-solid fa-globe text-lg"
                            ></i>

                        </div>


                        <div>

                            <h4
                                class="font-bold text-gray-800"
                            >
                                Lihat Website
                            </h4>

                            <p
                                class="text-sm text-gray-500 mt-1"
                            >
                                Buka halaman website
                            </p>

                        </div>

                    </div>

                </a>

            </div>

        </div>


    </section>

</main>


</body>

</html>