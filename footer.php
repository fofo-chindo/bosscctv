<!-- =========================================================
     FOOTER UTAMA (CV. Glory Securitech Electrindo / BOSS CCTV)
========================================================= -->
<footer class="bg-dark text-gray-300 py-12 border-t border-gray-800 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Brand Info -->
            <div>
                <div class="flex flex-col mb-4">
                    <span class="font-extrabold text-xl tracking-tight text-white leading-none">CV. GLORY</span>
                    <span class="text-[10px] font-bold text-primary tracking-widest uppercase">Securitech Electrindo</span>
                </div>
                <p class="text-sm text-gray-400 mb-4">Solusi sistem keamanan dan CCTV terpercaya di Bangka Belitung.</p>
                <div class="flex space-x-3 items-center">
                    <!-- Instagram Link -->
                    <a href="https://www.instagram.com/819bosscctv/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-300 hover:bg-white/20 hover:text-white transition-all duration-300" title="Instagram BOSS CCTV">
                        <i class="fa-brands fa-instagram text-lg"></i>
                    </a>
                    <!-- Shopee Link (Ikon Keranjang Belanja dengan Huruf S di Tengah) -->
                    <a href="https://id.shp.ee/nqizjFX8" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-300 hover:bg-orange-500 hover:border-orange-500 hover:text-white transition-all duration-300 relative group" title="Shopee BOSS CCTV">
                        <i class="fa-solid fa-bag-shopping text-base"></i>
                        <span class="absolute text-[10px] font-black mt-0.5">S</span>
                    </a>
                </div>
            </div>

            <!-- Perusahaan -->
            <div>
                <h4 class="text-white font-bold mb-4 uppercase text-sm tracking-wider">Perusahaan</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="tentang.php" class="hover:text-primary transition-colors">Tentang Kami</a></li>
                    <li><a href="#" onclick="openFooterModal('syarat')" class="hover:text-primary transition-colors">Syarat & Ketentuan</a></li>
                    <li><a href="#" onclick="openFooterModal('privasi')" class="hover:text-primary transition-colors">Kebijakan Privasi</a></li>
                    <li><a href="artikel.php" class="hover:text-primary transition-colors">Artikel</a></li>
                </ul>
            </div>

            <!-- Layanan Pelanggan -->
            <div>
                <h4 class="text-white font-bold mb-4 uppercase text-sm tracking-wider">Layanan Pelanggan</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" onclick="openFooterModal('pemesanan')" class="hover:text-primary transition-colors">Cara Pemesanan</a></li>
                    <li><a href="#" onclick="openFooterModal('garansi')" class="hover:text-primary transition-colors">Klaim Garansi</a></li>
                    <li><a href="#" onclick="openFooterModal('jadwal')" class="hover:text-primary transition-colors">Jadwal Instalasi</a></li>
                    <li><a href="layanan.php#area-faq" class="hover:text-primary transition-colors">FAQ</a></li>
                </ul>
            </div>

            <!-- Kontak & Nomor Tim -->
            <div>
                <h4 class="text-white font-bold mb-4 uppercase text-sm tracking-wider">Hubungi Kami</h4>
                <ul class="space-y-3 text-sm mb-6">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-location-dot mt-1 text-primary"></i>
                        <span>Jl. Kerabut 2, Selindung, Kec. Gabek, Kota Pangkal Pinang, Kepulauan Bangka Belitung 33172</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-phone text-primary w-4 text-center"></i>
                        <a href="https://wa.me/6281377766667" target="_blank" class="hover:text-white transition-colors">
                            0813-7776-6667 <strong class="text-orange-400 font-semibold">(Direktur)</strong>
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-phone text-primary w-4 text-center"></i>
                        <a href="https://wa.me/6282179999571" target="_blank" class="hover:text-white transition-colors">
                            0821-7999-9571 <strong class="text-blue-400 font-semibold">(Admin 1)</strong>
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fa-solid fa-phone text-primary w-4 text-center"></i>
                        <a href="https://wa.me/6281939090990" target="_blank" class="hover:text-white transition-colors">
                            0819-3909-0990 <strong class="text-blue-400 font-semibold">(Admin 2)</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-800 mt-10 pt-8 text-center text-sm text-gray-500">
            &copy; 2026 CV. Glory Securitech Electrindo (BOSS CCTV) Bangka Belitung. Hak Cipta Dilindungi.
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/6281377766667" target="_blank" class="fixed bottom-6 right-6 bg-green-500 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-2xl hover:bg-green-600 hover:scale-110 transition-all duration-300 z-50 group">
    <i class="fa-brands fa-whatsapp text-3xl"></i>
    <span class="absolute right-16 bg-white text-gray-800 text-sm font-semibold py-1 px-3 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
        Hubungi Boss CCTV
    </span>
</a>

<!-- =========================================================
     MODAL POPUP UNTUK INFORMASI FOOTER
========================================================= -->
<div id="footer-modal" class="hidden fixed inset-0 z-[999] bg-slate-950/80 backdrop-blur-md p-4 flex items-center justify-center transition-opacity">
    <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden transform transition-transform scale-95" id="footer-modal-container">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white px-6 py-5 flex justify-between items-center">
            <h3 id="footer-modal-title" class="font-extrabold text-lg flex items-center gap-2"></h3>
            <button onclick="closeFooterModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-red-500 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="footer-modal-body" class="p-6 text-gray-600 text-sm leading-relaxed max-h-[65vh] overflow-y-auto">
            <!-- Isi Konten Dinamis -->
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end">
            <button onclick="closeFooterModal()" class="px-5 py-2 rounded-xl bg-gray-900 text-white text-xs font-bold hover:bg-indigo-600 transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- SCRIPT MODAL FOOTER & KONTEN DINAMIS -->
<script>
const footerContentData = {
    'syarat': {
        title: '<i class="fa-solid fa-file-contract text-orange-400"></i> Syarat & Ketentuan',
        body: `
            <div class="space-y-4">
                <p class="font-semibold text-gray-800">Selamat datang di layanan resmi CV. Glory Securitech Electrindo (BOSS CCTV). Berikut adalah ketentuan layanan kami:</p>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                    <div class="flex gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 font-bold text-xs flex items-center justify-center">1</span>
                        <p><strong>Keaslian Produk:</strong> Seluruh perangkat CCTV dan aksesoris yang kami jual dijamin 100% original dan bergaransi resmi.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 font-bold text-xs flex items-center justify-center">2</span>
                        <p><strong>Prosedur Pembelian:</strong> Transaksi dapat dilakukan secara langsung melalui toko fisik, platform e-commerce resmi, maupun pemesanan via WhatsApp admin.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 font-bold text-xs flex items-center justify-center">3</span>
                        <p><strong>Ketentuan Pemasangan:</strong> Biaya dan estimasi waktu pengerjaan disesuaikan dengan paket atau tingkat kerumitan jalur kabel di lokasi.</p>
                    </div>
                </div>
            </div>
        `
    },
    'privasi': {
        title: '<i class="fa-solid fa-shield-halved text-blue-400"></i> Kebijakan Privasi',
        body: `
            <div class="space-y-4">
                <p class="font-semibold text-gray-800">Kami sangat menghargai dan melindungi kerahasiaan data pribadi Anda:</p>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                    <p><i class="fa-solid fa-circle-check text-green-500 mr-2"></i>Data pribadi seperti nama, nomor telepon, dan alamat lokasi pemasangan hanya digunakan untuk keperluan transaksi, pengiriman, serta instalasi perangkat.</p>
                    <p><i class="fa-solid fa-circle-check text-green-500 mr-2"></i>Kami tidak pernah memperjualbelikan atau membagikan informasi rahasia pelanggan kepada pihak ketiga tanpa izin.</p>
                    <p><i class="fa-solid fa-circle-check text-green-500 mr-2"></i>Pengaturan akses rekaman CCTV sepenuhnya berada di tangan pemilik properti setelah instalasi selesai.</p>
                </div>
            </div>
        `
    },
    'pemesanan': {
        title: '<i class="fa-solid fa-cart-shopping text-emerald-400"></i> Cara Pemesanan',
        body: `
            <div class="space-y-3">
                <p class="font-semibold text-gray-800">Pesan sistem keamanan Anda dengan mudah melalui langkah berikut:</p>
                <div class="space-y-2">
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-emerald-500 text-white font-bold text-xs flex items-center justify-center">1</span>
                        <div><strong>Pilih Produk:</strong> Tentukan pilihan kamera atau paket CCTV melalui halaman katalog produk kami.</div>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-emerald-500 text-white font-bold text-xs flex items-center justify-center">2</span>
                        <div><strong>Konsultasi & Keranjang:</strong> Masukkan produk ke keranjang belanja atau klik langsung tombol WhatsApp admin untuk konsultasi letak titik kamera.</div>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-emerald-500 text-white font-bold text-xs flex items-center justify-center">3</span>
                        <div><strong>Konfirmasi & Jadwal:</strong> Lengkapi alamat tujuan pengiriman/pemasangan, lalu lakukan konfirmasi pesanan kepada tim admin kami.</div>
                    </div>
                </div>
            </div>
        `
    },
    'garansi': {
        title: '<i class="fa-solid fa-award text-purple-400"></i> Klaim Garansi',
        body: `
            <div class="space-y-4">
                <p class="font-semibold text-gray-800">Nikmati ketenangan dengan jaminan garansi produk resmi:</p>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                    <div class="flex gap-3">
                        <i class="fa-solid fa-clock text-indigo-500 mt-1"></i>
                        <p><strong>Masa Berlaku:</strong> Garansi perangkat keras berlaku mulai dari 1 hingga 2 tahun sesuai ketentuan masing-masing pabrik produsen.</p>
                    </div>
                    <div class="flex gap-3">
                        <i class="fa-solid fa-file-invoice text-indigo-500 mt-1"></i>
                        <p><strong>Syarat Klaim:</strong> Menunjukkan nota pembelian atau catatan riwayat instalasi resmi dari BOSS CCTV.</p>
                    </div>
                    <div class="flex gap-3">
                        <i class="fa-solid fa-screwdriver-wrench text-indigo-500 mt-1"></i>
                        <p><strong>Cakupan:</strong> Kerusakan murni dari pabrik (bukan akibat kelalaian pengguna, lonjakan petir ekstrem, atau terkena air).</p>
                    </div>
                </div>
            </div>
        `
    },
    'jadwal': {
        title: '<i class="fa-solid fa-calendar-days text-blue-400"></i> Jadwal Instalasi',
        body: `
            <div class="space-y-4">
                <p class="font-semibold text-gray-800">Jam operasional layanan teknis dan survei lapangan kami:</p>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                        <span class="font-medium text-gray-700"><i class="fa-regular fa-calendar mr-2 text-indigo-500"></i> Senin s.d. Sabtu</span>
                        <span class="font-bold text-indigo-600">08.00 - 17.00 WIB</span>
                    </div>
                    <div class="flex items-center justify-between pb-2">
                        <span class="font-medium text-gray-700"><i class="fa-regular fa-calendar mr-2 text-red-400"></i> Minggu / Hari Libur</span>
                        <span class="font-bold text-orange-500">Berdasarkan Perjanjian / Janji Temu</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 italic">* Jadwal survei lokasi atau pemasangan mendadak dapat dikonfirmasikan terlebih dahulu melalui kontak WhatsApp admin.</p>
            </div>
        `
    }
};

function openFooterModal(key) {
    event.preventDefault();
    const data = footerContentData[key];
    if (!data) return;

    document.getElementById('footer-modal-title').innerHTML = data.title;
    document.getElementById('footer-modal-body').innerHTML = data.body;
    
    const modal = document.getElementById('footer-modal');
    const container = document.getElementById('footer-modal-container');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
    }, 10);
    document.body.classList.add('overflow-hidden');
}

function closeFooterModal() {
    const modal = document.getElementById('footer-modal');
    const container = document.getElementById('footer-modal-container');
    
    container.classList.remove('scale-100');
    container.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 200);
}

document.getElementById('footer-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFooterModal();
    }
});
</script>

<!-- Custom Script -->
<script src="script.js"></script>
</body>
</html>