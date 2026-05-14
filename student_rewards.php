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
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Get reward history
$reward_history = mysqli_query($conn, "SELECT * FROM rewards WHERE user_id = $user_id ORDER BY created_at DESC");

// Get reward points summary
$points_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT reward_points, total_reward_points, reward_count FROM users WHERE id = $user_id"));

// Get actual reward count from student_rewards table
$actual_reward_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM student_rewards WHERE user_id = $user_id"));
$reward_count = $actual_reward_count['count'];

// Get reward sessions earned
$reward_sessions = mysqli_query($conn, "SELECT * FROM student_rewards WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rewards-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card i {
            font-size: 2.5rem;
            color: #ffd600;
            margin-bottom: 0.5rem;
        }
        
        .summary-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .summary-card p {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .points-progress {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #4a5568;
        }
        
        .reward-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .reward-item:last-child {
            border-bottom: none;
        }
        
        .reward-icon {
            width: 40px;
            height: 40px;
            background: #e8f5e9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2e7d32;
        }
        
        .reward-details {
            flex: 1;
        }
        
        .reward-details h4 {
            font-size: 0.9rem;
            color: #1a202c;
            margin-bottom: 0.25rem;
        }
        
        .reward-details p {
            font-size: 0.75rem;
            color: #718096;
        }
        
        .reward-points {
            font-weight: 600;
            color: #ffd600;
        }
    </style>
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
                    <h1>My Rewards</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <!-- Rewards Summary -->
            <div class="rewards-summary">
                <div class="summary-card">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $points_summary['reward_points']; ?></h3>
                    <p>Current Points</p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-chart-line"></i>
                    <h3><?php echo $points_summary['total_reward_points']; ?></h3>
                    <p>Total Points Earned</p>
                </div>
                <div class="summary-card">
                    <i class="fas fa-gift"></i>
                    <h3><?php echo $reward_count; ?></h3>
                    <p>Rewards Received</p>
                </div>
            </div>

            <!-- Points Progress -->
            <div class="points-progress">
                <div class="progress-label">
                    <span>Progress to Next Reward</span>
                    <span><?php echo $points_summary['reward_points']; ?> / 3 points</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($points_summary['reward_points'] / 3) * 100; ?>%"></div>
                </div>
                <p style="font-size: 0.75rem; color: #718096; margin-top: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Earn 3 points to get 1 free sit-in session!
                </p>
            </div>

            <!-- Reward History -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-history"></i> Reward History</h2>
                </div>
                <div class="table-responsive">
                    <?php if(mysqli_num_rows($reward_history) > 0): ?>
                        <?php while($reward = mysqli_fetch_assoc($reward_history)): ?>
                        <div class="reward-item">
                            <div class="reward-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="reward-details">
                                <h4>Earned <?php echo $reward['points']; ?> point(s)</h4>
                                <p><?php echo htmlspecialchars($reward['reason']); ?></p>
                                <p><small><?php echo date('M d, Y h:i A', strtotime($reward['created_at'])); ?></small></p>
                            </div>
                            <div class="reward-points">+<?php echo $reward['points']; ?> pts</div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="reward-item">
                            <div class="reward-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="reward-details">
                                <h4>No rewards yet</h4>
                                <p>Complete sit-in sessions and be recognized by admins to earn points!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reward Sessions Earned -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-ticket-alt"></i> Rewards Earned (Free Sessions)</h2>
                </div>
                <div class="table-responsive">
                    <?php if(mysqli_num_rows($reward_sessions) > 0): ?>
                        <?php while($reward_session = mysqli_fetch_assoc($reward_sessions)): ?>
                        <div class="reward-item">
                            <div class="reward-icon" style="background: #e3f2fd; color: #0052cc;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="reward-details">
                                <h4>Free Sit-in Session Earned!</h4>
                                <p>+1 session added to your remaining sessions</p>
                                <p><small><?php echo date('M d, Y h:i A', strtotime($reward_session['created_at'])); ?></small></p>
                            </div>
                            <div class="reward-points">+1 session</div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="reward-item">
                            <div class="reward-icon" style="background: #e3f2fd; color: #0052cc;">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="reward-details">
                                <h4>No rewards earned yet</h4>
                                <p>Earn 3 points to get a free sit-in session!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #ffd600, #ffea8a);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
    </style>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>