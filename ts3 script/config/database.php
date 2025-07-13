<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ts3_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// TS3 Server configuration
define('TS3_HOST', '217.182.175.212');
define('TS3_PORT', 10011); // Query port (default: 10011)
define('TS3_USERNAME', 'user_643559.2fe50476');
define('TS3_PASSWORD', '6dAxataAjkfjbhhSznF6-58eGE7a5U4P');
define('TS3_SERVER_PORT', 2022); // Server port for clients

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Create tables if they don't exist
function createTables($pdo) {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE
    )");

    // Activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // TS3 Clients table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ts3_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        nickname VARCHAR(100),
        unique_id VARCHAR(50),
        ip_address VARCHAR(45),
        connected_time INT,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_online BOOLEAN DEFAULT FALSE
    )");

    // TS3 Channels table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ts3_channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT NOT NULL,
        channel_name VARCHAR(100),
        parent_id INT,
        max_clients INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@ts3.com', $admin_password, 'admin']);
    }

    // Insert default settings
    $default_settings = [
        ['ts3_host', TS3_HOST],
        ['ts3_port', TS3_PORT],
        ['ts3_username', TS3_USERNAME],
        ['ts3_password', TS3_PASSWORD],
        ['ts3_server_port', TS3_SERVER_PORT],
        ['site_title', 'TS3 Yönetim Paneli'],
        ['max_clients', '100'],
        ['auto_backup', '1']
    ];

    foreach ($default_settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute($setting);
    }
}

// Create tables
createTables($pdo);
?> 