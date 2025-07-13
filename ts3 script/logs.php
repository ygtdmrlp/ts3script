<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM activities");
$total_activities = $stmt->fetchColumn();
$total_pages = ceil($total_activities / $per_page);

// Get activities with pagination
$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM activities a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute();
$activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loglar - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Sistem Logları</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-alt"></i> Aktivite Logları (<?php echo $total_activities; ?> kayıt)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Henüz log kaydı bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Kullanıcı</th>
                                            <th>İşlem</th>
                                            <th>Detay</th>
                                            <th>IP Adresi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                            <td>
                                                <?php if ($activity['username']): ?>
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($activity['username']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sistem</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $action_icons = [
                                                    'login' => 'fas fa-sign-in-alt text-success',
                                                    'logout' => 'fas fa-sign-out-alt text-warning',
                                                    'kick_client' => 'fas fa-boot text-warning',
                                                    'ban_client' => 'fas fa-ban text-danger',
                                                    'create_channel' => 'fas fa-plus text-success',
                                                    'delete_channel' => 'fas fa-trash text-danger',
                                                    'create_user' => 'fas fa-user-plus text-success',
                                                    'update_user' => 'fas fa-user-edit text-primary',
                                                    'delete_user' => 'fas fa-user-times text-danger'
                                                ];
                                                $action_names = [
                                                    'login' => 'Giriş',
                                                    'logout' => 'Çıkış',
                                                    'kick_client' => 'Kullanıcı Atma',
                                                    'ban_client' => 'Kullanıcı Yasaklama',
                                                    'create_channel' => 'Kanal Oluşturma',
                                                    'delete_channel' => 'Kanal Silme',
                                                    'create_user' => 'Kullanıcı Oluşturma',
                                                    'update_user' => 'Kullanıcı Güncelleme',
                                                    'delete_user' => 'Kullanıcı Silme'
                                                ];
                                                ?>
                                                <i class="<?php echo $action_icons[$activity['action']] ?? 'fas fa-cog'; ?>"></i>
                                                <?php echo $action_names[$activity['action']] ?? $activity['action']; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($activity['ip_address']); ?></code>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Log sayfaları">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Önceki</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Sonraki</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 