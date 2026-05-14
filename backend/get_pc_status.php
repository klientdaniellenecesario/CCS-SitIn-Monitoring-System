<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$lab = mysqli_real_escape_string($conn, $_GET['lab'] ?? '524');

// Get active sessions for this lab
$active_sessions = [];
$sessions_query = "SELECT s.*, u.first_name, u.last_name, u.id_number 
                   FROM sit_in_sessions s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.sit_lab = '$lab' AND s.status = 'active'";
$sessions_result = mysqli_query($conn, $sessions_query);
while($row = mysqli_fetch_assoc($sessions_result)) {
    $active_sessions[$row['pc_number']] = $row;
}

// Get pending reservations for this lab
$pending_reservations = [];
$reservations_query = "SELECT r.*, u.first_name, u.last_name, u.id_number 
                       FROM reservations r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.lab = '$lab' AND r.status = 'pending'";
$reservations_result = mysqli_query($conn, $reservations_query);
while($row = mysqli_fetch_assoc($reservations_result)) {
    $pending_reservations[$row['pc_number']] = $row;
}

// Build PC status array
$pc_status = [];
for ($i = 1; $i <= 30; $i++) {
    $pc_number = sprintf('PC-%02d', $i);
    
    if (isset($active_sessions[$pc_number])) {
        $pc_status[$pc_number] = [
            'status' => 'occupied',
            'user' => $active_sessions[$pc_number]['first_name'] . ' ' . $active_sessions[$pc_number]['last_name'],
            'user_id' => $active_sessions[$pc_number]['user_id'],
            'session_id' => $active_sessions[$pc_number]['id']
        ];
    } elseif (isset($pending_reservations[$pc_number])) {
        $pc_status[$pc_number] = [
            'status' => 'requested',
            'user' => $pending_reservations[$pc_number]['first_name'] . ' ' . $pending_reservations[$pc_number]['last_name'],
            'user_id' => $pending_reservations[$pc_number]['user_id'],
            'reservation_id' => $pending_reservations[$pc_number]['id']
        ];
    } else {
        $pc_status[$pc_number] = ['status' => 'available'];
    }
}

echo json_encode($pc_status);
?>