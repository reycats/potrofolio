<?php
/**
 * Halaman baca artikel berdasarkan slug.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    http_response_code(404);
    require '404.php';
    exit;
}

$db = new SupabaseClient();
$post = $db->getRow('posts', ['slug' => 'eq.' . $slug]);

if (!$post) {
    http_response_code(404);
    require '404.php';
    exit;
}

$seo = new SEO();
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->renderMeta([
        'title' => $post['title'] . ' — XT4',
        'description' => substr(strip_tags($post['content']), 0, 160),
        'type' => 'article',
        'url' => '/post/' . $post['slug'],
        'image' => $post['image_url'] ?? null,
        'published_time' => $post['created_at'],
        'updated_time' => $post['updated_at'] ?? $post['created_at'],
        'author' => $post['author'] ?? 'XT4',
    ]) ?>
    <?= $seo->jsonLdArticle($post) ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0b0f19; color: #e2e8f0; }
        .glass { backdrop-filter: blur(16px); background: rgba(0,0,0,0.3); border: 1px solid rgba(0,255,255,0.1); border-radius: 1.5rem; }
        .prose { max-width: 65ch; color: #cbd5e1; line-height: 1.8; font-size: 1.1rem; }
        .prose h2, .prose h3 { color: #67e8f9; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-10">
    <article class="max-w-3xl mx-auto glass p-6 md:p-10">
        <a href="/" class="text-cyan-400 hover:text-cyan-300 mb-4 inline-block">← Kembali ke Beranda</a>
        <?php if (!empty($post['image_url'])): ?>
            <img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>" class="w-full h-64 object-cover rounded-xl mb-6" loading="lazy">
        <?php endif; ?>
        <h1 class="text-3xl md:text-4xl font-bold text-cyan-200 mb-2"><?= e($post['title']) ?></h1>
        <div class="text-gray-400 text-sm mb-6">
            <span>Oleh <?= e($post['author'] ?? 'XT4') ?></span> &bull;
            <time datetime="<?= e($post['created_at']) ?>"><?= e(date('d F Y', strtotime($post['created_at']))) ?></time>
        </div>
        <div class="prose">
            <?= $post['content'] // sudah aman karena dari trusted editor, perlu sanitasi output jika diperlukan ?>
        </div>
    </article>

    <footer class="text-center text-gray-600 mt-10 text-sm">
        © <?= date('Y') ?> XT4
    </footer>
</body>
</html>
