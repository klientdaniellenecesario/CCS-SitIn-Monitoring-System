<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="frontend/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0052cc 0%, #0066ff 100%);
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
        
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 2rem 2rem;
        }
        .hero-content {
            max-width: 800px;
            color: white;
        }
        .hero-content h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-primary, .btn-secondary {
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #ffd600;
            color: #1a202c;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        .btn-secondary:hover {
            background: white;
            color: #0052cc;
        }
        .features-section {
            background: white;
            padding: 4rem 2rem;
        }
        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .feature-card:hover { transform: translateY(-5px); }
        .feature-card i { font-size: 2.5rem; color: #ffd600; margin-bottom: 1rem; }
        .feature-card h3 { font-size: 1.2rem; color: #1a202c; margin-bottom: 0.5rem; }
        .feature-card p { color: #718096; font-size: 0.9rem; }
        
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
            .hero-content h1 { font-size: 2rem; }
            .cta-buttons { flex-direction: column; align-items: center; }
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

    <section class="hero-section">
        <div class="hero-content">
            <h1>College of Computer Studies</h1>
            <p>Sit-in Monitoring System — Track, manage, and optimize computer lab usage for UC Main Campus students.</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn-primary">Login to Your Account</a>
                <a href="register.php" class="btn-secondary">Register New Account</a>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-desktop"></i>
                <h3>Lab PC Monitoring</h3>
                <p>Real-time tracking of computer availability across Labs 524, 525, 526, and 527.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Leaderboard & Rewards</h3>
                <p>Earn points and rewards for consistent lab engagement and academic excellence.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Reservation System</h3>
                <p>Reserve PCs in advance for group projects, research, and programming sessions.</p>
            </div>
        </div>
    </section>

    <script>
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>