<?php
/**
 * Landing page XT4 Portfolio.
 * Menampilkan profil singkat, lalu daftar artikel terbaru.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/helpers.php';

$seo = new SEO();
$db = new SupabaseClient();
$articles = $db->getAllRows('posts', ['*'], [], 'created_at.desc');
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->renderMeta([
        'title' => 'XT4 — Personal Branding & Creative Portfolio',
        'type' => 'website',
        'url' => '/',
    ]) ?>
    <?= $seo->jsonLdPerson() ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0b0f19; color: #e2e8f0; }
        .glass { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); background: rgba(255,255,255,0.03); border: 1px solid rgba(0,255,255,0.12); border-radius: 2rem; }
        .neon-text { color: #00f3ff; text-shadow: 0 0 15px rgba(0,243,255,0.6); }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 0 25px rgba(0,243,255,0.15); }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="glass m-4 p-6 text-center">
        <h1 class="text-5xl md:text-7xl font-black neon-text">XT4</h1>
        <p class="text-lg md:text-xl text-gray-300 mt-2">Future-Driven Creator & Developer</p>
    </header>

    <!-- Portfolio / Introduction -->
    <section class="mx-4 my-6 glass p-8 grid md:grid-cols-2 gap-8 items-center">
        <div>
            <h2 class="text-2xl font-bold text-cyan-300">Tentang XT4</h2>
            <p class="mt-3 text-gray-400 leading-relaxed">
                XT4 adalah brand personal yang memadukan kreativitas digital, pengembangan web modern, dan estetika futuristik. Melalui blog ini, saya membagikan wawasan teknologi, tutorial, dan proyek portofolio dengan semangat inovasi.
            </p>
        </div>
        <div class="relative">
            <div class="w-48 h-48 mx-auto rounded-full bg-gradient-to-br from-cyan-400 to-purple-600 p-1 shadow-xl shadow-cyan-500/30">
                <div class="w-full h-full rounded-full bg-gray-900 flex items-center justify-center text-5xl font-black neon-text">XT4</div>
            </div>
        </div>
    </section>

    <!-- Artikel Terbaru -->
    <section class="mx-4 my-6 flex-1">
        <h2 class="text-3xl font-bold text-white mb-6">📰 Artikel Terbaru</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($articles)): ?>
                <div class="glass p-8 col-span-full text-center text-gray-500">Belum ada artikel.</div>
            <?php else: foreach ($articles as $article): ?>
                <a href="/post/<?= e($article['slug']) ?>" class="glass p-5 card-hover transition-all block">
                    <?php if (!empty($article['image_url'])): ?>
                        <img src="<?= e($article['image_url']) ?>" alt="<?= e($article['title']) ?>" class="w-full h-48 object-cover rounded-xl mb-4" loading="lazy">
                    <?php endif; ?>
                    <h3 class="text-xl font-semibold text-white"><?= e($article['title']) ?></h3>
                    <p class="text-sm text-gray-400 mt-2"><?= e(date('d M Y', strtotime($article['created_at']))) ?></p>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <footer class="glass m-4 p-4 text-center text-gray-500 text-sm">
        © <?= date('Y') ?> XT4 — All rights reversed.
    </footer>
</body>
</html>
