<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CSMS - Centralized School Management System</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <style>
        :root {
            --primary-blue: #0A3D91;
            --light-blue: #E6F0FF;
            --accent-blue: #1A73E8;
            --white: #FFFFFF;
            --gray: #666666;
            --success-green: #0F9D58;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--gray);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .section {
            padding: 80px 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header styling */
        header {
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }
        
        .logo {
            font-weight: 700;
            font-size: 24px;
            color: var(--primary-blue);
            text-decoration: none;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-menu li {
            margin-left: 30px;
        }
        
        .nav-menu a {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-menu a:hover {
            color: var(--primary-blue);
        }
        
        .header-buttons {
            display: flex;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-outline {
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            margin-right: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-blue);
            color: var(--white);
            border: 1px solid var(--primary-blue);
        }
        
        .btn-outline:hover {
            background-color: rgba(10, 61, 145, 0.1);
        }
        
        .btn-primary:hover {
            background-color: #092d6e;
        }
        
        /* Footer styling */
        footer {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 40px 0 20px;
            text-align: center;
        }
        
        .footer-content {
            margin-bottom: 20px;
        }
        
        .footer-copyright {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-blue);
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .nav-menu, .header-buttons {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .mobile-menu-open .nav-menu,
            .mobile-menu-open .header-buttons {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 70px;
                left: 0;
                width: 100%;
                background-color: var(--white);
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .mobile-menu-open .nav-menu li {
                margin: 10px 0;
            }
            
            .mobile-menu-open .header-buttons .btn {
                margin: 10px 0;
                text-align: center;
            }
        }
    </style>
    
    @livewireStyles
</head>
<body>
    <header>
        <div class="header-container container">
            <a href="#" class="logo">CSMS</a>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
            
            <ul class="nav-menu">
                <li><a href="#hero">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#courses">Courses</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            
            <div class="header-buttons">
                <a href="#" class="btn btn-outline">Login</a>
                <a href="/register" class="btn btn-primary">Enroll Now</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <p>Centralized School Management System</p>
            </div>
            <div class="footer-copyright">
                <p>&copy; {{ date('Y') }} CSMS. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.querySelector('header').classList.toggle('mobile-menu-open');
        });
    </script>
    
    @livewireScripts
</body>
</html> 