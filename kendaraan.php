<?php
require_once 'config.php';
check_login();

// HANYA ADMIN YANG BISA AKSES CRUD KENDARAAN
if (!can_manage_kendaraan()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengelola data kendaraan.';
    redirect('index.php');
}

$error = '';
$success = '';

// Tambah kendaraan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nomor_plat = strtoupper(clean_input($_POST['nomor_plat']));
    $jenis_kendaraan = clean_input($_POST['jenis_kendaraan']);
    $merk = clean_input($_POST['merk']);
    $warna = clean_input($_POST['warna']);
    
    if (empty($nomor_plat) || empty($jenis_kendaraan) || empty($merk) || empty($warna)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Cek duplikat
        $query = "SELECT id FROM kendaraan WHERE nomor_plat = '$nomor_plat'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Nomor plat <strong>$nomor_plat</strong> sudah terdaftar!";
        } else {
            $query = "INSERT INTO kendaraan (nomor_plat, jenis_kendaraan, merk, warna) 
                     VALUES ('$nomor_plat', '$jenis_kendaraan', '$merk', '$warna')";
            
            if (mysqli_query($conn, $query)) {
                $success = "Kendaraan berhasil ditambahkan!";
                log_aktivitas("Menambah kendaraan $nomor_plat", 'kendaraan', mysqli_insert_id($conn));
            } else {
                $error = "Gagal menambahkan kendaraan: " . mysqli_error($conn);
            }
        }
    }
}

// Edit kendaraan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = clean_input($_POST['id']);
    $nomor_plat = strtoupper(clean_input($_POST['nomor_plat']));
    $jenis_kendaraan = clean_input($_POST['jenis_kendaraan']);
    $merk = clean_input($_POST['merk']);
    $warna = clean_input($_POST['warna']);
    
    // Validasi
    if (empty($nomor_plat) || empty($jenis_kendaraan) || empty($merk) || empty($warna)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Cek duplikat
        $query = "SELECT id FROM kendaraan WHERE nomor_plat = '$nomor_plat' AND id != '$id'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Nomor plat <strong>$nomor_plat</strong> sudah digunakan!";
        } else {
            $query = "UPDATE kendaraan 
                     SET nomor_plat = '$nomor_plat',
                         jenis_kendaraan = '$jenis_kendaraan',
                         merk = '$merk',
                         warna = '$warna'
                     WHERE id = '$id'";
            
            if (mysqli_query($conn, $query)) {
                $success = "Data kendaraan berhasil diperbarui!";
                log_aktivitas("Mengedit kendaraan $nomor_plat", 'kendaraan', $id);
            } else {
                $error = "Gagal memperbarui data: " . mysqli_error($conn);
            }
        }
    }
}

// Hapus kendaraan
if (isset($_GET['hapus'])) {
    $id = clean_input($_GET['hapus']);
    
    // Cek apakah kendaraan sedang parkir
    $query = "SELECT COUNT(*) as total FROM transaksi_parkir 
              WHERE kendaraan_id = '$id' AND status = 'masuk'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['total'] > 0) {
        $error = "Tidak dapat menghapus kendaraan yang sedang parkir!";
    } else {
        $query = "DELETE FROM kendaraan WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            $success = "Kendaraan berhasil dihapus!";
            log_aktivitas("Menghapus kendaraan", 'kendaraan', $id);
        } else {
            $error = "Gagal menghapus kendaraan: " . mysqli_error($conn);
        }
    }
}

// Ambil data kendaraan
$query = "SELECT * FROM kendaraan ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Hitung statistik
$total_kendaraan = mysqli_num_rows($result);
$query_mobil = "SELECT COUNT(*) as total FROM kendaraan WHERE jenis_kendaraan = 'mobil'";
$result_mobil = mysqli_query($conn, $query_mobil);
$total_mobil = mysqli_fetch_assoc($result_mobil)['total'];

$query_motor = "SELECT COUNT(*) as total FROM kendaraan WHERE jenis_kendaraan = 'motor'";
$result_motor = mysqli_query($conn, $query_motor);
$total_motor = mysqli_fetch_assoc($result_motor)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kendaraan - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-car"></i> Daftar Kendaraan</h2>
                <button onclick="showTambahForm()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Kendaraan
                </button>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistik -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Kendaraan</h3>
                        <div class="number"><?php echo $total_kendaraan; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Mobil</h3>
                        <div class="number"><?php echo $total_mobil; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Motor</h3>
                        <div class="number"><?php echo $total_motor; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Form -->
            <div id="formModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle"><i class="fas fa-plus"></i> Tambah Kendaraan Baru</h3>
                        <button onclick="hideForm()" class="close-btn">&times;</button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" id="formId" name="id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nomor Plat <span class="required">*</span></label>
                                <input type="text" id="modal_nomor_plat" name="nomor_plat" class="form-control" 
                                       required placeholder="Contoh: B 1234 ABC"
                                       oninput="this.value = this.value.toUpperCase()">
                            </div>
                            
                            <div class="form-group">
                                <label>Jenis Kendaraan <span class="required">*</span></label>
                                <select id="modal_jenis_kendaraan" name="jenis_kendaraan" class="form-control" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="mobil">Mobil</option>
                                    <option value="motor">Motor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Merk <span class="required">*</span></label>
                                <input type="text" id="modal_merk" name="merk" class="form-control" 
                                       required placeholder="Contoh: Toyota">
                            </div>
                            
                            <div class="form-group">
                                <label>Warna <span class="required">*</span></label>
                                <input type="text" id="modal_warna" name="warna" class="form-control" 
                                       required placeholder="Contoh: Hitam">
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" id="submitBtn" name="tambah" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <button type="button" onclick="hideForm()" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabel Kendaraan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Kendaraan</h3>
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
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td><strong>" . $row['nomor_plat'] . "</strong></td>";
                                echo "<td>";
                                if ($row['jenis_kendaraan'] == 'mobil') {
                                    echo "<span class='badge badge-info'>Mobil</span>";
                                } else {
                                    echo "<span class='badge badge-success'>Motor</span>";
                                }
                                echo "</td>";
                                echo "<td>" . ($row['merk'] ?: '-') . "</td>";
                                echo "<td>" . ($row['warna'] ?: '-') . "</td>";
                                echo "<td>" . format_date($row['created_at'], 'd-m-Y') . "</td>";
                                echo "<td>";
                                echo "<button onclick='editKendaraan(" . json_encode($row) . ")' class='btn btn-warning btn-sm'>
                                        <i class='fas fa-edit'></i>
                                      </button>";
                                echo "<a href='kendaraan.php?hapus=" . $row['id'] . "' 
                                       class='btn btn-danger btn-sm' 
                                       onclick='return confirm(\"Hapus kendaraan " . $row['nomor_plat'] . "?\")'>
                                        <i class='fas fa-trash'></i>
                                      </a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            
                            if ($total_kendaraan == 0) {
                                echo "<tr><td colspan='7' style='text-align: center; padding: 20px;'>Belum ada data kendaraan</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        width: 90%;
        max-width: 600px;
        border-radius: 10px;
        padding: 25px;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    
    .close-btn:hover {
        color: #f44336;
    }
    
    .form-buttons {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .required {
        color: #f44336;
    }
    
    .btn-sm {
        padding: 5px 10px;
        margin: 0 2px;
    }
    </style>
    
    <script>
    function showTambahForm() {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Tambah Kendaraan Baru';
        document.getElementById('formId').value = '';
        document.getElementById('modal_nomor_plat').value = '';
        document.getElementById('modal_jenis_kendaraan').value = '';
        document.getElementById('modal_merk').value = '';
        document.getElementById('modal_warna').value = '';
        document.getElementById('submitBtn').name = 'tambah';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
        document.getElementById('formModal').style.display = 'flex';
    }
    
    function editKendaraan(data) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Kendaraan';
        document.getElementById('formId').value = data.id;
        document.getElementById('modal_nomor_plat').value = data.nomor_plat;
        document.getElementById('modal_jenis_kendaraan').value = data.jenis_kendaraan;
        document.getElementById('modal_merk').value = data.merk || '';
        document.getElementById('modal_warna').value = data.warna || '';
        document.getElementById('submitBtn').name = 'edit';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update';
        document.getElementById('formModal').style.display = 'flex';
    }
    
    function hideForm() {
        document.getElementById('formModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            hideForm();
        }
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