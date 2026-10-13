<?php
require_once __DIR__.'/config.php';
$success='';$error='';
try{
 $pdo=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $pdo->exec("CREATE DATABASE IF NOT EXISTS `".str_replace('`','',DB_NAME)."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $pdo->exec("USE `".str_replace('`','',DB_NAME)."`");
 $sql=file_get_contents(__DIR__.'/database.sql');
 $sql=preg_replace('/^\s*CREATE DATABASE IF NOT EXISTS.*?;\s*/im','',$sql);
 $sql=preg_replace('/^\s*USE\s+powervault_db\s*;\s*/im','',$sql);
 $statements=array_filter(array_map('trim',explode(';',$sql)));
 foreach($statements as $statement){if($statement!=='')$pdo->exec($statement);}
 $email='admin@powervault.pk';$password='Admin@12345';
 $s=$pdo->prepare("SELECT id FROM users WHERE email=?");$s->execute([$email]);
 if(!$s->fetch()){$s=$pdo->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'admin')");$s->execute(['POWERVAULT Admin',$email,password_hash($password,PASSWORD_DEFAULT)]);}
 $success='Database setup completed. Admin email: '.$email.' | Initial password: '.$password.'. Change this password before production and delete setup.php.';
}catch(Throwable $e){$error='Setup failed: '.$e->getMessage().'. Import database.sql in phpMyAdmin and confirm config.php credentials.';}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>POWERVAULT Setup</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/powervault/assets/css/style.css"></head><body><div class="container section-pad"><div class="form-panel mx-auto" style="max-width:760px"><div class="eyebrow">INSTALLATION WIZARD</div><h1>POWERVAULT <span class="gradient-text">Setup</span></h1><?php if($success): ?><div class="alert success"><?=e($success)?></div><p><a class="btn btn-neon" href="/powervault/">Open Store</a> <a class="btn btn-outline-glow" href="/powervault/admin/login.php">Admin Login</a></p><?php else: ?><div class="alert error"><?=e($error)?></div><p>Check MySQL is running, then retry setup or import database.sql using phpMyAdmin.</p><?php endif; ?><p class="muted">After installation, remove setup.php and change the initial admin password.</p></div></div></body></html>
