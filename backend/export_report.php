<?php
ob_start(); // Prevent accidental output before headers
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$format = isset($_GET['format']) ? $_GET['format'] : (isset($_POST['format']) ? $_POST['format'] : 'csv');
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : (isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d', strtotime('-30 days')));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : (isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d'));
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : (isset($_POST['report_type']) ? $_POST['report_type'] : 'sitins');
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : (isset($_POST['lab']) ? $_POST['lab'] : 'all');

// Build lab condition
$lab_condition = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";
$lab_condition_reservations = ($lab_filter !== 'all') ? "AND r.lab = '$lab_filter'" : "";
$lab_condition_feedback = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";

// Get data based on report type
$data = [];
$headers = [];

if ($report_type == 'sitins') {
    $query = "SELECT s.id, u.id_number, u.first_name, u.last_name, u.course, 
                     s.purpose, s.sit_lab, s.pc_number, s.session_date, s.time_in, s.time_out, s.duration_minutes
              FROM sit_in_sessions s
              JOIN users u ON s.user_id = u.id
              WHERE DATE(s.session_date) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY s.session_date DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'First Name', 'Last Name', 'Course', 'Purpose', 'Lab', 'PC', 'Date', 'Time In', 'Time Out', 'Duration (min)'];
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
    $lab_condition = ($lab_filter !== 'all') ? "AND r.lab = '$lab_filter'" : "";
    $query = "SELECT r.id, u.id_number, u.first_name, u.last_name, r.purpose, r.lab, r.pc_number, r.date, r.time_in, r.status, r.created_at
              FROM reservations r
              JOIN users u ON r.user_id = u.id
              WHERE DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY r.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'First Name', 'Last Name', 'Purpose', 'Lab', 'PC', 'Date', 'Time', 'Status', 'Reserved On'];
} elseif ($report_type == 'feedback') {
    $lab_condition = ($lab_filter !== 'all') ? "AND s.sit_lab = '$lab_filter'" : "";
    $query = "SELECT f.id, u.id_number, u.first_name, u.last_name, s.purpose as sit_purpose, f.message, f.created_at
              FROM feedback f
              JOIN users u ON f.user_id = u.id
              LEFT JOIN sit_in_sessions s ON f.session_id = s.id
              WHERE DATE(f.created_at) BETWEEN '$from_date' AND '$to_date'
              $lab_condition
              ORDER BY f.created_at DESC";
    $result = mysqli_query($conn, $query);
    $headers = ['ID', 'ID Number', 'First Name', 'Last Name', 'Sit-in Purpose', 'Feedback', 'Submitted On'];
}

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// ============================================
// PDF EXPORT - Using FPDF (AUTO-DOWNLOAD)
// ============================================
if ($format == 'pdf') {
    // Check if FPDF library exists
    if (!file_exists('../libraries/fpdf/fpdf.php')) {
        die('Error: FPDF library not found. Please download fpdf.php from http://www.fpdf.org and place it in libraries/fpdf/');
    }
    
    require_once '../libraries/fpdf/fpdf.php';
    
    $lab_label = ($lab_filter !== 'all') ? "Lab $lab_filter" : "All Labs";
    
    // Set report title
    $report_titles = [
        'sitins' => 'Sit-in Records Report',
        'students' => 'Student List Report',
        'leaderboard' => 'Leaderboard Report',
        'reservations' => 'Reservations Report',
        'feedback' => 'Feedback Summary Report'
    ];
    $report_title = isset($report_titles[$report_type]) ? $report_titles[$report_type] : 'System Report';
    
    // Create PDF class
    class ReportPDF extends FPDF {
        public $reportTitle = '';
        public $labLabel = '';
        public $dateFrom = '';
        public $dateTo = '';
        
        function Header() {
            // Blue header bar
            $this->SetFillColor(0, 65, 176);
            $this->Rect(0, 0, 297, 28, 'F');
            
            // Title text
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(10, 7);
            $this->Cell(0, 8, 'College of Computer Studies - UC Main Campus', 0, 1, 'L');
            
            $this->SetFont('Arial', '', 9);
            $this->SetXY(10, 16);
            $this->Cell(0, 6, $this->reportTitle . '  |  ' . $this->labLabel . '  |  ' . date('M d, Y', strtotime($this->dateFrom)) . ' to ' . date('M d, Y', strtotime($this->dateTo)), 0, 1, 'L');
            
            // Yellow accent line
            $this->SetFillColor(245, 192, 0);
            $this->Rect(0, 28, 297, 2, 'F');
            
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(0, 10, 'Generated on ' . date('F d, Y h:i A') . '  |  Page ' . $this->PageNo(), 0, 0, 'C');
        }
        
        function TableHeader($cols, $widths) {
            $this->SetFillColor(0, 65, 176);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 9);
            foreach ($cols as $i => $col) {
                $this->Cell($widths[$i], 10, $col, 1, 0, 'C', true);
            }
            $this->Ln();
        }
        
        function TableRow($data, $widths, $fill = false) {
            $this->SetFillColor(240, 245, 255);
            $this->SetTextColor(30, 30, 30);
            $this->SetFont('Arial', '', 8);
            foreach ($data as $i => $val) {
                $this->Cell($widths[$i], 8, $val, 1, 0, 'L', $fill);
            }
            $this->Ln();
        }
    }
    
    $pdf = new ReportPDF('L', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->reportTitle = $report_title;
    $pdf->labLabel = $lab_label;
    $pdf->dateFrom = $from_date;
    $pdf->dateTo = $to_date;
    $pdf->AddPage();
    
    // Build table based on report type
    if ($report_type == 'sitins') {
        $cols = ['ID', 'ID Number', 'First Name', 'Last Name', 'Course', 'Purpose', 'Lab', 'PC', 'Date', 'Time In', 'Time Out', 'Duration'];
        $widths = [10, 22, 30, 30, 18, 35, 12, 12, 22, 16, 16, 16];
        
        $pdf->TableHeader($cols, $widths);
        $fill = false;
        foreach ($data as $row) {
            $duration = ($row['duration_minutes'] > 0) ? floor($row['duration_minutes'] / 60) . 'h ' . ($row['duration_minutes'] % 60) . 'm' : '--';
            $pdf->TableRow([
                $row['id'],
                $row['id_number'],
                $row['first_name'],
                $row['last_name'],
                $row['course'],
                substr($row['purpose'] ?? 'Not specified', 0, 25),
                $row['sit_lab'],
                $row['pc_number'] ?? '--',
                date('M d, Y', strtotime($row['session_date'])),
                date('h:i A', strtotime($row['time_in'])),
                $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--',
                $duration
            ], $widths, $fill);
            $fill = !$fill;
        }
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 192, 0);
        $pdf->SetTextColor(0, 41, 102);
        $pdf->Cell(array_sum($widths), 8, "Total Records: " . count($data), 1, 1, 'R', true);
        
    } elseif ($report_type == 'students') {
        $cols = ['ID Number', 'First Name', 'Last Name', 'Course', 'Year', 'Email', 'Sessions Left', 'Total Sit-ins'];
        $widths = [22, 30, 30, 22, 12, 45, 20, 20];
        
        $pdf->TableHeader($cols, $widths);
        $fill = false;
        foreach ($data as $row) {
            $pdf->TableRow([
                $row['id_number'],
                $row['first_name'],
                $row['last_name'],
                $row['course'],
                $row['course_level'],
                $row['email'],
                $row['session_count'],
                $row['total_sitins']
            ], $widths, $fill);
            $fill = !$fill;
        }
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 192, 0);
        $pdf->SetTextColor(0, 41, 102);
        $pdf->Cell(array_sum($widths), 8, "Total Students: " . count($data), 1, 1, 'R', true);
        
    } elseif ($report_type == 'leaderboard') {
        $cols = ['ID Number', 'First Name', 'Last Name', 'Course', 'Total Hours', 'Points', 'Tasks'];
        $widths = [22, 30, 30, 25, 22, 18, 18];
        
        $pdf->TableHeader($cols, $widths);
        $fill = false;
        foreach ($data as $row) {
            $pdf->TableRow([
                $row['id_number'],
                $row['first_name'],
                $row['last_name'],
                $row['course'],
                $row['total_hours'] . ' hrs',
                $row['points'],
                $row['tasks_completed']
            ], $widths, $fill);
            $fill = !$fill;
        }
        
    } elseif ($report_type == 'reservations') {
        $cols = ['ID', 'ID Number', 'First Name', 'Last Name', 'Purpose', 'Lab', 'PC', 'Date', 'Time', 'Status', 'Reserved On'];
        $widths = [8, 18, 22, 22, 28, 10, 10, 20, 14, 14, 22];
        
        $pdf->TableHeader($cols, $widths);
        $fill = false;
        foreach ($data as $row) {
            $status = ucfirst($row['status']);
            $pdf->TableRow([
                $row['id'],
                $row['id_number'],
                $row['first_name'],
                $row['last_name'],
                substr($row['purpose'] ?? 'Not specified', 0, 20),
                $row['lab'],
                $row['pc_number'] ?? '--',
                date('M d, Y', strtotime($row['date'])),
                date('h:i A', strtotime($row['time_in'])),
                $status,
                date('M d, Y', strtotime($row['created_at']))
            ], $widths, $fill);
            $fill = !$fill;
        }
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 192, 0);
        $pdf->SetTextColor(0, 41, 102);
        $pdf->Cell(array_sum($widths), 8, "Total Records: " . count($data), 1, 1, 'R', true);
        
    } elseif ($report_type == 'feedback') {
        $cols = ['ID', 'ID Number', 'First Name', 'Last Name', 'Sit-in Purpose', 'Feedback', 'Submitted On'];
        $widths = [8, 18, 22, 22, 35, 55, 25];
        
        $pdf->TableHeader($cols, $widths);
        $fill = false;
        foreach ($data as $row) {
            $pdf->TableRow([
                $row['id'],
                $row['id_number'],
                $row['first_name'],
                $row['last_name'],
                substr($row['sit_purpose'] ?? 'General', 0, 25),
                substr($row['message'] ?? 'No comment', 0, 45),
                date('M d, Y', strtotime($row['created_at']))
            ], $widths, $fill);
            $fill = !$fill;
        }
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 192, 0);
        $pdf->SetTextColor(0, 41, 102);
        $pdf->Cell(array_sum($widths), 8, "Total Feedback: " . count($data), 1, 1, 'R', true);
    }
    
    // Force download headers
    $filename = 'CCS_Report_' . str_replace(' ', '_', $report_title) . '_' . date('Y-m-d') . '.pdf';
    
    // Clean output buffer before sending PDF
    ob_end_clean();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('D', $filename);
    exit();
}

// ============================================
// CSV EXPORT
// ============================================
if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add lab filter info row at the top
    $lab_label = ($lab_filter !== 'all') ? "Lab $lab_filter" : "All Labs";
    fputcsv($output, ['Lab Filter', $lab_label]);
    fputcsv($output, []); // Empty row for spacing
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// ============================================
// DOC EXPORT
// ============================================
if ($format == 'doc') {
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.doc"');
    
    $lab_label = ($lab_filter !== 'all') ? "Lab $lab_filter" : "All Labs";
    
    echo '<html><body>';
    echo '<h1>CCS Sit-in Monitoring System Report</h1>';
    echo '<p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p><strong>Report Type:</strong> ' . ucfirst($report_type) . '</p>';
    echo '<p><strong>Date Range:</strong> ' . $from_date . ' to ' . $to_date . '</p>';
    echo '<p><strong>Lab:</strong> ' . $lab_label . '</p>';
    echo '<hr>';
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
    exit();
}
?>