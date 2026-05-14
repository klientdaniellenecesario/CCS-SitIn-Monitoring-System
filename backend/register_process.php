<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $course_level = mysqli_real_escape_string($conn, $_POST['course_level']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Validation
    $errors = [];
    
    // Check if passwords match
    if ($password !== $repeat_password) {
        $errors[] = "Passwords do not match!";
    }
    
    // Check password length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long!";
    }
    
    // Check if ID number already exists
    $check_id = "SELECT id FROM users WHERE id_number = '$id_number'";
    $result_id = mysqli_query($conn, $check_id);
    if (mysqli_num_rows($result_id) > 0) {
        $errors[] = "ID Number already registered!";
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result_email = mysqli_query($conn, $check_email);
    if (mysqli_num_rows($result_email) > 0) {
        $errors[] = "Email already registered!";
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: ../register.php');
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into database
    $query = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, course, password, email, address, session_count) 
              VALUES ('$id_number', '$last_name', '$first_name', '$middle_name', '$course_level', '$course', '$hashed_password', '$email', '$address', 0)";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Registration successful! You can now login.";
        header('Location: ../login.php');
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . mysqli_error($conn);
        header('Location: ../register.php');
        exit();
    }
} else {
    header('Location: ../register.php');
    exit();
}
?>