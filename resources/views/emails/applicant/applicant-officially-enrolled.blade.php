<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Enrollment Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #004080;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            background-color: white;
            margin: 20px auto;
            padding: 20px;
            max-width: 600px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .credentials-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .details-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .label {
            font-weight: bold;
            width: 40%;
            color: #004080;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 0.8em;
            color: #666;
        }
        .important-info {
            background-color: #fef9e7;
            border-left: 4px solid #f1c40f;
            padding: 10px;
            margin: 15px 0;
        }
        .note {
            font-size: 0.9em;
            font-style: italic;
            color: #555;
            margin-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #27ae60;
            color: white;
            border-radius: 3px;
            font-weight: bold;
        }
        .student-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #004080;
            letter-spacing: 1px;
        }
        .congratulations {
            font-size: 1.1em;
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Official Enrollment Confirmation</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p class="congratulations">We are pleased to inform you that you are now officially enrolled at Bulacan Polytechnic College for the {{ $academicYear }} . Your dedication and effort have paid off, and we warmly welcome you to our academic community.</p>

        <div class="credentials-box">
            <div class="details-row">
                <div class="label">Student Number:</div>
                <div class="student-number">{{ $studentNumber }}</div>
            </div>
            <div class="details-row">
                <div class="label">Program:</div>
                <div>{{ $programName }}</div>
            </div>
            <div class="details-row">
                <div class="label">Academic Year:</div>
                <div>{{ $academicYear }}</div>
            </div>
            <div class="details-row">
                <div class="label">Status:</div>
                <div>
                    <span class="status-badge">{{ $status }}</span>
                </div>
            </div>
        </div>

        <div class="important-info">
            <p><strong>Important Information:</strong></p>
            <p>Please make sure to regularly check the website and registered email for updates regarding your class schedule, orientation, and other academic announcements.</p>
        </div>

        <p>Once again, congratulations and welcome to Bulacan Polytechnic College!</p>

        <p>If you have any questions or need assistance, please contact our registrar's office.</p>

        <p class="note">This is an automated email. Please do not reply directly to this message.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Best regards, Registrar’s Office. All rights reserved.</p>
    </div>
</body>
</html> 