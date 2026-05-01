<?php
$pageTitle = 'Unidades Organizativas';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';
Auth::requireLogin();

$api = new ApiClient();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $redirect = fn($t,$m) => (header("Location: /ad-manager/pages/ous.php?$t=".urlencode($m)) ?: exit());

    if ($action === 'create') {
        $r = $api->sendAction('POST', '/ous', [
            'name'        => $_POST['name'],
            'path'        => $_POST['parent_path'],
            'description' => $_POST['description'] ?? '',
        ]);
        Auth::log('CREAR_OU', $_POST['name']);
        $r['success'] ? $redirect('success', $r['message']) : $redirect('error', $r['error']);
    }
    elseif ($action === 'delete') {
        $r = $api->sendAction('DELETE', '/ous', ['dn' => $_POST['dn']]);
        Auth::log('ELIMINAR_OU', $_POST['dn']);
        $r['success'] ? $redirect('success', $r['message']) : $redirect('error', $r['error']);
    }
}

$ousR    = $api->getData('/ous');
$ous     = $ousR['data'] ?? [];
$domainR = $api->getData('/dominio');
$domainData = is_array($domainR['data'] ?? []) && isset($domainR['data'][0])
              ? $domainR['data'][0]
              : ($domainR['data'] ?? []);

// Construir DN del dominio desde DNSRoot si es necesario
$domainDN = '';
if (!empty($domainData['DNSRoot'])) {
    $parts    = explode('.', $domainData['DNSRoot']);
    $domainDN = implode(',', array_map(fn($p) => "DC=$p", $parts));
}

$pageActions = '<button class="btn btn-primary" onclick="openModal(\'modalNewOU\')">+ Nueva OU</button>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <?php if (empty($ous)): ?>
    <div class="empty-state"><p>No se encontraron unidades organizativas.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="ouTable">
            <thead><tr><th>Nombre</th><th>Distinguished Name</th><th>Descripción</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($ous as $ou): ?>
            <tr>
                <td>
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;color:#e3b341;flex-shrink:0"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <strong><?= htmlspecialchars($ou['Name']) ?></strong>
                    </span>
                </td>
                <td><span class="dn-text" title="<?= htmlspecialchars($ou['DistinguishedName']) ?>"><?= htmlspecialchars($ou['DistinguishedName']) ?></span></td>
                <td class="text-small text-muted"><?= htmlspecialchars($ou['Description'] ?? '') ?></td>
                <td>
                    <div class="actions-cell">
                        <a href="/ad-manager/pages/users.php?ou=<?= urlencode($ou['DistinguishedName']) ?>" class="btn btn-ghost btn-sm">👥 Usuarios</a>
                        <a href="/ad-manager/pages/groups.php?ou=<?= urlencode($ou['DistinguishedName']) ?>" class="btn btn-ghost btn-sm">🔗 Grupos</a>
                        <button class="btn btn-ghost btn-sm btn-icon" onclick="copyText('<?= addslashes($ou['DistinguishedName']) ?>')" title="Copiar DN">📋</button>
                        <form method="POST" style="display:inline" id="do_<?= md5($ou['DistinguishedName']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="dn"     value="<?= htmlspecialchars($ou['DistinguishedName']) ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon"
                                onclick="confirmDelete('¿Eliminar OU «<?= addslashes($ou['Name']) ?>» y todo su contenido?','do_<?= md5($ou['DistinguishedName']) ?>')">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL: Nueva OU -->
<div class="modal-overlay" id="modalNewOU">
    <div class="modal" style="max-width:500px">
        <div class="modal-header"><span class="modal-title">Nueva Unidad Organizativa</span><button class="btn-close" onclick="closeModal('modalNewOU')">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" class="form-control" required placeholder="TI, Ventas, RRHH…">
                </div>
                <div class="form-group">
                    <label class="form-label">Ubicación padre *</label>
                    <select name="parent_path" class="form-control" required>
                        <?php if ($domainDN): ?>
                        <option value="<?= htmlspecialchars($domainDN) ?>">Raíz — <?= htmlspecialchars($domainDN) ?></option>
                        <?php endif; ?>
                        <?php foreach ($ous as $ou): ?>
                        <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>"><?= htmlspecialchars($ou['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="description" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewOU')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear OU</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
