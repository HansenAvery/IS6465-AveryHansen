<?php
require_once __DIR__ . '/../repositories/userPDO.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('peer_tutoring_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCsrfToken(array $source): void {
    startSecureSession();
    $sent = (string)($source['csrf_token'] ?? '');
    if ($sent === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403); exit('Invalid security token. Reload the form and try again.');
    }
}
function logoutSession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function requireLogin(PDO $pdo): array {
    startSecureSession();
    if (empty($_SESSION['user_id'])) { header('Location: /php/login.php', true, 303); exit; }
    if (time() - (int)($_SESSION['last_activity'] ?? 0) > 1200) {
        logoutSession(); header('Location: /php/login.php?expired=1', true, 303); exit;
    }
    $user = findUserById($pdo, (int)$_SESSION['user_id']);
    if (!$user || $user['status'] !== 'Active') {
        logoutSession(); header('Location: /php/login.php?inactive=1', true, 303); exit;
    }
    $_SESSION['last_activity'] = time();
    $_SESSION['display_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    return $user;
}
function requireRole(array $user, array $allowedRoles): void {
    if (!in_array($user['role'], $allowedRoles, true)) { http_response_code(403); exit('You are not authorized to perform this action.'); }
}
function currentUser(PDO $pdo): ?array {
    startSecureSession();
    if (empty($_SESSION['user_id'])) return null;
    return requireLogin($pdo);
}
