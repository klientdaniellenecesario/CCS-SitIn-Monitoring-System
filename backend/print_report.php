<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Get POST or GET parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : (isset($_POST['report_type']) ? $_POST['report_type'] : 'sitins');
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : (isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d', strtotime('-30 days')));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : (isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d'));
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : (isset($_POST['lab']) ? $_POST['lab'] : 'all');

// Build lab condition
$lab_condition = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";
$lab_condition_reservations = ($lab_filter !== 'all') ? "AND r.lab = '$lab_filter'" : "";
$lab_condition_feedback = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";

// Set report title
$report_titles = [
    'sitins' => 'Sit-in Records Report',
    'students' => 'Student List Report',
    'leaderboard' => 'Leaderboard Report',
    'reservations' => 'Reservations Report',
    'feedback' => 'Feedback Summary Report'
];
$report_title = isset($report_titles[$report_type]) ? $report_titles[$report_type] : 'System Report';

// Get data based on report type
$data = [];
$headers = [];

if ($report_type == 'sitins') {
    $query = "SELECT u.id_number, CONCAT(u.first_name, ' ', u.last_name) AS student_name, 
                     u.course, s.sit_lab AS lab, IFNULL(s.pc_number, '--') AS pc_number, 
                     IFNULL(s.purpose, 'Not specified') AS purpose, 
                     DATE(s.session_date) AS date, 
                     TIME(s.time_in) AS time_in, 
                     IFNULL(TIME(s.time_out), '--') AS time_out, 
                     IFNULL(s.duration_minutes, 0) AS duration_minutes
              FROM sit_in_sessions s
              JOIN users u ON s.user_id = u.id
              WHERE DATE(s.session_date) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY s.session_date DESC, s.time_in DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID Number', 'Student Name', 'Course', 'Lab', 'PC', 'Purpose', 'Date', 'Time In', 'Time Out', 'Duration (min)'];
} elseif ($report_type == 'students') {
    $query = "SELECT u.id_number, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                     u.course, u.course_level AS year_level, u.email,
                     COUNT(s.id) AS total_sitins, u.session_count AS remaining_sessions,
                     DATE(u.created_at) AS registered_date
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
              WHERE u.role = 'student'
              GROUP BY u.id
              ORDER BY u.last_name ASC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID Number', 'Full Name', 'Course', 'Year Level', 'Email', 'Total Sit-ins', 'Remaining Sessions', 'Registered'];
} elseif ($report_type == 'leaderboard') {
    $query = "SELECT 
                     @rownum := @rownum + 1 AS rank,
                     CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                     u.course,
                     ROUND(COALESCE(SUM(s.duration_minutes), 0) / 60, 1) AS total_hours,
                     COALESCE(u.total_reward_points, 0) AS total_points,
                     (SELECT COUNT(*) FROM student_tasks st WHERE st.user_id = u.id AND st.status = 'completed') AS tasks_completed
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
              CROSS JOIN (SELECT @rownum := 0) AS r
              WHERE u.role = 'student'
              GROUP BY u.id
              ORDER BY total_hours DESC
              LIMIT 50";
    $result = mysqli_query($conn, $query);
    $headers = ['Rank', 'Student Name', 'Course', 'Total Hours', 'Total Points', 'Tasks Completed'];
} elseif ($report_type == 'reservations') {
    $query = "SELECT r.id, CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                     r.lab, r.pc_number, IFNULL(r.purpose, 'Not specified') AS purpose, 
                     DATE(r.date) AS date, TIME(r.time_in) AS time_in, 
                     DATE(r.created_at) AS reserved_on, UPPER(r.status) AS status
              FROM reservations r
              JOIN users u ON r.user_id = u.id
              WHERE DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition_reservations
              ORDER BY r.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'Student Name', 'Lab', 'PC', 'Purpose', 'Date', 'Time In', 'Reserved On', 'Status'];
} elseif ($report_type == 'feedback') {
    $query = "SELECT CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                     IFNULL(s.purpose, 'General') AS sit_purpose, 
                     IFNULL(f.message, 'No comment') AS comment,
                     DATE(f.created_at) AS submitted_on
              FROM feedback f
              JOIN users u ON f.user_id = u.id
              LEFT JOIN sit_in_sessions s ON f.session_id = s.id
              WHERE DATE(f.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition_feedback
              ORDER BY f.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['Student Name', 'Sit-in Purpose', 'Comment', 'Submitted On'];
}

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Helper function to get status badge styling
function getStatusBadge($status) {
    $status = strtoupper($status);
    switch($status) {
        case 'APPROVED':
            return '<span style="background-color: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 20px; font-size: 9px; font-weight: bold;">✓ APPROVED</span>';
        case 'REJECTED':
            return '<span style="background-color: #fee; color: #c33; padding: 4px 10px; border-radius: 20px; font-size: 9px; font-weight: bold;">✗ REJECTED</span>';
        case 'PENDING':
            return '<span style="background-color: #fff3e0; color: #ed6c02; padding: 4px 10px; border-radius: 20px; font-size: 9px; font-weight: bold;">⏳ PENDING</span>';
        default:
            return htmlspecialchars($status);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?> - CCS Sit-in System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            padding: 20px;
        }
        
        /* Print button - only visible on screen, hides when printing */
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }
        
        /* Report Container */
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
        }
        
        /* Report Header */
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f5c000;
        }
        
        .report-header h2 {
            color: #0041b0;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .report-header h3 {
            color: #333;
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #0041b0;
            margin-top: 5px;
        }
        
        .report-meta {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        /* Table Styles */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        
        .report-table th {
            background-color: #0041b0;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0052cc;
        }
        
        .report-table td {
            padding: 6px;
            border: 1px solid #ddd;
            color: #333;
            vertical-align: top;
        }
        
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .report-table tr:hover {
            background-color: #f0f4ff;
        }
        
        /* Footer */
        .report-footer {
            text-align: center;
            font-size: 9px;
            color: #888;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }
        
        /* Print Styles */
        @media print {
            .print-btn-container, .no-print {
                display: none !important;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
            
            .report-container {
                margin: 0;
                padding: 0;
            }
            
            .report-table th {
                background-color: #0041b0 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-table tr:nth-child(even) {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @page {
            size: landscape;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button onclick="window.print()" class="print-btn">
            <i class="fas fa-download"></i> Save as PDF / Print
        </button>
    </div>
    
    <div class="report-container">
        <div class="report-header">
            <h2>University of Cebu — College of Computer Studies</h2>
            <h3>CCS Sit-in Monitoring System</h3>
            <div class="report-title"><?php echo htmlspecialchars($report_title); ?></div>
        </div>
        
        <div class="report-meta">
            <strong>Generated by:</strong> System Administrator &nbsp;|&nbsp; 
            <strong>Date:</strong> <?php echo date('F d, Y h:i A'); ?><br>
            <strong>Date Range:</strong> <?php echo date('M d, Y', strtotime($from_date)); ?> to <?php echo date('M d, Y', strtotime($to_date)); ?> &nbsp;|&nbsp; 
            <strong>Lab:</strong> <?php echo ($lab_filter === 'all') ? 'All Labs' : 'Lab ' . $lab_filter; ?>
        </div>
        
        <?php if (count($data) > 0): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $key => $value): ?>
                        <?php if ($key == 'status'): ?>
                            <td><?php echo getStatusBadge($value); ?></td>
                        <?php elseif ($key == 'duration_minutes' && is_numeric($value) && $value > 0): ?>
                            <td><?php echo floor($value / 60) . 'h ' . ($value % 60) . 'm'; ?></td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($value ?? 'N/A'); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <p>No data found for the selected filters.</p>
            <p>Please adjust your date range or lab filter and try again.</p>
        </div>
        <?php endif; ?>
        
        <div class="report-footer">
            CCS Sit-in Monitoring System — University of Cebu Main Campus — Confidential
        </div>
    </div>
    
    <div class="print-btn-container no-print" style="margin-top: 20px;">
        <button onclick="window.print()" class="print-btn">
            <i class="fas fa-download"></i> Save as PDF / Print
        </button>
    </div>
    
    <script>
        // Auto-trigger print dialog when page loads (optional - uncomment if desired)
        // window.onload = function() { setTimeout(function() { window.print(); }, 500); };
    </script>
</body>
</html>