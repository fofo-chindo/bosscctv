
<?php
session_start();
require_once 'koneksi.php';

// Pastikan request berasal dari form checkout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit();
}

// Pastikan keranjang tidak kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: keranjang.php");
    exit();
}

// Ambil dan validasi data pembeli
$nama = trim($_POST['nama'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

if ($nama === '' || $no_hp === '' || $alamat === '') {
    die("Nama, nomor HP, dan alamat wajib diisi.");
}

// Buat kode pesanan
$kode_pesanan = "ORD-" . date('YmdHis') . "-" . random_int(100, 999);

// Ambil produk dari keranjang
$cart = $_SESSION['cart'];
$ids = array_map('intval', array_keys($cart));
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    die("Keranjang tidak valid.");
}

$id_list = implode(',', $ids);

$query = $koneksi->query(
    "SELECT id, nama_produk, harga
     FROM products
     WHERE id IN ($id_list)"
);

if (!$query) {
    die("Gagal mengambil produk: " . $koneksi->error);
}

$total_belanja = 0;
$items = [];

while ($produk = $query->fetch_assoc()) {
    $id = (int) $produk['id'];
    $qty = (int) ($cart[$id] ?? 0);

    if ($qty <= 0) {
        continue;
    }

    $harga = (float) $produk['harga'];
    $subtotal = $harga * $qty;

    $total_belanja += $subtotal;

    $items[] = [
        'id' => $id,
        'nama' => $produk['nama_produk'],
        'harga' => $harga,
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
}

if (empty($items)) {
    die("Tidak ada produk valid dalam keranjang.");
}

// Simpan pesanan dan detail dalam satu transaksi
$koneksi->begin_transaction();

try {
    // Simpan pesanan utama
    $stmt = $koneksi->prepare(
        "INSERT INTO orders
(kode_pesanan, nama_pembeli, no_hp, alamat,
 catatan, total_harga, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );

    if (!$stmt) {
        throw new Exception($koneksi->error);
    }

    $stmt->bind_param(
        "sssssd",
        $kode_pesanan,
        $nama,
        $no_hp,
        $alamat,
        $catatan,
        $total_belanja
    );

    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Simpan detail produk
    $stmt_item = $koneksi->prepare(
        "INSERT INTO order_items
        (order_id, product_id, nama_produk,
         harga, jumlah, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt_item) {
        throw new Exception($koneksi->error);
    }

    foreach ($items as $item) {
        $product_id = $item['id'];
        $nama_produk = $item['nama'];
        $harga = $item['harga'];
        $jumlah = $item['qty'];
        $subtotal = $item['subtotal'];

        $stmt_item->bind_param(
            "iisdid",
            $order_id,
            $product_id,
            $nama_produk,
            $harga,
            $jumlah,
            $subtotal
        );

        $stmt_item->execute();
    }

    $stmt_item->close();

    // Pastikan seluruh data tersimpan
    $koneksi->commit();

} catch (Throwable $e) {
    $koneksi->rollback();
    die("Gagal menyimpan pesanan: " . htmlspecialchars($e->getMessage()));
}

// Kosongkan keranjang setelah pesanan berhasil disimpan
unset($_SESSION['cart']);

// Nomor WhatsApp Admin
$no_wa_admin = "62895605091222";

// Susun pesan WhatsApp
$pesan_wa = "Halo GSE Boss CCTV,\n";
$pesan_wa .= "Saya telah membuat pesanan baru melalui website.\n\n";

$pesan_wa .= "*Kode Pesanan:* #" . $kode_pesanan . "\n";
$pesan_wa .= "*Nama:* " . $nama . "\n";
$pesan_wa .= "*No HP:* " . $no_hp . "\n";
$pesan_wa .= "*Alamat:* " . $alamat . "\n\n";

$pesan_wa .= "*Detail Barang:*\n";

foreach ($items as $item) {
    $pesan_wa .= "- " . $item['nama']
        . " (" . $item['qty'] . "x) : Rp "
        . number_format($item['subtotal'], 0, ',', '.')
        . "\n";
}

$pesan_wa .= "\n*Total:* Rp "
    . number_format($total_belanja, 0, ',', '.')
    . "\n";

if ($catatan !== '') {
    $pesan_wa .= "\n*Catatan/Pertanyaan:* "
        . $catatan . "\n";
}

$pesan_wa .= "\nMohon konfirmasi pesanan dan info selanjutnya. Terima kasih!";

// Arahkan ke WhatsApp
$link_wa = "https://wa.me/" . $no_wa_admin
    . "?text=" . urlencode($pesan_wa);

header("Location: " . $link_wa);
exit();
?>