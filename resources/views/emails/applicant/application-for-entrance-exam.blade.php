<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrance Examination Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #3B82F6;
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
            border-left: 4px solid #3B82F6;
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
            background-color: #3B82F6;
            /* info/blue */
        }

        .schedule-box {
            background-color: #ECFDF5;
            /* light green */
            border: 1px solid #6EE7B7;
            /* green */
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .schedule-box h3 {
            color: #065F46;
            /* darker green */
            margin-top: 0;
        }

        .guidelines {
            background-color: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .guidelines h3 {
            color: #1E40AF;
            margin-top: 0;
        }

        .guidelines ul {
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
        <h1>Entrance Examination Schedule</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>We are pleased to inform you that your application has been reviewed and you are now scheduled for an entrance examination.</p>

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
                    <span class="status-badge">For Entrance Exam</span>
                </div>
            </div>
        </div>

        {{-- Exam Schedule Details Box --}}
        <div class="schedule-box">
            <h3>Your Examination Details</h3>
            <div class="details-row">
                <div class="label">Date:</div>
                <div>{{ $examSchedule->exam_date->format('F d, Y') ?? 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">Start Time:</div>
                <div>{{ $examSchedule->start_time ? \Carbon\Carbon::parse($examSchedule->start_time)->format('g:i A') : 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">End Time:</div>
                <div>{{ $examSchedule->end_time ? \Carbon\Carbon::parse($examSchedule->end_time)->format('g:i A') : 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">Room:</div>
                <div>{{ is_object($examSchedule->room) ? $examSchedule->room->name : $examSchedule->room }}</div>
            </div>
            @if(is_object($examSchedule->room) && !empty($examSchedule->room->building))
            <div class="details-row">
                <div class="label">Building:</div>
                <div>{{ $examSchedule->room->building }}</div>
            </div>
            @endif
        </div>

        <div class="guidelines">
            <h3>Exam Preparation Guidelines</h3>
            <ul>
                <li>Review basic subjects relevant to your chosen program</li>
                <li>Be prepared to answer both multiple-choice and essay questions</li>
                <li>Bring a valid ID, your application number, and basic writing materials</li>
                <li>Arrive at least 30 minutes before the scheduled time</li>
                <li>Get adequate rest the night before your exam</li>
            </ul>
        </div>

        <p>The entrance examination is an important step in our admission process. Your performance will help us assess your academic readiness for your chosen program.</p>

        <p>If you have any questions or need special accommodations for your examination, please contact our admissions office as soon as possible.</p>

        <div class="footer">
            <p>Best of luck with your examination preparation!<br>
                The Admissions Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html>