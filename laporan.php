<?php
require_once 'config.php';
check_login();

// HANYA OWNER YANG BISA AKSES LAPORAN
if (!can_view_report()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya owner yang dapat melihat laporan.';
    redirect('index.php');
}

// Filter tanggal
$start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : date('Y-m-d');
$jenis_kendaraan = isset($_GET['jenis_kendaraan']) ? clean_input($_GET['jenis_kendaraan']) : '';

// Query laporan
$conditions = ["tp.status = 'keluar'"];
if ($jenis_kendaraan && $jenis_kendaraan != 'all') {
    $conditions[] = "k.jenis_kendaraan = '$jenis_kendaraan'";
}

// Filter berdasarkan tanggal
$date_column = "DATE(tp.waktu_keluar)";
$conditions[] = "$date_column BETWEEN '$start_date' AND '$end_date'";

$where = "WHERE " . implode(' AND ', $conditions);

// Query transaksi
$query = "SELECT 
            $date_column as tanggal,
            COUNT(*) as total_transaksi,
            SUM(tp.biaya) as total_pendapatan,
            SUM(CASE WHEN k.jenis_kendaraan = 'mobil' THEN 1 ELSE 0 END) as total_mobil,
            SUM(CASE WHEN k.jenis_kendaraan = 'motor' THEN 1 ELSE 0 END) as total_motor
          FROM transaksi_parkir tp
          JOIN kendaraan k ON tp.kendaraan_id = k.id
          $where
          GROUP BY $date_column
          ORDER BY tanggal DESC";

$result = mysqli_query($conn, $query);

// Total keseluruhan
$query_total = "SELECT 
                 COUNT(*) as total_transaksi,
                 SUM(tp.biaya) as total_pendapatan,
                 SUM(CASE WHEN k.jenis_kendaraan = 'mobil' THEN 1 ELSE 0 END) as total_mobil,
                 SUM(CASE WHEN k.jenis_kendaraan = 'motor' THEN 1 ELSE 0 END) as total_motor
               FROM transaksi_parkir tp
               JOIN kendaraan k ON tp.kendaraan_id = k.id
               $where";
               
$result_total = mysqli_query($conn, $query_total);
$total = mysqli_fetch_assoc($result_total) ?? ['total_transaksi' => 0, 'total_pendapatan' => 0, 'total_mobil' => 0, 'total_motor' => 0];

// Hitung hari
$days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
$avg_per_day = $total['total_pendapatan'] ? ($total['total_pendapatan'] / $days) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           STYLE KHUSUS UNTUK HALAMAN LAPORAN
        ============================================ */
        
        /* Header Laporan */
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .report-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .report-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #4CAF50;
        }
        
        .filter-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-section .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-section .form-group {
            margin-bottom: 0;
        }
        
        .filter-section .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
            font-size: 14px;
        }
        
        .filter-section .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .filter-section .form-control:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .stat-card:nth-child(1)::before { background: #4CAF50; }
        .stat-card:nth-child(2)::before { background: #2196F3; }
        .stat-card:nth-child(3)::before { background: #FF9800; }
        .stat-card:nth-child(4)::before { background: #9C27B0; }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-content h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-content .number {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.2;
        }
        
        /* Table Styling */
        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: white;
            border: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #495057;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .table tbody tr {
            transition: background 0.2s;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table-striped tbody tr:nth-child(odd) {
            background-color: #fafafa;
        }
        
        .table tfoot {
            background: #f8f9fa;
        }
        
        .table tfoot td {
            font-weight: 700;
            color: #2c3e50;
            font-size: 15px;
            padding: 18px 20px;
        }
        
        /* Statistik Section */
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 25px;
        }
        
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .stat-box h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-box p {
            margin: 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .stat-box strong {
            color: #2c3e50;
            font-size: 16px;
        }
        
        /* Export Buttons */
        .export-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 3px solid #4CAF50;
        }
        
        .export-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .export-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-excel {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }
        
        .btn-pdf {
            background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
            color: white;
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .filter-section .form-row {
                grid-template-columns: 1fr;
            }
            
            .statistics-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .card-header-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .export-btn {
                width: 100%;
                justify-content: center;
            }
            
            .table th,
            .table td {
                padding: 12px 15px;
                font-size: 13px;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, 
            .header-actions, 
            .filter-section, 
            .export-section,
            .no-print,
            .btn {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            body {
                background: white !important;
                font-size: 12px !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin-bottom: 10px !important;
            }
            
            .table th {
                background: #f5f5f5 !important;
                color: #000 !important;
                border-bottom: 2px solid #000 !important;
            }
            
            .table td {
                border-bottom: 1px solid #ddd !important;
            }
            
            .stat-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .report-header {
                background: white !important;
                color: black !important;
                border-bottom: 2px solid #000 !important;
            }
        }
        
        /* Custom Scrollbar */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Status Colors */
        .text-success {
            color: #4CAF50 !important;
        }
        
        .text-danger {
            color: #f44336 !important;
        }
        
        .text-warning {
            color: #FF9800 !important;
        }
        
        .text-info {
            color: #2196F3 !important;
        }
        
        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }
        
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }
        
        /* Weekend Highlight */
        .weekend {
            background-color: #fff8e1 !important;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }
        
        .empty-state h4 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
        }
        
        /* Form Validation */
        .error-input {
            border-color: #f44336 !important;
            background: #fff5f5 !important;
        }
        
        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <!-- Header -->
            <div class="report-header">
                <h2><i class="fas fa-chart-bar"></i> Laporan Parkir</h2>
                <p>Analisis dan statistik transaksi parkir</p>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filter Laporan</h3>
                <form method="GET" action="" id="filterForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-start"></i> Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo $start_date; ?>" 
                                   max="<?php echo date('Y-m-d'); ?>"
                                   data-tooltip="Pilih tanggal mulai periode">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-end"></i> Tanggal Akhir</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo $end_date; ?>" 
                                   max="<?php echo date('Y-m-d'); ?>"
                                   data-tooltip="Pilih tanggal akhir periode">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-car"></i> Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" class="form-control" data-tooltip="Filter berdasarkan jenis kendaraan">
                                <option value="">Semua Jenis</option>
                                <option value="mobil" <?php echo $jenis_kendaraan == 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                                <option value="motor" <?php echo $jenis_kendaraan == 'motor' ? 'selected' : ''; ?>>Motor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" name="filter" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tampilkan Laporan
                        </button>
                        <a href="laporan.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Filter
                        </a>
                        <button type="button" onclick="exportToPDF()" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Ringkasan Statistik -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Pendapatan</h3>
                        <div class="number"><?php echo format_rupiah($total['total_pendapatan']); ?></div>
                        <small>Periode <?php echo floor($days); ?> hari</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Transaksi</h3>
                        <div class="number"><?php echo number_format($total['total_transaksi']); ?></div>
                        <small><?php echo $total['total_transaksi'] > 0 ? round($total['total_transaksi'] / $days, 1) : 0; ?>/hari</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Mobil</h3>
                        <div class="number"><?php echo number_format($total['total_mobil']); ?></div>
                        <small><?php echo $total['total_transaksi'] > 0 ? round(($total['total_mobil'] / $total['total_transaksi']) * 100, 1) : 0; ?>% dari total</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Motor</h3>
                        <div class="number"><?php echo number_format($total['total_motor']); ?></div>
                        <small><?php echo $total['total_transaksi'] > 0 ? round(($total['total_motor'] / $total['total_transaksi']) * 100, 1) : 0; ?>% dari total</small>
                    </div>
                </div>
            </div>
            
            <!-- Tabel Laporan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Detail Laporan Harian</h3>
                    <div class="card-header-actions">
                        <span class="badge badge-info">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>
                        </span>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
                                <th><i class="fas fa-exchange-alt"></i> Transaksi</th>
                                <th><i class="fas fa-car"></i> Mobil</th>
                                <th><i class="fas fa-motorcycle"></i> Motor</th>
                                <th><i class="fas fa-money-bill-wave"></i> Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $grand_total = [
                                'transaksi' => 0,
                                'mobil' => 0,
                                'motor' => 0,
                                'pendapatan' => 0
                            ];
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $grand_total['transaksi'] += $row['total_transaksi'];
                                    $grand_total['mobil'] += $row['total_mobil'];
                                    $grand_total['motor'] += $row['total_motor'];
                                    $grand_total['pendapatan'] += $row['total_pendapatan'];
                                    
                                    $date_class = '';
                                    $day_name = date('l', strtotime($row['tanggal']));
                                    if ($day_name == 'Saturday' || $day_name == 'Sunday') {
                                        $date_class = 'text-warning';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>$no</td>";
                                    echo "<td class='$date_class'>" . date('d-m-Y', strtotime($row['tanggal'])) . "<br><small>" . date('l', strtotime($row['tanggal'])) . "</small></td>";
                                    echo "<td><strong>" . $row['total_transaksi'] . "</strong></td>";
                                    echo "<td class='text-info'>" . $row['total_mobil'] . "</td>";
                                    echo "<td class='text-success'>" . $row['total_motor'] . "</td>";
                                    echo "<td class='text-danger'><strong>" . format_rupiah($row['total_pendapatan']) . "</strong></td>";
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr>";
                                echo "<td colspan='6' class='empty-state'>";
                                echo "<i class='fas fa-inbox'></i>";
                                echo "<h4>Tidak ada data</h4>";
                                echo "<p>Tidak ada data transaksi keluar untuk periode yang dipilih</p>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <td colspan="2"><strong>TOTAL</strong></td>
                                <td><strong><?php echo number_format($grand_total['transaksi']); ?></strong></td>
                                <td class="text-info"><strong><?php echo number_format($grand_total['mobil']); ?></strong></td>
                                <td class="text-success"><strong><?php echo number_format($grand_total['motor']); ?></strong></td>
                                <td class="text-danger"><strong><?php echo format_rupiah($grand_total['pendapatan']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Statistik Tambahan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Analisis Statistik</h3>
                </div>
                <div class="statistics-grid">
                    <div class="stat-box">
                        <h4><i class="fas fa-chart-line"></i> Performa Harian</h4>
                        <p><strong>Rata-rata Pendapatan/Hari:</strong> <?php echo format_rupiah($avg_per_day); ?></p>
                        <p><strong>Rata-rata Transaksi/Hari:</strong> <?php echo $days > 0 ? round($total['total_transaksi'] / $days, 1) : 0; ?></p>
                        <p><strong>Total Hari:</strong> <?php echo floor($days); ?> hari</p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><i class="fas fa-percentage"></i> Persentase</h4>
                        <p><strong>Komposisi Mobil:</strong> 
                            <?php echo $total['total_transaksi'] > 0 ? round(($total['total_mobil'] / $total['total_transaksi']) * 100, 1) : 0; ?>%
                        </p>
                        <p><strong>Komposisi Motor:</strong> 
                            <?php echo $total['total_transaksi'] > 0 ? round(($total['total_motor'] / $total['total_transaksi']) * 100, 1) : 0; ?>%
                        </p>
                        <p><strong>Rasio Mobil:Motor:</strong> 
                            <?php echo $total['total_motor'] > 0 ? round($total['total_mobil'] / $total['total_motor'], 2) : 'N/A'; ?>
                        </p>
                    </div>
                    
                    <div class="stat-box">
                        <h4><i class="fas fa-info-circle"></i> Informasi Periode</h4>
                        <p><strong>Periode Laporan:</strong><br>
                            <?php echo date('d F Y', strtotime($start_date)); ?> - <?php echo date('d F Y', strtotime($end_date)); ?>
                        </p>
                        <p><strong>Dihasilkan pada:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                        <p><strong>User:</strong> <?php echo $_SESSION['nama_lengkap'] ?? 'Unknown'; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Export Section -->
            <div class="export-section no-print">
                <h3><i class="fas fa-download"></i> Export Laporan</h3>
                <p>Download laporan dalam berbagai format untuk keperluan dokumentasi</p>
                
                <div class="export-buttons">
                    <button onclick="exportToExcel()" class="export-btn btn-excel">
                        <i class="fas fa-file-excel"></i> Export ke Excel
                    </button>
                    
                    <button onclick="window.print()" class="export-btn btn-print">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    
                    <button onclick="exportToPDF()" class="export-btn btn-pdf">
                        <i class="fas fa-file-pdf"></i> Export ke PDF
                    </button>
                    
                    <a href="export_csv.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&jenis_kendaraan=<?php echo $jenis_kendaraan; ?>" 
                       class="export-btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </div>
        </main>
                
    </div>
    
    <script>
    // Fungsi untuk export
    function exportToExcel() {
        let params = new URLSearchParams();
        params.append('start_date', '<?php echo $start_date; ?>');
        params.append('end_date', '<?php echo $end_date; ?>');
        params.append('jenis_kendaraan', '<?php echo $jenis_kendaraan; ?>');
        params.append('export', 'excel');
        
        window.location.href = 'export_laporan.php?' + params.toString();
    }
    
    function exportToPDF() {
        window.open('export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&jenis_kendaraan=<?php echo $jenis_kendaraan; ?>', '_blank');
    }
    
    // Validasi tanggal
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        const filterForm = document.getElementById('filterForm');
        
        if (startDate && endDate) {
            // Set min/max dates
            startDate.min = '2024-01-01';
            startDate.max = '<?php echo date("Y-m-d"); ?>';
            endDate.min = '2024-01-01';
            endDate.max = '<?php echo date("Y-m-d"); ?>';
            
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
            });
            
            endDate.addEventListener('change', function() {
                startDate.max = this.value;
            });
        }
        
        // Form submission dengan validasi
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                if (startDate.value > endDate.value) {
                    e.preventDefault();
                    alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
                    startDate.focus();
                    return false;
                }
                
                if (!startDate.value || !endDate.value) {
                    e.preventDefault();
                    alert('Harap isi tanggal mulai dan tanggal akhir!');
                    return false;
                }
            });
        }
        
        // Auto-hide alerts setelah 5 detik
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Highlight weekend dates
        const dateCells = document.querySelectorAll('td');
        dateCells.forEach(cell => {
            if (cell.textContent.includes('Saturday') || cell.textContent.includes('Sunday')) {
                cell.closest('tr').classList.add('weekend');
            }
        });
    });
    
    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Data berhasil disalin ke clipboard!');
        }).catch(err => {
            console.error('Gagal menyalin: ', err);
        });
    }
    </script>
               <div class="login-footer">
            &copy; 2026 Aditya Herlambang Kelas 12 RPL <br> Smk Binainformatika
        </div>
</body>
</html>