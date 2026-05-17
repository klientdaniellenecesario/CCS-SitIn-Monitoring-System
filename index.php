<?php
session_start();
require_once 'config/database.php';

// Only redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin/admin_dashboard.php'); 
        exit();
    } else {
        header('Location: student_dashboard.php'); 
        exit();
    }
}

// Fetch Top 3 by Hours
$top_hours = [];
$h = mysqli_query($conn,
  "SELECT u.first_name, u.last_name, u.course,
          ROUND(COALESCE(SUM(s.duration_minutes),0)/60,1) AS total_hours,
          COUNT(s.id) AS total_sessions
   FROM users u
   LEFT JOIN sit_in_sessions s ON u.id = s.user_id AND s.status='completed'
   WHERE u.role='student'
   GROUP BY u.id ORDER BY total_hours DESC LIMIT 3");
while ($r = mysqli_fetch_assoc($h)) $top_hours[] = $r;

// Fetch Top 3 by Points
$top_points = [];
$p = mysqli_query($conn,
  "SELECT first_name, last_name, course, total_reward_points AS score
   FROM users WHERE role='student' ORDER BY score DESC LIMIT 3");
while ($r = mysqli_fetch_assoc($p)) $top_points[] = $r;

// Fetch Top 3 by Tasks
$top_tasks = [];
$t = mysqli_query($conn,
  "SELECT u.first_name, u.last_name, u.course, COUNT(st.id) AS tasks_done
   FROM users u
   LEFT JOIN student_tasks st ON u.id = st.user_id AND st.status='completed'
   WHERE u.role='student'
   GROUP BY u.id ORDER BY tasks_done DESC LIMIT 3");
while ($r = mysqli_fetch_assoc($t)) $top_tasks[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-in Monitoring System - UC Main Campus</title>
    <link rel="stylesheet" href="frontend/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Hero Section (unchanged) */
        .hero {
            background: linear-gradient(135deg, #0041b0, #0066ff);
            padding: 6rem 2rem;
            text-align: center;
            color: white;
            margin-top: 70px;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-hero-primary, .btn-hero-secondary {
            padding: 0.8rem 2rem;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-hero-primary {
            background: #ffd600;
            color: #0041b0;
        }
        .btn-hero-primary:hover {
            background: #ffea8a;
            transform: translateY(-2px);
        }
        .btn-hero-secondary {
            border: 2px solid white;
            color: white;
            background: transparent;
        }
        .btn-hero-secondary:hover {
            background: white;
            color: #0041b0;
        }
        
        /* ============================================
           LEADERBOARD SECTION - ORIGINAL SYSTEM COLORS
           ============================================ */
        .hall-of-fame {
            background: linear-gradient(135deg, #0041b0 0%, #0066ff 60%, #1a7fff 100%);
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .leader-label {
            color: #f5c000;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
            text-shadow: none;
            margin-bottom: 0.5rem;
        }
        
        .leader-title {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            color: #ffffff;
            text-shadow: none;
            letter-spacing: -0.5px;
            margin: 0.5rem 0;
        }
        
        .leader-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            color: rgba(255,255,255,0.9);
        }
        
        /* Tab Buttons */
        .leader-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }
        
        .leader-tab-btn {
            padding: 10px 24px;
            border-radius: 50px;
            border: 2px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.10);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.25s ease;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
        }
        
        .leader-tab-btn:hover {
            background: rgba(255,255,255,0.20);
            border-color: rgba(255,255,255,0.5);
        }
        
        .leader-tab-btn.active {
            background: #f5c000;
            color: #0041b0;
            border-color: #f5c000;
            box-shadow: none;
        }
        
        /* Podium Row - Proper Layout (2nd, 1st, 3rd order) */
        .podium-row {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 1.25rem;
            flex-wrap: nowrap;
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            padding-bottom: 1rem;
        }
        
        /* Card Base Styles */
        .podium-card {
            flex: 0 0 auto;
            width: 240px;
            border-radius: 18px;
            padding: 2rem 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.2s ease;
            backdrop-filter: blur(8px);
        }
        
        .podium-card:hover {
            transform: translateY(-5px);
        }
        
        /* 1st place - Tallest, Center */
        .rank-1 {
            min-height: 340px;
            background: rgba(255,255,255,0.15);
            border: 2px solid #f5c000;
        }
        
        /* 2nd place - Medium height */
        .rank-2 {
            min-height: 290px;
            background: rgba(255,255,255,0.10);
            border: 2px solid rgba(255,255,255,0.35);
        }
        
        /* 3rd place - Shortest */
        .rank-3 {
            min-height: 265px;
            background: rgba(255,255,255,0.08);
            border: 2px solid rgba(255,255,255,0.25);
        }
        
        /* Rank Badge Circles */
        .rank-badge {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 900;
            margin: 0 auto 1rem;
        }
        
        .rank-badge-1 {
            background: linear-gradient(135deg, #f5c000, #ff9900);
            color: #fff;
        }
        
        .rank-badge-2 {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #2d3748;
        }
        
        .rank-badge-3 {
            background: linear-gradient(135deg, #cd7f32, #e8a055);
            color: #fff;
        }
        
        /* Rank Label */
        .rank-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        
        .rank-label.gold { color: #f5c000; }
        .rank-label.silver { color: #c0c0c0; }
        .rank-label.bronze { color: #cd7f32; }
        
        /* Student Info */
        .student-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .student-course {
            font-size: 0.7rem;
            margin-bottom: 0.75rem;
        }
        
        .rank-1 .student-course { color: #f5c000; }
        .rank-2 .student-course { color: #c0c0c0; }
        .rank-3 .student-course { color: #cd7f32; }
        
        /* Score Display */
        .score-number {
            font-size: 2.5rem;
            font-weight: 900;
            line-height: 1;
            margin-top: 0.5rem;
            color: #f5c000;
        }
        
        .score-label {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .placeholder-text {
            color: rgba(255,255,255,0.5);
            padding: 1rem 0;
        }
        
        /* ============================================
           FOOTER - WHITE BACKGROUND
           ============================================ */
        footer {
            background: #ffffff;
            color: #0041b0;
            text-align: center;
            padding: 1.5rem;
            font-size: 0.82rem;
            border-top: 3px solid #f5c000;
        }
        
        /* Mobile Responsive */
        @media (max-width: 700px) {
            .hero h1 { font-size: 2rem; }
            .podium-row {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            .podium-card {
                width: 100%;
                max-width: 280px;
                min-height: unset;
            }
            .rank-1, .rank-2, .rank-3 {
                min-height: auto;
            }
            .podium-card:hover {
                transform: translateY(-3px);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <img src="images/CCS_LOGO.png" alt="CCS Logo" style="height:40px;width:auto; margin-right:10px;">
                <img src="images/UC_LOGO.png" alt="UC Logo" style="height:40px;width:auto;">
                <span>CCS Sit-in</span>
            </div>
            <div class="nav-links">
                <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="community.php"><i class="fas fa-users"></i> Community</a>
                <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>College of Computer Studies<br>Sit-in Monitoring System</h1>
        <p>Track your laboratory sessions, compete on the leaderboard, and earn rewards.</p>
        <div class="hero-buttons">
            <a href="login.php" class="btn-hero-primary">Login to Your Account</a>
            <a href="register.php" class="btn-hero-secondary">Register Now</a>
        </div>
    </section>

    <!-- Hall of Fame Section -->
    <section class="hall-of-fame">
        <div class="leader-label">HALL OF FAME</div>
        <h2 class="leader-title">Top Students This Session</h2>
        <p class="leader-subtitle">Rankings from the CCS computer laboratories — UC Main Campus</p>

        <!-- Tab Buttons -->
        <div class="leader-tabs">
            <button class="leader-tab-btn" id="btn-hours" onclick="showLeaderTab('hours')">🕐 MOST HOURS</button>
            <button class="leader-tab-btn" id="btn-points" onclick="showLeaderTab('points')">⭐ MOST POINTS</button>
            <button class="leader-tab-btn" id="btn-tasks" onclick="showLeaderTab('tasks')">✅ MOST TASKS</button>
        </div>

        <!-- Tab: Most Hours -->
        <div id="tab-hours" class="leader-tab-content">
            <div class="podium-row">
                <?php 
                // Prepare data in order: 2nd, 1st, 3rd for proper podium layout
                $hours_data = [];
                for ($i = 0; $i < 3; $i++) {
                    $hours_data[$i] = isset($top_hours[$i]) ? $top_hours[$i] : null;
                }
                // Reorder: index 1 (2nd place), index 0 (1st place), index 2 (3rd place)
                $ordered_hours = [
                    1 => $hours_data[1] ?? null,  // 2nd place - LEFT
                    0 => $hours_data[0] ?? null,  // 1st place - CENTER
                    2 => $hours_data[2] ?? null   // 3rd place - RIGHT
                ];
                
                $rank_map = [1 => 2, 2 => 1, 3 => 3]; // Display rank numbers
                $rank_counter = 1;
                foreach ($ordered_hours as $data_rank => $student):
                    $display_rank = $rank_map[$rank_counter];
                    $rank_label = ($display_rank == 1) ? 'gold' : (($display_rank == 2) ? 'silver' : 'bronze');
                    $badge_class = 'rank-badge-' . $display_rank;
                    $badge_icon = ($display_rank == 1) ? '🏆' : (($display_rank == 2) ? '2' : '3');
                ?>
                <div class="podium-card rank-<?php echo $display_rank; ?>">
                    <div class="rank-badge <?php echo $badge_class; ?>"><?php echo $badge_icon; ?></div>
                    <div class="rank-label <?php echo $rank_label; ?>"><?php echo $display_rank . (($display_rank == 1) ? 'ST' : (($display_rank == 2) ? 'ND' : 'RD')) . ' PLACE'; ?></div>
                    <?php if ($student): ?>
                        <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                        <div class="student-course"><?php echo htmlspecialchars($student['course']); ?></div>
                        <div class="score-number"><?php echo $student['total_hours']; ?></div>
                        <div class="score-label">TOTAL HOURS</div>
                    <?php else: ?>
                        <div class="placeholder-text">No entry yet</div>
                        <div class="score-number">—</div>
                        <div class="score-label">TOTAL HOURS</div>
                    <?php endif; ?>
                </div>
                <?php 
                    $rank_counter++;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Tab: Most Points -->
        <div id="tab-points" class="leader-tab-content" style="display: none;">
            <div class="podium-row">
                <?php 
                $points_data = [];
                for ($i = 0; $i < 3; $i++) {
                    $points_data[$i] = isset($top_points[$i]) ? $top_points[$i] : null;
                }
                $ordered_points = [
                    1 => $points_data[1] ?? null,
                    0 => $points_data[0] ?? null,
                    2 => $points_data[2] ?? null
                ];
                
                $rank_counter = 1;
                foreach ($ordered_points as $data_rank => $student):
                    $display_rank = $rank_map[$rank_counter];
                    $rank_label = ($display_rank == 1) ? 'gold' : (($display_rank == 2) ? 'silver' : 'bronze');
                    $badge_class = 'rank-badge-' . $display_rank;
                    $badge_icon = ($display_rank == 1) ? '🏆' : (($display_rank == 2) ? '2' : '3');
                ?>
                <div class="podium-card rank-<?php echo $display_rank; ?>">
                    <div class="rank-badge <?php echo $badge_class; ?>"><?php echo $badge_icon; ?></div>
                    <div class="rank-label <?php echo $rank_label; ?>"><?php echo $display_rank . (($display_rank == 1) ? 'ST' : (($display_rank == 2) ? 'ND' : 'RD')) . ' PLACE'; ?></div>
                    <?php if ($student): ?>
                        <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                        <div class="student-course"><?php echo htmlspecialchars($student['course']); ?></div>
                        <div class="score-number"><?php echo $student['score']; ?></div>
                        <div class="score-label">REWARD POINTS</div>
                    <?php else: ?>
                        <div class="placeholder-text">No entry yet</div>
                        <div class="score-number">—</div>
                        <div class="score-label">REWARD POINTS</div>
                    <?php endif; ?>
                </div>
                <?php 
                    $rank_counter++;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Tab: Most Tasks -->
        <div id="tab-tasks" class="leader-tab-content" style="display: none;">
            <div class="podium-row">
                <?php 
                $tasks_data = [];
                for ($i = 0; $i < 3; $i++) {
                    $tasks_data[$i] = isset($top_tasks[$i]) ? $top_tasks[$i] : null;
                }
                $ordered_tasks = [
                    1 => $tasks_data[1] ?? null,
                    0 => $tasks_data[0] ?? null,
                    2 => $tasks_data[2] ?? null
                ];
                
                $rank_counter = 1;
                foreach ($ordered_tasks as $data_rank => $student):
                    $display_rank = $rank_map[$rank_counter];
                    $rank_label = ($display_rank == 1) ? 'gold' : (($display_rank == 2) ? 'silver' : 'bronze');
                    $badge_class = 'rank-badge-' . $display_rank;
                    $badge_icon = ($display_rank == 1) ? '🏆' : (($display_rank == 2) ? '2' : '3');
                ?>
                <div class="podium-card rank-<?php echo $display_rank; ?>">
                    <div class="rank-badge <?php echo $badge_class; ?>"><?php echo $badge_icon; ?></div>
                    <div class="rank-label <?php echo $rank_label; ?>"><?php echo $display_rank . (($display_rank == 1) ? 'ST' : (($display_rank == 2) ? 'ND' : 'RD')) . ' PLACE'; ?></div>
                    <?php if ($student): ?>
                        <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                        <div class="student-course"><?php echo htmlspecialchars($student['course']); ?></div>
                        <div class="score-number"><?php echo $student['tasks_done']; ?></div>
                        <div class="score-label">TASKS COMPLETED</div>
                    <?php else: ?>
                        <div class="placeholder-text">No entry yet</div>
                        <div class="score-number">—</div>
                        <div class="score-label">TASKS COMPLETED</div>
                    <?php endif; ?>
                </div>
                <?php 
                    $rank_counter++;
                endforeach; 
                ?>
            </div>
        </div>

    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date('Y'); ?> College of Computer Studies — University of Cebu Main Campus</p>
    </footer>

    <script>
        function showLeaderTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.leader-tab-content').forEach(el => el.style.display = 'none');
            
            // Reset all tab buttons to inactive
            document.querySelectorAll('.leader-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById('tab-' + tabName).style.display = 'block';
            
            // Activate clicked button
            document.getElementById('btn-' + tabName).classList.add('active');
        }
        
        // Set default active tab on page load
        document.addEventListener('DOMContentLoaded', () => {
            showLeaderTab('hours');
        });
        
        // Mobile menu toggle
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>