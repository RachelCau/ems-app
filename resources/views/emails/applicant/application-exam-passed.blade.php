<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Congratulations! You Passed the Entrance Exam</title>
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
            border-bottom: 3px solid #4CAF50;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }

        h1 {
            color: #2E7D32;
            margin-top: 0;
        }

        .exam-results {
            background-color: #F1F8E9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
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
            color: #2E7D32;
            font-weight: bold;
        }

        .interview-details {
            background-color: #E3F2FD;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #1976D2;
        }

        .pending-notice {
            background-color: #FFF8E1;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #FFA000;
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
            background-color: #4CAF50;
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
        <img src="{{ asset('images/bpc_logo.png') }}" alt="School Logo" class="logo">
        <h1>Congratulations, {{ $applicant->first_name }}!</h1>
    </div>

    <p>We are pleased to inform you that you have <span class="highlight">successfully passed</span> the entrance examination for your program application.</p>

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
            <div class="highlight">{{ $reasonData['score'] }} / {{ $reasonData['total_items'] }}</div>
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

    @if($reasonData['reason_type'] === 'exam_passed_with_interview')
    <div class="interview-details">
        <h2>Your Interview Details</h2>
        <p>You have been automatically scheduled for an interview. Please make note of the details below:</p>

        <div class="details-row">
            <div class="label">Interview Date:</div>
            <div>{{ \Carbon\Carbon::parse($reasonData['interview_date'])->format('F d, Y') }}</div>
        </div>
        <div class="details-row">
            <div class="label">Time:</div>
            <div>{{ $reasonData['start_time'] }} - {{ $reasonData['end_time'] }}</div>
        </div>
        <div class="details-row">
            <div class="label">Venue:</div>
            <div>{{ $reasonData['venue'] }}</div>
        </div>

        <p><strong>Important:</strong> Please arrive at least 30 minutes before your scheduled time. Bring your ID, a copy of this email, and any required documents.</p>
    </div>
    @else
    <div class="pending-notice">
        <h2>Interview Schedule Pending</h2>
        <p>Your exam results qualify you for the next step in the admissions process - an interview.</p>
        <p>However, all current interview slots are filled. Our admissions team is working to create new interview schedules, and you will be automatically assigned to one as soon as it becomes available.</p>
        <p>You will receive an email notification with your interview details once you have been assigned to a schedule.</p>
    </div>
    @endif

    <p>If you have any questions or need further assistance, please don't hesitate to contact our admissions office.</p>

    <div class="footer">
        <p>Please note that this is an automated notification. Replies to this email will not be received or reviewed.</p>
        <p>&copy; {{ date('Y') }} School Admissions Office. All rights reserved.</p>
    </div>
</body>

</html>