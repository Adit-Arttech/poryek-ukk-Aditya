<?php
require_once 'config.php';
check_login();

// HANYA ADMIN YANG BISA AKSES
if (!can_edit_tarif()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengelola tarif.';
    redirect('index.php');
}

// Update tarif
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = clean_input($_POST['id']);
    $tarif_per_jam = str_replace('.', '', clean_input($_POST['tarif_per_jam']));
    
    if (empty($tarif_per_jam) || !is_numeric($tarif_per_jam)) {
        $_SESSION['error'] = 'Tarif harus angka yang valid!';
    } else {
        // HANYA UPDATE kolom yang ada di database: tarif_per_jam dan updated_at
        $query = "UPDATE tarif_parkir SET 
                  tarif_per_jam = '$tarif_per_jam',
                  updated_at = NOW()
                  WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            log_aktivitas("Mengupdate tarif parkir ID: $id", 'tarif_parkir', $id);
            $_SESSION['success'] = "Tarif berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui tarif: " . mysqli_error($conn);
        }
    }
    redirect('tarif.php');
}

// Ambil data tarif
$query = "SELECT * FROM tarif_parkir ORDER BY id";
$result = mysqli_query($conn, $query);
$tarif = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tarif[$row['jenis_kendaraan']] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarif Parkir - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tarif-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .tarif-card h1 {
            font-size: 48px;
            margin: 20px 0;
        }
        
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin-top: 20px;
        }
        
        .example-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-top: 20px;
        }
        
        .current-tarif {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-tags"></i> Tarif Parkir</h2>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Tarif Mobil -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-car"></i> Tarif Mobil</h3>
                </div>
                <div class="card-body">
                    <div class="tarif-card" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-car fa-3x"></i>
                        <h1 class="current-tarif" style="color: white;"><?php echo format_rupiah($tarif['mobil']['tarif_per_jam'] ?? 5000); ?></h1>
                        <p>per jam</p>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $tarif['mobil']['id'] ?? 1; ?>">
                        
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Tarif per Jam (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="tarif_per_jam" class="form-control rupiah-input" 
                                       value="<?php echo number_format($tarif['mobil']['tarif_per_jam'] ?? 5000, 0, ',', '.'); ?>" 
                                       required>
                            </div>
                            <small class="form-text text-muted">Masukkan tarif parkir untuk mobil per jam</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Tarif Mobil
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Tarif Motor -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-motorcycle"></i> Tarif Motor</h3>
                </div>
                <div class="card-body">
                    <div class="tarif-card" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-motorcycle fa-3x"></i>
                        <h1 class="current-tarif" style="color: white;"><?php echo format_rupiah($tarif['motor']['tarif_per_jam'] ?? 3000); ?></h1>
                        <p>per jam</p>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $tarif['motor']['id'] ?? 2; ?>">
                        
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Tarif per Jam (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="tarif_per_jam" class="form-control rupiah-input" 
                                       value="<?php echo number_format($tarif['motor']['tarif_per_jam'] ?? 3000, 0, ',', '.'); ?>" 
                                       required>
                            </div>
                            <small class="form-text text-muted">Masukkan tarif parkir untuk motor per jam</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Tarif Motor
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Info Perhitungan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Informasi Perhitungan</h3>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <h4><i class="fas fa-calculator"></i> Cara Perhitungan:</h4>
                        <p><strong>1. Tarif per Jam:</strong> Biaya dihitung per jam dengan pembulatan ke atas.</p>
                        <p><strong>2. Sistem Pembulatan:</strong> Setiap kelebihan menit dihitung sebagai 1 jam penuh.</p>
                    </div>
                    
                    <div class="example-box">
                        <h4><i class="fas fa-lightbulb"></i> Contoh Perhitungan:</h4>
                        <ul>
                            <li><strong>Mobil:</strong> Parkir 1 jam 15 menit = 2 jam × Rp <?php echo number_format($tarif['mobil']['tarif_per_jam'] ?? 5000, 0, ',', '.'); ?> = <strong><?php echo format_rupiah(($tarif['mobil']['tarif_per_jam'] ?? 5000) * 2); ?></strong></li>
                            <li><strong>Motor:</strong> Parkir 2 jam 30 menit = 3 jam × Rp <?php echo number_format($tarif['motor']['tarif_per_jam'] ?? 3000, 0, ',', '.'); ?> = <strong><?php echo format_rupiah(($tarif['motor']['tarif_per_jam'] ?? 3000) * 3); ?></strong></li>
                            <li><strong>Mobil:</strong> Parkir 30 menit = 1 jam × Rp <?php echo number_format($tarif['mobil']['tarif_per_jam'] ?? 5000, 0, ',', '.'); ?> = <strong><?php echo format_rupiah($tarif['mobil']['tarif_per_jam'] ?? 5000); ?></strong></li>
                        </ul>
                    </div>
                    
                    <div class="info-box" style="background: #e8f5e9; border-left-color: #4CAF50;">
                        <h4><i class="fas fa-database"></i> Data Saat Ini:</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Jenis Kendaraan</th>
                                <th>Tarif per Jam</th>
                                <th>Update Terakhir</th>
                            </tr>
                            <tr>
                                <td><i class="fas fa-car"></i> Mobil</td>
                                <td><strong><?php echo format_rupiah($tarif['mobil']['tarif_per_jam'] ?? 5000); ?></strong></td>
                                <td><?php echo format_date($tarif['mobil']['updated_at'] ?? date('Y-m-d H:i:s'), 'd/m/Y H:i'); ?></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-motorcycle"></i> Motor</td>
                                <td><strong><?php echo format_rupiah($tarif['motor']['tarif_per_jam'] ?? 3000); ?></strong></td>
                                <td><?php echo format_date($tarif['motor']['updated_at'] ?? date('Y-m-d H:i:s'), 'd/m/Y H:i'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Format input rupiah
    document.querySelectorAll('.rupiah-input').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = new Intl.NumberFormat('id-ID').format(value);
        });
    });
    
    // Submit form handler untuk menghilangkan titik
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const rupiahInputs = this.querySelectorAll('.rupiah-input');
            rupiahInputs.forEach(input => {
                let value = input.value.replace(/\./g, '');
                input.value = value;
            });
        });
    });
    </script>
    
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