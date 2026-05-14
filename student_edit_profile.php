<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$success = '';
$error = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $course_level = mysqli_real_escape_string($conn, $_POST['course_level']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Check if email already exists for another user
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email already in use by another account!";
    } else {
        $update = "UPDATE users SET 
                   id_number = '$id_number',
                   last_name = '$last_name',
                   first_name = '$first_name',
                   middle_name = '$middle_name',
                   course_level = '$course_level',
                   course = '$course',
                   email = '$email',
                   address = '$address'
                   WHERE id = $user_id";
        
        if (mysqli_query($conn, $update)) {
            // Update session variables
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
                if (mysqli_query($conn, $update)) {
                    $password_success = "Password changed successfully!";
                } else {
                    $password_error = "Failed to change password.";
                }
            } else {
                $password_error = "New password must be at least 8 characters long!";
            }
        } else {
            $password_error = "New passwords do not match!";
        }
    } else {
        $password_error = "Current password is incorrect!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>CCS Sit-in Monitoring System</span>
                </div>
            </div>
                <nav class="sidebar-nav">
                    <a href="student_dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                    <a href="student_notifications.php" class="nav-item"><i class="fas fa-bell"></i><span>Notification</span></a>
                    <a href="student_edit_profile.php" class="nav-item"><i class="fas fa-edit"></i><span>Edit Profile</span></a>
                    <a href="student_history.php" class="nav-item"><i class="fas fa-history"></i><span>History</span></a>
                    <a href="student_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                    <a href="student_rewards.php" class="nav-item"><i class="fas fa-gift"></i><span>Rewards</span></a>
                    <a href="student_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                    <a href="logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i><span>Log out</span>
                    </a>
                </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <h1>Edit Profile</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Profile Information Card -->
            <div class="profile-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_number">ID Number</label>
                                <input type="text" id="id_number" name="id_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['id_number']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_level">Course Level</label>
                                <select name="course_level" id="course_level" class="form-control" required>
                                    <option value="1" <?php echo $user['course_level'] == 1 ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo $user['course_level'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $user['course_level'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $user['course_level'] == 4 ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="course">Course</label>
                                <select name="course" id="course" class="form-control" required>
                                    <option value="BSIT" <?php echo $user['course'] == 'BSIT' ? 'selected' : ''; ?>>BSIT - Information Technology</option>
                                    <option value="BSCS" <?php echo $user['course'] == 'BSCS' ? 'selected' : ''; ?>>BSCS - Computer Science</option>
                                    <option value="BSIS" <?php echo $user['course'] == 'BSIS' ? 'selected' : ''; ?>>BSIS - Information Systems</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="update_profile" class="btn-save">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="profile-card">
                <div class="card-header">
                    <h2><i class="fas fa-key"></i> Change Password</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($password_success)): ?>
                        <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $password_success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($password_error)): ?>
                        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $password_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="current_password">Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                    <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                                </div>
                                <small class="form-hint">Password must be at least 8 characters</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="change_password" class="btn-change-password">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
        
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