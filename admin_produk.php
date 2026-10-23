<?php

session_start();
require_once 'koneksi.php';

// ======================================================
// PROTEKSI ADMIN / OWNER
// ======================================================

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'owner'])
) {
    header("Location: login.php");
    exit;
}


// ======================================================
// FUNGSI BANTU
// ======================================================

function cleanPrice($value)
{
    return (float) str_replace(
        ['.', ',', 'Rp', ' '],
        '',
        (string)$value
    );
}

function rupiah($value)
{
    return 'Rp ' . number_format(
        (float)$value,
        0,
        ',',
        '.'
    );
}


// ======================================================
// AJAX TAMBAH MEREK
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ajax_tambah_merek'])
) {
    header('Content-Type: application/json; charset=utf-8');

    $nama_merek = trim(
        $_POST['nama_merek'] ?? ''
    );

    if ($nama_merek === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Nama merek wajib diisi.'
        ]);
        exit;
    }

    // Cek nama merek
    $cek = $koneksi->prepare("
        SELECT id, nama_merek, is_active
        FROM brands
        WHERE LOWER(TRIM(nama_merek)) = LOWER(TRIM(?))
        LIMIT 1
    ");

    $cek->bind_param(
        "s",
        $nama_merek
    );

    $cek->execute();

    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {

        $merek_lama = $hasil->fetch_assoc();

        $cek->close();

        echo json_encode([
            'success' => false,
            'message' => 'Merek "' . $merek_lama['nama_merek'] . '" sudah tersedia.'
        ]);

        exit;
    }

    $cek->close();


    // Buat slug
    $slug = strtolower($nama_merek);

    $slug = preg_replace(
        '/[^a-z0-9]+/i',
        '-',
        $slug
    );

    $slug = trim(
        $slug,
        '-'
    );

    if ($slug === '') {
        $slug = 'merek-' . time();
    }

    $slug_awal = $slug;
    $nomor = 2;

    while (true) {

        $cek_slug = $koneksi->prepare("
            SELECT id
            FROM brands
            WHERE slug = ?
            LIMIT 1
        ");

        $cek_slug->bind_param(
            "s",
            $slug
        );

        $cek_slug->execute();

        $hasil_slug = $cek_slug->get_result();

        if ($hasil_slug->num_rows === 0) {

            $cek_slug->close();

            break;
        }

        $cek_slug->close();

        $slug =
            $slug_awal .
            '-' .
            $nomor;

        $nomor++;
    }


    // Simpan
    $stmt = $koneksi->prepare("
        INSERT INTO brands
        (
            nama_merek,
            slug,
            is_active
        )
        VALUES (?, ?, 1)
    ");

    $stmt->bind_param(
        "ss",
        $nama_merek,
        $slug
    );

    if ($stmt->execute()) {

        $id_baru = $stmt->insert_id;

        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Merek berhasil ditambahkan.',
            'id' => $id_baru,
            'nama_merek' => $nama_merek
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan merek.'
        ]);

        $stmt->close();
    }

    exit;
}


// ======================================================
// AJAX AMBIL DATA PRODUK UNTUK EDIT
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    ($_GET['ajax'] ?? '') === 'ambil_produk'
) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)(
        $_GET['id'] ?? 0
    );

    if ($id <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'ID produk tidak valid.'
        ]);

        exit;
    }


    // Ambil produk
    $stmt = $koneksi->prepare("
        SELECT
            id,
            category_id,
            brand_id,
            kode_sku,
            nama_produk,
            deskripsi,
            spesifikasi,
            harga,
            harga_pemasangan,
            stok,
            gambar,
            is_active,
            is_featured,
            parent_id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $hasil = $stmt->get_result();

    if ($hasil->num_rows === 0) {

        $stmt->close();

        echo json_encode([
            'success' => false,
            'message' => 'Produk tidak ditemukan.'
        ]);

        exit;
    }

    $produk = $hasil->fetch_assoc();
    $stmt->close();

    // Jika edit diklik pada sebuah varian, gunakan produk induknya.
    if (!empty($produk['parent_id'])) {
        $idInduk = (int)$produk['parent_id'];
        $stmtInduk = $koneksi->prepare("
            SELECT id, category_id, brand_id, kode_sku, nama_produk, deskripsi, spesifikasi,
                   harga, harga_pemasangan, stok, gambar, is_active, is_featured, parent_id
            FROM products
            WHERE id = ? AND parent_id IS NULL
            LIMIT 1
        ");
        $stmtInduk->bind_param("i", $idInduk);
        $stmtInduk->execute();
        $hasilInduk = $stmtInduk->get_result();
        if ($hasilInduk->num_rows === 0) {
            $stmtInduk->close();
            echo json_encode(['success' => false, 'message' => 'Produk induk tidak ditemukan.']);
            exit;
        }
        $produk = $hasilInduk->fetch_assoc();
        $stmtInduk->close();
        $id = $idInduk;
    }

    // Ambil semua kategori
    $kategori_ids = [];

    $stmt_kat = $koneksi->prepare("
        SELECT category_id
        FROM product_categories
        WHERE product_id = ?
        ORDER BY category_id ASC
    ");

    $stmt_kat->bind_param(
        "i",
        $id
    );

    $stmt_kat->execute();

    $hasil_kat =
        $stmt_kat->get_result();

    while (
        $kat = $hasil_kat->fetch_assoc()
    ) {

        $kategori_ids[] =
            (int)$kat['category_id'];
    }

    $stmt_kat->close();


    // Fallback ke category_id lama
    if (
        empty($kategori_ids) &&
        !empty($produk['category_id'])
    ) {

        $kategori_ids[] =
            (int)$produk['category_id'];
    }


    // Ambil semua varian aktif
    $varianProduk = [];
    $stmtVarianAjax = $koneksi->prepare("
        SELECT id, kode_sku, nama_produk, harga, stok, gambar, is_active
        FROM products
        WHERE parent_id = ? AND is_active = 1
        ORDER BY id ASC
    ");
    $stmtVarianAjax->bind_param("i", $id);
    $stmtVarianAjax->execute();
    $hasilVarianAjax = $stmtVarianAjax->get_result();
    while ($v = $hasilVarianAjax->fetch_assoc()) {
        $varianProduk[] = [
            'id' => (int)$v['id'],
            'kode_sku' => $v['kode_sku'],
            'nama_produk' => $v['nama_produk'],
            'harga' => (float)$v['harga'],
            'stok' => (int)$v['stok'],
            'gambar' => $v['gambar'],
            'is_active' => (int)$v['is_active'],
        ];
    }
    $stmtVarianAjax->close();

    echo json_encode([
        'success' => true,
        'produk' => [
            'id' => (int)$produk['id'],
            'category_id' => (int)$produk['category_id'],
            'brand_id' => (int)$produk['brand_id'],
            'kode_sku' => $produk['kode_sku'],
            'nama_produk' => $produk['nama_produk'],
            'deskripsi' => $produk['deskripsi'],
            'spesifikasi' => $produk['spesifikasi'],
            'harga' => (float)$produk['harga'],
            'harga_pemasangan' =>
                $produk['harga_pemasangan'] !== null
                    ? (float)$produk['harga_pemasangan']
                    : null,
            'stok' => (int)$produk['stok'],
            'gambar' => $produk['gambar'],
            'is_active' => (int)$produk['is_active'],
            'is_featured' => (int)$produk['is_featured'],
            'parent_id' => !empty($produk['parent_id']) ? (int)$produk['parent_id'] : null,
            'is_paket_cctv' => count($varianProduk) > 0,
            'varian' => $varianProduk,
            'kategori_ids' => $kategori_ids
        ]
    ]);

    exit;
}


// ======================================================
// PROSES TAMBAH PAKET CCTV (INDUK + SEMUA VARIAN)
// ======================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['simpan_paket_cctv'])
) {
    $nama_dasar = trim($_POST['nama_paket'] ?? '');
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');
    $kategori_ids = $_POST['kategori_id'] ?? [];

    if (!is_array($kategori_ids)) {
        $kategori_ids = [$kategori_ids];
    }
    $kategori_ids = array_values(array_unique(array_filter(array_map('intval', $kategori_ids), static fn($id) => $id > 0)));

    $v_nama_list = $_POST['paket_v_nama'] ?? [];
    $v_harga_list = $_POST['paket_v_harga'] ?? [];
    $v_stok_list = $_POST['paket_v_stok'] ?? [];

    $varianInput = [];
    foreach ($v_nama_list as $index => $namaVarian) {
        $namaVarian = trim((string)$namaVarian);
        if ($namaVarian === '') {
            continue;
        }
        $hargaVarian = cleanPrice($v_harga_list[$index] ?? 0);
        $stokVarian = filter_var($v_stok_list[$index] ?? null, FILTER_VALIDATE_INT);
        if ($hargaVarian <= 0 || $stokVarian === false || $stokVarian < 0) {
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Setiap varian wajib memiliki harga lebih dari 0 dan stok tidak boleh negatif."));
            exit;
        }
        $varianInput[] = [
            'source_index' => $index,
            'nama' => $namaVarian,
            'harga' => $hargaVarian,
            'stok' => $stokVarian,
            'gambar' => ''
        ];
    }

    if ($nama_dasar === '' || $brand_id <= 0 || empty($kategori_ids) || empty($varianInput)) {
        header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Mohon lengkapi nama paket, merek, kategori, dan minimal satu varian."));
        exit;
    }

    $cekBrand = $koneksi->prepare("SELECT id FROM brands WHERE id = ? AND is_active = 1 LIMIT 1");
    $cekBrand->bind_param("i", $brand_id);
    $cekBrand->execute();
    $brandValid = $cekBrand->get_result()->num_rows > 0;
    $cekBrand->close();
    if (!$brandValid) {
        header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Merek tidak ditemukan atau sedang nonaktif."));
        exit;
    }

    $cekKategori = $koneksi->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
    foreach ($kategori_ids as $kid) {
        $cekKategori->bind_param("i", $kid);
        $cekKategori->execute();
        if ($cekKategori->get_result()->num_rows === 0) {
            $cekKategori->close();
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Salah satu kategori tidak ditemukan."));
            exit;
        }
    }
    $cekKategori->close();

    foreach ($varianInput as &$varian) {
        $index = $varian['source_index'];
        if (!isset($_FILES['paket_v_gambar']['error'][$index]) ||
            $_FILES['paket_v_gambar']['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($_FILES['paket_v_gambar']['error'][$index] !== UPLOAD_ERR_OK) {
            unset($varian);
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Gagal membaca salah satu gambar varian."));
            exit;
        }
        $tmp = $_FILES['paket_v_gambar']['tmp_name'][$index];
        $ext = strtolower(pathinfo($_FILES['paket_v_gambar']['name'][$index], PATHINFO_EXTENSION));
        $size = (int)($_FILES['paket_v_gambar']['size'][$index] ?? 0);
        $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : '';
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true) || $size > 5 * 1024 * 1024 ||
            !in_array($mime, ['image/jpeg', 'image/png'], true)) {
            unset($varian);
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Gambar varian harus JPG/PNG yang valid dan maksimal 5 MB."));
            exit;
        }
        if (!is_dir('uploads') && !mkdir('uploads', 0755, true) && !is_dir('uploads')) {
            unset($varian);
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Folder uploads tidak dapat dibuat."));
            exit;
        }
        $newName = 'paket_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($tmp, 'uploads/' . $newName)) {
            unset($varian);
            header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Gagal menyimpan gambar varian."));
            exit;
        }
        $varian['gambar'] = $newName;
    }
    unset($varian);

    $kategori_utama = $kategori_ids[0];
    $varianPertama = $varianInput[0];
    $skuInduk = 'SKU-PKT-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

    $koneksi->begin_transaction();
    try {
        $stmt = $koneksi->prepare("
            INSERT INTO products
                (category_id, brand_id, kode_sku, nama_produk, deskripsi, spesifikasi, harga, harga_pemasangan, stok, gambar, is_active, is_featured, parent_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 1, 1, NULL)
        ");
        $hargaInduk = (float)$varianPertama['harga'];
        $stokInduk = (int)$varianPertama['stok'];
        $gambarInduk = (string)$varianPertama['gambar'];
        $stmt->bind_param("iissssdis", $kategori_utama, $brand_id, $skuInduk, $nama_dasar, $deskripsi, $spesifikasi, $hargaInduk, $stokInduk, $gambarInduk);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan produk induk: " . $stmt->error);
        }
        $id_induk_baru = (int)$stmt->insert_id;
        $stmt->close();

        $stmtKategori = $koneksi->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
        foreach ($kategori_ids as $kid) {
            $stmtKategori->bind_param("ii", $id_induk_baru, $kid);
            if (!$stmtKategori->execute()) {
                throw new Exception("Gagal menyimpan kategori paket: " . $stmtKategori->error);
            }
        }
        $stmtKategori->close();

        $stmtVarian = $koneksi->prepare("
            INSERT INTO products
                (category_id, brand_id, kode_sku, nama_produk, deskripsi, spesifikasi, harga, harga_pemasangan, stok, gambar, is_active, is_featured, parent_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 1, 0, ?)
        ");
        $stmtKategoriAnak = $koneksi->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");

        foreach ($varianInput as $varian) {
            $skuVarian = 'SKU-PKT-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
            $namaVarianDB = (string)$varian['nama'];
            $hargaVarianDB = (float)$varian['harga'];
            $stokVarianDB = (int)$varian['stok'];
            $gambarVarianDB = (string)$varian['gambar'];
            $stmtVarian->bind_param("iissssdisi", $kategori_utama, $brand_id, $skuVarian, $namaVarianDB, $deskripsi, $spesifikasi, $hargaVarianDB, $stokVarianDB, $gambarVarianDB, $id_induk_baru);
            if (!$stmtVarian->execute()) {
                throw new Exception("Gagal menyimpan varian {$varian['nama']}: " . $stmtVarian->error);
            }
            $idAnak = (int)$stmtVarian->insert_id;
            foreach ($kategori_ids as $kid) {
                $stmtKategoriAnak->bind_param("ii", $idAnak, $kid);
                if (!$stmtKategoriAnak->execute()) {
                    throw new Exception("Gagal menyimpan kategori varian: " . $stmtKategoriAnak->error);
                }
            }
        }
        $stmtVarian->close();
        $stmtKategoriAnak->close();

        $varianTermurah = $varianInput[0];
        foreach ($varianInput as $itemVarian) {
            if ($itemVarian['harga'] < $varianTermurah['harga']) {
                $varianTermurah = $itemVarian;
            }
        }
        $hargaKatalog = (float)$varianTermurah['harga'];
        $gambarKatalog = (string)($varianTermurah['gambar'] !== '' ? $varianTermurah['gambar'] : $gambarInduk);
        $stmtInduk = $koneksi->prepare("UPDATE products SET harga = ?, gambar = ? WHERE id = ?");
        $stmtInduk->bind_param("dsi", $hargaKatalog, $gambarKatalog, $id_induk_baru);
        if (!$stmtInduk->execute()) {
            throw new Exception("Gagal memperbarui harga katalog paket: " . $stmtInduk->error);
        }
        $stmtInduk->close();

        $koneksi->commit();
        header("Location: admin_produk.php?status=sukses_tambah");
        exit;
    } catch (Throwable $e) {
        $koneksi->rollback();
        header("Location: admin_produk.php?status=gagal&pesan=" . urlencode("Paket tidak berhasil disimpan. " . $e->getMessage()));
        exit;
    }
}


// ======================================================
// EDIT PRODUK INDUK + SELURUH VARIAN DALAM SATU FORM
// ======================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_produk']) &&
    ($_POST['edit_paket_cctv'] ?? '') === '1'
) {
    $parentId = (int)($_POST['product_id'] ?? 0);
    $namaInduk = trim((string)($_POST['nama_produk'] ?? ''));
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $spesifikasi = trim((string)($_POST['spesifikasi'] ?? ''));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    $kategoriIds = $_POST['kategori_id'] ?? [];
    if (!is_array($kategoriIds)) $kategoriIds = [$kategoriIds];
    $kategoriIds = array_values(array_unique(array_filter(array_map('intval', $kategoriIds), static fn($x) => $x > 0)));

    $varianIds = $_POST['paket_edit_id'] ?? [];
    $varianNama = $_POST['paket_edit_nama'] ?? [];
    $varianHarga = $_POST['paket_edit_harga'] ?? [];
    $varianStok = $_POST['paket_edit_stok'] ?? [];
    if (!is_array($varianIds) || !is_array($varianNama) || !is_array($varianHarga) || !is_array($varianStok)) {
        header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Data varian tidak valid.'));
        exit;
    }
    if ($parentId <= 0 || $namaInduk === '' || $brandId <= 0 || empty($kategoriIds)) {
        header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Nama produk, merek, dan kategori wajib diisi.'));
        exit;
    }
    if (count($varianNama) < 1) {
        header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Minimal harus ada satu varian aktif.'));
        exit;
    }

    $stmt = $koneksi->prepare('SELECT id FROM brands WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('i', $brandId);
    $stmt->execute();
    $brandOK = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if (!$brandOK) {
        header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Merek tidak ditemukan atau sedang nonaktif.'));
        exit;
    }

    $stmt = $koneksi->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
    foreach ($kategoriIds as $kid) {
        $stmt->bind_param('i', $kid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Salah satu kategori tidak ditemukan.'));
            exit;
        }
    }
    $stmt->close();

    $stmt = $koneksi->prepare('SELECT id, kode_sku, gambar FROM products WHERE id = ? AND parent_id IS NULL LIMIT 1');
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $parentResult = $stmt->get_result();
    if ($parentResult->num_rows === 0) {
        $stmt->close();
        header('Location: admin_produk.php?status=gagal_edit&pesan=' . urlencode('Produk induk tidak ditemukan.'));
        exit;
    }
    $parentOld = $parentResult->fetch_assoc();
    $stmt->close();

    $stmt = $koneksi->prepare('SELECT id, gambar, kode_sku FROM products WHERE parent_id = ?');
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $existingVariants = [];
    $rsExisting = $stmt->get_result();
    while ($old = $rsExisting->fetch_assoc()) $existingVariants[(int)$old['id']] = $old;
    $stmt->close();

    $preparedVariants = [];
    foreach ($varianNama as $i => $nameRaw) {
        $variantName = trim((string)$nameRaw);
        $variantId = (int)($varianIds[$i] ?? 0);
        $variantPrice = cleanPrice($varianHarga[$i] ?? 0);
        $stockRaw = filter_var($varianStok[$i] ?? null, FILTER_VALIDATE_INT);
        if ($variantName === '' || $variantPrice <= 0 || $stockRaw === false || $stockRaw < 0) {
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Setiap varian harus memiliki nama, harga lebih dari 0, dan stok minimal 0.'));
            exit;
        }
        if ($variantId > 0 && !isset($existingVariants[$variantId])) {
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('ID varian tidak cocok dengan produk induk.'));
            exit;
        }
        $preparedVariants[] = [
            'id' => $variantId,
            'nama' => $variantName,
            'harga' => (float)$variantPrice,
            'stok' => (int)$stockRaw,
            'gambar' => $variantId > 0 ? (string)($existingVariants[$variantId]['gambar'] ?? '') : '',
            'source_index' => $i,
        ];
    }

    $uploadedFiles = [];
    foreach ($preparedVariants as &$v) {
        $i = $v['source_index'];
        $uploadErr = $_FILES['paket_edit_gambar']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadErr === UPLOAD_ERR_NO_FILE) continue;
        if ($uploadErr !== UPLOAD_ERR_OK) {
            unset($v);
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Gagal membaca salah satu gambar varian.'));
            exit;
        }
        $tmp = $_FILES['paket_edit_gambar']['tmp_name'][$i] ?? '';
        $ext = strtolower(pathinfo((string)($_FILES['paket_edit_gambar']['name'][$i] ?? ''), PATHINFO_EXTENSION));
        $size = (int)($_FILES['paket_edit_gambar']['size'][$i] ?? 0);
        $mime = $tmp !== '' && function_exists('mime_content_type') ? mime_content_type($tmp) : '';
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true) || $size > 5 * 1024 * 1024 || !in_array($mime, ['image/jpeg', 'image/png'], true)) {
            unset($v);
            foreach ($uploadedFiles as $f) if (is_file('uploads/' . $f)) @unlink('uploads/' . $f);
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Gambar varian harus JPG/PNG yang valid dan maksimal 5 MB.'));
            exit;
        }
        if (!is_dir('uploads') && !mkdir('uploads', 0755, true) && !is_dir('uploads')) {
            unset($v);
            foreach ($uploadedFiles as $f) if (is_file('uploads/' . $f)) @unlink('uploads/' . $f);
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Folder uploads tidak dapat dibuat.'));
            exit;
        }
        $newImage = 'varian_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($tmp, 'uploads/' . $newImage)) {
            unset($v);
            foreach ($uploadedFiles as $f) if (is_file('uploads/' . $f)) @unlink('uploads/' . $f);
            header('Location: admin_produk.php?status=gagal&pesan=' . urlencode('Gagal menyimpan salah satu gambar varian.'));
            exit;
        }
        $v['gambar'] = $newImage;
        $uploadedFiles[] = $newImage;
    }
    unset($v);

    $kategoriUtama = (int)$kategoriIds[0];
    $koneksi->begin_transaction();
    try {
        $sumStock = array_sum(array_column($preparedVariants, 'stok'));
        $cheapest = $preparedVariants[0];
        foreach ($preparedVariants as $v) if ($v['harga'] < $cheapest['harga']) $cheapest = $v;
        $catalogImage = (string)$cheapest['gambar'];
        if ($catalogImage === '') {
            foreach ($preparedVariants as $v) if ($v['gambar'] !== '') { $catalogImage = $v['gambar']; break; }
        }
        if ($catalogImage === '') $catalogImage = (string)($parentOld['gambar'] ?? '');
        $catalogPrice = (float)$cheapest['harga'];

        $stmt = $koneksi->prepare('UPDATE products SET category_id = ?, brand_id = ?, nama_produk = ?, deskripsi = ?, spesifikasi = ?, harga = ?, harga_pemasangan = NULL, stok = ?, gambar = ?, is_featured = ? WHERE id = ? AND parent_id IS NULL');
        $stmt->bind_param('iisssdisii', $kategoriUtama, $brandId, $namaInduk, $deskripsi, $spesifikasi, $catalogPrice, $sumStock, $catalogImage, $isFeatured, $parentId);
        if (!$stmt->execute()) throw new Exception('Gagal memperbarui produk induk: ' . $stmt->error);
        $stmt->close();

        $keepIds = [];
        $stmtUpdate = $koneksi->prepare('UPDATE products SET category_id = ?, brand_id = ?, nama_produk = ?, deskripsi = ?, spesifikasi = ?, harga = ?, harga_pemasangan = NULL, stok = ?, gambar = ?, is_active = 1, is_featured = 0 WHERE id = ? AND parent_id = ?');
        $stmtInsert = $koneksi->prepare('INSERT INTO products (category_id, brand_id, kode_sku, nama_produk, deskripsi, spesifikasi, harga, harga_pemasangan, stok, gambar, is_active, is_featured, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 1, 0, ?)');
        foreach ($preparedVariants as $v) {
            if ($v['id'] > 0) {
                $idVarian = (int)$v['id'];
                $stmtUpdate->bind_param('iisssdisii', $kategoriUtama, $brandId, $v['nama'], $deskripsi, $spesifikasi, $v['harga'], $v['stok'], $v['gambar'], $idVarian, $parentId);
                if (!$stmtUpdate->execute()) throw new Exception('Gagal memperbarui varian ' . $v['nama'] . ': ' . $stmtUpdate->error);
                $keepIds[] = $idVarian;
            } else {
                $skuVarian = 'SKU-PKT-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $stmtInsert->bind_param('iissssdisi', $kategoriUtama, $brandId, $skuVarian, $v['nama'], $deskripsi, $spesifikasi, $v['harga'], $v['stok'], $v['gambar'], $parentId);
                if (!$stmtInsert->execute()) throw new Exception('Gagal menambahkan varian ' . $v['nama'] . ': ' . $stmtInsert->error);
                $keepIds[] = (int)$stmtInsert->insert_id;
            }
        }
        $stmtUpdate->close();
        $stmtInsert->close();

        $stmtDeactivate = $koneksi->prepare('UPDATE products SET is_active = 0 WHERE id = ? AND parent_id = ?');
        foreach ($existingVariants as $oldId => $_old) {
            if (!in_array((int)$oldId, $keepIds, true)) {
                $oldIdInt = (int)$oldId;
                $stmtDeactivate->bind_param('ii', $oldIdInt, $parentId);
                if (!$stmtDeactivate->execute()) throw new Exception('Gagal menonaktifkan varian yang dihapus: ' . $stmtDeactivate->error);
            }
        }
        $stmtDeactivate->close();

        $stmtDeleteCats = $koneksi->prepare('DELETE FROM product_categories WHERE product_id = ?');
        $stmtInsertCat = $koneksi->prepare('INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)');
        foreach (array_merge([$parentId], $keepIds) as $pid) {
            $stmtDeleteCats->bind_param('i', $pid);
            if (!$stmtDeleteCats->execute()) throw new Exception('Gagal memperbarui kategori produk: ' . $stmtDeleteCats->error);
            foreach ($kategoriIds as $kid) {
                $stmtInsertCat->bind_param('ii', $pid, $kid);
                if (!$stmtInsertCat->execute()) throw new Exception('Gagal menyimpan kategori produk: ' . $stmtInsertCat->error);
            }
        }
        $stmtDeleteCats->close();
        $stmtInsertCat->close();

        $koneksi->commit();
        header('Location: admin_produk.php?status=sukses_edit');
        exit;
    } catch (Throwable $e) {
        $koneksi->rollback();
        foreach ($uploadedFiles as $file) if (is_file('uploads/' . $file)) @unlink('uploads/' . $file);
        header('Location: admin_produk.php?status=gagal_edit&pesan=' . urlencode('Perubahan produk/varian gagal disimpan. ' . $e->getMessage()));
        exit;
    }
}


// ======================================================
// PROSES TAMBAH / EDIT PRODUK STANDAR ASLI
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['simpan_produk']) ||
        isset($_POST['update_produk'])
    )
) {

    $mode_edit =
        isset($_POST['update_produk']);

    $product_id =
        (int)(
            $_POST['product_id'] ?? 0
        );


    $nama =
        trim(
            $_POST['nama_produk'] ?? ''
        );

    $deskripsi =
        trim(
            $_POST['deskripsi'] ?? ''
        );
        
    $spesifikasi =
        trim(
            $_POST['spesifikasi'] ?? ''
        );

    $brand_id =
        (int)(
            $_POST['brand_id'] ?? 0
        );

    $harga =
        cleanPrice(
            $_POST['harga'] ?? 0
        );

    $harga_pemasangan_input = trim(
        $_POST['harga_pemasangan'] ?? ''
    );

    $pakai_pemasangan =
        isset($_POST['pakai_pemasangan']) &&
        $_POST['pakai_pemasangan'] === '1';

    $harga_pemasangan = null;

    if ($pakai_pemasangan) {
        if ($harga_pemasangan_input !== '') {
            $harga_pemasangan = cleanPrice($harga_pemasangan_input);
        }
    }

    $stok =
        (int)(
            $_POST['stok'] ?? 0
        );

    $is_featured =
        isset($_POST['is_featured'])
            ? 1
            : 0;


    $kategori_ids =
        $_POST['kategori_id'] ?? [];

    if (
        !is_array($kategori_ids)
    ) {

        $kategori_ids = [
            $kategori_ids
        ];
    }

    $kategori_ids =
        array_map(
            'intval',
            $kategori_ids
        );

    $kategori_ids =
        array_filter(
            $kategori_ids,
            function ($id) {
                return $id > 0;
            }
        );

    $kategori_ids =
        array_values(
            array_unique(
                $kategori_ids
            )
        );


    $error = '';


    if (
        $mode_edit &&
        $product_id <= 0
    ) {

        $error =
            'ID produk tidak valid.';

    } elseif (
        $nama === ''
    ) {

        $error =
            'Nama produk wajib diisi.';

    } elseif (
        empty($kategori_ids)
    ) {

        $error =
            'Silakan pilih minimal satu kategori.';

    } elseif (
        $brand_id <= 0
    ) {

        $error =
            'Silakan pilih merek produk.';

    } elseif (
        $harga <= 0
    ) {

        $error =
            'Harga CCTV harus lebih dari 0.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan === null
    ) {

        $error =
            'Harga pemasangan wajib diisi jika produk membutuhkan pemasangan.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan <= 0
    ) {

        $error =
            'Harga pemasangan harus lebih dari 0.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan < $harga
    ) {

        $error =
            'Harga CCTV + pemasangan tidak boleh lebih rendah dari harga CCTV.';

    } elseif (
        $stok < 0
    ) {

        $error =
            'Stok tidak boleh kurang dari 0.';
    }


    if ($error === '') {

        $stmt =
            $koneksi->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

        foreach (
            $kategori_ids
            as $kategori_id
        ) {

            $stmt->bind_param(
                "i",
                $kategori_id
            );

            $stmt->execute();

            $hasil =
                $stmt->get_result();

            if (
                $hasil->num_rows === 0
            ) {

                $error =
                    'Kategori yang dipilih tidak ditemukan.';

                break;
            }
        }

        $stmt->close();
    }


    if ($error === '') {

        $stmt =
            $koneksi->prepare("
                SELECT id
                FROM brands
                WHERE id = ?
                AND is_active = 1
                LIMIT 1
            ");

        $stmt->bind_param(
            "i",
            $brand_id
        );

        $stmt->execute();

        $hasil =
            $stmt->get_result();

        if (
            $hasil->num_rows === 0
        ) {

            $error =
                'Merek tidak ditemukan atau sedang nonaktif.';
        }

        $stmt->close();
    }


    $gambar_lama = '';

    if (
        $mode_edit &&
        $error === ''
    ) {

        $stmt =
            $koneksi->prepare("
                SELECT gambar
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

        $stmt->bind_param(
            "i",
            $product_id
        );

        $stmt->execute();

        $hasil =
            $stmt->get_result();

        if (
            $hasil->num_rows === 0
        ) {

            $error =
                'Produk yang ingin diedit tidak ditemukan.';

        } else {

            $data_lama =
                $hasil->fetch_assoc();

            $gambar_lama =
                $data_lama['gambar'];
        }

        $stmt->close();
    }


    $nama_file_baru = '';
    $file_baru_path = '';

    $ada_upload =
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;


    if (
        $error === '' &&
        $ada_upload
    ) {

        if (
            $_FILES['gambar']['error'] !== UPLOAD_ERR_OK
        ) {

            $error =
                'Gagal membaca gambar yang diunggah.';

        } else {

            $tmp =
                $_FILES['gambar']['tmp_name'];

            $nama_file =
                $_FILES['gambar']['name'];

            $ukuran =
                $_FILES['gambar']['size'];

            $ekstensi =
                strtolower(
                    pathinfo(
                        $nama_file,
                        PATHINFO_EXTENSION
                    )
                );


            $ekstensi_valid = [
                'jpg',
                'jpeg',
                'png'
            ];


            if (
                !in_array(
                    $ekstensi,
                    $ekstensi_valid
                )
            ) {

                $error =
                    'Format gambar harus JPG, JPEG, atau PNG.';

            } elseif (
                $ukuran > 5 * 1024 * 1024
            ) {

                $error =
                    'Ukuran gambar maksimal 5 MB.';

            } else {

                $mime =
                    mime_content_type($tmp);

                if (
                    !in_array(
                        $mime,
                        [
                            'image/jpeg',
                            'image/png'
                        ]
                    )
                ) {

                    $error =
                        'File yang diunggah bukan gambar yang valid.';

                } else {

                    if (
                        !is_dir('uploads')
                    ) {

                        mkdir(
                            'uploads',
                            0777,
                            true
                        );
                    }


                    $nama_file_baru =
                        'produk_' .
                        uniqid() .
                        '.' .
                        $ekstensi;

                    $file_baru_path =
                        'uploads/' .
                        $nama_file_baru;


                    if (
                        !move_uploaded_file(
                            $tmp,
                            $file_baru_path
                        )
                    ) {

                        $error =
                            'Gagal mengunggah gambar.';
                    }
                }
            }
        }
    }


    if ($error !== '') {

        $status =
            $mode_edit
                ? 'gagal_edit'
                : 'gagal_tambah';

        header(
            "Location: admin_produk.php?status=" .
            urlencode($status) .
            "&pesan=" .
            urlencode($error)
        );

        exit;
    }


    $koneksi->begin_transaction();

    try {

        if ($mode_edit) {

            $stmt =
                $koneksi->prepare("
                    SELECT
                        kode_sku,
                        gambar
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");

            $stmt->bind_param(
                "i",
                $product_id
            );

            $stmt->execute();

            $hasil =
                $stmt->get_result();

            if (
                $hasil->num_rows === 0
            ) {

                throw new Exception(
                    'Produk tidak ditemukan.'
                );
            }

            $produk_lama =
                $hasil->fetch_assoc();

            $stmt->close();


            $sku =
                $produk_lama['kode_sku'];

            $gambar_final =
                $produk_lama['gambar'];


            if (
                $nama_file_baru !== ''
            ) {

                $gambar_final =
                    $nama_file_baru;
            }


            $kategori_utama =
                $kategori_ids[0];


            // UPDATE PRODUK STANDAR (Tipe data: iisssddisii -> 11 parameter)
            $stmt =
                $koneksi->prepare("
                    UPDATE products
                    SET
                        category_id = ?,
                        brand_id = ?,
                        nama_produk = ?,
                        deskripsi = ?,
                        spesifikasi = ?,
                        harga = ?,
                        harga_pemasangan = ?,
                        stok = ?,
                        gambar = ?,
                        is_featured = ?
                    WHERE id = ?
                ");

            $stmt->bind_param(
                "iisssddisii",
                $kategori_utama,
                $brand_id,
                $nama,
                $deskripsi,
                $spesifikasi,
                $harga,
                $harga_pemasangan,
                $stok,
                $gambar_final,
                $is_featured,
                $product_id
            );


            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }

            $stmt->close();


            $stmt =
                $koneksi->prepare("
                    DELETE FROM product_categories
                    WHERE product_id = ?
                ");

            $stmt->bind_param(
                "i",
                $product_id
            );

            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }

            $stmt->close();


            $stmt =
                $koneksi->prepare("
                    INSERT INTO product_categories
                    (
                        product_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");

            foreach (
                $kategori_ids
                as $kategori_id
            ) {

                $stmt->bind_param(
                    "ii",
                    $product_id,
                    $kategori_id
                );

                if (
                    !$stmt->execute()
                ) {

                    throw new Exception(
                        $stmt->error
                    );
                }
            }

            $stmt->close();


            $koneksi->commit();


            if (
                $nama_file_baru !== '' &&
                !empty($produk_lama['gambar'])
            ) {

                $gambar_lama_path =
                    'uploads/' .
                    $produk_lama['gambar'];

                if (
                    file_exists(
                        $gambar_lama_path
                    )
                ) {

                    @unlink(
                        $gambar_lama_path
                    );
                }
            }


            header(
                "Location: admin_produk.php?status=sukses_edit"
            );

            exit;

        } else {

            $kategori_utama =
                $kategori_ids[0];

            $sku =
                'SKU-' .
                date('YmdHis') .
                '-' .
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(3)
                        ),
                        0,
                        6
                    )
                );


            $stmt =
                $koneksi->prepare("
                    INSERT INTO products
                    (
                        category_id,
                        brand_id,
                        kode_sku,
                        nama_produk,
                        deskripsi,
                        spesifikasi,
                        harga,
                        harga_pemasangan,
                        stok,
                        gambar,
                        is_active,
                        is_featured
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        1,
                        ?
                    )
                ");


            $stmt->bind_param(
                "iissssddisi",
                $kategori_utama,
                $brand_id,
                $sku,
                $nama,
                $deskripsi,
                $spesifikasi,
                $harga,
                $harga_pemasangan,
                $stok,
                $nama_file_baru,
                $is_featured
            );


            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }


            $product_id_baru =
                $stmt->insert_id;

            $stmt->close();


            $stmt =
                $koneksi->prepare("
                    INSERT INTO product_categories
                    (
                        product_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");


            foreach (
                $kategori_ids
                as $kategori_id
            ) {

                $stmt->bind_param(
                    "ii",
                    $product_id_baru,
                    $kategori_id
                );

                if (
                    !$stmt->execute()
                ) {

                    throw new Exception(
                        $stmt->error
                    );
                }
            }

            $stmt->close();


            $koneksi->commit();


            header(
                "Location: admin_produk.php?status=sukses_tambah"
            );

            exit;
        }

    } catch (Exception $e) {

        $koneksi->rollback();

        if (
            $file_baru_path !== '' &&
            file_exists($file_baru_path)
        ) {

            @unlink(
                $file_baru_path
            );
        }


        header(
            "Location: admin_produk.php?status=gagal&pesan=" .
            urlencode(
                $e->getMessage()
            )
        );

        exit;
    }
}


// ======================================================
// STATUS PESAN
// ======================================================

$pesan_html = '';

$status =
    $_GET['status'] ?? '';

$pesan_url =
    $_GET['pesan'] ?? '';


if (
    $status === 'sukses_tambah'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil ditambahkan.
        </div>
    ';

} elseif (
    $status === 'sukses_edit'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil diperbarui.
        </div>
    ';

} elseif (
    $status === 'sukses_hapus'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil dihapus.
        </div>
    ';

} elseif (
    $status === 'sukses_featured'
) {

    $pesan_html = '
        <div class="bg-yellow-100 border border-yellow-200 text-yellow-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-star"></i>
            Status Produk Unggulan berhasil diperbarui.
        </div>
    ';

} elseif (
    $status === 'gagal' ||
    $status === 'gagal_edit' ||
    $status === 'gagal_tambah'
) {

    $pesan_html = '
        <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i>
            ' .
            htmlspecialchars(
                $pesan_url ?: 'Terjadi kesalahan.'
            ) .
            '
        </div>
    ';
}


// ======================================================
// SEARCH & FILTER
// ======================================================

$search =
    trim(
        $_GET['search'] ?? ''
    );

$filter_kategori =
    (int)(
        $_GET['kategori'] ?? 0
    );

$filter_merek =
    (int)(
        $_GET['merek'] ?? 0
    );


// ======================================================
// QUERY PRODUK
// ======================================================

$sql = "
    SELECT
        p.*,

        GROUP_CONCAT(
            DISTINCT c.nama_kategori
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS nama_kategori,

        GROUP_CONCAT(
            DISTINCT c.slug
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS category_slugs,

        b.nama_merek

    FROM products p

    LEFT JOIN product_categories pc
        ON p.id = pc.product_id

    LEFT JOIN categories c
        ON pc.category_id = c.id

    LEFT JOIN brands b
        ON p.brand_id = b.id

    WHERE 1=1
        AND p.parent_id IS NULL
";

$params = [];
$types = "";


if (
    $search !== ''
) {

    $sql .= "
        AND (
            p.nama_produk LIKE ?
            OR p.kode_sku LIKE ?
            OR p.deskripsi LIKE ?
            OR b.nama_merek LIKE ?

            OR EXISTS (
                SELECT 1
                FROM product_categories pc_search

                INNER JOIN categories c_search
                    ON pc_search.category_id = c_search.id

                WHERE pc_search.product_id = p.id
                AND c_search.nama_kategori LIKE ?
            )
        )
    ";

    $keyword =
        '%' . $search . '%';

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $types .=
        "sssss";
}


if (
    $filter_kategori > 0
) {

    $sql .= "
        AND EXISTS (
            SELECT 1
            FROM product_categories pc_filter
            WHERE pc_filter.product_id = p.id
            AND pc_filter.category_id = ?
        )
    ";

    $params[] =
        $filter_kategori;

    $types .=
        "i";
}


if (
    $filter_merek > 0
) {

    $sql .= "
        AND p.brand_id = ?
    ";

    $params[] =
        $filter_merek;

    $types .=
        "i";
}


$sql .= "
    GROUP BY p.id
    ORDER BY p.id DESC
";


$stmt_produk =
    $koneksi->prepare(
        $sql
    );

if (
    !$stmt_produk
) {

    die(
        'Query produk gagal: ' .
        htmlspecialchars(
            $koneksi->error
        )
    );
}


if (
    !empty($params)
) {

    $stmt_produk->bind_param(
        $types,
        ...$params
    );
}


$stmt_produk->execute();

$query_produk =
    $stmt_produk->get_result();


$query_kategori =
    $koneksi->query("
        SELECT
            id,
            nama_kategori,
            slug
        FROM categories
        ORDER BY nama_kategori ASC
    ");


$query_merek =
    $koneksi->query("
        SELECT
            id,
            nama_merek,
            slug
        FROM brands
        WHERE is_active = 1
        ORDER BY nama_merek ASC
    ");


$total_featured =
    $koneksi->query("
        SELECT COUNT(id) AS total
        FROM products
        WHERE is_active = 1
        AND is_featured = 1
    ")->fetch_assoc()['total'] ?? 0;


$merek_modal =
    $koneksi->query("
        SELECT
            id,
            nama_merek
        FROM brands
        WHERE is_active = 1
        ORDER BY nama_merek ASC
    ");

$kategori_modal =
    $koneksi->query("
        SELECT
            id,
            nama_kategori
        FROM categories
        ORDER BY nama_kategori ASC
    ");

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
        Kelola Produk - BOSS CCTV
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .kategori-scroll::-webkit-scrollbar {
            width: 5px;
        }

    </style>

</head>


<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">


<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="w-64 bg-[#0f172a] text-gray-300 flex flex-col h-full shrink-0">

    <div class="h-16 flex items-center px-6 text-white font-bold border-b border-gray-800 gap-3">

        <i class="fa-solid fa-shield-halved text-blue-500"></i>

        POS Admin CCTV

    </div>


    <nav class="flex-1 px-4 py-6 space-y-2">

        <a
            href="admin_dashboard.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition"
        >

            <i class="fa-solid fa-chart-line w-5 text-center"></i>

            Dashboard

        </a>


        <a
            href="admin_produk.php"
            class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-lg"
        >

            <i class="fa-solid fa-box w-5 text-center"></i>

            Kelola Stok & Produk

        </a>


        <a
            href="pesanan.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition"
        >

            <i class="fa-solid fa-cart-shopping w-5 text-center"></i>

            Pesanan

        </a>

    </nav>


    <div class="p-4 border-t border-gray-800 space-y-2">

        <a
            href="index.php"
            class="flex items-center gap-3 px-4 py-2 hover:text-white"
        >

            <i class="fa-solid fa-globe w-5 text-center"></i>

            Lihat Website

        </a>


        <a
            href="logout.php"
            class="flex items-center gap-3 px-4 py-2 text-red-500 hover:text-red-400"
        >

            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>

            Keluar

        </a>

    </div>

</aside>


<!-- ======================================================
     MAIN
====================================================== -->

<main class="flex-1 overflow-y-auto p-8">

    <?= $pesan_html ?>


    <!-- HEADER -->

    <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">

        <div>

            <h1 class="text-2xl font-bold text-gray-900">
                Manajemen Produk
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Kelola produk, kategori, merek, harga, stok, dan Paket CCTV.
            </p>

        </div>


        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-3">

            <div class="flex items-center gap-3">

                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">

                    <i class="fa-solid fa-star text-yellow-500"></i>

                </div>


                <div>

                    <p class="text-xs text-yellow-700">
                        Paket CCTV Aktif
                    </p>

                    <p class="text-xl font-bold text-yellow-800">
                        <?= (int)$total_featured ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- ==================================================
         FILTER & TOMBOL UTAMA
    ================================================== -->

    <form
        method="GET"
        action="admin_produk.php"
        id="formFilter"
        class="bg-white p-4 rounded-t-xl border border-gray-200 border-b-0"
    >

        <div class="flex flex-col md:flex-row gap-3 items-center justify-between">

            <div class="relative w-full md:w-72">

                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>

                <input
                    type="search"
                    name="search"
                    id="searchProduk"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari nama, SKU, kategori, merek..."
                    autocomplete="off"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500"
                >

            </div>


            <div class="flex flex-wrap gap-2 w-full md:w-auto">

                <select
                    name="kategori"
                    id="filterKategori"
                    onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                >

                    <option value="0">
                        Semua Kategori
                    </option>


                    <?php while (
                        $kat =
                        $query_kategori->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int)$kat['id'] ?>"
                            <?= $filter_kategori == $kat['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $kat['nama_kategori']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <select
                    name="merek"
                    id="filterMerek"
                    onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                >

                    <option value="0">
                        Semua Merek
                    </option>


                    <?php while (
                        $brand =
                        $query_merek->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int)$brand['id'] ?>"
                            <?= $filter_merek == $brand['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $brand['nama_merek']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <?php if (
                    $search !== '' ||
                    $filter_kategori > 0 ||
                    $filter_merek > 0
                ): ?>

                    <a
                        href="admin_produk.php"
                        class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-red-50 hover:text-red-600 rounded-lg border border-gray-200"
                        title="Reset filter"
                    >

                        <i class="fa-solid fa-xmark"></i>

                    </a>

                <?php endif; ?>


                <button
                    type="button"
                    onclick="openTambahModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2"
                >

                    <i class="fa-solid fa-plus"></i>

                    Tambah Produk

                </button>

                <button
                    type="button"
                    onclick="openModalPaketCCTV()"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 shadow"
                >
                    <i class="fa-solid fa-boxes-stacked"></i>
                    Tambah Paket CCTV
                </button>

            </div>

        </div>

    </form>


    <!-- ==================================================
         TABLE
    ================================================== -->

    <div class="bg-white border border-gray-200 rounded-b-xl overflow-x-auto shadow-sm">

        <table class="w-full text-left border-collapse">

            <thead>

                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">

                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">GAMBAR</th>
                    <th class="px-6 py-4">PRODUK</th>
                    <th class="px-6 py-4">SKU</th>
                    <th class="px-6 py-4">KATEGORI</th>
                    <th class="px-6 py-4">MEREK</th>
                    <th class="px-6 py-4">HARGA</th>
                    <th class="px-6 py-4">HARGA + PASANG</th>
                    <th class="px-6 py-4 text-center">STOK</th>
                    <th class="px-6 py-4 text-center">PAKET CCTV</th>
                    <th class="px-6 py-4 text-center">STATUS</th>
                    <th class="px-6 py-4 text-center">AKSI</th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100 text-sm">


                <?php if (
                    $query_produk &&
                    $query_produk->num_rows > 0
                ): ?>


                    <?php while (
                        $row =
                        $query_produk->fetch_assoc()
                    ): ?>

                        <?php

                        $data_search =
                            strtolower(
                                ($row['nama_produk'] ?? '') . ' ' .
                                ($row['kode_sku'] ?? '') . ' ' .
                                ($row['deskripsi'] ?? '') . ' ' .
                                ($row['nama_kategori'] ?? '') . ' ' .
                                ($row['nama_merek'] ?? '')
                            );


                        $daftar_kategori = [];

                        if (
                            !empty(
                                $row['nama_kategori']
                            )
                        ) {

                            $daftar_kategori =
                                array_filter(
                                    array_map(
                                        'trim',
                                        explode(
                                            '|||',
                                            $row['nama_kategori']
                                        )
                                    )
                                );
                        }

                        ?>


                        <tr
                            data-search="<?= htmlspecialchars($data_search, ENT_QUOTES, 'UTF-8') ?>"
                            class="hover:bg-gray-50 transition"
                        >

                            <td class="px-6 py-4 text-gray-500">
                                #<?= (int)$row['id'] ?>
                            </td>


                            <td class="px-6 py-4">

                                <div class="w-12 h-12 bg-gray-100 border rounded-lg overflow-hidden flex items-center justify-center">

                                    <?php if (
                                        !empty($row['gambar']) &&
                                        file_exists(
                                            'uploads/' .
                                            $row['gambar']
                                        )
                                    ): ?>

                                        <img
                                            src="uploads/<?= htmlspecialchars($row['gambar']) ?>"
                                            alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                                            class="w-full h-full object-cover"
                                        >

                                    <?php else: ?>

                                        <i class="fa-solid fa-image text-gray-400"></i>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <div class="font-bold text-gray-900">

                                    <?= htmlspecialchars(
                                        $row['nama_produk']
                                    ) ?>

                                </div>

                                <div class="text-xs text-gray-500 max-w-xs truncate">

                                    <?= !empty($row['deskripsi'])
                                        ? htmlspecialchars(
                                            $row['deskripsi']
                                        )
                                        : 'Tidak ada deskripsi'
                                    ?>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <span class="font-mono text-xs text-gray-600">

                                    <?= htmlspecialchars(
                                        $row['kode_sku']
                                    ) ?>

                                </span>

                            </td>


                            <td class="px-6 py-4">

                                <?php if (
                                    !empty($daftar_kategori)
                                ): ?>

                                    <div class="flex flex-wrap gap-1.5 max-w-xs">

                                        <?php foreach (
                                            $daftar_kategori
                                            as $kategori
                                        ): ?>

                                            <span class="inline-flex bg-blue-50 text-blue-700 border border-blue-100 px-2.5 py-1 rounded-md text-xs font-medium">

                                                <?= htmlspecialchars(
                                                    $kategori
                                                ) ?>

                                            </span>

                                        <?php endforeach; ?>

                                    </div>

                                <?php else: ?>

                                    <span class="text-xs text-gray-400">
                                        Tanpa kategori
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="px-6 py-4">

                                <span class="inline-flex bg-purple-50 text-purple-700 border border-purple-100 px-2.5 py-1 rounded-md text-xs font-medium">

                                    <?= htmlspecialchars(
                                        $row['nama_merek'] ??
                                        'Tanpa Merek'
                                    ) ?>

                                </span>

                            </td>


                            <td class="px-6 py-4 font-medium">

                                <?= rupiah(
                                    $row['harga']
                                ) ?>

                            </td>


                            <td class="px-6 py-4 font-medium text-blue-700">

                                <?php if (
                                    $row['harga_pemasangan'] !== null &&
                                    $row['harga_pemasangan'] !== '' &&
                                    (float)$row['harga_pemasangan'] > 0
                                ): ?>

                                    <?= rupiah(
                                        $row['harga_pemasangan']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-gray-400">-</span>

                                <?php endif; ?>

                            </td>


                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['stok'] <= 0
                                ): ?>

                                    <span class="text-red-600 font-bold">
                                        Habis
                                    </span>

                                <?php elseif (
                                    (int)$row['stok'] <= 5
                                ): ?>

                                    <span class="text-orange-500 font-bold">

                                        <?= (int)$row['stok'] ?>

                                    </span>

                                <?php else: ?>

                                    <span class="text-green-600 font-bold">

                                        <?= (int)$row['stok'] ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['is_featured'] === 1
                                ): ?>

                                    <a
                                        href="toggle_featured.php?id=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Hapus produk ini dari Produk Unggulan?')"
                                        class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 border border-yellow-200 px-3 py-1.5 rounded-full text-xs font-semibold"
                                    >

                                        <i class="fa-solid fa-star"></i>

                                        Paket CCTV

                                    </a>

                                <?php else: ?>

                                    <a
                                        href="toggle_featured.php?id=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Jadikan produk ini Produk Unggulan?')"
                                        class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 border border-gray-200 px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-yellow-100 hover:text-yellow-700"
                                    >

                                        <i class="fa-regular fa-star"></i>

                                        Paket CCTV

                                    </a>

                                <?php endif; ?>

                            </td>


                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['is_active'] === 1
                                ): ?>

                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-medium">

                                        Aktif

                                    </span>

                                <?php else: ?>

                                    <span class="bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full text-xs font-medium">

                                        Nonaktif

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="px-6 py-4 text-center whitespace-nowrap">

                                <button
                                    type="button"
                                    onclick="openEditModal(<?= (int)$row['id'] ?>)"
                                    class="text-gray-400 hover:text-blue-600 mx-1 transition"
                                    title="Edit Produk"
                                >

                                    <i class="fa-solid fa-pen-to-square"></i>

                                </button>


                                <a
                                    href="hapus_produk.php?id=<?= (int)$row['id'] ?>"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')"
                                    class="text-gray-400 hover:text-red-600 mx-1 transition"
                                    title="Hapus Produk"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                    <tr
                        id="barisTidakDitemukan"
                        style="display:none;"
                    >

                        <td
                            colspan="12"
                            class="px-6 py-12 text-center"
                        >

                            <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>

                            <p class="text-gray-500 font-medium mt-3">
                                Produk tidak ditemukan
                            </p>

                            <p class="text-xs text-gray-400 mt-1">
                                Coba gunakan kata kunci lain.
                            </p>

                        </td>

                    </tr>


                <?php else: ?>

                    <tr>

                        <td
                            colspan="12"
                            class="px-6 py-12 text-center"
                        >

                            <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>

                            <p class="text-gray-500 font-medium mt-3">
                                Belum ada produk.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


<!-- ======================================================
     MODAL TAMBAH / EDIT PRODUK STANDAR ASLI
====================================================== -->

<div
    id="modalProduk"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"
>

    <div
        id="modalProdukContent"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[94vh] overflow-y-auto transform scale-95 transition-transform duration-200"
    >

        <div class="sticky top-0 bg-white z-10 flex items-center justify-between px-6 py-4 border-b border-gray-100">

            <div>

                <h3
                    id="judulModalProduk"
                    class="text-lg font-bold text-gray-900"
                >
                    Tambah Produk Baru
                </h3>

                <p
                    id="subjudulModalProduk"
                    class="text-xs text-gray-500 mt-1"
                >
                    Tambahkan produk CCTV baru
                </p>

            </div>


            <button
                type="button"
                onclick="closeProdukModal()"
                class="w-9 h-9 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700"
            >

                <i class="fa-solid fa-xmark text-xl"></i>

            </button>

        </div>


        <form
            id="formProduk"
            action="admin_produk.php"
            method="POST"
            enctype="multipart/form-data"
            class="p-6"
        >

            <input
                type="hidden"
                name="product_id"
                id="editProductId"
                value=""
            >

            <input
                type="hidden"
                name="simpan_produk"
                id="inputModeTambah"
                value="1"
            >

            <input
                type="hidden"
                name="update_produk"
                id="inputModeEdit"
                value=""
            >
            <input type="hidden" name="edit_paket_cctv" id="inputModeEditPaket" value="">

            <div id="panelEditPaketCCTV" class="hidden mb-6 border border-amber-200 bg-amber-50/50 rounded-xl p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-bold text-gray-900"><i class="fa-solid fa-layer-group text-amber-500 mr-2"></i>Varian produk ini</h4>
                        <p class="text-xs text-gray-600 mt-1">Contoh: EZVIZ 3MP memiliki varian 4 Channel dan 8 Channel. Semua varian bisa diedit sekaligus di sini.</p>
                    </div>
                    <button type="button" onclick="tambahRowEditVarian()" class="shrink-0 px-3 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Tambah Varian</button>
                </div>
                <div id="containerEditPaketVarian" class="space-y-3"></div>
                <p class="text-xs text-gray-500 mt-3">Varian yang dihapus dari formulir akan dinonaktifkan, bukan dihapus permanen dari database.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="space-y-5">

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            id="namaProduk"
                            maxlength="150"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Contoh: CUE 2 3MP"
                        >

                    </div>


                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            SKU
                        </label>

                        <input
                            type="text"
                            id="skuProduk"
                            readonly
                            class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-4 py-2.5 text-sm"
                            placeholder="SKU otomatis dibuat saat produk ditambahkan"
                        >

                        <p
                            id="infoSku"
                            class="text-xs text-gray-400 mt-1"
                        >
                            SKU dibuat otomatis oleh sistem.
                        </p>

                    </div>


                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kategori <span class="text-red-500">*</span>
                        </label>

                        <p class="text-xs text-gray-400 mb-2">
                            Produk dapat memiliki lebih dari satu kategori.
                        </p>


                        <div class="border border-gray-300 rounded-lg p-3 bg-gray-50 max-h-56 overflow-y-auto kategori-scroll">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">

                                <?php 
                                $kategori_modal->data_seek(0);
                                while ($kat = $kategori_modal->fetch_assoc()): 
                                ?>

                                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-white cursor-pointer">

                                        <input
                                            type="checkbox"
                                            name="kategori_id[]"
                                            value="<?= (int)$kat['id'] ?>"
                                            class="kategori-checkbox w-4 h-4 accent-blue-600"
                                        >

                                        <span class="text-sm text-gray-700">
                                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                                        </span>

                                    </label>

                                <?php endwhile; ?>

                            </div>

                        </div>


                        <p
                            id="infoKategori"
                            class="text-xs text-red-500 mt-2"
                        >
                            Belum ada kategori dipilih.
                        </p>

                    </div>


                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Merek <span class="text-red-500">*</span>
                        </label>


                        <div class="flex gap-2">

                            <select
                                name="brand_id"
                                id="brandSelect"
                                required
                                class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                            >

                                <option value="">
                                    -- Pilih Merek --
                                </option>


                                <?php 
                                $merek_modal->data_seek(0);
                                while ($brand = $merek_modal->fetch_assoc()): 
                                ?>

                                    <option
                                        value="<?= (int)$brand['id'] ?>"
                                    >
                                        <?= htmlspecialchars($brand['nama_merek']) ?>
                                    </option>

                                <?php endwhile; ?>

                            </select>


                            <button
                                type="button"
                                onclick="openBrandModal()"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 rounded-lg text-sm"
                                title="Tambah Merek"
                            >

                                <i class="fa-solid fa-plus"></i>

                            </button>

                        </div>

                    </div>


                    <div id="fieldHargaProduk">

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Harga <span class="text-red-500">*</span>
                        </label>


                        <div class="relative">

                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">
                                Rp
                            </span>

                            <input
                                type="text"
                                name="harga"
                                id="harga"
                                inputmode="numeric"
                                autocomplete="off"
                                required
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
                                placeholder="1.500.000"
                            >

                        </div>

                    </div>


                    <div id="fieldPemasanganProduk">

                        <div class="flex items-center justify-between mb-2">

                            <label class="block text-sm font-medium text-gray-700">
                                Harga + Pemasangan
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer">

                                <input
                                    type="checkbox"
                                    name="pakai_pemasangan"
                                    id="pakaiPemasangan"
                                    value="1"
                                    class="w-4 h-4 accent-blue-600"
                                >

                                <span class="text-xs font-medium text-gray-600">
                                    Butuh pemasangan teknisi
                                </span>

                            </label>

                        </div>


                        <div class="relative">

                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">
                                Rp
                            </span>

                            <input
                                type="text"
                                name="harga_pemasangan"
                                id="hargaPemasangan"
                                inputmode="numeric"
                                autocomplete="off"
                                readonly
                                class="w-full border border-gray-300 bg-gray-100 text-gray-400 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none"
                                placeholder="Tidak diperlukan"
                            >

                        </div>

                        <p id="infoPemasangan" class="text-xs text-gray-400 mt-1">
                            Centang jika produk membutuhkan jasa pemasangan teknisi.
                        </p>

                    </div>


                    <div id="fieldStokProduk">

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Stok <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="number"
                            name="stok"
                            id="stok"
                            min="0"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
                            placeholder="10"
                        >

                    </div>


                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">

                        <label class="flex gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_featured"
                                id="isFeatured"
                                value="1"
                                class="mt-1 w-4 h-4 accent-yellow-500"
                            >

                            <div>

                                <div class="font-semibold text-yellow-800">
                                    <i class="fa-solid fa-star mr-1"></i> Paket CCTV
                                </div>

                                <p class="text-xs text-yellow-700 mt-1">
                                    Tampilkan produk sebagai Paket CCTV.
                                </p>

                            </div>

                        </label>

                    </div>

                </div>


                <div class="space-y-5">

                    <div id="fieldGambarProduk">

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Gambar Produk <span id="wajibGambar" class="text-red-500">*</span>
                        </label>


                        <div
                            id="uploadBox"
                            onclick="document.getElementById('fileUpload').click()"
                            class="relative min-h-[230px] border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 overflow-hidden"
                        >

                            <i
                                id="iconUpload"
                                class="fa-solid fa-cloud-arrow-up text-4xl text-gray-300 mb-3"
                            ></i>

                            <span
                                id="textUpload"
                                class="text-sm text-gray-500"
                            >
                                Klik untuk memilih gambar
                            </span>

                            <span class="text-xs text-gray-400 mt-1">
                                JPG / JPEG / PNG • Maks. 5 MB
                            </span>


                            <img
                                id="imagePreview"
                                src=""
                                alt="Preview"
                                class="hidden absolute inset-0 w-full h-full object-contain bg-white p-2"
                            >

                        </div>


                        <input
                            type="file"
                            name="gambar"
                            id="fileUpload"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            class="hidden"
                        >


                        <p
                            id="infoGambar"
                            class="text-xs text-gray-400 mt-2"
                        >
                            Pilih gambar produk.
                        </p>

                    </div>


                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Deskripsi
                        </label>

                        <textarea
                            name="deskripsi"
                            id="deskripsi"
                            rows="4"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm resize-none focus:outline-none focus:border-blue-500"
                            placeholder="Masukkan rincian isi paket dan deskripsi produk..."
                        ></textarea>

                    </div>
                    
                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Spesifikasi CCTV <span class="text-xs text-gray-500 font-normal">(Opsional)</span>
                        </label>

                        <textarea
                            name="spesifikasi"
                            id="spesifikasi"
                            rows="4"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm resize-none focus:outline-none focus:border-blue-500"
                            placeholder="Contoh: Lensa 4mm, Jarak IR 20m, Sensor CMOS, Resolusi 1080p..."
                        ></textarea>

                    </div>


                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">

                        <div class="flex gap-3">

                            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>

                            <div class="text-xs text-blue-700">

                                <p class="font-semibold mb-1">Informasi</p>
                                <p>Harga CCTV adalah harga perangkat.</p>
                                <p class="mt-1">Harga + Pemasangan adalah harga perangkat yang sudah termasuk jasa pemasangan teknisi.</p>
                                <p class="mt-1">Produk yang tidak membutuhkan teknisi tidak perlu mengisi harga pemasangan.</p>
                                <p class="mt-1">Satu produk dapat memiliki beberapa kategori.</p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="sticky bottom-0 bg-white mt-8 pt-4 border-t border-gray-100 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeProdukModal()"
                    class="px-5 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                    Batal
                </button>


                <button
                    type="submit"
                    id="btnSimpanProduk"
                    class="px-5 py-2.5 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2"
                >

                    <i id="iconSimpan" class="fa-regular fa-floppy-disk"></i>
                    <span id="textSimpan">Simpan Produk</span>

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ======================================================
     MODAL KHUSUS TAMBAH PAKET CCTV
====================================================== -->

<div
    id="modalPaketCCTV"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm opacity-0 transition-opacity"
>

    <div
        id="modalPaketContent"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[94vh] overflow-y-auto transform scale-95 transition-transform"
    >

        <div class="sticky top-0 bg-white z-10 flex items-center justify-between px-6 py-4 border-b">

            <div>

                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-amber-500"></i> Tambah Paket CCTV & Variannya
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    Nama induk untuk katalog dan satu atau lebih varian yang masing-masing memiliki nama, harga, stok, serta foto.
                </p>

            </div>


            <button
                type="button"
                onclick="closeModalPaketCCTV()"
                class="w-9 h-9 rounded-lg hover:bg-gray-100 text-gray-400"
            >

                <i class="fa-solid fa-xmark text-xl"></i>

            </button>

        </div>


        <form
            action="admin_produk.php"
            method="POST"
            enctype="multipart/form-data"
            class="p-6 space-y-6"
        >

            <input type="hidden" name="simpan_paket_cctv" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Nama Paket / Produk Induk <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama_paket"
                        required
                        placeholder="Contoh: Paket CCTV EZVIZ H6C Two-Way Talk"
                        class="w-full border rounded-xl px-3 py-2 text-sm"
                    >

                </div>


                <div>

                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Merek CCTV <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="brand_id"
                        required
                        class="w-full border rounded-xl px-3 py-2 text-sm bg-white"
                    >

                        <option value="">-- Pilih Merek --</option>

                        <?php 
                        $merek_modal->data_seek(0);
                        while ($brand = $merek_modal->fetch_assoc()): 
                        ?>

                            <option value="<?= (int)$brand['id'] ?>">
                                <?= htmlspecialchars($brand['nama_merek']) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

            </div>


            <div>

                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                    Kategori Paket <span class="text-red-500">*</span>
                </label>

                <div class="border rounded-xl p-3 bg-gray-50 max-h-32 overflow-y-auto grid grid-cols-2 gap-2">

                    <?php 
                    $kategori_modal->data_seek(0);
                    while ($kat = $kategori_modal->fetch_assoc()): 
                    ?>

                        <label class="flex items-center gap-2 text-xs cursor-pointer">

                            <input
                                type="checkbox"
                                name="kategori_id[]"
                                value="<?= (int)$kat['id'] ?>"
                                class="accent-amber-500"
                            >

                            <?= htmlspecialchars($kat['nama_kategori']) ?>

                        </label>

                    <?php endwhile; ?>

                </div>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Deskripsi Paket
                    </label>

                    <textarea
                        name="deskripsi"
                        rows="3"
                        placeholder="Tulis deskripsi paket CCTV di sini..."
                        class="w-full border rounded-xl px-3 py-2 text-sm resize-none"
                    ></textarea>

                </div>
                
                <div>

                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Spesifikasi CCTV <span class="text-xs text-gray-500 font-normal normal-case">(Opsional)</span>
                    </label>

                    <textarea
                        name="spesifikasi"
                        rows="3"
                        placeholder="Contoh: Lensa 4mm, 1080p, dll..."
                        class="w-full border rounded-xl px-3 py-2 text-sm resize-none"
                    ></textarea>

                </div>

            </div>


            <div class="border-t pt-4">

                <div class="flex justify-between items-center mb-3">

                    <label class="text-xs font-bold text-gray-900 uppercase">
                        Pilihan Varian yang Dijual
                    </label>

                    <button
                        type="button"
                        onclick="tambahRowPaketVarian()"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1"
                    >

                        <i class="fa-solid fa-plus"></i> + Tambah Channel

                    </button>

                </div>


                <div id="containerPaketVarian" class="space-y-3">

                    <div class="row-paket-varian bg-gray-50 p-4 rounded-xl border border-gray-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-center">

                        <div class="md:col-span-4">

                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Varian / Konfigurasi</label>

                            <input
                                type="text"
                                name="paket_v_nama[]"
                                value="Paket CCTV 4 Channel"
                                required
                                class="w-full border rounded-lg px-2.5 py-1.5 text-xs"
                            >

                        </div>

                        <div class="md:col-span-3">

                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Harga (Rp)</label>

                            <input
                                type="text"
                                name="paket_v_harga[]"
                                placeholder="2.500.000"
                                required
                                class="w-full border rounded-lg px-2.5 py-1.5 text-xs"
                            >

                        </div>

                        <div class="md:col-span-2">

                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Stok</label>

                            <input
                                type="number"
                                name="paket_v_stok[]"
                                value="10"
                                min="0"
                                required
                                class="w-full border rounded-lg px-2.5 py-1.5 text-xs"
                            >

                        </div>

                        <div class="md:col-span-2">

                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Foto Varian</label>

                            <input
                                type="file"
                                name="paket_v_gambar[]"
                                class="w-full text-[10px] text-gray-500"
                            >

                        </div>

                        <div class="md:col-span-1 text-center pt-3">

                            <button
                                type="button"
                                onclick="hapusRowPaketVarian(this)"
                                class="text-red-500 hover:text-red-700"
                            >

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            <div class="border-t pt-4 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeModalPaketCCTV()"
                    class="px-5 py-2 text-xs font-bold border rounded-xl"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="px-5 py-2 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-xl shadow"
                >
                    Simpan Paket CCTV
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ======================================================
     MODAL TAMBAH MEREK
====================================================== -->

<div
    id="modalBrand"
    class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm opacity-0 transition-opacity"
>

    <div
        id="modalBrandContent"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-transform"
    >

        <div class="flex justify-between items-center px-6 py-4 border-b">

            <div>

                <h3 class="font-bold text-lg text-gray-900">
                    Tambah Merek
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    Tambahkan merek CCTV baru
                </p>

            </div>


            <button
                type="button"
                onclick="closeBrandModal()"
                class="text-gray-400 hover:text-gray-700"
            >

                <i class="fa-solid fa-xmark text-xl"></i>

            </button>

        </div>


        <form
            id="formBrand"
            class="p-6"
        >

            <label class="block text-sm font-medium text-gray-700 mb-2">
                Nama Merek <span class="text-red-500">*</span>
            </label>


            <input
                type="text"
                name="nama_merek"
                id="namaMerekBaru"
                required
                maxlength="100"
                autocomplete="off"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-purple-500"
                placeholder="Contoh: Hikvision"
            >


            <div
                id="pesanBrand"
                class="hidden mt-4 p-3 rounded-lg text-sm"
            ></div>


            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">

                <button
                    type="button"
                    onclick="closeBrandModal()"
                    class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm"
                >
                    Batal
                </button>


                <button
                    type="submit"
                    id="btnSimpanBrand"
                    class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm flex items-center gap-2"
                >

                    <i class="fa-solid fa-plus"></i>
                    Simpan Merek

                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openModalPaketCCTV() {
    const m = document.getElementById('modalPaketCCTV');
    const c = document.getElementById('modalPaketContent');
    m.classList.remove('hidden');
    m.classList.add('flex');
    setTimeout(() => {
        m.classList.remove('opacity-0');
        c.classList.remove('scale-95');
    }, 10);
}

function closeModalPaketCCTV() {
    const m = document.getElementById('modalPaketCCTV');
    const c = document.getElementById('modalPaketContent');
    m.classList.add('opacity-0');
    c.classList.add('scale-95');
    setTimeout(() => {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }, 200);
}

function tambahRowPaketVarian() {
    const container = document.getElementById('containerPaketVarian');
    const html = `
        <div class="row-paket-varian bg-gray-50 p-4 rounded-xl border border-gray-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-4">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Varian / Konfigurasi</label>
                <input type="text" name="paket_v_nama[]" placeholder="Contoh: Paket CCTV 8 Channel atau EZVIZ H6C 2K" required class="w-full border rounded-lg px-2.5 py-1.5 text-xs">
            </div>
            <div class="md:col-span-3">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Harga (Rp)</label>
                <input type="text" name="paket_v_harga[]" placeholder="Harga" required class="w-full border rounded-lg px-2.5 py-1.5 text-xs">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Stok</label>
                <input type="number" name="paket_v_stok[]" value="10" min="0" required class="w-full border rounded-lg px-2.5 py-1.5 text-xs">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Foto Varian</label>
                <input type="file" name="paket_v_gambar[]" class="w-full text-[10px] text-gray-500">
            </div>
            <div class="md:col-span-1 text-center pt-3">
                <button type="button" onclick="hapusRowPaketVarian(this)" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function hapusRowPaketVarian(btn) {
    const rows = document.querySelectorAll('.row-paket-varian');
    if (rows.length > 1) {
        btn.closest('.row-paket-varian').remove();
    } else {
        alert('Minimal harus ada 1 pilihan channel!');
    }
}

function escapeVariantHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (ch) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
    });
}

function renderRowEditVarian(variant = {}) {
    const container = document.getElementById('containerEditPaketVarian');
    if (!container) return;
    const id = Number(variant.id || 0);
    const nama = escapeVariantHtml(variant.nama_produk || '');
    const harga = Number(variant.harga || 0) > 0 ? String(Math.round(Number(variant.harga))) : '';
    const stok = Number.isFinite(Number(variant.stok)) ? Number(variant.stok) : 0;
    const gambar = String(variant.gambar || '');
    const imagePreview = gambar
        ? `<img src="uploads/${encodeURIComponent(gambar)}" alt="Foto varian" class="w-16 h-16 object-contain border rounded-lg bg-white p-1">`
        : `<div class="w-16 h-16 flex items-center justify-center border rounded-lg bg-white text-gray-300"><i class="fa-regular fa-image text-xl"></i></div>`;
    const html = `
        <div class="row-edit-paket-varian border border-gray-200 bg-white rounded-xl p-3" data-variant-id="${id}">
            <input type="hidden" name="paket_edit_id[]" value="${id}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Nama varian / konfigurasi</label>
                    <input type="text" name="paket_edit_nama[]" value="${nama}" maxlength="150" required placeholder="4 Channel / 8 Channel / EZVIZ H6C" class="w-full border rounded-lg px-3 py-2 text-sm">
                    ${id ? `<p class="text-[10px] text-gray-400 mt-1">SKU: ${escapeVariantHtml(variant.kode_sku || '-')}</p>` : `<p class="text-[10px] text-amber-600 mt-1">Varian baru</p>`}
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Harga (Rp)</label>
                    <input type="text" inputmode="numeric" name="paket_edit_harga[]" value="${escapeVariantHtml(harga)}" required placeholder="1500000" class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Stok</label>
                    <input type="number" name="paket_edit_stok[]" min="0" step="1" value="${stok}" required class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Foto</label>
                    <div class="flex items-center gap-2">
                        ${imagePreview}
                        <input type="file" name="paket_edit_gambar[]" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="min-w-0 w-full text-[10px] text-gray-500">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">Kosongkan untuk tetap memakai foto lama.</p>
                </div>
                <div class="md:col-span-1 flex justify-end md:justify-center">
                    <button type="button" onclick="hapusRowEditVarian(this)" title="Hapus varian" class="px-3 py-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function tambahRowEditVarian() {
    renderRowEditVarian({ id: 0, nama_produk: '', harga: '', stok: 0, gambar: '' });
}

function hapusRowEditVarian(button) {
    const rows = document.querySelectorAll('#containerEditPaketVarian .row-edit-paket-varian');
    if (rows.length <= 1) {
        alert('Minimal harus ada satu varian.');
        return;
    }
    button.closest('.row-edit-paket-varian')?.remove();
}

function setEditPaketMode(isPackage, variants) {
    const panel = document.getElementById('panelEditPaketCCTV');
    const mode = document.getElementById('inputModeEditPaket');
    if (!panel || !mode) return;
    ['fieldHargaProduk', 'fieldPemasanganProduk', 'fieldStokProduk', 'fieldGambarProduk'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden', Boolean(isPackage));
    });
    panel.classList.toggle('hidden', !isPackage);
    mode.value = isPackage ? '1' : '';
    const container = document.getElementById('containerEditPaketVarian');
    container.innerHTML = '';
    if (isPackage) {
        (Array.isArray(variants) ? variants : []).forEach(v => renderRowEditVarian(v));
        if (!container.children.length) renderRowEditVarian({ id: 0, nama_produk: '', harga: '', stok: 0, gambar: '' });
    }
}

const modalProduk = document.getElementById('modalProduk');
const modalProdukContent = document.getElementById('modalProdukContent');
const formProduk = document.getElementById('formProduk');
const modalBrand = document.getElementById('modalBrand');
const modalBrandContent = document.getElementById('modalBrandContent');

function openTambahModal() {
    formProduk.reset();
    setEditPaketMode(false, []);
    document.getElementById('inputModeEditPaket').value = '';
    document.getElementById('editProductId').value = '';
    document.getElementById('inputModeTambah').disabled = false;
    document.getElementById('inputModeEdit').disabled = true;

    document.getElementById('judulModalProduk').textContent = 'Tambah Produk Baru';
    document.getElementById('subjudulModalProduk').textContent = 'Tambahkan produk CCTV baru';
    document.getElementById('textSimpan').textContent = 'Simpan Produk';
    document.getElementById('iconSimpan').className = 'fa-regular fa-floppy-disk';
    document.getElementById('skuProduk').value = '';
    document.getElementById('skuProduk').placeholder = 'SKU otomatis dibuat saat disimpan';
    document.getElementById('infoSku').textContent = 'SKU dibuat otomatis oleh sistem.';
    document.getElementById('wajibGambar').classList.remove('hidden');
    document.getElementById('fileUpload').required = true;

    resetPreview();
    clearKategori();

    document.getElementById('pakaiPemasangan').checked = false;
    updatePemasanganField();

    openProdukModal();
}

function openProdukModal() {
    modalProduk.classList.remove('hidden');
    modalProduk.classList.add('flex');
    setTimeout(() => {
        modalProduk.classList.remove('opacity-0');
        modalProdukContent.classList.remove('scale-95');
    }, 10);
}

function closeProdukModal() {
    modalProduk.classList.add('opacity-0');
    modalProdukContent.classList.add('scale-95');
    setTimeout(() => {
        modalProduk.classList.add('hidden');
        modalProduk.classList.remove('flex');
    }, 200);
}

async function openEditModal(id) {
    if (!id) return;

    formProduk.reset();
    clearKategori();
    resetPreview();

    document.getElementById('judulModalProduk').textContent = 'Edit Produk';
    document.getElementById('subjudulModalProduk').textContent = 'Perbarui informasi produk CCTV';
    document.getElementById('textSimpan').textContent = 'Simpan Perubahan';
    document.getElementById('iconSimpan').className = 'fa-solid fa-pen-to-square';
    document.getElementById('inputModeTambah').disabled = true;
    document.getElementById('inputModeEdit').disabled = false;
    document.getElementById('inputModeEdit').value = '1';
    document.getElementById('fileUpload').required = false;
    document.getElementById('wajibGambar').classList.add('hidden');

    document.getElementById('namaProduk').value = 'Memuat data...';

    openProdukModal();

    try {
        const response = await fetch('admin_produk.php?ajax=ambil_produk&id=' + encodeURIComponent(id));
        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'Gagal mengambil data produk.');
            closeProdukModal();
            return;
        }

        const p = data.produk;
        setEditPaketMode(Boolean(p.is_paket_cctv), p.varian || []);

        document.getElementById('editProductId').value = p.id;
        document.getElementById('namaProduk').value = p.nama_produk;
        document.getElementById('skuProduk').value = p.kode_sku;
        document.getElementById('infoSku').textContent = 'SKU tidak dapat diubah.';
        document.getElementById('brandSelect').value = p.brand_id;
        document.getElementById('harga').value = formatRupiahInput(p.harga);

        const adaPemasangan = p.harga_pemasangan !== null && p.harga_pemasangan !== '' && Number(p.harga_pemasangan) > 0;
        document.getElementById('pakaiPemasangan').checked = adaPemasangan;
        document.getElementById('hargaPemasangan').value = adaPemasangan ? formatRupiahInput(p.harga_pemasangan) : '';
        updatePemasanganField();

        document.getElementById('stok').value = p.stok;
        document.getElementById('deskripsi').value = p.deskripsi || '';
        document.getElementById('spesifikasi').value = p.spesifikasi || '';
        document.getElementById('isFeatured').checked = p.is_featured == 1;

        const kategori = p.kategori_ids || [];
        document.querySelectorAll('.kategori-checkbox').forEach(function (checkbox) {
            checkbox.checked = kategori.includes(parseInt(checkbox.value));
        });
        updateInfoKategori();

        if (p.gambar) {
            const preview = document.getElementById('imagePreview');
            preview.src = 'uploads/' + p.gambar;
            preview.classList.remove('hidden');
            document.getElementById('iconUpload').classList.add('hidden');
            document.getElementById('textUpload').classList.add('hidden');
            document.getElementById('infoGambar').textContent = 'Gambar saat ini. Pilih gambar baru jika ingin menggantinya.';
        } else {
            document.getElementById('infoGambar').textContent = 'Produk belum memiliki gambar.';
        }
    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan saat mengambil data produk.');
        closeProdukModal();
    }
}

function clearKategori() {
    document.querySelectorAll('.kategori-checkbox').forEach(function (checkbox) {
        checkbox.checked = false;
    });
    updateInfoKategori();
}

function updateInfoKategori() {
    const jumlah = document.querySelectorAll('.kategori-checkbox:checked').length;
    const info = document.getElementById('infoKategori');
    if (jumlah === 0) {
        info.textContent = 'Belum ada kategori dipilih.';
        info.className = 'text-xs text-red-500 mt-2';
    } else {
        info.textContent = jumlah + ' kategori dipilih.';
        info.className = 'text-xs text-blue-600 mt-2';
    }
}

document.querySelectorAll('.kategori-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', updateInfoKategori);
});

function updatePemasanganField() {
    const checkbox = document.getElementById('pakaiPemasangan');
    const input = document.getElementById('hargaPemasangan');
    const info = document.getElementById('infoPemasangan');
    if (!checkbox || !input || !info) return;

    if (checkbox.checked) {
        input.readOnly = false;
        input.required = true;
        input.placeholder = '2.000.000';
        input.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        input.classList.add('bg-white', 'text-gray-700');
        info.textContent = 'Masukkan harga total perangkat + jasa pemasangan teknisi.';
        info.className = 'text-xs text-blue-600 mt-1';
    } else {
        input.readOnly = true;
        input.required = false;
        input.value = '';
        input.placeholder = 'Tidak diperlukan';
        input.classList.remove('bg-white', 'text-gray-700');
        input.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        info.textContent = 'Produk ini tidak membutuhkan jasa pemasangan teknisi.';
        info.className = 'text-xs text-gray-400 mt-1';
    }
}

document.getElementById('pakaiPemasangan').addEventListener('change', updatePemasanganField);

function formatRupiahInput(angka) {
    angka = String(angka).replace(/\D/g, '');
    if (angka === '') return '';
    return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function pasangFormatHarga(input) {
    input.addEventListener('input', function () {
        let angka = this.value.replace(/\D/g, '');
        if (angka === '') {
            this.value = '';
            return;
        }
        this.value = formatRupiahInput(angka);
    });
}

pasangFormatHarga(document.getElementById('harga'));
pasangFormatHarga(document.getElementById('hargaPemasangan'));

formProduk.addEventListener('submit', function (event) {
    const kategoriTerpilih = document.querySelectorAll('.kategori-checkbox:checked');
    if (kategoriTerpilih.length === 0) {
        event.preventDefault();
        alert('Silakan pilih minimal satu kategori.');
        return;
    }

    const harga = parseInt(document.getElementById('harga').value.replace(/\D/g, ''), 10) || 0;
    const pakaiPemasangan = document.getElementById('pakaiPemasangan').checked;
    const hargaPemasangan = parseInt(document.getElementById('hargaPemasangan').value.replace(/\D/g, ''), 10) || 0;

    if (harga <= 0) {
        event.preventDefault();
        alert('Harga CCTV harus lebih dari 0.');
        return;
    }

    if (pakaiPemasangan) {
        if (hargaPemasangan <= 0) {
            event.preventDefault();
            alert('Harga pemasangan wajib diisi.');
            return;
        }
        if (hargaPemasangan < harga) {
            event.preventDefault();
            alert('Harga CCTV + pemasangan tidak boleh lebih rendah dari harga CCTV.');
            return;
        }
    }

    document.getElementById('harga').value = harga;
    if (pakaiPemasangan) {
        document.getElementById('hargaPemasangan').value = hargaPemasangan;
    } else {
        document.getElementById('hargaPemasangan').value = '';
    }

    document.getElementById('btnSimpanProduk').disabled = true;
    document.getElementById('btnSimpanProduk').classList.add('opacity-70');
    document.getElementById('textSimpan').textContent = 'Menyimpan...';
    document.getElementById('iconSimpan').className = 'fa-solid fa-spinner fa-spin';
});

document.getElementById('fileUpload').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran gambar maksimal 5 MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
        const preview = document.getElementById('imagePreview');
        preview.src = event.target.result;
        preview.classList.remove('hidden');
        document.getElementById('iconUpload').classList.add('hidden');
        document.getElementById('textUpload').classList.add('hidden');
        document.getElementById('infoGambar').textContent = 'Gambar baru dipilih.';
    };
    reader.readAsDataURL(file);
});

function resetPreview() {
    const preview = document.getElementById('imagePreview');
    preview.src = '';
    preview.classList.add('hidden');
    document.getElementById('iconUpload').classList.remove('hidden');
    document.getElementById('textUpload').classList.remove('hidden');
    document.getElementById('infoGambar').textContent = 'Pilih gambar produk.';
    document.getElementById('fileUpload').value = '';
}

function openBrandModal() {
    document.getElementById('namaMerekBaru').value = '';
    document.getElementById('pesanBrand').className = 'hidden mt-4 p-3 rounded-lg text-sm';
    modalBrand.classList.remove('hidden');
    modalBrand.classList.add('flex');
    setTimeout(() => {
        modalBrand.classList.remove('opacity-0');
        modalBrandContent.classList.remove('scale-95');
        document.getElementById('namaMerekBaru').focus();
    }, 10);
}

function closeBrandModal() {
    modalBrand.classList.add('opacity-0');
    modalBrandContent.classList.add('scale-95');
    setTimeout(() => {
        modalBrand.classList.add('hidden');
        modalBrand.classList.remove('flex');
    }, 200);
}

document.getElementById('formBrand').addEventListener('submit', async function (event) {
    event.preventDefault();
    const nama = document.getElementById('namaMerekBaru').value.trim();
    if (nama === '') {
        tampilkanPesanBrand('Nama merek wajib diisi.', false);
        return;
    }

    const button = document.getElementById('btnSimpanBrand');
    button.disabled = true;
    button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...`;

    const formData = new FormData();
    formData.append('ajax_tambah_merek', '1');
    formData.append('nama_merek', nama);

    try {
        const response = await fetch('admin_produk.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('brandSelect');
            const option = document.createElement('option');
            option.value = data.id;
            option.textContent = data.nama_merek;
            select.appendChild(option);
            select.value = data.id;

            tampilkanPesanBrand(data.message, true);
            setTimeout(() => {
                closeBrandModal();
            }, 500);
        } else {
            tampilkanPesanBrand(data.message || 'Gagal menambahkan merek.', false);
        }
    } catch (error) {
        console.error(error);
        tampilkanPesanBrand('Terjadi kesalahan pada server.', false);
    }

    button.disabled = false;
    button.innerHTML = `<i class="fa-solid fa-plus"></i> Simpan Merek`;
});

function tampilkanPesanBrand(pesan, berhasil) {
    const box = document.getElementById('pesanBrand');
    box.textContent = pesan;
    box.className = berhasil
        ? 'mt-4 p-3 rounded-lg text-sm bg-green-100 border border-green-200 text-green-700'
        : 'mt-4 p-3 rounded-lg text-sm bg-red-100 border border-red-200 text-red-700';
}

modalProduk.addEventListener('click', function (event) {
    if (event.target === modalProduk) {
        closeProdukModal();
    }
});

modalBrand.addEventListener('click', function (event) {
    if (event.target === modalBrand) {
        closeBrandModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (!modalBrand.classList.contains('hidden')) {
        closeBrandModal();
    } else if (!modalProduk.classList.contains('hidden')) {
        closeProdukModal();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('searchProduk');
    const rows = Array.from(document.querySelectorAll('tbody tr[data-search]'));
    const emptyRow = document.getElementById('barisTidakDitemukan');

    if (!search) return;

    function filterRows() {
        const keyword = search.value.toLowerCase().trim();
        let jumlah = 0;

        rows.forEach(function (row) {
            const text = (row.dataset.search || '').toLowerCase();
            const cocok = keyword === '' || text.includes(keyword);
            row.style.display = cocok ? '' : 'none';
            if (cocok) jumlah++;
        });

        if (emptyRow) {
            emptyRow.style.display = jumlah === 0 ? 'table-row' : 'none';
        }
    }

    search.addEventListener('input', filterRows);
    search.addEventListener('search', filterRows);

    document.getElementById('formFilter').addEventListener('submit', function (event) {
        event.preventDefault();
        filterRows();
    });

    filterRows();
});

</script>

</body>
</html>