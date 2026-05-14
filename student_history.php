<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $session_id = intval($_POST['session_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    
    $check = mysqli_query($conn, "SELECT id FROM feedback WHERE session_id = $session_id AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        $update = "UPDATE feedback SET message = '$feedback', updated_at = NOW() WHERE session_id = $session_id AND user_id = $user_id";
        mysqli_query($conn, $update);
        $success = "Feedback updated successfully!";
    } else {
        $insert = "INSERT INTO feedback (user_id, session_id, message, created_at) 
                   VALUES ('$user_id', '$session_id', '$feedback', NOW())";
        mysqli_query($conn, $insert);
        $success = "Feedback submitted successfully!";
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT s.*, f.message as feedback, f.id as feedback_id,
                 COALESCE(NULLIF(s.purpose, ''), r.purpose, 'Not specified') AS display_purpose
          FROM sit_in_sessions s
          LEFT JOIN feedback f ON s.id = f.session_id AND f.user_id = $user_id
          LEFT JOIN reservations r ON r.user_id = s.user_id
                                   AND r.lab = s.sit_lab
                                   AND r.pc_number = s.pc_number
                                   AND r.status = 'approved'
          WHERE s.user_id = $user_id";
if ($search) {
    $query .= " AND (s.purpose LIKE '%$search%' OR s.sit_lab LIKE '%$search%' OR s.status LIKE '%$search%')";
}
$total_query = "SELECT COUNT(*) as total FROM ($query) as temp";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

$query .= " ORDER BY s.time_in DESC LIMIT $offset, $limit";
$history = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .live-dot {
            display: inline-block;
            width: 8px; height: 8px; border-radius: 50%;
            background: #22c55e;
            margin-right: 6px;
            animation: pulse-dot 1.5s ease-in-out infinite;
            vertical-align: middle;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }
        .live-timer { color: #16a34a; font-weight: 600; font-size: 0.85rem; }
        
        .btn-feedback {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #0052cc;
            border: 1.5px solid #90caf9;
            padding: 0.45rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }
        .btn-feedback:hover {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border-color: #0052cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.25);
        }
        .feedback-disabled {
            color: #a0aec0;
            font-size: 0.75rem;
            font-style: italic;
        }
        .btn-feedback-submit {
            flex: 1;
            padding: 0.75rem;
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        .btn-feedback-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }
        .btn-close {
            flex: 1;
            padding: 0.75rem;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-close:hover { background: #cbd5e0; }
    </style>
</head>
<body>
    <div class="container">
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
                <a href="student_history.php" class="nav-item active"><i class="fas fa-history"></i><span>History</span></a>
                <a href="student_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                <a href="student_rewards.php" class="nav-item"><i class="fas fa-gift"></i><span>Rewards</span></a>
                <a href="student_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                <a href="logout.php" class="nav-item logout" onclick="return confirmLogout();">
                    <i class="fas fa-sign-out-alt"></i><span>Log out</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-title"><h1>History</h1></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <?php if (isset($success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <div class="table-controls">
                <div class="entries-select"><label>10 entries per page</label></div>
                <div class="search-box-table">
                    <form method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                        <?php if($search): ?>
                            <a href="student_history.php" class="clear-btn">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="data-table">
                <div class="table-header"><h2><i class="fas fa-clock"></i> Sit-in History</h2></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Purpose</th>
                                <th>Laboratory</th>
                                <th>Login</th>
                                <th>Logout</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($history) > 0): ?>
                                <?php while($sit = mysqli_fetch_assoc($history)): 
                                    $login_display = !empty($sit['time_in'])
                                        ? date('h:i A', strtotime($sit['time_in']))
                                        : (!empty($sit['session_time']) && $sit['session_time'] !== '00:00:00'
                                            ? date('h:i A', strtotime($sit['session_time']))
                                            : '--');
                                    $logout_display = !empty($sit['time_out'])
                                        ? date('h:i A', strtotime($sit['time_out']))
                                        : '--';
                                    $duration_display = '';
                                    if ($sit['duration_minutes'] > 0) {
                                        $h = floor($sit['duration_minutes'] / 60);
                                        $m = $sit['duration_minutes'] % 60;
                                        $duration_display = ($h > 0 ? $h . 'h ' : '') . $m . 'm';
                                    } elseif ($sit['status'] === 'active' && !empty($sit['time_in'])) {
                                        $duration_display = '<span class="live-timer" data-timein="' . (strtotime($sit['time_in']) * 1000) . '">
                                            <span class="live-dot"></span><span class="timer-text">--</span></span>';
                                    } else {
                                        $duration_display = '--';
                                    }
                                    $display_date = ($sit['session_date'] === '0000-00-00' || empty($sit['session_date']))
                                        ? date('M d, Y', strtotime($sit['time_in']))
                                        : date('M d, Y', strtotime($sit['session_date']));
                                ?>
                                <tr>
                                    <td><?php echo $sit['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sit['display_purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['sit_lab']); ?></td>
                                    <td><?php echo $login_display; ?></td>
                                    <td><?php echo $logout_display; ?></td>
                                    <td class="duration-cell"><?php echo $duration_display; ?></td>
                                    <td><?php echo $display_date; ?></td>
                                    <td><span class="status-badge <?php echo $sit['status']; ?>"><?php echo ucfirst($sit['status']); ?></span></td>
                                    <td class="feedback-cell">
                                        <?php if($sit['status'] == 'completed'): ?>
                                            <button class="btn-feedback" onclick="openFeedbackModal(<?php echo $sit['id']; ?>, '<?php echo addslashes($sit['feedback']); ?>')">
                                                <i class="fas fa-comment-dots"></i> 
                                                <?php echo empty($sit['feedback']) ? 'Add Feedback' : 'Edit Feedback'; ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="feedback-disabled">Available after completion</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" style="text-align: center;">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <div class="table-footer"><p>Showing <?php echo mysqli_num_rows($history); ?> of <?php echo $total_rows; ?> entries</p></div>
            </div>
        </main>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-comment-dots"></i> Leave Feedback</h2>
                <span class="close" onclick="closeFeedbackModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="session_id" id="feedback_session_id">
                <div class="form-group">
                    <label for="feedback">Your Feedback:</label>
                    <textarea name="feedback" id="feedback" class="form-control" rows="5" placeholder="Share your experience about this sit-in session..." required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-close" onclick="closeFeedbackModal()">Cancel</button>
                    <button type="submit" name="submit_feedback" class="btn-feedback-submit">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmLogout() { return confirm('Are you sure you want to logout?'); }
        
        function openFeedbackModal(sessionId, existingFeedback) {
            document.getElementById('feedback_session_id').value = sessionId;
            document.getElementById('feedback').value = existingFeedback || '';
            document.getElementById('feedbackModal').style.display = 'flex';
        }
        
        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
            document.getElementById('feedback').value = '';
        }
        
        function updateTimers() {
            document.querySelectorAll('.live-timer').forEach(function(el) {
                const timeIn = parseInt(el.dataset.timein);
                if (!timeIn || isNaN(timeIn)) return;
                const now = Date.now();
                const diffMs = now - timeIn;
                if (diffMs < 0) { el.querySelector('.timer-text').textContent = '0h 0m 0s'; return; }
                const totalSeconds = Math.floor(diffMs / 1000);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                el.querySelector('.timer-text').textContent = h + 'h ' + m + 'm ' + s + 's';
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();
        
        window.onclick = function(event) {
            var modal = document.getElementById('feedbackModal');
            if (event.target == modal) closeFeedbackModal();
        }
    </script>
</body>
</html>