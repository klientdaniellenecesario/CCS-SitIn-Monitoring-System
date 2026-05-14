<?php
session_start();
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
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 82, 204, 0.1);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #0052cc;
        }
        .logo i { font-size: 1.8rem; color: #ffd600; }
        .nav-links { display: flex; gap: 2rem; }
        .nav-links a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-links a:hover, .nav-links a.active { color: #0052cc; }
        .mobile-menu { display: none; font-size: 1.5rem; cursor: pointer; }
        
        .page-header {
            background: linear-gradient(135deg, #0052cc, #0066ff);
            padding: 8rem 2rem 4rem;
            text-align: center;
            color: white;
        }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .page-header p { opacity: 0.9; font-size: 1rem; }
        
        .content-section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .content-card h2 {
            font-size: 1.5rem;
            color: #1a202c;
            margin-bottom: 1rem;
            border-left: 4px solid #ffd600;
            padding-left: 1rem;
        }
        .content-card h3 {
            font-size: 1.1rem;
            color: #0052cc;
            margin: 1rem 0 0.5rem;
        }
        .content-card p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .icon-grid {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .icon-item {
            text-align: center;
            flex: 1;
            min-width: 100px;
        }
        .icon-item i {
            font-size: 2rem;
            color: #ffd600;
            margin-bottom: 0.5rem;
        }
        .icon-item h4 { font-size: 0.9rem; color: #1a202c; margin-bottom: 0.25rem; }
        .icon-item p { font-size: 0.75rem; color: #718096; }
        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #0052cc, #0066ff);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.3s ease;
        }
        .social-link:hover { transform: translateY(-2px); }
        
        @media (max-width: 768px) {
            .nav-container { padding: 1rem; }
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            .nav-links.show { display: flex; }
            .mobile-menu { display: block; }
            .page-header { padding: 6rem 1rem 3rem; }
            .page-header h1 { font-size: 1.8rem; }
            .content-section { padding: 2rem 1rem; }
            .icon-grid { gap: 1rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-chalkboard-user"></i>
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

    <div class="page-header">
        <h1>CCS Community</h1>
        <p>UC Main Campus — College of Computer Studies</p>
    </div>

    <div class="content-section">
        <div class="content-card">
            <h2>Vibrant Academic Community</h2>
            <p>The College of Computer Studies at UC Main Campus fosters a vibrant academic community where students, faculty, and staff collaborate, innovate, and grow together.</p>
        </div>

        <div class="content-card">
            <h2>Student Organizations & Activities</h2>
            <p>CCS students are encouraged to join technology-related organizations, participate in hackathons, coding competitions, research events, and campus tech showcases. These activities build practical skills, confidence, and professional networks that complement classroom learning.</p>
            <div class="icon-grid">
                <div class="icon-item">
                    <i class="fas fa-code"></i>
                    <h4>Hackathons</h4>
                    <p>24-hour coding challenges</p>
                </div>
                <div class="icon-item">
                    <i class="fas fa-trophy"></i>
                    <h4>Competitions</h4>
                    <p>Regional & national tech contests</p>
                </div>
                <div class="icon-item">
                    <i class="fas fa-users"></i>
                    <h4>Tech Clubs</h4>
                    <p>Programming & IT organizations</p>
                </div>
                <div class="icon-item">
                    <i class="fas fa-chalkboard-user"></i>
                    <h4>Workshops</h4>
                    <p>Industry-led training sessions</p>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Computer Laboratories</h2>
            <p>The CCS computer labs (<strong>Rooms 524, 525, 526, and 527</strong>) are open to all enrolled CCS students for academic work, programming practice, research, and sit-in sessions. Students may use these labs during designated hours and must follow proper lab use guidelines.</p>
        </div>

        <div class="content-card">
            <h2>Sit-in Monitoring System</h2>
            <p>This system was built to manage and monitor student use of the CCS computer laboratories. It tracks sit-in sessions, lab availability, student participation, and leaderboard rankings to encourage consistent academic engagement.</p>
        </div>

        <div class="content-card">
            <h2>Connect With Us</h2>
            <p>Follow the official Facebook page of CCS UC Main for announcements, events, and updates:</p>
            <a href="#" class="social-link">
                <i class="fab fa-facebook-f"></i> College of Computer Studies – UC Main
            </a>
            <p style="margin-top: 0.75rem; font-size: 0.8rem;">facebook.com/ucmainccs</p>
        </div>
    </div>

    <script>
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>