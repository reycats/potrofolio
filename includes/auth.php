<?php
/**
 * Authentication & Session Management.
 *
 * Fitur:
 * - Login/logout dengan session aman.
 * - CSRF token generation & validation.
 * - Session fixation protection.
 * - Hanya satu admin yang diizinkan (dari variabel lingkungan).
 *
 * @package XT4
 */
class Auth {
    /**
     * Inisialisasi sesi dengan pengaturan ketat.
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 86400, // 1 hari
                'path'     => '/',
                'domain'   => '',
                'secure'   => true,   // hanya HTTPS
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    /**
     * Proses login admin.
     *
     * @param string $password Password dari form
     * @return bool
     */
    public static function login(string $password): bool {
        $adminPassword = env('ADMIN_PASSWORD');
        if (!$adminPassword) {
            throw new RuntimeException('ADMIN_PASSWORD tidak diset di environment.');
        }

        if (password_verify($password, $adminPassword)) {
            self::startSession();
            session_regenerate_id(true); // anti session fixation
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            return true;
        }
        return false;
    }

    /**
     * Cek apakah user saat ini sudah login.
     *
     * @return bool
     */
    public static function isAuthenticated(): bool {
        self::startSession();
        return ($_SESSION['admin_authenticated'] ?? false) === true
            && ($_SESSION['admin_ip'] === $_SERVER['REMOTE_ADDR'])
            && ($_SESSION['admin_user_agent'] === $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Logout: hapus sesi dan regenerasi ID.
     */
    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Generate CSRF token dan simpan di session.
     *
     * @return string
     */
    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validasi CSRF token dari request.
     *
     * @param string $token Token yang dikirim
     * @return bool
     */
    public static function validateCsrf(string $token): bool {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
