<?php
require_once 'config/database.php';

// User Management Functions
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function createUser($username, $email, $password, $role = 'user') {
    global $pdo;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hashed_password, $role]);
}

function updateUser($id, $data) {
    global $pdo;
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    $values[] = $id;
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

function deleteUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Authentication Functions
function loginUser($username, $password) {
    $user = getUserByUsername($username);
    if ($user && password_verify($password, $user['password']) && $user['is_active']) {
        // Update last login
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log activity
        logActivity($user['id'], 'login', 'Kullanıcı giriş yaptı');
        
        return $user;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    $user = getUserById($_SESSION['user_id']);
    if ($user['role'] !== 'admin') {
        header('Location: index.php');
        exit();
    }
}

// Activity Logging
function logActivity($user_id, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activities (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

function getRecentActivities($limit = 10) {
    global $pdo;
    $limit = (int)$limit; // Ensure it's an integer
    $stmt = $pdo->prepare("
        SELECT a.*, u.username 
        FROM activities a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT $limit
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// TS3 Server Functions
function getTS3ServerStatus() {
    try {
        // Test connection with timeout
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return [
                'online' => false, 
                'error' => "Bağlantı hatası: $errstr (Kod: $errno)",
                'ip' => TS3_HOST,
                'port' => TS3_SERVER_PORT,
                'clients' => 0,
                'max_clients' => 0,
                'uptime' => 0
            ];
        }
        
        // Set socket timeout
        stream_set_timeout($socket, 3);
        
        // Send login command
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        $response = fgets($socket, 1024);
        
        // Check if login was successful
        if (strpos($response, 'error id=0') === false && strpos($response, 'error') !== false) {
            fclose($socket);
            return [
                'online' => false,
                'error' => 'TS3 Query kimlik doğrulama hatası',
                'ip' => TS3_HOST,
                'port' => TS3_SERVER_PORT,
                'clients' => 0,
                'max_clients' => 0,
                'uptime' => 0
            ];
        }
        
        // Select server
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        $response = fgets($socket, 1024);
        
        // Get server info
        fwrite($socket, "serverinfo\n");
        
        $response = '';
        $timeout = time() + 5; // 5 second timeout
        
        while (!feof($socket) && time() < $timeout) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $response .= $line;
            if (strpos($line, 'error') !== false || strpos($line, 'virtualserver_') !== false) {
                break;
            }
        }
        fclose($socket);
        
        // Parse response
        $lines = explode("\n", $response);
        $info = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $info[trim($key)] = trim($value);
            }
        }
        
        return [
            'online' => true,
            'ip' => TS3_HOST,
            'port' => TS3_SERVER_PORT,
            'clients' => $info['virtualserver_clientsonline'] ?? 0,
            'max_clients' => $info['virtualserver_maxclients'] ?? 0,
            'uptime' => $info['virtualserver_uptime'] ?? 0
        ];
    } catch (Exception $e) {
        return [
            'online' => false, 
            'error' => 'Bağlantı hatası: ' . $e->getMessage(),
            'ip' => TS3_HOST,
            'port' => TS3_SERVER_PORT,
            'clients' => 0,
            'max_clients' => 0,
            'uptime' => 0
        ];
    }
}

function getTS3Clients() {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return [];
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, 'error id=0') === false && strpos($response, 'error') !== false) {
            fclose($socket);
            return [];
        }
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "clientlist\n");
        
        $response = '';
        $timeout = time() + 5;
        
        while (!feof($socket) && time() < $timeout) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $response .= $line;
            if (strpos($line, 'error') !== false) {
                break;
            }
        }
        fclose($socket);
        
        $clients = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (strpos($line, 'clid=') !== false) {
                $client = [];
                $parts = explode(' ', $line);
                foreach ($parts as $part) {
                    if (strpos($part, '=') !== false) {
                        list($key, $value) = explode('=', $part, 2);
                        $client[trim($key)] = trim($value);
                    }
                }
                if (!empty($client['clid'])) {
                    $clients[] = $client;
                }
            }
        }
        
        return $clients;
    } catch (Exception $e) {
        return [];
    }
}

function getTS3Channels() {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return [];
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, 'error id=0') === false && strpos($response, 'error') !== false) {
            fclose($socket);
            return [];
        }
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "channellist\n");
        
        $response = '';
        $timeout = time() + 5;
        
        while (!feof($socket) && time() < $timeout) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $response .= $line;
            if (strpos($line, 'error') !== false) {
                break;
            }
        }
        fclose($socket);
        
        $channels = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (strpos($line, 'cid=') !== false) {
                $channel = [];
                $parts = explode(' ', $line);
                foreach ($parts as $part) {
                    if (strpos($part, '=') !== false) {
                        list($key, $value) = explode('=', $part, 2);
                        $channel[trim($key)] = trim($value);
                    }
                }
                if (!empty($channel['cid'])) {
                    $channels[] = $channel;
                }
            }
        }
        
        return $channels;
    } catch (Exception $e) {
        return [];
    }
}

// TS3 Management Functions
function kickClient($client_id, $reason = '') {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return false;
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "clientkick clid=$client_id reasonid=5 reasonmsg=" . urlencode($reason) . "\n");
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function banClient($client_id, $reason = '', $duration = 0) {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return false;
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "banclient clid=$client_id time=$duration reason=" . urlencode($reason) . "\n");
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function createChannel($name, $parent_id = 0, $max_clients = 0) {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return false;
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "channelcreate channel_name=" . urlencode($name) . " cpid=$parent_id channel_maxclients=$max_clients\n");
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function deleteChannel($channel_id) {
    try {
        $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
        if (!$socket) {
            return false;
        }
        
        stream_set_timeout($socket, 3);
        
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "channeldelete cid=$channel_id force=1\n");
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Statistics Functions
function getTotalUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return $stmt->fetchColumn();
}

function getTotalChannels() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM ts3_channels");
    return $stmt->fetchColumn();
}

function getOnlineUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM ts3_clients WHERE is_online = 1");
    return $stmt->fetchColumn();
}

// Settings Functions
function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

function setSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Utility Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = $days . ' gün';
    if ($hours > 0) $parts[] = $hours . ' saat';
    if ($minutes > 0) $parts[] = $minutes . ' dakika';
    
    return implode(' ', $parts);
}

// Security Functions
function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?> 