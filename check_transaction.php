<?php
require_once 'config.php';
check_login();

$id = $_GET['id'] ?? 0;

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Transaksi - Sistem Parkir</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2, h3 {
            color: #333;
        }
        
        .debug-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #4CAF50;
            color: white;
        }
        
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        
        .btn:hover {
            background: #1976D2;
        }
        
        .btn-danger {
            background: #f44336;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-success {
            background: #4CAF50;
        }
        
        .btn-success:hover {
            background: #388E3C;
        }
        
        .error {
            color: #f44336;
            background: #ffebee;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .success {
            color: #4CAF50;
            background: #e8f5e8;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-bug'></i> Debugging Transaksi</h1>
        <h3>ID yang dicek: <strong>" . htmlspecialchars($id) . "</strong></h3>
        
        <div class='btn-group'>
            <a href='transaksi_masuk.php' class='btn'>
                <i class='fas fa-arrow-left'></i> Kembali ke Form
            </a>
            <a href='cetak_struk.php?type=masuk&id=" . htmlspecialchars($id) . "' class='btn btn-success' target='_blank'>
                <i class='fas fa-print'></i> Coba Cetak Lagi
            </a>
            <button onclick='window.close()' class='btn btn-danger'>
                <i class='fas fa-times'></i> Tutup
            </button>
        </div>";
        
// 1. Cek koneksi database
echo "<div class='debug-section'>
        <h2>1. Status Koneksi Database</h2>";
if ($conn) {
    echo "<div class='success'>✓ Koneksi database berhasil</div>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
} else {
    echo "<div class='error'>✗ Koneksi database gagal</div>";
}
echo "</div>";

// 2. Cek di tabel transaksi_parkir
echo "<div class='debug-section'>
        <h2>2. Data di Tabel transaksi_parkir</h2>";
$query1 = "SELECT * FROM transaksi_parkir WHERE id = '$id'";
$result1 = mysqli_query($conn, $query1);

if (!$result1) {
    echo "<div class='error'>Error query: " . mysqli_error($conn) . "</div>";
} elseif (mysqli_num_rows($result1) > 0) {
    echo "<div class='success'>✓ Data ditemukan (" . mysqli_num_rows($result1) . " baris)</div>";
    $row1 = mysqli_fetch_assoc($result1);
    echo "<table>";
    foreach ($row1 as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>✗ Data tidak ditemukan di transaksi_parkir</div>";
    
    // Cek apakah ada data dengan ID mendekati
    $query_near = "SELECT id, nomor_struk, waktu_masuk FROM transaksi_parkir ORDER BY id DESC LIMIT 5";
    $result_near = mysqli_query($conn, $query_near);
    if (mysqli_num_rows($result_near) > 0) {
        echo "<h4>5 Transaksi Terbaru:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nomor Struk</th><th>Waktu Masuk</th><th>Aksi</th></tr>";
        while ($row_near = mysqli_fetch_assoc($result_near)) {
            echo "<tr>";
            echo "<td>" . $row_near['id'] . "</td>";
            echo "<td>" . $row_near['nomor_struk'] . "</td>";
            echo "<td>" . $row_near['waktu_masuk'] . "</td>";
            echo "<td><a href='check_transaction.php?id=" . $row_near['id'] . "' class='btn'>Cek</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
echo "</div>";

// 3. Cek data kendaraan (JOIN)
echo "<div class='debug-section'>
        <h2>3. Data Kendaraan (JOIN)</h2>";
$query2 = "SELECT k.* FROM kendaraan k 
           JOIN transaksi_parkir tp ON k.id = tp.kendaraan_id 
           WHERE tp.id = '$id'";
$result2 = mysqli_query($conn, $query2);

if (!$result2) {
    echo "<div class='error'>Error query: " . mysqli_error($conn) . "</div>";
} elseif (mysqli_num_rows($result2) > 0) {
    echo "<div class='success'>✓ Data kendaraan ditemukan</div>";
    $row2 = mysqli_fetch_assoc($result2);
    echo "<table>";
    foreach ($row2 as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>✗ Data kendaraan tidak ditemukan</div>";
}
echo "</div>";

// 4. Cek data user (JOIN)
echo "<div class='debug-section'>
        <h2>4. Data Operator (JOIN)</h2>";
$query3 = "SELECT u.* FROM users u 
           JOIN transaksi_parkir tp ON u.id = tp.user_id 
           WHERE tp.id = '$id'";
$result3 = mysqli_query($conn, $query3);

if (!$result3) {
    echo "<div class='error'>Error query: " . mysqli_error($conn) . "</div>";
} elseif (mysqli_num_rows($result3) > 0) {
    echo "<div class='success'>✓ Data operator ditemukan</div>";
    $row3 = mysqli_fetch_assoc($result3);
    echo "<table>";
    foreach ($row3 as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>✗ Data operator tidak ditemukan</div>";
}
echo "</div>";

// 5. Cek session data
echo "<div class='debug-section'>
        <h2>5. Data Session</h2>";
echo "<table>";
foreach ($_SESSION as $key => $value) {
    echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars(print_r($value, true)) . "</td></tr>";
}
echo "</table>";

// 6. Cek GET parameters
echo "<div class='debug-section'>
        <h2>6. GET Parameters</h2>";
echo "<table>";
foreach ($_GET as $key => $value) {
    echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// 7. Solusi masalah
echo "<div class='debug-section'>
        <h2>7. Solusi Masalah</h2>
        <div class='info-box'>
            <h3>Jika transaksi tidak ditemukan:</h3>
            <ol>
                <li>Pastikan ID transaksi benar</li>
                <li>Coba refresh halaman form</li>
                <li>Cek log error di database</li>
                <li>Pastikan tidak ada masalah koneksi database</li>
            </ol>
            
            <h3>Jika struk tidak otomatis muncul:</h3>
            <ol>
                <li>Pastikan JavaScript diaktifkan</li>
                <li>Cek console browser untuk error</li>
                <li>Coba tekan Ctrl+F5 untuk hard refresh</li>
                <li>Gunakan tombol 'Coba Cetak Lagi' di atas</li>
            </ol>
        </div>
    </div>";

// 8. Tombol aksi
echo "<div style='margin-top: 30px; text-align: center;'>
        <a href='transaksi_masuk.php' class='btn'>
            <i class='fas fa-arrow-left'></i> Kembali ke Form Masuk
        </a>
        <a href='cetak_struk.php?type=masuk&id=" . htmlspecialchars($id) . "' class='btn btn-success' target='_blank'>
            <i class='fas fa-print'></i> Coba Cetak Struk
        </a>
        <a href='index.php' class='btn'>
            <i class='fas fa-home'></i> Dashboard
        </a>
    </div>";

echo "</div>
</body>
</html>";
?>