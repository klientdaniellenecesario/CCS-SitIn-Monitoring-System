<?php
// Start session at the VERY TOP - before any HTML
session_start();

// Any other PHP code that needs to run before HTML
// (like checking for errors, etc.)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="frontend/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional CSS to center the Register button and login link */
        .registration-form {
            text-align: center;
        }
        
        .register-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .login-link {
            text-align: center;
            width: 100%;
        }
        
        .login-link p {
            text-align: center;
            margin: 0;
        }
        
        /* Ensure the button container is centered */
        .registration-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Make form sections full width while button is centered */
        .form-section {
            width: 100%;
        }
        
        .register-btn {
            width: 100%;
            max-width: 350px;
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
                    <a href="login.php"><i class="fas fa-home"></i> Home</a>
                    <a href="#"><i class="fas fa-users"></i> Community</a>
                    <a href="#"><i class="fas fa-info-circle"></i> About</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="active"><i class="fas fa-user-plus"></i> Register</a>
                </div>
                <div class="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </nav>

        <!-- Registration Container -->
        <div class="registration-container">
            <div class="registration-card">
                <div class="card-header">
                    <div class="icon-wrapper">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1>Sign Up</h1>
                    <p class="subtitle">Join the CCS Sit-in Monitoring System</p>
                </div>

                <?php
                // Display error messages (already have session started at top)
                if (isset($_SESSION['errors'])) {
                    echo '<div class="message error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <div class="error-list">';
                    foreach ($_SESSION['errors'] as $error) {
                        echo '<span>• ' . $error . '</span><br>';
                    }
                    echo '</div></div>';
                    unset($_SESSION['errors']);
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="message error-message">
                            <i class="fas fa-exclamation-circle"></i> 
                            <span>' . $_SESSION['error'] . '</span>
                          </div>';
                    unset($_SESSION['error']);
                }
                ?>

                <form class="registration-form" id="registerForm" action="backend/register_process.php" method="POST">
                    <div class="form-section">
                        <h3><i class="fas fa-id-card"></i> Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_number">ID Number <span class="required">*</span></label>
                                <input type="text" id="id_number" name="id_number" placeholder="e.g., 2024-0001" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" placeholder="Enter your middle name (optional)">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_level">Course Level <span class="required">*</span></label>
                                <select id="course_level" name="course_level" required>
                                    <option value="">Select Year Level</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="course">Program of Study <span class="required">*</span></label>
                                <select id="course" name="course" required>
                                    <option value="">Select Program</option>
                                    <option value="BSIT">BSIT - Information Technology</option>
                                    <option value="BSCS">BSCS - Computer Science</option>
                                    <option value="BSIS">BSIS - Information Systems</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-lock"></i> Account Security</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                                    <i class="fas fa-eye toggle-password" data-target="password"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="repeat_password">Repeat Password <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" id="repeat_password" name="repeat_password" placeholder="Repeat your password" required>
                                    <i class="fas fa-eye toggle-password" data-target="repeat_password"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-envelope"></i> Contact Information</h3>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" placeholder="student@gmail.com" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="address">Address <span class="required">*</span></label>
                                <textarea id="address" name="address" placeholder="Enter your complete address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Register Button - NOW CENTERED -->
                    <button type="submit" class="register-btn">
                        <span>Register</span>
                    </button>
                </form>

                <!-- Login Link - NOW CENTERED -->
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });

        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>