<?php
session_start();
require_once 'koneksi.php';
include 'header.php';

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data produk yang sedang diklik
$stmt = $koneksi->prepare("
    SELECT p.*, b.nama_merek 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ? AND p.is_active = 1 
    LIMIT 1
");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="max-w-7xl mx-auto px-4 py-20 text-center text-white"><h2 class="text-2xl font-bold">Produk tidak ditemukan</h2><a href="produk.php" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-xl">Kembali ke Katalog</a></div>';
    include 'footer.php';
    exit;
}

$produk = $result->fetch_assoc();
$stmt->close();

// Bersihkan nama produk untuk mencocokkan varian sejenis di database
$namaIndukClean = trim(preg_replace('/(?:\s*\d+\s*(?:CH|Channel|channel).*$)/i', '', $produk['nama_produk']));
$brandId = $produk['brand_id'];

// Kumpulkan semua varian (seperti 4CH, 8CH, 16CH, dll.) dari database
$variasiList = [];
$sqlVarian = "
    SELECT p.*, b.nama_merek 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.brand_id = ? AND p.is_active = 1 
    AND (p.id = ? OR p.parent_id = ? OR p.nama_produk LIKE ?)
    ORDER BY p.harga ASC
";
$likeParam = "%" . $namaIndukClean . "%";
// Asumsikan struktur DB punya field parent_id
$refId = isset($produk['parent_id']) && $produk['parent_id'] !== null ? $produk['parent_id'] : $produk['id'];

$stmtVar = $koneksi->prepare($sqlVarian);
$stmtVar->bind_param("iiis", $brandId, $refId, $refId, $likeParam);
$stmtVar->execute();
$resVar = $stmtVar->get_result();

while ($v = $resVar->fetch_assoc()) {
    $vClean = trim(preg_replace('/(?:\s*\d+\s*(?:CH|Channel|channel).*$)/i', '', $v['nama_produk']));
    $vParentId = isset($v['parent_id']) ? $v['parent_id'] : null;
    
    if (strcasecmp($vClean, $namaIndukClean) === 0 || $v['id'] == $produk['id'] || $vParentId == $refId) {
        $exists = false;
        foreach ($variasiList as $existing) {
            if ($existing['id'] == $v['id']) $exists = true;
        }
        if (!$exists) {
            $variasiList[] = [
                'id' => $v['id'],
                'nama_produk' => $v['nama_produk'],
                'harga' => $v['harga'],
                'stok' => $v['stok'],
                'gambar' => !empty($v['gambar']) ? $v['gambar'] : $produk['gambar'],
                'kode_sku' => $v['kode_sku'],
                'deskripsi' => !empty($v['deskripsi']) ? $v['deskripsi'] : $produk['deskripsi'],
                'spesifikasi' => isset($v['spesifikasi']) && !empty($v['spesifikasi']) ? $v['spesifikasi'] : (isset($produk['spesifikasi']) && !empty($produk['spesifikasi']) ? $produk['spesifikasi'] : 'Data spesifikasi belum tersedia untuk produk ini.')
            ];
        }
    }
}
$stmtVar->close();

if (empty($variasiList)) {
    $variasiList[] = [
        'id' => $produk['id'],
        'nama_produk' => $produk['nama_produk'],
        'harga' => $produk['harga'],
        'stok' => $produk['stok'],
        'gambar' => $produk['gambar'],
        'kode_sku' => $produk['kode_sku'],
        'deskripsi' => $produk['deskripsi'],
        'spesifikasi' => isset($produk['spesifikasi']) && !empty($produk['spesifikasi']) ? $produk['spesifikasi'] : 'Data spesifikasi belum tersedia untuk produk ini.'
    ];
}

// Ubah array variasi ke format JSON
$json_variasi = json_encode($variasiList);
$foto_default = !empty($produk['gambar']) ? 'uploads/' . $produk['gambar'] : '';
?>

<main class="flex-grow bg-white min-h-screen pb-20">
    
    <!-- ==========================================
         NAVIGASI ATAS & BREADCRUMB (Dengan Tombol Back)
    =========================================== -->
    <div class="bg-white border-b border-gray-200 sticky top-[72px] z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <!-- Tombol Back (Selalu tampil di HP & Desktop) -->
            <a href="produk.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-bold text-sm transition-colors py-1">
                <i class="fa-solid fa-arrow-left"></i> <span>Kembali ke Katalog</span>
            </a>
            
            <!-- Breadcrumb (Sembunyi di HP agar rapi) -->
            <div class="hidden md:flex text-sm text-blue-600 font-semibold items-center">
                <a href="index.php" class="hover:text-blue-700 transition-colors">Beranda</a> 
                <span class="text-gray-400 mx-2 text-[10px]"><i class="fa-solid fa-chevron-right"></i></span>
                <a href="produk.php" class="hover:text-blue-700 transition-colors">Katalog CCTV</a> 
                <span class="text-gray-400 mx-2 text-[10px]"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="text-gray-500"><?= htmlspecialchars($produk['nama_merek'] ?? 'Merek') ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN GRID CONTENT (3 Kolom) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- ==========================================
                 KOLOM 1: GAMBAR PRODUK & GALERI THUMBNAIL 
            =========================================== -->
            <div class="col-span-1 lg:col-span-4">
                <div class="w-full aspect-square border border-gray-200 rounded-xl flex items-center justify-center p-4 bg-white mb-3 shadow-sm">
                    <?php if (!empty($foto_default) && file_exists($foto_default)): ?>
                        <img id="gambarUtama" src="<?= htmlspecialchars($foto_default) ?>" class="w-full h-full object-contain rounded-lg transition-opacity duration-300" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                    <?php else: ?>
                        <div class="text-center text-gray-300">
                            <i class="fa-solid fa-image text-8xl mb-2"></i>
                            <p>Tidak ada gambar</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnail Varian Dinamis -->
                <?php if (!empty($variasiList) && count($variasiList) > 1): ?>
                <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                    <?php foreach ($variasiList as $index => $v): ?>
                        <?php 
                        $imgThumb = !empty($v['gambar']) ? 'uploads/' . $v['gambar'] : $foto_default;
                        $isFirst = ($index === 0);
                        $borderThumb = $isFirst ? 'border-blue-600 ring-2 ring-blue-500/20' : 'border-gray-200';
                        ?>
                        <div id="thumb-<?= $v['id'] ?>" onclick="pilihVarianById(<?= $v['id'] ?>)" class="thumb-container w-16 h-16 sm:w-20 sm:h-20 border-2 <?= $borderThumb ?> rounded-lg overflow-hidden flex-shrink-0 cursor-pointer hover:border-blue-500 transition-all p-1 bg-white">
                            <?php if (!empty($imgThumb) && file_exists($imgThumb)): ?>
                                <img src="<?= htmlspecialchars($imgThumb) ?>" class="w-full h-full object-contain rounded">
                            <?php else: ?>
                                <i class="fa-solid fa-image text-gray-300 flex items-center justify-center h-full"></i>
                            <?php endif; ?>
                            <div class="text-[8px] sm:text-[10px] text-center font-bold text-gray-600 bg-gray-100 mt-0.5 truncate px-1 rounded">
                                <?= htmlspecialchars(strtoupper(substr(strrchr($v['nama_produk'], ' '), 1))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-400 mt-2"><i class="fa-solid fa-circle-info"></i> Klik foto untuk memilih paket otomatis.</p>
                <?php endif; ?>
            </div>

            <!-- ==========================================
                 KOLOM 2: INFORMASI PRODUK & DESKRIPSI 
            =========================================== -->
            <div class="col-span-1 lg:col-span-4 flex flex-col">
                <h1 id="teksNamaProduk" class="text-xl md:text-2xl font-bold text-gray-900 mb-2 leading-tight">
                    <?= htmlspecialchars($produk['nama_produk']) ?>
                </h1>
                

                <!-- Harga Utama Dinamis -->
                <div class="mb-6">
                    <h2 id="teksHargaUtama" class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-1">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></h2>
                </div>

                <!-- MENU TABS INTERAKTIF -->
                <div class="border-b border-gray-200 mb-6 flex space-x-8">
                    <button id="tab-detail" onclick="gantiTab('detail')" class="border-blue-600 text-blue-600 whitespace-nowrap py-3 px-1 border-b-2 font-bold text-sm transition-colors outline-none focus:outline-none">
                        Detail Produk
                    </button>
                    <button id="tab-spesifikasi" onclick="gantiTab('spesifikasi')" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none">
                        Spesifikasi
                    </button>
                </div>
                
                <!-- KONTEN: DETAIL PRODUK -->
                <div id="content-detail" class="text-sm text-gray-600 space-y-3 pb-6 block">
                    <p><span class="text-gray-400 w-24 inline-block">Merek:</span> <span class="font-bold text-blue-600"><?= htmlspecialchars($produk['nama_merek'] ?? '-') ?></span></p>
                    <p><span class="text-gray-400 w-24 inline-block">SKU:</span> <span id="teksSku" class="font-bold text-gray-800"><?= htmlspecialchars($produk['kode_sku'] ?? '-') ?></span></p>
                    <p><span class="text-gray-400 w-24 inline-block">Kondisi:</span> <span class="font-bold text-gray-800">Baru</span></p>
                    
                    <div id="teksDeskripsi" class="mt-6 whitespace-pre-line leading-relaxed text-gray-700 pt-4 border-t border-gray-100">
                        <?= htmlspecialchars($produk['deskripsi'] ?? 'Deskripsi belum tersedia.') ?>
                    </div>
                </div>

                <!-- KONTEN: SPESIFIKASI PRODUK -->
                <div id="content-spesifikasi" class="text-sm text-gray-600 pb-6 hidden">
                    <div id="teksSpesifikasi" class="whitespace-pre-line leading-relaxed text-gray-700 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        Data spesifikasi belum tersedia untuk produk ini.
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 KOLOM 3: KOTAK TRANSAKSI / ATUR PESANAN 
            =========================================== -->
            <div class="col-span-1 lg:col-span-4">
                <div class="border border-gray-200 rounded-2xl p-5 md:p-6 shadow-lg shadow-gray-100/50 bg-white lg:sticky lg:top-32">
                    <h3 class="font-bold text-gray-800 text-lg mb-5">Atur Pesanan</h3>
                    
                    <!-- PILIH VARIAN CHANNEL -->
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Paket & Varian:</label>
                        <div class="flex flex-col gap-3">
                            <?php foreach ($variasiList as $index => $v): ?>
                                <?php 
                                $isFirst = ($index === 0);
                                $btnStyle = $isFirst ? 'border-2 border-blue-600 text-blue-700 bg-blue-50' : 'border border-gray-300 text-gray-600 hover:border-blue-500';
                                ?>
                                <button type="button" onclick="pilihVarianById(<?= $v['id'] ?>)" id="btnVarian-<?= $v['id'] ?>" class="btn-varian <?= $btnStyle ?> p-3 rounded-xl text-sm transition text-left flex flex-col xl:flex-row items-start xl:items-center justify-between gap-2 h-auto w-full group outline-none focus:outline-none">
                                    <span class="whitespace-normal break-words w-full leading-snug font-semibold group-hover:text-blue-600">
                                        <?= htmlspecialchars($v['nama_produk']) ?>
                                    </span>
                                    <span class="whitespace-nowrap font-black shrink-0 xl:text-right mt-1 xl:mt-0 text-gray-900 group-hover:text-blue-700">
                                        Rp <?= number_format($v['harga'], 0, ',', '.') ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr class="border-gray-200 mb-5">

                    <!-- ATUR KUANTITAS -->
                    <div class="flex justify-between items-center mb-6">
                        <label class="text-sm font-bold text-gray-700">Kuantitas:</label>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center border border-gray-300 rounded-lg h-9">
                                <button type="button" onclick="ubahQty(-1)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 rounded-l-lg font-bold outline-none focus:outline-none">-</button>
                                <input type="number" id="inputQty" value="1" min="1" readonly class="w-10 text-center text-gray-800 font-bold focus:outline-none border-none p-0 text-sm bg-transparent pointer-events-none">
                                <button type="button" onclick="ubahQty(1)" class="w-8 h-full flex items-center justify-center text-blue-600 hover:bg-gray-100 rounded-r-lg font-bold outline-none focus:outline-none">+</button>
                            </div>
                            <span class="text-xs text-gray-500">Sisa <span id="teksStok" class="font-bold text-gray-800"><?= (int)$produk['stok'] ?></span></span>
                        </div>
                    </div>

                    <!-- SUBTOTAL -->
                    <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <span class="text-gray-600 font-semibold text-sm">Subtotal</span>
                        <span id="teksSubtotal" class="text-xl font-black text-gray-900">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></span>
                    </div>

                    <!-- FORM KERANJANG & BELI -->
                    <form action="tambah_keranjang.php" method="POST" class="flex flex-col gap-2">
                        <input type="hidden" name="id" id="formProductId" value="<?= (int)$produk['id'] ?>">
                        <input type="hidden" name="jumlah" id="formQty" value="1">
                        
                        <!-- Tombol Beli Langsung -->
                        <button type="submit" name="aksi" value="beli" class="w-full bg-white border-2 border-blue-600 text-blue-600 hover:bg-blue-50 font-bold py-3 rounded-xl text-sm transition outline-none focus:outline-none">
                            Beli Langsung
                        </button>
                        
                        <!-- Tombol Keranjang -->
                        <button type="submit" name="aksi" value="keranjang" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-md outline-none focus:outline-none">
                            <i class="fa-solid fa-cart-plus"></i> + Keranjang
                        </button>
                    </form>
                    
                </div>
            </div>

        </div>
    </div>
</main>

<style>
.custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #f97316; border-radius: 10px; } 
</style>

<script>
const dataVariasi = <?= $json_variasi ?>;
let activeVarianId = dataVariasi[0] ? dataVariasi[0].id : <?= (int)$id_produk ?>;
let currentQty = 1;

const formatRupiah = (angka) => 'Rp ' + Number(angka).toLocaleString('id-ID');

function findVarian(id) {
    return dataVariasi.find(v => v.id == id) || dataVariasi[0];
}

// ==========================================
// FUNGSI GANTI TAB (DETAIL & SPESIFIKASI)
// ==========================================
function gantiTab(tabAktif) {
    const tabDetail = document.getElementById('tab-detail');
    const tabSpes = document.getElementById('tab-spesifikasi');
    const contentDetail = document.getElementById('content-detail');
    const contentSpes = document.getElementById('content-spesifikasi');

    const classAktif = "border-blue-600 text-blue-600 whitespace-nowrap py-3 px-1 border-b-2 font-bold text-sm transition-colors outline-none focus:outline-none";
    const classPasif = "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors outline-none focus:outline-none";

    if (tabAktif === 'detail') {
        tabDetail.className = classAktif;
        tabSpes.className = classPasif;
        contentDetail.classList.remove('hidden');
        contentDetail.classList.add('block');
        contentSpes.classList.remove('block');
        contentSpes.classList.add('hidden');
    } else {
        tabSpes.className = classAktif;
        tabDetail.className = classPasif;
        contentSpes.classList.remove('hidden');
        contentSpes.classList.add('block');
        contentDetail.classList.remove('block');
        contentDetail.classList.add('hidden');
    }
}

// ==========================================
// FUNGSI UPDATE TAMPILAN VARIAN PRODUK
// ==========================================
function updateUI() {
    const v = findVarian(activeVarianId);
    if (!v) return;

    // 1. Update Tombol Varian Aktif
    document.querySelectorAll('.btn-varian').forEach(btn => {
        btn.className = "btn-varian border border-gray-300 text-gray-600 hover:border-blue-500 p-3 rounded-xl text-sm transition text-left flex flex-col xl:flex-row items-start xl:items-center justify-between gap-2 h-auto w-full group outline-none focus:outline-none";
    });
    const btnAktif = document.getElementById('btnVarian-' + v.id);
    if (btnAktif) {
        btnAktif.className = "btn-varian border-2 border-blue-600 text-blue-700 bg-blue-50 p-3 rounded-xl text-sm transition text-left flex flex-col xl:flex-row items-start xl:items-center justify-between gap-2 h-auto w-full group outline-none focus:outline-none";
    }

    // 2. Update Border Thumbnail
    document.querySelectorAll('.thumb-container').forEach(el => {
        el.classList.remove('border-blue-600', 'ring-2', 'ring-blue-500/20');
        el.classList.add('border-gray-200');
    });
    const thumbAktif = document.getElementById('thumb-' + v.id);
    if (thumbAktif) {
        thumbAktif.classList.remove('border-gray-200');
        thumbAktif.classList.add('border-blue-600', 'ring-2', 'ring-blue-500/20');
    }

    // 3. Update Foto Utama (Animasi halus)
    const imgElement = document.getElementById('gambarUtama');
    const fotoBaru = v.gambar ? (v.gambar.includes('uploads') ? v.gambar : 'uploads/' + v.gambar) : '<?= $foto_default ?>';
    if (imgElement) {
        imgElement.style.opacity = 0;
        setTimeout(() => {
            imgElement.src = fotoBaru;
            imgElement.style.opacity = 1;
        }, 150);
    }

    // 4. Update Teks Info Produk
    document.getElementById('teksNamaProduk').textContent = v.nama_produk;
    document.getElementById('teksSku').textContent = v.kode_sku || '-';
    document.getElementById('teksDeskripsi').textContent = v.deskripsi || 'Deskripsi belum tersedia.';
    document.getElementById('teksSpesifikasi').textContent = v.spesifikasi || 'Data spesifikasi belum tersedia untuk produk ini.';
    document.getElementById('teksStok').textContent = v.stok;

    // 5. Hitung Harga & Subtotal
    let hargaSatuan = parseFloat(v.harga) || 0;
    let subtotal = hargaSatuan * currentQty;

    document.getElementById('teksHargaUtama').textContent = formatRupiah(hargaSatuan);
    document.getElementById('teksSubtotal').textContent = formatRupiah(subtotal);

    // 6. Update Form Hidden Inputs
    document.getElementById('formProductId').value = v.id;
    document.getElementById('inputQty').value = currentQty;
    document.getElementById('formQty').value = currentQty;
}

function pilihVarianById(id) {
    activeVarianId = id;
    currentQty = 1; 
    updateUI();
}

function ubahQty(val) {
    const v = findVarian(activeVarianId);
    let stokMaks = v.stok > 0 ? parseInt(v.stok) : 999;
    let baru = currentQty + val;
    
    if (baru >= 1 && baru <= stokMaks) {
        currentQty = baru;
        updateUI();
    }
}

window.onload = () => updateUI();
</script>

<?php include 'footer.php'; ?>