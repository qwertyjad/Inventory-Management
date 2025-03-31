<?php
class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::set('msg', "<div class='toast-message error'>Please log in to access this page!</div>");
            header('Location: auth/login.php');
            exit;
        }
    }

    public static function requireRole($role) {
        self::requireLogin();
        $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
        if ($user_role !== $role) {
            self::set('msg', "<div class='toast-message error'>You do not have permission to access this page!</div>");
            header('Location: ../index.php');
            exit;
        }
    }

    public static function destroy() {
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
}
?>