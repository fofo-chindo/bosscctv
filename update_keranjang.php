<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    foreach ($_POST['qty'] as $key => $jumlah) {
        // Pastikan input jumlah di-cast ke integer
        $jumlah = (int)$jumlah;
        
        // PENTING: $key adalah string gabungan (contoh: "15|cctv"), biarkan sebagai string.
        // Jika jumlah 0 atau kurang, hapus item dari session keranjang
        if ($jumlah <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            // Update jumlah item di session keranjang
            $_SESSION['cart'][$key] = $jumlah;
        }
    }
}

// Kembalikan pengguna ke halaman keranjang
header("Location: keranjang.php");
exit();
?>