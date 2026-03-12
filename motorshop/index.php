<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceHub - Expert Vehicle Care</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Specific styles for the Landing Page */
        body, html {
            height: 100%;
            margin: 0;
            display: block; /* Overrides the flex centering from previous style.css */
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background-color: #161b22; /* Dark navbar matching the design */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .navbar-brand {
            font-size: 20px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand span {
            color: #FF7A00;
        }

        .nav-btn {
            background-color: #FF7A00;
            color: #fff;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background-color: #e66a00;
        }

        /* Hero Section */
        .hero {
            /* Using a placeholder image similar to your design. Replace with your actual local image path like 'images/motorcycle-bg.jpg' */
            background-image: linear-gradient(rgba(13, 17, 23, 0.6), rgba(13, 17, 23, 0.6)), url('https://images.unsplash.com/photo-1558981403-c5f9899a28bc?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: calc(100vh - 65px); /* Full height minus navbar */
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .hero-content {
            max-width: 600px;
            padding: 20px;
        }

        .hero-content h1 {
            font-size: 42px;
            margin-bottom: 15px;
            line-height: 1.2;
            color: #fff;
        }

        .hero-content h1 .highlight {
            color: #FF7A00;
        }

        .hero-content p {
            font-size: 16px;
            color: #c9d1d9;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .cta-btn {
            background-color: #FF7A00;
            color: #fff;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s, transform 0.2s;
        }

        .cta-btn:hover {
            background-color: #e66a00;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <span>🔧</span> ServiceHub
        </a>
        <a href="login.php" class="nav-btn">Login | Sign Up</a>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Expert Care for <br> Your <span class="highlight">DREAM MACHINE</span></h1>
            <p>Experience top-notch professional auto maintenance. <br> Fast, reliable, and pocket-friendly prices.</p>
            <a href="login.php" class="cta-btn">Book Appointment Now</a>
        </div>
    </section>

</body>
</html>