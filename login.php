<?php
session_start();
require_once 'config/database.php';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin/admin_dashboard.php');
        exit();
    } else {
        header('Location: student_dashboard.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = mysqli_real_escape_string($conn, $_POST['login_input']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']) ? true : false;
    
    $admin_query = "SELECT * FROM admins WHERE username = '$login_input'";
    $admin_result = mysqli_query($conn, $admin_query);
    
    if (mysqli_num_rows($admin_result) == 1) {
        $admin = mysqli_fetch_assoc($admin_result);
        
        if ($password === $admin['password']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_name'] = $admin['full_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['user_email'] = $admin['email'];
            
            $_SESSION['login_success'] = true;
            $_SESSION['login_message'] = "Welcome Admin! " . $admin['full_name'];
            
            if ($remember) {
                setcookie('user_id', $admin['id'], time() + (86400 * 30), "/");
                setcookie('user_type', 'admin', time() + (86400 * 30), "/");
            }
            
            header('Location: admin/admin_dashboard.php');
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $student_query = "SELECT * FROM users WHERE id_number = '$login_input'";
        $student_result = mysqli_query($conn, $student_query);
        
        if (mysqli_num_rows($student_result) == 1) {
            $student = mysqli_fetch_assoc($student_result);
            
            if (password_verify($password, $student['password'])) {
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['user_name'] = $student['first_name'] . ' ' . $student['last_name'];
                $_SESSION['first_name'] = $student['first_name'];
                $_SESSION['last_name'] = $student['last_name'];
                $_SESSION['id_number'] = $student['id_number'];
                $_SESSION['course'] = $student['course'];
                $_SESSION['course_level'] = $student['course_level'];
                $_SESSION['email'] = $student['email'];
                $_SESSION['address'] = $student['address'];
                $_SESSION['session_count'] = $student['session_count'];
                $_SESSION['profile_picture'] = $student['profile_picture'] ?? 'default-avatar.png';
                
                $_SESSION['login_success'] = true;
                $_SESSION['login_message'] = "Welcome! " . $student['first_name'] . " " . $student['last_name'];
                
                if ($remember) {
                    setcookie('user_id', $student['id'], time() + (86400 * 30), "/");
                    setcookie('user_type', 'student', time() + (86400 * 30), "/");
                }
                
                header('Location: student_dashboard.php');
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "ID Number not found. Please register first.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="frontend/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Make UC Logo BIGGER */
        .icon-wrapper img {
            width: 120px !important;
            height: 120px !important;
            object-fit: contain;
        }
        
        /* Optional: If you want it even bigger */
        .card-header .icon-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <img src="images/CCS_LOGO.png" alt="CCS Logo" style="height: 40px; width: auto;">
                    <span>CCS Sit-in</span>
                </div>
                <div class="nav-links">
                    <a href="#" class="active"><i class="fas fa-home"></i> Home</a>
                    <a href="#"><i class="fas fa-users"></i> Community</a>
                    <a href="#"><i class="fas fa-info-circle"></i> About</a>
                    <a href="#"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                </div>
                <div class="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="login-container">
            <div class="login-card">
                <div class="card-header">
                    <div class="icon-wrapper">
                        <!-- UC LOGO - NOW BIGGER (120x120) -->
                        <img src="images/UC_LOGO.png" alt="UC Logo" style="width: 120px; height: 120px; object-fit: contain;">
                    </div>
                    <h1>Welcome Back!</h1>
                    <p class="subtitle">Sign in to continue your academic journey</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> 
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="">
                    <div class="form-group">
                        <label for="login_input">
                            <i class="fas fa-id-card"></i>
                            ID Number / Admin Username
                        </label>
                        <input type="text" id="login_input" name="login_input" placeholder="Enter your student ID or admin username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Login</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Create Account</a></p>
                </div>

                <div class="features">
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Access</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Track Progress</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-headset"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-password')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>