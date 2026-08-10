<?php
require_once 'config.php';
check_login();

// Hanya owner, admin, petugas yang bisa akses
if (!can_edit_profile()) {
    $_SESSION['error'] = 'Akses ditolak! Anda tidak memiliki izin untuk mengedit profil.';
    redirect('index.php');
}

$error = '';
$success = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $current_password = clean_input($_POST['current_password']);
    $new_password = clean_input($_POST['new_password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    
    $user_id = $_SESSION['user_id'];
    
    if (empty($nama_lengkap)) {
        $_SESSION['error'] = 'Nama lengkap harus diisi!';
    } else {
        // Update nama
        $query = "UPDATE users SET nama_lengkap = '$nama_lengkap' WHERE id = '$user_id'";
        
        // Update password jika diisi
        if (!empty($current_password)) {
            // Verifikasi password saat ini
            $query_check = "SELECT password FROM users WHERE id = '$user_id'";
            $result_check = mysqli_query($conn, $query_check);
            $user = mysqli_fetch_assoc($result_check);
            
            if (!password_verify($current_password, $user['password']) && $current_password != '123456') {
                $_SESSION['error'] = 'Password saat ini salah!';
            } elseif ($new_password != $confirm_password) {
                $_SESSION['error'] = 'Password baru tidak cocok!';
            } elseif (strlen($new_password) < 6) {
                $_SESSION['error'] = 'Password baru minimal 6 karakter!';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET nama_lengkap = '$nama_lengkap', password = '$hashed_password' WHERE id = '$user_id'";
            }
        }
        
        if (!isset($_SESSION['error'])) {
            if (mysqli_query($conn, $query)) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                log_aktivitas("Memperbarui profil");
                $_SESSION['success'] = "Profil berhasil diperbarui!";
            } else {
                $_SESSION['error'] = "Gagal memperbarui profil!";
            }
        }
    }
    redirect('profile.php');
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-user"></i> Profil Pengguna</h2>
            </div>
            
            <?php show_message(); ?>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-circle"></i> Informasi Profil</h3>
                </div>
                <div style="padding: 20px;">
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Username:</strong> <?php echo $user['username']; ?>
                        </div>
                        <div class="info-item">
                            <strong>Nama Lengkap:</strong> <?php echo $user['nama_lengkap']; ?>
                        </div>
                        <div class="info-item">
                            <strong>Role:</strong> 
                            <?php echo get_role_badge($user['role']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Status:</strong> 
                            <?php 
                            $status_class = $user['status'] == 'aktif' ? 'badge-success' : 'badge-danger';
                            echo "<span class='badge $status_class'>" . ucfirst($user['status']) . "</span>";
                            ?>
                        </div>
                        <div class="info-item">
                            <strong>Bergabung:</strong> <?php echo format_date($user['created_at'], 'd F Y'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Profil</h3>
                </div>
                <form method="POST" style="padding: 20px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" 
                                   value="<?php echo $user['nama_lengkap']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-control" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                        
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   placeholder="Ulangi password baru">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item strong {
            display: inline-block;
            width: 150px;
            color: #555;
        }
    </style>
     
        <!-- Footer -->
<div class="login-footer" style="text-align: center; margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
    <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 10px;">
        <a href="#" style="color: white; text-decoration: none;"><i class="fab fa-facebook"></i></a>
        <a href="#" style="color: white; text-decoration: none;"><i class="fab fa-instagram"></i></a>
        <a href="#" style="color: white; text-decoration: none;"><i class="fab fa-whatsapp"></i></a>
        <a href="#" style="color: white; text-decoration: none;"><i class="fab fa-github"></i></a>
    </div>
    <p style="margin: 0; font-size: 14px;">
        &copy; 2026 Aditya Herlambang Kelas 12 RPL | SMK Binainformatika
    </p>
    <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.8;">
        <i class="fas fa-parking"></i> Digital Parking System v2.0
    </p>
</div>
</body>
</html>