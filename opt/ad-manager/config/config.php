<?php
// ============================================================
// CONFIGURACIÓN — AD Manager (Frontend PHP)
// ============================================================

// URL base de la API Flask (misma máquina, nginx hace proxy en /api/)
define('API_BASE', 'http://127.0.0.1:5000/api');

// Base de datos MySQL (solo para sesiones/usuarios de la app web)
define('DB_HOST',    'localhost');
define('DB_NAME',    'ad_manager');
define('DB_USER',    'admanager');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Aplicación
define('APP_NAME',       'AD Manager');
define('APP_VERSION',    '2.0');
define('SESSION_TIMEOUT', 3600);
