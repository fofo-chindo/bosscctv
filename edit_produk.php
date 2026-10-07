<?php
session_start();
require_once 'koneksi.php';

/* =========================================================
   PROTEKSI ADMIN / OWNER
========================================================= */
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'owner'])
) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   AMBIL ID PRODUK
========================================================= */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: admin_produk.php");
    exit;
}

/* =========================================================
   AMBIL DATA PRODUK
========================================================= */
$stmt = $koneksi->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$produk = $result->fetch_assoc();

$stmt->close();

if (!$produk) {
    header("Location: admin_produk.php");
    exit;
}

/* =========================================================
   AMBIL KATEGORI YANG DIMILIKI PRODUK
========================================================= */
$kategoriProduk = [];

$stmtKategoriProduk = $koneksi->prepare("
    SELECT category_id
    FROM product_categories
    WHERE product_id = ?
");
$stmtKategoriProduk->bind_param("i", $id);
$stmtKategoriProduk->execute();

$resultKategoriProduk = $stmtKategoriProduk->get_result();

while ($rowKategoriProduk = $resultKategoriProduk->fetch_assoc()) {
    $kategoriProduk[] = (int) $rowKategoriProduk['category_id'];
}

$stmtKategoriProduk->close();

/*
    Jika produk lama belum memiliki data di product_categories,
    gunakan category_id lama sebagai cadangan.
*/
if (empty($kategoriProduk) && !empty($produk['category_id'])) {
    $kategoriProduk[] = (int) $produk['category_id'];
}

/* =========================================================
   AMBIL SEMUA KATEGORI
========================================================= */
$queryKategori = $koneksi->query("
SELECT id, nama_kategori
FROM categories
ORDER BY nama_kategori ASC
");

/* =========================================================
   PROSES UPDATE
========================================================= */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_produk'])) {

    /* =====================================================
       AMBIL MULTIPLE KATEGORI
    ===================================================== */
    $kategori_ids = $_POST['kategori_id'] ?? [];

    if (!is_array($kategori_ids)) {
        $kategori_ids = [$kategori_ids];
    }

    $kategori_ids = array_map('intval', $kategori_ids);

    $kategori_ids = array_filter(
        $kategori_ids,
        function ($idKategori) {
            return $idKategori > 0;
        }
    );

    $kategori_ids = array_values(array_unique($kategori_ids));

    /* =====================================================
       DATA PRODUK
    ===================================================== */
    $nama = trim($_POST['nama_produk'] ?? '');

    /*
        Hilangkan titik/koma agar input:
        300.000
        300,000
        300000
        semuanya menjadi 300000
    */
    $harga_input = $_POST['harga'] ?? '0';
    $harga_pemasangan_input = $_POST['harga_pemasangan'] ?? '0';

    $harga_input = str_replace(['.', ','], '', $harga_input);
    $harga_pemasangan_input = str_replace(['.', ','], '', $harga_pemasangan_input);

    $harga = (float) $harga_input;
    $harga_pemasangan = (float) $harga_pemasangan_input;

    $stok = (int) ($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    /* =====================================================
       VALIDASI
    ===================================================== */
    if (empty($kategori_ids)) {

        $error = "Silakan pilih minimal satu kategori produk.";

    } elseif ($nama === '') {

        $error = "Nama produk wajib diisi.";

    } elseif ($harga <= 0) {

        $error = "Harga CCTV harus lebih dari 0.";

    } elseif ($harga_pemasangan < $harga) {

        $error = "Harga CCTV + Pemasangan tidak boleh lebih kecil dari harga CCTV.";

    } elseif ($stok < 0) {

        $error = "Stok tidak boleh kurang dari 0.";
    }

    /* =====================================================
       VALIDASI SEMUA KATEGORI
    ===================================================== */
    if (empty($error)) {

        $stmtCekKategori = $koneksi->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");

        $kategori_valid = true;

        foreach ($kategori_ids as $kategori_id) {

            $stmtCekKategori->bind_param("i", $kategori_id);
            $stmtCekKategori->execute();

            $hasilKategori = $stmtCekKategori->get_result();

            if ($hasilKategori->num_rows === 0) {
                $kategori_valid = false;
                break;
            }
        }

        $stmtCekKategori->close();

        if (!$kategori_valid) {
            $error = "Salah satu kategori yang dipilih tidak ditemukan.";
        }
    }

    /* =====================================================
       GAMBAR
    ===================================================== */
    $gambar_baru = $produk['gambar'];
    $file_gambar_baru = null;

    if (
        empty($error) &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {

            $error = "Gagal mengupload gambar.";

        } elseif ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {

            $error = "Ukuran gambar maksimal 2 MB.";

        } else {

            $allowed = ['jpg', 'jpeg', 'png'];

            $nama_file = $_FILES['gambar']['name'];

            $extension = strtolower(
                pathinfo($nama_file, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed)) {

                $error = "Format gambar harus JPG, JPEG, atau PNG.";

            } else {

                $folder = 'uploads/';

                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                $nama_baru =
                    'produk_' .
                    time() . '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;

                $path_baru = $folder . $nama_baru;

                if (
                    !move_uploaded_file(
                        $_FILES['gambar']['tmp_name'],
                        $path_baru
                    )
                ) {

                    $error = "Gagal menyimpan gambar.";

                } else {

                    $gambar_baru = $nama_baru;
                    $file_gambar_baru = $path_baru;
                }
            }
        }
    }

    /* =====================================================
       UPDATE DATABASE
    ===================================================== */
    if (empty($error)) {

        /*
            Kategori pertama dijadikan kategori utama
            untuk menjaga kompatibilitas dengan sistem lama.
        */
        $kategori_utama = $kategori_ids[0];

        /* =================================================
           UPDATE PRODUCTS
        ================================================= */
        $stmtUpdate = $koneksi->prepare("
            UPDATE products
            SET
                category_id = ?,
                nama_produk = ?,
                deskripsi = ?,
                harga = ?,
                harga_pemasangan = ?,
                stok = ?,
                gambar = ?,
                is_featured = ?
            WHERE id = ?
        ");

        $stmtUpdate->bind_param(
            "issddisii",
            $kategori_utama,
            $nama,
            $deskripsi,
            $harga,
            $harga_pemasangan,
            $stok,
            $gambar_baru,
            $is_featured,
            $id
        );

        if (!$stmtUpdate->execute()) {

            $error = "Gagal memperbarui data produk.";

            $stmtUpdate->close();

            /* Hapus gambar baru jika update gagal */
            if (
                $file_gambar_baru &&
                file_exists($file_gambar_baru)
            ) {
                unlink($file_gambar_baru);
            }

        } else {

            $stmtUpdate->close();

            /* =============================================
               HAPUS RELASI KATEGORI LAMA
            ============================================= */
            $stmtDeleteKategori = $koneksi->prepare("
                DELETE FROM product_categories
                WHERE product_id = ?
            ");

            $stmtDeleteKategori->bind_param("i", $id);
            $stmtDeleteKategori->execute();
            $stmtDeleteKategori->close();

            /* =============================================
               MASUKKAN KATEGORI BARU
            ============================================= */
            $stmtInsertKategori = $koneksi->prepare("
                INSERT INTO product_categories
                (
                    product_id,
                    category_id
                )
                VALUES (?, ?)
            ");

            $kategoriBerhasil = true;

            foreach ($kategori_ids as $kategori_id) {

                $stmtInsertKategori->bind_param(
                    "ii",
                    $id,
                    $kategori_id
                );

                if (!$stmtInsertKategori->execute()) {
                    $kategoriBerhasil = false;
                    break;
                }
            }

            $stmtInsertKategori->close();

            /* =============================================
               JIKA GAGAL SIMPAN KATEGORI
            ============================================= */
            if (!$kategoriBerhasil) {

                $error = "Produk berhasil diperbarui, tetapi kategori gagal disimpan.";

            } else {

                /*
                    Hapus gambar lama hanya jika gambar baru
                    berhasil disimpan dan database berhasil update.
                */
                if (
                    $file_gambar_baru &&
                    !empty($produk['gambar'])
                ) {

                    $gambar_lama = 'uploads/' . $produk['gambar'];

                    if (
                        file_exists($gambar_lama) &&
                        $produk['gambar'] !== $gambar_baru
                    ) {
                        unlink($gambar_lama);
                    }
                }

                header("Location: admin_produk.php?success=produk_diperbarui");
                exit;
            }
        }
    }
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

    <title>Edit Produk - BOSS CCTV</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-slate-100">

<div class="max-w-4xl mx-auto px-4 py-10">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

        <!-- =================================================
             HEADER
        ================================================== -->
        <div class="flex items-center justify-between mb-6">

            <div>

                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Produk
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Ubah informasi dan kategori produk.
                </p>

            </div>

            <a
                href="admin_produk.php"
                class="text-sm text-blue-700 hover:text-blue-900 font-semibold"
            >
                ← Kembali
            </a>

        </div>

        <!-- =================================================
             ERROR
        ================================================== -->
        <?php if (!empty($error)): ?>

            <div class="mb-5 p-4 bg-red-50 border border-red-200
                        text-red-700 rounded-xl">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORM
        ================================================== -->
        <form
            action=""
            method="POST"
            enctype="multipart/form-data"
            class="space-y-5"
            id="formEditProduk"
        >

            <!-- =================================================
                 KATEGORI MULTIPLE
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">

                    Kategori
                    <span class="text-red-500">*</span>

                </label>

                <p class="text-xs text-gray-500 mb-2">

                    Pilih satu atau beberapa kategori yang sesuai
                    dengan produk ini.

                </p>

                <div
                    class="border border-gray-300 rounded-xl
                           p-3 bg-gray-50
                           max-h-56 overflow-y-auto"
                >

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">

                        <?php while ($kategori = $queryKategori->fetch_assoc()): ?>

                            <?php
                                $kategori_id_tampil =
                                    (int) $kategori['id'];

                                $terpilih =
                                    in_array(
                                        $kategori_id_tampil,
                                        $kategoriProduk
                                    );
                            ?>

                            <label
                                class="flex items-center gap-3
                                       p-2.5 rounded-lg
                                       hover:bg-white
                                       cursor-pointer
                                       transition"
                            >

                                <input
                                    type="checkbox"
                                    name="kategori_id[]"
                                    value="<?= $kategori_id_tampil ?>"
                                    <?= $terpilih ? 'checked' : '' ?>
                                    class="kategori-checkbox
                                           w-4 h-4
                                           accent-blue-600"
                                >

                                <span class="text-sm text-gray-700">

                                    <?= htmlspecialchars(
                                        $kategori['nama_kategori']
                                    ) ?>

                                </span>

                            </label>

                        <?php endwhile; ?>

                    </div>

                </div>

                <p
                    id="infoKategori"
                    class="text-xs mt-2"
                ></p>

            </div>


            <!-- =================================================
                 SKU
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">
                    Kode SKU
                </label>

                <input
                    type="text"
                    value="<?= htmlspecialchars($produk['kode_sku']) ?>"
                    readonly
                    class="w-full border border-gray-300 rounded-xl
                           p-3 bg-gray-100 text-gray-600"
                >

            </div>


            <!-- =================================================
                 NAMA PRODUK
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">
                    Nama Produk
                </label>

                <input
                    type="text"
                    name="nama_produk"
                    value="<?= htmlspecialchars($produk['nama_produk']) ?>"
                    required
                    maxlength="255"
                    class="w-full border border-gray-300 rounded-xl p-3
                           focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500 outline-none"
                >

            </div>


            <!-- =================================================
                 HARGA
            ================================================== -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Harga CCTV -->
                <div>

                    <label class="block text-sm font-semibold mb-2">
                        Harga CCTV
                    </label>

                    <input
                        type="text"
                        name="harga"
                        id="harga"
                        value="<?= number_format(
                            (float)$produk['harga'],
                            0,
                            ',',
                            '.'
                        ) ?>"
                        required
                        inputmode="numeric"
                        class="harga-input w-full border
                               border-gray-300 rounded-xl p-3
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500 outline-none"
                    >

                    <p class="text-xs text-gray-500 mt-1">
                        Harga perangkat CCTV saja.
                    </p>

                </div>


                <!-- Harga + Pemasangan -->
                <div>

                    <label class="block text-sm font-semibold mb-2">
                        Harga CCTV + Pemasangan
                    </label>

                    <input
                        type="text"
                        name="harga_pemasangan"
                        id="harga_pemasangan"
                        value="<?= number_format(
                            (float)$produk['harga_pemasangan'],
                            0,
                            ',',
                            '.'
                        ) ?>"
                        required
                        inputmode="numeric"
                        class="harga-input w-full border
                               border-gray-300 rounded-xl p-3
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500 outline-none"
                    >

                    <p class="text-xs text-gray-500 mt-1">
                        Harga total yang sudah termasuk pemasangan.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 STOK
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">
                    Stok
                </label>

                <input
                    type="number"
                    name="stok"
                    value="<?= (int)$produk['stok'] ?>"
                    min="0"
                    required
                    class="w-full border border-gray-300 rounded-xl
                           p-3 focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500 outline-none"
                >

            </div>


            <!-- =================================================
                 GAMBAR
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">
                    Gambar Produk
                </label>

                <?php if (!empty($produk['gambar'])): ?>

                    <div class="mb-3">

                        <p class="text-xs text-gray-500 mb-2">
                            Gambar saat ini:
                        </p>

                        <img
                            src="uploads/<?= htmlspecialchars($produk['gambar']) ?>"
                            alt="Gambar Produk"
                            class="w-36 h-36 object-contain
                                   border border-gray-200
                                   rounded-xl bg-gray-50 p-2"
                        >

                    </div>

                <?php endif; ?>


                <input
                    type="file"
                    name="gambar"
                    accept=".jpg,.jpeg,.png"
                    class="w-full border border-gray-300
                           rounded-xl p-3"
                >

                <p class="text-xs text-gray-500 mt-1">
                    Kosongkan jika tidak ingin mengganti gambar.
                    Maksimal 2 MB. JPG, JPEG, PNG.
                </p>

            </div>


            <!-- =================================================
                 FEATURED
            ================================================== -->
            <div
                class="flex items-center gap-3
                       p-4 bg-gray-50 rounded-xl
                       border border-gray-200"
            >

                <input
                    type="checkbox"
                    name="is_featured"
                    value="1"
                    <?= (int)$produk['is_featured'] === 1 ? 'checked' : '' ?>
                    class="w-5 h-5 accent-blue-600"
                >

                <div>

                    <label class="font-semibold text-gray-800">
                        Jadikan Produk Unggulan
                    </label>

                    <p class="text-xs text-gray-500">
                        Produk akan ditampilkan sebagai produk unggulan.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 DESKRIPSI
            ================================================== -->
            <div>

                <label class="block text-sm font-semibold mb-2">
                    Deskripsi
                </label>

                <textarea
                    name="deskripsi"
                    rows="5"
                    class="w-full border border-gray-300 rounded-xl p-3
                           focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500 outline-none"
                ><?= htmlspecialchars($produk['deskripsi']) ?></textarea>

            </div>


            <!-- =================================================
                 TOMBOL
            ================================================== -->
            <div class="flex flex-col sm:flex-row gap-3 pt-3">

                <a
                    href="admin_produk.php"
                    class="px-6 py-3 bg-gray-200
                           hover:bg-gray-300
                           rounded-xl font-semibold
                           text-center transition"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    name="update_produk"
                    class="px-6 py-3 bg-blue-700
                           hover:bg-blue-800
                           text-white rounded-xl
                           font-semibold transition"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->
<script>

/* =========================================================
   INFORMASI JUMLAH KATEGORI
========================================================= */

const kategoriCheckboxes =
    document.querySelectorAll('.kategori-checkbox');

const infoKategori =
    document.getElementById('infoKategori');

function updateInfoKategori() {

    const jumlah =
        document.querySelectorAll(
            '.kategori-checkbox:checked'
        ).length;

    if (jumlah === 0) {

        infoKategori.textContent =
            'Belum ada kategori dipilih.';

        infoKategori.className =
            'text-xs text-red-500 mt-2';

    } else {

        infoKategori.textContent =
            jumlah + ' kategori dipilih.';

        infoKategori.className =
            'text-xs text-blue-600 mt-2';
    }
}

kategoriCheckboxes.forEach(function (checkbox) {

    checkbox.addEventListener(
        'change',
        updateInfoKategori
    );

});

updateInfoKategori();


/* =========================================================
   FORMAT HARGA
========================================================= */

const inputHarga =
    document.querySelectorAll('.harga-input');

inputHarga.forEach(function(input) {

    input.addEventListener('input', function() {

        let angka =
            this.value.replace(/[^0-9]/g, '');

        if (angka === '') {
            this.value = '';
            return;
        }

        this.value =
            new Intl.NumberFormat('id-ID').format(
                parseInt(angka, 10)
            );
    });

});


/* =========================================================
   VALIDASI FORM
========================================================= */

const formEditProduk =
    document.getElementById('formEditProduk');

formEditProduk.addEventListener(
    'submit',
    function(event) {

        const jumlahKategori =
            document.querySelectorAll(
                '.kategori-checkbox:checked'
            ).length;

        if (jumlahKategori === 0) {

            event.preventDefault();

            alert(
                'Silakan pilih minimal satu kategori produk.'
            );

            return false;
        }


        const harga =
            document.getElementById('harga')
                .value
                .replace(/[^0-9]/g, '');

        const hargaPemasangan =
            document.getElementById('harga_pemasangan')
                .value
                .replace(/[^0-9]/g, '');

        if (
            harga !== '' &&
            hargaPemasangan !== '' &&
            parseInt(hargaPemasangan, 10) <
            parseInt(harga, 10)
        ) {

            event.preventDefault();

            alert(
                'Harga CCTV + Pemasangan tidak boleh lebih kecil dari harga CCTV.'
            );

            return false;
        }

    }
);

</script>

</body>
</html>