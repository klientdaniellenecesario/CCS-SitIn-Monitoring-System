<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    header('Location: ../admin/admin_dashboard.php');
    exit();
}

// Check for login success message
$showLoginPopup = false;
$loginMessage = '';
if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
    $showLoginPopup = true;
    $loginMessage = $_SESSION['login_message'];
    unset($_SESSION['login_success']);
    unset($_SESSION['login_message']);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    if (isset($_COOKIE['user_id'])) {
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('user_type', '', time() - 3600, '/');
    }
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Get session count
$session_count = $user['session_count'];
$profile_picture = isset($user['profile_picture']) && !empty($user['profile_picture']) 
    ? $user['profile_picture'] 
    : 'default-avatar.png';

// Set user name
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : $user['first_name'] . ' ' . $user['last_name'];

// CHECK IF STUDENT HAS ACTIVE SIT-IN SESSION
$has_active_session = false;
$active_session_info = '';
$active_query = mysqli_query($conn, "SELECT id, session_date, session_time, purpose, sit_lab 
                                     FROM sit_in_sessions 
                                     WHERE user_id = $user_id AND status = 'active'");

if (mysqli_num_rows($active_query) > 0) {
    $has_active_session = true;
    $active = mysqli_fetch_assoc($active_query);
    $active_session_info = "You have an active sit-in session since " . 
                           date('M d, Y h:i A', strtotime($active['session_date'] . ' ' . $active['session_time'])) . 
                           " at Lab " . $active['sit_lab'] . " for " . $active['purpose'];
    
    // Create active session notification if not exists
    $check_active_notif = mysqli_query($conn, "SELECT id FROM notifications 
                                                WHERE user_id = $user_id 
                                                AND type = 'active-session' 
                                                AND is_read = 0");
    
    if (mysqli_num_rows($check_active_notif) == 0) {
        $active_notification = "Active Sit-in Session: " . $active_session_info;
        $notif_insert = "INSERT INTO notifications (user_id, message, type, created_at) 
                         VALUES ('$user_id', '$active_notification', 'active-session', NOW())";
        mysqli_query($conn, $notif_insert);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CCS Sit-in Monitoring System</title>
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
                    <a href="student_dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
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
            <!-- Header -->
            <header class="top-header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-grid">
                <!-- Student Information Card -->
                <div class="card student-info-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-graduate"></i> Student Information</h2>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <div class="profile-picture-container" onclick="document.getElementById('profile_file_input').click()">
                                <img src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile Picture" 
                                     class="profile-picture"
                                     onerror="this.src='uploads/default-avatar.png'">
                                <div class="edit-overlay">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </div>
                            </div>
                            <p style="margin-top: 0.5rem; color: #718096; font-size: 0.85rem;">Click on the picture to change</p>
                            
                            <form id="profileUploadForm" action="backend/upload_profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                                <input type="file" name="profile_picture" id="profile_file_input" accept="image/*" onchange="uploadProfilePicture()">
                            </form>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Course:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['course']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Year:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['course_level']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['address']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Session:</span>
                            <span class="info-value session-count"><?php echo $session_count; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Announcement Card -->
                <div class="card announcement-card">
                    <div class="card-header">
                        <h2><i class="fas fa-bullhorn"></i> Announcement</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get announcements from database
                        $announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
                        $announcement_result = mysqli_query($conn, $announcement_query);
                        
                        if (mysqli_num_rows($announcement_result) > 0) {
                            while($ann = mysqli_fetch_assoc($announcement_result)) {
                                echo '<div class="announcement-item">';
                                echo '<div class="announcement-header">';
                                echo '<span class="announcement-author">' . htmlspecialchars($ann['author']) . '</span>';
                                echo '<span class="announcement-date">' . date('M d, Y', strtotime($ann['created_at'])) . '</span>';
                                echo '</div>';
                                echo '<p class="announcement-content">';
                                echo '<strong>' . htmlspecialchars($ann['title']) . '</strong><br>';
                                echo htmlspecialchars($ann['content']);
                                echo '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="announcement-item">';
                            echo '<p class="announcement-content">No announcements yet.</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Rules and Regulations Card -->
                <div class="card rules-card">
                    <div class="card-header">
                        <h2><i class="fas fa-gavel"></i> Rules and Regulation</h2>
                    </div>
                    <div class="card-body">
                        <div class="university-header">
                            <h3>University of Cebu</h3>
                            <h4>COLLEGE OF INFORMATION & COMPUTER STUDIES</h4>
                            <h5>LABORATORY RULES AND REGULATIONS</h5>
                        </div>
                        <p class="rules-text">
                            To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:
                        </p>
                        <ol class="rules-list">
                            <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                            <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                            <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Spinner Modal -->
    <div id="loadingModal" class="modal" style="display: none;">
        <div class="modal-content" style="text-align: center; max-width: 300px;">
            <div class="spinner"></div>
            <h3 style="margin-top: 1rem;">Uploading...</h3>
            <p>Please wait while your picture is being uploaded</p>
        </div>
    </div>

    <!-- Login Success Popup -->
    <?php if ($showLoginPopup): ?>
    <div id="successPopup" class="popup-overlay" style="display: flex;">
        <div class="popup-content">
            <div class="popup-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Successful Login!</h3>
            <p><?php echo htmlspecialchars($loginMessage); ?></p>
            <button onclick="closePopup()" class="popup-btn">OK</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        /* eslint-disable */
        // @ts-nocheck
        
        // Confirmation for logout
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
        
        // Close popup
        function closePopup() {
            var popup = document.getElementById('successPopup');
            if (popup) {
                popup.style.display = 'none';
            }
        }
        
        <?php if ($showLoginPopup): ?>
        // Auto close popup after 3 seconds
        setTimeout(function() {
            var popup = document.getElementById('successPopup');
            if (popup) {
                popup.style.display = 'none';
            }
        }, 3000);
        <?php endif; ?>
        
        // Upload profile picture
        function uploadProfilePicture() {
            var fileInput = document.getElementById('profile_file_input');
            var file = fileInput.files[0];
            
            if (file) {
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large! Maximum size is 5MB.');
                    fileInput.value = '';
                    return;
                }
                
                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, PNG, and GIF files are allowed!');
                    fileInput.value = '';
                    return;
                }
                
                // Show loading modal
                document.getElementById('loadingModal').style.display = 'flex';
                
                // Submit the form
                var form = document.getElementById('profileUploadForm');
                var formData = new FormData(form);
                
                fetch('backend/upload_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.text();
                })
                .then(function(data) {
                    // Hide loading modal
                    document.getElementById('loadingModal').style.display = 'none';
                    // Show success message
                    alert('Profile picture updated successfully!');
                    // Reload the page to show new picture
                    location.reload();
                })
                .catch(function(error) {
                    // Hide loading modal
                    document.getElementById('loadingModal').style.display = 'none';
                    // Show error message
                    alert('Error uploading picture. Please try again.');
                    console.error('Error:', error);
                });
            }
        }
    </script>
</body>
</html>