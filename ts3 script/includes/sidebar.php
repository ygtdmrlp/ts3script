<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'channels.php' ? 'active' : ''; ?>" href="channels.php">
                    <i class="fas fa-hashtag"></i> Kanallar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bans.php' ? 'active' : ''; ?>" href="bans.php">
                    <i class="fas fa-ban"></i> Yasaklar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                    <i class="fas fa-list-alt"></i> Loglar
                </a>
            </li>
        </ul>

        <?php if ($_SESSION['role'] == 'admin'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Yönetim</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-user-cog"></i> Kullanıcı Yönetimi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'test_connection.php' ? 'active' : ''; ?>" href="test_connection.php">
                    <i class="fas fa-vial"></i> Bağlantı Testi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'server_settings.php' ? 'active' : ''; ?>" href="server_settings.php">
                    <i class="fas fa-server"></i> Sunucu Ayarları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>" href="backup.php">
                    <i class="fas fa-download"></i> Yedekleme
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                    <i class="fas fa-chart-line"></i> İstatistikler
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</nav> 