<?php
$pageTitle = 'Grupos';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';
Auth::requireLogin();

$api      = new ApiClient();
$ouFilter = $_GET['ou'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $redirect = fn($t,$m) => (header("Location: /ad-manager/pages/groups.php?$t=".urlencode($m)) ?: exit());

    if ($action === 'create') {
        $r = $api->sendAction('POST', '/grupos', [
            'name'             => $_POST['name'],
            'sam_account_name' => $_POST['sam_account_name'] ?? $_POST['name'],
            'scope'            => $_POST['scope']    ?? 'Global',
            'category'         => $_POST['category'] ?? 'Security',
            'description'      => $_POST['description'] ?? '',
            'ou_path'          => $_POST['ou_path'],
        ]);
        Auth::log('CREAR_GRUPO', $_POST['name']);
        $r['success'] ? $redirect('success',$r['message']) : $redirect('error',$r['error']);
    }
    elseif ($action === 'delete') {
        $sam = $_POST['sam'];
        $r   = $api->sendAction('DELETE', "/grupos/$sam");
        Auth::log('ELIMINAR_GRUPO', $sam);
        $r['success'] ? $redirect('success',$r['message']) : $redirect('error',$r['error']);
    }
    elseif ($action === 'add_member') {
        $gSam = $_POST['group_sam'];
        $r    = $api->sendAction('POST', "/grupos/$gSam/miembros", ['user_sam' => $_POST['user_sam']]);
        Auth::log('AGREGAR_MIEMBRO', $gSam, $_POST['user_sam']);
        $r['success'] ? $redirect('success',$r['message']) : $redirect('error',$r['error']);
    }
    elseif ($action === 'remove_member') {
        $gSam = $_POST['group_sam'];
        $uSam = $_POST['user_sam'];
        $r    = $api->sendAction('DELETE', "/grupos/$gSam/miembros/$uSam");
        Auth::log('QUITAR_MIEMBRO', $gSam, $uSam);
        $r['success'] ? $redirect('success',$r['message']) : $redirect('error',$r['error']);
    }
}

$groupsR = $api->getData('/grupos', $ouFilter ? ['ou' => $ouFilter] : []);
$groups  = $groupsR['data'] ?? [];
$ousR    = $api->getData('/ous');
$ous     = $ousR['data'] ?? [];
$usersR  = $api->getData('/usuarios');
$allUsers= $usersR['data'] ?? [];

$pageActions = '<button class="btn btn-primary" onclick="openModal(\'modalNewGroup\')">+ Nuevo Grupo</button>';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Filtro OU -->
<div class="card" style="padding:14px 20px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <select name="ou" class="form-control" style="max-width:360px" onchange="this.form.submit()">
            <option value="">— Todos los grupos —</option>
            <?php foreach ($ous as $ou): ?>
            <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>"
                <?= $ouFilter === $ou['DistinguishedName'] ? 'selected':'' ?>>
                <?= htmlspecialchars($ou['Name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchInput" placeholder="Filtrar…" class="form-control" style="max-width:200px">
    </form>
</div>

<div class="card">
    <?php if (empty($groups)): ?>
    <div class="empty-state"><p>No se encontraron grupos.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="groupsTable">
            <thead><tr>
                <th>Nombre</th><th>SAM</th><th>Tipo</th><th>Ámbito</th>
                <th>Miembros</th><th>Descripción</th><th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
            <tr>
                <td><strong><?= htmlspecialchars($g['Name']) ?></strong></td>
                <td class="text-mono"><?= htmlspecialchars($g['SamAccountName'] ?? '') ?></td>
                <td><span class="badge <?= ($g['GroupCategory']??'')==='Security' ? 'badge-blue':'badge-orange' ?>"><?= htmlspecialchars($g['GroupCategory']??'') ?></span></td>
                <td><span class="badge badge-gray"><?= htmlspecialchars($g['GroupScope']??'') ?></span></td>
                <td>
                    <button class="btn btn-ghost btn-sm"
                        onclick="loadMembers('<?= htmlspecialchars($g['SamAccountName']) ?>','<?= htmlspecialchars($g['Name']) ?>')">
                        👥 <?= (int)($g['MemberCount'] ?? 0) ?>
                    </button>
                </td>
                <td class="text-small text-muted"><?= htmlspecialchars($g['Description']??'') ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-ghost btn-sm btn-icon"
                            onclick="setAddMember('<?= htmlspecialchars($g['SamAccountName']) ?>','<?= htmlspecialchars($g['Name']) ?>');openModal('modalAddMember')"
                            title="Agregar miembro">➕</button>
                        <form method="POST" style="display:inline" id="dg_<?= md5($g['SamAccountName']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="sam"    value="<?= htmlspecialchars($g['SamAccountName']) ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon"
                                onclick="confirmDelete('¿Eliminar grupo «<?= addslashes($g['Name']) ?>»?','dg_<?= md5($g['SamAccountName']) ?>')">🗑</button>
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

<!-- MODAL: Nuevo Grupo -->
<div class="modal-overlay" id="modalNewGroup">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">Nuevo Grupo</span><button class="btn-close" onclick="closeModal('modalNewGroup')">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SAM Account Name *</label>
                        <input type="text" name="sam_account_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="category" class="form-control">
                            <option value="Security">Security</option>
                            <option value="Distribution">Distribution</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ámbito</label>
                        <select name="scope" class="form-control">
                            <option value="Global">Global</option>
                            <option value="DomainLocal">Domain Local</option>
                            <option value="Universal">Universal</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Ubicación (OU) *</label>
                    <select name="ou_path" class="form-control" required>
                        <option value="">— Selecciona OU —</option>
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewGroup')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Grupo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Agregar Miembro -->
<div class="modal-overlay" id="modalAddMember">
    <div class="modal" style="max-width:420px">
        <div class="modal-header"><span class="modal-title">Agregar Miembro</span><button class="btn-close" onclick="closeModal('modalAddMember')">✕</button></div>
        <form method="POST">
            <input type="hidden" name="action"    value="add_member">
            <input type="hidden" name="group_sam" id="addMemberGroupSam">
            <div class="modal-body">
                <p class="text-muted text-small" style="margin-bottom:12px">Grupo: <strong id="addMemberGroupName" class="text-mono"></strong></p>
                <div class="form-group">
                    <label class="form-label">Usuario *</label>
                    <select name="user_sam" class="form-control" required>
                        <option value="">— Selecciona usuario —</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= htmlspecialchars($u['SamAccountName']) ?>">
                            <?= htmlspecialchars(($u['DisplayName'] ?: $u['Name']) . ' (' . $u['SamAccountName'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddMember')">Cancelar</button>
                <button type="submit" class="btn btn-success">Agregar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ver Miembros -->
<div class="modal-overlay" id="modalMembers">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Miembros: <span id="membersGroupName"></span></span>
            <button class="btn-close" onclick="closeModal('modalMembers')">✕</button>
        </div>
        <div class="modal-body"><div id="membersContent"><div class="loading">Cargando…</div></div></div>
    </div>
</div>

<script>
filterTable('searchInput','groupsTable');
function setAddMember(sam, name) {
    document.getElementById('addMemberGroupSam').value = sam;
    document.getElementById('addMemberGroupName').textContent = name;
}
function loadMembers(sam, name) {
    document.getElementById('membersGroupName').textContent = name;
    document.getElementById('membersContent').innerHTML = '<div class="loading">Cargando miembros…</div>';
    openModal('modalMembers');
    fetch(`/api/grupos/${encodeURIComponent(sam)}/miembros`)
        .then(r => r.json())
        .then(res => {
            const data = res.data || [];
            if (!data.length) {
                document.getElementById('membersContent').innerHTML = '<p class="text-muted text-small">Este grupo no tiene miembros.</p>';
                return;
            }
            let html = '<table style="width:100%"><thead><tr><th>Nombre</th><th>SAM</th><th>Tipo</th><th></th></tr></thead><tbody>';
            data.forEach(m => {
                html += `<tr>
                    <td>${m.Name}</td>
                    <td class="text-mono">${m.SamAccountName}</td>
                    <td><span class="badge badge-gray">${m.objectClass}</span></td>
                    <td>
                        <form method="POST" action="/ad-manager/pages/groups.php">
                            <input type="hidden" name="action"    value="remove_member">
                            <input type="hidden" name="group_sam" value="${sam}">
                            <input type="hidden" name="user_sam"  value="${m.SamAccountName}">
                            <button type="submit" class="btn btn-ghost btn-sm"
                                onclick="return confirm('¿Quitar a ${m.Name}?')">Quitar</button>
                        </form>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('membersContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('membersContent').innerHTML = '<p style="color:var(--danger)">Error al cargar miembros.</p>';
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
