<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$baseUrl = rtrim(env('APP_URL', 'https://xt4.my.id'), '/');
$db = new SupabaseClient();
$posts = $db->getAllRows('posts', ['slug', 'updated_at'], [], 'updated_at.desc');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach ($posts as $post): ?>
    <url>
        <loc><?= $baseUrl ?>/post/<?= e($post['slug']) ?></loc>
        <lastmod><?= e(date('c', strtotime($post['updated_at']))) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
