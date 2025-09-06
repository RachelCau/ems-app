<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Schedule Assignment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            border: 1px solid #ddd;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Exam Schedule Assignment</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $applicant->full_name }},</p>
        
        <p>We are pleased to inform you that you have been assigned to take the entrance examination for your application to our institution.</p>
        
        <div class="info-box">
            <h3>Exam Details:</h3>
            <div class="info-item"><strong>Date:</strong> {{ $examSchedule->exam_date->format('F j, Y') }}</div>
            <div class="info-item"><strong>Time:</strong> {{ $examSchedule->start_time->format('g:i A') }} - {{ $examSchedule->end_time->format('g:i A') }}</div>
            <div class="info-item"><strong>Venue:</strong> {{ $examSchedule->room->name }}</div>
            <div class="info-item"><strong>Applicant Number:</strong> {{ $applicant->applicant_number }}</div>
            <div class="info-item"><strong>Program:</strong> {{ $applicant->program->name ?? $applicant->desired_program }}</div>
        </div>
        
        <h3>Important Reminders:</h3>
        <ul>
            <li>Please arrive at least 30 minutes before the exam time.</li>
            <li>Bring your applicant ID or any valid government-issued ID.</li>
            <li>Bring at least two (2) pencils, pens, and erasers.</li>
            <li>No electronic devices will be allowed in the exam room.</li>
            <li>Calculators are only allowed for specific exams if mentioned in your program requirements.</li>
        </ul>
        
        <p>If you have any questions or need to reschedule, please contact our admissions office immediately.</p>
        
        <p>Best regards,<br>Admissions Office</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html> 