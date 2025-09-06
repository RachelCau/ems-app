<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #5D4EF7;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .simple-header {
            text-align: center;
            padding: 20px;
            background-color: #113F35;
            color: white;
            font-weight: bold;
        }

        .content {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .approved-content {
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #0A9548;
            color: white;
        }

        .credentials-box {
            border-left: 4px solid #5D4EF7;
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
        }

        .status-pending {
            background-color: #6B7280;
            /* gray */
        }

        .status-approved {
            background-color: #10B981;
            /* success/green */
        }

        .status-for-entrance-exam {
            background-color: #3B82F6;
            /* info/blue */
        }

        .status-for-interview {
            background-color: #F59E0B;
            /* warning/yellow */
        }

        .status-for-enrollment {
            background-color: #10B981;
            /* success/green */
        }

        .status-declined {
            background-color: #EF4444;
            /* danger/red */
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .simple-footer {
            text-align: center;
            font-size: 12px;
            color: #fff;
            margin-top: 30px;
            background-color: #14379B;
            padding: 15px;
        }
    </style>
</head>

<body>
    @if($newStatus === 'approved')
    <div class="simple-header">
        <h2>Application Status</h2>
    </div>

    <div class="approved-content">
        <p>Dear {{ $applicant->first_name }} {{ $applicant->last_name }},</p>

        <p>We are delighted to inform you that your application has been reviewed and officially approved.</p>

        <p>Welcome to our school! Your acceptance reflects our confidence in your potential and commitment to academic excellence. We are excited to accompany you on this important step in your educational journey.</p>

        <p>You will soon receive an email with further instructions, including enrollment details, orientation materials, and key dates to prepare for the upcoming term.</p>

        <p>Should you have any questions, our team is here to assist you.</p>

        <p>Warmest congratulations,<br>
            The Admissions Team</p>
    </div>

    <div class="simple-footer">
        <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
    </div>
    @else
    <div class="header">
        <h1>Application Status</h1>
    </div>

    <div class="content">
        <p>Hello <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>Your application status has been updated in the CSMS system.</p>

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
                    @php
                    $statusClass = 'status-' . str_replace(' ', '-', strtolower($newStatus));
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>

        @if($newStatus === 'for entrance exam')
        <p>You are scheduled for an entrance examination. You will receive details about the examination schedule shortly.</p>
        @elseif($newStatus === 'for interview')
        <p>You have been scheduled for an interview. You will receive details about the interview schedule shortly.</p>
        @elseif($newStatus === 'for enrollment')
        <p>Congratulations! You can now proceed with enrollment. Please visit the admissions office with the required documents.</p>
        @elseif($newStatus === 'declined')
        <p>We regret to inform you that your application has not been approved at this time. Please contact the admissions office for more information.</p>
        @endif

        <p>You can log in to your account by visiting the CSMS portal and check your application status.</p>

        <p>If you have any questions or need assistance, please contact your campus administrator.</p>

        <div class="footer">
            <p>Thank you,<br>
                CSMS Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>
        </div>
    </div>
    @endif
</body>

</html>