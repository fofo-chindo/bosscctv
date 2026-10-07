<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman (Hanya Admin / Owner)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header("Location: login.php");
    exit;
}

$pesan = '';

// Proses Update Status Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status_baru = $_POST['status'];
    
    $stmt = $koneksi->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status_baru, $order_id);
    
    if ($stmt->execute()) {
        $pesan = '<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm"><i class="fa-solid fa-circle-check mr-3 text-emerald-500 text-lg"></i> Status pesanan berhasil diperbarui!</div>';
    } else {
        $pesan = '<div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm"><i class="fa-solid fa-circle-xmark mr-3 text-rose-500 text-lg"></i> Gagal memperbarui status.</div>';
    }
    $stmt->close();
}

// Proses Hapus Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_order'])) {
    $order_id = $_POST['order_id'];
    
    $stmt = $koneksi->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $pesan = '<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm"><i class="fa-solid fa-circle-check mr-3 text-emerald-500 text-lg"></i> Pesanan berhasil dihapus dari sistem!</div>';
    } else {
        $pesan = '<div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm"><i class="fa-solid fa-circle-xmark mr-3 text-rose-500 text-lg"></i> Gagal menghapus pesanan.</div>';
    }
    $stmt->close();
}

// Mengambil data pesanan dari tabel orders
$query_pesanan = $koneksi->query("SELECT * FROM orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - POS Admin CCTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden font-sans text-slate-800">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-[#0f172a] text-gray-300 flex flex-col h-full shrink-0 shadow-lg">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 text-white font-bold border-b border-slate-800 gap-3">
            <i class="fa-solid fa-shield-halved text-blue-500 text-xl"></i> POS Admin CCTV
        </div>
        
        <!-- Menu Navigasi -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800/60 rounded-xl transition-all">
                <i class="fa-solid fa-chart-line w-5 text-center"></i> Dashboard
            </a>
            <a href="admin_produk.php" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-800/60 rounded-xl transition-all">
                <i class="fa-solid fa-box w-5 text-center"></i> Kelola Stok & Produk
            </a>
            <a href="pesanan.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-xl shadow-md shadow-blue-600/20 font-medium">
                <i class="fa-solid fa-cart-shopping w-5 text-center"></i> Pesanan
            </a>
        </nav>
        
        <!-- Menu Bawah -->
        <div class="p-4 border-t border-slate-800 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-2.5 hover:text-white rounded-lg transition-colors text-sm">
                <i class="fa-solid fa-globe w-5 text-center"></i> Lihat Website
            </a>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-rose-400 hover:text-rose-300 rounded-lg transition-colors text-sm">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i> Keluar (Logout)
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8 relative flex flex-col">
        <?= $pesan ?>
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pesanan Masuk</h1>
                <p class="text-slate-500 text-sm mt-0.5">Kelola dan pantau seluruh transaksi pesanan pelanggan dengan mudah.</p>
            </div>
            <button onclick="window.print()" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center gap-2 group">
                <i class="fa-solid fa-print text-slate-400 group-hover:text-slate-600"></i> Cetak Laporan
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="relative w-full md:w-96">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input type="text" id="searchInput" onkeyup="filterPesanan()" placeholder="Cari kode, nama, atau no HP..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all">
            </div>
            
            <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mr-1">Filter:</span>
                <button onclick="filterStatus('all')" id="btn-all" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white transition-all shadow-sm">Semua</button>
                <button onclick="filterStatus('pending')" id="btn-pending" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">Menunggu</button>
                <button onclick="filterStatus('proses')" id="btn-proses" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">Proses</button>
                <button onclick="filterStatus('selesai')" id="btn-selesai" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">Selesai</button>
            </div>
        </div>

        <!-- Tabel Pesanan -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm flex-1 flex flex-col">
            <div class="overflow-x-auto flex-1">
                <table id="tabelPesanan" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Kode Pesanan</th>
                            <th class="px-6 py-4 font-semibold">Tanggal</th>
                            <th class="px-6 py-4 font-semibold">Pelanggan & No HP</th>
                            <th class="px-6 py-4 font-semibold">Total (Rp)</th>
                            <th class="px-6 py-4 font-semibold text-center">Status</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi (Status & Hapus)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if ($query_pesanan && $query_pesanan->num_rows > 0): ?>
                            <?php while($row = $query_pesanan->fetch_assoc()): ?>
                            <?php 
                                $status = strtolower($row['status'] ?? 'pending');
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors row-pesanan" data-status="<?= $status ?>">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full 
                                            <?= $status == 'selesai' ? 'bg-emerald-500' : ($status == 'proses' ? 'bg-blue-500' : ($status == 'batal' ? 'bg-rose-500' : 'bg-amber-500')) ?>"></span>
                                        <?= htmlspecialchars($row['kode_pesanan']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                                    <?= isset($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['nama_pembeli']) ?></div>
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $row['no_hp'])) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-emerald-600 hover:text-emerald-700 font-medium mt-0.5 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                        <i class="fa-brands fa-whatsapp text-sm"></i> <?= htmlspecialchars($row['no_hp']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    Rp <?= number_format($row['total_harga'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php 
                                        if ($status == 'selesai') {
                                            echo '<span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-200"><i class="fa-solid fa-check text-[10px]"></i> Selesai</span>';
                                        } elseif ($status == 'proses') {
                                            echo '<span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200"><i class="fa-solid fa-spinner text-[10px] animate-spin"></i> Diproses</span>';
                                        } elseif ($status == 'batal') {
                                            echo '<span class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 px-3 py-1 rounded-full text-xs font-bold border border-rose-200"><i class="fa-solid fa-xmark text-[10px]"></i> Dibatalkan</span>';
                                        } else {
                                            echo '<span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-200"><i class="fa-solid fa-clock text-[10px]"></i> Menunggu</span>';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Form Update Status -->
                                        <form action="" method="POST" class="inline-flex items-center gap-1 bg-slate-50 border border-slate-200 p-1 rounded-xl shadow-sm">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <select name="status" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 font-medium focus:outline-none focus:border-blue-500 cursor-pointer">
                                                <option value="Pending" <?= strcasecmp($status, 'pending') == 0 ? 'selected' : '' ?>>Menunggu</option>
                                                <option value="Proses" <?= strcasecmp($status, 'proses') == 0 ? 'selected' : '' ?>>Proses</option>
                                                <option value="Selesai" <?= strcasecmp($status, 'selesai') == 0 ? 'selected' : '' ?>>Selesai</option>
                                                <option value="Batal" <?= strcasecmp($status, 'batal') == 0 ? 'selected' : '' ?>>Batal</option>
                                            </select>
                                            <button type="submit" name="update_status" class="bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center shadow-sm" title="Simpan Perubahan Status">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>

                                        <!-- Form Hapus Pesanan -->
                                        <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan <?= htmlspecialchars($row['kode_pesanan']) ?> ini?');" class="inline">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_order" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 p-2 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center" title="Hapus Pesanan">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-300 text-2xl">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                    <p class="font-medium text-slate-600">Belum ada data pesanan masuk.</p>
                                    <p class="text-xs text-slate-400 mt-1">Pesanan dari website pelanggan akan muncul secara otomatis di sini.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Script JavaScript -->
    <script>
    function filterPesanan() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let rows = document.querySelectorAll('.row-pesanan');

        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            if (text.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    function filterStatus(status) {
        let buttons = document.querySelectorAll('[id^="btn-"]');
        buttons.forEach(btn => {
            btn.className = "px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all";
        });
        document.getElementById('btn-' + status).className = "px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white transition-all shadow-sm";

        let rows = document.querySelectorAll('.row-pesanan');
        rows.forEach(row => {
            let rowStatus = row.getAttribute('data-status');
            if (status === 'all' || rowStatus === status) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }
    </script>
</body>
</html>