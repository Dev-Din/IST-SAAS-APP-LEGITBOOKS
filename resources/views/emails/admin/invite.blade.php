<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Invitation - LegitBooks</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #392a26;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #392a26;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #392a26;
            padding: 15px;
            margin: 20px 0;
        }
        .permissions-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .permissions-list li {
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LegitBooks Admin Invitation</h1>
    </div>
    <div class="content">
        <p><strong>Hi {{ $invitation->first_name }},</strong></p>
        
        <p>{{ $tenantName }} has invited you to join LegitBooks as <strong>{{ $invitation->role_name ?? 'an Admin' }}</strong>.</p>

        <div class="info-box">
            <h3 style="margin-top: 0;">Your Temporary Credentials</h3>
            <p><strong>Email:</strong> {{ $invitation->email }}</p>
            <p><strong>Temporary Password:</strong> <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px;">{{ $tempPassword }}</code></p>
            <p style="margin-bottom: 0;"><strong>Important:</strong> You will be required to change this password when you first sign in.</p>
        </div>

        @if(!empty($invitation->permissions))
        <div>
            <h3>Assigned Permissions</h3>
            <ul class="permissions-list">
                @foreach($invitation->permissions as $permission)
                <li>{{ $permission }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <p>To accept this invitation and create your admin account, please click the button below:</p>
        
        <div style="text-align: center;">
            <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>
        </div>

        <p style="font-size: 14px; color: #6b7280;">
            Or copy and paste this link into your browser:<br>
            <a href="{{ $acceptUrl }}" style="color: #392a26; word-break: break-all;">{{ $acceptUrl }}</a>
        </p>

        <div class="info-box" style="background-color: #fef3c7; border-left-color: #f59e0b;">
            <p style="margin: 0; color: #92400e;">
                <strong>⏰ Expiry Notice:</strong> Your invitation remains active for 14 days from the date it was sent (expires on {{ $invitation->expires_at->format('F d, Y') }}).
            </p>
        </div>
    </div>
    <div class="footer">
        <p>This email was sent from {{ $tenantName }}</p>
        <p>If you did not expect this invitation, please ignore this email.</p>
    </div>
</body>
</html>

