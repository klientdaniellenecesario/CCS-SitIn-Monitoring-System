<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']) ? true : false;
    
    // Check if user exists
    $query = "SELECT * FROM users WHERE id_number = '$id_number'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id_number'] = $user['id_number'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['course'] = $user['course'];
            $_SESSION['course_level'] = $user['course_level'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['address'] = $user['address'];
            $_SESSION['session_count'] = $user['session_count'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? 'default-avatar.png';
            
            // Set login success message
            $_SESSION['login_success'] = true;
            $_SESSION['login_message'] = "Welcome! " . $user['first_name'] . " " . $user['last_name'];
            
            // Remember me functionality
            if ($remember) {
                setcookie('user_id', $user['id'], time() + (86400 * 30), "/");
                setcookie('id_number', $user['id_number'], time() + (86400 * 30), "/");
            }
            
            // Redirect to dashboard
            header('Location: ../student_dashboard.php');
            exit();
        } else {
            // Wrong password
            $_SESSION['error'] = "Invalid password. Please try again.";
            header('Location: ../login.php');
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "ID Number not found. Please register first.";
        header('Location: ../login.php');
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>