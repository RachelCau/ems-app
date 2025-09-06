<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrance Examination Results</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #F44336;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }

        h1 {
            color: #D32F2F;
            margin-top: 0;
        }

        .exam-results {
            background-color: #FFEBEE;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #F44336;
        }

        .details-row {
            display: flex;
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .highlight {
            color: #D32F2F;
            font-weight: bold;
        }

        .info-box {
            background-color: #E8EAF6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3F51B5;
        }

        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #777;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3F51B5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ asset('images/logo.png') }}" alt="School Logo" class="logo">
        <h1>Entrance Examination Results</h1>
    </div>

    <p>Dear {{ $applicant->first_name }},</p>

    <p>We appreciate your interest in our institution and thank you for taking the time to complete our entrance examination.</p>

    <div class="exam-results">
        <h2>Your Exam Results</h2>
        <div class="details-row">
            <div class="label">Applicant Number:</div>
            <div>{{ $applicant->applicant_number }}</div>
        </div>
        <div class="details-row">
            <div class="label">Exam Date:</div>
            <div>{{ isset($reasonData['exam_date']) ? \Carbon\Carbon::parse($reasonData['exam_date'])->format('F d, Y') : 'Recent' }}</div>
        </div>
        <div class="details-row">
            <div class="label">Score:</div>
            <div>{{ $reasonData['score'] }} / {{ $reasonData['total_items'] }}</div>
        </div>
        <div class="details-row">
            <div class="label">Passing Score:</div>
            <div>{{ $reasonData['passing_score'] }}</div>
        </div>
        <div class="details-row">
            <div class="label">Status:</div>
            <div class="highlight">{{ $reasonData['remarks'] }}</div>
        </div>
    </div>

    <p>We regret to inform you that your score does not meet the minimum requirement for admission to your selected program for the current academic year ({{ $reasonData['academic_year'] }}).</p>

    <div class="info-box">
        <h2>Important Information</h2>
        <p>According to our admission policy:</p>
        <ul>
            <li>You will not be able to apply again for the same academic year ({{ $reasonData['academic_year'] }}).</li>
            <li>You may apply again for the next academic year if you wish to do so.</li>
            <li>We recommend taking time to strengthen your knowledge in relevant subject areas before attempting again.</li>
        </ul>

        <p>If you would like to discuss your results or explore other program options, please contact our admissions office:</p>
        <p>
            <strong>Phone:</strong> (123) 456-7890<br>
            <strong>Email:</strong> admissions@school.edu
        </p>
    </div>

    <p>We wish you success in your future educational endeavors.</p>

    <div class="footer">
        <p>Please note that this is an automated notification. Replies to this email will not be received or reviewed.</p>
        <p>&copy; {{ date('Y') }} School Admissions Office. All rights reserved.</p>
    </div>
</body>

</html>