<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin')) {
    header('Location: ../login.php');
    exit();
}

// Get admin name
$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';

// Handle add announcement
if (isset($_POST['add_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $author = $admin_name;
    
    $insert = "INSERT INTO announcements (title, content, author, created_at) VALUES ('$title', '$content', '$author', NOW())";
    if (mysqli_query($conn, $insert)) {
        $success = "Announcement posted successfully!";
        
        // Create notifications for all students
        $students = mysqli_query($conn, "SELECT id FROM users WHERE role = 'student' OR role IS NULL");
        while($student = mysqli_fetch_assoc($students)) {
            $notif_message = "📢 New Announcement: " . $title . "\n\n" . $content;
            mysqli_query($conn, "INSERT INTO notifications (user_id, message, type, created_at) 
                                 VALUES ('{$student['id']}', '$notif_message', 'announcement', NOW())");
        }
    } else {
        $error = "Failed to post announcement.";
    }
}

// Handle edit announcement
if (isset($_POST['edit_announcement'])) {
    $id = intval($_POST['announcement_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    
    $update = "UPDATE announcements SET title = '$title', content = '$content', updated_at = NOW() WHERE id = $id";
    if (mysqli_query($conn, $update)) {
        $success = "Announcement updated successfully!";
    } else {
        $error = "Failed to update announcement.";
    }
}

// Handle delete announcement
if (isset($_POST['delete_announcement'])) {
    $id = intval($_POST['announcement_id']);
    mysqli_query($conn, "DELETE FROM announcements WHERE id = $id");
    $success = "Announcement deleted successfully!";
}

// Get all announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Admin Panel</title>
    <link rel="stylesheet" href="../frontend/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .announcement-form-card, .announcement-list-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #ffd600;
        }
        
        .card-header h2 {
            font-size: 1rem;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-header h2 i {
            color: #ffd600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d3748;
            font-size: 0.85rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ffd600;
            box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
        }
        
        .form-buttons {
            margin-top: 1rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 82, 204, 0.3);
        }
        
        .announcement-item {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 0.5rem;
        }
        
        .announcement-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .announcement-author {
            font-weight: 600;
            color: #0052cc;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .announcement-date {
            font-size: 0.7rem;
            color: #a0aec0;
        }
        
        .announcement-title {
            font-weight: 600;
            font-size: 1rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .announcement-content {
            color: #4a5568;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        .announcement-actions {
            margin-top: 0.75rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-edit-announcement, .btn-delete-announcement {
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.7rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-edit-announcement {
            background: #fff3e0;
            color: #ed6c02;
        }
        
        .btn-edit-announcement:hover {
            background: #ed6c02;
            color: white;
        }
        
        .btn-delete-announcement {
            background: #fee;
            color: #c33;
        }
        
        .btn-delete-announcement:hover {
            background: #c33;
            color: white;
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
            max-width: 500px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #ffd600;
        }
        
        .modal-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn-close {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-close:hover {
            background: #cbd5e0;
        }
        
        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            color: #718096;
        }
        
        .close-modal:hover {
            color: #c33;
        }
        
        .no-announcements {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }
        
        .no-announcements i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>Announcements</h1>
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

            <!-- Add Announcement Form -->
            <div class="announcement-form-card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Create New Announcement</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="Enter announcement title" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea name="content" id="content" class="form-control" rows="4" placeholder="Enter announcement content" required></textarea>
                        </div>
                        <div class="form-buttons">
                            <button type="submit" name="add_announcement" class="btn-submit">
                                <i class="fas fa-paper-plane"></i> Post Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="announcement-list-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> All Announcements</h2>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($announcements) > 0): ?>
                        <?php while($ann = mysqli_fetch_assoc($announcements)): ?>
                            <div class="announcement-item">
                                <div class="announcement-header">
                                    <span class="announcement-author">
                                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($ann['author']); ?>
                                    </span>
                                    <span class="announcement-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y h:i A', strtotime($ann['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="announcement-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                                <div class="announcement-content"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></div>
                                <div class="announcement-actions">
                                    <button class="btn-edit-announcement" onclick="openEditModal(<?php echo $ann['id']; ?>, '<?php echo addslashes($ann['title']); ?>', '<?php echo addslashes($ann['content']); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                        <button type="submit" name="delete_announcement" class="btn-delete-announcement" onclick="return confirm('Delete this announcement?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-announcements">
                            <i class="fas fa-bullhorn"></i>
                            <p>No announcements yet. Create your first announcement above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Announcement Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Announcement</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_content">Content</label>
                        <textarea name="content" id="edit_content" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-close" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_announcement" class="btn-submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, title, content) {
            document.getElementById('edit_announcement_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>