<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow w-100">
    <a class="navbar-brand me-0 px-3 flex-shrink-0" href="index.php">
        <i class="fas fa-server"></i> TS3 Yönetim
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="navbar-nav ms-auto">
        <div class="nav-item text-nowrap">
            <div class="dropdown">
                <a class="nav-link px-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user"></i> Profil
                    </a></li>
                    <li><a class="dropdown-item" href="settings.php">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</header> 