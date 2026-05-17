<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student') {
    header('Location: ../student_dashboard.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

// Dashboard statistics
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'"))['count'];
$active_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sit_in_sessions WHERE status = 'active'"))['count'];
$total_sitins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sit_in_sessions WHERE status = 'completed'"))['count'];
$total_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations"))['count'];
$avg_duration = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(duration_minutes) as avg FROM sit_in_sessions WHERE duration_minutes > 0"))['avg'];
$total_feedback = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback"))['count'];

// Sit-ins per day (last 7 days)
$daily_sitins = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sit_in_sessions WHERE DATE(session_date) = '$date'"))['count'];
    $daily_sitins[] = ['date' => date('M d', strtotime($date)), 'count' => $count];
}

// Lab usage distribution
$lab_usage = [];
$labs = ['524', '525', '526', '527'];
foreach ($labs as $lab) {
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sit_in_sessions WHERE sit_lab = '$lab'"))['count'];
    $lab_usage[] = ['lab' => $lab, 'count' => $count];
}

// Peak hours (hourly distribution)
$peak_hours = [];
for ($hour = 8; $hour <= 20; $hour++) {
    $hour_str = str_pad($hour, 2, '0', STR_PAD_LEFT);
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sit_in_sessions WHERE HOUR(time_in) = $hour"))['count'];
    $peak_hours[] = ['hour' => $hour_str . ':00', 'count' => $count];
}

// Top 5 students by total hours
$top_students = mysqli_query($conn, "SELECT u.id_number, u.first_name, u.last_name, u.course, 
                                     ROUND(COALESCE(SUM(s.duration_minutes), 0) / 60, 1) as total_hours
                                     FROM users u
                                     LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
                                     WHERE u.role = 'student'
                                     GROUP BY u.id
                                     ORDER BY total_hours DESC
                                     LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CCS Sit-in System</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard-specific styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        .stat-icon.blue { background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #0052cc; }
        .stat-icon.green { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
        .stat-icon.yellow { background: linear-gradient(135deg, #fff3e0, #ffe0b2); color: #ffd600; }
        .stat-icon.purple { background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2; }
        .stat-icon.orange { background: linear-gradient(135deg, #fff3e0, #ffe0b2); color: #ed6c02; }
        .stat-icon.teal { background: linear-gradient(135deg, #e0f2f1, #b2dfdb); color: #00695c; }
        .stat-info h3 { font-size: 1.8rem; font-weight: 700; color: #1a202c; margin: 0; }
        .stat-info p { color: #718096; font-size: 0.8rem; margin: 0; }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }
        .chart-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .chart-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ffd600;
        }
        .chart-card h3 i { color: #ffd600; }
        canvas { max-height: 250px; width: 100%; }
        
        /* Top Students Card */
        .top-students-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .top-students-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ffd600;
        }
        .top-students-card h3 i { color: #ffd600; }
        .student-rank-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .student-rank-item:last-child { border-bottom: none; }
        .rank-number {
            width: 30px;
            height: 30px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #0052cc;
        }
        .rank-number.rank-1 { background: #ffd600; color: #1a202c; }
        .rank-number.rank-2 { background: #c0c0c0; color: #1a202c; }
        .rank-number.rank-3 { background: #cd7f32; color: white; }
        .student-info { flex: 1; }
        .student-name { font-weight: 600; color: #1a202c; }
        .student-course { font-size: 0.7rem; color: #718096; }
        .student-hours { font-weight: 700; color: #0052cc; }
        
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="admin_dashboard.php" class="nav-item active"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="admin_search.php" class="nav-item"><i class="fas fa-search"></i><span>Search</span></a>
                <a href="admin_students.php" class="nav-item "><i class="fas fa-users"></i><span>Students</span></a>
                <a href="admin_sitins.php" class="nav-item"><i class="fas fa-clock"></i><span>Sit-in</span></a>
                <a href="admin_view_sitins.php" class="nav-item"><i class="fas fa-eye"></i><span>View Sit-in Records</span></a>
                <a href="admin_feedback_reports.php" class="nav-item"><i class="fas fa-comment-dots"></i><span>Feedback Reports</span></a>
                <a href="admin_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                <a href="admin_announcements.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
                <a href="admin_add_reward.php" class="nav-item"><i class="fas fa-gift"></i><span>Add Reward</span></a>
                <a href="admin_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                <a href="admin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
                <a href="admin_tasks.php" class="nav-item"><i class="fas fa-tasks"></i><span>Tasks</span></a>
                <a href="../logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_students; ?></h3>
                        <p>Students Registered</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $active_sessions; ?></h3>
                        <p>Currently Sit-in</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_sitins; ?></h3>
                        <p>Total Sit-in Sessions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_reservations; ?></h3>
                        <p>Total Reservations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo round($avg_duration / 60, 1); ?>h</h3>
                        <p>Avg. Sit-in Duration</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-comment-dots"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_feedback; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Sit-ins Per Day Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Sit-ins Per Day (Last 7 Days)</h3>
                    <canvas id="dailyChart"></canvas>
                </div>
                
                <!-- Lab Usage Distribution -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Lab Usage Distribution</h3>
                    <canvas id="labChart"></canvas>
                </div>
                
                <!-- Peak Hours Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Peak Hours (8 AM - 8 PM)</h3>
                    <canvas id="peakHoursChart"></canvas>
                </div>
                
                <!-- Top 5 Students by Hours -->
                <div class="chart-card">
                    <h3><i class="fas fa-trophy"></i> Top 5 Students by Total Hours</h3>
                    <div class="top-students-list">
                        <?php $rank = 1; while($student = mysqli_fetch_assoc($top_students)): ?>
                        <div class="student-rank-item">
                            <div class="rank-number <?php echo $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : '')); ?>">
                                <?php echo $rank; ?>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                <div class="student-course"><?php echo htmlspecialchars($student['course']); ?> • <?php echo htmlspecialchars($student['id_number']); ?></div>
                            </div>
                            <div class="student-hours"><?php echo $student['total_hours']; ?> hrs</div>
                        </div>
                        <?php $rank++; endwhile; ?>
                        <?php if($rank == 1): ?>
                        <div style="text-align: center; padding: 1rem; color: #718096;">No data available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sit-ins Per Day Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_sitins, 'date')); ?>,
                datasets: [{
                    label: 'Number of Sit-ins',
                    data: <?php echo json_encode(array_column($daily_sitins, 'count')); ?>,
                    borderColor: '#0052cc',
                    backgroundColor: 'rgba(0, 82, 204, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffd600',
                    pointBorderColor: '#0052cc',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { backgroundColor: '#1a202c', titleColor: '#ffd600' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Lab Usage Bar Chart
        const labCtx = document.getElementById('labChart').getContext('2d');
        new Chart(labCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($lab_usage, 'lab')); ?>,
                datasets: [{
                    label: 'Sit-in Count',
                    data: <?php echo json_encode(array_column($lab_usage, 'count')); ?>,
                    backgroundColor: ['#0052cc', '#ffd600', '#2e7d32', '#ed6c02'],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Peak Hours Line Chart
        const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
        new Chart(peakCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($peak_hours, 'hour')); ?>,
                datasets: [{
                    label: 'Active Sit-ins',
                    data: <?php echo json_encode(array_column($peak_hours, 'count')); ?>,
                    borderColor: '#ed6c02',
                    backgroundColor: 'rgba(237, 108, 2, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#ffd600',
                    pointBorderColor: '#ed6c02',
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { backgroundColor: '#1a202c' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 45 } }
                }
            }
        });
    </script>
</body>
</html>