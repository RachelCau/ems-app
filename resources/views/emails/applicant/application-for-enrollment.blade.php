<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ready for Enrollment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #10B981;
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
            border-left: 4px solid #10B981;
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
            width: 120px;
        }

        .status-badge {
            font-weight: bold;
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            background-color: #10B981;
            /* success/green */
        }

        .requirements {
            background-color: #ECFDF5;
            border: 1px solid #A7F3D0;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .requirements h3 {
            color: #065F46;
            margin-top: 0;
        }

        .requirements ul {
            padding-left: 20px;
        }

        .enrollment-steps {
            background-color: #F3F4F6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .enrollment-steps h3 {
            color: #374151;
            margin-top: 0;
        }

        .enrollment-steps ol {
            padding-left: 20px;
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
        <h1>Ready for Enrollment</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>We are pleased to inform you that you have successfully completed all the admission requirements for the Bachelor of Science in Information Systems program at Bulacan Polytechnic College. You are now eligible to proceed with your enrollment.</p>

        <div class="credentials-box">
            <div class="details-row">
                <div class="label">Applicant ID:</div>
                <div>{{ $applicant->applicant_number }}</div>
            </div>
            <div class="details-row">
                <div class="label">Campus:</div>
                <div>{{ $campus }}</div>
            </div>
            <div class="details-row">
                <div class="label">Program:</div>
                <div>{{ $program }}</div>
            </div>
            <div class="details-row">
                <div class="label">Status:</div>
                <div>
                    <span class="status-badge">For Enrollment</span>
                </div>
            </div>
        </div>

        <p>For any queries regarding the enrollment process, please contact our admissions office or visit our campus during office hours.</p>

        <div class="footer">
            <p>Welcome to our academic community!<br>
                The Admissions Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html>