<?php
/**
 * Halaman Login — hanya bisa diakses jika belum login.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::startSession();

// Jika sudah login, langsung ke dashboard
if (Auth::isAuthenticated()) {
    redirect('/admin/dashboard');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (Auth::login($password)) {
        redirect('/admin/dashboard');
    } else {
        $error = 'Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — XT4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Glassmorphism & Neon dark mode */
        body {
            background: linear-gradient(135deg, #0b0f19 0%, #1a1f35 100%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 255, 255, 0.15);
            border-radius: 1.5rem;
        }
        .neon-text {
            color: #00f3ff;
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass p-8 w-full max-w-md text-white">
        <h1 class="text-4xl font-bold neon-text text-center mb-6">🔐 XT4 Admin</h1>
        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-4">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-cyan-200">Password</label>
                <input type="password" name="password" id="password" required
                    class="mt-1 block w-full bg-white/10 border border-cyan-500/30 rounded-lg py-3 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-400">
            </div>
            <button type="submit"
                class="w-full py-3 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-lg font-semibold text-white hover:shadow-lg hover:shadow-cyan-500/20 transition">
                Masuk
            </button>
        </form>
    </div>
</body>
</html>
