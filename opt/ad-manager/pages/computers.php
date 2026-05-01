<?php
$pageTitle = 'Equipos';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';
Auth::requireLogin();

$api      = new ApiClient();
$ouFilter = $_GET['ou'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $redirect = fn($t,$m) => (header("Location: /ad-manager/pages/computers.php?$t=".urlencode($m)) ?: exit());

    if ($action === 'delete') {
        $sam = $_POST['sam'];
        $r   = $api->sendAction('DELETE', "/equipos/$sam");
        Auth::log('ELIMINAR_EQUIPO', $sam);
        $r['success'] ? $redirect('success', $r['message']) : $redirect('error', $r['error']);
    }
    elseif ($action === 'move') {
        $sam = $_POST['sam'];
        $r   = $api->sendAction('POST', "/equipos/$sam/mover", ['target_ou' => $_POST['target_ou']]);
        Auth::log('MOVER_EQUIPO', $sam);
        $r['success'] ? $redirect('success', $r['message']) : $redirect('error', $r['error']);
    }
}

$computersR = $api->getData('/equipos', $ouFilter ? ['ou' => $ouFilter] : []);
$computers  = $computersR['data'] ?? [];
$ousR       = $api->getData('/ous');
$ous        = $ousR['data'] ?? [];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="padding:14px 20px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <select name="ou" class="form-control" style="max-width:360px" onchange="this.form.submit()">
            <option value="">— Todos los equipos —</option>
            <?php foreach ($ous as $ou): ?>
            <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>"
                <?= $ouFilter === $ou['DistinguishedName'] ? 'selected':'' ?>><?= htmlspecialchars($ou['Name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchInput" placeholder="Filtrar…" class="form-control" style="max-width:200px">
    </form>
</div>

<div class="card">
    <?php if (empty($computers)): ?>
    <div class="empty-state"><p>No se encontraron equipos.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="computersTable">
            <thead><tr><th>Nombre</th><th>Sistema Operativo</th><th>Estado</th><th>Último Acceso</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($computers as $pc): ?>
            <tr>
                <td><strong><?= htmlspecialchars($pc['Name']) ?></strong>
                    <?php if (!empty($pc['Description'])): ?><br><span class="text-small text-muted"><?= htmlspecialchars($pc['Description']) ?></span><?php endif; ?>
                </td>
                <td class="text-small"><?= htmlspecialchars($pc['OperatingSystem'] ?? 'Desconocido') ?></td>
                <td><span class="badge <?= $pc['Enabled'] ? 'badge-green':'badge-red' ?>"><?= $pc['Enabled'] ? 'Habilitado':'Deshabilitado' ?></span></td>
                <td class="text-small text-muted"><?= !empty($pc['LastLogonDate']) ? htmlspecialchars(substr($pc['LastLogonDate'],0,10)) : '—' ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-ghost btn-sm btn-icon" title="Mover"
                            onclick="setMovePC('<?= htmlspecialchars($pc['SamAccountName']) ?>','<?= htmlspecialchars($pc['Name']) ?>');openModal('modalMovePC')">📁</button>
                        <form method="POST" style="display:inline" id="dpc_<?= md5($pc['SamAccountName']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="sam"    value="<?= htmlspecialchars($pc['SamAccountName']) ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon"
                                onclick="confirmDelete('¿Eliminar equipo «<?= addslashes($pc['Name']) ?>» del AD?','dpc_<?= md5($pc['SamAccountName']) ?>')">🗑</button>
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

<div class="modal-overlay" id="modalMovePC">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><span class="modal-title">Mover Equipo</span><button class="btn-close" onclick="closeModal('modalMovePC')">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="sam" id="movePCSam">
            <div class="modal-body">
                <p class="text-muted text-small" style="margin-bottom:12px">Equipo: <strong id="movePCName" class="text-mono"></strong></p>
                <div class="form-group">
                    <label class="form-label">Destino (OU) *</label>
                    <select name="target_ou" class="form-control" required>
                        <option value="">— Selecciona OU —</option>
                        <?php foreach ($ous as $ou): ?>
                        <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>"><?= htmlspecialchars($ou['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalMovePC')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Mover</button>
            </div>
        </form>
    </div>
</div>

<script>
filterTable('searchInput','computersTable');
function setMovePC(sam, name) {
    document.getElementById('movePCSam').value = sam;
    document.getElementById('movePCName').textContent = name;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
