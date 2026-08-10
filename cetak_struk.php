<?php
require_once 'config.php';
check_login();
require_role(['petugas', 'owner']);

$id = clean_input($_GET['id'] ?? 0);
$type = clean_input($_GET['type'] ?? 'masuk');

// DEBUG LOG
error_log("Cetak Struk - ID: $id, Type: $type, User: " . $_SESSION['user_id']);

// Jika ID 0, coba ambil dari session atau cari transaksi terakhir
if ($id == 0 || $id == '0') {
    // 1. Coba dari session
    if (isset($_SESSION['last_transaction_id']) && $_SESSION['last_transaction_id'] > 0) {
        $id = $_SESSION['last_transaction_id'];
        error_log("Menggunakan ID dari session: $id");
    }
    // 2. Cari transaksi terakhir user ini
    else {
        $user_id = $_SESSION['user_id'];
        $query_last = "SELECT id FROM transaksi_parkir 
                      WHERE user_id = '$user_id' 
                      AND status = 'masuk'
                      ORDER BY id DESC 
                      LIMIT 1";
        
        $result_last = mysqli_query($conn, $query_last);
        if ($result_last && mysqli_num_rows($result_last) > 0) {
            $row_last = mysqli_fetch_assoc($result_last);
            $id = $row_last['id'];
            error_log("Menggunakan ID terakhir user: $id");
        }
    }
}

// Validasi ID
if (empty($id) || !is_numeric($id) || $id <= 0) {
    // Coba validasi dengan fungsi baru
    if (!validate_transaction_id($id)) {
        $last_id = $_SESSION['last_transaction_id'] ?? 0;
        
        echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Error - Struk Parkir</title>
            <style>
                /* ... (CSS sama seperti sebelumnya) ... */
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-icon'>
                    <i class='fas fa-exclamation-triangle'></i>
                </div>
                <h2 style='color: #dc3545;'>ID Transaksi Tidak Valid!</h2>
                <p>ID yang diterima: <strong>" . htmlspecialchars($_GET['id'] ?? '0') . "</strong></p>
                <p>Menggunakan ID dari sistem: <strong>" . $id . "</strong></p>";
        
        if ($last_id > 0) {
            echo "<p>Transaksi terakhir yang dicatat: <strong>ID $last_id</strong></p>";
            echo "<p><a href='cetak_struk.php?type=masuk&id=$last_id' style='color: #007bff;'>Coba cetak dengan ID $last_id</a></p>";
        }
        
        echo "<div class='btn-group'>
                    <a href='transaksi_masuk.php' class='btn btn-primary'>
                        <i class='fas fa-arrow-left'></i> Kembali ke Form Masuk
                    </a>
                    <a href='check_transaction.php' class='btn btn-secondary'>
                        <i class='fas fa-bug'></i> Debug Sistem
                    </a>
                    <button onclick='window.close()' class='btn btn-danger'>
                        <i class='fas fa-times'></i> Tutup Window
                    </button>
                </div>
                <div style='margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>
                    <h4>Tips:</h4>
                    <ul>
                        <li>Pastikan Anda telah menyimpan transaksi terlebih dahulu</li>
                        <li>Refresh halaman form sebelum mencetak</li>
                        <li>Gunakan tombol 'Cetak' di tabel jika transaksi sudah ada</li>
                    </ul>
                </div>
            </div>
        </body>
        </html>";
        exit();
    }
}

// ... (lanjutan kode seperti sebelumnya) ...<?php
require_once 'config.php';
check_login();
require_role(['petugas', 'owner']);

$id = clean_input($_GET['id'] ?? 0);
$type = clean_input($_GET['type'] ?? 'masuk');

// DEBUG LOG
error_log("Cetak Struk - ID: $id, Type: $type, User: " . $_SESSION['user_id']);

// Jika ID 0, coba ambil dari session atau cari transaksi terakhir
if ($id == 0 || $id == '0') {
    // 1. Coba dari session
    if (isset($_SESSION['last_transaction_id']) && $_SESSION['last_transaction_id'] > 0) {
        $id = $_SESSION['last_transaction_id'];
        error_log("Menggunakan ID dari session: $id");
    }
    // 2. Cari transaksi terakhir user ini
    else {
        $user_id = $_SESSION['user_id'];
        $query_last = "SELECT id FROM transaksi_parkir 
                      WHERE user_id = '$user_id' 
                      AND status = 'masuk'
                      ORDER BY id DESC 
                      LIMIT 1";
        
        $result_last = mysqli_query($conn, $query_last);
        if ($result_last && mysqli_num_rows($result_last) > 0) {
            $row_last = mysqli_fetch_assoc($result_last);
            $id = $row_last['id'];
            error_log("Menggunakan ID terakhir user: $id");
        }
    }
}

// Validasi ID
if (empty($id) || !is_numeric($id) || $id <= 0) {
    // Coba validasi dengan fungsi baru
    if (!validate_transaction_id($id)) {
        $last_id = $_SESSION['last_transaction_id'] ?? 0;
        
        echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Error - Struk Parkir</title>
            <style>
                /* ... (CSS sama seperti sebelumnya) ... */
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-icon'>
                    <i class='fas fa-exclamation-triangle'></i>
                </div>
                <h2 style='color: #dc3545;'>ID Transaksi Tidak Valid!</h2>
                <p>ID yang diterima: <strong>" . htmlspecialchars($_GET['id'] ?? '0') . "</strong></p>
                <p>Menggunakan ID dari sistem: <strong>" . $id . "</strong></p>";
        
        if ($last_id > 0) {
            echo "<p>Transaksi terakhir yang dicatat: <strong>ID $last_id</strong></p>";
            echo "<p><a href='cetak_struk.php?type=masuk&id=$last_id' style='color: #007bff;'>Coba cetak dengan ID $last_id</a></p>";
        }
        
        echo "<div class='btn-group'>
                    <a href='transaksi_masuk.php' class='btn btn-primary'>
                        <i class='fas fa-arrow-left'></i> Kembali ke Form Masuk
                    </a>
                    <a href='check_transaction.php' class='btn btn-secondary'>
                        <i class='fas fa-bug'></i> Debug Sistem
                    </a>
                    <button onclick='window.close()' class='btn btn-danger'>
                        <i class='fas fa-times'></i> Tutup Window
                    </button>
                </div>
                <div style='margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>
                    <h4>Tips:</h4>
                    <ul>
                        <li>Pastikan Anda telah menyimpan transaksi terlebih dahulu</li>
                        <li>Refresh halaman form sebelum mencetak</li>
                        <li>Gunakan tombol 'Cetak' di tabel jika transaksi sudah ada</li>
                    </ul>
                </div>
            </div>
        </body>
        </html>";
        exit();
    }
}


$max_retries = 3;
$retry_count = 0;
$transaksi = null;

while ($retry_count < $max_retries && !$transaksi) {
    $query = "SELECT tp.*, k.nomor_plat, k.jenis_kendaraan, u.nama_lengkap as operator
              FROM transaksi_parkir tp
              JOIN kendaraan k ON tp.kendaraan_id = k.id
              JOIN users u ON tp.user_id = u.id
              WHERE tp.id = '$id'";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $transaksi = mysqli_fetch_assoc($result);
        break;
    }
    
    // Tunggu 0.5 detik sebelum retry
    usleep(500000);
    $retry_count++;
}

// Jika masih tidak ditemukan
if (!$transaksi) {
    echo "<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Transaksi Tidak Ditemukan</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                max-width: 500px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                text-align: center;
            }
            
            .error-icon {
                font-size: 60px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            
            .debug-info {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: left;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-icon'>
                <i class='fas fa-search'></i>
            </div>
            <h2 style='color: #dc3545;'>Transaksi Tidak Ditemukan!</h2>
            <p>ID transaksi <strong>" . htmlspecialchars($id) . "</strong> tidak ditemukan dalam database.</p>
            
            <div class='debug-info'>
                <p><strong>Kemungkinan penyebab:</strong></p>
                <ol>
                    <li>Data belum tersimpan di database</li>
                    <li>ID transaksi salah</li>
                    <li>Transaksi telah dihapus</li>
                </ol>
                <p><strong>Percobaan query:</strong> $retry_count kali</p>
            </div>
            
            <div style='margin-top: 30px;'>
                <a href='transaksi_masuk.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>
                    <i class='fas fa-arrow-left'></i> Kembali ke Form
                </a>
                
                <button onclick='window.close()' class='btn' style='background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; border: none; margin: 5px; cursor: pointer;'>
                    <i class='fas fa-times'></i> Tutup Window
                </button>
                
                <a href='check_transaction.php?id=" . htmlspecialchars($id) . "' class='btn' style='background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>
                    <i class='fas fa-bug'></i> Debug Transaksi
                </a>
            </div>
        </div>
    </body>
    </html>";
    exit();
}

// Inisialisasi variabel
$biaya = 0;
$durasi_menit = 0;
$durasi_jam = 0;
$tarif_per_jam = 0;
$tarif_flat = 0;
$durasi_flat = 0;
$waktu_keluar_display = date('H:i');

// Ambil data tarif terlebih dahulu
$query_tarif = "SELECT tarif_per_jam FROM tarif_parkir WHERE jenis_kendaraan = '{$transaksi['jenis_kendaraan']}'";
$result_tarif = mysqli_query($conn, $query_tarif);
if ($result_tarif && mysqli_num_rows($result_tarif) > 0) {
    $tarif = mysqli_fetch_assoc($result_tarif);
    $tarif_per_jam = $tarif['tarif_per_jam'] ?? 0;
} else {
    // Default tarif jika tidak ditemukan
    $tarif_per_jam = ($transaksi['jenis_kendaraan'] == 'mobil') ? 5000 : 2000;
}

// Hitung biaya
$biaya = $durasi_jam * $tarif_per_jam;

// Hitung biaya jika keluar
if ($type == 'keluar' && $transaksi['status'] == 'masuk') {
    $waktu_masuk = strtotime($transaksi['waktu_masuk']);
    $waktu_keluar = time();
    $durasi_menit = ceil(($waktu_keluar - $waktu_masuk) / 60);
    $durasi_jam = ceil($durasi_menit / 60);
    
    // Hitung biaya
    if ($tarif_flat > 0 && $durasi_flat > 0 && $durasi_jam >= $durasi_flat) {
        $biaya = $tarif_flat;
    } else {
        $biaya = $durasi_jam * $tarif_per_jam;
    }
    
    // Update transaksi
    $query_update = "UPDATE transaksi_parkir SET 
                    waktu_keluar = NOW(),
                    durasi_parkir = '$durasi_menit',
                    biaya = '$biaya',
                    status = 'keluar'
                    WHERE id = '$id'";
    mysqli_query($conn, $query_update);
    
    // Update area terisi
    $query_area = "UPDATE area_parkir SET terisi = terisi - 1 
                  WHERE kode_area = '{$transaksi['area_parkir']}'";
    mysqli_query($conn, $query_area);
    
    log_aktivitas("Kendaraan keluar: {$transaksi['nomor_plat']} - Rp " . number_format($biaya, 0, ',', '.'), 'transaksi_parkir', $id);
    
    // Ambil waktu keluar yang sudah diupdate
    $waktu_keluar_display = date('H:i');
} elseif ($type == 'keluar' && $transaksi['status'] == 'keluar') {
    // Jika sudah keluar, ambil data yang sudah ada
    $durasi_menit = $transaksi['durasi_parkir'] ?? 0;
    $durasi_jam = ceil($durasi_menit / 60);
    $biaya = $transaksi['biaya'] ?? 0;
    $waktu_keluar_display = $transaksi['waktu_keluar'] ? date('H:i', strtotime($transaksi['waktu_keluar'])) : date('H:i');
} elseif ($type == 'masuk') {
    // Untuk struk masuk, hitung perkiraan durasi saat ini
    $waktu_masuk = strtotime($transaksi['waktu_masuk']);
    $waktu_sekarang = time();
    $durasi_menit = ceil(($waktu_sekarang - $waktu_masuk) / 60);
    $durasi_jam = ceil($durasi_menit / 60);
}

// Tentukan judul berdasarkan jenis struk
$judul_struk = $type == 'masuk' ? 'STRUK MASUK' : 'STRUK KELUAR';
$status_struk = $type == 'masuk' ? 'Tiket Parkir' : 'Bukti Pembayaran';

// Tentukan URL kembali
$back_url = $type == 'masuk' ? 'transaksi_masuk.php' : 'transaksi_keluar.php';
$back_text = $type == 'masuk' ? 'Kembali ke Masuk' : 'Kembali ke Keluar';

// Simpan ID ke session untuk backup
$_SESSION['last_printed_id'] = $id;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $judul_struk; ?> - <?php echo $transaksi['nomor_struk']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', 'Arial', sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .struk-container { 
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
        }
        
        /* Header Struk */
        .struk-header { 
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            text-align: center;
            padding: 25px 20px;
            position: relative;
            border-bottom: 3px dashed rgba(255,255,255,0.2);
        }
        
        .struk-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
        }
        
        .struk-header h1 { 
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .struk-header p { 
            font-size: 14px;
            opacity: 0.9;
            margin: 3px 0;
        }
        
        .status-badge {
            display: inline-block;
            background: <?php echo $type == 'masuk' ? '#4CAF50' : '#FF9800'; ?>;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
            letter-spacing: 1px;
        }
        
        /* Body Struk */
        .struk-body { 
            padding: 25px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .info-row { 
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        
        .info-value {
            color: #2c3e50;
            font-size: 14px;
            font-weight: 600;
            text-align: right;
        }
        
        .plat-nomor {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 2px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            border: 2px solid #e0e0e0;
        }
        
        /* Bagian Biaya */
        .biaya-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        
        .tarif-detail {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .total-biaya {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(255,107,107,0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .total-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .total-amount {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        /* Footer Struk */
        .struk-footer { 
            background: #f8f9fa;
            text-align: center;
            padding: 20px;
            border-top: 3px dashed #dee2e6;
            color: #666;
        }
        
        .struk-footer p {
            margin: 8px 0;
            font-size: 13px;
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
            margin: 15px auto;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px dashed #ccc;
        }
        
        .qr-code i {
            font-size: 40px;
            color: #999;
        }
        
        /* Tombol Cetak */
        .print-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            flex-wrap: wrap;
            width: 100%;
            max-width: 400px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            min-width: 140px;
            justify-content: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-print {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }
        
        .btn-close {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        /* Print Styles */
        @media print {
            body { 
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .struk-container { 
                max-width: 100% !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }
            
            .print-actions, 
            .btn { 
                display: none !important; 
            }
            
            .total-biaya {
                animation: none !important;
                box-shadow: none !important;
            }
            
            @page {
                margin: 0;
                size: auto;
            }
        }
        
        /* Animasi */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .struk-container {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .struk-container {
                border-radius: 10px;
            }
            
            .struk-header {
                padding: 20px 15px;
            }
            
            .struk-body {
                padding: 20px;
            }
            
            .print-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
        
        /* Info Debug */
        .debug-info {
            font-size: 10px;
            color: #999;
            text-align: center;
            margin-top: 10px;
            padding: 5px;
            background: #f5f5f5;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="struk-container">
        <!-- Header Struk -->
        <div class="struk-header">
            <h1><i class="fas fa-parking"></i> PARKIR MALL CENTRAL</h1>
            <p>Jl. Parkir No. 123, Jakarta Pusat</p>
            <p>Telp: (021) 1234-5678 | Website: mallcentralparkir.com</p>
            <div class="status-badge">
                <i class="fas <?php echo $type == 'masuk' ? 'fa-sign-in-alt' : 'fa-sign-out-alt'; ?>"></i>
                <?php echo $judul_struk; ?>
            </div>
        </div>
        
        <!-- Body Struk -->
        <div class="struk-body">
            <!-- Informasi Transaksi -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-receipt"></i> INFORMASI TRANSAKSI
                </div>
                
                <div class="info-row">
                    <span class="info-label">Nomor Struk:</span>
                    <span class="info-value"><?php echo $transaksi['nomor_struk']; ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span class="info-value"><?php echo date('d/m/Y'); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Waktu Cetak:</span>
                    <span class="info-value"><?php echo date('H:i:s'); ?></span>
                </div>
                
                <div class="debug-info">
                    ID Transaksi: <?php echo $id; ?> | Jenis: <?php echo $type; ?>
                </div>
            </div>
            
            <!-- Informasi Kendaraan -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-car"></i> INFORMASI KENDARAAN
                </div>
                
                <div class="plat-nomor">
                    <?php echo $transaksi['nomor_plat']; ?>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Jenis Kendaraan:</span>
                    <span class="info-value">
                        <span style="padding: 3px 10px; background: <?php echo $transaksi['jenis_kendaraan'] == 'mobil' ? '#2196F3' : '#4CAF50'; ?>; color: white; border-radius: 15px; font-size: 12px;">
                            <?php echo strtoupper($transaksi['jenis_kendaraan']); ?>
                        </span>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Area Parkir:</span>
                    <span class="info-value"><?php echo $transaksi['area_parkir']; ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Operator:</span>
                    <span class="info-value"><?php echo $transaksi['operator']; ?></span>
                </div>
            </div>
            
            <!-- Informasi Waktu -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-clock"></i> INFORMASI WAKTU
                </div>
                
                <div class="info-row">
                    <span class="info-label">Waktu Masuk:</span>
                    <span class="info-value"><?php echo format_date($transaksi['waktu_masuk'], 'H:i'); ?></span>
                </div>
                
                <?php if ($type == 'keluar'): ?>
                <div class="info-row">
                    <span class="info-label">Waktu Keluar:</span>
                    <span class="info-value"><?php echo $waktu_keluar_display; ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Durasi Parkir:</span>
                    <span class="info-value">
                        <strong><?php echo $durasi_jam; ?> jam</strong> 
                        (<?php echo $durasi_menit; ?> menit)
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($type == 'keluar'): ?>
            <!-- Detail Biaya -->
            <div class="biaya-section">
                <div class="section-title">
                    <i class="fas fa-money-bill-wave"></i> DETAIL BIAYA
                </div>
                
                <div class="tarif-detail">
                    <div class="info-row">
                        <span class="info-label">Tarif per Jam:</span>
                        <span class="info-value"><?php echo format_rupiah($tarif_per_jam); ?></span>
                    </div>
                    
                    <?php if ($tarif_flat > 0 && $durasi_flat > 0): ?>
                    <div class="info-row">
                        <span class="info-label">Tarif Flat (<?php echo $durasi_flat; ?> jam):</span>
                        <span class="info-value"><?php echo format_rupiah($tarif_flat); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Status Tarif:</span>
                        <span class="info-value">
                            <?php if ($durasi_jam >= $durasi_flat): ?>
                                <span style="color: #4CAF50; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Flat Rate Applied
                                </span>
                            <?php else: ?>
                                <span style="color: #FF9800;">
                                    <i class="fas fa-clock"></i> Regular Rate
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="total-biaya">
                    <div class="total-label">TOTAL PEMBAYARAN</div>
                    <div class="total-amount"><?php echo format_rupiah($biaya); ?></div>
                </div>
            </div>
            <?php elseif ($type == 'masuk'): ?>
            <!-- Perkiraan untuk struk masuk -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i> INFORMASI PARKIR
                </div>
                
                <div class="info-row">
                    <span class="info-label">Durasi Saat Ini:</span>
                    <span class="info-value"><?php echo $durasi_jam; ?> jam</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Tarif per Jam:</span>
                    <span class="info-value"><?php echo format_rupiah($tarif_per_jam); ?></span>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-top: 15px; text-align: center;">
                    <i class="fas fa-exclamation-triangle" style="color: #f39c12; font-size: 20px; margin-bottom: 10px; display: block;"></i>
                    <p style="color: #856404; font-size: 14px; margin: 0;">
                        <strong>Simpan struk ini!</strong> Tunjukkan saat keluar untuk pembayaran.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer Struk -->
        <div class="struk-footer">
            <div class="qr-code">
                <i class="fas fa-qrcode"></i>
            </div>
            <p><strong><?php echo $status_struk; ?></strong></p>
            <p>Terima kasih atas kunjungan Anda</p>
            <p>Struk ini adalah bukti transaksi yang sah</p>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">
                Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
    
    <!-- Tombol Aksi (Non-print) -->
    <div class="print-actions">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Cetak Struk
        </button>
        
        <!-- TOMBOL KEMBALI -->
        <a href="<?php echo $back_url; ?>" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
        </a>
        
        <button onclick="downloadStruk()" class="btn btn-download">
            <i class="fas fa-download"></i> Simpan PDF
        </button>
        
        <button onclick="window.close()" class="btn btn-close">
            <i class="fas fa-times"></i> Tutup Window
        </button>
    </div>
    // Di bagian detail kendaraan, tambahkan setelah jenis kendaraan
// Cari bagian "Informasi Kendaraan" dan tambahkan:

// Ambil data kendaraan termasuk merk dan warna
$query_kendaraan_detail = "SELECT merk, warna FROM kendaraan WHERE id = '{$transaksi['kendaraan_id']}'";
$result_kendaraan_detail = mysqli_query($conn, $query_kendaraan_detail);
$kendaraan_detail = mysqli_fetch_assoc($result_kendaraan_detail);

// Kemudian di HTML, tambahkan:
<div class="info-row">
    <span class="info-label">Merk Kendaraan:</span>
    <span class="info-value"><?php echo $kendaraan_detail['merk'] ?? '-'; ?></span>
</div>

<div class="info-row">
    <span class="info-label">Warna:</span>
    <span class="info-value"><?php echo $kendaraan_detail['warna'] ?? '-'; ?></span>
</div>
    
    <script>
        window.onload = function() {
            console.log('Struk berhasil dimuat. ID Transaksi: <?php echo $id; ?>');
            
            // Auto print setelah 1 detik
            setTimeout(function() {
                console.log('Memulai pencetakan otomatis...');
                window.print();
            }, 1000);
            
            // Auto close setelah 30 detik jika berhasil print
            setTimeout(function() {
                if (confirm('Apakah Anda ingin menutup jendela struk ini?')) {
                    window.close();
                }
            }, 30000);
        };
        
        function downloadStruk() {
            alert('Fitur download PDF akan segera tersedia! Untuk sekarang, gunakan "Print to PDF" dengan menekan tombol Cetak Struk.');
            window.print();
        }
        
        // Deteksi jika user membatalkan print
        window.onbeforeprint = function() {
            console.log('Memulai pencetakan struk...');
        };
        
        window.onafterprint = function() {
            console.log('Pencetakan selesai');
            
            // Tampilkan konfirmasi setelah print
            setTimeout(function() {
                const shouldClose = confirm('Struk telah dicetak. Apakah Anda ingin menutup jendela ini?');
                if (shouldClose) {
                    window.close();
                }
            }, 1000);
        };
        
        // Animasi saat hover tombol
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P atau Cmd+P untuk print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape untuk close
            if (e.key === 'Escape') {
                if (confirm('Tutup jendela struk?')) {
                    window.close();
                }
            }
            
            // B untuk kembali
            if (e.key === 'b' || e.key === 'B') {
                window.location.href = '<?php echo $back_url; ?>';
            }
        });
    </script>
    
</body>
</html>