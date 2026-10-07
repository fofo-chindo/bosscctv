<?php
// Pastikan session dimulai jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mendapatkan nama file halaman yang sedang diakses saat ini
$current_page = basename($_SERVER['PHP_SELF']);

// Menghitung total item di keranjang
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += (int)$qty;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOSS CCTV - Pakar Keamanan Bangka Belitung</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Konfigurasi Tailwind Kustom -->
    <script src="tailwind-config.js"></script>
    
    <!-- Custom CSS (Opsional) -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 flex flex-col min-h-screen font-sans">

<!-- ======================================================
     NAVBAR / HEADER (Dark Premium Glassmorphism)
====================================================== -->
<header class="bg-gradient-to-r from-slate-950 via-[#0f172a] to-blue-950/95 backdrop-blur-xl shadow-lg border-b border-white/10 sticky top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            
            <!-- LOGO -->
            <a href="index.php" class="flex items-center gap-2 md:gap-3 shrink-0 group">
                <!-- Gambar Logo Baru GSE -->
                <img src="assets/logo-gse2.png" alt="GSE Logo" class="h-14 md:h-12 w-auto object-contain scale-110 -translate-y-0 drop-shadow-sm ml-1 transition-transform duration-500 group-hover:scale-[1.35]">
                
                <!-- Teks Logo -->
                <div class="flex flex-col justify-center ml-1 md:ml-0">
                    <span class="font-extrabold text-white text-lg md:text-xl leading-none tracking-tight drop-shadow-sm">
                        BOSS CCTV
                    </span>
                    <span class="text-[10px] md:text-xs text-orange-400 font-bold tracking-widest mt-1 uppercase">
                        Bangka Belitung
                    </span>
                </div>
            </a>

            <!-- DESKTOP NAVIGATION -->
            <!-- Penambahan class 'block' dan 'whitespace-nowrap' agar tinggi dan garis bawahnya sejajar presisi -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="index.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= $current_page == 'index.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Beranda
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= $current_page == 'index.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>

                <a href="kategori.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= $current_page == 'kategori.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Kategori
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= $current_page == 'kategori.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>
                
                <a href="produk.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= $current_page == 'produk.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Paket CCTV
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= $current_page == 'produk.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>
                
                <a href="layanan.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= $current_page == 'layanan.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Layanan
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= $current_page == 'layanan.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>
                
                <a href="tentang.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= $current_page == 'tentang.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Tentang Kami
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= $current_page == 'tentang.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>

                <a href="artikel.php" class="relative block font-semibold transition-colors group py-2 tracking-wide whitespace-nowrap <?= strtolower($current_page) == 'artikel.php' ? 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.8)]' : 'text-gray-400 hover:text-white' ?>">
                    Artikel
                    <span class="absolute bottom-0 left-0 h-[3px] bg-gradient-to-r from-orange-500 to-yellow-400 transition-all duration-300 rounded-full shadow-[0_0_5px_rgba(249,115,22,0.5)] <?= strtolower($current_page) == 'artikel.php' ? 'w-full' : 'w-0 group-hover:w-full' ?>"></span>
                </a>
            </nav>

            <!-- RIGHT SECTION (Cart, Login, Mobile Toggle) -->
            <div class="flex items-center gap-3 md:gap-5">
                
                <!-- Cart Icon -->
                <a href="keranjang.php" class="relative flex items-center justify-center w-10 h-10 md:w-11 md:h-11 rounded-full bg-white/5 border border-white/10 text-gray-300 hover:bg-white/25 hover:text-white transition-all duration-300 group <?= $current_page == 'keranjang.php' ? 'ring-2 ring-orange-500 bg-white/20 text-white' : '' ?>">
                    <i class="fa-solid fa-cart-shopping text-lg md:text-xl"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-br from-orange-500 to-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center shadow-lg ring-2 ring-slate-900">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- User Profile / Login Button (Desktop Only) -->
                <div class="hidden md:block">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'owner'])): ?>
                            <a href="admin_dashboard.php" class="flex items-center gap-2 bg-blue-500/20 border border-blue-500/50 text-blue-300 hover:bg-blue-600 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all shadow-sm">
                                <i class="fa-solid fa-gauge"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a href="logout.php" class="flex items-center gap-2 bg-red-500/20 border border-red-500/50 text-red-300 hover:bg-red-600 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all shadow-sm" onclick="return confirm('Yakin ingin keluar?');">
                                <i class="fa-solid fa-right-from-bracket"></i> Keluar
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 border border-blue-400/50 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                            <i class="fa-regular fa-user"></i> Masuk
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Hamburger Button -->
                <button id="mobile-menu-btn" class="md:hidden flex items-center justify-center w-10 h-10 rounded-full bg-white/5 border border-white/10 text-gray-300 hover:bg-white/25 hover:text-white focus:outline-none transition-all">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- MOBILE MENU DROPDOWN -->
    <div id="mobile-menu" style="display: none;" class="md:hidden bg-slate-950/95 backdrop-blur-2xl text-slate-100 border-t border-white/10 w-full shadow-[0_20px_30px_rgba(0,0,0,0.5)]">
        <div class="px-5 py-5 space-y-2.5">
            
            <a href="index.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= $current_page == 'index.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-house w-5 text-center text-orange-400"></i> Beranda</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>

            <a href="kategori.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= $current_page == 'kategori.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-tags w-5 text-center text-orange-400"></i> Kategori</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>
            
            <a href="produk.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= $current_page == 'produk.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-box-open w-5 text-center text-orange-400"></i> Produk Unggulan</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>
            
            <a href="layanan.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= $current_page == 'layanan.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-screwdriver-wrench w-5 text-center text-orange-400"></i> Layanan</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>
            
            <a href="tentang.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= $current_page == 'tentang.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-circle-info w-5 text-center text-orange-400"></i> Tentang Kami</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>

            <a href="artikel.php" class="flex items-center justify-between px-4 py-3.5 rounded-xl text-base font-semibold transition-all <?= strtolower($current_page) == 'artikel.php' ? 'bg-gradient-to-r from-orange-500/20 to-yellow-500/10 text-orange-400 border border-orange-500/30 shadow-md' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="flex items-center gap-3"><i class="fa-solid fa-newspaper w-5 text-center text-orange-400"></i> Artikel</span>
                <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
            </a>
            
            <!-- Auth / Akun untuk Mobile -->
            <div class="border-t border-white/10 pt-4 mt-3 pb-1">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'owner'])): ?>
                        <a href="admin_dashboard.php" class="flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl text-base font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg transition-all mb-2.5">
                            <i class="fa-solid fa-gauge"></i> Dashboard Admin
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl text-base font-bold text-red-400 bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 transition-all" onclick="return confirm('Yakin ingin keluar?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </a>
                <?php else: ?>
                    <a href="login.php" class="flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl text-base font-bold text-white bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 shadow-[0_0_20px_rgba(249,115,22,0.4)] transition-all">
                        <i class="fa-regular fa-user"></i> Masuk / Daftar Akun
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</header>

<!-- JavaScript untuk Toggle Menu Mobile dengan Inline Style Langsung -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btn = document.getElementById("mobile-menu-btn");
        const menu = document.getElementById("mobile-menu");
        const icon = btn.querySelector("i");

        btn.addEventListener("click", () => {
            if (menu.style.display === "block") {
                menu.style.display = "none";
                icon.classList.remove("fa-xmark");
                icon.classList.add("fa-bars");
            } else {
                menu.style.display = "block";
                icon.classList.remove("fa-bars");
                icon.classList.add("fa-xmark");
            }
        });
    });
</script>
<!-- Header selesai. Konten utama berada di bawah ini. -->