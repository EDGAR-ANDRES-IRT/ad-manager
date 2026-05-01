<?php
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';

Auth::requireLogin();
$api      = new ApiClient();
$ouFilter = $_GET['ou'] ?? '';

// ── Acciones POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $redirect = function(string $type, string $msg) {
        header("Location: /ad-manager/pages/users.php?$type=" . urlencode($msg));
        exit;
    };

    if ($action === 'create') {
        $payload = [
            'sam_account_name' => trim($_POST['sam_account_name']),
            'first_name'       => trim($_POST['first_name']),
            'last_name'        => trim($_POST['last_name'] ?? ''),
            'display_name'     => trim($_POST['display_name'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'department'       => trim($_POST['department'] ?? ''),
            'title'            => trim($_POST['title'] ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'password'         => $_POST['password'],
            'ou_path'          => $_POST['ou_path'],
            'enabled'          => isset($_POST['enabled']),
        ];
        $r = $api->sendAction('POST', '/usuarios', $payload);
        Auth::log('CREAR_USUARIO', $payload['sam_account_name'], $r['message']);
        $r['success']
            ? $redirect('success', $r['message'])
            : $redirect('error',   $r['error']);
    }

    elseif ($action === 'delete') {
        $sam = $_POST['sam'] ?? '';
        $r   = $api->sendAction('DELETE', "/usuarios/$sam");
        Auth::log('ELIMINAR_USUARIO', $sam, $r['message']);
        $r['success']
            ? $redirect('success', $r['message'])
            : $redirect('error',   $r['error']);
    }

    elseif ($action === 'toggle') {
        $sam     = $_POST['sam'] ?? '';
        $current = ($_POST['enabled'] ?? '0') === '1';
        $r = $api->sendAction('POST', "/usuarios/$sam/toggle", ['enabled' => !$current]);
        Auth::log('TOGGLE_USUARIO', $sam, $r['message']);
        $r['success']
            ? $redirect('success', $r['message'])
            : $redirect('error',   $r['error']);
    }

    elseif ($action === 'reset_password') {
        $sam = $_POST['sam'] ?? '';
        $r   = $api->sendAction('POST', "/usuarios/$sam/reset-password",
                                ['password' => $_POST['new_password'] ?? '']);
        Auth::log('RESET_PASS', $sam);
        $r['success']
            ? $redirect('success', 'Contraseña restablecida correctamente')
            : $redirect('error',   $r['error']);
    }

    elseif ($action === 'unlock') {
        $sam = $_POST['sam'] ?? '';
        $r   = $api->sendAction('POST', "/usuarios/$sam/unlock");
        Auth::log('UNLOCK', $sam);
        $r['success']
            ? $redirect('success', 'Cuenta desbloqueada')
            : $redirect('error',   $r['error']);
    }

    elseif ($action === 'move') {
        $sam = $_POST['sam'] ?? '';
        $r   = $api->sendAction('POST', "/usuarios/$sam/mover",
                                ['target_ou' => $_POST['target_ou'] ?? '']);
        Auth::log('MOVER_USUARIO', $sam);
        $r['success']
            ? $redirect('success', 'Usuario movido correctamente')
            : $redirect('error',   $r['error']);
    }
}

// ── Datos para la vista ─────────────────────────────────────
$usersR = $api->getData('/usuarios', $ouFilter ? ['ou' => $ouFilter] : []);
$users  = $usersR['data'] ?? [];
$ousR   = $api->getData('/ous');
$ous    = $ousR['data'] ?? [];

$pageActions = '<button class="btn btn-primary" onclick="openModal(\'modalNewUser\')">
    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/></svg>
    Nuevo Usuario
</button>';

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$usersR['success'] && $usersR['error']): ?>
<div class="alert alert-error">⚠ <?= htmlspecialchars($usersR['error']) ?></div>
<?php endif; ?>

<!-- Filtro OU + búsqueda -->
<div class="card" style="padding:14px 20px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <label class="form-label" style="margin:0">Filtrar por OU:</label>
        <select name="ou" class="form-control" style="max-width:380px" onchange="this.form.submit()">
            <option value="">— Todos los usuarios —</option>
            <?php foreach ($ous as $ou): ?>
            <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>"
                <?= $ouFilter === $ou['DistinguishedName'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ou['Name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchInput" placeholder="Filtrar tabla…"
               class="form-control" style="max-width:200px">
    </form>
</div>

<div class="card">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <p>No se encontraron usuarios<?= $ouFilter ? ' en esta OU' : '' ?>.</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table id="usersTable">
            <thead><tr>
                <th>Nombre</th><th>SAM Account</th><th>Email</th>
                <th>Departamento</th><th>Estado</th><th>Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($u['DisplayName'] ?: ($u['Name'] ?? '')) ?></strong>
                    <?php if (!empty($u['Title'])): ?>
                    <br><span class="text-small text-muted"><?= htmlspecialchars($u['Title']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-mono"><?= htmlspecialchars($u['SamAccountName'] ?? '') ?></td>
                <td class="text-small"><?= htmlspecialchars($u['EmailAddress'] ?? '') ?></td>
                <td class="text-small"><?= htmlspecialchars($u['Department'] ?? '') ?></td>
                <td>
                    <span class="badge <?= $u['Enabled'] ? 'badge-green' : 'badge-red' ?>">
                        <?= $u['Enabled'] ? 'Activo' : 'Desactivado' ?>
                    </span>
                </td>
                <td>
                    <div class="actions-cell">
                        <!-- Toggle -->
                        <form method="POST">
                            <input type="hidden" name="action"  value="toggle">
                            <input type="hidden" name="sam"     value="<?= htmlspecialchars($u['SamAccountName']) ?>">
                            <input type="hidden" name="enabled" value="<?= $u['Enabled'] ? '1' : '0' ?>">
                            <button type="submit" class="btn btn-ghost btn-sm btn-icon"
                                    title="<?= $u['Enabled'] ? 'Desactivar' : 'Activar' ?>">
                                <?= $u['Enabled'] ? '⏸' : '▶' ?>
                            </button>
                        </form>
                        <!-- Reset password -->
                        <button class="btn btn-ghost btn-sm btn-icon" title="Restablecer contraseña"
                                onclick="setResetUser('<?= htmlspecialchars($u['SamAccountName']) ?>');openModal('modalResetPass')">🔑</button>
                        <!-- Unlock -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="unlock">
                            <input type="hidden" name="sam"    value="<?= htmlspecialchars($u['SamAccountName']) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Desbloquear cuenta">🔓</button>
                        </form>
                        <!-- Mover -->
                        <button class="btn btn-ghost btn-sm btn-icon" title="Mover a otra OU"
                                onclick="setMoveUser('<?= htmlspecialchars($u['SamAccountName']) ?>');openModal('modalMoveUser')">📁</button>
                        <!-- Eliminar -->
                        <form method="POST" style="display:inline" id="del_<?= md5($u['SamAccountName']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="sam"    value="<?= htmlspecialchars($u['SamAccountName']) ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Eliminar"
                                    onclick="confirmDelete('¿Eliminar al usuario <?= addslashes(htmlspecialchars($u['SamAccountName'])) ?>?','del_<?= md5($u['SamAccountName']) ?>')">🗑</button>
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

<!-- MODAL: Nuevo Usuario -->
<div class="modal-overlay" id="modalNewUser">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Nuevo Usuario</span>
            <button class="btn-close" onclick="closeModal('modalNewUser')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="first_name" class="form-control" required placeholder="Juan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="last_name" class="form-control" placeholder="Pérez">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">SAM Account Name *</label>
                        <input type="text" name="sam_account_name" class="form-control" required placeholder="jperez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="text" name="password" class="form-control" required placeholder="Abc123!">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="jperez@dominio.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Departamento</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cargo</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad Organizativa *</label>
                    <select name="ou_path" class="form-control" required>
                        <option value="">— Selecciona una OU —</option>
                        <?php foreach ($ous as $ou): ?>
                        <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>">
                            <?= htmlspecialchars($ou['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="enabled" value="1" checked> Cuenta habilitada
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewUser')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Reset Password -->
<div class="modal-overlay" id="modalResetPass">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <span class="modal-title">Restablecer Contraseña</span>
            <button class="btn-close" onclick="closeModal('modalResetPass')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="sam" id="resetSamField">
            <div class="modal-body">
                <p class="text-muted text-small" style="margin-bottom:12px">
                    Usuario: <strong id="resetSamLabel" class="text-mono"></strong>
                </p>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña *</label>
                    <input type="text" name="new_password" class="form-control" required placeholder="NuevaPass123!">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalResetPass')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Restablecer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Mover Usuario -->
<div class="modal-overlay" id="modalMoveUser">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title">Mover Usuario</span>
            <button class="btn-close" onclick="closeModal('modalMoveUser')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="move">
            <input type="hidden" name="sam" id="moveSamField">
            <div class="modal-body">
                <p class="text-muted text-small" style="margin-bottom:12px">
                    Usuario: <strong id="moveSamLabel" class="text-mono"></strong>
                </p>
                <div class="form-group">
                    <label class="form-label">Destino (OU) *</label>
                    <select name="target_ou" class="form-control" required>
                        <option value="">— Selecciona OU destino —</option>
                        <?php foreach ($ous as $ou): ?>
                        <option value="<?= htmlspecialchars($ou['DistinguishedName']) ?>">
                            <?= htmlspecialchars($ou['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalMoveUser')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Mover</button>
            </div>
        </form>
    </div>
</div>

<script>
filterTable('searchInput', 'usersTable');
function setResetUser(sam) {
    document.getElementById('resetSamField').value = sam;
    document.getElementById('resetSamLabel').textContent = sam;
}
function setMoveUser(sam) {
    document.getElementById('moveSamField').value = sam;
    document.getElementById('moveSamLabel').textContent = sam;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
