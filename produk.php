    <?php
    session_start();
    require_once 'koneksi.php';
    include 'header.php';

    /* =========================================================
    TANGKAP PARAMETER FILTER & PAGINASI
    ========================================================= */
    $merekAktif = $_GET['merek'] ?? 'semua';
    $merekId = ($merekAktif !== 'semua') ? (int)$merekAktif : 0;
    $kategoriAktif = $_GET['kategori'] ?? 'semua';

    $batas_produk = 12; 
    $halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
    if ($halaman_aktif < 1) $halaman_aktif = 1;
    $offset = ($halaman_aktif - 1) * $batas_produk;

    $kategoriId = 0;
    if ($kategoriAktif !== 'semua') {
        $stmtKategori = $koneksi->prepare("SELECT id, nama_kategori FROM categories WHERE slug = ? LIMIT 1");
        $stmtKategori->bind_param("s", $kategoriAktif);
        $stmtKategori->execute();
        $resKategori = $stmtKategori->get_result();
        if ($rowKat = $resKategori->fetch_assoc()) {
            $kategoriId = (int)$rowKat['id'];
            $namaKategoriAktif = $rowKat['nama_kategori'];
        }
        $stmtKategori->close();
    }

    $query_merek = $koneksi->query("SELECT id, nama_merek FROM brands WHERE is_active = 1 ORDER BY nama_merek ASC");
    $query_kategori = $koneksi->query("SELECT id, nama_kategori, slug FROM categories ORDER BY nama_kategori ASC");

    $whereClause = "WHERE p.is_active = 1 AND p.is_featured = 1";
    $types = "";
    $params = [];

    if ($merekId > 0) {
        $whereClause .= " AND p.brand_id = ?";
        $types .= "i";
        $params[] = $merekId;
    }

    if ($kategoriId > 0) {
        $whereClause .= " AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = ?)";
        $types .= "i";
        $params[] = $kategoriId;
    }

    // AMBIL SEMUA PRODUK DAN KELOMPOKKAN BERDASARKAN NAMA INDUK
    $sqlAll = "
        SELECT p.*, b.nama_merek 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        $whereClause 
        ORDER BY p.id DESC
    ";
    $stmtAll = $koneksi->prepare($sqlAll);
    if (!empty($params)) {
        $stmtAll->bind_param($types, ...$params);
    }
    $stmtAll->execute();
    $resultAll = $stmtAll->get_result();

    $groupedProducts = [];
    while ($row = $resultAll->fetch_assoc()) {
        $namaInduk = trim(preg_replace('/(?:\s*\d+\s*(?:CH|Channel|channel).*$)/i', '', $row['nama_produk']));
        $brandKey = $row['brand_id'] . '_' . strtolower($namaInduk);

        if (!isset($groupedProducts[$brandKey])) {
            $groupedProducts[$brandKey] = [
                'id_target' => $row['id'], 
                'nama_induk' => $namaInduk,
                'nama_merek' => $row['nama_merek'],
                'gambar' => $row['gambar'],
                'deskripsi' => $row['deskripsi'],
                'harga_mulai' => $row['harga']
            ];
        }

        if ($row['harga'] < $groupedProducts[$brandKey]['harga_mulai']) {
            $groupedProducts[$brandKey]['harga_mulai'] = $row['harga'];
            $groupedProducts[$brandKey]['id_target'] = $row['id'];
        }
    }
    $stmtAll->close();

    $groupedArray = array_values($groupedProducts);
    $total_data = count($groupedArray);
    $total_halaman = ceil($total_data / $batas_produk);
    $paginatedProducts = array_slice($groupedArray, $offset, $batas_produk);
    ?>

    <main class="flex-grow bg-gradient-to-r from-indigo-950 via-purple-950 to-blue-950 min-h-screen relative">
        <div class="absolute top-0 right-1/4 w-[400px] h-[400px] bg-violet-500/20 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-1/3 left-1/4 w-[350px] h-[350px] bg-cyan-500/20 rounded-full blur-[90px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-12 md:pt-16 pb-8 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/20 border border-orange-500/40 text-orange-300 font-bold tracking-widest text-xs uppercase mb-3 backdrop-blur-md shadow-sm">
                <i class="fa-solid fa-star"></i> Pilihan Terbaik
            </span>
            <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-3 drop-shadow-md">
                Paket CCTV
            </h2>
            <p class="text-purple-100 max-w-xl mx-auto text-sm md:text-base leading-relaxed font-light">
                Pilihan perangkat keamanan terbaik yang kami rekomendasikan untuk kebutuhan Anda.
            </p>
        </div>

        <!-- ==========================================
            FILTER KATEGORI & MEREK (TETAP DI TEMPAT)
        =========================================== -->
        <div class="relative w-full bg-[#0b0b1a]/40 backdrop-blur-md border-y border-white/10 py-3 mb-6 shadow-lg transition-all z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-2.5">
                
                <!-- Kategori -->
                <div class="flex justify-center">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-1.5 max-w-full overflow-x-auto custom-scrollbar">
                        <div class="flex items-center gap-1 min-w-max">
                            <a href="produk.php?kategori=semua&merek=<?= urlencode($merekAktif) ?>" class="px-3.5 py-2 rounded-lg font-bold text-xs transition-colors <?= $kategoriAktif === 'semua' ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">Semua Kategori</a>
                            <?php 
                            if ($query_kategori && $query_kategori->num_rows > 0) {
                                while ($kat = $query_kategori->fetch_assoc()) {
                                    $activeKat = ($kategoriAktif === $kat['slug']) ? 'bg-blue-600 text-white shadow' : 'text-gray-300 hover:bg-white/10 hover:text-white';
                                    echo '<a href="produk.php?kategori=' . htmlspecialchars($kat['slug']) . '&merek=' . urlencode($merekAktif) . '" class="px-3.5 py-2 rounded-lg font-bold text-xs transition-colors ' . $activeKat . '">' . htmlspecialchars($kat['nama_kategori']) . '</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Merek -->
                <div class="flex justify-center">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-1.5 max-w-full overflow-x-auto custom-scrollbar">
                        <div class="flex items-center gap-1 min-w-max">
                            <a href="produk.php?kategori=<?= urlencode($kategoriAktif) ?>&merek=semua" class="px-3.5 py-2 rounded-lg font-bold text-xs transition-colors <?= $merekAktif === 'semua' ? 'bg-orange-500 text-white shadow' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">Semua Merek</a>
                            <?php 
                            if ($query_merek && $query_merek->num_rows > 0) {
                                while ($mrk = $query_merek->fetch_assoc()) {
                                    $activeMrk = ((string)$merekAktif === (string)$mrk['id']) ? 'bg-orange-500 text-white shadow' : 'text-gray-300 hover:bg-white/10 hover:text-white';
                                    echo '<a href="produk.php?kategori=' . urlencode($kategoriAktif) . '&merek=' . $mrk['id'] . '" class="px-3.5 py-2 rounded-lg font-bold text-xs transition-colors ' . $activeMrk . '">' . htmlspecialchars($mrk['nama_merek']) . '</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- END FILTER NORMAL -->

        <!-- PENCARIAN PRODUK -->
                <div class="mb-6">
                    <div class="relative max-w-2xl mx-auto">

                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                        <input
                            type="text"
                            id="searchProduk"
                            placeholder="Cari produk di halaman ini..."
                            autocomplete="off"
                            class="w-full bg-white border border-gray-200 rounded-xl pl-11 pr-11 py-3.5 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >

                        <button
                            type="button"
                            id="clearSearchProduk"
                            class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition items-center justify-center"
                            aria-label="Hapus pencarian"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>

                    <p
                        id="hasilPencarianProduk"
                        class="text-xs text-purple-200 text-center mt-2 hidden"
                    ></p>
                </div>

        <!-- GRID KARTU PRODUK TERKELOMPOK -->
        <div class="pb-10 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-8">
                    <?php if (!empty($paginatedProducts)): ?>
                        <?php foreach ($paginatedProducts as $prod): ?>
                            <div class="produk-card bg-white rounded-2xl shadow-xl hover:-translate-y-2 transition-all duration-300 overflow-hidden flex flex-col group relative"
                                data-search="<?= htmlspecialchars(strtolower($prod['nama_induk'] . ' ' . $prod['nama_merek'] . ' ' . $prod['deskripsi']), ENT_QUOTES, 'UTF-8') ?>">

                                <div class="absolute top-2 left-2 sm:top-4 sm:left-4 z-20 bg-gradient-to-r from-orange-500 to-red-500 text-white text-[8px] sm:text-[10px] uppercase font-extrabold px-2 py-1 rounded-full shadow">
                                    <i class="fa-solid fa-star mr-1"></i> Paket CCTV
                                </div>

                                <!-- GAMBAR KARTU -->
                                <div class="relative overflow-hidden bg-white h-32 sm:h-56 flex items-center justify-center p-3 cursor-pointer" onclick="location.href='detail_produk.php?id=<?= $prod['id_target'] ?>'">
                                    <?php if (!empty($prod['gambar']) && file_exists('uploads/' . $prod['gambar'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_induk']) ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-500 relative z-10" loading="lazy">
                                    <?php else: ?>
                                        <div class="text-center text-gray-300 relative z-10">
                                            <i class="fa-solid fa-image text-3xl sm:text-5xl mb-1"></i>
                                            <p class="text-[10px] sm:text-sm">Tidak ada gambar</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- INFORMASI PRODUK -->
                                <div class="p-3 sm:p-5 flex flex-col flex-grow bg-white">
                                    <div class="flex items-center gap-1 mb-1">
                                        <?php for ($i = 0; $i < 5; $i++): ?><i class="fa-solid fa-star text-yellow-400 text-[8px] sm:text-xs"></i><?php endfor; ?>
                                        <span class="text-[10px] text-gray-400 ml-1">(5.0)</span>
                                    </div>

                                    <?php if (!empty($prod['nama_merek'])): ?>
                                        <div class="mb-1"><span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-md text-[10px] font-bold"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($prod['nama_merek']) ?></span></div>
                                    <?php endif; ?>

                                    <h3 class="font-bold text-xs sm:text-base text-gray-900 leading-tight mb-2 line-clamp-2 cursor-pointer hover:text-blue-600 transition" onclick="location.href='detail_produk.php?id=<?= $prod['id_target'] ?>'">
                                        <?= htmlspecialchars($prod['nama_induk']) ?>
                                    </h3>

                                    <div class="mt-auto pt-2 border-t border-gray-100 mb-3">
                                        <p class="text-[10px] text-gray-400">Harga Mulai Dari</p>
                                        <p class="text-sm sm:text-lg font-black text-blue-700">Rp <?= number_format($prod['harga_mulai'], 0, ',', '.') ?></p>
                                    </div>

                                    <!-- TOMBOL -->
                                    <a href="detail_produk.php?id=<?= $prod['id_target'] ?>" class="w-full bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-800 text-white font-bold py-2.5 px-3 rounded-xl transition shadow flex items-center justify-center gap-2 text-xs sm:text-sm text-center">
                                        <i class="fa-solid fa-cart-plus"></i> Pilih Variasi & Paket
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-2 lg:col-span-4 text-center py-20 bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 text-white">
                            <i class="fa-solid fa-video text-5xl text-yellow-400 mb-4 block"></i>
                            <h3 class="text-xl font-bold">Tidak Ada Produk</h3>
                            <p class="text-purple-200 mt-2">Belum ada Paket CCTV untuk kombinasi kategori dan merek ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PAGINASI -->
                <?php if ($total_halaman > 1): ?>
                    <div class="flex justify-center items-center gap-2 mt-12 mb-8">
                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="?kategori=<?= urlencode($kategoriAktif) ?>&merek=<?= urlencode($merekAktif) ?>&halaman=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl transition <?= $i == $halaman_aktif ? 'bg-orange-500 text-white font-black shadow-lg shadow-orange-500/30' : 'bg-white/10 text-white hover:bg-white/20' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <style>
    /* Styling khusus agar scrollbar di filter tidak tebal & jelek */
    .custom-scrollbar::-webkit-scrollbar { height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
    </style>

    <script>
    // Pencarian Real-Time
    (function () {
        const input = document.getElementById('searchProduk');
        const clearButton = document.getElementById('clearSearchProduk');
        const info = document.getElementById('hasilPencarianProduk');
        const cards = document.querySelectorAll('.produk-card');

        if (!input) return;

        input.addEventListener('input', function () {
            const kataKunci = input.value.trim().toLowerCase();
            let jumlahDitemukan = 0;

            cards.forEach(card => {
                const match = (card.getAttribute('data-search') || '').toLowerCase().includes(kataKunci);
                card.style.display = match ? '' : 'none';
                if (match) jumlahDitemukan++;
            });

            if (kataKunci !== '') {
                clearButton.classList.remove('hidden');
                clearButton.classList.add('flex');
                info.textContent = `Menemukan ${jumlahDitemukan} produk di halaman ini.`;
                info.classList.remove('hidden');
            } else {
                clearButton.classList.add('hidden');
                clearButton.classList.remove('flex');
                info.classList.add('hidden');
            }
        });

        clearButton.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    })();
    </script>

    <?php include 'footer.php'; ?>