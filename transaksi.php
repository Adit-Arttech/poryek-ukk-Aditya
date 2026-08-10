<?php
require_once 'config.php';
check_login();

// HANYA OWNER YANG BISA AKSES REKAP TRANSAKSI
if (!can_view_transactions()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya owner yang dapat melihat rekap transaksi.';
    redirect('index.php');
}

$error = '';
$success = '';

// Filter berdasarkan tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$jenis_kendaraan = isset($_GET['jenis_kendaraan']) ? $_GET['jenis_kendaraan'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$conditions = ["DATE(tp.waktu_masuk) BETWEEN '$start_date' AND '$end_date'"];

if ($jenis_kendaraan && $jenis_kendaraan != 'all') {
    $conditions[] = "k.jenis_kendaraan = '$jenis_kendaraan'";
}

if ($status && $status != 'all') {
    $conditions[] = "tp.status = '$status'";
}

$where = "WHERE " . implode(' AND ', $conditions);

// Query transaksi
$query = "SELECT tp.*, k.nomor_plat, k.jenis_kendaraan, k.merk, k.warna, u.nama_lengkap as operator
          FROM transaksi_parkir tp
          JOIN kendaraan k ON tp.kendaraan_id = k.id
          JOIN users u ON tp.user_id = u.id
          $where
          ORDER BY tp.waktu_masuk DESC";

$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
                 COUNT(*) as total_transaksi,
                 SUM(CASE WHEN tp.status = 'masuk' THEN 1 ELSE 0 END) as total_masuk,
                 SUM(CASE WHEN tp.status = 'keluar' THEN 1 ELSE 0 END) as total_keluar,
                 SUM(tp.biaya) as total_pendapatan
               FROM transaksi_parkir tp
               JOIN kendaraan k ON tp.kendaraan_id = k.id
               $where";

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-history"></i> Rekap Transaksi</h2>
                <div class="header-actions">
                    <span class="badge badge-info">Owner View</span>
                </div>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Statistik -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Transaksi</h3>
                        <div class="number"><?php echo $stats['total_transaksi'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Masuk</h3>
                        <div class="number"><?php echo $stats['total_masuk'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Keluar</h3>
                        <div class="number"><?php echo $stats['total_keluar'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pendapatan</h3>
                        <div class="number"><?php echo format_rupiah($stats['total_pendapatan'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filter -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Transaksi</h3>
                </div>
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" 
                                   class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" 
                                   class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="jenis_kendaraan">Jenis Kendaraan</label>
                            <select id="jenis_kendaraan" name="jenis_kendaraan" class="form-control">
                                <option value="all">Semua Jenis</option>
                                <option value="mobil" <?php echo $jenis_kendaraan == 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                                <option value="motor" <?php echo $jenis_kendaraan == 'motor' ? 'selected' : ''; ?>>Motor</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="all">Semua Status</option>
                                <option value="masuk" <?php echo $status == 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                                <option value="keluar" <?php echo $status == 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="transaksi.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="button" onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </form>
            </div>
            
            <!-- Tabel Transaksi -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Transaksi</h3>
                    <div class="card-header-actions">
                        <span class="badge badge-info">
                            <?php echo mysqli_num_rows($result); ?> transaksi ditemukan
                        </span>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nomor Struk</th>
                                <th>Nomor Plat</th>
                                <th>Jenis</th>
                                <th>Merk</th>
                                <th>Warna</th>
                                <th>Waktu Masuk</th>
                                <th>Waktu Keluar</th>
                                <th>Durasi</th>
                                <th>Biaya</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Operator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $durasi = $row['durasi_parkir'];
                                    $durasi_text = '';
                                    
                                    if ($durasi) {
                                        $jam = floor($durasi / 60);
                                        $menit = $durasi % 60;
                                        $durasi_text = $jam . ' jam ' . $menit . ' mnt';
                                    } elseif ($row['status'] == 'masuk') {
                                        $waktu_masuk = strtotime($row['waktu_masuk']);
                                        $sekarang = time();
                                        $durasi_menit = floor(($sekarang - $waktu_masuk) / 60);
                                        $jam = floor($durasi_menit / 60);
                                        $menit = $durasi_menit % 60;
                                        $durasi_text = $jam . ' jam ' . $menit . ' mnt';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . date('d-m-Y', strtotime($row['waktu_masuk'])) . "</td>";
                                    echo "<td><small>" . ($row['nomor_struk'] ?? '-') . "</small></td>";
                                    echo "<td><strong>" . $row['nomor_plat'] . "</strong></td>";
                                    echo "<td>" . ucfirst($row['jenis_kendaraan']) . "</td>";
                                    echo "<td>" . ($row['merk'] ?? '-') . "</td>";
                                    echo "<td>" . ($row['warna'] ?? '-') . "</td>";
                                    echo "<td>" . format_date($row['waktu_masuk'], 'H:i') . "</td>";
                                    echo "<td>" . ($row['waktu_keluar'] ? format_date($row['waktu_keluar'], 'H:i') : '-') . "</td>";
                                    echo "<td>" . $durasi_text . "</td>";
                                    echo "<td>" . ($row['biaya'] ? format_rupiah($row['biaya']) : '-') . "</td>";
                                    echo "<td>" . ($row['area_parkir'] ?? '-') . "</td>";
                                    echo "<td>";
                                    if ($row['status'] == 'masuk') {
                                        echo "<span class='badge badge-success'>Masuk</span>";
                                    } else {
                                        echo "<span class='badge badge-warning'>Keluar</span>";
                                    }
                                    echo "</td>";
                                    echo "<td><small>" . $row['operator'] . "</small></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='14' style='text-align: center; padding: 20px;'>Tidak ada transaksi</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                </div>
            </div>
            
        </main>
    </div>
    
    <script>
    function exportToExcel() {
        let params = new URLSearchParams();
        params.append('start_date', '<?php echo $start_date; ?>');
        params.append('end_date', '<?php echo $end_date; ?>');
        params.append('jenis_kendaraan', '<?php echo $jenis_kendaraan; ?>');
        params.append('status', '<?php echo $status; ?>');
        params.append('export', 'excel');
        
        window.location.href = 'export_transaksi.php?' + params.toString();
    }
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