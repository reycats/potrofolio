<?php
/**
 * Fungsi global pembantu untuk aplikasi XT4.
 * Diletakkan di includes/helpers.php dan di-autoload.
 */
if (!function_exists('e')) {
    /**
     * Escape string untuk output HTML (anti XSS).
     */
    function e(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Ambil variabel lingkungan dengan fallback.
     */
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect HTTP dan hentikan eksekusi.
     */
    function redirect(string $url, int $statusCode = 302): void {
        header("Location: $url", true, $statusCode);
        exit;
    }
}

if (!function_exists('slugify')) {
    /**
     * Ubah string menjadi slug URL-friendly.
     */
    function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text) ?: 'untitled';
    }
}
