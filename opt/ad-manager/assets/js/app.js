// AD Manager v2 — app.js

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Cerrar modal al hacer click en el overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// Auto-dismiss de alertas tras 4 segundos
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4000);
    });
});

// Confirmación antes de submit de un formulario de borrado
function confirmDelete(message, formId) {
    if (confirm(message || '¿Estás seguro de que deseas eliminar este elemento?')) {
        document.getElementById(formId).submit();
    }
}

// Filtro en tiempo real sobre una tabla
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', function() {
        const q = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// Copiar texto al portapapeles
function copyText(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copiado al portapapeles');
    });
}

// Toast de notificación
function showToast(message, type) {
    type = type || 'success';
    const colors = { success: '#238636', error: '#da3633', info: '#2563eb' };
    const toast = document.createElement('div');
    toast.style.cssText =
        'position:fixed;bottom:24px;right:24px;' +
        'background:' + (colors[type] || colors.success) + ';' +
        'color:white;padding:10px 18px;border-radius:8px;' +
        'font-weight:700;font-size:14px;z-index:9999;' +
        'box-shadow:0 4px 16px rgba(0,0,0,.4);';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
}
