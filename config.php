<?php
// ============================================
// KONFIGURASI DATABASE - Digital Parking System
// ============================================

// Cegah multiple declaration
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    // Database Configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'parkir_db');

    // Koneksi Database
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Cek Koneksi
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Set karakter set
    mysqli_set_charset($conn, 'utf8mb4');

    // Set timezone Jakarta
    date_default_timezone_set('Asia/Jakarta');

    // Enable error reporting untuk debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // ============================================
    // FUNGSI-FUNGSI UTAMA
    // ============================================

    /**
     * Debugging function
     */
    if (!function_exists('debug')) {
        function debug($data) {
            echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px;'>";
            print_r($data);
            echo "</pre>";
        }
    }

    /**
     * Clean input data untuk keamanan
     */
    if (!function_exists('clean_input')) {
        function clean_input($data) {
            global $conn;
            if (!is_string($data)) return $data;
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            return mysqli_real_escape_string($conn, $data);
        }
    }

    /**
     * Redirect ke halaman tertentu
     */
    if (!function_exists('redirect')) {
        function redirect($url) {
            header("Location: $url");
            exit();
        }
    }

    /**
     * Cek login user
     */
    if (!function_exists('check_login')) {
        function check_login() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['error'] = 'Silakan login terlebih dahulu!';
                redirect('login.php');
            }
        }
    }

    /**
     * Cek role user
     */
    if (!function_exists('require_role')) {
        function require_role($allowed_roles) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
                $_SESSION['error'] = 'Akses ditolak! Anda tidak memiliki izin.';
                redirect('index.php');
            }
        }
    }

    /**
     * Format Rupiah
     */
    if (!function_exists('format_rupiah')) {
        function format_rupiah($angka) {
            if (empty($angka) || $angka == 0) return 'Rp 0';
            return 'Rp ' . number_format($angka, 0, ',', '.');
        }
    }

    /**
     * Format tanggal Indonesia
     */
    if (!function_exists('format_date')) {
        function format_date($date, $format = 'd-m-Y H:i:s') {
            if (empty($date) || $date == '0000-00-00 00:00:00' || $date == null) {
                return '-';
            }
            
            $timestamp = strtotime($date);
            
            if ($format == 'd-m-Y') {
                return date('d-m-Y', $timestamp);
            } elseif ($format == 'd F Y') {
                $bulan = [
                    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];
                $tgl = date('d', $timestamp);
                $bln = $bulan[(int)date('m', $timestamp)];
                $thn = date('Y', $timestamp);
                return "$tgl $bln $thn";
            } elseif ($format == 'H:i') {
                return date('H:i', $timestamp);
            } elseif ($format == 'd/m/Y H:i') {
                return date('d/m/Y H:i', $timestamp);
            } else {
                return date($format, $timestamp);
            }
        }
    }

    /**
     * Generate nomor struk otomatis (menggunakan fungsi database)
     */
    if (!function_exists('generate_nomor_struk')) {
        function generate_nomor_struk() {
            global $conn;
            $query = "SELECT generate_nomor_struk() as nomor_struk";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            return $row['nomor_struk'];
        }
    }

    /**
     * Hitung biaya parkir
     */
    if (!function_exists('hitung_biaya_parkir')) {
        function hitung_biaya_parkir($jenis, $durasi_menit) {
            global $conn;
            
            // Ambil tarif dari database
            $query = "SELECT tarif_per_jam FROM tarif_parkir WHERE jenis_kendaraan = '$jenis'";
            $result = mysqli_query($conn, $query);
            
            if (!$result || mysqli_num_rows($result) == 0) {
                // Default tarif jika tidak ditemukan
                return ($jenis == 'mobil') ? 5000 * ceil($durasi_menit / 60) : 2000 * ceil($durasi_menit / 60);
            }
            
            $tarif = mysqli_fetch_assoc($result);
            $durasi_jam = ceil($durasi_menit / 60);
            
            return $durasi_jam * $tarif['tarif_per_jam'];
        }
    }

    /**
     * LOG AKTIVITAS - SESUAI STRUKTUR DATABASE TERBARU
     * Kolom: user_id, aktivitas, tabel, data_id, ip_address, created_at
     */
    if (!function_exists('log_aktivitas')) {
        function log_aktivitas($aktivitas, $tabel = null, $data_id = null) {
            global $conn;
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $data_id = $data_id ? intval($data_id) : null;
            
            // Gunakan prepared statement untuk keamanan
            $query = "INSERT INTO log_aktivitas (user_id, aktivitas, tabel, data_id, ip_address, created_at) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'issis', $user_id, $aktivitas, $tabel, $data_id, $ip_address);
            
            return mysqli_stmt_execute($stmt);
        }
    }

    /**
     * Get role badge HTML
     */
    if (!function_exists('get_role_badge')) {
        function get_role_badge($role) {
            $badge_class = '';
            $icon = '';
            
            switch ($role) {
                case 'admin':
                    $badge_class = 'badge-danger';
                    $icon = 'fa-crown';
                    break;
                case 'petugas':
                    $badge_class = 'badge-success';
                    $icon = 'fa-user-tie';
                    break;
                case 'owner':
                    $badge_class = 'badge-info';
                    $icon = 'fa-user-cog';
                    break;
                default:
                    $badge_class = 'badge-secondary';
                    $icon = 'fa-user';
            }
            
            return "<span class='badge $badge_class'><i class='fas $icon'></i> " . ucfirst($role) . "</span>";
        }
    }

    /**
     * Get sidebar menu berdasarkan role
     */
    if (!function_exists('get_sidebar_menu')) {
        function get_sidebar_menu() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $current_page = basename($_SERVER['PHP_SELF']);
            $role = $_SESSION['role'] ?? '';
            $nama = $_SESSION['nama_lengkap'] ?? 'Guest';
            
            $menu = '';
            
            // Sidebar wrapper
            $menu .= '<aside class="sidebar">';
            
            // Logo
            $menu .= '<div class="logo">';
            $menu .= '<i class="fas fa-parking"></i>';
            $menu .= '<h1>Digital Parking</h1>';
            $menu .= '</div>';
            
            // User info
            $menu .= '<div class="user-info">';
            $menu .= '<h3>' . htmlspecialchars($nama) . '</h3>';
            $menu .= get_role_badge($role);
            $menu .= '</div>';
            
            // Navigation menu
            $menu .= '<ul class="nav-menu">';
            
            // Dashboard (semua role)
            $menu .= '<li class="nav-item">';
            $menu .= '<a href="index.php" class="nav-link ' . ($current_page == 'index.php' ? 'active' : '') . '">';
            $menu .= '<i class="fas fa-home"></i> <span>Dashboard</span></a></li>';
            
            // Menu untuk PETUGAS
            if ($role == 'petugas') {
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="transaksi_masuk.php" class="nav-link ' . ($current_page == 'transaksi_masuk.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-sign-in-alt"></i> <span>Kendaraan Masuk</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="transaksi_keluar.php" class="nav-link ' . ($current_page == 'transaksi_keluar.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-sign-out-alt"></i> <span>Kendaraan Keluar</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="cetak_struk.php" class="nav-link ' . ($current_page == 'cetak_struk.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-print"></i> <span>Cetak Struk</span></a></li>';
            }
            
            // Menu untuk OWNER
            if ($role == 'owner') {
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="owner_dashboard.php" class="nav-link ' . ($current_page == 'owner_dashboard.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-chart-line"></i> <span>Dashboard Owner</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="transaksi.php" class="nav-link ' . ($current_page == 'transaksi.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-history"></i> <span>Rekap Transaksi</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="laporan.php" class="nav-link ' . ($current_page == 'laporan.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-chart-bar"></i> <span>Laporan</span></a></li>';
            }
            
            // Menu untuk ADMIN
            if ($role == 'admin') {
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="users.php" class="nav-link ' . ($current_page == 'users.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-users-cog"></i> <span>Manajemen User</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="area.php" class="nav-link ' . ($current_page == 'area.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-map-marker-alt"></i> <span>Area Parkir</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="tarif.php" class="nav-link ' . ($current_page == 'tarif.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-tags"></i> <span>Tarif Parkir</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="kendaraan.php" class="nav-link ' . ($current_page == 'kendaraan.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-car"></i> <span>Data Kendaraan</span></a></li>';
                
                $menu .= '<li class="nav-item">';
                $menu .= '<a href="log.php" class="nav-link ' . ($current_page == 'log.php' ? 'active' : '') . '">';
                $menu .= '<i class="fas fa-history"></i> <span>Log Aktivitas</span></a></li>';
            }
            
            // Profile (semua role)
            $menu .= '<li class="nav-item">';
            $menu .= '<a href="profile.php" class="nav-link ' . ($current_page == 'profile.php' ? 'active' : '') . '">';
            $menu .= '<i class="fas fa-user"></i> <span>Profil</span></a></li>';
            
            // Logout (semua role)
            $menu .= '<li class="nav-item">';
            $menu .= '<a href="logout.php" class="nav-link">';
            $menu .= '<i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>';
            
            $menu .= '</ul>';
            $menu .= '</aside>';
            
            return $menu;
        }
    }

    /**
     * Tampilkan pesan error/success
     */
    if (!function_exists('show_message')) {
        function show_message() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">';
                echo '<i class="fas fa-exclamation-circle"></i> ';
                echo $_SESSION['error'];
                echo '</div>';
                unset($_SESSION['error']);
            }
            
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">';
                echo '<i class="fas fa-check-circle"></i> ';
                echo $_SESSION['success'];
                echo '</div>';
                unset($_SESSION['success']);
            }
        }
    }

    // ============================================
    // FUNGSI CEK AKSES (PERMISSION CHECKS)
    // ============================================

    if (!function_exists('can_edit_profile')) {
        function can_edit_profile() {
            return in_array($_SESSION['role'] ?? '', ['admin', 'petugas', 'owner']);
        }
    }

    if (!function_exists('can_manage_area')) {
        function can_manage_area() {
            return ($_SESSION['role'] ?? '') == 'admin';
        }
    }

    if (!function_exists('can_edit_tarif')) {
        function can_edit_tarif() {
            return ($_SESSION['role'] ?? '') == 'admin';
        }
    }

    if (!function_exists('can_manage_kendaraan')) {
        function can_manage_kendaraan() {
            return ($_SESSION['role'] ?? '') == 'admin';
        }
    }

    if (!function_exists('can_do_transaction')) {
        function can_do_transaction() {
            return ($_SESSION['role'] ?? '') == 'petugas';
        }
    }

    if (!function_exists('can_manage_users')) {
        function can_manage_users() {
            return ($_SESSION['role'] ?? '') == 'admin';
        }
    }

    if (!function_exists('can_view_transactions')) {
        function can_view_transactions() {
            return ($_SESSION['role'] ?? '') == 'owner';
        }
    }

    if (!function_exists('can_view_log')) {
        function can_view_log() {
            return ($_SESSION['role'] ?? '') == 'admin';
        }
    }

    if (!function_exists('can_view_report')) {
        function can_view_report() {
            return ($_SESSION['role'] ?? '') == 'owner';
        }
    }

    if (!function_exists('can_print_struk')) {
        function can_print_struk() {
            return ($_SESSION['role'] ?? '') == 'petugas';
        }
    }

    // ============================================
    // FUNGSI-FUNGSI DATABASE
    // ============================================

    /**
     * Get data kendaraan berdasarkan nomor plat
     */
    if (!function_exists('get_kendaraan_by_plat')) {
        function get_kendaraan_by_plat($nomor_plat) {
            global $conn;
            $nomor_plat = clean_input($nomor_plat);
            $query = "SELECT * FROM kendaraan WHERE nomor_plat = '$nomor_plat'";
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
            return null;
        }
    }

    /**
     * Get area yang tersedia berdasarkan jenis kendaraan
     */
    if (!function_exists('get_suggested_area')) {
        function get_suggested_area($jenis_kendaraan) {
            global $conn;
            
            if ($jenis_kendaraan == 'mobil') {
                $query = "SELECT * FROM area_parkir 
                          WHERE status = 'Tersedia' 
                          AND terisi < kapasitas 
                          AND (kode_area LIKE '%M%' OR nama_area LIKE '%mobil%') 
                          ORDER BY kode_area 
                          LIMIT 1";
            } else {
                $query = "SELECT * FROM area_parkir 
                          WHERE status = 'Tersedia' 
                          AND terisi < kapasitas 
                          AND (kode_area LIKE '%R%' OR nama_area LIKE '%motor%') 
                          ORDER BY kode_area 
                          LIMIT 1";
            }
            
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                return mysqli_fetch_assoc($result);
            }
            
            return null;
        }
    }

    /**
     * Get last transaction ID untuk user tertentu
     */
    if (!function_exists('get_last_transaction_id')) {
        function get_last_transaction_id($user_id) {
            global $conn;
            
            $query = "SELECT id FROM transaksi_parkir 
                      WHERE user_id = '$user_id' 
                      ORDER BY id DESC 
                      LIMIT 1";
            
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return $row['id'];
            }
            
            return 0;
        }
    }

    /**
     * Validasi ID transaksi
     */
    if (!function_exists('validate_transaction_id')) {
        function validate_transaction_id($id) {
            global $conn;
            
            if (empty($id) || !is_numeric($id) || $id <= 0) {
                return false;
            }
            
            $id = intval($id);
            $query = "SELECT COUNT(*) as count FROM transaksi_parkir WHERE id = '$id'";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                return $row['count'] > 0;
            }
            
            return false;
        }
    }

    /**
     * Get list role
     */
    if (!function_exists('get_role_list')) {
        function get_role_list() {
            return ['admin', 'petugas', 'owner'];
        }
    }

    /**
     * Validasi password
     */
    if (!function_exists('validate_password')) {
        function validate_password($password) {
            if (strlen($password) < 6) {
                return "Password minimal 6 karakter";
            }
            return true;
        }
    }

    /**
     * Get back URL berdasarkan tipe transaksi
     */
    if (!function_exists('get_back_url')) {
        function get_back_url($type) {
            switch($type) {
                case 'masuk': return 'transaksi_masuk.php';
                case 'keluar': return 'transaksi_keluar.php';
                default: return 'index.php';
            }
        }
    }

    /**
     * Get page name berdasarkan tipe
     */
    if (!function_exists('get_page_name')) {
        function get_page_name($type) {
            switch($type) {
                case 'masuk': return 'Kendaraan Masuk';
                case 'keluar': return 'Kendaraan Keluar';
                default: return 'Dashboard';
            }
        }
    }

    // Inisialisasi session jika belum dimulai
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }

} // END OF CONFIG_LOADED check
?>