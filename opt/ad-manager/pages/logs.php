<?php
$pageTitle = 'Registro de Actividad';
require_once __DIR__ . '/../includes/auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../includes/database.php';

$db = Database::getConnection();

// Limpiar logs (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
    $db->exec("DELETE FROM activity_logs");
    Auth::log('LIMPIAR_LOGS', '', 'Registro de actividad limpiado');
    header('Location: /ad-manager/pages/logs.php?success=' . urlencode('Registro limpiado'));
    exit;
}

// Paginación
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$total = (int)$db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$pages = ceil($total / $limit);

$logs = $db->query("
    SELECT l.*, u.username, u.full_name
    FROM activity_logs l
    LEFT JOIN app_users u ON l.app_user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

$pageActions = '
<form method="POST" style="display:inline" id="clearLogsForm">
    <input type="hidden" name="action" value="clear">
    <button type="button" class="btn btn-danger" onclick="confirmDelete(\'¿Limpiar todo el registro de actividad?\',\'clearLogsForm\')">
        🗑 Limpiar todo
    </button>
</form>';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <span class="text-muted text-small"><?= number_format($total) ?> registros en total</span>
        <span class="text-muted text-small">Página <?= $page ?> de <?= max(1,$pages) ?></span>
    </div>

    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
        <p>No hay registros de actividad aún.</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Usuario App</th>
                    <th>Acción</th>
                    <th>Objetivo</th>
                    <th>Detalles</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="text-mono text-small"><?= htmlspecialchars(substr($log['created_at'], 0, 16)) ?></td>
                <td>
                    <span class="text-mono"><?= htmlspecialchars($log['username'] ?? 'Sistema') ?></span>
                    <?php if ($log['full_name']): ?>
                    <br><span class="text-small text-muted"><?= htmlspecialchars($log['full_name']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= match(true) {
                        str_contains($log['action'], 'CREAR')    => 'badge-green',
                        str_contains($log['action'], 'ELIMINAR') => 'badge-red',
                        str_contains($log['action'], 'LOGIN')    => 'badge-blue',
                        str_contains($log['action'], 'RESET')    => 'badge-orange',
                        default                                   => 'badge-gray',
                    } ?>">
                        <?= htmlspecialchars($log['action']) ?>
                    </span>
                </td>
                <td class="text-mono text-small"><?= htmlspecialchars($log['target'] ?? '') ?></td>
                <td class="text-small text-muted"><?= htmlspecialchars($log['details'] ?? '') ?></td>
                <td>
                    <span class="badge <?= $log['status'] === 'success' ? 'badge-green' : 'badge-red' ?>">
                        <?= $log['status'] ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($pages > 1): ?>
    <div style="display:flex;gap:8px;margin-top:16px;justify-content:center;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>" class="btn btn-secondary btn-sm">← Anterior</a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
        <a href="?page=<?= $i ?>" class="btn <?= $i===$page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?page=<?= $page+1 ?>" class="btn btn-secondary btn-sm">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
