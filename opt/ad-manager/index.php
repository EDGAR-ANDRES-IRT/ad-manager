<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

Auth::startSession();

// Redirigir si ya está autenticado
if (Auth::isLoggedIn()) {
    header('Location: /ad-manager/pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::login($username, $password)) {
        Auth::log('LOGIN', $username, 'Inicio de sesión exitoso');
        header('Location: /ad-manager/pages/dashboard.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ad-manager/assets/css/app.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="56" height="56" rx="14" fill="#2563eb"/>
                <circle cx="28" cy="28" r="14" stroke="white" stroke-width="2.5"/>
                <path d="M28 18v10l6 3" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="28" cy="28" r="3" fill="white"/>
            </svg>
            <h1><?= APP_NAME ?></h1>
            <p>Active Directory Manager v<?= APP_VERSION ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($msg === 'session_expired'): ?>
        <div class="alert alert-warning">⚠ Tu sesión ha expirado. Inicia sesión nuevamente.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-control"
                       placeholder="usuario" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                Iniciar Sesión
            </button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:12px;color:var(--text2);">
            Servidor: <span style="font-family:var(--mono);"><?= AD_SERVER_IP ?></span>
        </p>
    </div>
</div>
<script src="/ad-manager/assets/js/app.js"></script>
</body>
</html>
