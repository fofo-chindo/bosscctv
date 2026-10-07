<?php
session_start();
require_once 'koneksi.php';

$no_wa_admin = "6281377766667";
$pesan = $_SESSION['pesan_keranjang'] ?? '';
unset($_SESSION['pesan_keranjang']);

$total_belanja = 0;
$total_item = 0;
$cart_tampil = [];

$cart = $_SESSION['cart'] ?? [];

if (is_array($cart) && !empty($cart)) {
    $ids = [];

    foreach ($cart as $key => $qty) {
        $parts = explode('|', (string)$key);

        if (
            count($parts) === 2 &&
            ctype_digit($parts[0]) &&
            (int)$parts[0] > 0 &&
            in_array($parts[1], ['cctv', 'pemasangan'], true) &&
            (int)$qty > 0
        ) {
            $ids[] = (int)$parts[0];
        }
    }

    $ids = array_unique($ids);

    if ($ids) {
        $id_list = implode(',', $ids);
        $result = $koneksi->query("
            SELECT id, nama_produk, harga, harga_pemasangan,
                   stok, gambar, is_active
            FROM products
            WHERE id IN ($id_list)
        ");

        $produk_data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $produk_data[(int)$row['id']] = $row;
            }
        }

        foreach ($cart as $key => $qty) {
            $parts = explode('|', (string)$key);

            if (
                count($parts) !== 2 ||
                !ctype_digit($parts[0]) ||
                !in_array($parts[1], ['cctv', 'pemasangan'], true)
            ) {
                continue;
            }

            $id = (int)$parts[0];
            $paket = $parts[1];
            $qty = (int)$qty;

            if (
                $qty < 1 ||
                !isset($produk_data[$id]) ||
                (int)$produk_data[$id]['is_active'] !== 1
            ) {
                continue;
            }

            $p = $produk_data[$id];

            if ($paket === 'pemasangan') {
                $nama_paket = 'CCTV + Pemasangan';
                $harga = (float)$p['harga_pemasangan'];
            } else {
                $nama_paket = 'CCTV Saja';
                $harga = (float)$p['harga'];
            }

            $subtotal = $harga * $qty;

            $cart_tampil[] = [
                'key' => $id . '|' . $paket,
                'id' => $id,
                'nama' => $p['nama_produk'],
                'gambar' => $p['gambar'],
                'paket' => $nama_paket,
                'harga' => $harga,
                'qty' => $qty,
                'stok' => (int)$p['stok'],
                'subtotal' => $subtotal
            ];

            $total_belanja += $subtotal;
            $total_item += $qty;
        }
    }
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

$draf_wa = "Halo GSE Boss CCTV, saya ingin berkonsultasi mengenai barang di keranjang:\n\n";

foreach ($cart_tampil as $item) {
    $draf_wa .=
        "- {$item['nama']}\n" .
        "  Paket: {$item['paket']}\n" .
        "  Jumlah: {$item['qty']}x\n" .
        "  Subtotal: " . rupiah($item['subtotal']) . "\n\n";
}

$draf_wa .= "*Total Estimasi:* " . rupiah($total_belanja);
$link_wa = "https://wa.me/" . $no_wa_admin . "?text=" . urlencode($draf_wa);

include 'header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">
        <i class="fa-solid fa-cart-shopping text-blue-600"></i>
        Keranjang Belanja
    </h1>

    <?php if ($pesan !== ''): ?>
        <div class="mb-5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 p-4">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_tampil)): ?>
        <div class="bg-white rounded-2xl p-12 text-center shadow-sm border">
            <i class="fa-solid fa-cart-arrow-down text-4xl text-blue-500 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Keranjang Belanja Anda Kosong</h2>
            <p class="text-gray-500 mb-6">Anda belum menambahkan produk ke keranjang.</p>
            <a href="produk.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-xl">
                Mulai Belanja
            </a>
        </div>
    <?php else: ?>
        <form action="update_keranjang.php" method="POST">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white rounded-2xl shadow-sm border divide-y">
                        <?php foreach ($cart_tampil as $item): ?>
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden">
                                        <?php if (!empty($item['gambar'])): ?>
                                            <img
                                                src="uploads/<?= htmlspecialchars($item['gambar']) ?>"
                                                alt="<?= htmlspecialchars($item['nama']) ?>"
                                                class="w-full h-full object-cover"
                                            >
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h3 class="font-bold text-gray-900">
                                            <?= htmlspecialchars($item['nama']) ?>
                                        </h3>
                                        <p class="text-orange-600 text-sm font-semibold">
                                            <?= htmlspecialchars($item['paket']) ?>
                                        </p>
                                        <p class="text-blue-600 text-sm font-semibold">
                                            <?= rupiah($item['harga']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                    <input
                                        type="number"
                                        name="qty[<?= htmlspecialchars($item['key']) ?>]"
                                        value="<?= $item['qty'] ?>"
                                        min="1"
                                        max="<?= max(1, $item['stok']) ?>"
                                        required
                                        class="w-20 border rounded-lg p-2 text-center"
                                    >

                                    <div class="text-right min-w-[120px]">
                                        <span class="block text-xs text-gray-400">Subtotal</span>
                                        <strong><?= rupiah($item['subtotal']) ?></strong>
                                    </div>

                                    <a
                                        href="hapus_keranjang.php?key=<?= urlencode($item['key']) ?>"
                                        onclick="return confirm('Hapus produk ini?')"
                                        class="text-red-500 p-2"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between items-center">
                        <a href="produk.php" class="text-gray-600 font-semibold">
                            <i class="fa-solid fa-arrow-left"></i> Lanjut Belanja
                        </a>
                        <button type="submit" class="bg-gray-800 text-white px-4 py-3 rounded-lg">
                            Update Keranjang
                        </button>
                    </div>
                </div>

                <div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border space-y-4 sticky top-28">
                        <h2 class="text-lg font-bold border-b pb-3">Ringkasan Belanja</h2>
                        <div class="flex justify-between text-sm">
                            <span>Total Item</span>
                            <strong><?= $total_item ?> Barang</strong>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-3">
                            <span>Total Harga</span>
                            <span class="text-blue-600"><?= rupiah($total_belanja) ?></span>
                        </div>



                        <a href="<?= htmlspecialchars($link_wa) ?>" target="_blank" rel="noopener"
                           class="block bg-emerald-600 text-white text-center font-semibold py-3 rounded-xl">
                            <i class="fa-brands fa-whatsapp"></i>
                            Tanya / Konsultasi via WA
                        </a>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>