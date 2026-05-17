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
    <title>About - CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="frontend/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #0041b0, #0066ff);
            padding: 5rem 2rem;
            text-align: center;
            color: white;
            margin-top: 70px;
        }
        .about-badge {
            font-size: 0.8rem;
            letter-spacing: 2px;
            color: #ffd600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .about-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .about-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        .about-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .about-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #ffd600;
        }
        .about-card h2 {
            font-size: 1.5rem;
            color: #0041b0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .about-card p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        .contact-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
        }
        .contact-info p {
            margin: 0.5rem 0;
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
            .about-hero h1 { font-size: 1.8rem; }
            .about-card h2 { font-size: 1.2rem; }
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

    <!-- Hero Section -->
    <div class="about-hero">
        <div class="about-badge">About</div>
        <h1>About CCS — UC Main Campus</h1>
        <p class="about-subtitle">Excellence in computer education since 1964</p>
    </div>

    <div class="about-container">
        <!-- Section 1 -->
        <div class="about-card">
            <h2><i class="fas fa-university"></i> University of Cebu</h2>
            <p>The University of Cebu was founded in 1964 by Augusto W. Go as Cebu College of Commerce and gained university status in 1992. It is one of the most prominent private universities in the Visayas region, committed to offering affordable and quality education responsive to the demands of local and international communities. UC Main Campus is located at Sanciangko Street, Cebu City — in the heart of downtown Cebu.</p>
        </div>

        <!-- Section 2 -->
        <div class="about-card">
            <h2><i class="fas fa-laptop-code"></i> College of Computer Studies</h2>
            <p>The College of Computer Studies at UC Main Campus offers three undergraduate programs: Bachelor of Science in Computer Science (BSCS), Bachelor of Science in Information Technology (BSIT — PACUCOA Level II Accredited), and Bachelor of Science in Information Systems (BSIS). CCS prepares graduates for careers in software development, IT infrastructure, systems analysis, and the IT-BPM industry.</p>
        </div>

        <!-- Section 3 -->
        <div class="about-card">
            <h2><i class="fas fa-desktop"></i> Computer Laboratories</h2>
            <p>CCS manages four fully-equipped computer laboratories — Lab 524, Lab 525, Lab 526, and Lab 527 — available for student sit-in sessions, programming practice, and practical examinations.</p>
        </div>

        <!-- Section 4 -->
        <div class="about-card">
            <h2><i class="fas fa-envelope"></i> Contact</h2>
            <div class="contact-info">
                <p><i class="fas fa-map-marker-alt"></i> Address: Sanciangko St., Cebu City 6000</p>
                <p><i class="fas fa-phone"></i> Phone: (032) 255-7777</p>
                <p><i class="fas fa-phone-alt"></i> Registrar: (032) 253-9434</p>
                <p><i class="fas fa-envelope"></i> Email: main.collegeregistrar@uc.edu.ph</p>
            </div>
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