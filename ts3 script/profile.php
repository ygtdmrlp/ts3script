<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    $csrf_token = generateCSRFToken();
    
    if ($_POST['action'] == 'update_profile') {
        $email = sanitizeInput($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin.';
        }
        
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.';
        }
        
        // Password change validation
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = 'Mevcut şifrenizi girin.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Mevcut şifreniz yanlış.';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'Yeni şifre en az 6 karakter olmalıdır.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'Yeni şifreler eşleşmiyor.';
            }
        }
        
        if (empty($errors)) {
            $update_data = ['email' => $email];
            
            if (!empty($new_password)) {
                $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            if (updateUser($user_id, $update_data)) {
                logActivity($user_id, 'update_profile', 'Profil güncellendi');
                $success = 'Profil başarıyla güncellendi.';
                
                // Refresh user data
                $user = getUserById($user_id);
            } else {
                $error = 'Profil güncellenirken hata oluştu.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Get user statistics
$user_activities_stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE user_id = ?");
$user_activities_stmt->execute([$user_id]);
$user_activities = $user_activities_stmt->fetchColumn();
$recent_activities = $pdo->prepare("
    SELECT action, details, created_at 
    FROM activities 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_activities->execute([$user_id]);
$activities = $recent_activities->fetchAll();

// Get role information
$role_names = [
    'admin' => 'Yönetici',
    'moderator' => 'Moderatör',
    'user' => 'Kullanıcı'
];

$role_descriptions = [
    'admin' => 'Tüm sistem ayarlarına ve yönetim paneline erişim',
    'moderator' => 'TS3 sunucu yönetimi ve kullanıcı işlemleri',
    'user' => 'Temel görüntüleme ve sınırlı işlemler'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Profil</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Yenile
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

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user"></i> Profil Bilgileri
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-<?php 
                                        echo $user['role'] == 'admin' ? 'danger' : 
                                            ($user['role'] == 'moderator' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo $role_names[$user['role']]; ?>
                                    </span>
                                </div>
                                
                                <div class="text-start">
                                    <p><strong>Kayıt Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                                    <p><strong>Son Giriş:</strong> 
                                        <?php 
                                        if ($user['last_login']) {
                                            echo date('d.m.Y H:i', strtotime($user['last_login']));
                                        } else {
                                            echo '<span class="text-muted">Hiç giriş yapmamış</span>';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Durum:</strong> 
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Role Information -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-shield-alt"></i> Rol Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6><?php echo $role_names[$user['role']]; ?></h6>
                                <p class="text-muted small"><?php echo $role_descriptions[$user['role']]; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Edit Form -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit"></i> Profil Düzenle
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                                <input type="text" class="form-control" id="username" 
                                                       value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                                <small class="text-muted">Kullanıcı adı değiştirilemez</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">E-posta Adresi</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6>Şifre Değiştir (İsteğe bağlı)</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="current_password" class="form-label">Mevcut Şifre</label>
                                                <input type="password" class="form-control" id="current_password" name="current_password">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Not:</strong> Şifre değiştirmek istemiyorsanız şifre alanlarını boş bırakın.
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Profili Güncelle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- User Statistics -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar"></i> Kullanıcı İstatistikleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <h3 class="text-primary"><?php echo $user_activities; ?></h3>
                                        <p class="text-muted">Toplam Aktivite</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h3 class="text-success"><?php echo $user['is_active'] ? 'Aktif' : 'Pasif'; ?></h3>
                                        <p class="text-muted">Hesap Durumu</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h3 class="text-info"><?php echo $role_names[$user['role']]; ?></h3>
                                        <p class="text-muted">Rol</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Son Aktiviteleriniz
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($activities)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz aktivite bulunmuyor.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>İşlem</th>
                                                    <th>Detay</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($activities as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html> 