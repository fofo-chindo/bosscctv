<?php

session_start();

require_once 'koneksi.php';

/* =========================================================
   AMBIL DATA
========================================================= */

$id_produk = (int) ($_POST['id'] ?? 0);
$paket = $_POST['paket'] ?? 'cctv';

/* =========================================================
   VALIDASI PAKET
========================================================= */

$paket_valid = ['cctv', 'pemasangan'];

if (!in_array($paket, $paket_valid, true)) {
    header("Location: produk.php");
    exit;
}

/* =========================================================
   VALIDASI ID
========================================================= */

if ($id_produk <= 0) {
    header("Location: produk.php");
    exit;
}

/* =========================================================
   AMBIL PRODUK
========================================================= */

$stmt = $koneksi->prepare("
    SELECT
        id,
        nama_produk,
        harga,
        harga_pemasangan,
        stok,
        is_active
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id_produk);
$stmt->execute();

$result = $stmt->get_result();
$produk = $result->fetch_assoc();

$stmt->close();

if (!$produk) {
    header("Location: produk.php");
    exit;
}

/* =========================================================
   VALIDASI AKTIF
========================================================= */

if ((int)$produk['is_active'] !== 1) {
    header("Location: produk.php");
    exit;
}

/* =========================================================
   VALIDASI STOK
========================================================= */

if ((int)$produk['stok'] <= 0) {
    header("Location: produk.php");
    exit;
}

/* =========================================================
   BUAT CART
========================================================= */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/*
   Format key:
   ID|PAKET

   Contoh:
   15|cctv
   15|pemasangan
*/

$cart_key = $id_produk . '|' . $paket;

/* =========================================================
   HITUNG JUMLAH PRODUK YANG SUDAH ADA
========================================================= */

$total_produk_di_cart = 0;

foreach ($_SESSION['cart'] as $key => $qty) {

    $parts = explode('|', (string)$key);

    if ((int)$parts[0] === $id_produk) {
        $total_produk_di_cart += (int)$qty;
    }
}

/* =========================================================
   TAMBAH 1
========================================================= */

$jumlah_lama = (int)($_SESSION['cart'][$cart_key] ?? 0);
$jumlah_baru = $jumlah_lama + 1;

if (
    $total_produk_di_cart + 1 >
    (int)$produk['stok']
) {
    $jumlah_baru = $jumlah_lama;
}

/* =========================================================
   SIMPAN
========================================================= */

if ($jumlah_baru > 0) {
    $_SESSION['cart'][$cart_key] = $jumlah_baru;
}

/* =========================================================
   KEMBALI
========================================================= */

header("Location: keranjang.php");
exit;