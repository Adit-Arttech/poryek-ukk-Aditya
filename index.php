<?php
require_once 'config.php';
check_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-home"></i> Dashboard</h2>
                <div class="header-actions">
                    <span class="badge badge-info"><?php echo date('d F Y H:i:s'); ?></span>
                </div>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Statistik -->
            <div class="stats-container">
                <?php
                // Total kendaraan parkir
                $query1 = "SELECT COUNT(*) as total FROM transaksi_parkir WHERE status = 'masuk'";
                $result1 = mysqli_query($conn, $query1);
                $total_parkir = mysqli_fetch_assoc($result1)['total'] ?? 0;
                
                // Pendapatan hari ini
                $query2 = "SELECT SUM(biaya) as total FROM transaksi_parkir 
                          WHERE DATE(waktu_keluar) = CURDATE() AND status = 'keluar'";
                $result2 = mysqli_query($conn, $query2);
                $pendapatan_hari_ini = mysqli_fetch_assoc($result2)['total'] ?? 0;
                
                // Total kendaraan
                $query3 = "SELECT COUNT(*) as total FROM kendaraan";
                $result3 = mysqli_query($conn, $query3);
                $total_kendaraan = mysqli_fetch_assoc($result3)['total'] ?? 0;
                
                // Total transaksi hari ini
                $query4 = "SELECT COUNT(*) as total FROM transaksi_parkir WHERE DATE(waktu_masuk) = CURDATE()";
                $result4 = mysqli_query($conn, $query4);
                $transaksi_hari_ini = mysqli_fetch_assoc($result4)['total'] ?? 0;
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Kendaraan Parkir</h3>
                        <div class="number"><?php echo $total_parkir; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pendapatan Hari Ini</h3>
                        <div class="number"><?php echo format_rupiah($pendapatan_hari_ini); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Kendaraan</h3>
                        <div class="number"><?php echo $total_kendaraan; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Transaksi Hari Ini</h3>
                        <div class="number"><?php echo $transaksi_hari_ini; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Area Parkir -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Status Area Parkir</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Nama Area</th>
                                <th>Kapasitas</th>
                                <th>Terisi</th>
                                <th>Tersedia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM area_parkir ORDER BY kode_area";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tersedia = $row['kapasitas'] - $row['terisi'];
                                $status_class = $row['status'] == 'Tersedia' ? 'badge-success' : 
                                              ($row['status'] == 'Penuh' ? 'badge-danger' : 'badge-warning');
                                echo "<tr>";
                                echo "<td><strong>{$row['kode_area']}</strong></td>";
                                echo "<td>{$row['nama_area']}</td>";
                                echo "<td>{$row['kapasitas']}</td>";
                                echo "<td>{$row['terisi']}</td>";
                                echo "<td>$tersedia</td>";
                                echo "<td><span class='badge $status_class'>{$row['status']}</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
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