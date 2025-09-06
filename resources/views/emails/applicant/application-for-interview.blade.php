<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #8B5CF6;
            /* Purple shade */
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
            border-left: 4px solid #8B5CF6;
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
            background-color: #8B5CF6;
            /* Purple */
        }

        .schedule-box {
            background-color: #F5F3FF;
            /* Light purple */
            border: 1px solid #C4B5FD;
            /* Purple */
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .schedule-box h3 {
            color: #5B21B6;
            /* Darker purple */
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
        <h1>Interview Schedule</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>Congratulations! We are pleased to inform you that you have qualified for an interview as part of our admissions process.</p>

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
                    <span class="status-badge">For Interview</span>
                </div>
            </div>
        </div>

        {{-- Interview Schedule Details Box --}}
        <div class="schedule-box">
            <h3>Your Interview Details</h3>
            <div class="details-row">
                <div class="label">Date:</div>
                <div>{{ $interviewSchedule && $interviewSchedule->interview_date ? $interviewSchedule->interview_date->format('F d, Y') : 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">Start Time:</div>
                <div>{{ $interviewSchedule && $interviewSchedule->start_time ? (is_string($interviewSchedule->start_time) ? $interviewSchedule->start_time : \Carbon\Carbon::parse($interviewSchedule->start_time)->format('g:i A')) : 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">End Time:</div>
                <div>{{ $interviewSchedule && $interviewSchedule->end_time ? (is_string($interviewSchedule->end_time) ? $interviewSchedule->end_time : \Carbon\Carbon::parse($interviewSchedule->end_time)->format('g:i A')) : 'To be determined' }}</div>
            </div>
            <div class="details-row">
                <div class="label">Building:</div>
                <div>{{ $interviewSchedule && isset($interviewSchedule->room) && is_object($interviewSchedule->room) ? $interviewSchedule->room->building : ($interviewSchedule->venue ?? 'Main Campus') }}</div>
            </div>
            @if($interviewSchedule && !empty($interviewSchedule->interviewer))
            <div class="details-row">
                <div class="label">Interviewer:</div>
                <div>{{ $interviewSchedule->interviewer }}</div>
            </div>
            @endif
        </div>

        <div class="guidelines">
            <h3>Interview Preparation Guidelines</h3>
            <ul>
                <li>Arrive at least 15 minutes before your scheduled interview time</li>
                <li>Bring your valid ID and application documents</li>
                <li>Dress professionally for your interview</li>
                <li>Be prepared to discuss your academic achievements, goals, and interest in your chosen program</li>
                <li>Prepare questions you may want to ask about the program or institution</li>
            </ul>
        </div>

        <p>The interview is an important opportunity for us to get to know you better and for you to learn more about our program. We look forward to meeting you.</p>

        <p>If you need to reschedule your interview or have any questions, please contact our admissions office as soon as possible.</p>

        <div class="footer">
            <p>We look forward to meeting you!<br>
                The Admissions Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html>