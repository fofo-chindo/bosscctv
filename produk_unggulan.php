<?php

session_start();
require_once 'koneksi.php';

// ======================================================
// PROTEKSI
// ======================================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    !in_array(
        $_SESSION['role'],
        ['admin', 'owner']
    )
) {

    header("Location: login.php");
    exit;
}

// ======================================================
// AMBIL PRODUK UNGGULAN
// ======================================================

$query =
    $koneksi->query("
        SELECT
            p.*,
            c.nama_kategori
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.is_active = 1
        AND p.is_featured = 1
        ORDER BY p.id DESC
    ");

$total =
    $query->num_rows;

?>
<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Produk Unggulan - BOSS CCTV
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

</head>

<body class="bg-gray-50 min-h-screen">

<div class="max-w-7xl mx-auto p-6 md:p-10">

    <!-- HEADER -->

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">

        <div class="flex items-center gap-4">

            <a
                href="admin_dashboard.php"
                class="w-10 h-10 bg-white border rounded-lg flex items-center justify-center hover:bg-gray-100"
            >

                <i class="fa-solid fa-arrow-left"></i>

            </a>

            <div>

                <h1 class="text-2xl font-bold text-gray-900">

                    Produk Unggulan

                </h1>

                <p class="text-gray-500 text-sm">

                    Daftar produk yang dipilih sebagai Produk Unggulan.

                </p>

            </div>

        </div>

        <a
            href="admin_produk.php"
            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium"
        >

            <i class="fa-solid fa-box"></i>

            Kelola Produk

        </a>

    </div>

    <!-- INFO -->

    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 mb-8">

        <div class="flex gap-3">

            <i class="fa-solid fa-star text-yellow-500 mt-1"></i>

            <div>

                <h2 class="font-bold text-yellow-800">

                    <?= $total ?> Produk Unggulan

                </h2>

                <p class="text-sm text-yellow-700 mt-1">

                    Produk di halaman ini hanya berasal dari produk
                    yang memiliki status <b>is_featured = 1</b>.

                </p>

            </div>

        </div>

    </div>

    <!-- PRODUK -->

    <?php if (
        $total > 0
    ): ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

            <?php while (
                $produk =
                $query->fetch_assoc()
            ): ?>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl transition">

                    <!-- GAMBAR -->

                    <div class="h-56 bg-gray-100 flex items-center justify-center">

                        <?php if (
                            !empty(
                                $produk['gambar']
                            ) &&
                            file_exists(
                                'uploads/' .
                                $produk['gambar']
                            )
                        ): ?>

                            <img
                                src="uploads/<?= htmlspecialchars(
                                    $produk['gambar']
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $produk['nama_produk']
                                ) ?>"
                                class="w-full h-full object-contain p-5"
                            >

                        <?php else: ?>

                            <i class="fa-solid fa-image text-5xl text-gray-300"></i>

                        <?php endif; ?>

                    </div>

                    <!-- INFORMASI -->

                    <div class="p-5">

                        <div class="flex items-center justify-between gap-2">

                            <span class="inline-flex items-center bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">

                                <?= htmlspecialchars(
                                    $produk['nama_kategori']
                                    ??
                                    'Tanpa Kategori'
                                ) ?>

                            </span>

                            <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full text-xs font-semibold">

                                <i class="fa-solid fa-star"></i>

                                Unggulan

                            </span>

                        </div>

                        <h2 class="font-bold text-lg text-gray-900 mt-4">

                            <?= htmlspecialchars(
                                $produk['nama_produk']
                            ) ?>

                        </h2>

                        <p class="text-xs text-gray-400 mt-1">

                            SKU:
                            <?= htmlspecialchars(
                                $produk['kode_sku']
                            ) ?>

                        </p>

                        <p class="text-xl font-bold text-blue-600 mt-4">

                            Rp
                            <?= number_format(
                                $produk['harga'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </p>

                        <div class="flex justify-between items-center mt-3">

                            <span class="text-sm text-gray-500">

                                Stok

                            </span>

                            <?php if (
                                $produk['stok'] > 0
                            ): ?>

                                <span class="text-sm font-semibold text-green-600">

                                    <?= $produk['stok'] ?>
                                    tersedia

                                </span>

                            <?php else: ?>

                                <span class="text-sm font-semibold text-red-600">

                                    Habis

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- BUTTON -->

                        <div class="mt-5 flex gap-2">

                            <a
                                href="edit_produk.php?id=<?= $produk['id'] ?>"
                                class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-2.5 rounded-lg text-sm font-medium"
                            >

                                <i class="fa-solid fa-pen-to-square mr-1"></i>

                                Edit

                            </a>

                            <a
                                href="toggle_featured.php?id=<?= $produk['id'] ?>"
                                onclick="return confirm('Hapus produk ini dari Produk Unggulan?');"
                                class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2.5 rounded-lg text-sm font-medium"
                            >

                                <i class="fa-solid fa-star-half-stroke mr-1"></i>

                                Hapus

                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <!-- KOSONG -->

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16 px-6">

            <div class="w-20 h-20 mx-auto bg-yellow-50 rounded-full flex items-center justify-center">

                <i class="fa-regular fa-star text-3xl text-yellow-400"></i>

            </div>

            <h2 class="text-xl font-bold text-gray-800 mt-5">

                Belum Ada Produk Unggulan

            </h2>

            <p class="text-gray-500 mt-2 max-w-md mx-auto">

                Belum ada produk yang dipilih sebagai Produk Unggulan.
                Silakan masuk ke Kelola Produk untuk memilih produk.

            </p>

            <a
                href="admin_produk.php"
                class="inline-flex items-center gap-2 mt-6 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg font-medium"
            >

                <i class="fa-solid fa-star"></i>

                Pilih Produk Unggulan

            </a>

        </div>

    <?php endif; ?>

</div>

</body>
</html>