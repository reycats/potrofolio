<?php
/**
 * Editor Artikel — Buat / Edit postingan.
 * Mengintegrasikan TinyMCE (self-hosted atau CDN) untuk WYSIWYG,
 * serta tombol upload gambar langsung ke Supabase Storage.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::startSession();
if (!Auth::isAuthenticated()) {
    redirect('/admin/login');
}

$db = new SupabaseClient();
$post = ['title' => '', 'slug' => '', 'content' => '', 'image_url' => ''];
$editMode = false;

if (isset($_GET['id'])) {
    $existing = $db->getRow('posts', ['id' => 'eq.' . (int)$_GET['id']]);
    if ($existing) {
        $post = $existing;
        $editMode = true;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $slug = trim($_POST['slug'] ?? '') ?: slugify($title);
        $image_url = $_POST['image_url'] ?? '';

        if (empty($title) || empty($content)) {
            $error = 'Judul dan konten wajib diisi.';
        } else {
            try {
                $data = [
                    'title'     => $title,
                    'slug'      => $slug,
                    'content'   => $content,
                    'image_url' => $image_url,
                    'updated_at' => date('c'),
                ];

                if ($editMode) {
                    $db->update('posts', ['id' => 'eq.' . (int)$_GET['id']], $data);
                    $success = 'Artikel berhasil diperbarui.';
                } else {
                    $data['author'] = 'XT4';
                    $data['created_at'] = date('c');
                    $db->insert('posts', $data);
                    $success = 'Artikel baru diterbitkan!';
                }
            } catch (Exception $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor — XT4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body { background: #0b0f19; color: white; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(10px); border: 1px solid rgba(0,255,255,0.15); border-radius: 1.5rem; }
        .neon { color: #00f3ff; text-shadow: 0 0 10px #00f3ff80; }
    </style>
</head>
<body class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto glass p-6">
        <h1 class="text-3xl font-bold neon mb-6"><?= $editMode ? '✏️ Edit' : '✨ Artikel Baru' ?></h1>
        <?php if ($error): ?><div class="bg-red-500/20 border border-red-400 p-3 rounded mb-4"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-500/20 border border-green-400 p-3 rounded mb-4"><?= e($success) ?></div><?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

            <div>
                <label class="block text-cyan-300 mb-1">Judul</label>
                <input type="text" name="title" value="<?= e($post['title']) ?>" required
                    class="w-full bg-white/5 border border-cyan-500/30 rounded-lg p-3 text-white focus:ring-2 focus:ring-cyan-400">
            </div>

            <div>
                <label class="block text-cyan-300 mb-1">Slug (URL)</label>
                <input type="text" name="slug" value="<?= e($post['slug']) ?>"
                    class="w-full bg-white/5 border border-cyan-500/30 rounded-lg p-3 text-white">
            </div>

            <div>
                <label class="block text-cyan-300 mb-1">URL Gambar Utama</label>
                <div class="flex gap-2">
                    <input type="url" name="image_url" id="image_url" value="<?= e($post['image_url']) ?>"
                        class="flex-1 bg-white/5 border border-cyan-500/30 rounded-lg p-3 text-white">
                    <button type="button" id="uploadBtn" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg transition">Upload</button>
                </div>
            </div>

            <div>
                <label class="block text-cyan-300 mb-1">Konten</label>
                <textarea id="contentEditor" name="content"><?= e($post['content']) ?></textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-lg font-semibold hover:shadow-cyan-500/20 transition">Simpan</button>
                <a href="/admin/dashboard" class="px-6 py-3 bg-gray-700 rounded-lg">Batal</a>
            </div>
        </form>
    </div>

    <script>
        // TinyMCE init
        tinymce.init({
            selector: '#contentEditor',
            plugins: 'image link code media',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | image link media | code',
            height: 500,
            images_upload_handler: function (blobInfo, success, failure) {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('csrf_token', '<?= Auth::csrfToken() ?>');
                fetch('/admin/upload_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(json => {
                    if (json.location) {
                        success(json.location);
                    } else {
                        failure(json.message || 'Upload failed');
                    }
                })
                .catch(() => failure('Network error'));
            }
        });

        // Tombol upload eksternal untuk gambar utama
        document.getElementById('uploadBtn').addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function() {
                const file = this.files[0];
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', '<?= Auth::csrfToken() ?>');
                fetch('/admin/upload_handler.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json()).then(json => {
                    if (json.location) {
                        document.getElementById('image_url').value = json.location;
                    } else {
                        alert('Upload gagal: ' + json.message);
                    }
                });
            };
            input.click();
        });
    </script>
</body>
</html>
