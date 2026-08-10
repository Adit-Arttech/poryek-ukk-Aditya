<?php
// File sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="logo">
        <h1><i class="fas fa-parking"></i> ParkirKu</h1>
    </div>
    
    <div class="user-info">
        <h3><?php echo $_SESSION['nama_lengkap']; ?></h3>
        <?php 
        $role = $_SESSION['role'] ?? '';
        $badge_class = '';
        switch($role) {
            case 'admin': $badge_class = 'badge-danger'; break;
            case 'petugas': $badge_class = 'badge-success'; break;
            case 'owner': $badge_class = 'badge-info'; break;
        }
        ?>
        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($role); ?></span>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        
        <?php if (| $_SESSION['role'] == 'petugas'): ?>
        <li class="nav-item">
            <a href="transaksi_masuk.php" class="nav-link <?php echo $current_page == 'transaksi_masuk.php' ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i> Kendaraan Masuk
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi_keluar.php" class="nav-link <?php echo $current_page == 'transaksi_keluar.php' ? 'active' : ''; ?>">
                <i class="fas fa-sign-out-alt"></i> Kendaraan Keluar
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link <?php echo $current_page == 'transaksi.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Riwayat Transaksi
            </a>
        </li>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a href="kendaraan.php" class="nav-link <?php echo $current_page == 'kendaraan.php' ? 'active' : ''; ?>">
                <i class="fas fa-car"></i> Daftar Kendaraan
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'owner'): ?>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Manajemen User
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'owner'): ?>
        <li class="nav-item">
            <a href="laporan.php" class="nav-link <?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a href="tarif.php" class="nav-link <?php echo $current_page == 'tarif.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill"></i> Tarif Parkir
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profil
            </a>
        </li>
        
        <li class="nav-item">
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</aside>