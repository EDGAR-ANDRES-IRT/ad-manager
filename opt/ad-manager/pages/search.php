<?php
$pageTitle = 'Buscar en Active Directory';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';
Auth::requireLogin();

$api     = new ApiClient();
$query   = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    $r       = $api->getData('/buscar', ['q' => $query]);
    $results = $r['data'] ?? [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;">
        <input type="text" name="q" class="form-control"
               value="<?= htmlspecialchars($query) ?>"
               placeholder="Buscar usuarios, grupos, equipos…" autofocus style="flex:1">
        <button type="submit" class="btn btn-primary">🔍 Buscar</button>
    </form>
</div>

<?php if ($query !== ''): ?>
<div class="card">
    <div class="card-title">
        Resultados para "<?= htmlspecialchars($query) ?>"
        <?php if (!empty($results)): ?>
        <span class="badge badge-blue" style="margin-left:8px"><?= count($results) ?></span>
        <?php endif; ?>
    </div>
    <?php if (empty($results)): ?>
    <div class="empty-state"><p>Sin resultados para "<?= htmlspecialchars($query) ?>".</p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Tipo</th><th>Nombre</th><th>SAM Account</th><th>Estado</th><th>Distinguished Name</th></tr></thead>
            <tbody>
            <?php foreach ($results as $obj): ?>
            <tr>
                <td>
                    <?php $cls = match($obj['Type']??'') {'Usuario'=>'badge-blue','Grupo'=>'badge-purple','Equipo'=>'badge-orange',default=>'badge-gray'}; ?>
                    <span class="badge <?= $cls ?>"><?= htmlspecialchars($obj['Type']??'') ?></span>
                </td>
                <td><strong><?= htmlspecialchars($obj['DisplayName'] ?: ($obj['Name']??'')) ?></strong></td>
                <td class="text-mono"><?= htmlspecialchars($obj['SamAccountName']??'') ?></td>
                <td><span class="badge <?= $obj['Enabled'] ? 'badge-green':'badge-red' ?>"><?= $obj['Enabled'] ? 'Activo':'Inactivo' ?></span></td>
                <td><span class="dn-text" title="<?= htmlspecialchars($obj['DistinguishedName']??'') ?>"><?= htmlspecialchars($obj['DistinguishedName']??'') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="empty-state" style="margin-top:48px">
    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
    <p>Escribe un término para buscar usuarios, grupos o equipos en el AD.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
