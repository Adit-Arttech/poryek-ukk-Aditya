<?php
require_once 'config.php';
check_login();

// PERBAIKAN: Hanya admin yang bisa akses
if (!can_manage_users()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengelola pengguna.';
    redirect('index.php');
}

$error = '';
$success = '';

// Proses CRUD User
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // HAPUS USER
    if ($action == 'delete' && $id > 0) {
        // Cek apakah user sedang login
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Tidak dapat menghapus user yang sedang login!';
            redirect('users.php');
        }
        
        // Cek apakah user memiliki transaksi
        $query_check = "SELECT COUNT(*) as total FROM transaksi_parkir WHERE user_id = '$id'";
        $result_check = mysqli_query($conn, $query_check);
        $check = mysqli_fetch_assoc($result_check);
        
        if ($check['total'] > 0) {
            $_SESSION['error'] = 'Tidak dapat menghapus user karena masih memiliki transaksi!';
            redirect('users.php');
        }
        
        // Hapus user
        $query = "DELETE FROM users WHERE id = '$id'";
        if (mysqli_query($conn, $query)) {
            log_aktivitas("Menghapus user ID: $id", 'users', $id);
            $_SESSION['success'] = 'User berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus user: ' . mysqli_error($conn);
        }
        redirect('users.php');
    }
    
    // TOGGLE STATUS (aktif/nonaktif)
    if ($action == 'toggle' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Tidak dapat mengubah status user yang sedang login!';
            redirect('users.php');
        }
        
        $query = "SELECT status FROM users WHERE id = '$id'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        
        $new_status = ($user['status'] == 'aktif') ? 'nonaktif' : 'aktif';
        $query_update = "UPDATE users SET status = '$new_status' WHERE id = '$id'";
        
        if (mysqli_query($conn, $query_update)) {
            log_aktivitas("Mengubah status user ID: $id menjadi $new_status", 'users', $id);
            $_SESSION['success'] = 'Status user berhasil diubah!';
        } else {
            $_SESSION['error'] = 'Gagal mengubah status user!';
        }
        redirect('users.php');
    }
}

// TAMBAH USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $role = clean_input($_POST['role']);
    $status = clean_input($_POST['status'] ?? 'aktif');
    
    // Validasi
    $errors = [];
    
    if (empty($username)) $errors[] = "Username harus diisi";
    if (empty($password)) $errors[] = "Password harus diisi";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    if (empty($nama_lengkap)) $errors[] = "Nama lengkap harus diisi";
    if (empty($role)) $errors[] = "Role harus dipilih";
    
    // Cek username duplikat
    $query_check = "SELECT id FROM users WHERE username = '$username'";
    $result_check = mysqli_query($conn, $query_check);
    if (mysqli_num_rows($result_check) > 0) {
        $errors[] = "Username '$username' sudah digunakan";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $created_by = $_SESSION['user_id'];
        
        $query = "INSERT INTO users (username, password, nama_lengkap, role, status, created_by) 
                  VALUES ('$username', '$hashed_password', '$nama_lengkap', '$role', '$status', '$created_by')";
        
        if (mysqli_query($conn, $query)) {
            $new_id = mysqli_insert_id($conn);
            log_aktivitas("Menambah user baru: $username", 'users', $new_id);
            $_SESSION['success'] = "User <strong>$username</strong> berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan user: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    redirect('users.php');
}

// EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = clean_input($_POST['id']);
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $role = clean_input($_POST['role']);
    $status = clean_input($_POST['status'] ?? 'aktif');
    
    // Validasi
    $errors = [];
    
    if (empty($username)) $errors[] = "Username harus diisi";
    if (empty($nama_lengkap)) $errors[] = "Nama lengkap harus diisi";
    if (empty($role)) $errors[] = "Role harus dipilih";
    
    // Cek username duplikat (kecuali user ini sendiri)
    $query_check = "SELECT id FROM users WHERE username = '$username' AND id != '$id'";
    $result_check = mysqli_query($conn, $query_check);
    if (mysqli_num_rows($result_check) > 0) {
        $errors[] = "Username '$username' sudah digunakan";
    }
    
    if (empty($errors)) {
        // Update tanpa password
        $query = "UPDATE users SET 
                  username = '$username',
                  nama_lengkap = '$nama_lengkap',
                  role = '$role',
                  status = '$status'
                  WHERE id = '$id'";
        
        // Jika password diisi, update juga password
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = "Password minimal 6 karakter";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET 
                          username = '$username',
                          password = '$hashed_password',
                          nama_lengkap = '$nama_lengkap',
                          role = '$role',
                          status = '$status'
                          WHERE id = '$id'";
            }
        }
        
        if (empty($errors)) {
            if (mysqli_query($conn, $query)) {
                log_aktivitas("Mengedit user ID: $id", 'users', $id);
                $_SESSION['success'] = "User <strong>$username</strong> berhasil diperbarui!";
            } else {
                $_SESSION['error'] = "Gagal memperbarui user: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    redirect('users.php');
}

// Ambil data users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Hitung statistik
$total_users = mysqli_num_rows($result);
$query_admin = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
$result_admin = mysqli_query($conn, $query_admin);
$total_admin = mysqli_fetch_assoc($result_admin)['total'];

$query_petugas = "SELECT COUNT(*) as total FROM users WHERE role = 'petugas'";
$result_petugas = mysqli_query($conn, $query_petugas);
$total_petugas = mysqli_fetch_assoc($result_petugas)['total'];

$query_owner = "SELECT COUNT(*) as total FROM users WHERE role = 'owner'";
$result_owner = mysqli_query($conn, $query_owner);
$total_owner = mysqli_fetch_assoc($result_owner)['total'];

$query_aktif = "SELECT COUNT(*) as total FROM users WHERE status = 'aktif'";
$result_aktif = mysqli_query($conn, $query_aktif);
$total_aktif = mysqli_fetch_assoc($result_aktif)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Style untuk modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
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
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Style untuk form */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group .required {
            color: #f44336;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
        }
        
        /* Style untuk tabel */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Style untuk badge */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #f44336;
            color: white;
        }
        
        .badge-petugas {
            background: #4CAF50;
            color: white;
        }
        
        .badge-owner {
            background: #2196F3;
            color: white;
        }
        
        .badge-aktif {
            background: #4CAF50;
            color: white;
        }
        
        .badge-nonaktif {
            background: #9e9e9e;
            color: white;
        }
        
        /* Style untuk tombol */
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .stat-content h3 {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-content .number {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-content small {
            color: #999;
            font-size: 11px;
        }
        
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-users-cog"></i> Manajemen Pengguna</h2>
                <button onclick="showTambahModal()" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Tambah Pengguna
                </button>
            </div>
            
            <?php show_message(); ?>
            
            <!-- Statistik -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Pengguna</h3>
                        <div class="number"><?php echo $total_users; ?></div>
                        <small><?php echo $total_aktif; ?> aktif</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f44336 0%, #c62828 100%);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Admin</h3>
                        <div class="number"><?php echo $total_admin; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Petugas</h3>
                        <div class="number"><?php echo $total_petugas; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Owner</h3>
                        <div class="number"><?php echo $total_owner; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Cari username, nama lengkap, atau role..." onkeyup="searchTable()">
                <button class="btn btn-primary" onclick="searchTable()">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
            
            <!-- Tabel Pengguna -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Pengguna</h3>
                </div>
                <div class="table-container">
                    <table class="table" id="userTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($user = mysqli_fetch_assoc($result)) {
                                    // Badge role
                                    $role_badge = '';
                                    switch($user['role']) {
                                        case 'admin':
                                            $role_badge = '<span class="badge badge-admin">Admin</span>';
                                            break;
                                        case 'petugas':
                                            $role_badge = '<span class="badge badge-petugas">Petugas</span>';
                                            break;
                                        case 'owner':
                                            $role_badge = '<span class="badge badge-owner">Owner</span>';
                                            break;
                                    }
                                    
                                    // Badge status
                                    $status_badge = $user['status'] == 'aktif' ? 
                                        '<span class="badge badge-aktif"><i class="fas fa-check-circle"></i> Aktif</span>' : 
                                        '<span class="badge badge-nonaktif"><i class="fas fa-ban"></i> Nonaktif</span>';
                                    
                                    echo '<tr>';
                                    echo '<td>' . $user['id'] . '</td>';
                                    echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
                                    echo '<td>' . htmlspecialchars($user['nama_lengkap']) . '</td>';
                                    echo '<td>' . $role_badge . '</td>';
                                    echo '<td>' . $status_badge . '</td>';
                                    echo '<td>' . format_date($user['created_at'], 'd/m/Y') . '</td>';
                                    echo '<td>' . ($user['last_login'] ?? '-') . '</td>';
                                    echo '<td class="btn-group">';
                                    
                                    // Tombol Edit
                                    echo '<button onclick="showEditModal(' . htmlspecialchars(json_encode($user)) . ')" 
                                          class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                          </button>';
                                    
                                    // Tombol Toggle Status
                                    if ($user['id'] != $_SESSION['user_id']) {
                                        $toggle_title = $user['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan';
                                        $toggle_icon = $user['status'] == 'aktif' ? 'fa-ban' : 'fa-check-circle';
                                        echo '<a href="?action=toggle&id=' . $user['id'] . '" 
                                              class="btn btn-info btn-sm" 
                                              onclick="return confirm(\'' . $toggle_title . ' user ' . $user['username'] . '?\')"
                                              title="' . $toggle_title . '">
                                                <i class="fas ' . $toggle_icon . '"></i>
                                              </a>';
                                    }
                                    
                                    // Tombol Hapus
                                    if ($user['id'] != $_SESSION['user_id']) {
                                        echo '<button onclick="showDeleteModal(' . $user['id'] . ', \'' . $user['username'] . '\')" 
                                              class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                              </button>';
                                    } else {
                                        echo '<button class="btn btn-secondary btn-sm" disabled title="Tidak dapat menghapus diri sendiri">
                                                <i class="fas fa-trash"></i>
                                              </button>';
                                    }
                                    
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="8" style="text-align: center; padding: 40px;">';
                                echo '<i class="fas fa-users fa-3x" style="color: #ccc; margin-bottom: 15px; display: block;"></i>';
                                echo '<h4 style="color: #666;">Belum ada data pengguna</h4>';
                                echo '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- MODAL TAMBAH USER -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h3>
                <button onclick="closeModal('tambahModal')" class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password <span class="required">*</span> <small>(minimal 6 karakter)</small></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('tambahModal')" class="btn btn-secondary">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL EDIT USER -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Pengguna</h3>
                <button onclick="closeModal('editModal')" class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password <small>(kosongkan jika tidak diubah)</small></label>
                        <input type="password" name="password" class="form-control" minlength="6">
                        <small style="color: #666;">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Batal</button>
                    <button type="submit" name="edit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL HAPUS -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: #f44336;"></i> Konfirmasi Hapus</h3>
                <button onclick="closeModal('deleteModal')" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Apakah Anda yakin ingin menghapus user ini?</p>
                <p><small class="text-danger">* Aksi ini tidak dapat dibatalkan!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">Batal</button>
                <a href="#" id="deleteLink" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus
                </a>
                 <div class="login-footer">
            &copy; 2026 Aditya Herlambang Kelas 12 RPL
        </div>
            </div>
        </div>
    </div>
    
    <script>
    // Fungsi untuk menampilkan modal tambah
    function showTambahModal() {
        document.getElementById('tambahModal').classList.add('active');
    }
    
    // Fungsi untuk menampilkan modal edit
    function showEditModal(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_nama').value = user.nama_lengkap;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        document.getElementById('editModal').classList.add('active');
    }
    
    // Fungsi untuk menampilkan modal hapus
    function showDeleteModal(userId, username) {
        document.getElementById('deleteMessage').innerHTML = 
            'Apakah Anda yakin ingin menghapus user <strong>"' + username + '"</strong>?';
        document.getElementById('deleteLink').href = '?action=delete&id=' + userId;
        document.getElementById('deleteModal').classList.add('active');
    }
    
    // Fungsi untuk menutup modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // Fungsi pencarian tabel
    function searchTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('userTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length - 1; j++) { // -1 untuk skip kolom aksi
                const cell = cells[j];
                if (cell) {
                    const textValue = cell.textContent || cell.innerText;
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            rows[i].style.display = found ? '' : 'none';
        }
    }
    
    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    
    // Keyboard shortcut: ESC untuk tutup modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
    </script>
</body>
</html>