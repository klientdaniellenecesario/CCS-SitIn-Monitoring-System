<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_id = intval($_POST['session_id']);
    $pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    
    // Get user_id for notification
    $session_query = mysqli_query($conn, "SELECT user_id FROM sit_in_sessions WHERE id = $session_id");
    $session = mysqli_fetch_assoc($session_query);
    $user_id = $session['user_id'];
    
    // Update session status
    $update = "UPDATE sit_in_sessions SET status = 'completed' WHERE id = $session_id";
    if (mysqli_query($conn, $update)) {
        // Send notification to student
        $notification = "Your sit-in session at Lab $lab, $pc_number has been ended by the admin.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) 
                             VALUES ('$user_id', '$notification', 'timeout', NOW())");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to end session']);
    }
}
?>