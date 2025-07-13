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
            'app_name' => sanitizeInput($_POST['app_name']),
            'app_description' => sanitizeInput($_POST['app_description']),
            'app_version' => sanitizeInput($_POST['app_version']),
            'timezone' => sanitizeInput($_POST['timezone']),
            'date_format' => sanitizeInput($_POST['date_format']),
            'time_format' => sanitizeInput($_POST['time_format']),
            'language' => sanitizeInput($_POST['language']),
            'theme' => sanitizeInput($_POST['theme']),
            'pagination_limit' => (int)$_POST['pagination_limit'],
            'session_timeout' => (int)$_POST['session_timeout'],
            'max_login_attempts' => (int)$_POST['max_login_attempts'],
            'lockout_duration' => (int)$_POST['lockout_duration'],
            'enable_registration' => isset($_POST['enable_registration']) ? '1' : '0',
            'require_email_verification' => isset($_POST['require_email_verification']) ? '1' : '0',
            'enable_notifications' => isset($_POST['enable_notifications']) ? '1' : '0',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'maintenance_message' => sanitizeInput($_POST['maintenance_message']),
            'contact_email' => sanitizeInput($_POST['contact_email']),
            'admin_email' => sanitizeInput($_POST['admin_email'])
        ];
        
        $success_count = 0;
        foreach ($settings as $key => $value) {
            if (setSetting($key, $value)) {
                $success_count++;
            }
        }
        
        if ($success_count == count($settings)) {
            logActivity($user_id, 'update_settings', 'Uygulama ayarları güncellendi');
            $success = 'Ayarlar başarıyla güncellendi.';
        } else {
            $error = 'Bazı ayarlar güncellenirken hata oluştu.';
        }
    }
}

// Get current settings
$current_settings = [
    'app_name' => getSetting('app_name') ?: 'TS3 Yönetim Paneli',
    'app_description' => getSetting('app_description') ?: 'Teamspeak3 Sunucu Yönetim Sistemi',
    'app_version' => getSetting('app_version') ?: '1.0.0',
    'timezone' => getSetting('timezone') ?: 'Europe/Istanbul',
    'date_format' => getSetting('date_format') ?: 'd.m.Y',
    'time_format' => getSetting('time_format') ?: 'H:i:s',
    'language' => getSetting('language') ?: 'tr',
    'theme' => getSetting('theme') ?: 'default',
    'pagination_limit' => getSetting('pagination_limit') ?: '20',
    'session_timeout' => getSetting('session_timeout') ?: '3600',
    'max_login_attempts' => getSetting('max_login_attempts') ?: '5',
    'lockout_duration' => getSetting('lockout_duration') ?: '900',
    'enable_registration' => getSetting('enable_registration') ?: '0',
    'require_email_verification' => getSetting('require_email_verification') ?: '0',
    'enable_notifications' => getSetting('enable_notifications') ?: '1',
    'maintenance_mode' => getSetting('maintenance_mode') ?: '0',
    'maintenance_message' => getSetting('maintenance_message') ?: 'Sistem bakımda. Lütfen daha sonra tekrar deneyin.',
    'contact_email' => getSetting('contact_email') ?: 'contact@example.com',
    'admin_email' => getSetting('admin_email') ?: 'admin@example.com'
];

// Get available timezones
$timezones = DateTimeZone::listIdentifiers();
$languages = [
    'tr' => 'Türkçe',
    'en' => 'English',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español'
];
$themes = [
    'default' => 'Varsayılan',
    'dark' => 'Koyu Tema',
    'light' => 'Açık Tema',
    'blue' => 'Mavi Tema'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uygulama Ayarları - TS3 Yönetim Paneli</title>
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
                    <h1 class="h2">Uygulama Ayarları</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Sıfırla
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
                    
                    <!-- Application Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Uygulama Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="app_name" class="form-label">Uygulama Adı</label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" 
                                               value="<?php echo htmlspecialchars($current_settings['app_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="app_version" class="form-label">Uygulama Sürümü</label>
                                        <input type="text" class="form-control" id="app_version" name="app_version" 
                                               value="<?php echo htmlspecialchars($current_settings['app_version']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="app_description" class="form-label">Uygulama Açıklaması</label>
                                <textarea class="form-control" id="app_description" name="app_description" rows="3"><?php echo htmlspecialchars($current_settings['app_description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Localization Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-globe"></i> Yerelleştirme Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Zaman Dilimi</label>
                                        <select class="form-control" id="timezone" name="timezone" required>
                                            <?php foreach ($timezones as $tz): ?>
                                                <option value="<?php echo $tz; ?>" <?php echo $current_settings['timezone'] == $tz ? 'selected' : ''; ?>>
                                                    <?php echo $tz; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Dil</label>
                                        <select class="form-control" id="language" name="language" required>
                                            <?php foreach ($languages as $code => $name): ?>
                                                <option value="<?php echo $code; ?>" <?php echo $current_settings['language'] == $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_format" class="form-label">Tarih Formatı</label>
                                        <select class="form-control" id="date_format" name="date_format" required>
                                            <option value="d.m.Y" <?php echo $current_settings['date_format'] == 'd.m.Y' ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                            <option value="Y-m-d" <?php echo $current_settings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                            <option value="m/d/Y" <?php echo $current_settings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                            <option value="d/m/Y" <?php echo $current_settings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="time_format" class="form-label">Saat Formatı</label>
                                        <select class="form-control" id="time_format" name="time_format" required>
                                            <option value="H:i:s" <?php echo $current_settings['time_format'] == 'H:i:s' ? 'selected' : ''; ?>>24 Saat</option>
                                            <option value="h:i:s A" <?php echo $current_settings['time_format'] == 'h:i:s A' ? 'selected' : ''; ?>>12 Saat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interface Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-palette"></i> Arayüz Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="theme" class="form-label">Tema</label>
                                        <select class="form-control" id="theme" name="theme" required>
                                            <?php foreach ($themes as $code => $name): ?>
                                                <option value="<?php echo $code; ?>" <?php echo $current_settings['theme'] == $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pagination_limit" class="form-label">Sayfa Başına Öğe Sayısı</label>
                                        <input type="number" class="form-control" id="pagination_limit" name="pagination_limit" 
                                               value="<?php echo $current_settings['pagination_limit']; ?>" min="5" max="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt"></i> Güvenlik Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="session_timeout" class="form-label">Oturum Zaman Aşımı (saniye)</label>
                                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                               value="<?php echo $current_settings['session_timeout']; ?>" min="300" max="86400">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_login_attempts" class="form-label">Maksimum Giriş Denemesi</label>
                                        <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                               value="<?php echo $current_settings['max_login_attempts']; ?>" min="3" max="10">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lockout_duration" class="form-label">Hesap Kilitleme Süresi (saniye)</label>
                                        <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                               value="<?php echo $current_settings['lockout_duration']; ?>" min="300" max="3600">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable_registration" name="enable_registration" 
                                                   <?php echo $current_settings['enable_registration'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_registration">
                                                Kullanıcı Kaydına İzin Ver
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog"></i> Sistem Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" 
                                                   <?php echo $current_settings['enable_notifications'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_notifications">
                                                Bildirimleri Etkinleştir
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                   <?php echo $current_settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="maintenance_mode">
                                                Bakım Modu
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Bakım Modu Mesajı</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="2"><?php echo htmlspecialchars($current_settings['maintenance_message']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-envelope"></i> İletişim Ayarları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_email" class="form-label">İletişim E-posta</label>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                               value="<?php echo htmlspecialchars($current_settings['contact_email']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="admin_email" class="form-label">Admin E-posta</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>">
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
        function resetForm() {
            if (confirm('Formu sıfırlamak istediğinizden emin misiniz?')) {
                location.reload();
            }
        }
        
        // Theme preview
        document.getElementById('theme').addEventListener('change', function() {
            const theme = this.value;
            // Here you could add theme preview functionality
            console.log('Selected theme:', theme);
        });
        
        // Maintenance mode warning
        document.getElementById('maintenance_mode').addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('Bakım modu etkinleştirildiğinde sadece admin kullanıcılar sisteme erişebilir. Devam etmek istiyor musunuz?')) {
                    this.checked = false;
                }
            }
        });
    </script>
</body>
</html> 