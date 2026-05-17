<?php
session_start();
require_once 'config/database.php';

// No redirect - public page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="frontend/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .community-hero {
            background: linear-gradient(135deg, #0041b0, #0066ff);
            padding: 5rem 2rem;
            text-align: center;
            color: white;
            margin-top: 70px;
        }
        .community-badge {
            font-size: 0.8rem;
            letter-spacing: 2px;
            color: #ffd600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .community-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .community-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        .community-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .community-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #ffd600;
        }
        .community-card h2 {
            font-size: 1.5rem;
            color: #0041b0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .community-card p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        .social-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            background: #1877f2;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            background: #0d5fb9;
            transform: translateY(-2px);
        }
        
        /* FOOTER - FIXED: White background, dark blue text, yellow top border */
        footer {
            background: #ffffff;
            color: #0041b0;
            text-align: center;
            padding: 1.5rem;
            font-size: 0.82rem;
            border-top: 3px solid #f5c000;
        }
        
        @media (max-width: 768px) {
            .community-hero h1 { font-size: 1.8rem; }
            .community-card h2 { font-size: 1.2rem; }
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
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="community.php" class="active"><i class="fas fa-users"></i> Community</a>
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
    <div class="community-hero">
        <div class="community-badge">Community</div>
        <h1>CCS Community</h1>
        <p class="community-subtitle">Connecting students, faculty, and technology at UC Main Campus.</p>
    </div>

    <div class="community-container">
        <!-- Section 1 -->
        <div class="community-card">
            <h2><i class="fas fa-users"></i> Student Life at CCS</h2>
            <p>CCS students are encouraged to join technology-related organizations, participate in hackathons, coding competitions, campus tech showcases, and research events. These activities build practical skills and professional networks that complement classroom learning.</p>
        </div>

        <!-- Section 2 -->
        <div class="community-card">
            <h2><i class="fas fa-desktop"></i> Computer Laboratories</h2>
            <p>Labs 524, 525, 526, and 527 are open to all enrolled CCS students for academic work, programming practice, and sit-in sessions during designated hours. Students must follow proper lab guidelines and use this system to log their sessions.</p>
        </div>

        <!-- Section 3 -->
        <div class="community-card">
            <h2><i class="fas fa-chart-line"></i> Sit-in Monitoring System</h2>
            <p>This system was built to manage and monitor student use of the CCS computer laboratories. It tracks sit-in sessions, lab availability, student participation, leaderboard rankings, and rewards to encourage consistent academic engagement.</p>
        </div>

        <!-- Section 4 -->
        <div class="community-card">
            <h2><i class="fab fa-facebook"></i> Connect With Us</h2>
            <p>Follow the official Facebook page of CCS UC Main for announcements, events, and updates.</p>
            <a href="https://facebook.com/ucmainccs" target="_blank" class="social-link">
                <i class="fab fa-facebook"></i> College of Computer Studies – UC Main
            </a>
        </div>
    </div>

    <!-- Footer - Fixed: White background, dark blue text -->
    <footer>
        <p>© <?php echo date('Y'); ?> College of Computer Studies — University of Cebu Main Campus</p>
    </footer>

    <script>
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>