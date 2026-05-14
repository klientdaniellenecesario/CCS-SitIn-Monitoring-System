<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_picture'];
    
    // File validation
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $errors = [];
    
    // Check file type
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File size must be less than 5MB.";
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error: " . $file['error'];
    }
    
    if (empty($errors)) {
        // Create unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $user_id . '_' . time() . '.' . $extension;
        $upload_path = '../uploads/' . $filename;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update database
            $query = "UPDATE users SET profile_picture = '$filename' WHERE id = $user_id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['profile_picture'] = $filename;
                $_SESSION['success'] = "Profile picture updated successfully!";
            } else {
                $_SESSION['error'] = "Database update failed.";
            }
        } else {
            $_SESSION['error'] = "Failed to upload file.";
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
    
    header('Location: ../student_dashboard.php');
    exit();
} else {
    header('Location: ../student_dashboard.php');
    exit();
}
?>