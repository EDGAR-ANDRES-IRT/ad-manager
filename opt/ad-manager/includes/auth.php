<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

class Auth {

    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function login(string $username, string $password): bool {
        self::startSession();
        $db   = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM app_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['login_time'] = time();
            $db->prepare("UPDATE app_users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
            return true;
        }
        return false;
    }

    public static function logout(): void {
        self::startSession();
        session_destroy();
        header('Location: /ad-manager/index.php');
        exit;
    }

    public static function isLoggedIn(): bool {
        self::startSession();
        if (!isset($_SESSION['user_id'])) return false;
        if ((time() - ($_SESSION['login_time'] ?? 0)) > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['login_time'] = time();
        return true;
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /ad-manager/index.php?msg=session_expired');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /ad-manager/pages/dashboard.php?error=no_permission');
            exit;
        }
    }

    public static function getCurrentUser(): array {
        self::startSession();
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'username'  => $_SESSION['username']  ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role'      => $_SESSION['role']      ?? '',
        ];
    }

    public static function log(string $action, string $target = '', string $details = '', string $status = 'success'): void {
        try {
            $db   = Database::getConnection();
            $user = self::getCurrentUser();
            $db->prepare("INSERT INTO activity_logs (app_user_id,action,target,details,status) VALUES (?,?,?,?,?)")
               ->execute([$user['id'], $action, $target, $details, $status]);
        } catch (Exception $e) { /* silencioso */ }
    }
}
