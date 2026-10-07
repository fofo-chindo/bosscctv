<?php
ob_start();
session_start();
include 'koneksi.php';

// Redirect otomatis jika pengguna sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($koneksi, "SELECT id, nama, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verifikasi Password Hash / Plain-text Fallback
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'admin') {
                    echo "<script>window.location.href='admin_dashboard.php';</script>";
                    header("Location: admin_dashboard.php");
                } else {
                    echo "<script>window.location.href='index.php';</script>";
                    header("Location: index.php");
                }
                exit();
            } else {
                $error_message = "Password yang Anda masukkan salah!";
            }
        } else {
            $error_message = "Email tidak terdaftar!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Silakan isi email dan password!";
    }
}

include 'header.php';
?>

<main class="min-h-[80vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">Selamat Datang</h2>
            <p class="mt-2 text-sm text-gray-600">Silakan masuk ke akun GSE Boss CCTV Anda</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm text-center font-medium">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="space-y-5" action="login.php" method="POST">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input id="email" name="email" type="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all text-sm" placeholder="nama@email.com">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input id="password" name="password" type="password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all text-sm" placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-gray-700 cursor-pointer">Ingat Saya</label>
                </div>
                <a href="#" class="font-medium text-primary hover:underline">Lupa kata sandi?</a>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-800 focus:outline-none transition-colors">
                    Masuk Sekarang
                </button>
            </div>
        </form>

        <div class="text-center text-sm text-gray-600">
            Belum punya akun? 
            <a href="register.php" class="font-medium text-primary hover:underline">Daftar di sini</a>
        </div>
    </div>
</main>

<?php 
include 'footer.php'; 
ob_end_flush();
?>