<?php
session_start();
require_once 'koneksi.php';

// ======================================================
// PROTEKSI ADMIN & OWNER
// ======================================================
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'owner'])
) {
    header("Location: login.php");
    exit;
}

// ======================================================
// PESAN
// ======================================================
$pesan = '';
$tipe_pesan = '';

// ======================================================
// TAMBAH MEREK
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_merek'])) {

    $nama_merek = trim($_POST['nama_merek'] ?? '');

    if ($nama_merek === '') {

        $pesan = 'Nama merek wajib diisi.';
        $tipe_pesan = 'error';

    } else {

        // Cek apakah merek sudah ada
        $cek = $koneksi->prepare("
            SELECT id
            FROM brands
            WHERE LOWER(nama_merek) = LOWER(?)
            LIMIT 1
        ");

        $cek->bind_param("s", $nama_merek);
        $cek->execute();
        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = 'Merek tersebut sudah tersedia.';
            $tipe_pesan = 'error';

        } else {

            // Buat slug
            $slug = strtolower($nama_merek);
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
            $slug = trim($slug, '-');

            // Pastikan slug unik
            $slug_awal = $slug;
            $nomor = 1;

            while (true) {

                $cek_slug = $koneksi->prepare("
                    SELECT id
                    FROM brands
                    WHERE slug = ?
                    LIMIT 1
                ");

                $cek_slug->bind_param("s", $slug);
                $cek_slug->execute();
                $hasil_slug = $cek_slug->get_result();

                if ($hasil_slug->num_rows === 0) {
                    break;
                }

                $slug = $slug_awal . '-' . $nomor;
                $nomor++;
            }

            $stmt = $koneksi->prepare("
                INSERT INTO brands
                (nama_merek, slug, is_active)
                VALUES (?, ?, 1)
            ");

            $stmt->bind_param("ss", $nama_merek, $slug);

            if ($stmt->execute()) {

                header("Location: admin_merek.php?sukses=tambah");
                exit;

            } else {

                $pesan = 'Gagal menambahkan merek.';
                $tipe_pesan = 'error';
            }
        }
    }
}

// ======================================================
// EDIT MEREK
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_merek'])) {

    $id = (int)($_POST['id'] ?? 0);
    $nama_merek = trim($_POST['nama_merek'] ?? '');

    if ($id <= 0 || $nama_merek === '') {

        $pesan = 'Data merek tidak valid.';
        $tipe_pesan = 'error';

    } else {

        // Cek nama merek duplikat
        $cek = $koneksi->prepare("
            SELECT id
            FROM brands
            WHERE LOWER(nama_merek) = LOWER(?)
              AND id != ?
            LIMIT 1
        ");

        $cek->bind_param("si", $nama_merek, $id);
        $cek->execute();

        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = 'Nama merek tersebut sudah digunakan.';
            $tipe_pesan = 'error';

        } else {

            // Buat slug baru
            $slug = strtolower($nama_merek);
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
            $slug = trim($slug, '-');

            $slug_awal = $slug;
            $nomor = 1;

            while (true) {

                $cek_slug = $koneksi->prepare("
                    SELECT id
                    FROM brands
                    WHERE slug = ?
                      AND id != ?
                    LIMIT 1
                ");

                $cek_slug->bind_param("si", $slug, $id);
                $cek_slug->execute();

                $hasil_slug = $cek_slug->get_result();

                if ($hasil_slug->num_rows === 0) {
                    break;
                }

                $slug = $slug_awal . '-' . $nomor;
                $nomor++;
            }

            $stmt = $koneksi->prepare("
                UPDATE brands
                SET nama_merek = ?, slug = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssi", $nama_merek, $slug, $id);

            if ($stmt->execute()) {

                header("Location: admin_merek.php?sukses=edit");
                exit;

            } else {

                $pesan = 'Gagal mengedit merek.';
                $tipe_pesan = 'error';
            }
        }
    }
}

// ======================================================
// AKTIF / NONAKTIF MEREK
// ======================================================
if (isset($_GET['toggle'])) {

    $id = (int)$_GET['toggle'];

    if ($id > 0) {

        $stmt = $koneksi->prepare("
            UPDATE brands
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: admin_merek.php");
    exit;
}

// ======================================================
// PESAN SUKSES
// ======================================================
if (isset($_GET['sukses'])) {

    if ($_GET['sukses'] === 'tambah') {
        $pesan = 'Merek berhasil ditambahkan.';
        $tipe_pesan = 'success';
    }

    if ($_GET['sukses'] === 'edit') {
        $pesan = 'Merek berhasil diperbarui.';
        $tipe_pesan = 'success';
    }
}

// ======================================================
// PENCARIAN
// ======================================================
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $search_like = '%' . $search . '%';

    $stmt = $koneksi->prepare("
        SELECT
            b.id,
            b.nama_merek,
            b.slug,
            b.is_active,
            COUNT(p.id) AS jumlah_produk
        FROM brands b
        LEFT JOIN products p
            ON p.brand_id = b.id
        WHERE b.nama_merek LIKE ?
        GROUP BY
            b.id,
            b.nama_merek,
            b.slug,
            b.is_active
        ORDER BY b.nama_merek ASC
    ");

    $stmt->bind_param("s", $search_like);
    $stmt->execute();

    $result_merek = $stmt->get_result();

} else {

    $result_merek = $koneksi->query("
        SELECT
            b.id,
            b.nama_merek,
            b.slug,
            b.is_active,
            COUNT(p.id) AS jumlah_produk
        FROM brands b
        LEFT JOIN products p
            ON p.brand_id = b.id
        GROUP BY
            b.id,
            b.nama_merek,
            b.slug,
            b.is_active
        ORDER BY b.nama_merek ASC
    ");
}

// ======================================================
// STATISTIK
// ======================================================
$total_merek = 0;
$merek_aktif = 0;
$merek_nonaktif = 0;

$statistik = $koneksi->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1) AS aktif,
        SUM(is_active = 0) AS nonaktif
    FROM brands
");

if ($statistik) {

    $stat = $statistik->fetch_assoc();

    $total_merek = (int)($stat['total'] ?? 0);
    $merek_aktif = (int)($stat['aktif'] ?? 0);
    $merek_nonaktif = (int)($stat['nonaktif'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Merek - POS Admin CCTV</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="w-64 bg-[#0f172a] text-white flex-shrink-0 flex flex-col">

    <!-- LOGO -->
    <div class="h-20 flex items-center px-6 border-b border-gray-700">

        <div>

            <h1 class="text-xl font-bold">
                POS Admin CCTV
            </h1>

            <p class="text-xs text-gray-400">
                BOSS CCTV
            </p>

        </div>

    </div>


    <!-- MENU -->
    <nav class="flex-1 px-4 py-6 space-y-2">

        <!-- DASHBOARD -->
        <a
            href="admin_dashboard.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition"
        >

            <i class="fa-solid fa-chart-line w-5"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- PRODUK -->
        <a
            href="admin_produk.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition"
        >

            <i class="fa-solid fa-box w-5"></i>

            <span>
                Kelola Stok & Produk
            </span>

        </a>


        <!-- MEREK -->
        <a
            href="admin_merek.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg bg-gray-800 text-white"
        >

            <i class="fa-solid fa-tags w-5"></i>

            <span>
                Kelola Merek
            </span>

        </a>


        <!-- PESANAN -->
        <a
            href="pesanan.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition"
        >

            <i class="fa-solid fa-cart-shopping w-5"></i>

            <span>
                Pesanan
            </span>

        </a>


        <!-- WEBSITE -->
        <a
            href="index.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition"
        >

            <i class="fa-solid fa-globe w-5"></i>

            <span>
                Lihat Website
            </span>

        </a>

    </nav>


    <!-- LOGOUT -->
    <div class="p-4 border-t border-gray-700">

        <a
            href="logout.php"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition"
        >

            <i class="fa-solid fa-right-from-bracket w-5"></i>

            <span>
                Keluar
            </span>

        </a>

    </div>

</aside>


<!-- ======================================================
     MAIN
====================================================== -->

<main class="flex-1 overflow-y-auto">

    <div class="p-8 max-w-7xl mx-auto">


        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">

            <div>

                <h2 class="text-2xl font-bold text-gray-800">
                    Kelola Merek
                </h2>

                <p class="text-gray-500 text-sm mt-1">
                    Tambahkan atau ubah merek CCTV yang tersedia.
                </p>

            </div>


            <!-- KEMBALI KE PRODUK -->
            <a
                href="admin_produk.php"
                class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-sm"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Kembali ke Produk

            </a>

        </div>


        <!-- ==================================================
             NOTIFIKASI
        ================================================== -->

        <?php if ($pesan !== ''): ?>

            <div
                class="<?= $tipe_pesan === 'success'
                    ? 'bg-green-50 border-green-200 text-green-700'
                    : 'bg-red-50 border-red-200 text-red-700'
                ?>
                border px-4 py-3 rounded-lg mb-6 flex items-center gap-3"
            >

                <i
                    class="fa-solid <?= $tipe_pesan === 'success'
                        ? 'fa-circle-check'
                        : 'fa-circle-exclamation'
                    ?>"
                ></i>

                <span>
                    <?= htmlspecialchars($pesan) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             STATISTIK
        ================================================== -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">

            <!-- TOTAL -->
            <div class="bg-white border border-gray-200 rounded-xl p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Total Merek
                        </p>

                        <p class="text-2xl font-bold text-gray-800 mt-1">
                            <?= $total_merek ?>
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">

                        <i class="fa-solid fa-tags"></i>

                    </div>

                </div>

            </div>


            <!-- AKTIF -->
            <div class="bg-white border border-gray-200 rounded-xl p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Merek Aktif
                        </p>

                        <p class="text-2xl font-bold text-green-600 mt-1">
                            <?= $merek_aktif ?>
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                </div>

            </div>


            <!-- NONAKTIF -->
            <div class="bg-white border border-gray-200 rounded-xl p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Merek Nonaktif
                        </p>

                        <p class="text-2xl font-bold text-red-600 mt-1">
                            <?= $merek_nonaktif ?>
                        </p>

                    </div>

                    <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
             FORM TAMBAH + SEARCH
        ================================================== -->

        <div class="bg-white border border-gray-200 rounded-xl mb-6">

            <div class="p-6 border-b border-gray-200">

                <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:justify-between">

                    <!-- FORM TAMBAH -->
                    <form
                        method="POST"
                        class="flex flex-col sm:flex-row gap-3 flex-1"
                    >

                        <div class="flex-1">

                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Merek Baru
                            </label>

                            <input
                                type="text"
                                name="nama_merek"
                                required
                                maxlength="100"
                                placeholder="Contoh: Hikvision"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >

                        </div>

                        <div class="flex items-end">

                            <button
                                type="submit"
                                name="tambah_merek"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2"
                            >

                                <i class="fa-solid fa-plus"></i>

                                Tambah Merek

                            </button>

                        </div>

                    </form>


                    <!-- SEARCH -->
                    <form
                        method="GET"
                        class="w-full lg:w-72"
                    >

                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cari Merek
                        </label>

                        <div class="relative">

                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Cari nama merek..."
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >

                        </div>

                    </form>

                </div>

            </div>


            <!-- ==================================================
                 TABEL MEREK
            ================================================== -->

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-50 border-b border-gray-200">

                        <tr>

                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                No
                            </th>

                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                Nama Merek
                            </th>

                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                Produk
                            </th>

                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                Status
                            </th>

                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                    <?php if ($result_merek && $result_merek->num_rows > 0): ?>

                        <?php
                        $no = 1;
                        while ($row = $result_merek->fetch_assoc()):
                        ?>

                            <tr class="hover:bg-gray-50 transition">

                                <!-- NO -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?= $no++ ?>
                                </td>


                                <!-- MEREK -->
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">

                                            <i class="fa-solid fa-tag"></i>

                                        </div>

                                        <div>

                                            <p class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($row['nama_merek']) ?>
                                            </p>

                                            <p class="text-xs text-gray-400">
                                                <?= htmlspecialchars($row['slug']) ?>
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <!-- JUMLAH PRODUK -->
                                <td class="px-6 py-4">

                                    <span class="text-sm font-medium text-gray-700">

                                        <?= (int)$row['jumlah_produk'] ?>

                                        produk

                                    </span>

                                </td>


                                <!-- STATUS -->
                                <td class="px-6 py-4">

                                    <?php if ((int)$row['is_active'] === 1): ?>

                                        <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 px-2.5 py-1 rounded-full text-xs font-medium">

                                            <i class="fa-solid fa-circle text-[7px]"></i>

                                            Aktif

                                        </span>

                                    <?php else: ?>

                                        <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-full text-xs font-medium">

                                            <i class="fa-solid fa-circle text-[7px]"></i>

                                            Nonaktif

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- AKSI -->
                                <td class="px-6 py-4">

                                    <div class="flex items-center justify-end gap-2">


                                        <!-- EDIT -->
                                        <button
                                            type="button"
                                            onclick="editMerek(
                                                <?= (int)$row['id'] ?>,
                                                '<?= htmlspecialchars(
                                                    $row['nama_merek'],
                                                    ENT_QUOTES
                                                ) ?>'
                                            )"
                                            class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                                            title="Edit Merek"
                                        >

                                            <i class="fa-solid fa-pen"></i>

                                        </button>


                                        <!-- AKTIF / NONAKTIF -->
                                        <?php if ((int)$row['is_active'] === 1): ?>

                                            <a
                                                href="admin_merek.php?toggle=<?= (int)$row['id'] ?>"
                                                onclick="return confirm('Nonaktifkan merek ini?');"
                                                class="w-9 h-9 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 transition inline-flex items-center justify-center"
                                                title="Nonaktifkan"
                                            >

                                                <i class="fa-solid fa-eye-slash"></i>

                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="admin_merek.php?toggle=<?= (int)$row['id'] ?>"
                                                onclick="return confirm('Aktifkan kembali merek ini?');"
                                                class="w-9 h-9 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition inline-flex items-center justify-center"
                                                title="Aktifkan"
                                            >

                                                <i class="fa-solid fa-eye"></i>

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                class="px-6 py-12 text-center"
                            >

                                <div class="text-gray-400">

                                    <i class="fa-solid fa-tags text-4xl mb-3"></i>

                                    <p class="font-medium">
                                        Belum ada merek
                                    </p>

                                    <p class="text-sm mt-1">
                                        Silakan tambahkan merek baru.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>


<!-- ======================================================
     MODAL EDIT MEREK
====================================================== -->

<div
    id="modalEdit"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4"
>

    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">

        <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">

            <h3 class="text-lg font-bold text-gray-800">
                Edit Merek
            </h3>

            <button
                type="button"
                onclick="closeEditModal()"
                class="text-gray-400 hover:text-gray-600 text-xl"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form method="POST">

            <div class="p-6">

                <input
                    type="hidden"
                    name="id"
                    id="edit_id"
                >

                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Merek
                </label>

                <input
                    type="text"
                    name="nama_merek"
                    id="edit_nama_merek"
                    required
                    maxlength="100"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

            </div>


            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-sm font-medium"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    name="edit_merek"
                    class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function editMerek(id, nama) {

    document.getElementById('edit_id').value = id;

    document.getElementById('edit_nama_merek').value = nama;

    const modal = document.getElementById('modalEdit');

    modal.classList.remove('hidden');

    modal.classList.add('flex');

}


function closeEditModal() {

    const modal = document.getElementById('modalEdit');

    modal.classList.add('hidden');

    modal.classList.remove('flex');

}


// Klik area luar modal untuk menutup
document.getElementById('modalEdit').addEventListener('click', function(e) {

    if (e.target === this) {
        closeEditModal();
    }

});

</script>

</body>
</html>