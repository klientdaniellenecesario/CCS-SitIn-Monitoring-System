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

// Handle delete feedback
if (isset($_POST['delete_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    mysqli_query($conn, "DELETE FROM feedback WHERE id = $feedback_id");
    $success = "Feedback deleted successfully!";
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get feedback with student and session details
$query = "SELECT f.*, u.first_name, u.last_name, u.id_number, u.course, 
          s.purpose, s.sit_lab, s.session_date, s.session_time
          FROM feedback f 
          JOIN users u ON f.user_id = u.id 
          JOIN sit_in_sessions s ON f.session_id = s.id
          ORDER BY f.created_at DESC";

$total_query = "SELECT COUNT(*) as total FROM ($query) as temp";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

$query .= " LIMIT $offset, $limit";
$feedbacks = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h1>Feedback Reports</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <?php if (isset($success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Feedback Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-comment-dots"></i> Student Feedback</h2>
                </div>
                <div class="table-responsive">
                     <table>
                        <thead>
                             <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Date</th>
                                <th>Feedback</th>
                                <th>Submitted On</th>
                                <th>Actions</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($feedbacks) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($feedbacks)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sit_lab']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['session_date'])); ?></td>
                                    <td class="feedback-message">
                                        <div class="feedback-content">
                                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                    <td class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="feedback_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_feedback" class="btn-delete" onclick="return confirm('Delete this feedback?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center;">No feedback submitted yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <div class="table-footer">
                    <p>Showing <?php echo mysqli_num_rows($feedbacks); ?> of <?php echo $total_rows; ?> entries</p>
                </div>
            </div>
        </main>
    </div>

    <style>
        .feedback-message {
            max-width: 300px;
        }
        
        .feedback-content {
            background: #f8fafc;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            line-height: 1.4;
            border-left: 3px solid #ffd600;
            word-wrap: break-word;
        }
    </style>
</body>
</html>