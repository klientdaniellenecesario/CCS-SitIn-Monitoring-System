<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Get all completed sit-in sessions
$query = "SELECT s.*, u.first_name, u.last_name, u.id_number, u.course 
          FROM sit_in_sessions s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.status = 'completed'";

if ($search) {
    $query .= " AND (u.id_number LIKE '%$search%' 
                OR u.first_name LIKE '%$search%' 
                OR u.last_name LIKE '%$search%' 
                OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%'
                OR s.purpose LIKE '%$search%'
                OR s.sit_lab LIKE '%$search%')";
}

$total_query = "SELECT COUNT(*) as total FROM ($query) as temp";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

$query .= " ORDER BY s.time_in DESC LIMIT $offset, $limit";
$sit_ins = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sit-in Records - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional styles for consistent design */
        .search-section {
            margin-bottom: 1.5rem;
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: white;
            padding: 0.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .search-input-wrapper {
            position: relative;
            flex: 1;
        }
        
        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-size: 0.9rem;
        }
        
        .search-input-wrapper input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
            outline: none;
        }
        
        .search-input-wrapper input:focus {
            border-color: #0052cc;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 82, 204, 0.3);
        }
        
        .clear-btn {
            background: #f0f4ff;
            color: #0052cc;
            border: 2px solid #dbeafe;
            padding: 0.75rem 1.2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .clear-btn:hover {
            background: #e3f2fd;
        }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .records-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .records-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }
        
        .records-table tr:hover {
            background: #f7fafc;
        }
        
        .status-badge.completed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .pagination a {
            padding: 0.5rem 0.75rem;
            background: #f8fafc;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover, .pagination a.active {
            background: #0052cc;
            color: white;
        }
        
        .table-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            text-align: right;
            font-size: 0.8rem;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .search-box {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-btn, .clear-btn {
                justify-content: center;
            }
            
            .records-table th, .records-table td {
                padding: 0.5rem;
                font-size: 0.75rem;
            }
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
                <a href="admin_view_sitins.php" class="nav-item active"><i class="fas fa-eye"></i><span>View Sit-in Records</span></a>
                <a href="admin_feedback_reports.php" class="nav-item"><i class="fas fa-comment-dots"></i><span>Feedback Reports</span></a>
                <a href="admin_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                <a href="admin_announcements.php" class="nav-item"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
                <a href="admin_add_reward.php" class="nav-item"><i class="fas fa-gift"></i><span>Add Reward</span></a>
                <a href="admin_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                <a href="admin_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Reports</span></a>
                <a href="../logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i><span>Log out</span></a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>View Sit-in Records</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <!-- Styled Search Bar -->
            <div class="search-section">
                <form method="GET" action="">
                    <div class="search-box">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by ID number, name, course..."
                                   autocomplete="off">
                        </div>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="admin_view_sitins.php" class="clear-btn">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Sit-in Records Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-clock"></i> Complete Sit-in History</h2>
                </div>
                <div class="table-responsive">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>PC</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($sit_ins) > 0): ?>
                                <?php while($sit = mysqli_fetch_assoc($sit_ins)): 
                                    $duration_h = floor($sit['duration_minutes'] / 60);
                                    $duration_m = $sit['duration_minutes'] % 60;
                                    $duration_str = ($duration_h > 0 ? $duration_h . 'h ' : '') . $duration_m . 'm';
                                    $display_date = ($sit['session_date'] === '0000-00-00' || empty($sit['session_date']))
                                        ? date('M d, Y', strtotime($sit['time_in']))
                                        : date('M d, Y', strtotime($sit['session_date']));
                                    $display_purpose = !empty($sit['purpose']) ? htmlspecialchars($sit['purpose']) : '<em style="color:#a0aec0;">Not specified</em>';
                                    $display_pc = !empty($sit['pc_number']) ? htmlspecialchars($sit['pc_number']) : '--';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sit['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['first_name'] . ' ' . $sit['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sit['course']); ?></td>
                                    <td><?php echo $display_purpose; ?></td>
                                    <td><?php echo htmlspecialchars($sit['sit_lab']); ?></td>
                                    <td><?php echo $display_pc; ?></td>
                                    <td><?php echo $display_date; ?></td>
                                    <td><?php echo !empty($sit['time_in']) ? date('h:i A', strtotime($sit['time_in'])) : '--'; ?></td>
                                    <td><?php echo !empty($sit['time_out']) ? date('h:i A', strtotime($sit['time_out'])) : '--'; ?></td>
                                    <td><?php echo $duration_str; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 2rem; color: #718096;">
                                        <i class="fas fa-search" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                        No sit-in records found
                                        <?php if ($search): ?>
                                            <br>for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                        <?php endif; ?>
                                    </td>
                                </tr>
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
                <div class="table-footer">
                    <p>Showing <?php echo mysqli_num_rows($sit_ins); ?> of <?php echo $total_rows; ?> entries</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>