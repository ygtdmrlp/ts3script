<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    $csrf_token = generateCSRFToken();
    
    if ($_POST['action'] == 'update_settings') {
        $settings = [
            'site_title' => sanitizeInput($_POST['site_title']),
            'ts3_host' => sanitizeInput($_POST['ts3_host']),
            'ts3_port' => (int)$_POST['ts3_port'],
            'ts3_username' => sanitizeInput($_POST['ts3_username']),
            'ts3_password' => $_POST['ts3_password'],
            'ts3_server_port' => (int)$_POST['ts3_server_port'],
            'max_clients' => (int)$_POST['max_clients'],
            'auto_backup' => isset($_POST['auto_backup']) ? '1' : '0',
            'session_timeout' => (int)$_POST['session_timeout'],
            'log_retention_days' => (int)$_POST['log_retention_days']
        ];
        
        $success_count = 0;
        foreach ($settings as $key => $value) {
            if (setSetting($key, $value)) {
                $success_count++;
            }
        }
        
        if ($success_count == count($settings)) {
            logActivity($user_id, 'update_settings', 'Sunucu ayarları güncellendi');
            $success = 'Ayarlar başarıyla güncellendi.';
        } else {
            $error = 'Bazı ayarlar güncellenirken hata oluştu.';
        }
    }
}

// Get current settings
$current_settings = [
    'site_title' => getSetting('site_title') ?: 'TS3 Yönetim Paneli',
    'ts3_host' => getSetting('ts3_host') ?: TS3_HOST,
    'ts3_port' => getSetting('ts3_port') ?: TS3_PORT,
    'ts3_username' => getSetting('ts3_username') ?: TS3_USERNAME,
    'ts3_password' => getSetting('ts3_password') ?: TS3_PASSWORD,
    'ts3_server_port' => getSetting('ts3_server_port') ?: TS3_SERVER_PORT,
    'max_clients' => getSetting('max_clients') ?: '100',
    'auto_backup' => getSetting('auto_backup') ?: '1',
    'session_timeout' => getSetting('session_timeout') ?: '3600',
    'log_retention_days' => getSetting('log_retention_days') ?: '30'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sunucu Ayarları - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Sunucu Ayarları</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="testConnection()">
                            <i class="fas fa-vial"></i> Bağlantı Testi
                        </button>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- General Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog"></i> Genel Ayarlar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="site_title" class="form-label">Site Başlığı</label>
                                        <input type="text" class="form-control" id="site_title" name="site_title" 
                                               value="<?php echo htmlspecialchars($current_settings['site_title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="session_timeout" class="form-label">Oturum Zaman Aşımı (saniye)</label>
                                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                               value="<?php echo $current_settings['session_timeout']; ?>" min="300" max="86400">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TS3 Server Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-server"></i> TS3 Sunucu Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ts3_host" class="form-label">TS3 Host</label>
                                        <input type="text" class="form-control" id="ts3_host" name="ts3_host" 
                                               value="<?php echo htmlspecialchars($current_settings['ts3_host']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ts3_port" class="form-label">Query Port</label>
                                        <input type="number" class="form-control" id="ts3_port" name="ts3_port" 
                                               value="<?php echo $current_settings['ts3_port']; ?>" min="1" max="65535" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ts3_username" class="form-label">Query Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="ts3_username" name="ts3_username" 
                                               value="<?php echo htmlspecialchars($current_settings['ts3_username']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ts3_password" class="form-label">Query Şifresi</label>
                                        <input type="password" class="form-control" id="ts3_password" name="ts3_password" 
                                               value="<?php echo htmlspecialchars($current_settings['ts3_password']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ts3_server_port" class="form-label">Sunucu Port</label>
                                        <input type="number" class="form-control" id="ts3_server_port" name="ts3_server_port" 
                                               value="<?php echo $current_settings['ts3_server_port']; ?>" min="1" max="65535" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_clients" class="form-label">Maksimum Kullanıcı</label>
                                        <input type="number" class="form-control" id="max_clients" name="max_clients" 
                                               value="<?php echo $current_settings['max_clients']; ?>" min="1" max="1000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools"></i> Sistem Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" 
                                                   <?php echo $current_settings['auto_backup'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="auto_backup">
                                                Otomatik Yedekleme
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="log_retention_days" class="form-label">Log Saklama Süresi (gün)</label>
                                        <input type="number" class="form-control" id="log_retention_days" name="log_retention_days" 
                                               value="<?php echo $current_settings['log_retention_days']; ?>" min="1" max="365">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Sıfırla
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Ayarları Kaydet
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testConnection() {
            window.open('test_connection.php', '_blank');
        }
        
        function resetForm() {
            if (confirm('Formu sıfırlamak istediğinizden emin misiniz?')) {
                location.reload();
            }
        }
    </script>
</body>
</html> 