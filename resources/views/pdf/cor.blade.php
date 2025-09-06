<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Certificate of Registration</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 7pt;
            position: relative;
        }

        body::before {
            content: "";
            background-image: url('{{ public_path('images/logo.png') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 50%;
            opacity: 0.1;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .logo-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }

        .logo-header img {
            height: 60px;
            margin-right: 15px;
        }

        .header-title {
            text-align: center;
        }

        .header h1 {
            font-size: 16pt;
            margin: 0;
            padding: 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 14pt;
            margin: 5px 0;
            padding: 0;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0;
            font-size: 7pt;
        }

        .student-info {
            margin-bottom: 15px;
        }

        .student-info p {
            margin: 2px 0;
            font-size: 9pt;
        }

        .subject-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .subject-table th,
        .subject-table td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
            font-size: 7pt;
        }

        .subject-table th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .subject-table td {
            padding: 8px 5px;
        }

        .subject-table .total-row {
            font-weight: bold;
        }

        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .assessment-table td {
            padding: 3px;
            font-size: 7pt;
        }

        .signature {
            margin-top: 30px;
            text-align: center;
            width: 40%;
        }

        .signature p {
            margin: 0;
            font-size: 7pt;
            text-align: center;
        }

        .signature .name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature .title {
            font-style: italic;
        }

        .layout {
            display: flex;
            flex-direction: row;
        }

        .footer {
            margin-top: 20px;
            font-size: 7pt;
            line-height: 1.0;
            text-align: left;
        }

        .nothing-follows {
            text-align: center;
            font-style: italic;
            margin: 10px 0;
            font-weight: bold;
            font-size: 7pt;
        }

        .total-row {
            font-weight: bold;
        }
        
        .center-content {
            text-align: left;
            margin: 0 auto;
            width: 80%;
        }

        .approval-box {
            text-align: right;
            padding: 10px;
        }
        
        .approval-heading {
            margin-bottom: 20px;
            font-size: 7pt;
        }
        
        .approval-name {
            font-weight: bold;
            margin: 0;
            font-size: 7pt;
            text-transform: uppercase;
        }
        
        .approval-title {
            font-style: italic;
            margin-top: 5px;
            font-size: 7pt;
        }

        h3 {
            font-size: 12pt;
            margin-top: 15px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo-header">
            <img src="{{ public_path('images/logo.png') }}" alt="BPC Logo">
            <div class="header-title">
                <h1>Bulacan Polytechnic College</h1>
            </div>
        </div>
        <h2>{{ $student->campus->name ?? 'MALOLOS CAMPUS' }}</h2>
        <p>School Year <strong>{{ $academicYear->name ?? '2024-2025' }} {{ $student->semester == 1 ? '1st' : ($student->semester == 2 ? '2nd' : '3rd') }} Semester</strong> </p>
        <h2>Certificate of Registration(COR)</h2>
    </div>

    <div class="student-info">
        <p><strong>Student ID:</strong> {{ $student->student_number }}</p>
        <p><strong>Name:</strong> {{ strtoupper($student->last_name . ', ' . $student->first_name . ' ' . $student->middle_name) }}</p>
        <p><strong>Course:</strong> {{ $student->program_code }}{{ $student->year_level }}A</p>
        <p><strong>Application Status:</strong> OFFICIALLY ENROLLED</p>
        <p><strong>Campus:</strong> {{ $student->campus->name ?? 'MALOLOS' }}</p>
    </div>

    <h3>Courses</h3>
    <table class="subject-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Units</th>
                <th>Prerequisite</th>
            </tr>
        </thead>
        <tbody>
            @php $totalUnits = 0; @endphp
            @forelse($enrolledCourses as $course)
            <tr>
                <td>{{ $course->course->code }}</td>
                <td>{{ $course->course->name }}</td>
                <td style="text-align: center;">{{ $course->course->unit }}</td>
                <td>{{ $course->course->prerequisite ?? '' }}</td>
            </tr>
            @php $totalUnits += $course->course->unit; @endphp
            @empty
            <tr>
                <td colspan="4" style="text-align: center;">No enrolled courses found</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">Total Units:</td>
                <td style="text-align: center;">{{ $totalUnits }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="nothing-follows">**************NOTHING FOLLOWS**************</div>

    <div style="display: flex; width: 100%; margin-top: 30px; border: 1px solid black;">
        <div style="width: 50%; border-right: 1px solid black;">
            <!-- Empty space on the left side -->
        </div>
        <div style="width: 50%; text-align: center; padding: 20px;">
            <div class="approval-box">
                <p class="approval-heading">Approved By:</p>
                <p class="approval-name">KAREN-ANN ROSE V. FAYUMO</p>
                <p class="approval-title">College Registrar</p>
            </div>
        </div>
    </div>

    <div class="center-content">
        <div class="footer">
            <p>FOR BPC PROGRAMS under CHED – FREE TUITION AND MISC. FEES</p>
            <p>-Tuition and Miscellaneous Fees shall be CHARGED to CHED UniFAST.</p>
            
            <p>FOR BPC TECH-VOC PROGRAMS under TESDA- FREE TUITION AND MISC. FEES</p>
            <p>- Tuition and Miscellaneous Fees shall be CHARGED to PROVINCIAL GOVERNMENT OF BULACAN</p>
            <p>Scholarship Program "Tulong Pang-Edukasyon Para Sa Bulakenyo".</p>
            
            <p>FOR BPC SHS Program under DepEd – FREE FOR VOUCHER RECIPIENTS</p>
            <p>-If an enrollee is Voucher recipient, his/her tuition fee is CHARGED to DepEd</p>
            <p>and he/shall pay only Php 515.00 for Miscellaneous Fees.</p>
            <p>-If an enrollee is NOT Voucher recipient, he/she shall pay for Tuition</p>
            <p>(with Discount: P3937.50 | without Discount: P4812.50) and Php 515.00 for Miscellaneous Fees.</p>
        </div>
    </div>
</body>

</html>