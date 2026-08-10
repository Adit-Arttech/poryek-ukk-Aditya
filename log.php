<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
check_login();

// HANYA ADMIN YANG BISA AKSES LOG AKTIVITAS
if (!can_view_log()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat melihat log aktivitas.';
    redirect('index.php');
}

// Ambil log aktivitas
$query = "SELECT la.*, u.username, u.nama_lengkap 
          FROM log_aktivitas la 
          LEFT JOIN users u ON la.user_id = u.id 
          ORDER BY la.created_at DESC 
          LIMIT 100";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Sistem Parkir</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php echo get_sidebar_menu(); ?>
        
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-history"></i> Log Aktivitas</h2>
            </div>
            
            <!-- Tabel Log -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Riwayat Aktivitas</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Aktivitas</th>
                                <th>Tabel</th>
                                <th>ID Data</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . format_date($row['created_at']) . "</td>";
                                echo "<td>" . ($row['nama_lengkap'] ?? 'System') . "</td>";
                                echo "<td>{$row['aktivitas']}</td>";
                                echo "<td>{$row['tabel']}</td>";
                                echo "<td>{$row['data_id']}</td>";
                                echo "<td><small>{$row['ip_address']}</small></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>