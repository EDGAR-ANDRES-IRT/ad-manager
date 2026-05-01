<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';

Auth::requireLogin();
$api = new ApiClient();

$ping   = $api->get('/ping');
$apiOk  = ($ping['status'] ?? '') === 'success';

$stats  = [];
$domain = [];

if ($apiOk) {
    $statsR  = $api->getData('/dominio/stats');
    $domainR = $api->getData('/dominio');
    $stats   = $statsR['data']  ?? [];
    $domain  = is_array($domainR['data']) && isset($domainR['data'][0])
               ? $domainR['data'][0]
               : ($domainR['data'] ?? []);
}
?>
<?php
$pageActions = '';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$apiOk): ?>
<div class="alert alert-error">
    ⚠ No se pudo conectar a la API Flask (<code>http://127.0.0.1:5000</code>).
    Verifica que el servicio <strong>ad-sistema</strong> esté corriendo:
    <code>sudo systemctl status ad-sistema</code>
</div>
<?php else: ?>
<div class="alert alert-success">✓ API Flask operativa — conexión con AD establecida</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-number"><?= $stats['Users'] ?? '—' ?></div>
        <div class="stat-label">Usuarios</div>
    </div>
    <div class="stat-card green">
        <div class="stat-number"><?= $stats['Groups'] ?? '—' ?></div>
        <div class="stat-label">Grupos</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-number"><?= $stats['Computers'] ?? '—' ?></div>
        <div class="stat-label">Equipos</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-number"><?= $stats['OUs'] ?? '—' ?></div>
        <div class="stat-label">Unidades Org.</div>
    </div>
    <div class="stat-card red">
        <div class="stat-number"><?= $stats['DisabledUsers'] ?? '—' ?></div>
        <div class="stat-label">Usuarios Desactivados</div>
    </div>
</div>

<?php if (!empty($domain)): ?>
<div class="card">
    <div class="card-title">Información del Dominio</div>
    <div class="form-row-3">
        <?php
        $campos = [
            'DNSRoot'    => 'Nombre DNS',
            'NetBIOSName'=> 'NetBIOS',
            'DomainMode' => 'Nivel Funcional',
            'PDCEmulator'=> 'PDC Emulator',
            'Forest'     => 'Bosque',
        ];
        foreach ($campos as $key => $label):
            if (empty($domain[$key])) continue;
        ?>
        <div>
            <div class="text-muted text-small"><?= $label ?></div>
            <div class="text-mono" style="margin-top:4px;"><?= htmlspecialchars($domain[$key]) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Acciones Rápidas</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="/ad-manager/pages/users.php" class="btn btn-primary">👥 Usuarios</a>
        <a href="/ad-manager/pages/groups.php" class="btn btn-secondary">🔗 Grupos</a>
        <a href="/ad-manager/pages/ous.php" class="btn btn-secondary">📁 Unidades Org.</a>
        <a href="/ad-manager/pages/computers.php" class="btn btn-secondary">🖥 Equipos</a>
        <a href="/ad-manager/pages/search.php" class="btn btn-secondary">🔍 Buscar</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
