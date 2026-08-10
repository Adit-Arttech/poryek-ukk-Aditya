<?php
require_once 'config.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Cek permission (hanya owner)
if ($_SESSION['role'] != 'owner') {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get parameters
$user_id = isset($_GET['user_id']) ? clean_input($_GET['user_id']) : '';

if (empty($user_id)) {
    echo json_encode([]);
    exit();
}

// Query zona tugas user
$query = "SELECT zona_tugas FROM user_zona WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);

$zona_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $zona_data[] = $row;
}

echo json_encode($zona_data);
?>