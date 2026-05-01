<?php
require_once __DIR__ . '/includes/auth.php';
Auth::startSession();
Auth::log('LOGOUT', '', 'Cierre de sesión');
Auth::logout();
