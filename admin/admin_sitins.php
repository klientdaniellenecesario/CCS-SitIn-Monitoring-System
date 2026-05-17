<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

// Get all students
$students = mysqli_query($conn, "SELECT * FROM users WHERE role = 'student' OR role IS NULL ORDER BY last_name ASC");

// Handle manual sit-in creation
if (isset($_POST['start_sit_in'])) {
    $user_id = intval($_POST['user_id']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    $pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
    $session_date = date('Y-m-d');
    $session_time = date('H:i:s');
    $time_in = date('Y-m-d H:i:s');
    
    // Check if student already has active session
    $check_active = mysqli_query($conn, "SELECT id FROM sit_in_sessions WHERE user_id = $user_id AND status = 'active'");
    
    if (mysqli_num_rows($check_active) > 0) {
        $error = "Student already has an active sit-in session!";
    } else {
        // Check if PC is available
        $check_pc = mysqli_query($conn, "SELECT id FROM sit_in_sessions WHERE sit_lab = '$lab' AND pc_number = '$pc_number' AND status = 'active'");
        if (mysqli_num_rows($check_pc) > 0) {
            $error = "PC $pc_number is currently occupied!";
        } else {
            $check_student = mysqli_query($conn, "SELECT session_count FROM users WHERE id = $user_id");
            $student_data = mysqli_fetch_assoc($check_student);
            
            if ($student_data['session_count'] > 0) {
                $insert = "INSERT INTO sit_in_sessions (user_id, session_date, session_time, time_in, purpose, sit_lab, pc_number, status) 
                           VALUES ('$user_id', '$session_date', '$session_time', '$time_in', '$purpose', '$lab', '$pc_number', 'active')";
                
                if (mysqli_query($conn, $insert)) {
                    mysqli_query($conn, "UPDATE users SET session_count = session_count - 1 WHERE id = $user_id");
                    $success = "Sit-in session started successfully at PC $pc_number!";
                } else {
                    $error = "Failed to start sit-in session.";
                }
            } else {
                $error = "Student has no remaining sessions! Please reset sessions first.";
            }
        }
    }
}

// Handle end session
if (isset($_POST['end_sit_in'])) {
    $session_id = intval($_POST['session_id']);
    $user_id = intval($_POST['user_id']);
    $time_out = date('Y-m-d H:i:s');
    
    $session_query = mysqli_query($conn, "SELECT time_in FROM sit_in_sessions WHERE id = $session_id");
    $session_data = mysqli_fetch_assoc($session_query);
    $time_in = $session_data['time_in'];
    
    $duration_minutes = 0;
    if ($time_in) {
        $datetime1 = new DateTime($time_in);
        $datetime2 = new DateTime($time_out);
        $interval = $datetime1->diff($datetime2);
        $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    }
    
    $query = "UPDATE sit_in_sessions SET status = 'completed', time_out = '$time_out', duration_minutes = $duration_minutes WHERE id = $session_id";
    
    if (mysqli_query($conn, $query)) {
        $success = "Sit-in session ended successfully.";
    } else {
        $error = "Failed to end sit-in session.";
    }
    header('Location: admin_sitins.php');
    exit();
}

// Get active sit-ins
$active_sitins = mysqli_query($conn, "SELECT s.*, u.first_name, u.last_name, u.id_number, u.course 
                                      FROM sit_in_sessions s 
                                      JOIN users u ON s.user_id = u.id 
                                      WHERE s.status = 'active'
                                      ORDER BY s.time_in DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Management - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .live-dot {
            display: inline-block;
            width: 8px; height: 8px; border-radius: 50%;
            background: #22c55e;
            margin-right: 6px;
            animation: pulse-dot 1.5s ease-in-out infinite;
            vertical-align: middle;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }
        .live-timer { color: #16a34a; font-weight: 600; font-size: 0.85rem; }
        .btn-end {
            background: linear-gradient(135deg, #ed6c02, #f57c00);
            color: white; border: none;
            padding: 0.4rem 1rem; border-radius: 8px;
            cursor: pointer; font-size: 0.78rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 0.3rem;
            transition: all 0.3s ease;
        }
        .btn-end:hover { background: linear-gradient(135deg, #c24500, #ed6c02); transform: translateY(-1px); }
        
        /* PC Grid Styles */
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        .pc-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            background: white;
        }
        .pc-item.available:hover {
            border-color: #0052cc;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,82,204,0.2);
        }
        .pc-item.available.selected {
            border-color: #ffd600;
            background: #fffbf0;
        }
        .pc-item.occupied, .pc-item.reserved {
            opacity: 0.5;
            cursor: not-allowed;
            background: #e2e8f0;
        }
        .pc-item.occupied { border-color: #e53935; }
        .pc-item.reserved { border-color: #ffd600; }
        .pc-icon { font-size: 1.2rem; margin-bottom: 0.2rem; }
        .pc-number { font-size: 0.7rem; font-weight: 600; }
        .pc-status-badge { font-size: 0.6rem; padding: 0.1rem 0.3rem; border-radius: 10px; margin-top: 0.2rem; }
        .pc-status-badge.available { background: #e8f5e9; color: #2e7d32; }
        .pc-status-badge.occupied { background: #fee; color: #c33; }
        .pc-status-badge.reserved { background: #fff3e0; color: #ed6c02; }
        
        .lab-full-message {
            text-align: center;
            padding: 1rem;
            color: #e53935;
            font-weight: 500;
        }
        
        .search-student {
            position: relative;
            margin-bottom: 1rem;
        }
        .search-student input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
        }
        .student-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        .student-suggestion {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
        }
        .student-suggestion:hover { background: #f8fafc; }
        
        @media (max-width: 1200px) { .pc-grid { grid-template-columns: repeat(8, 1fr); } }
        @media (max-width: 900px) { .pc-grid { grid-template-columns: repeat(6, 1fr); } }
        @media (max-width: 600px) { .pc-grid { grid-template-columns: repeat(5, 1fr); } }
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
                <a href="admin_dashboard.php" class="nav-item "><i class="fas fa-home"></i><span>Home</span></a>
                <a href="admin_search.php" class="nav-item "><i class="fas fa-search"></i><span>Search</span></a>
                <a href="admin_students.php" class="nav-item "><i class="fas fa-users"></i><span>Students</span></a>
                <a href="admin_sitins.php" class="nav-item active"><i class="fas fa-clock"></i><span>Sit-in</span></a>
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
                <h1>Sit-in Management</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Start New Sit-in Form -->
            <div class="form-card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Start New Sit-in Session</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="sitInForm">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="student_search">Student</label>
                                <div class="search-student">
                                    <input type="text" id="student_search" placeholder="Search by ID number or name..." autocomplete="off">
                                    <input type="hidden" name="user_id" id="selected_user_id">
                                    <div id="student_suggestions" class="student-suggestions"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="purpose">Purpose</label>
                                <select name="purpose" id="purpose" class="form-control" required>
                                    <option value="">Select Purpose</option>
                                    <option value="C Programming">C Programming</option>
                                    <option value="Java Programming">Java Programming</option>
                                    <option value="Python Programming">Python Programming</option>
                                    <option value="Web Development">Web Development</option>
                                    <option value="Research">Research</option>
                                    <option value="Group Study">Group Study</option>
                                    <option value="Project Meeting">Project Meeting</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lab">Lab</label>
                                <select name="lab" id="lab" class="form-control" required>
                                    <option value="">Select Lab</option>
                                    <option value="524">524</option>
                                    <option value="525">525</option>
                                    <option value="526">526</option>
                                    <option value="527">527</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- PC Selection Grid -->
                        <div id="pc-selection-container" style="display: none;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3748;">
                                <i class="fas fa-desktop"></i> Select PC Number
                            </label>
                            <div id="pc-grid" class="pc-grid">
                                <!-- PCs will be loaded here -->
                            </div>
                            <div id="lab-full-message" class="lab-full-message" style="display: none;"></div>
                            <input type="hidden" name="pc_number" id="selected_pc_number" required>
                        </div>
                        
                        <div class="form-buttons" style="margin-top: 1.5rem;">
                            <button type="submit" name="start_sit_in" id="startSitInBtn" class="btn-submit" disabled>Start Sit-in Session</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Active Sit-ins Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-hourglass-half"></i> Active Sit-ins</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>PC</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Time In</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sit = mysqli_fetch_assoc($active_sitins)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sit['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($sit['first_name'] . ' ' . $sit['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($sit['course']); ?></td>
                                <td><?php echo htmlspecialchars($sit['pc_number']); ?></td>
                                <td><?php echo !empty($sit['purpose']) ? htmlspecialchars($sit['purpose']) : '<em style="color:#a0aec0;">Not specified</em>'; ?></td>
                                <td><?php echo htmlspecialchars($sit['sit_lab']); ?></td>
                                <td><?php echo date('h:i A', strtotime($sit['time_in'])); ?></td>
                                <td class="duration-cell">
                                    <?php if (!empty($sit['time_in'])): ?>
                                        <span class="live-timer" data-timein="<?php echo strtotime($sit['time_in']) * 1000; ?>">
                                            <span class="live-dot"></span>
                                            <span class="timer-text">calculating...</span>
                                        </span>
                                    <?php else: ?>--<?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="session_id" value="<?php echo $sit['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $sit['user_id']; ?>">
                                        <button type="submit" name="end_sit_in" class="btn-end" onclick="return confirm('End this sit-in session?')">
                                            <i class="fas fa-stop-circle"></i> End Session
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($active_sitins) == 0): ?>
                                <tr><td colspan="9" style="text-align: center;">No active sit-ins</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Student search functionality
        const studentSearch = document.getElementById('student_search');
        const suggestionsDiv = document.getElementById('student_suggestions');
        const selectedUserId = document.getElementById('selected_user_id');
        
        studentSearch.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }
            
            fetch(`../backend/search_students_simple.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        suggestionsDiv.innerHTML = data.map(student => `
                            <div class="student-suggestion" data-id="${student.id}" data-name="${student.first_name} ${student.last_name}" data-idnumber="${student.id_number}">
                                <strong>${student.id_number}</strong> - ${student.first_name} ${student.last_name} (${student.course})
                            </div>
                        `).join('');
                        suggestionsDiv.style.display = 'block';
                    } else {
                        suggestionsDiv.innerHTML = '<div class="student-suggestion" style="color:#718096;">No students found</div>';
                        suggestionsDiv.style.display = 'block';
                    }
                });
        });
        
        document.addEventListener('click', function(e) {
            if (!studentSearch.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
        
        suggestionsDiv.addEventListener('click', function(e) {
            const suggestion = e.target.closest('.student-suggestion');
            if (suggestion && suggestion.dataset.id) {
                studentSearch.value = `${suggestion.dataset.idnumber} - ${suggestion.dataset.name}`;
                selectedUserId.value = suggestion.dataset.id;
                suggestionsDiv.style.display = 'none';
                checkFormComplete();
            }
        });
        
        // PC Grid functionality
        const labSelect = document.getElementById('lab');
        const pcGridContainer = document.getElementById('pc-selection-container');
        const pcGrid = document.getElementById('pc-grid');
        const labFullMessage = document.getElementById('lab-full-message');
        const selectedPcNumber = document.getElementById('selected_pc_number');
        const startBtn = document.getElementById('startSitInBtn');
        const purposeSelect = document.getElementById('purpose');
        
        function checkFormComplete() {
            if (selectedUserId.value && purposeSelect.value && labSelect.value && selectedPcNumber.value) {
                startBtn.disabled = false;
            } else {
                startBtn.disabled = true;
            }
        }
        
        purposeSelect.addEventListener('change', checkFormComplete);
        labSelect.addEventListener('change', function() {
            selectedPcNumber.value = '';
            checkFormComplete();
            loadPCGrid();
        });
        
        function loadPCGrid() {
            const lab = labSelect.value;
            if (!lab) {
                pcGridContainer.style.display = 'none';
                return;
            }
            
            pcGridContainer.style.display = 'block';
            pcGrid.innerHTML = '<div style="text-align:center; padding:1rem;"><i class="fas fa-spinner fa-spin"></i> Loading PCs...</div>';
            
            fetch(`../backend/get_pc_status.php?lab=${encodeURIComponent(lab)}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    let availableCount = 0;
                    
                    for (let i = 1; i <= 30; i++) {
                        const pcNumber = `PC-${i.toString().padStart(2, '0')}`;
                        const status = data[pcNumber];
                        let statusClass = '';
                        let statusText = '';
                        let disabled = false;
                        let selectable = false;
                        
                        if (status.status === 'available') {
                            statusClass = 'available';
                            statusText = 'Free';
                            selectable = true;
                            availableCount++;
                        } else if (status.status === 'occupied') {
                            statusClass = 'occupied';
                            statusText = 'Occupied';
                            disabled = true;
                        } else if (status.status === 'requested') {
                            statusClass = 'reserved';
                            statusText = 'Requested';
                            disabled = true;
                        }
                        
                        const selectableAttr = selectable ? `onclick="selectPC('${pcNumber}')"` : '';
                        const selectedAttr = (selectedPcNumber.value === pcNumber) ? 'selected' : '';
                        
                        html += `
                            <div class="pc-item ${statusClass} ${selectedAttr}" data-pc="${pcNumber}" ${selectableAttr}>
                                <i class="fas fa-desktop pc-icon"></i>
                                <span class="pc-number">${pcNumber}</span>
                                <span class="pc-status-badge ${statusClass}">${statusText}</span>
                            </div>
                        `;
                    }
                    
                    if (availableCount === 0) {
                        labFullMessage.style.display = 'block';
                        labFullMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lab is full! No available PCs.';
                        pcGrid.innerHTML = '';
                    } else {
                        labFullMessage.style.display = 'none';
                        pcGrid.innerHTML = html;
                    }
                });
        }
        
        function selectPC(pcNumber) {
            // Remove selected class from all PC items
            document.querySelectorAll('.pc-item').forEach(item => {
                item.classList.remove('selected');
            });
            // Add selected class to clicked PC
            const clickedItem = document.querySelector(`.pc-item[data-pc="${pcNumber}"]`);
            if (clickedItem) {
                clickedItem.classList.add('selected');
            }
            selectedPcNumber.value = pcNumber;
            checkFormComplete();
        }
        
        // Live timer for active sessions
        function updateTimers() {
            document.querySelectorAll('.live-timer').forEach(function(el) {
                const timeIn = parseInt(el.dataset.timein);
                if (!timeIn || isNaN(timeIn)) return;
                const now = Date.now();
                const diffMs = now - timeIn;
                if (diffMs < 0) { el.querySelector('.timer-text').textContent = '0h 0m 0s'; return; }
                const totalSeconds = Math.floor(diffMs / 1000);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                el.querySelector('.timer-text').textContent = h + 'h ' + m + 'm ' + s + 's';
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();
    </script>
</body>
</html>