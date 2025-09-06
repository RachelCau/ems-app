<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .simple-header {
            text-align: center;
            padding: 20px;
            background-color: rgb(23, 10, 107);
            color: white;
            font-weight: bold;
        }

        .approved-content {
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            color: #333;
        }

        .credentials-box {
            border-left: 4px solid #5D4EF7;
            background-color: #f7f7f9;
            padding: 15px;
            margin: 20px 0;
            color: #333;
        }

        .details-row {
            display: flex;
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            width: 120px;
            color: #333;
        }

        .simple-footer {
            text-align: center;
            font-size: 12px;
            color: #333;
            margin-top: 30px;
            padding: 15px;
        }
    </style>
</head>

<body>
    <div class="simple-header">
        <h2>Application Status</h2>
    </div>

    <div class="approved-content">
        <p>Dear {{ $applicant->first_name }} {{ $applicant->last_name }},</p>

        <p>We are delighted to inform you that your application has been reviewed and officially approved.</p>

        <p>Welcome to our school! Your acceptance reflects our confidence in your potential and commitment to academic excellence. We are excited to accompany you on this important step in your educational journey.</p>

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
        </div>

        <p>You will soon receive an email with further instructions, including enrollment details, orientation materials, and key dates to prepare for the upcoming term.</p>

        <p>Should you have any questions, our team is here to assist you.</p>

        <p>Warmest congratulations,<br>
            The Admissions Team</p>

        <p style="font-size: 12px; color: #ddd; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 10px;">
            Please note that this is an automated notification. Replies to this email will not be received or reviewed.
        </p>
    </div>

    <div class="simple-footer">
        <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
    </div>
</body>

</html>