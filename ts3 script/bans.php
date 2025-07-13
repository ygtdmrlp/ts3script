<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get TS3 bans using the proper Query connection
function getTS3Bans() {
    try {
        // Use the Query port (usually 10011) instead of the server port
        $query_port = getSetting('ts3_port') ?: TS3_PORT;
        $query_host = getSetting('ts3_host') ?: TS3_HOST;
        $query_username = getSetting('ts3_username') ?: TS3_USERNAME;
        $query_password = getSetting('ts3_password') ?: TS3_PASSWORD;
        $server_port = getSetting('ts3_server_port') ?: TS3_SERVER_PORT;
        
        // Connect to Query port
        $socket = fsockopen($query_host, $query_port, $errno, $errstr, 5);
        if (!$socket) {
            error_log("TS3 Query connection failed: $errstr ($errno)");
            return [];
        }
        
        // Login to Query
        fwrite($socket, "login " . $query_username . " " . $query_password . "\n");
        $response = fgets($socket, 1024);
        
        // Check if login was successful
        if (strpos($response, 'error id=0') === false) {
            error_log("TS3 Query login failed: $response");
            fclose($socket);
            return [];
        }
        
        // Select server
        fwrite($socket, "use port=" . $server_port . "\n");
        $response = fgets($socket, 1024);
        
        // Check if server selection was successful
        if (strpos($response, 'error id=0') === false) {
            error_log("TS3 server selection failed: $response");
            fclose($socket);
            return [];
        }
        
        // Get ban list
        fwrite($socket, "banlist\n");
        
        $response = '';
        $timeout = time() + 10; // 10 second timeout
        
        while (!feof($socket) && time() < $timeout) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            
            $response .= $line;
            
            // Check if we've received the complete response
            if (strpos($line, 'error id=0') !== false) {
                break;
            }
        }
        
        fclose($socket);
        
        // Parse ban list
        $bans = [];
        $lines = explode("\n", $response);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'error') !== false) {
                continue;
            }
            
            if (strpos($line, 'banid=') !== false) {
                $ban = [];
                $parts = explode(' ', $line);
                
                foreach ($parts as $part) {
                    if (strpos($part, '=') !== false) {
                        list($key, $value) = explode('=', $part, 2);
                        $ban[trim($key)] = trim($value);
                    }
                }
                
                if (!empty($ban['banid'])) {
                    $bans[] = $ban;
                }
            }
        }
        
        return $bans;
        
    } catch (Exception $e) {
        error_log("TS3 ban list error: " . $e->getMessage());
        return [];
    }
}

$bans = getTS3Bans();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yasaklar - TS3 Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Yasaklar</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="testConnection()">
                            <i class="fas fa-vial"></i> Bağlantı Testi
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-ban"></i> Aktif Yasaklar (<?php echo count($bans); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bans)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aktif yasak bulunmuyor veya TS3 sunucusuna bağlanılamıyor.</p>
                                <p class="text-muted small">
                                    <i class="fas fa-info-circle"></i> 
                                    Bağlantı sorunu yaşıyorsanız "Bağlantı Testi" butonunu kullanın.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ban ID</th>
                                            <th>IP Adresi</th>
                                            <th>Kullanıcı Adı</th>
                                            <th>Sebep</th>
                                            <th>Yasaklayan</th>
                                            <th>Tarih</th>
                                            <th>Süre</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bans as $ban): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ban['banid'] ?? ''); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($ban['ip'] ?? ''); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($ban['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($ban['reason'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($ban['invokername'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                if (isset($ban['created'])) {
                                                    echo date('d.m.Y H:i', $ban['created']);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (isset($ban['duration'])) {
                                                    if ($ban['duration'] == 0) {
                                                        echo '<span class="badge bg-danger">Kalıcı</span>';
                                                    } else {
                                                        echo formatUptime($ban['duration']);
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="unbanUser(<?php echo $ban['banid']; ?>)">
                                                    <i class="fas fa-unlock"></i> Kaldır
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function unbanUser(banId) {
            if (confirm('Bu yasağı kaldırmak istediğinizden emin misiniz?')) {
                // Bu fonksiyon TS3 sunucusuna unban komutu gönderecek
                alert('Ban kaldırma özelliği henüz implement edilmedi.');
            }
        }
        
        function testConnection() {
            window.open('test_connection.php', '_blank');
        }
    </script>
</body>
</html> 