<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Get admin name
$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

// Get all students with correct reward count from student_rewards table
$students = mysqli_query($conn, "SELECT u.id, u.id_number, u.first_name, u.last_name, u.course, u.reward_points, u.total_reward_points, 
                                         (SELECT COUNT(*) FROM student_rewards WHERE user_id = u.id) as reward_count 
                                  FROM users u 
                                  WHERE u.role = 'student' OR u.role IS NULL 
                                  ORDER BY u.last_name ASC");
$error = '';
$success = '';

// Handle add points
if (isset($_POST['add_points'])) {
    $user_id = intval($_POST['user_id']);
    $points = intval($_POST['points']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $session_id = !empty($_POST['session_id']) ? intval($_POST['session_id']) : NULL;
    
    // Get current user data
    $user_query = mysqli_query($conn, "SELECT reward_points, total_reward_points, reward_count, session_count FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($user_query);
    
    $new_points = $user['reward_points'] + $points;
    $new_total_points = $user['total_reward_points'] + $points;
    $reward_earned = false;
    $reward_message = '';
    
    // Check if points reached 3 or more
    if ($new_points >= 3) {
        $rewards_earned = floor($new_points / 3);
        $new_points = $new_points % 3;
        $new_reward_count = $user['reward_count'] + $rewards_earned;
        $new_session_count = $user['session_count'] + $rewards_earned;
        
        // Update session count
        mysqli_query($conn, "UPDATE users SET session_count = session_count + $rewards_earned WHERE id = $user_id");
        
        // Record the reward
        for ($i = 0; $i < $rewards_earned; $i++) {
            mysqli_query($conn, "INSERT INTO student_rewards (user_id, reward_type, points_used, session_added) 
                                 VALUES ('$user_id', 'session', 3, 1)");
        }
        
        $reward_message = " and earned $rewards_earned reward session(s)!";
        $reward_earned = true;
    }
    
    // Update user points
    $update = "UPDATE users SET 
               reward_points = $new_points,
               total_reward_points = $new_total_points,
               reward_count = reward_count + " . ($reward_earned ? floor($points / 3) : 0) . "
               WHERE id = $user_id";
    
    if (mysqli_query($conn, $update)) {
        // Insert reward record
        $insert = "INSERT INTO rewards (user_id, points, total_points, reward_count, session_id, reason) 
                   VALUES ('$user_id', '$points', '$new_total_points', '" . ($user['reward_count'] + ($reward_earned ? floor($points / 3) : 0)) . "', " . ($session_id ? "'$session_id'" : "NULL") . ", '$reason')";
        mysqli_query($conn, $insert);
        
        $success = "Added $points point(s) to student!$reward_message";
    } else {
        $error = "Failed to add points.";
    }
}

// Get recent sit-in sessions for dropdown
$recent_sessions = mysqli_query($conn, "SELECT s.id, u.first_name, u.last_name, u.id_number, s.purpose, s.session_date 
                                        FROM sit_in_sessions s 
                                        JOIN users u ON s.user_id = u.id 
                                        WHERE s.status = 'completed'
                                        ORDER BY s.session_date DESC LIMIT 50");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Reward - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reward-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .reward-card h3 {
            font-size: 1.1rem;
            color: #1a202c;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .reward-card h3 i {
            color: #ffd600;
        }
        
        .points-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .points-badge.low {
            background: #fff3e0;
            color: #ed6c02;
        }
        
        .points-badge.medium {
            background: #e3f2fd;
            color: #0052cc;
        }
        
        .points-badge.high {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #ffd600, #ffea8a);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .points-table th, .points-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .points-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>College of Computer Studies</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i><span>Home</span>
                </a>
                <a href="admin_search.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_search.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i><span>Search</span>
                </a>
                <a href="admin_students.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i><span>Students</span>
                </a>
                <a href="admin_sitins.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sitins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i><span>Sit-in</span>
                </a>
                <a href="admin_view_sitins.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_view_sitins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-eye"></i><span>View Sit-in Records</span>
                </a>
                <a href="admin_feedback_reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_feedback_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comment-dots"></i><span>Feedback Reports</span>
                </a>
                <a href="admin_reservation.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i><span>Reservation</span>
                </a>
                <a href="admin_announcements.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_announcements.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i><span>Announcements</span>
                </a>
                <a href="admin_add_reward.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_add_reward.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gift"></i><span>Add Reward</span>
                </a>
                <a href="admin_leaderboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_leaderboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i><span>Leaderboard</span>
                </a>
                <a href="../logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Add Reward Points</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Points Form -->
            <div class="reward-card">
                <h3><i class="fas fa-plus-circle"></i> Add Reward Points</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="user_id">Select Student</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">Select Student</option>
                                <?php 
                                mysqli_data_seek($students, 0);
                                while($student = mysqli_fetch_assoc($students)): 
                                ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['id_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['course'] . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="points">Points to Add (1-3)</label>
                            <input type="number" name="points" id="points" class="form-control" min="1" max="3" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_id">Associated Sit-in Session (Optional)</label>
                            <select name="session_id" id="session_id" class="form-control">
                                <option value="">Select a session (optional)</option>
                                <?php while($session = mysqli_fetch_assoc($recent_sessions)): ?>
                                <option value="<?php echo $session['id']; ?>">
                                    <?php echo $session['id_number'] . ' - ' . $session['purpose'] . ' (' . date('M d, Y', strtotime($session['session_date'])) . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason</label>
                            <input type="text" name="reason" id="reason" class="form-control" placeholder="e.g., Excellent performance, Good behavior, etc." required>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" name="add_points" class="btn-submit">
                            <i class="fas fa-gift"></i> Add Points
                        </button>
                    </div>
                </form>
            </div>

            <!-- Student Points Overview -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-trophy"></i> Student Points Overview</h2>
                </div>
                <div class="table-responsive">
                    <table class="points-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Current Points</th>
                                <th>Progress to Next Reward</th>
                                <th>Total Points Earned</th>
                                <th>Rewards Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($students, 0);
                            while($student = mysqli_fetch_assoc($students)): 
                                $progress = ($student['reward_points'] / 3) * 100;
                                $points_class = $student['reward_points'] == 0 ? 'low' : ($student['reward_points'] >= 2 ? 'high' : 'medium');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td>
                                    <span class="points-badge <?php echo $points_class; ?>">
                                        <?php echo $student['reward_points']; ?> / 3
                                    </span>
                                </td>
                                <td style="width: 150px;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </td>
                                <td><?php echo $student['total_reward_points']; ?></td>
                                <td><?php echo $student['reward_count']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show student info when selected
        document.getElementById('user_id').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
        });
    </script>
</body>
</html>