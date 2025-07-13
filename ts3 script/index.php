<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get TS3 server status
$ts3_status = getTS3ServerStatus();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Server Status -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-server"></i> Sunucu Durumu
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($ts3_status['online']): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> Sunucu Çevrimiçi
                                    </div>
                                    <p><strong>IP:</strong> <?php echo htmlspecialchars($ts3_status['ip']); ?></p>
                                    <p><strong>Port:</strong> <?php echo htmlspecialchars($ts3_status['port']); ?></p>
                                    <p><strong>Bağlı Kullanıcı:</strong> <?php echo htmlspecialchars($ts3_status['clients']); ?></p>
                                    <?php if (isset($ts3_status['max_clients']) && $ts3_status['max_clients'] > 0): ?>
                                        <p><strong>Maksimum Kullanıcı:</strong> <?php echo htmlspecialchars($ts3_status['max_clients']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($ts3_status['uptime']) && $ts3_status['uptime'] > 0): ?>
                                        <p><strong>Çalışma Süresi:</strong> <?php echo formatUptime($ts3_status['uptime']); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle"></i> Sunucu Çevrimdışı
                                    </div>
                                    <?php if (isset($ts3_status['error'])): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <strong>Hata:</strong> <?php echo htmlspecialchars($ts3_status['error']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <p><strong>IP:</strong> <?php echo htmlspecialchars($ts3_status['ip']); ?></p>
                                    <p><strong>Port:</strong> <?php echo htmlspecialchars($ts3_status['port']); ?></p>
                                    
                                    <!-- Troubleshooting Tips -->
                                    <div class="mt-3">
                                        <h6><i class="fas fa-tools"></i> Sorun Giderme:</h6>
                                        <ul class="list-unstyled small">
                                            <li><i class="fas fa-check text-muted"></i> TS3 sunucusunun çalıştığından emin olun</li>
                                            <li><i class="fas fa-check text-muted"></i> Query port (21218) açık olmalı</li>
                                            <li><i class="fas fa-check text-muted"></i> Firewall ayarlarını kontrol edin</li>
                                            <li><i class="fas fa-check text-muted"></i> Query kullanıcı bilgilerini kontrol edin</li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar"></i> İstatistikler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h3 class="text-primary"><?php echo getTotalUsers(); ?></h3>
                                        <p class="text-muted">Toplam Kullanıcı</p>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-success"><?php echo getTotalChannels(); ?></h3>
                                        <p class="text-muted">Toplam Kanal</p>
                                    </div>
                                </div>
                                
                                <?php if ($ts3_status['online']): ?>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-info"><?php echo $ts3_status['clients']; ?></h4>
                                        <p class="text-muted">Çevrimiçi Kullanıcı</p>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-warning"><?php echo $ts3_status['max_clients']; ?></h4>
                                        <p class="text-muted">Maksimum Kapasite</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Son Aktiviteler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Kullanıcı</th>
                                                <th>İşlem</th>
                                                <th>Detay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (getRecentActivities(10) as $activity): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        function refreshStats() {
            location.reload();
        }
    </script>
</body>
</html> 