<?php
require_once 'config.php';
check_login();

// HANYA PETUGAS YANG BISA AKSES
if (!can_do_transaction()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya petugas yang dapat melakukan transaksi keluar.';
    redirect('index.php');
}

$error = '';
$success = '';
$transaksi = null;

// Cari kendaraan berdasarkan plat untuk keluar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cari'])) {
    $nomor_plat = strtoupper(str_replace(' ', '', clean_input($_POST['nomor_plat'])));
    
    if (empty($nomor_plat)) {
        $_SESSION['error'] = 'Masukkan nomor plat kendaraan!';
    } else {
        $query = "SELECT tp.*, k.nomor_plat, k.jenis_kendaraan, k.merk, k.warna,
                         u.nama_lengkap as operator, a.nama_area
                 FROM transaksi_parkir tp
                 JOIN kendaraan k ON tp.kendaraan_id = k.id
                 JOIN users u ON tp.user_id = u.id
                 LEFT JOIN area_parkir a ON tp.area_parkir = a.kode_area
                 WHERE k.nomor_plat = '$nomor_plat' 
                 AND tp.status = 'masuk'
                 ORDER BY tp.waktu_masuk DESC
                 LIMIT 1";
        
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $transaksi = mysqli_fetch_assoc($result);
            
            // Hitung durasi dan biaya
            $waktu_masuk = strtotime($transaksi['waktu_masuk']);
            $waktu_sekarang = time();
            $durasi_menit = ceil(($waktu_sekarang - $waktu_masuk) / 60);
            $biaya = hitung_biaya_parkir($transaksi['jenis_kendaraan'], $durasi_menit);
            
            $transaksi['durasi_menit'] = $durasi_menit;
            $transaksi['biaya'] = $biaya;
            $transaksi['durasi_jam'] = ceil($durasi_menit / 60);
            
            // Simpan ke session untuk sementara
            $_SESSION['temp_transaksi'] = $transaksi;
        } else {
            $_SESSION['error'] = "Kendaraan dengan plat <strong>$nomor_plat</strong> tidak ditemukan atau sudah keluar.";
        }
    }
    redirect('transaksi_keluar.php');
}

// Proses keluar dan bayar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar'])) {
    $transaksi_id = clean_input($_POST['transaksi_id']);
    $nomor_plat = clean_input($_POST['nomor_plat']);
    $biaya = str_replace('.', '', clean_input($_POST['biaya']));
    $metode_bayar = clean_input($_POST['metode_bayar']);
    $area_parkir = clean_input($_POST['area_parkir']);
    $durasi_menit = clean_input($_POST['durasi_menit']);
    $uang_dibayar = str_replace('.', '', clean_input($_POST['uang_dibayar']));
    $kembalian = str_replace('.', '', clean_input($_POST['kembalian']));
    
    // Validasi
    if (empty($metode_bayar)) {
        $_SESSION['error'] = 'Pilih metode pembayaran!';
        redirect('transaksi_keluar.php');
    }
    
    if ($metode_bayar == 'tunai' && $uang_dibayar < $biaya) {
        $_SESSION['error'] = 'Uang yang dibayarkan kurang dari total biaya!';
        redirect('transaksi_keluar.php');
    }
    
    // Update transaksi
    $query = "UPDATE transaksi_parkir 
              SET waktu_keluar = NOW(), 
                  biaya = '$biaya', 
                  durasi_parkir = '$durasi_menit',
                  metode_bayar = '$metode_bayar',
                  status = 'keluar'
              WHERE id = '$transaksi_id'";
    
    if (mysqli_query($conn, $query)) {
        // Update area parkir
        $query_area = "UPDATE area_parkir SET terisi = terisi - 1 
                      WHERE kode_area = '$area_parkir'";
        mysqli_query($conn, $query_area);
        
        log_aktivitas("Kendaraan keluar: $nomor_plat - Rp " . number_format($biaya, 0, ',', '.'), 'transaksi_parkir', $transaksi_id);
        
        // Simpan informasi pembayaran ke session untuk ditampilkan di struk
        $_SESSION['pembayaran_info'] = [
            'uang_dibayar' => $uang_dibayar,
            'kembalian' => $kembalian,
            'metode_bayar' => $metode_bayar
        ];
        
        $_SESSION['last_transaction_id'] = $transaksi_id;
        $_SESSION['success'] = "Transaksi berhasil diproses!";
        
        // Redirect ke cetak struk
        header("Location: cetak_struk.php?type=keluar&id=" . $transaksi_id);
        exit();
    } else {
        $_SESSION['error'] = "Gagal memproses transaksi: " . mysqli_error($conn);
        redirect('transaksi_keluar.php');
    }
}

// Ambil data dari session jika ada
if (isset($_SESSION['temp_transaksi'])) {
    $transaksi = $_SESSION['temp_transaksi'];
}

// Hapus session temp setelah digunakan
if (isset($_GET['clear'])) {
    unset($_SESSION['temp_transaksi']);
    unset($_SESSION['pembayaran_info']);
    redirect('transaksi_keluar.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kendaraan Keluar - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        
        .payment-box h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-box h4 i {
            color: #28a745;
        }
        
        .total-biaya {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .total-biaya .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .total-biaya .nominal {
            font-size: 32px;
            font-weight: 700;
        }
        
        .kembalian-box {
            background: #d4edda;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
            display: none;
            border: 1px solid #c3e6cb;
        }
        
        .kembalian-box.show {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }
        
        .kembalian-box .label {
            font-size: 14px;
            color: #155724;
            margin-bottom: 5px;
        }
        
        .kembalian-box .nominal {
            font-size: 28px;
            font-weight: 700;
            color: #155724;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-value.biaya {
            color: #f44336;
            font-size: 18px;
        }
        
        .method-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .method-tunai { background: #28a745; color: white; }
        .method-qris { background: #17a2b8; color: white; }
        .method-debit { background: #ffc107; color: #333; }
        
        .btn-bayar {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 14px 28px;
            font-size: 16px;
        }
        
        .btn-bayar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            padding: 12px 15px;
            border-radius: 8px 0 0 8px;
        }
        
        .rupiah-input {
            border-radius: 0 8px 8px 0;
        }
        
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-sign-out-alt"></i> Kendaraan Keluar</h2>
                <div class="header-actions">
                    <?php if (isset($_SESSION['temp_transaksi'])): ?>
                    <a href="?clear=1" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Transaksi Baru
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Form Pencarian -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Cari Kendaraan</h3>
                </div>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <label>Nomor Plat Kendaraan</label>
                            <div class="input-group">
                                <input type="text" name="nomor_plat" id="nomor_plat" class="form-control" 
                                       placeholder="Masukkan nomor plat (contoh: B1234ABC)" required
                                       oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')"
                                       value="<?php echo isset($_POST['nomor_plat']) ? htmlspecialchars($_POST['nomor_plat']) : ''; ?>">
                                <div class="input-group-append">
                                    <button type="submit" name="cari" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (isset($transaksi)): ?>
            <!-- Detail Transaksi -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-receipt"></i> Detail Pembayaran</h3>
                </div>
                <form method="POST" action="" id="formBayar" onsubmit="return validateAndSubmit()">
                    <input type="hidden" name="bayar" value="1">
                    <input type="hidden" name="transaksi_id" value="<?php echo $transaksi['id']; ?>">
                    <input type="hidden" name="nomor_plat" value="<?php echo $transaksi['nomor_plat']; ?>">
                    <input type="hidden" name="biaya" id="biaya_hidden" value="<?php echo $transaksi['biaya']; ?>">
                    <input type="hidden" name="durasi_menit" value="<?php echo $transaksi['durasi_menit']; ?>">
                    <input type="hidden" name="area_parkir" value="<?php echo $transaksi['area_parkir']; ?>">
                    <input type="hidden" name="kembalian" id="kembalian_hidden" value="0">
                    
                    <div style="padding: 20px;">
                        <!-- Informasi Kendaraan -->
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-car"></i> Nomor Plat:</span>
                            <span class="info-value" style="font-size: 18px;"><?php echo $transaksi['nomor_plat']; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-tag"></i> Jenis Kendaraan:</span>
                            <span class="info-value"><?php echo ucfirst($transaksi['jenis_kendaraan']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-building"></i> Merk:</span>
                            <span class="info-value"><?php echo $transaksi['merk'] ?? '-'; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-palette"></i> Warna:</span>
                            <span class="info-value"><?php echo $transaksi['warna'] ?? '-'; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Area Parkir:</span>
                            <span class="info-value"><?php echo $transaksi['area_parkir']; ?> (<?php echo $transaksi['nama_area']; ?>)</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-clock"></i> Waktu Masuk:</span>
                            <span class="info-value"><?php echo format_date($transaksi['waktu_masuk'], 'd/m/Y H:i:s'); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-clock"></i> Waktu Keluar:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-hourglass-half"></i> Durasi Parkir:</span>
                            <span class="info-value"><strong><?php echo $transaksi['durasi_jam']; ?> jam</strong> (<?php echo $transaksi['durasi_menit']; ?> menit)</span>
                        </div>
                        
                        <?php 
                        // Ambil info tarif
                        $query_tarif = "SELECT tarif_per_jam FROM tarif_parkir 
                                       WHERE jenis_kendaraan = '{$transaksi['jenis_kendaraan']}'";
                        $result_tarif = mysqli_query($conn, $query_tarif);
                        $tarif = mysqli_fetch_assoc($result_tarif);
                        ?>
                        
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-tags"></i> Tarif per Jam:</span>
                            <span class="info-value"><?php echo format_rupiah($tarif['tarif_per_jam']); ?></span>
                        </div>
                        
                        <!-- Total Biaya -->
                        <div class="total-biaya">
                            <div class="label"><i class="fas fa-money-bill-wave"></i> TOTAL BIAYA</div>
                            <div class="nominal" id="totalBiaya"><?php echo format_rupiah($transaksi['biaya']); ?></div>
                        </div>
                        
                        <!-- Payment Section -->
                        <div class="payment-box">
                            <h4><i class="fas fa-credit-card"></i> Metode Pembayaran</h4>
                            
                            <div class="form-group">
                                <label>Metode Pembayaran *</label>
                                <select name="metode_bayar" id="metode_bayar" class="form-control" required onchange="togglePaymentInput()">
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="tunai" selected>Tunai</option>
                                    <option value="qris">QRIS</option>
                                    <option value="debit">Kartu Debit</option>
                                </select>
                            </div>
                            
                            <div id="tunaiSection">
                                <div class="form-group">
                                    <label><i class="fas fa-money-bill"></i> Uang yang Dibayarkan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="uang_dibayar" id="uang_dibayar" class="form-control rupiah-input" 
                                               placeholder="0" oninput="hitungKembalian()" value="0">
                                    </div>
                                </div>
                                
                                <div id="kembalianBox" class="kembalian-box">
                                    <div class="label"><i class="fas fa-exchange-alt"></i> Kembalian</div>
                                    <div class="nominal" id="kembalianNominal">Rp 0</div>
                                </div>
                                
                                <div id="warningKurang" class="alert-warning" style="display: none; margin-top: 10px;">
                                    <i class="fas fa-exclamation-triangle"></i> Uang yang dibayarkan kurang dari total biaya!
                                </div>
                            </div>
                            
                            <div id="nonTunaiSection" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    Pembayaran via QRIS/Debit akan diproses melalui mesin EDC.
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row" style="margin-top: 20px;">
                            <div class="form-group">
                                <button type="submit" id="btnBayar" class="btn btn-success btn-lg btn-bayar">
                                    <i class="fas fa-check-circle"></i> Proses Pembayaran & Cetak Struk
                                </button>
                                <a href="transaksi_keluar.php?clear=1" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Transaksi Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Daftar Kendaraan Parkir -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-car"></i> Kendaraan Sedang Parkir</h3>
                </div>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor Plat</th>
                                <th>Jenis</th>
                                <th>Merk</th>
                                <th>Warna</th>
                                <th>Area</th>
                                <th>Waktu Masuk</th>
                                <th>Durasi</th>
                                <th>Struk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT tp.*, k.nomor_plat, k.jenis_kendaraan, k.merk, k.warna 
                                    FROM transaksi_parkir tp 
                                    JOIN kendaraan k ON tp.kendaraan_id = k.id 
                                    WHERE tp.status = 'masuk'
                                    ORDER BY tp.waktu_masuk ASC";
                            $result = mysqli_query($conn, $query);
                            $no = 1;
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $waktu_masuk = strtotime($row['waktu_masuk']);
                                    $durasi = time() - $waktu_masuk;
                                    $jam = floor($durasi / 3600);
                                    $menit = floor(($durasi % 3600) / 60);
                                    
                                    echo "<tr>";
                                    echo "<td>$no</td>";
                                    echo "<td><strong>" . $row['nomor_plat'] . "</strong></td>";
                                    echo "<td>" . ucfirst($row['jenis_kendaraan']) . "</td>";
                                    echo "<td>" . ($row['merk'] ?? '-') . "</td>";
                                    echo "<td>" . ($row['warna'] ?? '-') . "</td>";
                                    echo "<td>" . $row['area_parkir'] . "</td>";
                                    echo "<td>" . format_date($row['waktu_masuk']) . "</td>";
                                    echo "<td>" . $jam . " jam " . $menit . " menit</td>";
                                    echo "<td><small>" . $row['nomor_struk'] . "</small></td>";
                                    echo "<td style='white-space: nowrap;'>";
                                    echo "<form method='POST' style='display: inline; margin-right: 5px;' action=''>";
                                    echo "<input type='hidden' name='nomor_plat' value='" . $row['nomor_plat'] . "'>";
                                    echo "<button type='submit' name='cari' class='btn btn-warning btn-sm' title='Proses Keluar'>";
                                    echo "<i class='fas fa-sign-out-alt'></i> Bayar";
                                    echo "</button>";
                                    echo "</form>";
                                    
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
                                echo "<tr><td colspan='10' style='text-align: center; padding: 20px;'>Tidak ada kendaraan parkir</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    let isProcessing = false;
    
    function togglePaymentInput() {
        const metode = document.getElementById('metode_bayar').value;
        const tunaiSection = document.getElementById('tunaiSection');
        const nonTunaiSection = document.getElementById('nonTunaiSection');
        
        if (metode === 'tunai') {
            tunaiSection.style.display = 'block';
            nonTunaiSection.style.display = 'none';
            document.getElementById('uang_dibayar').required = true;
        } else {
            tunaiSection.style.display = 'none';
            nonTunaiSection.style.display = 'block';
            document.getElementById('uang_dibayar').required = false;
            document.getElementById('uang_dibayar').value = '0';
            document.getElementById('kembalianBox').classList.remove('show');
        }
    }
    
    function hitungKembalian() {
        const uangInput = document.getElementById('uang_dibayar');
        let uang = uangInput.value.replace(/\./g, '');
        uang = parseInt(uang) || 0;
        
        const biayaText = document.getElementById('totalBiaya').innerText;
        let biaya = biayaText.replace(/[^0-9]/g, '');
        biaya = parseInt(biaya) || 0;
        
        const kembalianBox = document.getElementById('kembalianBox');
        const kembalianNominal = document.getElementById('kembalianNominal');
        const kembalianHidden = document.getElementById('kembalian_hidden');
        const warningKurang = document.getElementById('warningKurang');
        const btnBayar = document.getElementById('btnBayar');
        
        if (uang >= biaya && uang > 0) {
            const kembalian = uang - biaya;
            kembalianNominal.innerHTML = 'Rp ' + kembalian.toLocaleString('id-ID');
            kembalianHidden.value = kembalian;
            kembalianBox.classList.add('show');
            warningKurang.style.display = 'none';
            btnBayar.disabled = false;
            btnBayar.style.opacity = '1';
        } else if (uang > 0 && uang < biaya) {
            kembalianBox.classList.remove('show');
            warningKurang.style.display = 'block';
            btnBayar.disabled = true;
            btnBayar.style.opacity = '0.6';
        } else {
            kembalianBox.classList.remove('show');
            warningKurang.style.display = 'none';
            btnBayar.disabled = false;
            btnBayar.style.opacity = '1';
        }
    }
    
    // Format input rupiah
    document.querySelectorAll('.rupiah-input').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = new Intl.NumberFormat('id-ID').format(value);
        });
    });
    
    // Validate and submit form
    function validateAndSubmit() {
        if (isProcessing) {
            return false;
        }
        
        const metode = document.getElementById('metode_bayar').value;
        
        if (!metode) {
            alert('Pilih metode pembayaran terlebih dahulu!');
            return false;
        }
        
        if (metode === 'tunai') {
            const uangInput = document.getElementById('uang_dibayar');
            let uang = uangInput.value.replace(/\./g, '');
            uang = parseInt(uang) || 0;
            
            const biayaText = document.getElementById('totalBiaya').innerText;
            let biaya = biayaText.replace(/[^0-9]/g, '');
            biaya = parseInt(biaya) || 0;
            
            if (uang < biaya) {
                alert('Uang yang dibayarkan kurang dari total biaya!');
                return false;
            }
            
            if (uang === 0) {
                alert('Masukkan jumlah uang yang dibayarkan!');
                return false;
            }
            
            // Bersihkan format rupiah sebelum submit
            uangInput.value = uang;
        }
        
        // Konfirmasi pembayaran
        const totalBiaya = document.getElementById('totalBiaya').innerText;
        const metodeText = document.getElementById('metode_bayar').options[document.getElementById('metode_bayar').selectedIndex].text;
        
        let konfirmasi = `Konfirmasi Pembayaran:\n\nTotal Biaya: ${totalBiaya}\nMetode: ${metodeText}\n`;
        
        if (metode === 'tunai') {
            const uang = document.getElementById('uang_dibayar').value;
            const kembalian = document.getElementById('kembalianNominal').innerText;
            konfirmasi += `Uang Dibayar: Rp ${uang}\nKembalian: ${kembalian}\n\n`;
        }
        
        konfirmasi += `Lanjutkan pembayaran?`;
        
        if (!confirm(konfirmasi)) {
            return false;
        }
        
        isProcessing = true;
        const btnBayar = document.getElementById('btnBayar');
        btnBayar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        btnBayar.disabled = true;
        
        return true;
    }
    
    // Auto-focus ke input plat
    document.addEventListener('DOMContentLoaded', function() {
        const platInput = document.getElementById('nomor_plat');
        if (platInput && !<?php echo isset($transaksi) ? 'true' : 'false'; ?>) {
            platInput.focus();
        }
        
        togglePaymentInput();
        
        // Set nilai awal uang_dibayar
        const uangInput = document.getElementById('uang_dibayar');
        if (uangInput) {
            uangInput.value = '0';
        }
    });
    </script>
    
    <div class="login-footer" align="center">
        &copy; 2026 Aditya Herlambang Kelas 12 RPL
    </div>
</body>
</html>