<?php

session_start();

require_once 'koneksi.php';

// ======================================================
// PROTEKSI
// ======================================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')
) {
    header("Location: login.php");
    exit;
}

// ======================================================
// AMBIL ID
// ======================================================

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: admin_produk.php?status=gagal");
    exit;
}

// ======================================================
// AMBIL DATA PRODUK
// ======================================================

$stmt = $koneksi->prepare("
    SELECT gambar
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: admin_produk.php?status=gagal");

    exit;
}

$produk = $result->fetch_assoc();

$stmt->close();

// ======================================================
// HAPUS DATABASE
// ======================================================

$stmt = $koneksi->prepare("
    DELETE FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    $stmt->close();

    // ==================================================
    // HAPUS GAMBAR
    // ==================================================

    if (
        !empty($produk['gambar']) &&
        file_exists('uploads/' . $produk['gambar'])
    ) {

        unlink(
            'uploads/' . $produk['gambar']
        );
    }

    header(
        "Location: admin_produk.php?status=sukses_hapus"
    );

    exit;

} else {

    $stmt->close();

    header(
        "Location: admin_produk.php?status=gagal"
    );

    exit;
}

?>