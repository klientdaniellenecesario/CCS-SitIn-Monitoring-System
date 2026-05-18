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

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header('Location: student_notifications.php');
    exit();
}

// Mark single as read
if (isset($_POST['mark_read'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $user_id");
    header('Location: student_notifications.php');
    exit();
}

// Delete notification
if (isset($_POST['delete_notification'])) {
    $notif_id = intval($_POST['notif_id']);
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $notif_id AND user_id = $user_id");
    header('Location: student_notifications.php');
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get all notifications (not just unread)
$notifications = mysqli_query($conn, "SELECT * FROM notifications 
                                      WHERE user_id = $user_id 
                                      ORDER BY created_at DESC 
                                      LIMIT $offset, $limit");

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id");
$total_rows = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_rows / $limit);

// Get counts
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notifications-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-bottom: 2px solid #ffd600;
        }
        
        .notifications-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .notifications-title i {
            font-size: 1.25rem;
            color: #ffd600;
        }
        
        .notifications-title h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
        }
        
        .notification-badge {
            background: #0052cc;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
        }
        
        .mark-all-btn {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .mark-all-btn:hover {
            background: #cbd5e0;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
            position: relative;
        }
        
        .notification-item:hover {
            background: #f7fafc;
        }
        
        .notification-item.unread {
            background: #fffef7;
            border-left: 3px solid #ffd600;
        }
        
        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon.timeout {
            background: #fee;
            color: #c33;
        }
        
        .notification-icon.active-session {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .notification-icon.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .notification-icon.warning {
            background: #fff3e0;
            color: #ed6c02;
        }
        
        .notification-icon.info {
            background: #e3f2fd;
            color: #0052cc;
        }
        
        .notification-icon i {
            font-size: 1.2rem;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            color: #2d3748;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.35rem;
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            color: #a0aec0;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .mark-read-btn, .delete-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.3rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.75rem;
        }
        
        .mark-read-btn {
            color: #0052cc;
        }
        
        .mark-read-btn:hover {
            background: #e3f2fd;
        }
        
        .delete-btn {
            color: #c33;
        }
        
        .delete-btn:hover {
            background: #fee;
        }
        
        .no-notifications {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }
        
        .no-notifications i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
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
                    <a href="student_notifications.php" class="nav-item active"><i class="fas fa-bell"></i><span>Notification</span></a>
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
                    <h1>Notifications</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <!-- Notifications Container -->
            <div class="notifications-container">
                <div class="notifications-header">
                    <div class="notifications-title">
                        <i class="fas fa-bell"></i>
                        <h2>All Notifications</h2>
                        <?php if($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?> unread</span>
                        <?php endif; ?>
                    </div>
                    <?php if($total_rows > 0): ?>
                        <a href="?mark_all_read=1" class="mark-all-btn">
                            <i class="fas fa-check-double"></i> Mark all as read
                        </a>
                    <?php endif; ?>
                </div>

                <div class="notifications-list">
                    <?php if(mysqli_num_rows($notifications) > 0): ?>
                        <?php while($notif = mysqli_fetch_assoc($notifications)): ?>
                            <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>">
                                <div class="notification-icon <?php echo $notif['type']; ?>">
                                    <?php if($notif['type'] == 'timeout'): ?>
                                        <i class="fas fa-sign-out-alt"></i>
                                    <?php elseif($notif['type'] == 'active-session'): ?>
                                        <i class="fas fa-play-circle"></i>
                                    <?php elseif($notif['type'] == 'success'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif($notif['type'] == 'warning'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                    </div>
                                    <div class="notification-time">
                                        <i class="far fa-clock"></i>
                                        <?php 
                                        $time_ago = strtotime($notif['created_at']);
                                        $current_time = time();
                                        $time_diff = $current_time - $time_ago;
                                        
                                        if ($time_diff < 60) {
                                            echo "Just now";
                                        } elseif ($time_diff < 3600) {
                                            echo floor($time_diff / 60) . " minutes ago";
                                        } elseif ($time_diff < 86400) {
                                            echo floor($time_diff / 3600) . " hours ago";
                                        } else {
                                            echo date('M d, Y h:i A', $time_ago);
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    <?php if($notif['is_read'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn" title="Mark as read">
                                                <i class="fas fa-envelope-open"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" name="delete_notification" class="delete-btn" title="Delete" onclick="return confirm('Delete this notification?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                            <small>When you receive notifications, they will appear here</small>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>