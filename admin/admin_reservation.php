<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$active_lab = isset($_GET['lab']) ? $_GET['lab'] : '524';

// Handle approve reservation
if (isset($_POST['approve'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $user_id = intval($_POST['user_id']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    $pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
    $time_in = $_POST['time_in'];
    $date = $_POST['date'];
    $datetime_in = $date . ' ' . $time_in . ':00';
    
    mysqli_query($conn, "UPDATE reservations SET status = 'approved' WHERE id = $reservation_id");
    
    $check_active = mysqli_query($conn, "SELECT id FROM sit_in_sessions WHERE user_id = $user_id AND status = 'active'");
    if (mysqli_num_rows($check_active) > 0) {
        $error = "Student already has an active sit-in session!";
        header('Location: admin_reservation.php?lab=' . $lab);
        exit();
    }
    
    $insert = "INSERT INTO sit_in_sessions (user_id, session_date, session_time, time_in, purpose, sit_lab, pc_number, status) 
               VALUES ('$user_id', '$date', '$time_in', '$datetime_in', '$purpose', '$lab', '$pc_number', 'active')";
    
    if (mysqli_query($conn, $insert)) {
        mysqli_query($conn, "UPDATE users SET session_count = GREATEST(0, session_count - 1) WHERE id = $user_id");
        $notification = "Your reservation for $purpose at Lab $lab, PC $pc_number on " . date('M d, Y', strtotime($date)) . 
                        " at " . date('h:i A', strtotime($time_in)) . " has been APPROVED.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) 
                             VALUES ('$user_id', '$notification', 'success', NOW())");
        $success = "Reservation approved and sit-in session created!";
    } else {
        $error = "Failed to create sit-in session.";
    }
    header('Location: admin_reservation.php?lab=' . $lab);
    exit();
}

// Handle reject reservation
if (isset($_POST['reject'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $user_id = intval($_POST['user_id']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    $pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
    $date = $_POST['date'];
    
    mysqli_query($conn, "UPDATE reservations SET status = 'rejected' WHERE id = $reservation_id");
    $notification = "Your reservation for $purpose at Lab $lab, PC $pc_number on " . date('M d, Y', strtotime($date)) . 
                    " has been REJECTED.";
    mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) 
                         VALUES ('$user_id', '$notification', 'warning', NOW())");
    header('Location: admin_reservation.php?lab=' . $lab);
    exit();
}

// Handle delete reservation
if (isset($_POST['delete'])) {
    $reservation_id = intval($_POST['reservation_id']);
    mysqli_query($conn, "DELETE FROM reservations WHERE id = $reservation_id");
    header('Location: admin_reservation.php?lab=' . $active_lab);
    exit();
}

// Get all reservations
$reservations = mysqli_query($conn, "SELECT r.*, u.first_name, u.last_name, u.id_number 
                                     FROM reservations r 
                                     JOIN users u ON r.user_id = u.id 
                                     ORDER BY r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Management - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lab-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .lab-tab {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            color: #4a5568;
            transition: all 0.3s ease;
        }
        .lab-tab:hover { background: #e2e8f0; }
        .lab-tab.active { background: linear-gradient(135deg, #0052cc, #0066ff); color: white; }
        
        .pc-grid-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 1rem;
        }
        .pc-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 0.75rem;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .pc-card.available { border-color: #cbd5e0; background: #f8fafc; }
        .pc-card.unavailable { background: #fff5f5; border-color: #dc3545; }
        .pc-card.occupied { background: #fff5f5; border-color: #e53935; }
        .pc-card.requested { background: #fffff0; border-color: #ffd600; }
        .pc-icon { font-size: 2rem; margin-bottom: 0.25rem; }
        .pc-card.available .pc-icon { color: #0052cc; }
        .pc-card.unavailable .pc-icon { color: #dc3545; }
        .pc-card.occupied .pc-icon { color: #e53935; }
        .pc-card.requested .pc-icon { color: #ffd600; }
        .pc-number { font-weight: 700; font-size: 0.9rem; color: #1a202c; }
        .pc-status { font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 10px; display: inline-block; margin-top: 0.25rem; }
        .pc-status.available { background: #e8f5e9; color: #2e7d32; }
        .pc-status.unavailable { background: #fee; color: #dc3545; }
        .pc-status.occupied { background: #fee; color: #c33; }
        .pc-status.requested { background: #fff3e0; color: #ed6c02; }
        .pc-user { font-size: 0.7rem; color: #718096; margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pc-actions { display: flex; gap: 0.3rem; justify-content: center; margin-top: 0.5rem; }
        .btn-end-session, .btn-approve-pc, .btn-reject-pc { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; cursor: pointer; border: none; }
        .btn-end-session { background: #ed6c02; color: white; }
        .btn-end-session:hover { background: #c24500; }
        .btn-approve-pc { background: #2e7d32; color: white; }
        .btn-approve-pc:hover { background: #1b5e20; }
        .btn-reject-pc { background: #c33; color: white; }
        .btn-reject-pc:hover { background: #a00; }
        
        /* Toggle buttons for PC availability */
        .toggle-pc-btn {
            margin-top: 0.5rem;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
        }
        .disable-btn {
            background: #dc3545;
            color: white;
        }
        .disable-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .enable-btn {
            background: #28a745;
            color: white;
        }
        .enable-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        /* Reservations Table */
        .data-table {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #ffd600;
        }
        .table-header h2 {
            font-size: 1rem;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
            vertical-align: middle;
        }
        tr:hover { background: #f7fafc; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-badge.pending { background: #fff3e0; color: #ed6c02; }
        .status-badge.approved { background: #e8f5e9; color: #2e7d32; }
        .status-badge.rejected { background: #fee; color: #c33; }
        .btn-approve {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 0.3rem;
        }
        .btn-reject {
            background: #ed6c02;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 0.3rem;
        }
        .btn-delete {
            background: #fee;
            color: #c33;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-delete:hover { background: #c33; color: white; }
        .success-message, .error-message {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .success-message { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .error-message { background: #fee; color: #c33; border-left: 4px solid #c33; }
        @media (max-width: 768px) { .pc-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); } }
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
                <a href="admin_sitins.php" class="nav-item "><i class="fas fa-clock"></i><span>Sit-in</span></a>
                <a href="admin_view_sitins.php" class="nav-item "><i class="fas fa-eye"></i><span>View Sit-in Records</span></a>
                <a href="admin_feedback_reports.php" class="nav-item "><i class="fas fa-comment-dots"></i><span>Feedback Reports</span></a>
                <a href="admin_reservation.php" class="nav-item active"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
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
                <h1>Reservation Management</h1>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-user-cog"></i>
                </div>
            </header>

            <?php if (isset($success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Lab Tabs -->
            <div class="lab-tabs">
                <a href="?lab=524" class="lab-tab <?php echo $active_lab == '524' ? 'active' : ''; ?>">Lab 524</a>
                <a href="?lab=525" class="lab-tab <?php echo $active_lab == '525' ? 'active' : ''; ?>">Lab 525</a>
                <a href="?lab=526" class="lab-tab <?php echo $active_lab == '526' ? 'active' : ''; ?>">Lab 526</a>
                <a href="?lab=527" class="lab-tab <?php echo $active_lab == '527' ? 'active' : ''; ?>">Lab 527</a>
            </div>

            <!-- PC Monitor Section -->
            <div class="pc-grid-container">
                <div class="pc-grid-title">
                    <h3><i class="fas fa-desktop"></i> Lab <?php echo $active_lab; ?> - PC Monitor</h3>
                </div>
                <div class="pc-legend" style="display: flex; gap: 1.5rem; margin-bottom: 1rem; flex-wrap: wrap; padding: 0.75rem; background: #f8fafc; border-radius: 12px;">
                    <div class="legend-item"><i class="fas fa-desktop" style="color: #0052cc;"></i><span>Available</span></div>
                    <div class="legend-item"><i class="fas fa-desktop" style="color: #e53935;"></i><span>Occupied - Active Session</span></div>
                    <div class="legend-item"><i class="fas fa-desktop" style="color: #ffd600;"></i><span>Pending Reservation Request</span></div>
                    <div class="legend-item"><i class="fas fa-ban" style="color: #dc3545;"></i><span>Unavailable (Admin Disabled)</span></div>
                </div>
                <div id="adminPcGrid" class="pc-grid">
                    <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading PCs...</div>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> Student Reservations</h2>
                </div>
                <div class="table-responsive">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Number</th>
                                <th>Student Name</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>PC</th>
                                <th>Time In</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Reserved On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($reservations) > 0): ?>
                                <?php while($res = mysqli_fetch_assoc($reservations)): ?>
                                <tr>
                                    <td><?php echo $res['id']; ?></td>
                                    <td><?php echo htmlspecialchars($res['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($res['lab']); ?></td>
                                    <td><?php echo htmlspecialchars($res['pc_number']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($res['time_in'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($res['date'])); ?></td>
                                    <td><span class="status-badge <?php echo $res['status']; ?>"><?php echo ucfirst($res['status']); ?></span></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($res['created_at'])); ?></td>
                                    <td class="actions">
                                        <?php if($res['status'] == 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $res['user_id']; ?>">
                                                <input type="hidden" name="purpose" value="<?php echo $res['purpose']; ?>">
                                                <input type="hidden" name="lab" value="<?php echo $res['lab']; ?>">
                                                <input type="hidden" name="pc_number" value="<?php echo $res['pc_number']; ?>">
                                                <input type="hidden" name="time_in" value="<?php echo $res['time_in']; ?>">
                                                <input type="hidden" name="date" value="<?php echo $res['date']; ?>">
                                                <button type="submit" name="approve" class="btn-approve" onclick="return confirm('Approve this reservation?')">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $res['user_id']; ?>">
                                                <input type="hidden" name="purpose" value="<?php echo $res['purpose']; ?>">
                                                <input type="hidden" name="lab" value="<?php echo $res['lab']; ?>">
                                                <input type="hidden" name="pc_number" value="<?php echo $res['pc_number']; ?>">
                                                <input type="hidden" name="date" value="<?php echo $res['date']; ?>">
                                                <button type="submit" name="reject" class="btn-reject" onclick="return confirm('Reject this reservation?')">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                            <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Delete this reservation?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="11" style="text-align: center;">No reservations found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function loadAdminPCGrid() {
            const lab = '<?php echo $active_lab; ?>';
            const gridContainer = document.getElementById('adminPcGrid');
            gridContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading PCs...</div>';
            
            fetch('../backend/get_pc_status.php?lab=' + encodeURIComponent(lab))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    for (let i = 1; i <= 30; i++) {
                        const pcNumber = `PC-${i.toString().padStart(2, '0')}`;
                        const status = data[pcNumber];
                        let statusClass = '', statusText = '', iconColor = '', userHtml = '', actionsHtml = '', toggleButton = '';
                        let toggleDisabled = false;
                        
                        if (status.status === 'available') {
                            statusClass = 'available';
                            statusText = 'Available';
                            iconColor = '#0052cc';
                            toggleButton = `<button class="toggle-pc-btn disable-btn" onclick="togglePcStatus(this, '${lab}', '${pcNumber}', 0)">Mark Unavailable</button>`;
                        } else if (status.status === 'unavailable') {
                            statusClass = 'unavailable';
                            statusText = 'Unavailable (Admin)';
                            iconColor = '#dc3545';
                            toggleButton = `<button class="toggle-pc-btn enable-btn" onclick="togglePcStatus(this, '${lab}', '${pcNumber}', 1)">Mark Available</button>`;
                        } else if (status.status === 'occupied') {
                            statusClass = 'occupied';
                            statusText = 'Occupied';
                            iconColor = '#e53935';
                            userHtml = `<div class="pc-user"><i class="fas fa-user"></i> ${status.user || 'Unknown'}</div>`;
                            actionsHtml = `<div class="pc-actions"><button class="btn-end-session" onclick="endSession(${status.session_id}, '${pcNumber}', '${lab}')">End Session</button></div>`;
                            toggleDisabled = true;
                        } else if (status.status === 'requested') {
                            statusClass = 'requested';
                            statusText = 'Reserved';
                            iconColor = '#ffd600';
                            userHtml = `<div class="pc-user"><i class="fas fa-user"></i> ${status.user || 'Unknown'}</div>`;
                            actionsHtml = `<div class="pc-actions"><button class="btn-approve-pc" onclick="approveReservation(${status.reservation_id}, ${status.user_id}, '${pcNumber}', '${lab}')">Approve</button><button class="btn-reject-pc" onclick="rejectReservation(${status.reservation_id}, ${status.user_id}, '${pcNumber}', '${lab}')">Reject</button></div>`;
                            toggleDisabled = true;
                        }
                        
                        html += `
                            <div class="pc-card ${statusClass}">
                                <i class="fas fa-desktop pc-icon" style="color: ${iconColor};"></i>
                                <div class="pc-number">${pcNumber}</div>
                                <span class="pc-status ${statusClass}">${statusText}</span>
                                ${userHtml}
                                ${actionsHtml}
                                ${!toggleDisabled ? toggleButton : ''}
                            </div>
                        `;
                    }
                    gridContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    gridContainer.innerHTML = '<div class="loading-spinner">Error loading PCs. Please refresh.</div>';
                });
        }
        
        function togglePcStatus(button, lab, pcNumber, currentDisabled) {
            const newDisabled = currentDisabled === 1 ? 0 : 1;
            const confirmMsg = newDisabled === 1
                ? `Mark ${pcNumber} in Lab ${lab} as unavailable for students?`
                : `Mark ${pcNumber} in Lab ${lab} as available again?`;

            if (!confirm(confirmMsg)) return;

            fetch('../backend/update_pc_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lab=${lab}&pc_number=${pcNumber}&admin_disabled=${newDisabled}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tile = button.closest('.pc-card');
                    const icon = tile.querySelector('.pc-icon');
                    const label = tile.querySelector('.pc-status');
                    
                    if (newDisabled === 1) {
                        tile.style.borderColor = '#dc3545';
                        if (icon) icon.style.color = '#dc3545';
                        if (label) {
                            label.textContent = 'Unavailable (Admin)';
                            label.style.color = '#dc3545';
                            label.className = 'pc-status unavailable';
                        }
                        button.textContent = 'Mark Available';
                        button.className = 'toggle-pc-btn enable-btn';
                        button.onclick = () => togglePcStatus(button, lab, pcNumber, 1);
                    } else {
                        tile.style.borderColor = '';
                        if (icon) icon.style.color = '#0052cc';
                        if (label) {
                            label.textContent = 'Available';
                            label.style.color = '#28a745';
                            label.className = 'pc-status available';
                        }
                        button.textContent = 'Mark Unavailable';
                        button.className = 'toggle-pc-btn disable-btn';
                        button.onclick = () => togglePcStatus(button, lab, pcNumber, 0);
                    }
                } else {
                    alert('Something went wrong. Please try again.');
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        }
        
        function endSession(sessionId, pcNumber, lab) {
            if (confirm(`End session for ${pcNumber} in Lab ${lab}?`)) {
                fetch('../backend/end_session_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `session_id=${sessionId}&pc_number=${pcNumber}&lab=${lab}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) { alert('Session ended successfully!'); location.reload(); }
                    else { alert('Error: ' + (data.error || 'Unknown error')); }
                })
                .catch(error => { console.error('Error:', error); alert('Failed to end session.'); });
            }
        }
        
        function approveReservation(reservationId, userId, pcNumber, lab) {
            if (confirm(`Approve reservation for ${pcNumber} in Lab ${lab}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.innerHTML = `
                    <input type="hidden" name="reservation_id" value="${reservationId}">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="lab" value="${lab}">
                    <input type="hidden" name="pc_number" value="${pcNumber}">
                    <input type="hidden" name="purpose" value="">
                    <input type="hidden" name="time_in" value="">
                    <input type="hidden" name="date" value="">
                    <input type="hidden" name="approve" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectReservation(reservationId, userId, pcNumber, lab) {
            if (confirm(`Reject reservation for ${pcNumber} in Lab ${lab}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.innerHTML = `
                    <input type="hidden" name="reservation_id" value="${reservationId}">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="lab" value="${lab}">
                    <input type="hidden" name="pc_number" value="${pcNumber}">
                    <input type="hidden" name="purpose" value="">
                    <input type="hidden" name="date" value="">
                    <input type="hidden" name="reject" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        setInterval(loadAdminPCGrid, 30000);
        loadAdminPCGrid();
    </script>
</body>
</html>