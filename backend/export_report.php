<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sitins';

// Get data based on report type
$data = [];
$headers = [];

if ($report_type == 'sitins') {
    $query = "SELECT s.id, u.id_number, u.first_name, u.last_name, u.course, 
                     s.purpose, s.sit_lab, s.pc_number, s.session_date, s.time_in, s.time_out, s.duration_minutes
              FROM sit_in_sessions s
              JOIN users u ON s.user_id = u.id
              WHERE DATE(s.session_date) BETWEEN '$from_date' AND '$to_date'
              ORDER BY s.session_date DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'Name', 'Course', 'Purpose', 'Lab', 'PC', 'Date', 'Time In', 'Time Out', 'Duration (min)'];
} elseif ($report_type == 'students') {
    $query = "SELECT u.id_number, u.first_name, u.last_name, u.course, u.course_level, u.email, u.session_count,
                     COUNT(s.id) as total_sitins
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
              WHERE u.role = 'student'
              GROUP BY u.id
              ORDER BY u.last_name ASC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID Number', 'First Name', 'Last Name', 'Course', 'Year Level', 'Email', 'Remaining Sessions', 'Total Sit-ins'];
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
              ORDER BY total_hours DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID Number', 'First Name', 'Last Name', 'Course', 'Total Hours', 'Reward Points', 'Tasks Completed'];
} elseif ($report_type == 'reservations') {
    $query = "SELECT r.id, u.id_number, u.first_name, u.last_name, r.purpose, r.lab, r.pc_number, r.date, r.time_in, r.status, r.created_at
              FROM reservations r
              JOIN users u ON r.user_id = u.id
              WHERE DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'
              ORDER BY r.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'First Name', 'Last Name', 'Purpose', 'Lab', 'PC', 'Date', 'Time', 'Status', 'Reserved On'];
} elseif ($report_type == 'feedback') {
    $query = "SELECT f.id, u.id_number, u.first_name, u.last_name, s.purpose as sit_purpose, f.message, f.created_at
              FROM feedback f
              JOIN users u ON f.user_id = u.id
              LEFT JOIN sit_in_sessions s ON f.session_id = s.id
              WHERE DATE(f.created_at) BETWEEN '$from_date' AND '$to_date'
              ORDER BY f.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'First Name', 'Last Name', 'Sit-in Purpose', 'Feedback', 'Submitted On'];
}

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Export based on format
if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
} elseif ($format == 'doc') {
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.doc"');
    
    echo '<html><body>';
    echo '<h1>CCS Sit-in Monitoring System Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p>Report Type: ' . ucfirst($report_type) . '</p>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    echo '</body></html>';
} elseif ($format == 'pdf') {
    // For PDF, we'll output HTML that can be printed to PDF
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="report_' . date('Y-m-d') . '.html"');
    
    echo '<!DOCTYPE html>';
    echo '<html><head><title>CCS Sit-in Report</title>';
    echo '<style>
        body { font-family: "Inter", Arial, sans-serif; margin: 40px; }
        h1 { color: #0052cc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #0052cc; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
    </style>';
    echo '</head><body>';
    echo '<h1>CCS Sit-in Monitoring System Report</h1>';
    echo '<p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p><strong>Report Type:</strong> ' . ucfirst($report_type) . '</p>';
    echo '<p><strong>Date Range:</strong> ' . $from_date . ' to ' . $to_date . '</p>';
    echo '<table>';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    echo '<div class="footer">CCS Sit-in Monitoring System © ' . date('Y') . '</div>';
    echo '</body></html>';
}
?>