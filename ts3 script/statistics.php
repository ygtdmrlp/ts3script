<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get date range for filtering
$date_range = isset($_GET['range']) ? $_GET['range'] : '7';
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-$date_range days"));

// Get statistics
$stats = [];

// User statistics
$stats['total_users'] = getTotalUsers();
$stats['active_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$stats['admin_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$stats['moderator_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'moderator'")->fetchColumn();

// Activity statistics
$stats['total_activities'] = $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$recent_activities_stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE DATE(created_at) >= ?");
$recent_activities_stmt->execute([$start_date]);
$stats['recent_activities'] = $recent_activities_stmt->fetchColumn();

// TS3 statistics
$ts3_status = getTS3ServerStatus();
$stats['ts3_online'] = $ts3_status['online'] ? 'Çevrimiçi' : 'Çevrimdışı';
$stats['ts3_clients'] = $ts3_status['clients'] ?? 0;
$stats['ts3_max_clients'] = $ts3_status['max_clients'] ?? 0;

// Channel statistics
$stats['total_channels'] = getTotalChannels();

// Activity breakdown
$activity_types = $pdo->query("
    SELECT action, COUNT(*) as count 
    FROM activities 
    WHERE DATE(created_at) >= '$start_date' 
    GROUP BY action 
    ORDER BY count DESC
")->fetchAll();

// Daily activity chart data
$daily_activities = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM activities 
    WHERE DATE(created_at) >= ? 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$daily_activities->execute([$start_date]);
$chart_data = $daily_activities->fetchAll();

// User registration trend
$user_registrations = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE DATE(created_at) >= ? 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$user_registrations->execute([$start_date]);
$registration_data = $user_registrations->fetchAll();

// Top active users
$top_users = $pdo->prepare("
    SELECT u.username, COUNT(a.id) as activity_count 
    FROM users u 
    LEFT JOIN activities a ON u.id = a.user_id 
    WHERE DATE(a.created_at) >= ? 
    GROUP BY u.id, u.username 
    ORDER BY activity_count DESC 
    LIMIT 10
");
$top_users->execute([$start_date]);
$top_active_users = $top_users->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İstatistikler - TS3 Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">İstatistikler</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select form-select-sm" onchange="changeDateRange(this.value)">
                                <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Son 7 Gün</option>
                                <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Son 30 Gün</option>
                                <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Son 90 Gün</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>

                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $stats['total_users']; ?></h3>
                                <p class="text-muted">Toplam Kullanıcı</p>
                                <small class="text-success"><?php echo $stats['active_users']; ?> aktif</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $stats['total_activities']; ?></h3>
                                <p class="text-muted">Toplam Aktivite</p>
                                <small class="text-info"><?php echo $stats['recent_activities']; ?> son <?php echo $date_range; ?> günde</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo $stats['ts3_clients']; ?></h3>
                                <p class="text-muted">Çevrimiçi Kullanıcı</p>
                                <small class="text-<?php echo $ts3_status['online'] ? 'success' : 'danger'; ?>">
                                    <?php echo $stats['ts3_online']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo $stats['total_channels']; ?></h3>
                                <p class="text-muted">Toplam Kanal</p>
                                <small class="text-muted">TS3 Sunucusu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line"></i> Günlük Aktivite Grafiği
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activityChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie"></i> Aktivite Türleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activityTypeChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users"></i> Kullanıcı Dağılımı
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-danger"><?php echo $stats['admin_users']; ?></h4>
                                        <p class="text-muted">Yönetici</p>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning"><?php echo $stats['moderator_users']; ?></h4>
                                        <p class="text-muted">Moderatör</p>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-secondary"><?php echo $stats['total_users'] - $stats['admin_users'] - $stats['moderator_users']; ?></h4>
                                        <p class="text-muted">Kullanıcı</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-trophy"></i> En Aktif Kullanıcılar
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_active_users)): ?>
                                    <p class="text-muted text-center">Henüz aktivite bulunmuyor.</p>
                                <?php else: ?>
                                    <?php foreach ($top_active_users as $index => $user): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                        <span class="badge bg-success"><?php echo $user['activity_count']; ?> aktivite</span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-server"></i> TS3 Sunucu Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Durum:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $ts3_status['online'] ? 'success' : 'danger'; ?>">
                                                <?php echo $stats['ts3_online']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Host:</strong></td>
                                        <td><?php echo TS3_HOST; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Port:</strong></td>
                                        <td><?php echo TS3_SERVER_PORT; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kullanıcı Sayısı:</strong></td>
                                        <td><?php echo $stats['ts3_clients']; ?> / <?php echo $stats['ts3_max_clients']; ?></td>
                                    </tr>
                                    <?php if ($ts3_status['online'] && isset($ts3_status['uptime'])): ?>
                                    <tr>
                                        <td><strong>Çalışma Süresi:</strong></td>
                                        <td><?php echo formatUptime($ts3_status['uptime']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Sistem Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Sürümü:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>MySQL Sürümü:</strong></td>
                                        <td><?php echo $pdo->query('SELECT VERSION()')->fetchColumn(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sunucu Zamanı:</strong></td>
                                        <td><?php echo date('d.m.Y H:i:s'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Zaman Dilimi:</strong></td>
                                        <td><?php echo date_default_timezone_get(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bellek Kullanımı:</strong></td>
                                        <td><?php echo formatBytes(memory_get_usage(true)); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chart_data, 'date')); ?>,
                datasets: [{
                    label: 'Aktivite Sayısı',
                    data: <?php echo json_encode(array_column($chart_data, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Activity Type Chart
        const typeCtx = document.getElementById('activityTypeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($activity_types, 'action')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($activity_types, 'count')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        function changeDateRange(range) {
            window.location.href = '?range=' + range;
        }
    </script>
</body>
</html> 