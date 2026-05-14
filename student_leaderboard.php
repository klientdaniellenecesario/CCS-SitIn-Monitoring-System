<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    header('Location: admin/admin_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Category 1 - Most Hours (Total duration_minutes summed across ALL completed sessions)
$hours_result = mysqli_query($conn,
  "SELECT u.id, u.first_name, u.last_name, u.course, u.profile_picture,
          ROUND(COALESCE(SUM(s.duration_minutes), 0) / 60, 1) AS score
   FROM users u
   LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status = 'completed'
   WHERE u.role = 'student'
   GROUP BY u.id
   ORDER BY score DESC
   LIMIT 20"
);
$hours_data = [];
while ($row = mysqli_fetch_assoc($hours_result)) { $hours_data[] = $row; }

// Category 2 - Most Points
$points_result = mysqli_query($conn,
  "SELECT id, first_name, last_name, course, profile_picture,
          total_reward_points AS score
   FROM users
   WHERE role = 'student'
   ORDER BY score DESC
   LIMIT 20"
);
$points_data = [];
while ($row = mysqli_fetch_assoc($points_result)) { $points_data[] = $row; }

// Category 3 - Most Tasks Completed
$tasks_result = mysqli_query($conn,
  "SELECT u.id, u.first_name, u.last_name, u.course, u.profile_picture,
          COUNT(st.id) AS score
   FROM users u
   LEFT JOIN student_tasks st ON u.id = st.user_id AND st.status = 'completed'
   WHERE u.role = 'student'
   GROUP BY u.id
   ORDER BY score DESC
   LIMIT 20"
);
$tasks_data = [];
while ($row = mysqli_fetch_assoc($tasks_result)) { $tasks_data[] = $row; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - CCS Sit-in System</title>
    <link rel="stylesheet" href="frontend/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #718096;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }
        .tab-btn.active {
            background: #ffd600;
            color: #1a202c;
            border-color: #ffd600;
            box-shadow: 0 4px 12px rgba(255, 214, 0, 0.4);
        }
        .tab-btn:hover:not(.active) {
            border-color: #0052cc;
            color: #0052cc;
        }

        .podium-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border-radius: 20px;
            padding: 2.5rem 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .podium-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.4) 0%, transparent 100%),
                radial-gradient(1px 1px at 80% 10%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 50% 60%, rgba(255,255,255,0.2) 0%, transparent 100%),
                radial-gradient(1px 1px at 10% 80%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 90% 70%, rgba(255,255,255,0.2) 0%, transparent 100%);
            pointer-events: none;
        }

        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .podium-slot {
            text-align: center;
            padding-bottom: 0;
            flex: 1;
            max-width: 160px;
        }

        .podium-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 0.5rem;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .podium-slot.rank-1 .podium-avatar {
            width: 85px;
            height: 85px;
            border: 4px solid #ffd600;
            box-shadow: 0 0 0 4px rgba(255,214,0,0.3), 0 0 25px rgba(255,214,0,0.5);
        }

        .pedestal-1 {
            background: linear-gradient(180deg, #ffd600, #e6c200);
            height: 120px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a202c;
            margin-top: 0.5rem;
        }
        .pedestal-2 {
            background: linear-gradient(180deg, #c0c0c0, #a8a8a8);
            height: 90px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: #1a202c;
            margin-top: 0.5rem;
        }
        .pedestal-3 {
            background: linear-gradient(180deg, #cd7f32, #b8722d);
            height: 70px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-top: 0.5rem;
        }

        @keyframes crownPulse {
            0%, 100% { text-shadow: 0 0 10px #ffd600, 0 0 20px #ffd600; transform: scale(1) rotate(-5deg); }
            50% { text-shadow: 0 0 20px #ffd600, 0 0 40px rgba(255,214,0,0.8); transform: scale(1.2) rotate(5deg); }
        }
        .crown-icon {
            font-size: 1.8rem;
            color: #ffd600;
            animation: crownPulse 2s ease-in-out infinite;
            display: block;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .podium-name {
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            text-align: center;
            max-width: 120px;
            margin: 0 auto;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .podium-course {
            color: rgba(255,255,255,0.55);
            font-size: 0.7rem;
            text-align: center;
        }
        .podium-score {
            background: rgba(255,214,0,0.2);
            border: 1px solid rgba(255,214,0,0.4);
            color: #ffd600;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            text-align: center;
            margin: 0.4rem auto 0.8rem;
            display: inline-block;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f0f4ff;
            color: #0052cc;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .my-row {
            background: rgba(255, 214, 0, 0.08) !important;
            border-left: 4px solid #ffd600;
        }
        .my-tag {
            background: #ffd600;
            color: #1a202c;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            margin-left: 0.4rem;
            vertical-align: middle;
        }
        .table-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        #leaderboard-content {
            transition: opacity 0.25s ease;
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
                <a href="student_leaderboard.php" class="nav-item active"><i class="fas fa-trophy"></i><span>Leaderboard</span></a>
                <a href="logout.php" class="nav-item logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i><span>Log out</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <h1>Leaderboard</h1>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <!-- Tab Bar -->
            <div class="tab-bar">
                <button class="tab-btn active" data-tab="hours" onclick="switchTab('hours')">🕐 Most Hours</button>
                <button class="tab-btn" data-tab="points" onclick="switchTab('points')">⭐ Most Points</button>
                <button class="tab-btn" data-tab="tasks" onclick="switchTab('tasks')">✅ Most Tasks</button>
            </div>

            <!-- Leaderboard Content -->
            <div id="leaderboard-content">
                <!-- Podium Section -->
                <div class="podium-section">
                    <div id="podium-container" class="podium-container"></div>
                </div>

                <!-- Rankings Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h2><i class="fas fa-list-ol"></i> Full Rankings</h2>
                    </div>
                    <div class="table-responsive">
                        <table id="rankings-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Score</th>
                                    <th>Badge</th>
                                </tr>
                            </thead>
                            <tbody id="rankings-tbody"></tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <p id="rankings-count"></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const leaderboardData = {
            hours: <?php echo json_encode($hours_data); ?>,
            points: <?php echo json_encode($points_data); ?>,
            tasks:  <?php echo json_encode($tasks_data); ?>
        };
        const scoreLabels = {
            hours: 'hrs',
            points: 'pts',
            tasks:  'tasks'
        };
        const currentUserId = <?php echo intval($user_id); ?>;

        let activeTab = 'hours';

        function getAvatarHTML(pic, size, forPodium) {
            const defaultPic = 'default-avatar.png';
            const prefix = 'uploads/';
            if (!pic || pic === defaultPic || pic === '') {
                const color = forPodium ? 'rgba(255,255,255,0.5)' : '#0052cc';
                return `<i class="fas fa-user-circle" style="font-size:${size}px;color:${color}"></i>`;
            }
            const cls = forPodium ? 'podium-avatar' : 'table-avatar';
            return `<img src="${prefix}${pic}" class="${cls}" style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover;" onerror="this.outerHTML='<i class=\'fas fa-user-circle\' style=\'font-size:${size}px;color:#0052cc\'></i>'">`;
        }

        function renderPodium(data, label) {
            const podium = document.getElementById('podium-container');
            const avatarSizes = { 1: 85, 2: 70, 3: 65 };

            const first = data[0] || null;
            const second = data[1] || null;
            const third = data[2] || null;

            const secondHtml = second ? `
                <div class="podium-slot rank-2" style="text-align:center;padding-bottom:0">
                    <i class="fas fa-medal" style="color:#c0c0c0;font-size:1.5rem;display:block;text-align:center;margin-bottom:0.5rem"></i>
                    ${getAvatarHTML(second.profile_picture, avatarSizes[2], true)}
                    <div class="podium-name">${second.first_name} ${second.last_name}</div>
                    <div class="podium-course">${second.course}</div>
                    <span class="podium-score">${second.score} ${label}</span>
                    <div class="pedestal-2">2</div>
                </div>
            ` : `<div class="podium-slot rank-2 ghost" style="text-align:center;padding-bottom:0">
                <i class="fas fa-user-circle" style="font-size:3rem;color:rgba(255,255,255,0.2)"></i>
                <div class="podium-name" style="color:rgba(255,255,255,0.3)">---</div>
                <div class="pedestal-2">?</div>
            </div>`;

            const firstHtml = first ? `
                <div class="podium-slot rank-1" style="text-align:center;padding-bottom:0">
                    <i class="fas fa-crown crown-icon"></i>
                    ${getAvatarHTML(first.profile_picture, avatarSizes[1], true)}
                    <div class="podium-name">${first.first_name} ${first.last_name}</div>
                    <div class="podium-course">${first.course}</div>
                    <span class="podium-score">${first.score} ${label}</span>
                    <div class="pedestal-1">1</div>
                </div>
            ` : `<div class="podium-slot rank-1 ghost" style="text-align:center;padding-bottom:0">
                <i class="fas fa-user-circle" style="font-size:3rem;color:rgba(255,255,255,0.2)"></i>
                <div class="podium-name" style="color:rgba(255,255,255,0.3)">---</div>
                <div class="pedestal-1">?</div>
            </div>`;

            const thirdHtml = third ? `
                <div class="podium-slot rank-3" style="text-align:center;padding-bottom:0">
                    <i class="fas fa-medal" style="color:#cd7f32;font-size:1.3rem;display:block;text-align:center;margin-bottom:0.5rem"></i>
                    ${getAvatarHTML(third.profile_picture, avatarSizes[3], true)}
                    <div class="podium-name">${third.first_name} ${third.last_name}</div>
                    <div class="podium-course">${third.course}</div>
                    <span class="podium-score">${third.score} ${label}</span>
                    <div class="pedestal-3">3</div>
                </div>
            ` : `<div class="podium-slot rank-3 ghost" style="text-align:center;padding-bottom:0">
                <i class="fas fa-user-circle" style="font-size:3rem;color:rgba(255,255,255,0.2)"></i>
                <div class="podium-name" style="color:rgba(255,255,255,0.3)">---</div>
                <div class="pedestal-3">?</div>
            </div>`;

            podium.innerHTML = secondHtml + firstHtml + thirdHtml;
        }

        function renderTable(data, label) {
            const tbody = document.getElementById('rankings-tbody');
            const countEl = document.getElementById('rankings-count');
            const tableData = data.slice(3);

            if (tableData.length === 0) {
                tbody.innerHTML = `</table><td colspan="5" style="text-align:center;padding:2rem;color:#718096;">No more rankings to show. 🏆</td></tr>`;
                countEl.textContent = '';
                return;
            }

            tbody.innerHTML = tableData.map((d, i) => {
                const rank = i + 4;
                const isMe = d.id == currentUserId;
                const name = d.first_name + ' ' + d.last_name;
                const avatar = getAvatarHTML(d.profile_picture, 32, false);
                let badge = '';
                if (rank <= 7) {
                    badge = `<span style="background:#fff0f0;color:#e53935;border:1px solid #ffcdd2;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.75rem;font-weight:700;">🔥 Elite</span>`;
                } else if (rank <= 12) {
                    badge = `<span style="background:#fff8e1;color:#f57c00;border:1px solid #ffe082;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.75rem;font-weight:700;">⚡ Rising</span>`;
                } else {
                    badge = `<span style="background:#f1f8e9;color:#388e3c;border:1px solid #c8e6c9;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.75rem;font-weight:700;">🌱 Active</span>`;
                }
                return `<tr class="${isMe ? 'my-row' : ''}">
                    <td><span class="rank-badge">${rank}</span></td>
                    <td style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;">
                        ${avatar}
                        <span>${name}${isMe ? '<span class="my-tag">You</span>' : ''}</span>
                    </td>
                    <td>${d.course}</td>
                    <td><strong>${d.score}</strong> <span style="color:#718096;font-size:0.8rem">${label}</span></td>
                    <td>${badge}</td>
                </tr>`;
            }).join('');

            countEl.textContent = `Showing ${tableData.length} more student${tableData.length !== 1 ? 's' : ''} (ranks 4–${data.length})`;
        }

        function renderLeaderboard(data, label) {
            renderPodium(data, label);
            renderTable(data, label);
        }

        function switchTab(tab) {
            activeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
            });
            const content = document.getElementById('leaderboard-content');
            content.style.opacity = '0';
            setTimeout(() => {
                renderLeaderboard(leaderboardData[tab], scoreLabels[tab]);
                content.style.opacity = '1';
            }, 250);
        }

        // Initialize on page load
        renderLeaderboard(leaderboardData['hours'], scoreLabels['hours']);
    </script>
</body>
</html>