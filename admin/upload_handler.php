<?php
/**
 * Handler upload file ke Supabase Storage.
 * Menerima POST dengan file dan CSRF token, lalu mengembalikan URL publik.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::startSession();
if (!Auth::isAuthenticated()) {
    http_response_code(403);
    exit(json_encode(['message' => 'Unauthorized']));
}

header('Content-Type: application/json');

try {
    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Auth::validateCsrf($csrf)) {
        throw new RuntimeException('CSRF token tidak valid.');
    }

    // File
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File tidak ditemukan atau error upload.');
    }

    $file = $_FILES['file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        throw new RuntimeException('Tipe file tidak diizinkan.');
    }

    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Ukuran file maksimal 5MB.');
    }

    // Generate nama unik
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $bucket = 'blog-images'; // pastikan bucket public sudah dibuat di Supabase
    $path = 'uploads/' . $filename;

    $db = new SupabaseClient();
    $publicUrl = $db->uploadFile($bucket, $path, file_get_contents($file['tmp_name']), $mime);

    echo json_encode(['location' => $publicUrl]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
}
