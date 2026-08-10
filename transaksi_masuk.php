<?php
require_once 'config.php';
check_login();

// HANYA PETUGAS YANG BISA AKSES
if (!can_do_transaction()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya petugas yang dapat melakukan transaksi.';
    redirect('index.php');
}

// Clear session jika ada parameter
if (isset($_GET['clear_session'])) {
    unset($_SESSION['last_transaction_data']);
    redirect('transaksi_masuk.php');
}

// Variabel untuk menyimpan data transaksi terakhir
$last_transaction_data = [
    'id' => 0,
    'struk' => '',
    'plat' => '',
    'jenis' => '',
    'merk' => '',
    'warna' => '',
    'area' => '',
    'waktu_masuk' => '',
    'tanggal_masuk' => ''
];

// Proses kendaraan masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomor_plat = strtoupper(str_replace(' ', '', clean_input($_POST['nomor_plat'])));
    $jenis_kendaraan = clean_input($_POST['jenis_kendaraan']);
    $merk = clean_input($_POST['merk']);
    $warna = clean_input($_POST['warna']);
    $area_parkir = clean_input($_POST['area_parkir']);
    
    // Validasi input
    if (empty($nomor_plat) || empty($jenis_kendaraan) || empty($merk) || empty($warna) || empty($area_parkir)) {
        $_SESSION['error'] = "Semua field harus diisi!";
    } else {
        // Cek apakah kendaraan sudah parkir
        $query_check = "SELECT tp.* FROM transaksi_parkir tp 
                       JOIN kendaraan k ON tp.kendaraan_id = k.id 
                       WHERE k.nomor_plat = '$nomor_plat' AND tp.status = 'masuk'";
        $result_check = mysqli_query($conn, $query_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            $_SESSION['error'] = "Kendaraan dengan plat $nomor_plat masih parkir!";
        } else {
            // Mulai transaksi database untuk memastikan data konsisten
            mysqli_begin_transaction($conn);
            
            try {
                // Cek apakah kendaraan sudah terdaftar
                $kendaraan = get_kendaraan_by_plat($nomor_plat);
                
                if ($kendaraan) {
                    $kendaraan_id = $kendaraan['id'];
                    
                    // Update data kendaraan jika ada perubahan
                    if (!empty($merk) || !empty($warna)) {
                        $update_fields = [];
                        if (!empty($merk)) $update_fields[] = "merk = '$merk'";
                        if (!empty($warna)) $update_fields[] = "warna = '$warna'";
                        
                        if (!empty($update_fields)) {
                            $update_query = "UPDATE kendaraan SET " . implode(', ', $update_fields) . " WHERE id = '$kendaraan_id'";
                            mysqli_query($conn, $update_query);
                        }
                    }
                } else {
                    // Tambah kendaraan baru dengan merk dan warna
                    $query_insert = "INSERT INTO kendaraan (nomor_plat, jenis_kendaraan, merk, warna) 
                                   VALUES ('$nomor_plat', '$jenis_kendaraan', '$merk', '$warna')";
                    
                    if (!mysqli_query($conn, $query_insert)) {
                        throw new Exception("Gagal menambahkan kendaraan: " . mysqli_error($conn));
                    }
                    
                    $kendaraan_id = mysqli_insert_id($conn);
                }
                
                // Generate nomor struk
                $nomor_struk = generate_nomor_struk();
                $user_id = $_SESSION['user_id'];
                $waktu_masuk = date('Y-m-d H:i:s');
                $jam_masuk = date('H:i');
                $tanggal_masuk = date('d/m/Y');
                
                // Tambah transaksi
                $query_transaksi = "INSERT INTO transaksi_parkir 
                                   (nomor_struk, kendaraan_id, user_id, area_parkir, waktu_masuk, status) 
                                   VALUES ('$nomor_struk', '$kendaraan_id', '$user_id', 
                                           '$area_parkir', '$waktu_masuk', 'masuk')";
                
                if (!mysqli_query($conn, $query_transaksi)) {
                    throw new Exception("Gagal mencatat transaksi: " . mysqli_error($conn));
                }
                
                $transaksi_id = mysqli_insert_id($conn);
                
                // Update area terisi
                $query_update = "UPDATE area_parkir SET terisi = terisi + 1 
                               WHERE kode_area = '$area_parkir'";
                
                if (!mysqli_query($conn, $query_update)) {
                    throw new Exception("Gagal update area parkir: " . mysqli_error($conn));
                }
                
                // Commit transaksi
                mysqli_commit($conn);
                
                // Simpan data transaksi terakhir
                $last_transaction_data = [
                    'id' => $transaksi_id,
                    'struk' => $nomor_struk,
                    'plat' => $nomor_plat,
                    'jenis' => $jenis_kendaraan,
                    'merk' => $merk,
                    'warna' => $warna,
                    'area' => $area_parkir,
                    'waktu_masuk' => $jam_masuk,
                    'tanggal_masuk' => $tanggal_masuk,
                    'full_waktu' => $waktu_masuk
                ];
                
                // Simpan ke session
                $_SESSION['last_transaction_data'] = $last_transaction_data;
                
                log_aktivitas("Kendaraan masuk: $nomor_plat ($merk - $warna)", 'transaksi_parkir', $transaksi_id);
                
                // Tampilkan pesan sukses
                $_SESSION['success'] = "Kendaraan berhasil dicatat masuk!";
                
                // Redirect dengan parameter untuk show modal
                redirect('transaksi_masuk.php?show_modal=true');
                
            } catch (Exception $e) {
                // Rollback jika ada error
                mysqli_rollback($conn);
                $_SESSION['error'] = $e->getMessage();
            }
        }
    }
}

// Cek jika ada data transaksi terakhir di session
if (isset($_SESSION['last_transaction_data']) && !empty($_SESSION['last_transaction_data'])) {
    $last_transaction_data = $_SESSION['last_transaction_data'];
}

// Cek parameter untuk menampilkan modal
$show_modal = isset($_GET['show_modal']) && $_GET['show_modal'] == 'true';

// Ambil area yang tersedia
$query_area = "SELECT * FROM area_parkir WHERE status = 'Tersedia' AND terisi < kapasitas ORDER BY kode_area";
$result_area = mysqli_query($conn, $query_area);

// Ambil area untuk mobil dan motor
$area_mobil = get_suggested_area('mobil');
$area_motor = get_suggested_area('motor');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kendaraan Masuk - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS untuk area otomatis */
        .area-suggestion {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 10px;
            margin-top: 5px;
            display: none;
        }
        
        .area-suggestion.active {
            display: block;
        }
        
        .suggested-area {
            color: #0066cc;
            font-weight: bold;
        }
        
        .btn-suggest {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .btn-suggest:hover {
            background: #218838;
        }
        
        /* Style untuk field kendaraan */
        .vehicle-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #2196F3;
        }
        
        .vehicle-info h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vehicle-info h4 i {
            color: #2196F3;
        }
        
        /* Modal untuk konfirmasi cetak struk */
        .print-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .print-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .btn-close-modal {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 20px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 50%;
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-close-modal:hover {
            background: #f8f9fa;
            color: #dc3545;
            transform: rotate(90deg);
        }
        
        .modal-buttons {
            margin-top: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .modal-full-btn {
            grid-column: span 2;
        }
        
        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            text-align: left;
            border: 1px solid #e9ecef;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            color: #6c757d;
            font-weight: 500;
            min-width: 120px;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 600;
            text-align: right;
            flex: 1;
        }
        
        .highlight-value {
            color: #2c3e50;
            font-weight: 700;
            font-size: 18px;
        }
        
        .struk-number {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            margin: 15px 0;
            display: inline-block;
            letter-spacing: 1px;
        }
        
        .modal-instruction {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px 15px;
            margin: 15px 0;
            text-align: left;
            font-size: 13px;
            color: #856404;
        }
        
        .instruction-icon {
            color: #f39c12;
            margin-right: 8px;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .modal-content {
                padding: 20px;
            }
            
            .modal-buttons {
                grid-template-columns: 1fr;
            }
            
            .modal-full-btn {
                grid-column: span 1;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-value {
                text-align: left;
                margin-top: 5px;
            }
        }
        
        .btn-animate {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-animate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-animate:active {
            transform: translateY(0);
        }
        
        .btn-exit {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-exit:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
        }
        
        /* Required field indicator */
        .required {
            color: #f44336;
        }
        
        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-sign-in-alt"></i> Kendaraan Masuk</h2>
                <div class="header-actions">
                    <?php if ($show_modal || !empty($last_transaction_data['id'])): ?>
                    <a href="transaksi_masuk.php?clear_session=true" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Tutup Struk
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Modal Cetak Struk -->
            <div id="printModal" class="print-modal <?php echo $show_modal ? 'active' : ''; ?>">
                <div class="modal-content">
                    <!-- Header dengan tombol tutup -->
                    <div class="modal-header">
                        <h3 style="color: #2c3e50; margin: 0;">
                            <i class="fas fa-receipt"></i> Struk Parkir
                        </h3>
                        <button onclick="closeModalOnly()" class="btn-close-modal" title="Tutup">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="modal-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 style="color: #4CAF50; margin-bottom: 10px;">Transaksi Berhasil!</h2>
                    <p style="color: #6c757d; margin-bottom: 15px;">Kendaraan berhasil dicatat masuk ke sistem parkir</p>
                    
                    <div class="struk-number" id="modalStrukNumber">
                        <?php echo $last_transaction_data['struk'] ?? ''; ?>
                    </div>
                    
                    <div class="transaction-details">
                        <h4 style="color: #2c3e50; margin-bottom: 15px; text-align: center;">
                            <i class="fas fa-info-circle"></i> Detail Transaksi
                        </h4>
                        
                        <div class="detail-row">
                            <span class="detail-label">Nomor Plat:</span>
                            <span class="detail-value highlight-value" id="modalPlatNumber">
                                <?php echo $last_transaction_data['plat'] ?? ''; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Jenis Kendaraan:</span>
                            <span class="detail-value" id="modalVehicleType">
                                <?php echo isset($last_transaction_data['jenis']) ? ucfirst($last_transaction_data['jenis']) : ''; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Merk:</span>
                            <span class="detail-value" id="modalMerk">
                                <?php echo $last_transaction_data['merk'] ?? '-'; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Warna:</span>
                            <span class="detail-value" id="modalWarna">
                                <?php echo $last_transaction_data['warna'] ?? '-'; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Area Parkir:</span>
                            <span class="detail-value" id="modalParkingArea">
                                <?php echo $last_transaction_data['area'] ?? ''; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Masuk:</span>
                            <span class="detail-value" id="modalEntryDate">
                                <?php echo $last_transaction_data['tanggal_masuk'] ?? ''; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Jam Masuk:</span>
                            <span class="detail-value highlight-value" id="modalEntryTime">
                                <?php echo $last_transaction_data['waktu_masuk'] ?? ''; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value" style="color: #4CAF50; font-weight: 700;">
                                <i class="fas fa-check-circle"></i> Tercatat
                            </span>
                        </div>
                    </div>
                    
                    <div class="modal-instruction">
                        <i class="fas fa-info-circle instruction-icon"></i>
                        <strong>Simpan struk ini!</strong> Tunjukkan saat keluar untuk pembayaran.
                    </div>
                    
                    <!-- Tombol aksi -->
                    <div class="modal-buttons">
                        <button onclick="printStruk()" class="btn btn-primary btn-animate modal-full-btn" 
                                style="padding: 12px 20px;">
                            <i class="fas fa-print"></i> Cetak Struk Sekarang
                        </button>
                        
                        <button onclick="closeModalAndReset()" class="btn btn-success btn-animate" 
                                style="padding: 10px 15px;">
                            <i class="fas fa-plus"></i> Baru
                        </button>
                        
                        <button onclick="closeModalOnly()" class="btn-exit btn-animate" 
                                style="padding: 10px 15px;">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </button>
                    </div>
                    
                    <p style="color: #999; font-size: 12px; margin-top: 15px;">
                        <i class="fas fa-clock"></i> Dicatat pada: 
                        <span id="modalTimestamp">
                            <?php echo isset($last_transaction_data['full_waktu']) 
                                ? date('d/m/Y H:i:s', strtotime($last_transaction_data['full_waktu'])) 
                                : date('d/m/Y H:i:s'); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Form Kendaraan Masuk -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-car"></i> Form Kendaraan Masuk</h3>
                </div>
                <form method="POST" action="" id="formMasuk" onsubmit="return validateForm(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nomor Plat <span class="required">*</span></label>
                            <input type="text" name="nomor_plat" id="nomor_plat" class="form-control" 
                                   placeholder="Contoh: B1234ABC" required
                                   oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')"
                                   value="<?php echo isset($_POST['nomor_plat']) ? htmlspecialchars($_POST['nomor_plat']) : ''; ?>">
                            <div class="form-hint">Masukkan nomor plat tanpa spasi</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Kendaraan <span class="required">*</span></label>
                            <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-control" required onchange="updateAreaSuggestion()">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="mobil" <?php echo (isset($_POST['jenis_kendaraan']) && $_POST['jenis_kendaraan'] == 'mobil') ? 'selected' : ''; ?>>Mobil</option>
                                <option value="motor" <?php echo (isset($_POST['jenis_kendaraan']) && $_POST['jenis_kendaraan'] == 'motor') ? 'selected' : ''; ?>>Motor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="vehicle-info">
                        <h4><i class="fas fa-info-circle"></i> Informasi Kendaraan</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Merk <span class="required">*</span></label>
                                <input type="text" name="merk" id="merk" class="form-control" 
                                       placeholder="Contoh: Toyota, Honda, dll" required
                                       value="<?php echo isset($_POST['merk']) ? htmlspecialchars($_POST['merk']) : ''; ?>">
                                <div class="form-hint">Isi merk kendaraan</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Warna <span class="required">*</span></label>
                                <input type="text" name="warna" id="warna" class="form-control" 
                                       placeholder="Contoh: Hitam, Putih, dll" required
                                       value="<?php echo isset($_POST['warna']) ? htmlspecialchars($_POST['warna']) : ''; ?>">
                                <div class="form-hint">Isi warna kendaraan</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Area Parkir <span class="required">*</span></label>
                            <select name="area_parkir" id="area_parkir" class="form-control" required>
                                <option value="">-- Pilih Area --</option>
                                <?php 
                                mysqli_data_seek($result_area, 0); // Reset pointer
                                while ($area = mysqli_fetch_assoc($result_area)): 
                                    $tersedia = $area['kapasitas'] - $area['terisi'];
                                    $selected = (isset($_POST['area_parkir']) && $_POST['area_parkir'] == $area['kode_area']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $area['kode_area']; ?>" 
                                        data-kapasitas="<?php echo $tersedia; ?>"
                                        data-jenis="<?php echo (strpos(strtolower($area['nama_area']), 'mobil') !== false || strpos($area['kode_area'], 'M') !== false) ? 'mobil' : 'motor'; ?>"
                                        <?php echo $selected; ?>>
                                    <?php echo $area['kode_area'] . ' - ' . $area['nama_area'] . " (Tersedia: $tersedia)"; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            
                            <!-- Suggestion Area -->
                            <div id="areaSuggestion" class="area-suggestion">
                                <i class="fas fa-lightbulb"></i> 
                                <span id="suggestedAreaText" class="suggested-area"></span>
                                <button type="button" onclick="applySuggestedArea()" class="btn-suggest">
                                    <i class="fas fa-check"></i> Gunakan Area Ini
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Simpan Kendaraan Masuk
                            </button>
                            <a href="transaksi_masuk.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Form
                            </a>
                            
                            <?php if ($show_modal || !empty($last_transaction_data['id'])): ?>
                            <button type="button" onclick="showPrintModal()" class="btn btn-info">
                                <i class="fas fa-eye"></i> Lihat Struk
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Daftar Kendaraan Parkir -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Kendaraan Sedang Parkir</h3>
                    <span class="badge">
                        <?php
                        $query_count = "SELECT COUNT(*) as total FROM transaksi_parkir WHERE status = 'masuk' AND user_id = '{$_SESSION['user_id']}'";
                        $result_count = mysqli_query($conn, $query_count);
                        $count = mysqli_fetch_assoc($result_count)['total'];
                        echo $count . " kendaraan";
                        ?>
                    </span>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Plat</th>
                                <th>Jenis</th>
                                <th>Merk</th>
                                <th>Warna</th>
                                <th>Area</th>
                                <th>Waktu Masuk</th>
                                <th>Durasi</th>
                                <th>No. Struk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $user_id = $_SESSION['user_id'];
                            $query_parkir = "SELECT tp.*, k.nomor_plat, k.jenis_kendaraan, k.merk, k.warna 
                                           FROM transaksi_parkir tp 
                                           JOIN kendaraan k ON tp.kendaraan_id = k.id 
                                           WHERE tp.status = 'masuk' AND tp.user_id = '$user_id'
                                           ORDER BY tp.waktu_masuk DESC LIMIT 10";
                            $result_parkir = mysqli_query($conn, $query_parkir);
                            $no = 1;
                            
                            if ($result_parkir && mysqli_num_rows($result_parkir) > 0) {
                                while ($row = mysqli_fetch_assoc($result_parkir)) {
                                    $durasi = time() - strtotime($row['waktu_masuk']);
                                    $jam = floor($durasi / 3600);
                                    $menit = floor(($durasi % 3600) / 60);
                                    echo "<tr>";
                                    echo "<td>$no</td>";
                                    echo "<td><strong>{$row['nomor_plat']}</strong></td>";
                                    echo "<td>" . ucfirst($row['jenis_kendaraan']) . "</td>";
                                    echo "<td>{$row['merk']}</td>";
                                    echo "<td>{$row['warna']}</td>";
                                    echo "<td>{$row['area_parkir']}</td>";
                                    echo "<td>" . format_date($row['waktu_masuk'], 'H:i') . "</td>";
                                    echo "<td>$jam jam $menit menit</td>";
                                    echo "<td><small>{$row['nomor_struk']}</small></td>";
                                    echo "<td>";
                                    echo "<a href='cetak_struk.php?type=masuk&id=" . $row['id'] . "' 
                                          class='btn btn-info btn-sm' target='_blank' 
                                          title='Cetak Struk Masuk'>";
                                    echo "<i class='fas fa-print'></i>";
                                    echo "</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='10' style='text-align: center; padding: 20px;'>Belum ada kendaraan yang dicatat masuk</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Data area default dari PHP
    const areaData = {
        mobil: {
            code: "<?php echo $area_mobil ? $area_mobil['kode_area'] : ''; ?>",
            name: "<?php echo $area_mobil ? $area_mobil['nama_area'] : ''; ?>",
            tersedia: "<?php echo $area_mobil ? ($area_mobil['kapasitas'] - $area_mobil['terisi']) : 0; ?>"
        },
        motor: {
            code: "<?php echo $area_motor ? $area_motor['kode_area'] : ''; ?>",
            name: "<?php echo $area_motor ? $area_motor['nama_area'] : ''; ?>",
            tersedia: "<?php echo $area_motor ? ($area_motor['kapasitas'] - $area_motor['terisi']) : 0; ?>"
        }
    };
    
    // Data transaksi terakhir dari PHP
    const lastTransactionData = <?php echo json_encode($last_transaction_data); ?>;
    
    function updateAreaSuggestion() {
        const jenisSelect = document.getElementById('jenis_kendaraan');
        const areaSelect = document.getElementById('area_parkir');
        const suggestionDiv = document.getElementById('areaSuggestion');
        const suggestedText = document.getElementById('suggestedAreaText');
        
        const selectedJenis = jenisSelect.value;
        
        if (selectedJenis && areaData[selectedJenis] && areaData[selectedJenis].code) {
            const area = areaData[selectedJenis];
            
            // Update text suggestion
            suggestedText.textContent = `Area ${selectedJenis}: ${area.code} - ${area.name} (Tersedia: ${area.tersedia})`;
            suggestionDiv.classList.add('active');
            
            // Filter area dropdown berdasarkan jenis
            filterAreaDropdown(selectedJenis);
            
            // Auto select jika area untuk jenis ini ada di dropdown
            const areaOption = Array.from(areaSelect.options).find(option => 
                option.value === area.code
            );
            
            if (areaOption && !areaSelect.value) {
                areaSelect.value = area.code;
            }
        } else {
            suggestionDiv.classList.remove('active');
            // Reset filter dropdown
            resetAreaDropdown();
        }
    }
    
    function filterAreaDropdown(jenis) {
        const areaSelect = document.getElementById('area_parkir');
        const options = areaSelect.getElementsByTagName('option');
        
        // Sembunyikan semua opsi terlebih dahulu
        for (let i = 1; i < options.length; i++) {
            const option = options[i];
            const optionJenis = option.getAttribute('data-jenis');
            
            if (optionJenis === jenis || optionJenis === 'both') {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        }
    }
    
    function resetAreaDropdown() {
        const areaSelect = document.getElementById('area_parkir');
        const options = areaSelect.getElementsByTagName('option');
        
        // Tampilkan semua opsi
        for (let i = 1; i < options.length; i++) {
            options[i].style.display = '';
        }
    }
    
    function applySuggestedArea() {
        const jenisSelect = document.getElementById('jenis_kendaraan');
        const areaSelect = document.getElementById('area_parkir');
        const selectedJenis = jenisSelect.value;
        
        if (selectedJenis && areaData[selectedJenis] && areaData[selectedJenis].code) {
            areaSelect.value = areaData[selectedJenis].code;
        }
    }
    
    function validateForm(event) {
        const plat = document.getElementById('nomor_plat').value.trim();
        const jenis = document.getElementById('jenis_kendaraan').value;
        const merk = document.getElementById('merk').value.trim();
        const warna = document.getElementById('warna').value.trim();
        const area = document.getElementById('area_parkir').value;
        
        if (!plat) {
            event.preventDefault();
            alert('Harap isi nomor plat kendaraan!');
            document.getElementById('nomor_plat').focus();
            return false;
        }
        
        if (!jenis) {
            event.preventDefault();
            alert('Harap pilih jenis kendaraan!');
            document.getElementById('jenis_kendaraan').focus();
            return false;
        }
        
        if (!merk) {
            event.preventDefault();
            alert('Harap isi merk kendaraan!');
            document.getElementById('merk').focus();
            return false;
        }
        
        if (!warna) {
            event.preventDefault();
            alert('Harap isi warna kendaraan!');
            document.getElementById('warna').focus();
            return false;
        }
        
        if (!area) {
            event.preventDefault();
            alert('Harap pilih area parkir!');
            document.getElementById('area_parkir').focus();
            return false;
        }
        
        // Validasi format plat nomor (minimal 3 karakter)
        if (plat.length < 3) {
            event.preventDefault();
            alert('Nomor plat terlalu pendek! Minimal 3 karakter.');
            document.getElementById('nomor_plat').focus();
            return false;
        }
        
        // Tampilkan loading
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        submitBtn.disabled = true;
        
        // Tambahkan pesan konfirmasi
        const confirmed = confirm(`Konfirmasi data:\n\nPlat: ${plat}\nJenis: ${jenis}\nMerk: ${merk}\nWarna: ${warna}\nArea: ${area}\n\nLanjutkan?`);
        
        if (!confirmed) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            event.preventDefault();
            return false;
        }
        
        return true;
    }
    
    function showPrintModal() {
        if (lastTransactionData && lastTransactionData.id > 0) {
            // Tampilkan modal
            document.getElementById('printModal').classList.add('active');
            
            // Auto focus ke tombol cetak
            setTimeout(() => {
                document.querySelector('.modal-content .btn-primary').focus();
            }, 300);
            
            // Blokir scroll di background
            document.body.style.overflow = 'hidden';
        } else {
            alert('Tidak ada data transaksi untuk ditampilkan.');
        }
    }
    
    function printStruk() {
        if (lastTransactionData && lastTransactionData.id > 0) {
            // Buka halaman cetak struk di tab baru
            const printWindow = window.open(`cetak_struk.php?type=masuk&id=${lastTransactionData.id}`, '_blank');
            
            // Fokus ke window cetakan
            if (printWindow) {
                printWindow.focus();
            }
            
            // Tutup modal setelah cetak (opsional)
            setTimeout(() => {
                closeModalAndReset();
            }, 1000);
        } else {
            alert('ID transaksi tidak valid! Silakan coba lagi.');
        }
    }
    
    function closeModalAndReset() {
        // Tutup modal
        closeModalOnly();
        
        // Reset form
        document.getElementById('formMasuk').reset();
        
        // Reset button
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Kendaraan Masuk';
        
        // Focus ke input plat
        document.getElementById('nomor_plat').focus();
        
        // Reset area suggestion
        resetAreaDropdown();
        
        // Hapus data transaksi dari session (PHP)
        // Ini akan dihandle oleh PHP saat halaman direfresh
        window.location.href = 'transaksi_masuk.php?clear_session=true';
    }
    
    function closeModalOnly() {
        // Hanya tutup modal tanpa reset form
        document.getElementById('printModal').classList.remove('active');
        
        // Reset button
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Kendaraan Masuk';
        
        // Kembalikan scroll
        document.body.style.overflow = 'auto';
        
        // Hapus parameter dari URL
        window.history.replaceState({}, document.title, 'transaksi_masuk.php');
    }
    
    // Auto-focus ke input plat
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi area suggestion
        updateAreaSuggestion();
        
        // Auto focus ke input plat hanya jika tidak ada modal terbuka
        if (!document.getElementById('printModal').classList.contains('active')) {
            document.getElementById('nomor_plat').focus();
        }
        
        // Cek jika ada transaksi yang baru saja dibuat
        if (lastTransactionData && lastTransactionData.id > 0) {
            // Jika modal sudah aktif dari PHP, blokir scroll
            if (document.getElementById('printModal').classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            }
        }
        
        // Auto-refresh setiap 60 detik
        setInterval(() => {
            if (!document.getElementById('printModal').classList.contains('active')) {
                location.reload();
            }
        }, 60000);
    });
    
    // Handle form submission
    document.getElementById('formMasuk').addEventListener('submit', function(e) {
        if (!validateForm(e)) {
            return false;
        }
        
        // Form akan diproses oleh PHP
        // Setelah submit, jika berhasil, PHP akan mengatur session
        // dan JavaScript akan menampilkan modal
        
        return true;
    });
    
    // Keyboard shortcuts untuk modal
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('printModal');
        
        if (modal.classList.contains('active')) {
            // Escape untuk tutup modal
            if (e.key === 'Escape') {
                closeModalOnly();
                e.preventDefault();
            }
            
            // Enter untuk cetak struk
            if (e.key === 'Enter' && !e.ctrlKey && !e.metaKey) {
                printStruk();
                e.preventDefault();
            }
            
            // N untuk transaksi baru
            if (e.key === 'n' || e.key === 'N') {
                closeModalAndReset();
                e.preventDefault();
            }
            
            // K untuk keluar (kembali)
            if (e.key === 'k' || e.key === 'K') {
                closeModalOnly();
                e.preventDefault();
            }
        }
    });
    
    // Klik di luar modal untuk menutup
    document.getElementById('printModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalOnly();
        }
    });
    
    // Animasi untuk tombol di modal
    document.querySelectorAll('.modal-buttons .btn, .modal-buttons .btn-exit').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    </script>
                 <div class="login-footer" align="center">
            &copy; 2026 Aditya Herlambang Kelas 12 RPL
        </div>
</body>
</html>