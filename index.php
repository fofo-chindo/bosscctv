<?php
session_start();
require_once 'koneksi.php';
include 'header.php';
?>

<!-- BACKGROUND UTAMA -->
<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative min-h-screen flex flex-col justify-between overflow-hidden">
    
    <!-- Wadah Background Terisolasi & Pernak-pernik Shape -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Glow Cahaya Modern -->
        <div class="absolute top-10 right-1/4 w-[450px] h-[450px] bg-violet-500/25 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute top-1/3 left-10 w-[400px] h-[400px] bg-blue-500/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-10 right-10 w-[450px] h-[450px] bg-cyan-500/20 rounded-full blur-[120px]"></div>

        <!-- Watermark Ikon FontAwesome -->
        <div class="absolute inset-0 opacity-15 text-white">
            <i class="fa-solid fa-video absolute top-20 left-10 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-camera absolute top-32 right-16 text-7xl transform rotate-12"></i>
            <i class="fa-solid fa-shield-halved absolute top-1/2 left-16 text-7xl transform rotate-6"></i>
            <i class="fa-solid fa-network-wired absolute top-1/2 right-20 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-server absolute bottom-32 left-20 text-5xl transform rotate-45"></i>
            <i class="fa-solid fa-lock absolute bottom-32 right-24 text-5xl transform -rotate-45"></i>
            <i class="fa-solid fa-plug absolute top-1/4 left-1/3 text-4xl transform rotate-12"></i>
            <i class="fa-solid fa-eye absolute bottom-1/4 right-1/3 text-5xl transform -rotate-6"></i>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-16 lg:py-24 flex flex-col lg:flex-row items-center flex-grow gap-12">
        <div class="lg:w-1/2">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-500/20 border border-orange-500/40 text-orange-300 font-bold tracking-widest text-xs uppercase mb-6 backdrop-blur-md shadow-lg">
                <i class="fa-solid fa-shield-halved"></i> Pakar Keamanan & Jaringan Bangka Belitung
            </span>

            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight mb-6 leading-tight drop-shadow-md">
                Keamanan Maksimal, <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-teal-300">Pikiran Tenang.</span>
            </h1>

            <p class="text-purple-100 max-w-xl text-base md:text-lg leading-relaxed font-light mb-8">
                Pusat penjualan, instalasi sistem keamanan CCTV, dan jaringan terlengkap di Bangka Belitung. Dapatkan layanan profesional bergaransi resmi.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 mb-12">
                <a href="kategori.php"
                   class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold py-4 px-8 rounded-2xl shadow-xl text-center transition duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1">
                    <i class="fa-solid fa-store text-lg"></i> Jelajahi Produk
                </a>

                <a href="https://wa.me/6281377766667"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="bg-white/10 hover:bg-white/20 border border-white/30 text-white font-bold py-4 px-8 rounded-2xl shadow-xl text-center backdrop-blur-md transition duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1">
                    <i class="fa-brands fa-whatsapp text-2xl text-green-400"></i>
                    Konsultasi Gratis
                </a>
            </div>

            <!-- Statistik Singkat -->
            <div class="grid grid-cols-3 gap-6 pt-6 border-t border-white/10">
                <div>
                    <h3 class="text-2xl font-black text-white">100%</h3>
                    <p class="text-xs text-purple-200 mt-1">Produk Original</p>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-white">2 Tahun</h3>
                    <p class="text-xs text-purple-200 mt-1">Garansi Resmi</p>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-white">24/7</h3>
                    <p class="text-xs text-purple-200 mt-1">Dukungan Teknisi</p>
                </div>
            </div>
        </div>

        <div class="lg:w-1/2 flex justify-center lg:justify-end relative w-full">
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-blue-500/30 rounded-full blur-3xl pointer-events-none animate-pulse"></div>

            <div class="relative z-10 bg-white/10 backdrop-blur-2xl p-4 rounded-[2.5rem] border border-white/20 shadow-2xl max-w-md w-full transform hover:scale-[1.02] transition duration-500">
                <!-- GAMBAR TELAH DIUBAH DI SINI -->
                <img
                    src="assets/beranda.png"
                    alt="Solusi Keamanan Modern CCTV"
                    class="rounded-[2rem] shadow-lg w-full object-cover"
                >
                <div class="mt-4 px-4 pb-2 text-center">
                    <p class="text-xs font-semibold text-cyan-300 tracking-wider uppercase">CV. Glory Securitech Electrindo</p>
                    <p class="text-sm font-bold text-white mt-1">Solusi Terbaik Perlindungan Properti Anda</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>