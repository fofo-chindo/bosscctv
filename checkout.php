
<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: keranjang.php");
    exit();
}

include 'header.php';

$ids = array_map('intval', array_keys($_SESSION['cart']));
$ids = array_filter($ids, fn($id) => $id > 0);

if (empty($ids)) {
    header("Location: keranjang.php");
    exit();
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

$produk_checkout = [];
$total_belanja = 0;

while ($produk = $query->fetch_assoc()) {
    $id = (int) $produk['id'];
    $qty = (int) ($_SESSION['cart'][$id] ?? 0);

    if ($qty <= 0) {
        continue;
    }

    $subtotal = (float) $produk['harga'] * $qty;
    $produk['qty'] = $qty;
    $produk['subtotal'] = $subtotal;

    $produk_checkout[] = $produk;
    $total_belanja += $subtotal;
}

if (empty($produk_checkout)) {
    header("Location: keranjang.php");
    exit();
}
?>

<main class="max-w-5xl mx-auto px-4 py-12">

    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
        Formulir Checkout Pesanan
    </h1>

    <form
        action="proses_checkout.php"
        method="POST"
        class="grid grid-cols-1 lg:grid-cols-3 gap-8"
    >

        <!-- Informasi Pemesan -->
        <div class="lg:col-span-2 bg-white rounded-2xl
                    p-6 border border-gray-200 shadow-sm
                    space-y-4">

            <h2 class="text-xl font-bold text-gray-800
                       border-b pb-3">
                Informasi Pemesan
            </h2>

            <div>
                <label class="block text-sm font-semibold
                              text-gray-700 mb-1">
                    Nama Lengkap *
                </label>

                <input
                    type="text"
                    name="nama"
                    required
                    maxlength="150"
                    class="w-full border-gray-300 rounded-xl
                           p-3 border focus:ring-2
                           focus:ring-blue-500 outline-none"
                    placeholder="Masukkan nama Anda"
                >
            </div>

            <div>
                <label class="block text-sm font-semibold
                              text-gray-700 mb-1">
                    Nomor WhatsApp / HP *
                </label>

                <input
                    type="tel"
                    name="no_hp"
                    required
                    maxlength="20"
                    class="w-full border-gray-300 rounded-xl
                           p-3 border focus:ring-2
                           focus:ring-blue-500 outline-none"
                    placeholder="Contoh: 081234567890"
                >
            </div>

            <div>
                <label class="block text-sm font-semibold
                              text-gray-700 mb-1">
                    Alamat Lengkap *
                </label>

                <textarea
                    name="alamat"
                    rows="3"
                    required
                    class="w-full border-gray-300 rounded-xl
                           p-3 border focus:ring-2
                           focus:ring-blue-500 outline-none"
                    placeholder="Alamat lengkap lokasi pemasangan / pengiriman"
                ></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold
                              text-gray-700 mb-1">
                    Catatan / Pertanyaan Konsultasi (Opsional)
                </label>

                <textarea
                    name="catatan"
                    rows="2"
                    class="w-full border-gray-300 rounded-xl
                           p-3 border focus:ring-2
                           focus:ring-blue-500 outline-none"
                    placeholder="Misal: Ingin konsultasi biaya pemasangan atau jadwal survey."
                ></textarea>
            </div>

        </div>

        <!-- Ringkasan Pesanan -->
        <div class="lg:col-span-1 bg-white rounded-2xl
                    p-6 border border-gray-200 shadow-sm
                    flex flex-col justify-between">

            <div>
                <h2 class="text-xl font-bold text-gray-800
                           border-b pb-3 mb-4">
                    Detail Pesanan
                </h2>

                <div class="space-y-3 mb-6">

                    <?php foreach ($produk_checkout as $produk): ?>

                        <div class="flex justify-between
                                    text-sm gap-3">

                            <span class="text-gray-700 font-medium">
                                <?= htmlspecialchars(
                                    $produk['nama_produk'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                (x<?= $produk['qty'] ?>)
                            </span>

                            <span class="font-bold text-gray-900
                                         whitespace-nowrap">
                                Rp <?= number_format(
                                    $produk['subtotal'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="border-t pt-4 flex
                            justify-between text-lg
                            font-bold gap-3">

                    <span>Total Pembayaran</span>

                    <span class="text-blue-600 whitespace-nowrap">
                        Rp <?= number_format(
                            $total_belanja,
                            0,
                            ',',
                            '.'
                        ) ?>
                    </span>

                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-700 hover:bg-blue-800
                       text-white font-bold py-3.5 px-4
                       rounded-xl mt-6 transition shadow-md
                       flex items-center justify-center gap-2"
            >
                <i class="fa-brands fa-whatsapp text-xl"></i>
                Buat Pesanan & Kirim WA
            </button>

        </div>

    </form>

</main>

<?php include 'footer.php'; ?>