<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #000; color: #fff; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
        .button { display: inline-block; background-color: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .review-details { background-color: #fff; padding: 15px; border-left: 4px solid #000; margin: 15px 0; }
        .notice { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>You're Invited to Join a Review</h1>
        </div>
        
        <div class="content">
            <p>Hi{{ $invitee ? ' ' . $invitee->name : '' }},</p>
            
            <p><strong>{{ $inviter->name }}</strong> has invited you to join a systematic review on Rayyan:</p>
            
            <div class="review-details">
                <h3>{{ $review->title }}</h3>
                @if($review->description)
                    <p>{{ $review->description }}</p>
                @endif
            </div>
            
            @if($message)
                <p><strong>Message from {{ $inviter->name }}:</strong></p>
                <p>{{ $message }}</p>
            @endif
            
            @if(!$isRegistered)
                <div class="notice">
                    <p><strong>Note:</strong> You need to create a Rayyan account first before you can accept this invitation.</p>
                </div>
                
                <p>To get started:</p>
                <ol>
                    <li>Click the button below to sign up</li>
                    <li>Create your account</li>
                    <li>You'll be automatically added to the review</li>
                </ol>
                
                <a href="{{ $acceptUrl }}" class="button">Sign Up & Join Review</a>
            @else
                <p>To accept this invitation and start collaborating, click the button below:</p>
                
                <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>
            @endif
            
            <p>Or copy and paste this link in your browser:</p>
            <p><small>{{ $acceptUrl }}</small></p>
            
            <p>Best regards,<br>The Rayyan Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Rayyan. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
