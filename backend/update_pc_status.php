<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$lab = mysqli_real_escape_string($conn, $_POST['lab']);
$pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
$disabled = intval($_POST['admin_disabled']); // 1 = disable, 0 = enable

$sql = "INSERT INTO pc_status (lab, pc_number, admin_disabled)
        VALUES ('$lab', '$pc_number', $disabled)
        ON DUPLICATE KEY UPDATE admin_disabled = $disabled";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'admin_disabled' => $disabled]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>