<?php
require_once 'config.php';
check_login();

// HANYA OWNER YANG BISA AKSES
if ($_SESSION['role'] != 'owner') {
    $_SESSION['error'] = 'Akses ditolak! Halaman ini khusus untuk owner.';
    redirect('index.php');
}

// Ambil data untuk diagram pendapatan 7 hari terakhir
$query_chart = "SELECT 
                    DATE(waktu_keluar) as tanggal,
                    SUM(biaya) as total_pendapatan,
                    COUNT(*) as total_transaksi
                FROM transaksi_parkir 
                WHERE status = 'keluar' 
                AND waktu_keluar >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(waktu_keluar)
                ORDER BY tanggal ASC";

$result_chart = mysqli_query($conn, $query_chart);

$tanggal = [];
$pendapatan = [];
$transaksi = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $tanggal[] = date('d/m', strtotime($row['tanggal']));
    $pendapatan[] = $row['total_pendapatan'];
    $transaksi[] = $row['total_transaksi'];
}

// Data statistik hari ini
$query_hari_ini = "SELECT 
                    COUNT(*) as total_kendaraan,
                    SUM(CASE WHEN status = 'masuk' THEN 1 ELSE 0 END) as masih_parkir,
                    SUM(CASE WHEN status = 'keluar' THEN 1 ELSE 0 END) as sudah_keluar,
                    SUM(biaya) as pendapatan_hari_ini
                FROM transaksi_parkir 
                WHERE DATE(waktu_masuk) = CURDATE()";

$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$hari_ini = mysqli_fetch_assoc($result_hari_ini);

// Data statistik bulan ini
$query_bulan_ini = "SELECT 
                    COUNT(*) as total_transaksi,
                    SUM(biaya) as total_pendapatan,
                    AVG(biaya) as rata_rata
                FROM transaksi_parkir 
                WHERE status = 'keluar' 
                AND MONTH(waktu_keluar) = MONTH(CURDATE())
                AND YEAR(waktu_keluar) = YEAR(CURDATE())";

$result_bulan_ini = mysqli_query($conn, $query_bulan_ini);
$bulan_ini = mysqli_fetch_assoc($result_bulan_ini);

// Data perbandingan mobil vs motor
$query_perbandingan = "SELECT 
                        k.jenis_kendaraan,
                        COUNT(*) as jumlah,
                        SUM(tp.biaya) as total_pendapatan
                    FROM transaksi_parkir tp
                    JOIN kendaraan k ON tp.kendaraan_id = k.id
                    WHERE tp.status = 'keluar' 
                    AND MONTH(tp.waktu_keluar) = MONTH(CURDATE())
                    AND YEAR(tp.waktu_keluar) = YEAR(CURDATE())
                    GROUP BY k.jenis_kendaraan";

$result_perbandingan = mysqli_query($conn, $query_perbandingan);
$mobil_data = ['jumlah' => 0, 'pendapatan' => 0];
$motor_data = ['jumlah' => 0, 'pendapatan' => 0];

while ($row = mysqli_fetch_assoc($result_perbandingan)) {
    if ($row['jenis_kendaraan'] == 'mobil') {
        $mobil_data = $row;
    } else {
        $motor_data = $row;
    }
}

// Data top 5 area tersibuk
$query_area = "SELECT 
                tp.area_parkir,
                a.nama_area,
                COUNT(*) as total_kunjungan,
                SUM(tp.biaya) as total_pendapatan
            FROM transaksi_parkir tp
            LEFT JOIN area_parkir a ON tp.area_parkir = a.kode_area
            WHERE tp.status = 'keluar'
            AND MONTH(tp.waktu_keluar) = MONTH(CURDATE())
            AND YEAR(tp.waktu_keluar) = YEAR(CURDATE())
            GROUP BY tp.area_parkir
            ORDER BY total_kunjungan DESC
            LIMIT 5";

$result_area = mysqli_query($conn, $query_area);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard Owner Specific Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .dashboard-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-trend {
            margin-top: 10px;
            font-size: 13px;
            color: #27ae60;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            color: #2c3e50;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-header h3 i {
            color: #3498db;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .chart-box h4 {
            color: #34495e;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-box canvas {
            max-height: 300px;
        }
        
        .area-list {
            background: white;
            border-radius: 15px;
            padding: 20px;
        }
        
        .area-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .area-item:last-child {
            border-bottom: none;
        }
        
        .area-rank {
            width: 30px;
            height: 30px;
            background: #3498db;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .area-info {
            flex: 1;
        }
        
        .area-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .area-code {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .area-stats {
            text-align: right;
        }
        
        .area-count {
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 3px;
        }
        
        .area-revenue {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        .date-range {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .kpi-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .kpi-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-chart-line"></i>
                    Owner Dashboard
                </h1>
                <p>Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! Berikut ringkasan performa parkir Anda.</p>
                <div class="date-range" style="display: inline-block; margin-top: 10px;">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Statistik Utama -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($hari_ini['total_kendaraan'] ?? 0); ?></div>
                    <div class="stat-label">Total Kendaraan Hari Ini</div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> 
                        <?php echo ($hari_ini['masih_parkir'] ?? 0); ?> masih parkir
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #229954);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value"><?php echo format_rupiah($hari_ini['pendapatan_hari_ini'] ?? 0); ?></div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i> 
                        <?php echo ($hari_ini['sudah_keluar'] ?? 0); ?> transaksi selesai
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($bulan_ini['total_transaksi'] ?? 0); ?></div>
                    <div class="stat-label">Transaksi Bulan Ini</div>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i> 
                        Rata-rata Rp <?php echo number_format(($bulan_ini['rata_rata'] ?? 0), 0, ',', '.'); ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-value"><?php echo format_rupiah($bulan_ini['total_pendapatan'] ?? 0); ?></div>
                    <div class="stat-label">Total Pendapatan Bulan Ini</div>
                    <div class="stat-trend">
                        <i class="fas fa-percentage"></i> 
                        Mobil: <?php echo number_format(($mobil_data['jumlah'] ?? 0)); ?> | Motor: <?php echo number_format(($motor_data['jumlah'] ?? 0)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Chart Utama - Pendapatan 7 Hari -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>
                        <i class="fas fa-chart-bar"></i>
                        Grafik Pendapatan 7 Hari Terakhir
                    </h3>
                    <span class="badge badge-info">
                        <i class="fas fa-calendar-week"></i> 
                        <?php echo date('d M', strtotime('-6 days')); ?> - <?php echo date('d M Y'); ?>
                    </span>
                </div>
                <canvas id="revenueChart" style="width:100%; max-height:400px;"></canvas>
            </div>
            
            <!-- Grid Charts -->
            <div class="chart-grid">
                <!-- Pie Chart - Perbandingan Kendaraan -->
                <div class="chart-box">
                    <h4>
                        <i class="fas fa-chart-pie" style="color: #e67e22;"></i>
                        Komposisi Kendaraan Bulan Ini
                    </h4>
                    <canvas id="vehicleChart"></canvas>
                    <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px;">
                        <div>
                            <span style="color: #3498db;"><i class="fas fa-circle"></i> Mobil</span><br>
                            <strong><?php echo number_format($mobil_data['jumlah'] ?? 0); ?></strong> (<?php 
                                $total = ($mobil_data['jumlah'] ?? 0) + ($motor_data['jumlah'] ?? 0);
                                echo $total > 0 ? round(($mobil_data['jumlah'] ?? 0) / $total * 100, 1) : 0; ?>%)
                        </div>
                        <div>
                            <span style="color: #2ecc71;"><i class="fas fa-circle"></i> Motor</span><br>
                            <strong><?php echo number_format($motor_data['jumlah'] ?? 0); ?></strong> (<?php 
                                echo $total > 0 ? round(($motor_data['jumlah'] ?? 0) / $total * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                </div>
                
                <!-- Doughnut Chart - Pendapatan per Jenis -->
                <div class="chart-box">
                    <h4>
                        <i class="fas fa-chart-doughnut" style="color: #27ae60;"></i>
                        Pendapatan per Jenis Kendaraan
                    </h4>
                    <canvas id="revenuePieChart"></canvas>
                    <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px;">
                        <div>
                            <span style="color: #e67e22;"><i class="fas fa-circle"></i> Mobil</span><br>
                            <strong><?php echo format_rupiah($mobil_data['pendapatan'] ?? 0); ?></strong>
                        </div>
                        <div>
                            <span style="color: #f1c40f;"><i class="fas fa-circle"></i> Motor</span><br>
                            <strong><?php echo format_rupiah($motor_data['pendapatan'] ?? 0); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Area Tersibuk -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>
                        <i class="fas fa-map-marker-alt"></i>
                        Top 5 Area Parkir Tersibuk Bulan Ini
                    </h3>
                </div>
                
                <div class="area-list">
                    <?php 
                    $no = 1;
                    $max_kunjungan = 0;
                    $area_data = [];
                    while ($row = mysqli_fetch_assoc($result_area)) {
                        $area_data[] = $row;
                        if ($row['total_kunjungan'] > $max_kunjungan) {
                            $max_kunjungan = $row['total_kunjungan'];
                        }
                    }
                    
                    if (empty($area_data)) {
                        echo '<p style="text-align: center; padding: 30px; color: #7f8c8d;">Belum ada data transaksi bulan ini</p>';
                    } else {
                        foreach ($area_data as $row) {
                            $persentase = $max_kunjungan > 0 ? ($row['total_kunjungan'] / $max_kunjungan) * 100 : 0;
                    ?>
                    <div class="area-item">
                        <div class="area-rank"><?php echo $no++; ?></div>
                        <div class="area-info">
                            <div class="area-name"><?php echo $row['nama_area'] ?? 'Area ' . $row['area_parkir']; ?></div>
                            <div class="area-code">Kode: <?php echo $row['area_parkir']; ?></div>
                        </div>
                        <div style="flex: 1; max-width: 300px; margin: 0 20px;">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $persentase; ?>%;"></div>
                            </div>
                        </div>
                        <div class="area-stats">
                            <div class="area-count"><?php echo $row['total_kunjungan']; ?> kunjungan</div>
                            <div class="area-revenue"><?php echo format_rupiah($row['total_pendapatan']); ?></div>
                        </div>
                    </div>
                    <?php 
                        }
                    } 
                    ?>
                </div>
            </div>
            
            <!-- Ringkasan Eksekutif -->
            <div class="chart-grid">
                <div class="kpi-card">
                    <i class="fas fa-chart-line fa-2x" style="margin-bottom: 10px;"></i>
                    <div class="kpi-value"><?php 
                        $total_pendapatan = $bulan_ini['total_pendapatan'] ?? 0;
                        $hari_dalam_bulan = date('t');
                        $hari_berjalan = date('j');
                        $proyeksi = $hari_berjalan > 0 ? ($total_pendapatan / $hari_berjalan) * $hari_dalam_bulan : 0;
                        echo format_rupiah($proyeksi);
                    ?></div>
                    <div class="kpi-label">Proyeksi Pendapatan Bulan Ini</div>
                </div>
                
                <div class="kpi-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-clock fa-2x" style="margin-bottom: 10px;"></i>
                    <div class="kpi-value"><?php 
                        $rata_per_hari = $bulan_ini['total_pendapatan'] > 0 && $hari_berjalan > 0 
                            ? $bulan_ini['total_pendapatan'] / $hari_berjalan 
                            : 0;
                        echo format_rupiah($rata_per_hari);
                    ?></div>
                    <div class="kpi-label">Rata-rata Pendapatan per Hari</div>
                </div>
            </div>
            
            <!-- Tombol Export -->
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 30px;">
                <a href="laporan.php" class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> Lihat Laporan Lengkap
                </a>
                <a href="transaksi.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> Riwayat Transaksi
                </a>
            </div>
        </main>
    </div>

    <script>
    // Data dari PHP
    const chartLabels = <?php echo json_encode($tanggal); ?>;
    const revenueData = <?php echo json_encode($pendapatan); ?>;
    const transactionData = <?php echo json_encode($transaksi); ?>;
    
    // Chart 1: Pendapatan 7 Hari
    const ctx1 = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revenueData,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#2980b9',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8
            }, {
                label: 'Jumlah Transaksi',
                data: transactionData,
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1',
                pointBackgroundColor: '#229954',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.label.includes('Pendapatan')) {
                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            } else {
                                label += context.parsed.y + ' transaksi';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' trx';
                        }
                    }
                }
            }
        }
    });
    
    // Chart 2: Komposisi Kendaraan
    const ctx2 = document.getElementById('vehicleChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Mobil', 'Motor'],
            datasets: [{
                data: [
                    <?php echo $mobil_data['jumlah'] ?? 0; ?>, 
                    <?php echo $motor_data['jumlah'] ?? 0; ?>
                ],
                backgroundColor: ['#3498db', '#2ecc71'],
                borderColor: 'white',
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '60%'
        }
    });
    
    // Chart 3: Pendapatan per Jenis
    const ctx3 = document.getElementById('revenuePieChart').getContext('2d');
    new Chart(ctx3, {
        type: 'pie',
        data: {
            labels: ['Mobil', 'Motor'],
            datasets: [{
                data: [
                    <?php echo $mobil_data['pendapatan'] ?? 0; ?>, 
                    <?php echo $motor_data['pendapatan'] ?? 0; ?>
                ],
                backgroundColor: ['#e67e22', '#f1c40f'],
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Auto refresh setiap 5 menit
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 menit
    
    // Animasi counter
    function animateValue(element, start, end, duration) {
        if (!element) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.innerHTML = value.toLocaleString('id-ID');
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stat values
        const statValues = document.querySelectorAll('.stat-value');
        statValues.forEach(value => {
            const text = value.innerText;
            const num = parseInt(text.replace(/[^0-9]/g, '')) || 0;
            if (num > 0) {
                value.innerText = '0';
                animateValue(value, 0, num, 1000);
            }
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