<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sitins';
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : 'all';

// Get preview data based on report type
$preview_data = [];
$preview_columns = [];

if ($report_type == 'sitins') {
    $lab_condition = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";
    $query = "SELECT s.id, u.id_number, u.first_name, u.last_name, u.course, 
                     s.purpose, s.sit_lab, s.pc_number, s.session_date, s.time_in, s.time_out, s.duration_minutes
              FROM sit_in_sessions s
              JOIN users u ON s.user_id = u.id
              WHERE DATE(s.session_date) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY s.session_date DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    $preview_columns = ['ID', 'ID Number', 'Student', 'Course', 'Purpose', 'Lab', 'PC', 'Date', 'Time In', 'Time Out', 'Duration'];
} elseif ($report_type == 'students') {
    $query = "SELECT u.id_number, u.first_name, u.last_name, u.course, u.course_level, u.email, u.session_count,
                     COUNT(s.id) as total_sitins
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
              WHERE u.role = 'student'
              GROUP BY u.id
              ORDER BY u.last_name ASC";
    $result = mysqli_query($conn, $query);
    $preview_columns = ['ID Number', 'Name', 'Course', 'Year', 'Email', 'Remaining Sessions', 'Total Sit-ins'];
} elseif ($report_type == 'leaderboard') {
    $query = "SELECT u.id_number, u.first_name, u.last_name, u.course,
                     ROUND(COALESCE(SUM(s.duration_minutes), 0) / 60, 1) as total_hours,
                     u.total_reward_points as points,
                     COUNT(st.id) as tasks_completed
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
              LEFT JOIN student_tasks st ON u.id = st.user_id AND st.status = 'completed'
              WHERE u.role = 'student'
              GROUP BY u.id
              ORDER BY total_hours DESC
              LIMIT 50";
    $result = mysqli_query($conn, $query);
    $preview_columns = ['ID Number', 'Name', 'Course', 'Total Hours', 'Reward Points', 'Tasks Completed'];
} elseif ($report_type == 'reservations') {
    $lab_condition = ($lab_filter !== 'all') ? "AND r.lab = '$lab_filter'" : "";
    $query = "SELECT r.id, u.id_number, u.first_name, u.last_name, r.purpose, r.lab, r.pc_number, r.date, r.time_in, r.status, r.created_at
              FROM reservations r
              JOIN users u ON r.user_id = u.id
              WHERE DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY r.created_at DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    $preview_columns = ['ID', 'ID Number', 'Student', 'Purpose', 'Lab', 'PC', 'Date', 'Time', 'Status', 'Reserved On'];
} elseif ($report_type == 'feedback') {
    $lab_condition = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";
    $query = "SELECT f.id, u.id_number, u.first_name, u.last_name, s.purpose as sit_purpose, f.message, f.created_at
              FROM feedback f
              JOIN users u ON f.user_id = u.id
              LEFT JOIN sit_in_sessions s ON f.session_id = s.id
              WHERE DATE(f.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY f.created_at DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    $preview_columns = ['ID', 'ID Number', 'Student', 'Sit-in Purpose', 'Feedback', 'Submitted On'];
}

while ($row = mysqli_fetch_assoc($result)) {
    $preview_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reports-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #ffd600;
            display: inline-block;
        }
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        .filter-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.25rem;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #ffd600;
        }
        .export-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .btn-export {
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
        }
        .btn-csv {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
        }
        .btn-doc {
            background: linear-gradient(135deg, #2e7d32, #388e3c);
            color: white;
        }
        .btn-pdf {
            background: linear-gradient(135deg, #c62828, #e53935);
            color: white;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .preview-table {
            margin-top: 1.5rem;
            overflow-x: auto;
        }
        .preview-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .preview-table th {
            background: #f8fafc;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        .preview-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }
        .feedback-preview {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
        }
        .lab-info {
            background: #f0f4ff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #0052cc;
            display: inline-block;
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
                <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="admin_search.php" class="nav-item"><i class="fas fa-search"></i><span>Search</span></a>
                <a href="admin_students.php" class="nav-item"><i class="fas fa-users"></i><span>Students</span></a>
                <a href="admin_sitins.php" class="nav-item"><i class="fas fa-clock"></i><span>Sit-in</span></a>
                <a href="admin_view_sitins.php" class="nav-item"><i class="fas fa-eye"></i><span>View Sit-in Records</span></a>
                <a href="admin_feedback_reports.php" class="nav-item"><i class="fas fa-comment-dots"></i><span>Feedback Reports</span></a>
                <a href="admin_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                <a href="admin_announcements.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
                <a href="admin_add_reward.php" class="nav-item"><i class="fas fa-gift"></i><span>Add Reward</span></a>
                <a href="admin_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                <a href="admin_reports.php" class="nav-item active"><i class="fas fa-chart-line"></i><span>Reports</span></a>
                <a href="admin_tasks.php" class="nav-item"><i class="fas fa-tasks"></i><span>Tasks</span></a>
                <a href="../logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Generate Reports</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <div class="reports-container">
                <h3 class="section-title"><i class="fas fa-chart-line"></i> Report Options</h3>
                
                <form method="GET" action="" id="reportForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>From Date</label>
                            <input type="date" name="from_date" id="from_date" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="filter-group">
                            <label>To Date</label>
                            <input type="date" name="to_date" id="to_date" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="filter-group">
                            <label>Report Type</label>
                            <select name="report_type" id="report_type">
                                <option value="sitins" <?php echo $report_type == 'sitins' ? 'selected' : ''; ?>>Sit-in Records</option>
                                <option value="students" <?php echo $report_type == 'students' ? 'selected' : ''; ?>>Student List</option>
                                <option value="leaderboard" <?php echo $report_type == 'leaderboard' ? 'selected' : ''; ?>>Leaderboard</option>
                                <option value="reservations" <?php echo $report_type == 'reservations' ? 'selected' : ''; ?>>Reservations</option>
                                <option value="feedback" <?php echo $report_type == 'feedback' ? 'selected' : ''; ?>>Feedback Summary</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Lab</label>
                            <select name="lab" id="lab_filter">
                                <option value="all" <?php echo $lab_filter == 'all' ? 'selected' : ''; ?>>All Labs</option>
                                <option value="524" <?php echo $lab_filter == '524' ? 'selected' : ''; ?>>Lab 524</option>
                                <option value="525" <?php echo $lab_filter == '525' ? 'selected' : ''; ?>>Lab 525</option>
                                <option value="526" <?php echo $lab_filter == '526' ? 'selected' : ''; ?>>Lab 526</option>
                                <option value="527" <?php echo $lab_filter == '527' ? 'selected' : ''; ?>>Lab 527</option>
                            </select>
                        </div>
                        <div class="filter-group" style="flex: 0.5;">
                            <button type="submit" style="background: linear-gradient(135deg, #0052cc, #0066ff); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%;">
                                <i class="fas fa-sync-alt"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if ($lab_filter !== 'all'): ?>
                <div class="lab-info">
                    <i class="fas fa-flask"></i> Currently filtering by: <strong>Lab <?php echo htmlspecialchars($lab_filter); ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="export-buttons">
                    <button class="btn-export btn-csv" onclick="exportReport('csv')">
                        <i class="fas fa-file-csv"></i> Export as CSV
                    </button>
                    <button class="btn-export btn-doc" onclick="exportReport('doc')">
                        <i class="fab fa-microsoft-word"></i> Export as DOC
                    </button>
                    
                    <!-- PDF Export Form - Uses POST method for auto-download -->
                    <form method="POST" action="../backend/export_report.php" style="display: inline;" id="pdfForm">
                        <input type="hidden" name="format" value="pdf">
                        <input type="hidden" name="report_type" id="pdf_report_type" value="<?php echo $report_type; ?>">
                        <input type="hidden" name="from_date" id="pdf_from_date" value="<?php echo $from_date; ?>">
                        <input type="hidden" name="to_date" id="pdf_to_date" value="<?php echo $to_date; ?>">
                        <input type="hidden" name="lab" id="pdf_lab" value="<?php echo $lab_filter; ?>">
                        <button type="submit" class="btn-export btn-pdf">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="reports-container">
                <h3 class="section-title"><i class="fas fa-eye"></i> Preview</h3>
                <div class="preview-table">
                    <?php if (count($preview_data) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($preview_columns as $col): ?>
                                    <th><?php echo $col; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $key => $value): ?>
                                        <td class="<?php echo $key == 'message' ? 'feedback-preview' : ''; ?>">
                                            <?php echo htmlspecialchars($value ?? 'N/A'); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #718096;">
                        <i class="fas fa-info-circle"></i> No data found for the selected filters.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function exportReport(format) {
            const from_date = document.getElementById('from_date').value;
            const to_date = document.getElementById('to_date').value;
            const report_type = document.getElementById('report_type').value;
            const lab = document.getElementById('lab_filter').value;
            
            window.location.href = `../backend/export_report.php?format=${format}&from_date=${from_date}&to_date=${to_date}&report_type=${report_type}&lab=${lab}`;
        }
        
        // Sync PDF form hidden fields with current filter values
        function syncPdfFilters() {
            const reportType = document.getElementById('report_type');
            const fromDate = document.getElementById('from_date');
            const toDate = document.getElementById('to_date');
            const labFilter = document.getElementById('lab_filter');
            
            if (reportType) document.getElementById('pdf_report_type').value = reportType.value;
            if (fromDate) document.getElementById('pdf_from_date').value = fromDate.value;
            if (toDate) document.getElementById('pdf_to_date').value = toDate.value;
            if (labFilter) document.getElementById('pdf_lab').value = labFilter.value;
        }
        
        // Add event listeners to filter inputs
        const reportTypeSelect = document.getElementById('report_type');
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const labFilterSelect = document.getElementById('lab_filter');
        
        if (reportTypeSelect) reportTypeSelect.addEventListener('change', syncPdfFilters);
        if (fromDateInput) fromDateInput.addEventListener('change', syncPdfFilters);
        if (toDateInput) toDateInput.addEventListener('change', syncPdfFilters);
        if (labFilterSelect) labFilterSelect.addEventListener('change', syncPdfFilters);
        
        // Sync on page load
        syncPdfFilters();
    </script>
</body>
</html>