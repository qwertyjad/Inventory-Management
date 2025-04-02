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
        // Check for admin/staff users
        if (isset($_SESSION['user_id']) && isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
            return true;
        }
        // Check for guest users
        if (isset($_SESSION['guest_user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') {
            return true;
        }
        return false;
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
        $user_role = self::get('user_role');
        if ($user_role !== $role) {
            self::set('msg', "<div class='toast-message error'>You do not have permission to access this page!</div>");
            if ($user_role === 'admin') {
                header('Location: admin/index.php');
            } elseif ($user_role === 'staff') {
                header('Location: user/index.php');
            } elseif ($user_role === 'user') {
                header('Location: user-dashboard.php');
            } else {
                header('Location: index.php');
            }
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