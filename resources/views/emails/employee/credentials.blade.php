<!DOCTYPE html>
<html>
<head>
    <title>Your Login Credentials</title>
</head>
<body>
    <h2>Your Login Credentials</h2>
    <p>Hello {{ $employee->first_name . ' ' . $employee->last_name }},</p>
    <p>Your account has been created. Here are your login credentials:</p>
    <p><strong>Username (Employee ID):</strong> {{ $employee->employee_number }}</p>
    <p><strong>Email:</strong> {{ $employee->user->email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please change your password after you login.</p>
    <p>Thank you!</p>
    
    <p style="font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
        This is an automated message. Please do not reply to this email as the mailbox is not monitored.
    </p>
</body>
</html> 