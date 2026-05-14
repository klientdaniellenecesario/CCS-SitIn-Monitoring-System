<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$error = '';
$success = '';

// Handle add task
if (isset($_POST['add_task'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $points_reward = intval($_POST['points_reward']);
    $task_type = mysqli_real_escape_string($conn, $_POST['task_type']);
    $target_value = intval($_POST['target_value']);
    
    $insert = "INSERT INTO tasks (title, description, points_reward, task_type, target_value) 
               VALUES ('$title', '$description', '$points_reward', '$task_type', '$target_value')";
    if (mysqli_query($conn, $insert)) {
        $success = "Task added successfully!";
    } else {
        $error = "Failed to add task.";
    }
}

// Handle toggle task status
if (isset($_POST['toggle_task'])) {
    $task_id = intval($_POST['task_id']);
    $is_active = intval($_POST['is_active']);
    $new_status = $is_active ? 0 : 1;
    mysqli_query($conn, "UPDATE tasks SET is_active = $new_status WHERE id = $task_id");
    $success = "Task status updated!";
    header('Location: admin_tasks.php');
    exit();
}

// Handle delete task
if (isset($_POST['delete_task'])) {
    $task_id = intval($_POST['task_id']);
    mysqli_query($conn, "DELETE FROM tasks WHERE id = $task_id");
    $success = "Task deleted!";
    header('Location: admin_tasks.php');
    exit();
}

// Get all tasks
$tasks = mysqli_query($conn, "SELECT t.*, 
                              (SELECT COUNT(*) FROM student_tasks WHERE task_id = t.id AND status = 'completed') as completed_count
                              FROM tasks t ORDER BY t.is_active DESC, t.id ASC");

// Get task completion stats
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student' OR role IS NULL"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Management - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .task-form-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .task-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .task-card.inactive {
            opacity: 0.6;
            background: #f8fafc;
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }
        
        .task-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1a202c;
        }
        
        .task-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .task-badge.active { background: #e8f5e9; color: #2e7d32; }
        .task-badge.inactive { background: #fee; color: #c33; }
        
        .task-stats {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: #718096;
        }
        
        .progress-bar-bg {
            background: #e2e8f0;
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, #0052cc, #0066ff);
            height: 100%;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
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
                <a href="admin_sit_in_reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sit_in_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i><span>Sit-in Reports</span>
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

        <main class="admin-main">
            <header class="admin-header">
                <h1>Tasks Management</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Task Form -->
            <div class="task-form-card">
                <h3><i class="fas fa-plus-circle"></i> Add New Task</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Task Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="points_reward">Points Reward</label>
                            <input type="number" name="points_reward" id="points_reward" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="task_type">Task Type</label>
                            <select name="task_type" id="task_type" class="form-control" required>
                                <option value="sit_in_count">Sit-in Count</option>
                                <option value="hours_milestone">Hours Milestone</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="target_value">Target Value</label>
                            <input type="number" name="target_value" id="target_value" class="form-control" min="1" placeholder="e.g., 5 for 5 sit-ins, 60 for 1 hour">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" name="add_task" class="btn-submit">Add Task</button>
                    </div>
                </form>
            </div>

            <!-- Tasks List -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> All Tasks</h2>
                </div>
                <div class="table-responsive">
                    <?php while($task = mysqli_fetch_assoc($tasks)): 
                        $completion_percentage = $total_students > 0 ? round(($task['completed_count'] / $total_students) * 100) : 0;
                    ?>
                    <div class="task-card <?php echo $task['is_active'] ? '' : 'inactive'; ?>">
                        <div class="task-header">
                            <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div>
                                <span class="task-badge <?php echo $task['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $task['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <span class="task-badge" style="background: #ffd600; color: #1a202c;"><?php echo $task['points_reward']; ?> pts</span>
                            </div>
                        </div>
                        <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                        <div class="task-stats">
                            <span><i class="fas fa-users"></i> <?php echo $task['completed_count']; ?>/<?php echo $total_students; ?> students completed</span>
                            <span><i class="fas fa-bullseye"></i> Target: <?php echo $task['target_value']; ?></span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                        </div>
                        <div class="task-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $task['is_active']; ?>">
                                <button type="submit" name="toggle_task" class="btn-edit"><?php echo $task['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="btn-delete" onclick="return confirm('Delete this task?')">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>