<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registration success confirmation page for applicants.">
    <meta name="author" content="Your Company Name">

    <title>{{ $title ?? 'Registration Success' }}</title>

    <!-- Preload critical resources -->
    <link rel="preload" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style">

    <!-- Google Fonts - Reduced to only necessary weights -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <!-- FontAwesome (Icons) - Using a smaller subset if possible -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url("{{ asset('assets/images/min-reg.jpg') }}") no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }

        .card-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-card {
            max-width: 600px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            background-color: white;
            transform: translateY(0);
            animation: simple-fade-in 0.8s ease-out forwards;
        }

        @keyframes simple-fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background-color: #28a745;
            padding: 20px;
            text-align: center;
            color: white;
        }

        .card-body {
            padding: 30px;
        }

        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 25px 0;
        }

        .btn-primary {
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            width: 100%;
            margin-top: 16px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .checkmark {
            background-color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }

        .checkmark i {
            color: #28a745;
            font-size: 30px;
        }

        .text-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .text-content {
            color: #666;
            margin-bottom: 0.75rem;
        }

        .text-signature {
            color: #888;
        }
    </style>
</head>

<body>
    <div class="card-container">
        <div class="success-card">
            <div class="card-header">
                <div class="checkmark">
                    <i class="fas fa-check"></i>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">Application Received</h3>
            </div>
            
            <div class="card-body">
                <h4 class="text-title">Dear {{ session('name') }}!</h4>
                
                <p class="text-content">
                    Thank you for your interest in joining our school. We have successfully received your application, and it is currently under academic review.
                </p>
                
                <p class="text-content">
                    Our admissions team is carefully evaluating your submission to ensure alignment with our educational standards and program objectives.
                </p>
                
                <p class="text-content">
                    You will be notified via email once a decision has been made. We appreciate your commitment to learning and look forward to the possibility of welcoming you to our school community.
                </p>
                
                <div class="divider"></div>
                
                <div class="text-signature">
                    <p style="margin-bottom: 4px;">Kind regards,</p>
                    <p style="margin: 0;">The Admissions Team</p>
                </div>
                
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    <span>Return to Home</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap Scripts - Load at end of body -->
    <script defer src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>