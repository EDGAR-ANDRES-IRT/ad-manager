<?php
require_once __DIR__ . '/../includes/auth.php';
Auth::requireLogin();
$currentUser = Auth::getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'AD Manager' ?> — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ad-manager/assets/css/app.css">
</head>
<body>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="var(--accent)"/><path d="M8 16a8 8 0 1016 0A8 8 0 008 16z" stroke="white" stroke-width="2"/><path d="M16 10v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">AD Manager</span>
                <span class="brand-server">v<?= APP_VERSION ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">General</div>
            <a href="/ad-manager/pages/dashboard.php" class="nav-item <?= $currentPage==='dashboard' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm8-8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm0 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                Dashboard
            </a>
            <a href="/ad-manager/pages/search.php" class="nav-item <?= $currentPage==='search' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                Buscar
            </a>

            <div class="nav-section-label">Directorio</div>
            <a href="/ad-manager/pages/ous.php" class="nav-item <?= $currentPage==='ous' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                Unidades Org.
            </a>
            <a href="/ad-manager/pages/users.php" class="nav-item <?= $currentPage==='users' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                Usuarios
            </a>
            <a href="/ad-manager/pages/groups.php" class="nav-item <?= $currentPage==='groups' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-1a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v1h-3zM4.75 12.094A5.973 5.973 0 004 15v1H1v-1a3 3 0 013.75-2.906z"/></svg>
                Grupos
            </a>
            <a href="/ad-manager/pages/computers.php" class="nav-item <?= $currentPage==='computers' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/></svg>
                Equipos
            </a>

            <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="nav-section-label">Sistema</div>
            <a href="/ad-manager/pages/app_users.php" class="nav-item <?= $currentPage==='app_users' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                Usuarios App
            </a>
            <a href="/ad-manager/pages/logs.php" class="nav-item <?= $currentPage==='logs' ? 'active':'' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                Registro de Actividad
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'] ?: $currentUser['username'], 0, 2)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']) ?></span>
                    <span class="user-role"><?= $currentUser['role'] ?></span>
                </div>
            </div>
            <a href="/ad-manager/logout.php" class="btn-logout" title="Cerrar sesión">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title"><?= $pageTitle ?? '' ?></h1>
            <?php if (isset($pageActions)): ?>
            <div class="page-actions"><?= $pageActions ?></div>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars(urldecode($_GET['success'])) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">✗ <?= htmlspecialchars(urldecode($_GET['error'])) ?></div>
        <?php endif; ?>
