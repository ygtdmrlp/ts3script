<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle actions
if ($_POST && isset($_POST['action'])) {
    $csrf_token = generateCSRFToken();
    
    if ($_POST['action'] == 'create' && isset($_POST['username'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = sanitizeInput($_POST['role']);
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Tüm alanlar gereklidir.';
        } elseif (getUserByUsername($username)) {
            $error = 'Bu kullanıcı adı zaten kullanılıyor.';
        } else {
            if (createUser($username, $email, $password, $role)) {
                logActivity($user_id, 'create_user', "Username: $username, Role: $role");
                $success = 'Kullanıcı başarıyla oluşturuldu.';
            } else {
                $error = 'Kullanıcı oluşturulurken hata oluştu.';
            }
        }
    } elseif ($_POST['action'] == 'update' && isset($_POST['user_id'])) {
        $update_user_id = (int)$_POST['user_id'];
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $update_data = [
            'email' => $email,
            'role' => $role,
            'is_active' => $is_active
        ];
        
        if (!empty($_POST['password'])) {
            $update_data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        if (updateUser($update_user_id, $update_data)) {
            logActivity($user_id, 'update_user', "User ID: $update_user_id");
            $success = 'Kullanıcı başarıyla güncellendi.';
        } else {
            $error = 'Kullanıcı güncellenirken hata oluştu.';
        }
    } elseif ($_POST['action'] == 'delete' && isset($_POST['user_id'])) {
        $delete_user_id = (int)$_POST['user_id'];
        
        if ($delete_user_id == $user_id) {
            $error = 'Kendinizi silemezsiniz.';
        } else {
            if (deleteUser($delete_user_id)) {
                logActivity($user_id, 'delete_user', "User ID: $delete_user_id");
                $success = 'Kullanıcı başarıyla silindi.';
            } else {
                $error = 'Kullanıcı silinirken hata oluştu.';
            }
        }
    }
}

// Get all users
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Kullanıcı Yönetimi</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" onclick="showCreateModal()">
                            <i class="fas fa-plus"></i> Yeni Kullanıcı
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users-cog"></i> Sistem Kullanıcıları (<?php echo count($users); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>E-posta</th>
                                        <th>Rol</th>
                                        <th>Durum</th>
                                        <th>Son Giriş</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td>
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($u['username']); ?>
                                            <?php if ($u['id'] == $user_id): ?>
                                                <span class="badge bg-primary">Siz</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <?php
                                            $role_badges = [
                                                'admin' => 'bg-danger',
                                                'moderator' => 'bg-warning',
                                                'user' => 'bg-secondary'
                                            ];
                                            $role_names = [
                                                'admin' => 'Yönetici',
                                                'moderator' => 'Moderatör',
                                                'user' => 'Kullanıcı'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $role_badges[$u['role']]; ?>">
                                                <?php echo $role_names[$u['role']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($u['last_login']) {
                                                echo date('d.m.Y H:i', strtotime($u['last_login']));
                                            } else {
                                                echo '<span class="text-muted">Hiç giriş yapmamış</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="showEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($u['id'] != $user_id): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="showDeleteModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user">Kullanıcı</option>
                                <option value="moderator">Moderatör</option>
                                <option value="admin">Yönetici</option>
                            </select>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="editUserId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="editUsername" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">Şifre (Boş bırakın değiştirmek istemiyorsanız)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Rol</label>
                            <select class="form-control" id="editRole" name="role" required>
                                <option value="user">Kullanıcı</option>
                                <option value="moderator">Moderatör</option>
                                <option value="admin">Yönetici</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editIsActive" name="is_active">
                                <label class="form-check-label" for="editIsActive">
                                    Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcıyı Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Dikkat!</strong> Bu işlem geri alınamaz.
                        </div>
                        
                        <p>Kullanıcı: <strong id="deleteUserName"></strong></p>
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
        
        function showEditModal(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            document.getElementById('editIsActive').checked = user.is_active == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function showDeleteModal(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html> 