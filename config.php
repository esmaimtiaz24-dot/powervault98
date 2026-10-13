<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'powervault_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('STORE_NAME', 'POWERVAULT');
define('SUPPORT_EMAIL', 'Supportpowervault@gmail.com');
define('WHATSAPP_NUMBER', '923273822228');
define('EASYPAISA_NUMBER', '03273822228');
define('JAZZCASH_NUMBER', '03074364067');
define('STORE_PHONE', '03273822228');
define('SHIPPING_CHARGE', 250.00);
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
    }
    return $pdo;
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Session token expired. Refresh and try again.'); } }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) { header('Location: /powervault/account/login.php'); exit; } }
function require_admin(): void { if (!user() || (user()['role'] ?? '') !== 'admin') { header('Location: /powervault/admin/login.php'); exit; } }
function money($n): string { return 'Rs. '.number_format((float)$n, 2); }
function flash(string $msg, string $type='success'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function get_flash(): string { if (empty($_SESSION['flash'])) return ''; $f=$_SESSION['flash']; unset($_SESSION['flash']); return '<div class="alert '.e($f['type']).'">'.e($f['msg']).'</div>'; }
function setting(string $key, $default=null) { $s=db()->prepare('SELECT setting_value FROM store_settings WHERE setting_key=?'); $s->execute([$key]); $r=$s->fetch(); return $r ? $r['setting_value'] : $default; }
