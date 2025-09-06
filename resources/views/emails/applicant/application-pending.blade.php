<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Received</title>
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
            width: 120px;
        }

        .status-badge {
            font-weight: bold;
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            background-color: #6B7280;
            /* gray */
        }

        .next-steps {
            background-color: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .next-steps h3 {
            color: #4B5563;
            margin-top: 0;
        }

        .next-steps ol {
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
        <h1>Application Received</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>Thank you for submitting your application to our institution. We are pleased to confirm that we have received your application and it is currently under review.</p>

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
                    <span class="status-badge">Pending</span>
                </div>
            </div>
        </div>

        <div class="next-steps">
            <h3>What Happens Next?</h3>
            <ol>
                <li>Our admissions team will review your application and verify all the submitted documents.</li>
                <li>We will assess your eligibility for the program you have applied for.</li>
                <li>You will receive an email notification once your application status changes.</li>
                <li>Depending on your program, you may be scheduled for an entrance examination or interview.</li>
            </ol>
        </div>

        <p>The application review process typically takes 3-5 business days. You can track the status of your application by logging into your student portal using the credentials you created during registration.</p>

        <p>If you have any questions or need to update your application information, please contact our admissions office.</p>

        <div class="footer">
            <p>Thank you for choosing our institution.<br>
                The Admissions Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html>