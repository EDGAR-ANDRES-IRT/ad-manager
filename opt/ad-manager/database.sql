-- Base de datos para AD Manager
CREATE DATABASE IF NOT EXISTS ad_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ad_manager;

-- Tabla de usuarios de la aplicación web
CREATE TABLE IF NOT EXISTS app_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(200),
    role ENUM('admin', 'operator') DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Tabla de logs de actividad
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_user_id INT,
    action VARCHAR(255) NOT NULL,
    target VARCHAR(255),
    details TEXT,
    status ENUM('success', 'error') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_user_id) REFERENCES app_users(id) ON DELETE SET NULL
);

-- Usuario administrador por defecto (contraseña: admin123)
INSERT INTO app_users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin');

CREATE USER 'admanager'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'admanager'@'localhost';
FLUSH PRIVILEGES;