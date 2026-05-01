<?php
$pageTitle = 'Usuarios de la Aplicación';
require_once __DIR__ . '/../includes/auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../includes/database.php';

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role      = $_POST['role'] ?? 'operator';

        if ($username && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $db->prepare("INSERT INTO app_users (username, password, full_name, role) VALUES (?,?,?,?)")
                   ->execute([$username, $hash, $full_name, $role]);
                Auth::log('CREAR_APP_USER', $username, 'Nuevo usuario de aplicación creado');
                header('Location: /ad-manager/pages/app_users.php?success=' . urlencode('Usuario creado correctamente'));
            } catch (Exception $e) {
                header('Location: /ad-manager/pages/app_users.php?error=' . urlencode('Error: el usuario ya existe o datos inválidos'));
            }
        } else {
            header('Location: /ad-manager/pages/app_users.php?error=' . urlencode('Usuario y contraseña son requeridos'));
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $currentUser = Auth::getCurrentUser();
        if ($id === (int)$currentUser['id']) {
            header('Location: /ad-manager/pages/app_users.php?error=' . urlencode('No puedes eliminar tu propio usuario'));
            exit;
        }
        $db->prepare("DELETE FROM app_users WHERE id = ?")->execute([$id]);
        Auth::log('ELIMINAR_APP_USER', "id:$id", 'Usuario de aplicación eliminado');
        header('Location: /ad-manager/pages/app_users.php?success=' . urlencode('Usuario eliminado'));
        exit;
    }

    if ($action === 'change_password') {
        $id       = (int)$_POST['id'];
        $password = $_POST['new_password'] ?? '';
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE app_users SET password = ? WHERE id = ?")->execute([$hash, $id]);
            Auth::log('CAMBIAR_PASS_APP_USER', "id:$id", 'Contraseña de usuario de app actualizada');
            header('Location: /ad-manager/pages/app_users.php?success=' . urlencode('Contraseña actualizada'));
        } else {
            header('Location: /ad-manager/pages/app_users.php?error=' . urlencode('La contraseña no puede estar vacía'));
        }
        exit;
    }
}

$users = $db->query("SELECT * FROM app_users ORDER BY created_at DESC")->fetchAll();
$currentUser = Auth::getCurrentUser();

$pageActions = '<button class="btn btn-primary" onclick="openModal(\'modalNewAppUser\')">
    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/></svg>
    Nuevo Usuario
</button>';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="alert alert-warning">
    ⚠ Estos son los usuarios que pueden acceder a esta aplicación web, no son usuarios de Active Directory.
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Rol</th>
                    <th>Último Acceso</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td class="text-mono text-muted">#<?= $u['id'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                    <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                    <span class="badge badge-blue" style="margin-left:6px;">Tú</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['full_name'] ?? '') ?></td>
                <td>
                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-orange' : 'badge-gray' ?>">
                        <?= $u['role'] ?>
                    </span>
                </td>
                <td class="text-small text-muted"><?= $u['last_login'] ? htmlspecialchars(substr($u['last_login'], 0, 16)) : 'Nunca' ?></td>
                <td class="text-small text-muted"><?= htmlspecialchars(substr($u['created_at'], 0, 10)) ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-ghost btn-sm btn-icon" title="Cambiar contraseña"
                                onclick="setChangePassUser(<?= $u['id'] ?>,'<?= htmlspecialchars($u['username']) ?>');openModal('modalChangePass')">
                            🔑
                        </button>
                        <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                        <form method="POST" style="display:inline" id="delusr_<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" title="Eliminar"
                                    onclick="confirmDelete('¿Eliminar al usuario \'<?= htmlspecialchars(addslashes($u['username'])) ?>\'?','delusr_<?= $u['id'] ?>')">
                                🗑
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: Nuevo Usuario App -->
<div class="modal-overlay" id="modalNewAppUser">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <span class="modal-title">Nuevo Usuario de Aplicación</span>
            <button class="btn-close" onclick="closeModal('modalNewAppUser')">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario *</label>
                    <input type="text" name="username" class="form-control" required placeholder="operador1">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Juan Pérez">
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña *</label>
                    <input type="text" name="password" class="form-control" required placeholder="Contraseña123">
                </div>
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-control">
                        <option value="operator">Operador — puede gestionar AD</option>
                        <option value="admin">Administrador — acceso completo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewAppUser')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Cambiar Contraseña -->
<div class="modal-overlay" id="modalChangePass">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <span class="modal-title">Cambiar Contraseña</span>
            <button class="btn-close" onclick="closeModal('modalChangePass')">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="id" id="changePassId">
            <div class="modal-body">
                <p class="text-muted text-small" style="margin-bottom:12px;">Usuario: <strong id="changePassUsername" class="text-mono"></strong></p>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña *</label>
                    <input type="text" name="new_password" class="form-control" required placeholder="NuevaContraseña123">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalChangePass')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
function setChangePassUser(id, username) {
    document.getElementById('changePassId').value = id;
    document.getElementById('changePassUsername').textContent = username;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
