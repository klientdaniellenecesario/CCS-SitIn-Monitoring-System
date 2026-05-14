<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$error = '';
$success = '';
$selected_pc = '';

// Handle reservation submission
if (isset($_POST['reserve'])) {
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $lab = mysqli_real_escape_string($conn, $_POST['lab']);
    $pc_number = mysqli_real_escape_string($conn, $_POST['pc_number']);
    $time_in = mysqli_real_escape_string($conn, $_POST['time_in']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    
    // Check if PC is already reserved or occupied
    $check_pc = mysqli_query($conn, "SELECT id FROM reservations WHERE lab = '$lab' AND pc_number = '$pc_number' AND status = 'pending'");
    $check_active = mysqli_query($conn, "SELECT id FROM sit_in_sessions WHERE sit_lab = '$lab' AND pc_number = '$pc_number' AND status = 'active'");
    
    if (mysqli_num_rows($check_pc) > 0) {
        $error = "This PC already has a pending reservation!";
    } elseif (mysqli_num_rows($check_active) > 0) {
        $error = "This PC is currently occupied!";
    } else {
        $insert = "INSERT INTO reservations (user_id, id_number, student_name, purpose, lab, pc_number, time_in, date, status, created_at) 
                   VALUES ('$user_id', '$id_number', '$student_name', '$purpose', '$lab', '$pc_number', '$time_in', '$date', 'pending', NOW())";
        
        if (mysqli_query($conn, $insert)) {
            $success = "Reservation submitted successfully! Please wait for admin approval.";
            $selected_pc = '';
        } else {
            $error = "Failed to submit reservation. Please try again.";
        }
    }
}

// Get user's reservations
$reservations = mysqli_query($conn, "SELECT * FROM reservations 
                                     WHERE user_id = $user_id 
                                     ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* PC Grid Styles */
        .lab-selector {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .lab-selector label {
            font-weight: 500;
            color: #2d3748;
        }
        
        .lab-selector select {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            background: white;
        }
        
        .pc-legend {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        
        .legend-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .pc-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
            border: 2px solid transparent;
        }
        
        .pc-item.available {
            background: #f8fafc;
            border-color: #cbd5e0;
        }
        
        .pc-item.available:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #0052cc;
        }
        
        .pc-item.selected {
            border-color: #0052cc;
            background: #e3f2fd;
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.2);
        }
        
        .pc-item.occupied, .pc-item.requested {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .pc-icon {
            font-size: 2rem;
            margin-bottom: 0.25rem;
        }
        
        .pc-icon.available {
            color: #0052cc;
        }
        
        .pc-icon.occupied {
            color: #e53935;
        }
        
        .pc-icon.requested {
            color: #ffd600;
        }
        
        .pc-number {
            font-size: 0.75rem;
            font-weight: 600;
            color: #4a5568;
        }
        
        .pc-status-badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            margin-top: 0.25rem;
        }
        
        .pc-status-badge.available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .pc-status-badge.occupied {
            background: #fee;
            color: #c33;
        }
        
        .pc-status-badge.requested {
            background: #fff3e0;
            color: #ed6c02;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }
        
        .pc-info-text {
            font-size: 0.7rem;
            color: #718096;
            margin-top: 0.25rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .pc-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 0.5rem;
                padding: 1rem;
            }
            
            .pc-icon {
                font-size: 1.5rem;
            }
            
            .pc-number {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>CCS Sit-in Monitoring System</span>
                </div>
            </div>
                <nav class="sidebar-nav">
                    <a href="student_dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                    <a href="student_notifications.php" class="nav-item"><i class="fas fa-bell"></i><span>Notification</span></a>
                    <a href="student_edit_profile.php" class="nav-item"><i class="fas fa-edit"></i><span>Edit Profile</span></a>
                    <a href="student_history.php" class="nav-item"><i class="fas fa-history"></i><span>History</span></a>
                    <a href="student_reservation.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Reservation</span></a>
                    <a href="student_rewards.php" class="nav-item"><i class="fas fa-gift"></i><span>Rewards</span></a>
                    <a href="student_leaderboard.php" class="nav-item"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                    <a href="logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i><span>Log out</span>
                    </a>
                </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <h1>Reservation</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Lab PC Availability Section -->
            <div class="reservation-form-card">
                <div class="card-header">
                    <h2><i class="fas fa-desktop"></i> Lab PC Availability</h2>
                </div>
                <div class="card-body">
                    <!-- Lab Selector -->
                    <div class="lab-selector">
                        <label for="lab_select">Select Lab:</label>
                        <select id="lab_select">
                            <option value="524">Lab 524</option>
                            <option value="525">Lab 525</option>
                            <option value="526">Lab 526</option>
                            <option value="527">Lab 527</option>
                        </select>
                    </div>
                    
                    <!-- Legend -->
                    <div class="pc-legend">
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-desktop" style="color: #0052cc;"></i></div>
                            <span>Available - Click to select</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-desktop" style="color: #e53935;"></i></div>
                            <span>Occupied - Currently in use</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon"><i class="fas fa-desktop" style="color: #ffd600;"></i></div>
                            <span>Requested - Pending approval</span>
                        </div>
                    </div>
                    
                    <!-- PC Grid -->
                    <div id="pcGrid" class="pc-grid">
                        <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading PCs...</div>
                    </div>
                    
                    <!-- Hidden field for selected PC -->
                    <input type="hidden" id="selected_pc" value="">
                </div>
            </div>

            <!-- Reservation Form -->
            <div class="reservation-form-card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-plus"></i> New Reservation</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="reservationForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_number">ID Number:</label>
                                <input type="text" id="id_number" name="id_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['id_number']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="student_name">Student Name:</label>
                                <input type="text" id="student_name" name="student_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="purpose">Purpose:</label>
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
                                <label for="lab">Lab:</label>
                                <select name="lab" id="form_lab" class="form-control" required>
                                    <option value="">Select Lab</option>
                                    <option value="524">524</option>
                                    <option value="525">525</option>
                                    <option value="526">526</option>
                                    <option value="527">527</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pc_number">PC Number:</label>
                                <input type="text" name="pc_number" id="pc_number" class="form-control" readonly required>
                            </div>
                            <div class="form-group">
                                <label for="remaining_session">Remaining Session:</label>
                                <input type="text" id="remaining_session" class="form-control" 
                                       value="<?php echo $user['session_count']; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_in">Time In:</label>
                                <input type="time" id="time_in" name="time_in" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="date">Date:</label>
                                <input type="date" id="date" name="date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="reserve" class="btn-reserve" id="reserveBtn" disabled>
                                <i class="fas fa-calendar-check"></i> Reserve
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- My Reservations Table -->
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-list"></i> My Reservations</h2>
                </div>
                <div class="table-responsive">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>PC</th>
                                <th>Time In</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Reserved On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($reservations) > 0): ?>
                                <?php while($res = mysqli_fetch_assoc($reservations)): ?>
                                <tr>
                                    <td><?php echo $res['id']; ?></td>
                                    <td><?php echo htmlspecialchars($res['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($res['lab']); ?></td>
                                    <td><?php echo htmlspecialchars($res['pc_number']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($res['time_in'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($res['date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $res['status']; ?>">
                                            <?php echo ucfirst($res['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($res['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No reservations found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
        
        // Set minimum date to today
        const dateInput = document.getElementById('date');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }
        
        // Load PC grid based on selected lab
        function loadPCGrid() {
            const lab = document.getElementById('lab_select').value;
            const gridContainer = document.getElementById('pcGrid');
            const formLab = document.getElementById('form_lab');
            formLab.value = lab;
            
            gridContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading PCs...</div>';
            
            fetch('backend/get_pc_status.php?lab=' + encodeURIComponent(lab))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    for (let i = 1; i <= 30; i++) {
                        const pcNumber = `PC-${i.toString().padStart(2, '0')}`;
                        const status = data[pcNumber];
                        let statusClass = '';
                        let statusText = '';
                        let iconColor = '';
                        let onclickAttr = '';
                        let disabledClass = '';
                        
                        if (status.status === 'available') {
                            statusClass = 'available';
                            statusText = 'Available';
                            iconColor = '#0052cc';
                            onclickAttr = `onclick="selectPC('${pcNumber}', '${lab}')"`;
                        } else if (status.status === 'occupied') {
                            statusClass = 'occupied';
                            statusText = 'Occupied';
                            iconColor = '#e53935';
                            disabledClass = 'occupied';
                        } else if (status.status === 'requested') {
                            statusClass = 'requested';
                            statusText = 'Requested';
                            iconColor = '#ffd600';
                            disabledClass = 'requested';
                        }
                        
                        const isSelected = (document.getElementById('selected_pc').value === pcNumber && document.getElementById('form_lab').value === lab);
                        const selectedClass = isSelected ? 'selected' : '';
                        
                        html += `
                            <div class="pc-item ${statusClass} ${selectedClass} ${disabledClass}" ${onclickAttr} data-pc="${pcNumber}">
                                <i class="fas fa-desktop pc-icon ${statusClass}" style="color: ${iconColor};"></i>
                                <span class="pc-number">${pcNumber}</span>
                                <span class="pc-status-badge ${statusClass}">${statusText}</span>
                        `;
                        
                        if (status.status === 'occupied' && status.user) {
                            html += `<div class="pc-info-text" title="${status.user}">${status.user.substring(0, 10)}${status.user.length > 10 ? '...' : ''}</div>`;
                        } else if (status.status === 'requested' && status.user) {
                            html += `<div class="pc-info-text" title="${status.user}">${status.user.substring(0, 10)}${status.user.length > 10 ? '...' : ''}</div>`;
                        }
                        
                        html += `</div>`;
                    }
                    gridContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    gridContainer.innerHTML = '<div class="loading-spinner">Error loading PCs. Please refresh.</div>';
                });
        }
        
        // Select a PC
        function selectPC(pcNumber, lab) {
            // Remove selected class from all PCs
            document.querySelectorAll('.pc-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected class to clicked PC
            const clickedItem = document.querySelector(`.pc-item[data-pc="${pcNumber}"]`);
            if (clickedItem) {
                clickedItem.classList.add('selected');
            }
            
            // Update form fields
            document.getElementById('selected_pc').value = pcNumber;
            document.getElementById('pc_number').value = pcNumber;
            document.getElementById('form_lab').value = lab;
            document.getElementById('lab_select').value = lab;
            document.getElementById('reserveBtn').disabled = false;
        }
        
        // Refresh PC grid when lab changes
        document.getElementById('lab_select').addEventListener('change', function() {
            loadPCGrid();
            document.getElementById('selected_pc').value = '';
            document.getElementById('pc_number').value = '';
            document.getElementById('reserveBtn').disabled = true;
        });
        
        // Initial load
        loadPCGrid();
        
        // Auto-refresh every 30 seconds
        setInterval(loadPCGrid, 30000);
        
        // Sync form lab with dropdown
        document.getElementById('form_lab').addEventListener('change', function() {
            document.getElementById('lab_select').value = this.value;
            loadPCGrid();
        });
    </script>
</body>
</html>