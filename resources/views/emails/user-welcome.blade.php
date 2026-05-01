<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5f7a; color: #fff; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
        .button { display: inline-block; background-color: #1a5f7a; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .credentials { background-color: #fff; padding: 15px; border-left: 4px solid #1a5f7a; margin: 15px 0; font-family: monospace; }
        .credentials-label { font-weight: bold; color: #1a5f7a; }
        .notice { background-color: #e7f3ff; padding: 12px; border-left: 4px solid #1a5f7a; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ $appName }}</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $user->name }},</p>
            
            <p>Your account has been created and is ready to use! Below are your login credentials:</p>
            
            <div class="credentials">
                <p><span class="credentials-label">Email:</span><br>{{ $user->email }}</p>
                <p><span class="credentials-label">Password:</span><br>{{ $password }}</p>
            </div>
            
            <div class="notice">
                <p><strong>Important:</strong> Please save your password in a secure location. We recommend changing it after your first login.</p>
            </div>
            
            <p>To get started, click the button below to log in:</p>
            
            <a href="{{ $loginUrl }}" class="button">Log In to {{ $appName }}</a>
            
            <p>Or copy and paste this link in your browser:</p>
            <p><small>{{ $loginUrl }}</small></p>
            
            <h3>What's Next?</h3>
            <ul>
                <li>Log in with your credentials</li>
                <li>Complete your profile if needed</li>
                <li>Start collaborating on systematic reviews</li>
            </ul>
            
            <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
            
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
