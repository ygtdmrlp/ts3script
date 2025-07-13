<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle actions
if ($_POST && isset($_POST['action'])) {
    $csrf_token = generateCSRFToken();
    
    if ($_POST['action'] == 'create' && isset($_POST['channel_name'])) {
        $channel_name = sanitizeInput($_POST['channel_name']);
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        $max_clients = (int)($_POST['max_clients'] ?? 0);
        
        if (createChannel($channel_name, $parent_id, $max_clients)) {
            logActivity($user_id, 'create_channel', "Channel: $channel_name, Parent: $parent_id");
            $success = 'Kanal başarıyla oluşturuldu.';
        } else {
            $error = 'Kanal oluşturulurken hata oluştu.';
        }
    } elseif ($_POST['action'] == 'delete' && isset($_POST['channel_id'])) {
        $channel_id = (int)$_POST['channel_id'];
        
        if (deleteChannel($channel_id)) {
            logActivity($user_id, 'delete_channel', "Channel ID: $channel_id");
            $success = 'Kanal başarıyla silindi.';
        } else {
            $error = 'Kanal silinirken hata oluştu.';
        }
    }
}

// Get TS3 channels
$channels = getTS3Channels();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanallar - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Kanallar</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Yenile
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showCreateModal()">
                                <i class="fas fa-plus"></i> Yeni Kanal
                            </button>
                        </div>
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

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-hashtag"></i> Kanal Listesi (<?php echo count($channels); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($channels)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-hashtag fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz kanal bulunmuyor.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Kanal Adı</th>
                                                    <th>Üst Kanal</th>
                                                    <th>Maks. Kullanıcı</th>
                                                    <th>Kullanıcı Sayısı</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($channels as $channel): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($channel['cid'] ?? ''); ?></td>
                                                    <td>
                                                        <i class="fas fa-hashtag"></i>
                                                        <?php echo htmlspecialchars($channel['channel_name'] ?? ''); ?>
                                                        <?php if (isset($channel['channel_flag_permanent'])): ?>
                                                            <span class="badge bg-info">Kalıcı</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $parent_id = $channel['cpid'] ?? 0;
                                                        if ($parent_id > 0) {
                                                            foreach ($channels as $parent) {
                                                                if ($parent['cid'] == $parent_id) {
                                                                    echo htmlspecialchars($parent['channel_name']);
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">Ana Kanal</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $max_clients = $channel['channel_maxclients'] ?? 0;
                                                        echo $max_clients > 0 ? $max_clients : 'Sınırsız';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?php echo $channel['total_clients'] ?? 0; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-info" 
                                                                    onclick="showChannelInfo(<?php echo $channel['cid']; ?>)">
                                                                <i class="fas fa-info-circle"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="showDeleteModal(<?php echo $channel['cid']; ?>, '<?php echo htmlspecialchars($channel['channel_name']); ?>')">
                                                                <i class="fas fa-trash"></i>
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
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie"></i> Kanal İstatistikleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h3 class="text-primary"><?php echo count($channels); ?></h3>
                                        <p class="text-muted">Toplam Kanal</p>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-success">
                                            <?php 
                                            $total_clients = 0;
                                            foreach ($channels as $channel) {
                                                $total_clients += $channel['total_clients'] ?? 0;
                                            }
                                            echo $total_clients;
                                            ?>
                                        </h3>
                                        <p class="text-muted">Toplam Kullanıcı</p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6>En Popüler Kanallar</h6>
                                <?php
                                $sorted_channels = $channels;
                                usort($sorted_channels, function($a, $b) {
                                    return ($b['total_clients'] ?? 0) - ($a['total_clients'] ?? 0);
                                });
                                $top_channels = array_slice($sorted_channels, 0, 5);
                                ?>
                                
                                <?php foreach ($top_channels as $channel): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-truncate"><?php echo htmlspecialchars($channel['channel_name']); ?></span>
                                    <span class="badge bg-primary"><?php echo $channel['total_clients'] ?? 0; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Channel Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kanal Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="channelName" class="form-label">Kanal Adı</label>
                            <input type="text" class="form-control" id="channelName" name="channel_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parentId" class="form-label">Üst Kanal (İsteğe bağlı)</label>
                            <select class="form-control" id="parentId" name="parent_id">
                                <option value="0">Ana Kanal</option>
                                <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['cid']; ?>">
                                    <?php echo htmlspecialchars($channel['channel_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="maxClients" class="form-label">Maksimum Kullanıcı (0 = sınırsız)</label>
                            <input type="number" class="form-control" id="maxClients" name="max_clients" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Channel Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kanalı Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="channel_id" id="deleteChannelId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Dikkat!</strong> Bu işlem geri alınamaz. Kanal ve tüm alt kanalları silinecektir.
                        </div>
                        
                        <p>Kanal: <strong id="deleteChannelName"></strong></p>
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
        
        function showDeleteModal(channelId, channelName) {
            document.getElementById('deleteChannelId').value = channelId;
            document.getElementById('deleteChannelName').textContent = channelName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        function showChannelInfo(channelId) {
            // Bu fonksiyon kanal detaylarını göstermek için kullanılabilir
            alert('Kanal ID: ' + channelId + ' detayları burada gösterilecek.');
        }
    </script>
</body>
</html> 