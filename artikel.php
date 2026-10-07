<?php
session_start();

require_once 'koneksi.php';

$current_page = basename($_SERVER['PHP_SELF']);

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += (int)$qty;
    }
}

/*
|--------------------------------------------------------------------------
| DATA ARTIKEL
|--------------------------------------------------------------------------
*/
$artikel = [
    [
        'kategori' => 'Produk',
        'judul' => 'Produk CCTV Berkualitas untuk Berbagai Kebutuhan',
        'tanggal' => '05 Oktober 2026',
        'icon' => 'fa-camera',
        'warna' => 'from-blue-600 to-indigo-900',
        'ringkas' => 'BOSS CCTV menyediakan berbagai pilihan CCTV yang dapat disesuaikan dengan kebutuhan pengawasan rumah, toko, kantor, maupun tempat usaha.',
        'isi' => [
            'BOSS CCTV menyediakan berbagai pilihan produk CCTV untuk membantu memenuhi kebutuhan keamanan dan pengawasan di berbagai lokasi.',
            'Pemilihan produk dapat disesuaikan dengan kondisi lokasi, area yang ingin dipantau, kualitas gambar yang dibutuhkan, serta fitur yang diperlukan.',
            'Dengan pilihan produk yang sesuai, pelanggan dapat memperoleh sistem CCTV yang lebih optimal untuk kebutuhan keamanan.'
        ]
    ],
    [
        'kategori' => 'Teknisi',
        'judul' => 'Didukung Teknisi yang Berpengalaman di Bidangnya',
        'tanggal' => '05 Oktober 2026',
        'icon' => 'fa-user-gear',
        'warna' => 'from-orange-500 to-red-900',
        'ringkas' => 'Pemasangan CCTV ditangani oleh teknisi yang memahami instalasi, konfigurasi, jaringan, servis, dan perawatan CCTV.',
        'isi' => [
            'Pemasangan CCTV membutuhkan ketelitian agar kamera dapat memberikan area pemantauan yang sesuai dengan kebutuhan pelanggan.',
            'Teknisi BOSS CCTV menangani pekerjaan yang berkaitan dengan pemasangan, konfigurasi, jaringan, servis, dan perawatan CCTV.',
            'Setiap lokasi memiliki kondisi yang berbeda sehingga proses pemasangan disesuaikan dengan kebutuhan dan kondisi di lapangan.'
        ]
    ],
    [
        'kategori' => 'Instalasi',
        'judul' => 'Pemasangan CCTV yang Terencana dan Profesional',
        'tanggal' => '05 Oktober 2026',
        'icon' => 'fa-screwdriver-wrench',
        'warna' => 'from-emerald-500 to-teal-900',
        'ringkas' => 'Mulai dari posisi kamera, penataan kabel, konfigurasi perangkat, hingga jaringan disesuaikan dengan kondisi lokasi.',
        'isi' => [
            'Pemasangan CCTV tidak hanya memasang kamera pada suatu tempat. Posisi kamera perlu diperhatikan agar area penting dapat terpantau dengan baik.',
            'BOSS CCTV membantu proses pemasangan perangkat, penataan kabel, konfigurasi kamera dan perangkat pendukung, serta pengaturan jaringan apabila diperlukan.',
            'Perencanaan pemasangan yang baik membantu sistem CCTV bekerja lebih sesuai dengan kebutuhan pengawasan di lokasi.'
        ]
    ],
    [
        'kategori' => 'Layanan',
        'judul' => 'Servis dan Perawatan CCTV',
        'tanggal' => '05 Oktober 2026',
        'icon' => 'fa-headset',
        'warna' => 'from-purple-600 to-fuchsia-900',
        'ringkas' => 'BOSS CCTV juga menyediakan layanan servis dan perawatan untuk membantu menjaga sistem CCTV tetap berfungsi dengan baik.',
        'isi' => [
            'Sistem CCTV yang sudah terpasang tetap membutuhkan pemeriksaan dan perawatan agar perangkat dapat digunakan dengan baik.',
            'Pemeriksaan dapat mencakup kondisi kamera, kualitas gambar, koneksi jaringan, perangkat penyimpanan, kabel, dan komponen pendukung lainnya.',
            'Dengan adanya layanan servis dan perawatan, pelanggan dapat memperoleh bantuan ketika sistem CCTV mengalami kendala atau membutuhkan pemeriksaan.'
        ]
    ]
];

/*
|--------------------------------------------------------------------------
| GALERI DOKUMENTASI
|--------------------------------------------------------------------------
*/
$folderAsset = __DIR__ . '/assets/';

/*
 * Video 1, 2, 4, 5, 6 menggunakan file MP4 dari folder assets.
 * Video 3 sengaja menggunakan gambar karena sesuai permintaan.
 */
$dokumentasi = [
    [
        'tipe' => 'video',
        'file' => 'assets/video-1.mp4',
        'judul' => 'Pemasangan CCTV',
        'keterangan' => 'Dokumentasi proses pemasangan kamera CCTV di lokasi pelanggan.'
    ],
    [
        'tipe' => 'video',
        'file' => 'assets/video-2.mp4',
        'judul' => 'Proses Instalasi',
        'keterangan' => 'Dokumentasi teknisi saat melakukan instalasi perangkat CCTV.'
    ],
    [
        'tipe' => 'gambar',
        'file' => 'assets/video-3.jpeg',
        'judul' => 'Teknisi BOSS CCTV',
        'keterangan' => 'Dokumentasi teknisi saat melakukan pekerjaan di lapangan.'
    ],
    [
        'tipe' => 'video',
        'file' => 'assets/video-4.mp4',
        'judul' => 'Konfigurasi CCTV',
        'keterangan' => 'Dokumentasi proses konfigurasi dan pengecekan sistem CCTV.'
    ],
    [
        'tipe' => 'video',
        'file' => 'assets/video-5.mp4',
        'judul' => 'Instalasi Jaringan',
        'keterangan' => 'Dokumentasi penataan dan konfigurasi jaringan CCTV.'
    ],
    [
        'tipe' => 'video',
        'file' => 'assets/video-6.mp4',
        'judul' => 'Servis CCTV',
        'keterangan' => 'Dokumentasi kegiatan pemeriksaan dan perawatan CCTV.'
    ]
];

$wa_number = '62895605091222';
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel & Dokumentasi | BOSS CCTV Bangka Belitung</title>
    <meta name="description" content="Informasi produk, teknisi, pemasangan, servis dan dokumentasi pekerjaan BOSS CCTV Bangka Belitung.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="tailwind-config.js"></script>
    <link rel="stylesheet" href="style.css">

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .image-placeholder {
            background:
                radial-gradient(circle at 20% 20%, rgba(59,130,246,.15), transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(249,115,22,.15), transparent 40%),
                linear-gradient(135deg, #0f172a, #1e3a8a);
        }
    </style>
</head>

<body class="bg-slate-50 flex flex-col min-h-screen font-sans">

<?php include 'header.php'; ?>

<!-- =========================================================
     BACKGROUND UTAMA
========================================================= -->
<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative flex-grow overflow-hidden">
    
    <!-- Wadah Background Terisolasi & Pernak-pernik Shape Dinamis -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Glow Cahaya Modern dengan Efek Pulse -->
        <div class="absolute top-10 right-1/4 w-[450px] h-[450px] bg-violet-500/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute top-1/2 left-10 w-[400px] h-[400px] bg-blue-500/15 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-10 right-10 w-[450px] h-[450px] bg-cyan-500/15 rounded-full blur-[120px] animate-pulse" style="animation-delay: 4s;"></div>

        <!-- Watermark Ikon FontAwesome -->
        <div class="absolute inset-0 opacity-[0.07] text-white">
            <i class="fa-solid fa-video absolute top-20 left-10 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-camera absolute top-32 right-16 text-7xl transform rotate-12"></i>
            <i class="fa-solid fa-shield-halved absolute top-1/2 left-16 text-7xl transform rotate-6"></i>
            <i class="fa-solid fa-network-wired absolute bottom-32 right-20 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-server absolute top-1/4 left-1/3 text-5xl transform rotate-45"></i>
            <i class="fa-solid fa-lock absolute bottom-1/4 right-1/3 text-5xl transform -rotate-45"></i>
        </div>
    </div>


    <!-- =========================================================
         KONTEN UTAMA
    ========================================================= -->
    <section class="relative z-10">
        <!-- JARAK KE HEADER DISAMAKAN DENGAN produk.php (pt-12 md:pt-16 pb-16) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-12 md:pt-16 pb-16">

            <!-- =========================================================
                 BAGIAN 1: ARTIKEL & INFORMASI
            ========================================================= -->
            <div class="text-center mb-16">
                <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-orange-500/20 border border-orange-500/40 text-orange-300 font-bold tracking-widest text-xs uppercase mb-4 backdrop-blur-md shadow-lg">
                    <i class="fa-solid fa-book-open"></i> Informasi BOSS CCTV
                </span>

                <h2 class="text-4xl md:text-5xl font-black text-white mt-2 leading-tight drop-shadow-md">
                    Produk & <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-teal-300">Layanan Kami</span>
                </h2>

                <div class="w-24 h-1.5 bg-gradient-to-r from-blue-400 to-cyan-300 mx-auto mt-6 rounded-full shadow-[0_0_15px_rgba(56,189,248,0.5)]"></div>

                <p class="mt-6 text-purple-100 max-w-2xl mx-auto text-lg font-light leading-relaxed">
                    Pelajari lebih dalam mengenai standar kualitas produk, metode instalasi, dan dukungan teknisi profesional yang kami berikan.
                </p>
            </div>

            <!-- GRID ARTIKEL (Glowing Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-28">
                <?php foreach ($artikel as $index =>$item): ?>
                    <!-- Container Relative untuk Efek Glow di Belakang -->
                    <div class="relative group h-full">
                        
                        <!-- Glow Background Effect pada Hover -->
                        <div class="absolute -inset-0.5 bg-gradient-to-r <?= $item['warna'] ?> rounded-[2.5rem] blur opacity-0 group-hover:opacity-40 transition duration-500"></div>
                        
                        <article class="relative h-full bg-white/95 backdrop-blur-xl rounded-[2rem] overflow-hidden border border-white/40 shadow-2xl transform group-hover:-translate-y-2 transition-all duration-300 flex flex-col z-10">
                            
                            <!-- Header / Gambar Artikel -->
                            <div class="relative h-48 bg-gradient-to-br <?= $item['warna'] ?> overflow-hidden shrink-0 flex items-center justify-center">
                                <!-- Lingkaran Abstrak -->
                                <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full bg-white/10 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="absolute -left-10 -top-10 w-40 h-40 rounded-full bg-white/10 group-hover:scale-150 transition-transform duration-700"></div>
                                
                                <div class="relative flex flex-col items-center justify-center text-white z-10">
                                    <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mb-3 shadow-lg transform group-hover:rotate-12 transition-transform duration-300">
                                        <i class="fa-solid <?= $item['icon'] ?> text-3xl drop-shadow-md"></i>
                                    </div>
                                    <span class="px-4 py-1 rounded-full bg-black/20 text-[10px] font-bold uppercase tracking-widest shadow-inner">
                                        <?= htmlspecialchars($item['kategori']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Konten Artikel -->
                            <div class="p-6 md:p-8 flex flex-col flex-grow">
                                <p class="text-xs text-indigo-500 font-bold mb-2 flex items-center gap-1.5">
                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['tanggal']) ?>
                                </p>
                                <h3 class="text-xl font-extrabold text-gray-900 leading-tight mb-3 line-clamp-2">
                                    <?= htmlspecialchars($item['judul']) ?>
                                </h3>
                                <p class="text-sm text-gray-500 leading-relaxed line-clamp-3 mb-6 flex-grow">
                                    <?= htmlspecialchars($item['ringkas']) ?>
                                </p>
                                
                                <!-- Tombol Modern -->
                                <button type="button" onclick="openArticle(<?= $index ?>)" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-50 text-indigo-600 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-blue-600 hover:text-white font-bold text-sm px-5 py-3 rounded-xl transition-all duration-300 shadow-sm hover:shadow-md group/btn">
                                    Baca Detail <i class="fa-solid fa-arrow-right text-xs transform group-hover/btn:translate-x-1 transition-transform"></i>
                                </button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>


            <!-- =========================================================
                 BAGIAN 2: GALERI DOKUMENTASI
            ========================================================= -->
            <div class="border-t border-white/20 pt-20">
                <div class="text-center mb-16">
                    <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 font-bold tracking-widest text-xs uppercase mb-4 backdrop-blur-md shadow-lg">
                        <i class="fa-solid fa-images"></i> Galeri Lapangan
                    </span>

                    <h2 class="text-4xl md:text-5xl font-black text-white mt-2 drop-shadow-md">
                        Dokumentasi Pekerjaan
                    </h2>

                    <div class="w-24 h-1.5 bg-gradient-to-r from-blue-400 to-cyan-300 mx-auto mt-6 rounded-full shadow-[0_0_15px_rgba(56,189,248,0.5)]"></div>

                    <p class="mt-6 text-purple-100 max-w-2xl mx-auto text-lg font-light">
                        Saksikan dokumentasi kegiatan pemasangan, instalasi, konfigurasi, jaringan, dan servis CCTV BOSS CCTV di lapangan.
                    </p>
                </div>

                <!-- GRID GALERI (Cinematic Cards) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($dokumentasi as $index =>$foto): ?>
                        <div class="relative group rounded-[2rem] overflow-hidden bg-white/5 border border-white/20 shadow-2xl hover:-translate-y-2 transition-all duration-500 cursor-pointer">
                            
                            <div class="relative aspect-[4/3] overflow-hidden">
                                <?php if (file_exists(__DIR__ . '/' . $foto['file'])): ?>
                                    <?php if ($foto['tipe'] === 'video'): ?>
                                        <video
                                            class="w-full h-full object-cover bg-black"
                                            controls
                                            preload="metadata"
                                            playsinline
                                        >
                                            <source src="<?= htmlspecialchars($foto['file']) ?>" type="video/mp4">
                                            Browser Anda tidak mendukung pemutar video HTML5.
                                        </video>
                                    <?php else: ?>
                                        <img
                                            src="<?= htmlspecialchars($foto['file']) ?>"
                                            alt="<?= htmlspecialchars($foto['judul']) ?>"
                                            class="w-full h-full object-cover bg-black"
                                        >
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="image-placeholder w-full h-full flex flex-col items-center justify-center text-white">
                                        <i class="fa-solid <?= $foto['tipe'] === 'gambar' ? 'fa-image' : 'fa-video-slash' ?> text-5xl text-orange-400 opacity-80 mb-3"></i>
                                        <p class="text-sm font-bold">File <?= $index + 1 ?> Belum Tersedia</p>
                                        <p class="text-xs text-gray-300 mt-2 px-4 text-center">
                                            Pastikan file <strong><?= htmlspecialchars(basename($foto['file'])) ?></strong> berada di folder assets/.
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Overlay Gradien Gelap dari bawah ke atas -->
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-900/60 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300 pointer-events-none"></div>
                                
                                <!-- Badge di Pojok -->
                                <div class="absolute top-5 left-5 pointer-events-none">
                                    <span class="px-4 py-1.5 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] uppercase tracking-widest font-bold shadow-lg">
                                        <?= $foto['tipe'] === 'gambar' ? 'Dokumentasi Foto' : 'Video Dokumentasi' ?>
                                    </span>
                                </div>

                                <!-- Teks Sinematik di Bagian Bawah Gambar -->
                                <div class="absolute bottom-0 left-0 w-full p-6 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300 pointer-events-none">
                                    <h3 class="font-extrabold text-white text-xl mb-2 drop-shadow-md">
                                        <?= htmlspecialchars($foto['judul']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-300 leading-relaxed line-clamp-2 drop-shadow">
                                        <?= htmlspecialchars($foto['keterangan']) ?>
                                    </p>
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>
</main>


<!-- =========================================================
     MODAL ARTIKEL 
========================================================= -->
<div id="article-modal" class="hidden fixed inset-0 z-[100] bg-slate-950/80 backdrop-blur-md p-4 sm:p-6 transition-opacity">
    <div class="min-h-full flex items-center justify-center">
        
        <!-- Wadah Modal -->
        <div class="bg-white w-full max-w-3xl max-h-[90vh] rounded-[2rem] sm:rounded-[2.5rem] shadow-[0_30px_60px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col transform transition-transform duration-300 scale-95" id="modal-container">
            
            <!-- Header Modal (Sudah Diperkecil Spacenya) -->
            <div class="relative shrink-0 bg-gradient-to-br from-indigo-950 via-slate-900 to-blue-900 text-white px-6 sm:px-10 py-6 overflow-hidden">
                <!-- Ornamen Modal -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/20 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/20 rounded-full blur-[50px] pointer-events-none"></div>

                <button type="button" onclick="closeArticle()" class="absolute top-5 right-5 w-9 h-9 rounded-full bg-white/10 border border-white/20 hover:bg-red-500 hover:border-red-500 flex items-center justify-center transition-all duration-300 z-10">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>

                <div class="relative z-10 pr-8">
                    <span id="modal-category" class="inline-block px-3 py-1 bg-orange-500/20 border border-orange-500/40 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-widest text-orange-400 mb-2 shadow-sm"></span>
                    
                    <!-- Ukuran font judul diperkecil dari 4xl menjadi 3xl agar tidak memakan banyak baris -->
                    <h2 id="modal-title" class="text-2xl sm:text-3xl font-black leading-snug drop-shadow-lg text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-300"></h2>
                    
                    <p id="modal-date" class="text-xs sm:text-sm text-indigo-300 mt-2 font-medium flex items-center gap-2"></p>
                </div>
            </div>

            <!-- Konten Modal (Padding disesuaikan) -->
            <div id="modal-content" class="p-6 sm:p-8 overflow-y-auto text-gray-600 leading-relaxed text-base bg-gray-50/50 flex-grow">
                <!-- Diisi via JavaScript -->
            </div>

            <!-- Footer Modal (Padding diperkecil) -->
            <div class="shrink-0 bg-white border-t border-gray-100 px-6 sm:px-10 py-4 flex justify-between items-center">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider hidden sm:block">BOSS CCTV Bangka Belitung</p>
                <button type="button" onclick="closeArticle()" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gray-900 text-white font-bold hover:bg-indigo-600 shadow-lg hover:shadow-indigo-600/30 transition-all duration-300">
                    Tutup Artikel
                </button>
            </div>
            
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- JAVASCRIPT MODAL -->
<script>
const articles = <?= json_encode($artikel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const modalEl = document.getElementById('article-modal');
const modalContainer = document.getElementById('modal-container');

function openArticle(index) {
    const article = articles[index];

    document.getElementById('modal-category').textContent = article.kategori;
    document.getElementById('modal-title').textContent = article.judul;
    document.getElementById('modal-date').innerHTML = '<i class="fa-regular fa-clock"></i> Dipublikasikan pada: ' + article.tanggal;

    let html = '';
    article.isi.forEach((paragraph, i) => {
        html += '<p class="mb-6 text-gray-700 text-lg leading-relaxed">' + escapeHtml(paragraph) + '</p>';
        
        if (i === 0) {
            html += `
                <div class="my-10 p-8 rounded-3xl bg-white border border-indigo-100 shadow-[0_10px_40px_rgba(79,70,229,0.08)] relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-full blur-2xl transform translate-x-10 -translate-y-10"></div>
                    <div class="relative flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                        <div class="w-16 h-16 shrink-0 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-indigo-500/30">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-gray-900 text-xl mb-1">
                                Layanan Terpadu BOSS CCTV
                            </h4>
                            <p class="text-sm text-gray-500 leading-relaxed font-medium">
                                Penjualan produk CCTV original, instalasi profesional, konfigurasi jaringan, hingga perawatan berkala dalam satu atap dengan garansi resmi.
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }
    });

    document.getElementById('modal-content').innerHTML = html;

    // Animasi tampil
    modalEl.classList.remove('hidden');
    setTimeout(() => {
        modalContainer.classList.remove('scale-95');
        modalContainer.classList.add('scale-100');
    }, 10);
    
    document.body.classList.add('overflow-hidden');
}

function closeArticle() {
    modalContainer.classList.remove('scale-100');
    modalContainer.classList.add('scale-95');
    
    setTimeout(() => {
        modalEl.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 300);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

modalEl.addEventListener('click', function(e) {
    if (e.target === this || e.target.closest('.min-h-full') === e.target) {
        closeArticle();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !modalEl.classList.contains('hidden')) {
        closeArticle();
    }
});
</script>

</body>
</html>