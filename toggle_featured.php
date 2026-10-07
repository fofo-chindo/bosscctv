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
// AMBIL ID PRODUK
// ======================================================

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {

    header(
        "Location: admin_produk.php?status=gagal"
    );

    exit;
}

// ======================================================
// AMBIL STATUS SEKARANG
// ======================================================

$stmt = $koneksi->prepare("
    SELECT
        id,
        is_featured
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result =
    $stmt->get_result();

$produk =
    $result->fetch_assoc();

$stmt->close();

// ======================================================
// CEK PRODUK
// ======================================================

if (!$produk) {

    header(
        "Location: admin_produk.php?status=produk_tidak_ditemukan"
    );

    exit;
}

// ======================================================
// BALIK STATUS
// ======================================================

$status_sekarang =
    (int)$produk['is_featured'];

$status_baru =
    $status_sekarang === 1
        ? 0
        : 1;

// ======================================================
// UPDATE
// ======================================================

$stmt = $koneksi->prepare("
    UPDATE products
    SET is_featured = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ii",
    $status_baru,
    $id
);

$stmt->execute();

if (
    $stmt->affected_rows >= 0
) {

    $stmt->close();

    if ($status_baru === 1) {

        header(
            "Location: admin_produk.php?status=sukses_featured"
        );

    } else {

        header(
            "Location: admin_produk.php?status=sukses_unfeatured"
        );

    }

    exit;

}

$stmt->close();

header(
    "Location: admin_produk.php?status=gagal"
);

exit;

?>