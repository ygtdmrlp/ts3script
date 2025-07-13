<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle backup actions
if ($_POST && isset($_POST['action'])) {
    $csrf_token = generateCSRFToken();
    
    if ($_POST['action'] == 'create_backup') {
        $backup_type = sanitizeInput($_POST['backup_type']);
        $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '_' . $backup_type . '.sql';
        
        try {
            // Create backup directory if it doesn't exist
            $backup_dir = 'backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            // Generate backup file
            $backup_file = $backup_dir . $backup_name;
            
            if ($backup_type == 'full') {
                // Full backup - all tables
                $tables = ['users', 'activities', 'ts3_clients', 'ts3_channels', 'settings'];
            } else {
                // Partial backup - only essential tables
                $tables = ['users', 'settings'];
            }
            
            $backup_content = "-- TS3 Management Panel Backup\n";
            $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $backup_content .= "-- Type: " . $backup_type . "\n\n";
            
            foreach ($tables as $table) {
                // Get table structure
                $stmt = $pdo->query("SHOW CREATE TABLE $table");
                $create_table = $stmt->fetch();
                $backup_content .= $create_table['Create Table'] . ";\n\n";
                
                // Get table data
                $stmt = $pdo->query("SELECT * FROM $table");
                $rows = $stmt->fetchAll();
                
                if (!empty($rows)) {
                    $backup_content .= "INSERT INTO `$table` VALUES\n";
                    $insert_values = [];
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            return "'" . addslashes($value) . "'";
                        }, $row);
                        $insert_values[] = "(" . implode(', ', $values) . ")";
                    }
                    $backup_content .= implode(",\n", $insert_values) . ";\n\n";
                }
            }
            
            // Save backup file
            if (file_put_contents($backup_file, $backup_content)) {
                logActivity($user_id, 'create_backup', "Backup created: $backup_name");
                $success = "Yedekleme başarıyla oluşturuldu: $backup_name";
            } else {
                $error = 'Yedekleme dosyası oluşturulurken hata oluştu.';
            }
        } catch (Exception $e) {
            $error = 'Yedekleme hatası: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'delete_backup' && isset($_POST['backup_file'])) {
        $backup_file = sanitizeInput($_POST['backup_file']);
        $backup_path = 'backups/' . $backup_file;
        
        if (file_exists($backup_path) && unlink($backup_path)) {
            logActivity($user_id, 'delete_backup', "Backup deleted: $backup_file");
            $success = 'Yedekleme dosyası başarıyla silindi.';
        } else {
            $error = 'Yedekleme dosyası silinirken hata oluştu.';
        }
    }
}

// Get existing backups
$backups = [];
$backup_dir = 'backups/';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    foreach ($files as $file) {
        $filename = basename($file);
        $backups[] = [
            'name' => $filename,
            'size' => filesize($file),
            'date' => filemtime($file),
            'path' => $file
        ];
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Get system statistics
$total_users = getTotalUsers();
$total_activities = $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$total_settings = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yedekleme - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Yedekleme</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" onclick="showCreateModal()">
                            <i class="fas fa-plus"></i> Yeni Yedekleme
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

                <!-- System Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $total_users; ?></h3>
                                <p class="text-muted">Toplam Kullanıcı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $total_activities; ?></h3>
                                <p class="text-muted">Toplam Aktivite</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count($backups); ?></h3>
                                <p class="text-muted">Mevcut Yedekleme</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-download"></i> Yedekleme Dosyaları
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backups)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-download fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Henüz yedekleme dosyası bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dosya Adı</th>
                                            <th>Boyut</th>
                                            <th>Tarih</th>
                                            <th>Tür</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-archive"></i>
                                                <?php echo htmlspecialchars($backup['name']); ?>
                                            </td>
                                            <td><?php echo formatBytes($backup['size']); ?></td>
                                            <td><?php echo date('d.m.Y H:i:s', $backup['date']); ?></td>
                                            <td>
                                                <?php 
                                                if (strpos($backup['name'], '_full_') !== false) {
                                                    echo '<span class="badge bg-primary">Tam Yedekleme</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Kısmi Yedekleme</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?php echo $backup['path']; ?>" class="btn btn-outline-primary" download>
                                                        <i class="fas fa-download"></i> İndir
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="showDeleteModal('<?php echo htmlspecialchars($backup['name']); ?>')">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </button>
                                                </div>
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

    <!-- Create Backup Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Yedekleme Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_backup">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="backupType" class="form-label">Yedekleme Türü</label>
                            <select class="form-control" id="backupType" name="backup_type" required>
                                <option value="full">Tam Yedekleme (Tüm tablolar)</option>
                                <option value="partial">Kısmi Yedekleme (Sadece kullanıcılar ve ayarlar)</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Tam Yedekleme:</strong> Tüm veritabanı tablolarını içerir.<br>
                            <strong>Kısmi Yedekleme:</strong> Sadece kullanıcılar ve ayarlar tablolarını içerir.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Yedekleme Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Backup Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yedekleme Dosyasını Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_backup">
                        <input type="hidden" name="backup_file" id="deleteBackupFile">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Dikkat!</strong> Bu işlem geri alınamaz.
                        </div>
                        
                        <p>Dosya: <strong id="deleteBackupName"></strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showCreateModal() {
            new bootstrap.Modal(document.getElementById('createModal')).show();
        }
        
        function showDeleteModal(backupName) {
            document.getElementById('deleteBackupFile').value = backupName;
            document.getElementById('deleteBackupName').textContent = backupName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html> 