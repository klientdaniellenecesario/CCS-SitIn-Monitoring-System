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

// Get all tasks with student progress
$tasks_query = "SELECT t.*, st.id as student_task_id, st.status as student_status, 
                st.progress, st.completed_at
                FROM tasks t
                LEFT JOIN student_tasks st ON t.id = st.task_id AND st.user_id = $user_id
                WHERE t.is_active = 1
                ORDER BY 
                    CASE 
                        WHEN st.status = 'completed' THEN 1
                        ELSE 0
                    END,
                    t.id ASC";
$tasks = mysqli_query($conn, $tasks_query);

// Get completed tasks for the table
$completed_tasks = mysqli_query($conn, "SELECT t.*, st.completed_at 
                                        FROM student_tasks st
                                        JOIN tasks t ON st.task_id = t.id
                                        WHERE st.user_id = $user_id AND st.status = 'completed'
                                        ORDER BY st.completed_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .task-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .task-card.completed {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-left: 4px solid #2e7d32;
        }
        
        .task-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .points-badge {
            background: #ffd600;
            color: #1a202c;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .task-body {
            padding: 1.25rem 1.5rem;
        }
        
        .task-description {
            color: #4a5568;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .progress-container {
            margin: 1rem 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar-bg {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, #0052cc, #0066ff);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .progress-bar-fill.completed {
            background: linear-gradient(90deg, #2e7d32, #4caf50);
        }
        
        .task-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .task-status.in-progress {
            background: #e3f2fd;
            color: #0052cc;
        }
        
        .task-status.completed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .task-footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reward-points {
            font-size: 0.85rem;
            color: #ffd600;
            font-weight: 700;
        }
        
        .check-icon {
            color: #2e7d32;
            font-size: 1.2rem;
        }
        
        .table-header {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #ffd600;
        }
        
        .completed-table th, .completed-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .completed-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #4a5568;
        }
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
                    <a href="student_history.php" class="nav-item"><i class="fas fa-history"></i><span>History</span></a>
                    <a href="student_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                    <a href="student_rewards.php" class="nav-item"><i class="fas fa-gift"></i><span>Rewards</span></a>
                    <a href="student_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                    <a href="logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i><span>Log out</span>
                    </a>
                </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-title"><h1>My Challenges</h1></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <!-- Tasks Grid -->
            <div class="tasks-grid">
                <?php while($task = mysqli_fetch_assoc($tasks)): 
                    $progress = $task['progress'] ?? 0;
                    $target = $task['target_value'];
                    $percentage = $target > 0 ? min(($progress / $target) * 100, 100) : 0;
                    $is_completed = ($task['student_status'] ?? 'in_progress') == 'completed';
                ?>
                <div class="task-card <?php echo $is_completed ? 'completed' : ''; ?>">
                    <div class="task-header">
                        <span class="task-title"><?php echo htmlspecialchars($task['title']); ?></span>
                        <span class="points-badge"><i class="fas fa-star"></i> <?php echo $task['points_reward']; ?> pts</span>
                    </div>
                    <div class="task-body">
                        <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                        <?php if ($task['task_type'] != 'manual'): ?>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Progress</span>
                                <span><?php echo $progress; ?> / <?php echo $target; ?></span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill <?php echo $is_completed ? 'completed' : ''; ?>" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="task-status <?php echo $is_completed ? 'completed' : 'in-progress'; ?>">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check-circle"></i> Completed
                            <?php else: ?>
                                <i class="fas fa-hourglass-half"></i> In Progress
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="task-footer">
                        <span class="reward-points"><i class="fas fa-gift"></i> Reward: <?php echo $task['points_reward']; ?> points</span>
                        <?php if ($is_completed): ?>
                            <i class="fas fa-check-circle check-icon"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Completed Tasks Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-check-circle"></i> Completed Tasks</h2>
                </div>
                <div class="table-responsive">
                    <table class="completed-table">
                        <thead>
                            <tr>
                                <th>Task Title</th>
                                <th>Points Earned</th>
                                <th>Completed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($completed_tasks) > 0): ?>
                                <?php while($task = mysqli_fetch_assoc($completed_tasks)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo $task['points_reward']; ?> points</td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($task['completed_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No completed tasks yet. Complete challenges to earn rewards!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>