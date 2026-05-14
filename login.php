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
    
    // First, check if it's an admin
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
        /* Professional Redesign Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 82, 204, 0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #0052cc;
        }
        
        .logo i {
            font-size: 1.8rem;
            color: #ffd600;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a:hover {
            color: #0052cc;
        }
        
        .mobile-menu {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Login Container */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 6rem 2rem 2rem 2rem;
            background: linear-gradient(135deg, #0052cc 0%, #0066ff 100%);
            position: relative;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 214, 0, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        
        .login-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(0);
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffd600, #0052cc, #ffd600);
            border-radius: 32px 32px 0 0;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .uc-logo {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border: 2px solid #ffd600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .uc-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .card-header h1 {
            font-size: 1.8rem;
            color: #1a202c;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }
        
        .subtitle {
            color: #718096;
            font-size: 0.9rem;
        }
        
        /* Form Styles */
        .login-form {
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0052cc;
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 2.5rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #0052cc;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #4a5568;
            font-size: 0.85rem;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #0052cc;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: #ffd600;
        }
        
        .login-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 82, 204, 0.3);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .register-link p {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .register-link a {
            color: #0052cc;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            color: #ffd600;
        }
        
        .error-message {
            background: #fee;
            border-left: 4px solid #c33;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #c33;
        }
        
        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
            }
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
                border-top: 1px solid #e2e8f0;
            }
            .nav-links.show { display: flex; }
            .mobile-menu { display: block; }
            .login-card { padding: 1.5rem; margin: 1rem; }
            .card-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>CCS Sit-in</span>
                </div>
                <div class="nav-links">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="community.php"><i class="fas fa-users"></i> Community</a>
                    <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
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
                    <div class="uc-logo">
                        <img src="images/UC_LOGO.png" alt="UC Logo">
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
                        <label for="login_input">ID Number / Admin Username</label>
                        <input type="text" id="login_input" name="login_input" placeholder="Enter your student ID or admin username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me"> Remember me
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