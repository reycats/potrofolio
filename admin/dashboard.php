<?php
/**
 * Dashboard Admin: daftar artikel, aksi hapus/edit.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::startSession();
if (!Auth::isAuthenticated()) {
    redirect('/admin/login');
}

$db = new SupabaseClient();
$posts = $db->getAllRows('posts', ['*'], [], 'created_at.desc');

$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — XT4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0b0f19; color: #e2e8f0; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(10px); border: 1px solid rgba(0,255,255,0.1); border-radius: 1.5rem; }
        .neon { color: #00f3ff; text-shadow: 0 0 10px #00f3ff80; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 glass p-6">
            <h1 class="text-3xl font-bold neon">⚡ Dashboard XT4</h1>
            <div class="flex gap-4 mt-4 md:mt-0">
                <a href="/admin/editor" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg transition">+ Artikel Baru</a>
                <a href="/admin/logout.php" class="px-4 py-2 bg-red-600/70 hover:bg-red-500 rounded-lg transition">Logout</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="bg-green-500/20 border border-green-400 text-green-200 p-4 rounded-lg mb-6"><?= e($message) ?></div>
        <?php endif; ?>

        <div class="grid gap-4">
            <?php if (empty($posts)): ?>
                <div class="glass p-8 text-center text-gray-400">Belum ada artikel. Klik "Artikel Baru".</div>
            <?php else: foreach ($posts as $post): ?>
                <div class="glass p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-cyan-200"><?= e($post['title']) ?></h2>
                        <p class="text-sm text-gray-400"><?= e(date('d M Y H:i', strtotime($post['created_at']))) ?></p>
                    </div>
                    <div class="flex gap-3 mt-3 sm:mt-0">
                        <a href="/admin/editor?id=<?= e($post['id']) ?>" class="text-blue-400 hover:text-blue-300"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="/admin/hapus.php" onsubmit="return confirm('Yakin hapus?')">
                            <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</body>
</html>
