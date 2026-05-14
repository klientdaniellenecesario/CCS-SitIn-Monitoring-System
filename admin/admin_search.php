<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$search = '';
$results = [];

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    
    $query = "SELECT u.*,
                     COUNT(DISTINCT s.id) AS total_sessions,
                     SUM(CASE WHEN s.status='active' THEN 1 ELSE 0 END) AS active_sessions
              FROM users u
              LEFT JOIN sit_in_sessions s ON u.id = s.user_id
              WHERE u.role = 'student'
                AND (
                  u.id_number LIKE '%$search%'
                  OR u.first_name LIKE '%$search%'
                  OR u.last_name LIKE '%$search%'
                  OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%'
                  OR u.course LIKE '%$search%'
                  OR u.email LIKE '%$search%'
                  OR u.address LIKE '%$search%'
                )
              GROUP BY u.id
              ORDER BY u.last_name ASC";
    
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h1>Search Students</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <!-- Search Bar -->
            <div class="search-card" style="background:white;border-radius:16px;padding:1.5rem;
                 box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:1.5rem;">
                <form method="GET" action="">
                    <div style="display:flex;gap:1rem;align-items:center;">
                        <div style="position:relative;flex:1;">
                            <i class="fas fa-search" style="position:absolute;left:1rem;top:50%;
                               transform:translateY(-50%);color:#718096;"></i>
                            <input type="text" name="search"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by ID number, name, course, email..."
                                   style="width:100%;padding:0.75rem 1rem 0.75rem 2.5rem;
                                          border:2px solid #e2e8f0;border-radius:12px;
                                          font-family:'Inter',sans-serif;font-size:0.95rem;
                                          transition:border-color 0.3s ease;outline:none;"
                                   onfocus="this.style.borderColor='#0052cc'"
                                   onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        <button type="submit"
                                style="background:linear-gradient(135deg,#0052cc,#0066ff);
                                       color:white;border:none;padding:0.75rem 1.5rem;
                                       border-radius:12px;font-weight:600;cursor:pointer;
                                       display:flex;align-items:center;gap:0.5rem;
                                       font-family:'Inter',sans-serif;white-space:nowrap;">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="admin_search.php"
                               style="background:#f0f4ff;color:#0052cc;border:2px solid #dbeafe;
                                      padding:0.75rem 1.2rem;border-radius:12px;
                                      font-weight:600;text-decoration:none;white-space:nowrap;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <?php if ($search): ?>
                <div class="data-table">
                    <div class="table-header">
                        <h2><i class="fas fa-users"></i> Search Results
                            <span style="font-size:0.85rem;font-weight:400;color:#718096;margin-left:0.5rem;">
                                (<?php echo count($results); ?> student<?php echo count($results) !== 1 ? 's' : ''; ?> found for "<?php echo htmlspecialchars($search); ?>")
                            </span>
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <?php if (count($results) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Profile</th>
                                    <th>ID Number</th>
                                    <th>Full Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Email</th>
                                    <th>Sessions Left</th>
                                    <th>Total Sit-ins</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $s): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($s['profile_picture']) && $s['profile_picture'] !== 'default-avatar.png'): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($s['profile_picture']); ?>"
                                                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;"
                                                 onerror="this.outerHTML='<i class=\'fas fa-user-circle\' style=\'font-size:2rem;color:#0052cc\'></i>'">
                                        <?php else: ?>
                                            <i class="fas fa-user-circle" style="font-size:2rem;color:#0052cc"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['id_number']); ?></td>
                                    <td style="font-weight:500"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['course']); ?></td>
                                    <td><?php echo $s['course_level']; ?></td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td>
                                        <span style="background:#f0f4ff;color:#0052cc;padding:0.2rem 0.6rem;
                                                     border-radius:20px;font-weight:600;font-size:0.85rem;">
                                            <?php echo $s['session_count']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $s['total_sessions']; ?></td>
                                    <td>
                                        <?php if ($s['active_sessions'] > 0): ?>
                                            <span class="status-badge active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge completed">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <div style="text-align:center;padding:3rem;color:#718096;">
                                <i class="fas fa-search" style="font-size:3rem;color:#e2e8f0;display:block;margin-bottom:1rem;"></i>
                                No students found matching "<?php echo htmlspecialchars($search); ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="search-card" style="background:white;border-radius:16px;padding:3rem;
                     text-align:center;color:#718096;">
                    <i class="fas fa-search" style="font-size:4rem;color:#e2e8f0;display:block;margin-bottom:1rem;"></i>
                    <p style="font-size:1.1rem;">Enter a name, ID number, course, or email to search for students.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>