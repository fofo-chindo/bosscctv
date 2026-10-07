<?php
session_start();
require_once 'koneksi.php';
include 'header.php';
?>

<!-- BACKGROUND UTAMA -->
<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative min-h-screen">
    
    <!-- Wadah Background Terisolasi & Pernak-pernik Shape -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-10 right-1/4 w-[400px] h-[400px] bg-violet-500/20 rounded-full blur-[100px]"></div>
        <div class="absolute top-1/2 left-10 w-[350px] h-[350px] bg-blue-500/15 rounded-full blur-[90px]"></div>
        <div class="absolute bottom-0 right-10 w-[400px] h-[400px] bg-cyan-500/20 rounded-full blur-[100px]"></div>

        <div class="absolute inset-0 opacity-15 text-white">
            <i class="fa-solid fa-video absolute top-28 left-8 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-camera absolute top-36 right-12 text-7xl transform rotate-12"></i>
            <i class="fa-solid fa-shield-halved absolute bottom-16 left-16 text-7xl transform rotate-6"></i>
            <i class="fa-solid fa-network-wired absolute bottom-20 right-20 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-server absolute top-1/2 left-10 text-5xl transform rotate-45"></i>
            <i class="fa-solid fa-lock absolute top-1/2 right-10 text-5xl transform -rotate-45"></i>
            <i class="fa-solid fa-plug absolute bottom-32 left-24 text-4xl transform rotate-12"></i>
            <i class="fa-solid fa-eye absolute top-32 left-1/4 text-5xl transform -rotate-6"></i>
        </div>
    </div>

    <!-- KONTEN TENTANG KAMI -->
    <section class="relative z-10">
        
        <!-- =====================================================
             AREA JUDUL TENTANG KAMI (Disamakan dengan produk.php)
        ====================================================== -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 md:pt-16 pb-8 text-center">
            
            <!-- BADGE ORANYE -->
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/20 border border-orange-500/40 text-orange-300 font-bold tracking-widest text-xs uppercase mb-3 backdrop-blur-md shadow-sm">
                <i class="fa-solid fa-building"></i> Profil Perusahaan
            </span>

            <!-- JUDUL -->
            <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-3 drop-shadow-md">
                Tentang Kami
            </h1>
            
            <!-- DESKRIPSI -->
            <p class="text-purple-100 max-w-xl mx-auto text-sm md:text-base leading-relaxed font-light">
                Mengenal lebih dekat perjalanan dan komitmen CV. Glory Securitech Electrindo.
            </p>
            
        </div>

        <!-- =====================================================
             KONTEN BAWAH (Ruko & PDF)
        ====================================================== -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            
            <!-- CARD PUTIH PROFIL -->
            <div class="bg-white/95 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl p-8 md:p-14 border border-white/40 mb-16">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl transform -rotate-2 opacity-20 blur-sm z-0"></div>
                        <!-- GAMBAR SHOWROOM -->
                        <img src="assets/ruko.png" alt="Showroom dan Armada Boss CCTV" class="rounded-3xl shadow-xl relative z-10 w-full object-cover h-[400px]">
                    </div>
                    <div>
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-widest bg-blue-50 px-3 py-1 rounded-full">Sejak Berdiri</span>
                        <h3 class="text-3xl font-black text-gray-900 mt-3 mb-4">Pakar Keamanan Terpercaya di Bangka Belitung</h3>
                        <p class="text-gray-600 mb-4 leading-relaxed">
                            Berawal dari komitmen untuk menciptakan lingkungan yang aman dan nyaman, <strong>CV. Glory Securitech Electrindo (BOSS CCTV)</strong> hadir sebagai perusahaan terdepan yang berfokus pada penyediaan sistem keamanan (CCTV) dan jaringan terpadu.
                        </p>
                        <p class="text-gray-600 mb-8 leading-relaxed">
                            Kami melayani berbagai segmen pelanggan mulai dari perumahan pribadi, area komersial, instansi perkantoran, hingga kawasan industri di seluruh wilayah Kepulauan Bangka Belitung.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-700 shadow-sm">
                                    <i class="fa-solid fa-check text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg">100% Produk Original</h4>
                                    <p class="text-sm text-gray-500">Hanya menyediakan perangkat resmi berstandar internasional.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center flex-shrink-0 text-indigo-700 shadow-sm">
                                    <i class="fa-solid fa-medal text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg">Teknisi Tersertifikasi</h4>
                                    <p class="text-sm text-gray-500">Pemasangan ditangani langsung oleh tim ahli berpengalaman.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SEKSI PENAMPIL PDF COMPANY PROFILE -->
            <div class="border-t border-white/20 pt-16">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold text-white">Company Profile</h2>
                    <p class="mt-4 text-purple-200 max-w-2xl mx-auto">Kenali lebih dekat profil perusahaan, portofolio instalasi, dan detail layanan CV. Glory Securitech Electrindo (BOSS CCTV).</p>
                </div>

                <div class="bg-white/95 backdrop-blur-xl p-4 md:p-6 rounded-[2.5rem] border border-white/40 shadow-2xl max-w-5xl mx-auto">
                    <!-- Tinggi iframe diatur -->
                    <div class="w-full h-[500px] md:h-[1350px] rounded-2xl overflow-hidden shadow-inner border border-gray-200 bg-gray-50">
                        <iframe src="assets/Profil_GSE.pdf#toolbar=0&navpanes=0&view=FitH" type="application/pdf" width="100%" height="100%" class="w-full h-full border-none block">
                            <p class="text-center p-10 text-gray-600">
                                Browser Anda tidak mendukung pratinjau PDF bawaan. 
                                <br><br>
                                <a href="assets/Profil_GSE.pdf" class="text-indigo-600 font-bold underline hover:text-indigo-800">
                                    Klik di sini untuk mengunduh PDF Company Profile
                                </a>
                            </p>
                        </iframe>
                    </div>
                </div>
            </div>

        </div>
    </section>

</main>

<?php include 'footer.php'; ?>