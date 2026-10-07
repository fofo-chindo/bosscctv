<?php
ob_start();
session_start();
include 'koneksi.php';

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = 'user'; // Role otomatis user biasa

    if (!empty($nama) && !empty($email) && !empty($password)) {
        // Cek apakah email sudah terdaftar
        $check_stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error_message = "Email ini sudah terdaftar!";
        } else {
            $hash_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $hash_password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Pendaftaran berhasil! Silakan <a href='login.php' class='underline font-bold'>login di sini</a>.";
            } else {
                $error_message = "Terjadi kesalahan sistem, coba lagi nanti.";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    } else {
        $error_message = "Semua kolom wajib diisi!";
    }
}

include 'header.php';
?>

<main class="min-h-[80vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">Buat Akun Baru</h2>
            <p class="mt-2 text-sm text-gray-600">Lengkapi data di bawah ini untuk mendaftar</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm text-center">
                <?= $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm text-center font-medium">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="space-y-4" action="register.php" method="POST">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary outline-none">
            </div>
            <button type="submit" class="w-full py-3 px-4 rounded-lg text-sm font-medium text-white bg-primary hover:bg-blue-800 transition-colors">
                Daftar Akun
            </button>
        </form>

        <div class="text-center text-sm text-gray-600">
            Sudah punya akun? <a href="login.php" class="font-medium text-primary hover:underline">Masuk di sini</a>
        </div>
    </div>
</main>

<?php 
include 'footer.php'; 
ob_end_flush();
?>