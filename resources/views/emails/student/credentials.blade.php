<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Credentials</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #6B7280;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .content {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .credentials-box {
            border-left: 4px solid #6B7280;
            background-color: #f7f7f9;
            padding: 15px;
            margin: 20px 0;
        }

        .details-row {
            display: flex;
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            width: 160px;
        }

        .value {
            font-family: 'Courier New', monospace;
        }

        .status-active {
            font-weight: bold;
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            background-color: #10B981; /* green */
        }

        .important-notice {
            background-color: #FEF3C7;
            border: 1px solid #FCD34D;
            border-left: 4px solid #F59E0B;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            background-color: #2563EB;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Student Account Credentials</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $student->first_name }} {{ $student->last_name }},</b></p>

        <p>Congratulations! Your enrollment has been completed successfully. You are now officially a student in our institution.</p>

        <p>Below are your student account credentials that you can use to access your student portal:</p>

        <div class="credentials-box">
            <div class="details-row">
                <div class="label">Student Number:</div>
                <div class="value">{{ $student->student_number }}</div>
            </div>
            <div class="details-row">
                <div class="label">Email/Username:</div>
                <div class="value">{{ $student->user->email }}</div>
            </div>
            <div class="details-row">
                <div class="label">Password:</div>
                <div class="value">{{ $password }}</div>
            </div>
            <div class="details-row">
                <div class="label">Status:</div>
                <div><span class="status-active">Active</span></div>
            </div>
        </div>

        <div class="important-notice">
            <h3 style="margin-top: 0; color: #B45309;">Important:</h3>
            <p>For security reasons, please change your password after your first login. Keep your credentials confidential and do not share them with anyone.</p>
        </div>

        <p>You can now access the student portal to view your enrollment details, courses, grades, and other important academic information.</p>

        <p style="text-align: center; margin: 25px 0;">
            <a href="{{ url('/') }}" class="button">Access Student Portal</a>
        </p>

        <p>If you have any questions or need assistance, please contact your campus registrar's office.</p>

        <div class="footer">
            <p>Thank you for choosing our institution.<br>
                <b>The Registrar's Office</b>
            </p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                This is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html> 