<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Complete your application form quickly and easily.">
    <title>{{ $title ?? 'CSMS - Application Form' }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- AOS Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* Background and Typography */
        body {
            background: url("{{ asset('assets/images/min-reg.jpg') }}") no-repeat center center/cover;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            padding: 1.5rem 0;
        }

        /* Glassmorphic Effect */
        .form-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease-in-out;
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
        }

        .form-container:hover {
            transform: scale(1.02);
        }

        /* Heading and Labels */
        .form-title {
            text-align: center;
            font-weight: 700;
            color: #222;
        }

        .form-subtitle {
            text-align: center;
            color: #555;
            font-size: 14px;
        }

        .form-group label {
            font-weight: 500;
        }

        /* Floating Shapes */
        .shape {
            position: absolute;
            width: 80px;
            height: 80px;
            opacity: 0.5;
            border-radius: 50%;
            animation: float 6s infinite ease-in-out, rotate 10s infinite linear;
        }

        .shape:nth-child(1) {
            top: 5%;
            left: 10%;
            background: linear-gradient(135deg, #ff7eb3, #ff758c);
        }

        .shape:nth-child(2) {
            top: 80%;
            right: 5%;
            background: linear-gradient(135deg, #6a85ff, #7686ff);
            animation-delay: 1.5s;
        }

        .shape:nth-child(3) {
            bottom: 10%;
            left: 45%;
            background: linear-gradient(135deg, #ff9f43, #ff6f43);
            animation-delay: 3s;
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Fade-in Effect */
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.8s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Container adjustment for smaller screens */
        @media (max-width: 768px) {
            .form-container {
                padding: 1rem;
                max-width: 95%;
            }
        }

    </style>
</head>

<body>

    <!-- Floating Shapes -->
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>

    <div class="container my-3">
        <div class="form-container" data-aos="fade-up" data-aos-duration="1000">
            <h1 class="form-title"><i class="fas fa-user-edit"></i> Application Form</h1>
            <p class="form-subtitle">Please indicate all required information below.</p>
            <hr>
            @livewire('multi-step-form')
        </div>
    </div>

    @livewireScripts

    <!-- Bootstrap Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS Animation Initialization -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            AOS.init();
        });
    </script>

    <!-- Flatpickr Date Picker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#dob", {
                dateFormat: "m-d-Y",
                maxDate: new Date().fp_incr(-17 * 365),
                minDate: new Date().fp_incr(-100 * 365),
                disableMobile: true
            });
        });
    </script>

</body>

</html>
