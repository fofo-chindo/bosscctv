<?php
session_start();

$key = $_GET['key'] ?? '';
$parts = explode('|', (string)$key);

if (
    count($parts) === 2 &&
    ctype_digit($parts[0]) &&
    (int)$parts[0] > 0 &&
    in_array($parts[1], ['cctv', 'pemasangan'], true)
) {
    unset($_SESSION['cart'][(int)$parts[0] . '|' . $parts[1]]);
}

header("Location: keranjang.php");
exit;