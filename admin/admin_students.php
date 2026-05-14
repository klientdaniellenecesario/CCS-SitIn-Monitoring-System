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

// Handle update session count
if (isset($_POST['update_sessions'])) {
    $student_id = intval($_POST['student_id']);
    $new_session_count = intval($_POST['session_count']);
    
    $update = "UPDATE users SET session_count = $new_session_count WHERE id = $student_id";
    if (mysqli_query($conn, $update)) {
        $success = "Session count updated successfully!";
    } else {
        $error = "Failed to update session count.";
    }
    header('Location: admin_students.php');
    exit();
}

// Handle delete student
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    header('Location: admin_students.php');
    exit();
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Get students with sit-in count
$query = "SELECT u.*, COUNT(s.id) as total_sitins 
          FROM users u 
          LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
          WHERE u.role = 'student' OR u.role IS NULL";

if ($search) {
    $query .= " AND (u.id_number LIKE '%$search%' 
                OR u.first_name LIKE '%$search%' 
                OR u.last_name LIKE '%$search%' 
                OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%'
                OR u.course LIKE '%$search%')";
}

$total_query = "SELECT COUNT(*) as total FROM ($query) as temp";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

$query .= " GROUP BY u.id ORDER BY u.last_name ASC LIMIT $offset, $limit";
$students = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-edit-sessions {
            background: #fff3e0;
            color: #ed6c02;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
        }
        .btn-edit-sessions:hover {
            background: #ed6c02;
            color: white;
            transform: translateY(-1px);
        }
        .sessions-cell {
            font-weight: 600;
        }
        .low-sessions {
            color: #e53935;
            background: #fee;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            display: inline-block;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #ffd600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn-modal-cancel {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-modal-save {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
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
                <a href="admin_dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="admin_search.php" class="nav-item"><i class="fas fa-search"></i><span>Search</span></a>
                <a href="admin_students.php" class="nav-item active"><i class="fas fa-users"></i><span>Students</span></a>
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
                <h1>Student List</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <!-- Search Bar -->
            <div class="search-section" style="margin-bottom: 1.5rem;">
                <form method="GET" action="">
                    <div class="search-box" style="display: flex; gap: 1rem; align-items: center; background: white; padding: 0.5rem; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #718096;"></i>
                            <input type="text" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by ID number, name, course..."
                                   style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem;
                                          border: 2px solid #e2e8f0; border-radius: 12px;
                                          font-family: 'Inter', sans-serif; font-size: 0.95rem;
                                          transition: border-color 0.3s ease; outline: none;">
                        </div>
                        <button type="submit"
                                style="background: linear-gradient(135deg, #0052cc, #0066ff);
                                       color: white; border: none; padding: 0.75rem 1.5rem;
                                       border-radius: 12px; font-weight: 600; cursor: pointer;
                                       display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="admin_students.php"
                               style="background: #f0f4ff; color: #0052cc; border: 2px solid #dbeafe;
                                      padding: 0.75rem 1.2rem; border-radius: 12px;
                                      font-weight: 600; text-decoration: none;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-users"></i> Registered Students</h2>
                </div>
                <div class="table-responsive">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Email</th>
                                <th>Sit-ins</th>
                                <th>Remaining Sessions</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($students) > 0): ?>
                                <?php while($student = mysqli_fetch_assoc($students)): 
                                    $sessions_left = $student['session_count'];
                                    $sessions_class = $sessions_left <= 5 ? 'low-sessions' : '';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo $student['course_level']; ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['total_sitins']; ?></td>
                                    <td class="sessions-cell">
                                        <span class="<?php echo $sessions_class; ?>"><?php echo $sessions_left; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn-edit-sessions" onclick="openSessionsModal(<?php echo $student['id']; ?>, <?php echo $sessions_left; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                            <i class="fas fa-plus-circle"></i> Add Sessions
                                        </button>
                                        <a href="admin_students.php?delete=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('Delete this student?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" style="text-align: center;">No students found</td></tr>
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
                    <p>Showing <?php echo mysqli_num_rows($students); ?> of <?php echo $total_rows; ?> entries</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Sessions Modal -->
    <div id="sessionsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Update Remaining Sessions</h3>
                <span class="close" onclick="closeSessionsModal()" style="font-size: 1.5rem; cursor: pointer;">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="student_id" id="modal_student_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Student Name</label>
                        <input type="text" id="modal_student_name" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="session_count">Remaining Sessions</label>
                        <input type="number" name="session_count" id="modal_session_count" class="form-control" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeSessionsModal()">Cancel</button>
                    <button type="submit" name="update_sessions" class="btn-modal-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSessionsModal(id, sessions, name) {
            document.getElementById('modal_student_id').value = id;
            document.getElementById('modal_session_count').value = sessions;
            document.getElementById('modal_student_name').value = name;
            document.getElementById('sessionsModal').style.display = 'flex';
        }
        
        function closeSessionsModal() {
            document.getElementById('sessionsModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('sessionsModal');
            if (event.target == modal) {
                closeSessionsModal();
            }
        }
    </script>
</body>
</html>