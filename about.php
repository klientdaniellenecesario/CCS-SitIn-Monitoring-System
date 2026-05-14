<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - CCS Sit-in Monitoring System</title>
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
        .program-list {
            list-style: none;
            padding: 0;
        }
        .program-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .program-list li i { color: #ffd600; width: 20px; }
        .contact-info {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .contact-info p { margin-bottom: 0.5rem; }
        .contact-info i { width: 25px; color: #0052cc; }
        
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
                <a href="community.php"><i class="fas fa-users"></i> Community</a>
                <a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <h1>About the College of Computer Studies</h1>
        <p>University of Cebu Main Campus</p>
    </div>

    <div class="content-section">
        <div class="content-card">
            <h2>Our History & Mission</h2>
            <p>The University of Cebu (UC) is one of the most prominent private universities in the Visayas region, founded in 1964 by lawyer and businessman Augusto W. Go as Cebu College of Commerce. It was renamed the University of Cebu in 1992 after achieving university status.</p>
            <p>UC is committed to its mission: <strong>"University of Cebu offers affordable and quality education responsive to the demands of local and international communities."</strong></p>
            <p>The UC Main Campus is located at Sanciangko Street, Cebu City — situated in the heart of downtown Cebu, close to Colon Street. It houses the Colleges of Business and Accountancy, Computer Studies, and Law, and serves as a hub for academic excellence and student achievement.</p>
        </div>

        <div class="content-card">
            <h2>Academic Programs</h2>
            <p>The College of Computer Studies (CCS) at UC Main Campus offers the following undergraduate programs:</p>
            <ul class="program-list">
                <li><i class="fas fa-check-circle"></i> Bachelor of Science in Computer Science (BSCS)</li>
                <li><i class="fas fa-check-circle"></i> Bachelor of Science in Information Technology (BSIT) — <strong>Level II Accredited Status by PACUCOA/FAAP</strong></li>
                <li><i class="fas fa-check-circle"></i> Bachelor of Science in Information Systems (BSIS)</li>
            </ul>
            <p>CCS is part of a network of international and ASEAN academic partners, including BINUS University (Indonesia), MAPUA University (Philippines), and Saint Louis University (Philippines), among others.</p>
        </div>

        <div class="content-card">
            <h2>Computer Laboratories</h2>
            <p>The college is equipped with modern computer laboratories designed for programming sessions, practical examinations, and hands-on system-based activities. <strong>Labs 524, 525, 526, and 527</strong> serve as the primary computer laboratory rooms for CCS students.</p>
            <p>These facilities support the college's outcomes-based curriculum and prepare students for careers in software development, IT infrastructure, systems analysis, and the rapidly growing IT-BPM industry in Cebu City.</p>
        </div>

        <div class="content-card">
            <h2>Contact Information</h2>
            <div class="contact-info">
                <p><i class="fas fa-map-marker-alt"></i> Sanciangko St., Cebu City 6000, Philippines</p>
                <p><i class="fas fa-phone"></i> Phone: (032) 255-7777</p>
                <p><i class="fas fa-phone-alt"></i> Registrar: (032) 253-9434</p>
                <p><i class="fas fa-envelope"></i> Email: main.collegeregistrar@uc.edu.ph</p>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.mobile-menu')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html>