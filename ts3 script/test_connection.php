<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);
if ($user['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$test_results = [];

// Test 1: Basic connection
$test_results['basic_connection'] = [
    'name' => 'Temel Bağlantı Testi',
    'description' => 'TS3 sunucusuna temel bağlantı testi',
    'status' => 'pending'
];

try {
    $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
    if ($socket) {
        $test_results['basic_connection']['status'] = 'success';
        $test_results['basic_connection']['message'] = 'Bağlantı başarılı';
        fclose($socket);
    } else {
        $test_results['basic_connection']['status'] = 'error';
        $test_results['basic_connection']['message'] = "Bağlantı hatası: $errstr (Kod: $errno)";
    }
} catch (Exception $e) {
    $test_results['basic_connection']['status'] = 'error';
    $test_results['basic_connection']['message'] = 'Bağlantı hatası: ' . $e->getMessage();
}

// Test 2: Authentication
$test_results['authentication'] = [
    'name' => 'Kimlik Doğrulama Testi',
    'description' => 'Query kullanıcı bilgileri ile giriş testi',
    'status' => 'pending'
];

try {
    $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
    if ($socket) {
        stream_set_timeout($socket, 3);
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, 'error id=0') !== false || strpos($response, 'error') === false) {
            $test_results['authentication']['status'] = 'success';
            $test_results['authentication']['message'] = 'Kimlik doğrulama başarılı';
        } else {
            $test_results['authentication']['status'] = 'error';
            $test_results['authentication']['message'] = 'Kimlik doğrulama hatası: ' . trim($response);
        }
        fclose($socket);
    } else {
        $test_results['authentication']['status'] = 'error';
        $test_results['authentication']['message'] = 'Bağlantı kurulamadı';
    }
} catch (Exception $e) {
    $test_results['authentication']['status'] = 'error';
    $test_results['authentication']['message'] = 'Kimlik doğrulama hatası: ' . $e->getMessage();
}

// Test 3: Server selection
$test_results['server_selection'] = [
    'name' => 'Sunucu Seçimi Testi',
    'description' => 'TS3 sunucusu seçimi testi',
    'status' => 'pending'
];

try {
    $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
    if ($socket) {
        stream_set_timeout($socket, 3);
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        $response = fgets($socket, 1024);
        
        if (strpos($response, 'error id=0') !== false || strpos($response, 'error') === false) {
            $test_results['server_selection']['status'] = 'success';
            $test_results['server_selection']['message'] = 'Sunucu seçimi başarılı';
        } else {
            $test_results['server_selection']['status'] = 'error';
            $test_results['server_selection']['message'] = 'Sunucu seçimi hatası: ' . trim($response);
        }
        fclose($socket);
    } else {
        $test_results['server_selection']['status'] = 'error';
        $test_results['server_selection']['message'] = 'Bağlantı kurulamadı';
    }
} catch (Exception $e) {
    $test_results['server_selection']['status'] = 'error';
    $test_results['server_selection']['message'] = 'Sunucu seçimi hatası: ' . $e->getMessage();
}

// Test 4: Server info
$test_results['server_info'] = [
    'name' => 'Sunucu Bilgisi Testi',
    'description' => 'Sunucu bilgilerini alma testi',
    'status' => 'pending'
];

try {
    $socket = @fsockopen(TS3_HOST, TS3_PORT, $errno, $errstr, 3);
    if ($socket) {
        stream_set_timeout($socket, 3);
        fwrite($socket, "login " . TS3_USERNAME . " " . TS3_PASSWORD . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "use port=" . TS3_SERVER_PORT . "\n");
        fgets($socket, 1024);
        
        fwrite($socket, "serverinfo\n");
        $response = '';
        $timeout = time() + 5;
        
        while (!feof($socket) && time() < $timeout) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $response .= $line;
            if (strpos($line, 'error') !== false || strpos($line, 'virtualserver_') !== false) {
                break;
            }
        }
        fclose($socket);
        
        if (strpos($response, 'virtualserver_') !== false) {
            $test_results['server_info']['status'] = 'success';
            $test_results['server_info']['message'] = 'Sunucu bilgileri alındı';
        } else {
            $test_results['server_info']['status'] = 'error';
            $test_results['server_info']['message'] = 'Sunucu bilgileri alınamadı';
        }
    } else {
        $test_results['server_info']['status'] = 'error';
        $test_results['server_info']['message'] = 'Bağlantı kurulamadı';
    }
} catch (Exception $e) {
    $test_results['server_info']['status'] = 'error';
    $test_results['server_info']['message'] = 'Sunucu bilgisi hatası: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bağlantı Testi - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">TS3 Bağlantı Testi</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Yeniden Test Et
                        </button>
                    </div>
                </div>

                <!-- Connection Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog"></i> Bağlantı Ayarları
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Host:</strong> <?php echo TS3_HOST; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Query Port:</strong> <?php echo TS3_PORT; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Server Port:</strong> <?php echo TS3_SERVER_PORT; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Username:</strong> <?php echo TS3_USERNAME; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Results -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-vial"></i> Test Sonuçları
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($test_results as $test): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <h6><?php echo htmlspecialchars($test['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($test['description']); ?></small>
                            </div>
                            <div class="col-md-8">
                                <?php if ($test['status'] == 'success'): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($test['message']); ?>
                                    </div>
                                <?php elseif ($test['status'] == 'error'): ?>
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($test['message']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-clock"></i> Test bekleniyor...
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Troubleshooting Guide -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools"></i> Sorun Giderme Rehberi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Bağlantı Sorunları</h6>
                                <ul>
                                    <li>TS3 sunucusunun çalıştığından emin olun</li>
                                    <li>Query port (<?php echo TS3_PORT; ?>) açık olmalı</li>
                                    <li>Firewall ayarlarını kontrol edin</li>
                                    <li>IP adresinin doğru olduğundan emin olun</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Kimlik Doğrulama Sorunları</h6>
                                <ul>
                                    <li>Query kullanıcı adı ve şifresini kontrol edin</li>
                                    <li>Query kullanıcısının yetkilerini kontrol edin</li>
                                    <li>TS3 sunucu ayarlarında Query erişimini kontrol edin</li>
                                    <li>Şifrede özel karakterler varsa dikkatli olun</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 