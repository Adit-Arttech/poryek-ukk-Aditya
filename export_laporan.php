<?php
require_once 'config.php';
check_login();

// HANYA OWNER YANG BISA AKSES LAPORAN
if (!can_view_report()) {
    $_SESSION['error'] = 'Akses ditolak! Hanya owner yang dapat melihat laporan.';
    redirect('index.php');
}

// Filter tanggal
$start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : date('Y-m-d');
$jenis_kendaraan = isset($_GET['jenis_kendaraan']) ? clean_input($_GET['jenis_kendaraan']) : '';

// Query laporan
$conditions = ["tp.status = 'keluar'"];
if ($jenis_kendaraan && $jenis_kendaraan != 'all') {
    $conditions[] = "k.jenis_kendaraan = '$jenis_kendaraan'";
}

$date_column = "DATE(tp.waktu_keluar)";
$conditions[] = "$date_column BETWEEN '$start_date' AND '$end_date'";
$where = "WHERE " . implode(' AND ', $conditions);

$query = "SELECT 
            $date_column as tanggal,
            COUNT(*) as total_transaksi,
            SUM(tp.biaya) as total_pendapatan,
            SUM(CASE WHEN k.jenis_kendaraan = 'mobil' THEN 1 ELSE 0 END) as total_mobil,
            SUM(CASE WHEN k.jenis_kendaraan = 'motor' THEN 1 ELSE 0 END) as total_motor
          FROM transaksi_parkir tp
          JOIN kendaraan k ON tp.kendaraan_id = k.id
          $where
          GROUP BY $date_column
          ORDER BY tanggal DESC";

$result = mysqli_query($conn, $query);

// Set header untuk export Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_parkir_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output HTML untuk Excel
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Laporan Parkir</title>';
echo '<style>
        th { background-color: #4CAF50; color: white; padding: 8px; text-align: center; }
        td { padding: 6px; border: 1px solid #ddd; }
        .header { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .subheader { text-align: center; margin-bottom: 20px; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
      </style>';
echo '</head>';
echo '<body>';

// Header Laporan
echo '<div class="header">LAPORAN PARKIR MALL CENTRAL</div>';
echo '<div class="subheader">Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</div>';
echo '<div class="subheader">Dicetak: ' . date('d/m/Y H:i:s') . '</div>';
echo '<div class="subheader">User: ' . $_SESSION['nama_lengkap'] . '</div>';

// Tabel Laporan
echo '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
echo '<thead>';
echo '<tr>';
echo '<th>No</th>';
echo '<th>Tanggal</th>';
echo '<th>Hari</th>';
echo '<th>Total Transaksi</th>';
echo '<th>Mobil</th>';
echo '<th>Motor</th>';
echo '<th>Pendapatan</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$no = 1;
$grand_total = [
    'transaksi' => 0,
    'mobil' => 0,
    'motor' => 0,
    'pendapatan' => 0
];

$days = [
    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis', 'Friday' => "Jum'at", 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $grand_total['transaksi'] += $row['total_transaksi'];
        $grand_total['mobil'] += $row['total_mobil'];
        $grand_total['motor'] += $row['total_motor'];
        $grand_total['pendapatan'] += $row['total_pendapatan'];
        
        $day_name = date('l', strtotime($row['tanggal']));
        $hari = $days[$day_name] ?? $day_name;
        
        echo '<tr>';
        echo '<td align="center">' . $no . '</td>';
        echo '<td align="center">' . date('d-m-Y', strtotime($row['tanggal'])) . '</td>';
        echo '<td>' . $hari . '</td>';
        echo '<td align="center">' . number_format($row['total_transaksi']) . '</td>';
        echo '<td align="center">' . number_format($row['total_mobil']) . '</td>';
        echo '<td align="center">' . number_format($row['total_motor']) . '</td>';
        echo '<td align="right">' . number_format($row['total_pendapatan'], 0, ',', '.') . '</td>';
        echo '</tr>';
        $no++;
    }
} else {
    echo '<tr><td colspan="7" align="center">Tidak ada data untuk periode yang dipilih</td></tr>';
}

// Footer Total
echo '<tr class="total-row">';
echo '<td colspan="3" align="right"><strong>TOTAL</strong></td>';
echo '<td align="center"><strong>' . number_format($grand_total['transaksi']) . '</strong></td>';
echo '<td align="center"><strong>' . number_format($grand_total['mobil']) . '</strong></td>';
echo '<td align="center"><strong>' . number_format($grand_total['motor']) . '</strong></td>';
echo '<td align="right"><strong>' . number_format($grand_total['pendapatan'], 0, ',', '.') . '</strong></td>';
echo '</tr>';

echo '</tbody>';
echo '</table>';

// Ringkasan
echo '<br><br>';
echo '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
echo '<tr><th colspan="2">RINGKASAN LAPORAN</th></tr>';
echo '<tr><td width="50%">Total Hari</td><td>' . ((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1) . ' hari</td></tr>';
echo '<tr><td>Rata-rata Pendapatan per Hari</td><td>Rp ' . number_format($grand_total['pendapatan'] / max(1, ((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1)), 0, ',', '.') . '</td></tr>';
echo '<tr><td>Rata-rata Transaksi per Hari</td><td>' . number_format($grand_total['transaksi'] / max(1, ((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1)), 1) . ' transaksi</td></tr>';
echo '<tr><td>Persentase Mobil</td><td>' . ($grand_total['transaksi'] > 0 ? round(($grand_total['mobil'] / $grand_total['transaksi']) * 100, 1) : 0) . '%</td></tr>';
echo '<tr><td>Persentase Motor</td><td>' . ($grand_total['transaksi'] > 0 ? round(($grand_total['motor'] / $grand_total['transaksi']) * 100, 1) : 0) . '%</td></tr>';
echo '</table>';

echo '</body>';
echo '</html>';
?>