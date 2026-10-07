<?php
session_start();
require_once 'koneksi.php';
include 'header.php';
?>

<!-- BACKGROUND UTAMA -->
<main class="bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 relative min-h-screen">
    
    <!-- Wadah Background Terisolasi & Pernak-pernik Shape -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Glow Cahaya Modern -->
        <div class="absolute top-10 right-1/4 w-[400px] h-[400px] bg-violet-500/20 rounded-full blur-[100px]"></div>
        <div class="absolute top-1/2 left-10 w-[350px] h-[350px] bg-blue-500/15 rounded-full blur-[90px]"></div>
        <div class="absolute bottom-0 right-10 w-[400px] h-[400px] bg-cyan-500/20 rounded-full blur-[100px]"></div>

        <!-- Watermark Ikon FontAwesome -->
        <div class="absolute inset-0 opacity-15 text-white">
            <i class="fa-solid fa-video absolute top-28 left-8 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-camera absolute top-36 right-12 text-7xl transform rotate-12"></i>
            <i class="fa-solid fa-shield-halved absolute bottom-16 left-16 text-7xl transform rotate-6"></i>
            <i class="fa-solid fa-network-wired absolute bottom-20 right-20 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-server absolute top-1/2 left-10 text-5xl transform rotate-45"></i>
            <i class="fa-solid fa-lock absolute top-1/2 right-10 text-5xl transform -rotate-45"></i>
            <i class="fa-solid fa-plug absolute bottom-32 left-24 text-4xl transform rotate-12"></i>
            <i class="fa-solid fa-eye absolute top-32 left-1/4 text-5xl transform -rotate-6"></i>
            <i class="fa-solid fa-wifi absolute top-1/3 right-1/4 text-5xl transform rotate-12"></i>
            <i class="fa-solid fa-hard-drive absolute bottom-1/3 right-12 text-6xl transform -rotate-12"></i>
            <i class="fa-solid fa-microchip absolute bottom-10 left-1/3 text-5xl transform rotate-45"></i>
            <i class="fa-solid fa-video absolute bottom-10 right-1/3 text-7xl transform rotate-12"></i>
        </div>
    </div>

    <!-- KONTEN UTAMA -->
    <section class="relative z-10">
        <!-- JARAK KE HEADER DISAMAKAN DENGAN produk.php (pt-12 md:pt-16 pb-16) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-12 md:pt-16 pb-16">
            
            <!-- =========================================================
                 BAGIAN 1: LAYANAN UNGGULAN
            ========================================================= -->
            <div class="text-center mb-16">
                <!-- BADGE DENGAN IKON -->
                <span class="inline-flex items-center gap-2 text-orange-300 font-semibold uppercase tracking-wider text-sm bg-orange-500/20 px-3 py-1 rounded-full border border-orange-500/30 shadow-sm">
                    <i class="fa-solid fa-handshake"></i> Komitmen Profesional
                </span>

                <h2 class="text-4xl md:text-5xl font-black text-white mt-4 drop-shadow-md">
                    Layanan Unggulan Kami
                </h2>

                <div class="w-24 h-1.5 bg-gradient-to-r from-blue-400 to-cyan-300 mx-auto mt-4 rounded-full"></div>

                <p class="mt-4 text-purple-100 max-w-2xl mx-auto text-lg">
                    Kami memberikan komitmen penuh untuk keamanan dan kepuasan Anda melalui standar kerja profesional.
                </p>
            </div>

            <!-- GRID LAYANAN -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-24">
                
                <!-- Card 1 -->
                <div class="bg-white/95 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-white/40 flex flex-col items-center text-center group">
                    <div class="mx-auto bg-gradient-to-br from-indigo-600 to-blue-700 text-white w-20 h-20 flex items-center justify-center rounded-2xl mb-6 text-3xl shadow-lg transform group-hover:rotate-6 transition duration-300">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-3">Garansi Resmi</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Semua produk dilengkapi garansi resmi pabrik hingga 2 tahun dengan prosedur klaim cepat dan penggantian unit baru jika cacat produksi.</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white/95 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-white/40 flex flex-col items-center text-center group">
                    <div class="mx-auto bg-gradient-to-br from-orange-500 to-amber-600 text-white w-20 h-20 flex items-center justify-center rounded-2xl mb-6 text-3xl shadow-lg transform group-hover:rotate-6 transition duration-300">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-3">Instalasi Profesional</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Layanan pemasangan langsung oleh teknisi bersertifikat. Hasil instalasi dijamin rapi, aman, estetis, dan siap digunakan (Plug & Play).</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white/95 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-white/40 flex flex-col items-center text-center group">
                    <div class="mx-auto bg-gradient-to-br from-teal-500 to-emerald-600 text-white w-20 h-20 flex items-center justify-center rounded-2xl mb-6 text-3xl shadow-lg transform group-hover:rotate-6 transition duration-300">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-3">Dukungan 24/7</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Tim after-sales kami selalu siaga membantu Anda kapan pun jika terjadi kendala teknis ataupun kebutuhan konsultasi pengaturan perangkat.</p>
                </div>

            </div>

            <!-- =========================================================
                 BAGIAN 2: FITUR FAQ
            ========================================================= -->
            <div id="area-faq" class="max-w-4xl mx-auto scroll-mt-24">
                <div class="text-center mb-12">
                    <!-- BADGE DENGAN IKON -->
                    <span class="inline-flex items-center gap-2 text-cyan-300 font-semibold uppercase tracking-wider text-sm bg-cyan-500/20 px-4 py-1.5 rounded-full border border-cyan-500/30 shadow-sm">
                        <i class="fa-solid fa-circle-question"></i> Pertanyaan Umum
                    </span>
                    <h3 class="text-3xl md:text-4xl font-black text-white mt-4 drop-shadow-md">
                        Frequently Asked Questions (FAQ)
                    </h3>
                    <p class="mt-3 text-purple-100 max-w-lg mx-auto">
                        Temukan jawaban cepat seputar produk, garansi, dan prosedur pemasangan sistem keamanan kami.
                    </p>
                </div>

                <!-- Accordion Container -->
                <div class="space-y-4">
                    
                    <!-- FAQ Item 1 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl border border-white/40 shadow-xl overflow-hidden transition-all duration-300 hover:border-indigo-300">
                        <button onclick="toggleFaq(1)" class="w-full p-6 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <div class="flex items-center gap-3">
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Pemesanan</span>
                                <span class="font-bold text-gray-900 text-base md:text-lg">Apakah harga produk sudah termasuk jasa instalasi dan pemasangan?</span>
                            </div>
                            <i id="faq-icon-1" class="fa-solid fa-chevron-down text-indigo-600 transition-transform duration-300 flex-shrink-0"></i>
                        </button>
                        <div id="faq-content-1" class="hidden px-6 pb-6 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                            Beberapa paket khusus CCTV sudah mencakup gratis jasa instalasi standar. Namun, untuk pembelian satuan atau kustomisasi jalur kabel yang panjang, biaya instalasi dapat dikonsultasikan terlebih dahulu dengan tim teknisi kami secara gratis.
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl border border-white/40 shadow-xl overflow-hidden transition-all duration-300 hover:border-indigo-300">
                        <button onclick="toggleFaq(2)" class="w-full p-6 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <div class="flex items-center gap-3">
                                <span class="bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Garansi</span>
                                <span class="font-bold text-gray-900 text-base md:text-lg">Berapa lama masa garansi produk dan layanan yang diberikan?</span>
                            </div>
                            <i id="faq-icon-2" class="fa-solid fa-chevron-down text-indigo-600 transition-transform duration-300 flex-shrink-0"></i>
                        </button>
                        <div id="faq-content-2" class="hidden px-6 pb-6 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                            Semua perangkat CCTV dan sistem keamanan utama kami dilengkapi garansi resmi pabrik mulai dari 1 hingga 2 tahun. Selain itu, kami juga memberikan garansi servis pemasangan untuk memastikan instalasi tetap optimal.
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl border border-white/40 shadow-xl overflow-hidden transition-all duration-300 hover:border-indigo-300">
                        <button onclick="toggleFaq(3)" class="w-full p-6 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <div class="flex items-center gap-3">
                                <span class="bg-teal-100 text-teal-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Teknis</span>
                                <span class="font-bold text-gray-900 text-base md:text-lg">Apakah kamera CCTV dapat dipantau secara online lewat HP / Smartphone?</span>
                            </div>
                            <i id="faq-icon-3" class="fa-solid fa-chevron-down text-indigo-600 transition-transform duration-300 flex-shrink-0"></i>
                        </button>
                        <div id="faq-content-3" class="hidden px-6 pb-6 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                            Ya, tentu saja! Seluruh sistem CCTV modern yang kami pasang mendukung pemantauan jarak jauh secara *real-time* melalui aplikasi di smartphone (Android/iOS) maupun komputer Anda kapan pun dan di mana pun.
                        </div>
                    </div>

                    <!-- FAQ Item 4 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl border border-white/40 shadow-xl overflow-hidden transition-all duration-300 hover:border-indigo-300">
                        <button onclick="toggleFaq(4)" class="w-full p-6 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <div class="flex items-center gap-3">
                                <span class="bg-purple-100 text-purple-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Layanan</span>
                                <span class="font-bold text-gray-900 text-base md:text-lg">Bagaimana cara berkonsultasi mengenai kebutuhan layout keamanan rumah atau kantor?</span>
                            </div>
                            <i id="faq-icon-4" class="fa-solid fa-chevron-down text-indigo-600 transition-transform duration-300 flex-shrink-0"></i>
                        </button>
                        <div id="faq-content-4" class="hidden px-6 pb-6 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                            Anda dapat langsung menghubungi tim kami melalui tombol WhatsApp yang tersedia di website ini. Tim teknisi kami siap memberikan rekomendasi jumlah titik kamera serta spesifikasi terbaik sesuai dengan tata letak bangunan Anda secara gratis.
                        </div>
                    </div>

                    <!-- FAQ Item 5 -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl border border-white/40 shadow-xl overflow-hidden transition-all duration-300 hover:border-indigo-300">
                        <button onclick="toggleFaq(5)" class="w-full p-6 text-left flex justify-between items-center gap-4 focus:outline-none">
                            <div class="flex items-center gap-3">
                                <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Cakupan</span>
                                <span class="font-bold text-gray-900 text-base md:text-lg">Wilayah mana saja yang mencakup layanan instalasi langsung di tempat?</span>
                            </div>
                            <i id="faq-icon-5" class="fa-solid fa-chevron-down text-indigo-600 transition-transform duration-300 flex-shrink-0"></i>
                        </button>
                        <div id="faq-content-5" class="hidden px-6 pb-6 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4">
                            Kami melayani survei, pengiriman, dan instalasi langsung ke seluruh wilayah di Kepulauan Bangka Belitung.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

</main>

<!-- =========================================================
     JAVASCRIPT UNTUK ACCORDION FAQ
========================================================= -->
<script>
function toggleFaq(id) {
    const content = document.getElementById('faq-content-' + id);
    const icon = document.getElementById('faq-icon-' + id);
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<?php include 'footer.php'; ?>