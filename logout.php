<?php
// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah ada permintaan logout
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    require_once 'config.php';
    
    if (isset($_SESSION['user_id'])) {
        log_aktivitas("Logout dari sistem");
    }
    
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hancurkan session
    session_destroy();
    
    // Redirect ke login
    header("Location: login.php");
    exit();
}

// Jika tidak ada konfirmasi, tampilkan halaman konfirmasi
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logout-icon {
            font-size: 70px;
            color: #f44336;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .logout-icon i {
            filter: drop-shadow(0 5px 15px rgba(244, 67, 54, 0.3));
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .user-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            border-left: 4px solid #667eea;
        }
        
        .user-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .user-info-item:last-child {
            margin-bottom: 0;
        }
        
        .user-info-item i {
            width: 25px;
            color: #667eea;
            font-size: 16px;
        }
        
        .user-info-item span {
            color: #555;
        }
        
        .user-info-item strong {
            color: #333;
            margin-left: 5px;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            flex: 1;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
            color: white;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px 15px;
            margin: 20px 0;
            text-align: left;
            color: #856404;
            font-size: 14px;
        }
        
        .warning-message i {
            color: #f39c12;
            margin-right: 8px;
        }
        
        .session-info {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #dee2e6;
        }
        
        @media (max-width: 480px) {
            .logout-container {
                padding: 25px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h2>Konfirmasi Logout</h2>
        <p>Apakah Anda yakin ingin keluar dari sistem?</p>
        
        <?php
        // Tampilkan informasi user jika session masih ada
        if (isset($_SESSION['user_id'])) {
            ?>
            <div class="user-info">
                <div class="user-info-item">
                    <i class="fas fa-user"></i>
                    <span>Nama:</span>
                    <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Unknown'); ?></strong>
                </div>
                <div class="user-info-item">
                    <i class="fas fa-tag"></i>
                    <span>Username:</span>
                    <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></strong>
                </div>
                <div class="user-info-item">
                    <i class="fas fa-badge"></i>
                    <span>Role:</span>
                    <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Unknown')); ?></strong>
                </div>
            </div>
            <?php
        }
        ?>
        
        <div class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Perhatian:</strong> Setelah logout, Anda akan dialihkan ke halaman login dan perlu memasukkan kembali username dan password untuk mengakses sistem.
        </div>
        
        <div class="button-group">
            <a href="?confirm=yes" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Ya, Logout
            </a>
            <a href="javascript:history.back()" class="btn btn-cancel">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
        
        <div class="session-info">
            <i class="fas fa-clock"></i> 
            Session akan berakhir: <?php echo date('H:i:s'); ?>
        </div>
    </div>
    
    <script>
    // Mencegah user kembali dengan tombol back setelah logout
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Enter untuk logout
        if (e.key === 'Enter') {
            window.location.href = '?confirm=yes';
        }
        
        // Escape untuk batal
        if (e.key === 'Escape') {
            window.history.back();
        }
        
        // Y untuk ya (logout)
        if (e.key === 'y' || e.key === 'Y') {
            window.location.href = '?confirm=yes';
        }
        
        // N untuk tidak (batal)
        if (e.key === 'n' || e.key === 'N') {
            window.history.back();
        }
    });
    
    // Animasi hover untuk tombol
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Auto redirect setelah 60 detik jika tidak ada aksi
    setTimeout(function() {
        if (confirm('Sesi akan berakhir. Apakah Anda ingin tetap login?')) {
            // Refresh halaman untuk reset timer
            window.location.reload();
        } else {
            window.location.href = '?confirm=yes';
        }
    }, 60000); // 60 detik
    </script>
</body>
</html>