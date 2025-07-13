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
    
    if ($_POST['action'] == 'kick' && isset($_POST['client_id'])) {
        $client_id = (int)$_POST['client_id'];
        $reason = sanitizeInput($_POST['reason'] ?? '');
        
        if (kickClient($client_id, $reason)) {
            logActivity($user_id, 'kick_client', "Client ID: $client_id, Reason: $reason");
            $success = 'Kullanıcı başarıyla atıldı.';
        } else {
            $error = 'Kullanıcı atılırken hata oluştu.';
        }
    } elseif ($_POST['action'] == 'ban' && isset($_POST['client_id'])) {
        $client_id = (int)$_POST['client_id'];
        $reason = sanitizeInput($_POST['reason'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        
        if (banClient($client_id, $reason, $duration)) {
            logActivity($user_id, 'ban_client', "Client ID: $client_id, Reason: $reason, Duration: $duration");
            $success = 'Kullanıcı başarıyla yasaklandı.';
        } else {
            $error = 'Kullanıcı yasaklanırken hata oluştu.';
        }
    }
}

// Get TS3 clients
$clients = getTS3Clients();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcılar - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Kullanıcılar</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Yenile
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users"></i> Çevrimiçi Kullanıcılar (<?php echo count($clients); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($clients)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Şu anda çevrimiçi kullanıcı bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kullanıcı Adı</th>
                                            <th>IP Adresi</th>
                                            <th>Kanal</th>
                                            <th>Bağlantı Süresi</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['clid'] ?? ''); ?></td>
                                            <td>
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($client['client_nickname'] ?? ''); ?>
                                                <?php if (isset($client['client_type']) && $client['client_type'] == 1): ?>
                                                    <span class="badge bg-warning">Query</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($client['connection_client_ip'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($client['cid'] ?? ''); ?></td>
                                            <td><?php echo formatUptime($client['client_lastconnected'] ?? 0); ?></td>
                                            <td>
                                                <?php if (isset($client['client_away'])): ?>
                                                    <span class="badge bg-warning">Uzakta</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Çevrimiçi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="showKickModal(<?php echo $client['clid']; ?>, '<?php echo htmlspecialchars($client['client_nickname']); ?>')">
                                                        <i class="fas fa-boot"></i> At
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="showBanModal(<?php echo $client['clid']; ?>, '<?php echo htmlspecialchars($client['client_nickname']); ?>')">
                                                        <i class="fas fa-ban"></i> Yasakla
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

    <!-- Kick Modal -->
    <div class="modal fade" id="kickModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcıyı At</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="kick">
                        <input type="hidden" name="client_id" id="kickClientId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <p>Kullanıcı: <strong id="kickClientName"></strong></p>
                        
                        <div class="mb-3">
                            <label for="kickReason" class="form-label">Sebep (İsteğe bağlı)</label>
                            <textarea class="form-control" id="kickReason" name="reason" rows="3" placeholder="Atma sebebini yazın..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-boot"></i> At
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ban Modal -->
    <div class="modal fade" id="banModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcıyı Yasakla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ban">
                        <input type="hidden" name="client_id" id="banClientId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <p>Kullanıcı: <strong id="banClientName"></strong></p>
                        
                        <div class="mb-3">
                            <label for="banReason" class="form-label">Sebep</label>
                            <textarea class="form-control" id="banReason" name="reason" rows="3" placeholder="Yasaklama sebebini yazın..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="banDuration" class="form-label">Süre (saniye, 0 = kalıcı)</label>
                            <input type="number" class="form-control" id="banDuration" name="duration" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Yasakla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showKickModal(clientId, clientName) {
            document.getElementById('kickClientId').value = clientId;
            document.getElementById('kickClientName').textContent = clientName;
            new bootstrap.Modal(document.getElementById('kickModal')).show();
        }
        
        function showBanModal(clientId, clientName) {
            document.getElementById('banClientId').value = clientId;
            document.getElementById('banClientName').textContent = clientName;
            new bootstrap.Modal(document.getElementById('banModal')).show();
        }
    </script>
</body>
</html> 