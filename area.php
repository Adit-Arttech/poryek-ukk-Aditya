<?php
require_once 'config.php';
check_login();

// HANYA ADMIN YANG BISA AKSES
if (!can_manage_area()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengelola area parkir.';
    redirect('index.php');
}

// Tambah area
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $kode_area = strtoupper(clean_input($_POST['kode_area']));
    $nama_area = clean_input($_POST['nama_area']);
    $lokasi = clean_input($_POST['lokasi']);
    $kapasitas = clean_input($_POST['kapasitas']);
    
    if (empty($kode_area) || empty($nama_area) || empty($kapasitas)) {
        $_SESSION['error'] = 'Data tidak lengkap!';
    } else {
        $query = "INSERT INTO area_parkir (kode_area, nama_area, lokasi, kapasitas) 
                 VALUES ('$kode_area', '$nama_area', '$lokasi', '$kapasitas')";
        
        if (mysqli_query($conn, $query)) {
            log_aktivitas("Menambah area parkir $kode_area", 'area_parkir', mysqli_insert_id($conn));
            $_SESSION['success'] = "Area berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan area!";
        }
    }
    redirect('area.php');
}

// Edit area
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = clean_input($_POST['id']);
    $kode_area = strtoupper(clean_input($_POST['kode_area']));
    $nama_area = clean_input($_POST['nama_area']);
    $lokasi = clean_input($_POST['lokasi']);
    $kapasitas = clean_input($_POST['kapasitas']);
    $status = clean_input($_POST['status']);
    
    $query = "UPDATE area_parkir SET 
              kode_area = '$kode_area',
              nama_area = '$nama_area',
              lokasi = '$lokasi',
              kapasitas = '$kapasitas',
              status = '$status'
              WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        log_aktivitas("Mengedit area parkir $kode_area", 'area_parkir', $id);
        $_SESSION['success'] = "Area berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui area!";
    }
    redirect('area.php');
}

// Hapus area
if (isset($_GET['hapus'])) {
    $id = clean_input($_GET['hapus']);
    
    // Cek apakah area masih digunakan
    $query_check = "SELECT COUNT(*) as total FROM transaksi_parkir WHERE area_parkir = 
                   (SELECT kode_area FROM area_parkir WHERE id = '$id')";
    $result_check = mysqli_query($conn, $query_check);
    $check = mysqli_fetch_assoc($result_check);
    
    if ($check['total'] > 0) {
        $_SESSION['error'] = 'Area masih digunakan dalam transaksi!';
    } else {
        $query = "DELETE FROM area_parkir WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            log_aktivitas("Menghapus area parkir", 'area_parkir', $id);
            $_SESSION['success'] = "Area berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus area!";
        }
    }
    redirect('area.php');
}

// Ambil data area
$query = "SELECT * FROM area_parkir ORDER BY kode_area";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Parkir - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-map-marker-alt"></i> Area Parkir</h2>
                <button onclick="showModal('tambah')" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Area
                </button>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Modal -->
            <div id="modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <form method="POST" id="areaForm">
                        <input type="hidden" name="id" id="form_id">
                        <input type="hidden" name="edit" id="form_type">
                        
                        <h3 id="modal_title">Tambah Area Baru</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Kode Area</label>
                                <input type="text" name="kode_area" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Kapasitas</label>
                                <input type="number" name="kapasitas" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Area</label>
                            <input type="text" name="nama_area" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Lokasi</label>
                            <input type="text" name="lokasi" class="form-control">
                        </div>
                        
                        <div class="form-group" id="status_field" style="display: none;">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Tersedia">Tersedia</option>
                                <option value="Penuh">Penuh</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" onclick="hideModal()" class="btn btn-secondary">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabel Area -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Area Parkir</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Area</th>
                                <th>Lokasi</th>
                                <th>Kapasitas</th>
                                <th>Terisi</th>
                                <th>Tersedia</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = mysqli_fetch_assoc($result)) {
                                $tersedia = $row['kapasitas'] - $row['terisi'];
                                $status_class = $row['status'] == 'Tersedia' ? 'badge-success' : 
                                              ($row['status'] == 'Penuh' ? 'badge-danger' : 'badge-warning');
                                echo "<tr>";
                                echo "<td><strong>{$row['kode_area']}</strong></td>";
                                echo "<td>{$row['nama_area']}</td>";
                                echo "<td>{$row['lokasi']}</td>";
                                echo "<td>{$row['kapasitas']}</td>";
                                echo "<td>{$row['terisi']}</td>";
                                echo "<td>$tersedia</td>";
                                echo "<td><span class='badge $status_class'>{$row['status']}</span></td>";
                                echo "<td>";
                                echo "<button onclick='editArea(" . json_encode($row) . ")' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></button> ";
                                echo "<a href='area.php?hapus={$row['id']}' onclick='return confirm(\"Hapus area {$row['nama_area']}?\")' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i></a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    function showModal(type) {
        document.getElementById('modal').style.display = 'block';
        if (type == 'tambah') {
            document.getElementById('modal_title').textContent = 'Tambah Area Baru';
            document.getElementById('form_type').value = 'tambah';
            document.getElementById('status_field').style.display = 'none';
            document.getElementById('areaForm').reset();
        }
    }
    
    function editArea(area) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal_title').textContent = 'Edit Area';
        document.getElementById('form_type').value = 'edit';
        document.getElementById('form_id').value = area.id;
        document.querySelector('input[name="kode_area"]').value = area.kode_area;
        document.querySelector('input[name="nama_area"]').value = area.nama_area;
        document.querySelector('input[name="lokasi"]').value = area.lokasi;
        document.querySelector('input[name="kapasitas"]').value = area.kapasitas;
        document.querySelector('select[name="status"]').value = area.status;
        document.getElementById('status_field').style.display = 'block';
    }
    
    function hideModal() {
        document.getElementById('modal').style.display = 'none';
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