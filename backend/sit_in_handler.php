<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sit_in'])) {
    $user_id = intval($_POST['user_id']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    $session_date = date('Y-m-d');
    $session_time = date('H:i:s');
    
    // CHECK IF STUDENT HAS ACTIVE SESSION
    $check_active = mysqli_query($conn, "SELECT id, session_date, session_time FROM sit_in_sessions 
                                         WHERE user_id = $user_id AND status = 'active'");
    
    if (mysqli_num_rows($check_active) > 0) {
        $active = mysqli_fetch_assoc($check_active);
        $_SESSION['sit_in_error'] = "Student already has an active sit-in session from " . 
                                     date('M d, Y h:i A', strtotime($active['session_date'] . ' ' . $active['session_time'])) . 
                                     ". Please complete or cancel the current session first.";
        header('Location: ../' . $_POST['redirect_page']);
        exit();
    }
    
    // Check remaining sessions
    $check_student = mysqli_query($conn, "SELECT session_count FROM users WHERE id = $user_id");
    $student_data = mysqli_fetch_assoc($check_student);
    
    if ($student_data['session_count'] > 0) {
        // Insert sit-in record
        $insert = "INSERT INTO sit_in_sessions (user_id, session_date, session_time, purpose, sit_lab, status) 
                   VALUES ('$user_id', '$session_date', '$session_time', '$purpose', '$lab', 'active')";
        
        if (mysqli_query($conn, $insert)) {
            // DECREMENT session count
            mysqli_query($conn, "UPDATE users SET session_count = session_count - 1 WHERE id = $user_id");
            $_SESSION['sit_in_success'] = "Sit-in recorded successfully!";
        } else {
            $_SESSION['sit_in_error'] = "Failed to record sit-in.";
        }
    } else {
        $_SESSION['sit_in_error'] = "Student has no remaining sessions! Please reset sessions first.";
    }
    
    // Redirect back
    $redirect_page = isset($_POST['redirect_page']) ? $_POST['redirect_page'] : 'admin_dashboard.php';
    header('Location: ../' . $redirect_page);
    exit();
}
?>