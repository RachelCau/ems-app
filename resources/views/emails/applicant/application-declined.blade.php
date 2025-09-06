<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Declined</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #EF4444;
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
            border-left: 4px solid #EF4444;
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
            background-color: #EF4444;
            /* danger/red */
        }

        .reason-box {
            border-left: 4px solid #FF9800;
            /* warning/orange */
            background-color: #FFF8E1;
            padding: 15px;
            margin: 20px 0;
        }

        .action-box {
            border-left: 4px solid #2563EB;
            /* primary/blue */
            background-color: #EFF6FF;
            padding: 15px;
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1e40af;
            /* Darker blue */
            background-image: linear-gradient(to bottom, #2563eb, #1e40af);
            /* Blue gradient */
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 12px;
            border: 1px solid #1e3a8a;
            /* Subtle border */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
            text-align: center;
        }

        /* This won't work in all email clients but provides a better experience where supported */
        a.button:hover {
            background-color: #1e3a8a;
            background-image: linear-gradient(to bottom, #1e40af, #1e3a8a);
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
        <h1>Application Status Update - Action Required</h1>
    </div>

    <div class="content">
        <p>Dear <b>{{ $applicant->first_name }} {{ $applicant->last_name }},</b></p>

        <p>Thank you for your interest in our institution. We have reviewed your application and need some additional information or corrections before we can proceed.</p>

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
                    <span class="status-badge">Declined</span>
                </div>
            </div>
        </div>

        <div class="reason-box">
            <h3>Reason for Declined Status:</h3>
            <p><strong>{{ $reasonType ?? 'Your application requires attention' }}</strong></p>

            @if(isset($reasonData) && $reasonData['reason_type'] === 'invalid_document')
            @if(isset($documentName))
            <p>The document <strong>"{{ $documentName }}"</strong> needs to be corrected or re-uploaded.</p>
            @endif

            @if(isset($reasonData['rejection_reason']))
            <div class="p-3 bg-red-50 rounded-md mt-2">
                <p class="font-medium">{{ $reasonData['rejection_reason'] }}</p>
                @if(isset($reasonData['rejection_subtext']))
                <p class="text-sm mt-1">{{ $reasonData['rejection_subtext'] }}</p>
                @endif
            </div>
            @endif
            @endif

            <p>{{ $declineDetails ?? 'Please review and update your application materials.' }}</p>
        </div>

        <div class="action-box">
            <h3>How to Fix This:</h3>
            <p>You can upload or resubmit your documents directly using the secure link below:</p>

            <div class="mt-4 text-center">
                <a href="{{ url('/secure-upload/' . $applicant->generateUploadToken()) }}" class="button">
                    Access Document Upload Portal
                </a>
            </div>

            <p class="mt-4 text-sm">This link is valid for 48 hours and provides secure access to upload your documents without logging in.</p>

            <ul class="mt-4 pl-5 list-disc space-y-1 text-sm">
                @if(isset($reasonData) && $reasonData['reason_type'] === 'invalid_document' && isset($documentName))
                <li>Please upload a corrected version of the <strong>{{ $documentName }}</strong> document</li>
                @else
                <li>Review and update any missing or invalid documents</li>
                @endif
                <li>Ensure all documents are clearly legible</li>
                <li>File formats accepted: PDF, JPG, PNG (max 6MB)</li>
            </ul>
        </div>

        <p>Once you've made the necessary updates, our admissions team will review your application again. If you have any questions or need assistance, please contact our admissions office.</p>

        <div class="footer">
            <p>Thank you for your interest in our institution.<br>
                The Admissions Team</p>

            <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                Please note that this is an automated notification. Replies to this email will not be received or reviewed.
            </p>

            <p>© {{ date('Y') }} CSMS. All rights reserved.</p>
        </div>
    </div>
</body>

</html>