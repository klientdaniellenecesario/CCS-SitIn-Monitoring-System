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
        /* Additional centering styles */
        .register-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            text-align: center;
        }
        
        .register-btn span {
            display: inline-block;
        }
        
        .login-link {
            text-align: center;
            width: 100%;
        }
        
        .login-link p {
            text-align: center;
            margin: 0;
        }
        
        .login-link a {
            display: inline-block;
        }
        
        /* ============================================
           NAVBAR LOGO STYLES - MAKE BIGGER
           ============================================ */
        
        /* Logo container in navbar */
        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Logo image styling */
        .navbar .logo img {
            height: 50px;        /* Change this value to make bigger */
            width: auto;
            object-fit: contain;
        }
        
        /* Logo icon styling if using icon */
        .navbar .logo i {
            font-size: 2.2rem;   /* Change this value to make icon bigger */
            color: #ffd600;
        }
        
        /* Logo text styling */
        .navbar .logo span {
            font-size: 1.1rem;   /* Change this value for text size */
            font-weight: 600;
            white-space: nowrap;
        }
        
        /* ============================================
           CARD LOGO STYLES - MAKE BIGGER
           ============================================ */
        
        /* Card header icon wrapper */
        .card-header .icon-wrapper {
            width: 160px;        /* Change this to make container bigger */
            height: 160px;
            background: transparent;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        /* Logo image inside card */
        .card-header .logo-img {
            width: 160px;        /* Change this to make logo bigger */
            height: 160px;
            object-fit: contain;
        }
        
        /* For multiple logos in card */
        .card-header .logo-img-small {
            width: 130px;        /* Change this for side-by-side logos */
            height: 130px;
            object-fit: contain;
        }
        
        /* Card header icon if using font awesome */
        .card-header .icon-wrapper i {
            font-size: 5rem;     /* Change this for icon size */
            background: linear-gradient(135deg, #ffd600, #ffea8a);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .navbar .logo img {
                height: 35px;
            }
            
            .navbar .logo span {
                font-size: 0.85rem;
                white-space: normal;
            }
            
            .card-header .icon-wrapper {
                width: 100px;
                height: 100px;
            }
            
            .card-header .logo-img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <!-- OPTION 1: Use your custom logo image -->
                    <img src="images/CCS_LOGO.png" alt="CCS Logo" class="navbar-logo-img" style="height: 50px;">
                    <span>College of Computer Studies Sit-in Monitoring System</span>
                    
                    <!-- OPTION 2: If you want to use Font Awesome icon instead -->
                    <!-- <i class="fas fa-graduation-cap"></i>
                    <span>College of Computer Studies Sit-in Monitoring System</span> -->
                    
                    <!-- OPTION 3: If you want logo only (no text) -->
                    <!-- <img src="uploads/uc-logo.png" alt="UC Logo" style="height: 50px;"> -->
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
                    <!-- LOGO SECTION - NO BORDER, BIGGER -->
                    <div class="icon-wrapper">
                        <!-- Option 1: Custom logo image -->
                        <img src="images/UC_LOGO.png" alt="UC Logo" class="logo-img">
                        
                        <!-- Option 2: Multiple logos side by side -->
                        <!-- <div class="logo-group">
                            <img src="uploads/uc-logo.png" alt="UC Logo" class="logo-img-small">
                            <img src="uploads/ccs-logo.png" alt="CCS Logo" class="logo-img-small">
                        </div> -->
                        
                        <!-- Option 3: Keep the icon but bigger -->
                        <!-- <i class="fas fa-user-plus"></i> -->
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

                    <button type="submit" class="register-btn">
                        <span>Register</span>
                    </button>
                </form>

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