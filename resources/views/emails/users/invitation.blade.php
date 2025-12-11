<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>You've Been Invited to Join {{ $tenant->name }} on LegitBooks</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      padding: 24px;
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      color: #111827;
    }
    .wrapper {
      max-width: 600px;
      margin: 0 auto;
    }
    .card {
      background-color: #ffffff;
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      overflow: hidden;
      box-shadow: 0 6px 24px rgba(0,0,0,0.06);
    }
    .header {
      background-color: #392a26;
      padding: 24px;
      color: #ffffff;
      text-align: center;
    }
    .header-title {
      margin: 0;
      font-size: 24px;
      font-weight: 600;
    }
    .header-subtitle {
      margin: 8px 0 0;
      font-size: 14px;
      opacity: 0.9;
    }
    .body {
      padding: 32px 24px;
    }
    .greeting {
      font-size: 16px;
      line-height: 1.6;
      color: #111827;
      margin-bottom: 20px;
    }
    .message {
      font-size: 15px;
      line-height: 1.6;
      color: #374151;
      margin-bottom: 24px;
    }
    .cta-button {
      display: inline-block;
      margin: 24px 0;
      padding: 14px 28px;
      background-color: #392a26;
      color: #ffffff !important;
      text-decoration: none;
      font-size: 16px;
      font-weight: 600;
      border-radius: 6px;
      text-align: center;
    }
    .cta-button:hover {
      background-color: #2a1f1c;
    }
    .footer {
      background-color: #fafafa;
      padding: 20px 24px;
      font-size: 13px;
      color: #6b7280;
      border-top: 1px solid #f3f4f6;
      text-align: center;
    }
    .info-box {
      background-color: #f9fafb;
      border-left: 4px solid #392a26;
      padding: 16px;
      margin: 24px 0;
      border-radius: 4px;
    }
    .info-box p {
      margin: 0;
      font-size: 14px;
      color: #374151;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <h1 class="header-title">You've Been Invited!</h1>
        @php
            $brandMode = $tenant ? $tenant->getBrandingMode() : 'A';
            $brandText = $brandMode !== 'C' ? ' via LegitBooks' : '';
        @endphp
        <p class="header-subtitle">
          {{ $tenant->name }}{{ $brandText }}
        </p>
      </div>
      <div class="body">
        <div class="greeting">
          Hello {{ $invitation->first_name }},
        </div>
        @php
            $brandMode = $tenant ? $tenant->getBrandingMode() : 'A';
            $platformText = $brandMode !== 'C' ? ' on LegitBooks, a comprehensive accounting and invoicing platform' : '';
        @endphp
        <div class="message">
          You've been invited to join <strong>{{ $tenant->name }}</strong>{{ $platformText }}.
        </div>
        <div class="message">
          To get started, please click the button below to set up your account and create your password.
        </div>
        <div class="message" style="font-size: 14px; color: #6b7280; font-style: italic;">
          Your invitation remains active for 14 days from the date it was sent.
        </div>
        <div style="text-align: center;">
          <a href="{{ $acceptUrl }}" class="cta-button">Set up your account</a>
        </div>
        <div class="info-box">
          <p><strong>Invitation Details:</strong></p>
          <p>Role: {{ $invitation->role_name ?? 'Not specified' }}</p>
          <p>This invitation will expire on {{ $invitation->expires_at->timezone(config('app.timezone'))->format('d/m/Y') }}.</p>
        </div>
        <div class="message" style="font-size: 14px; color: #6b7280;">
          If you did not expect this invitation, you can safely ignore this email.
        </div>
      </div>
      <div class="footer">
        <p style="margin: 0;">
          @php
              $brandMode = $tenant ? $tenant->getBrandingMode() : 'A';
              $brandText = $brandMode !== 'C' ? ' via LegitBooks' : '';
          @endphp
          This invitation was sent by {{ $tenant->name }}{{ $brandText }}.<br>
          If you have questions, please contact {{ $tenant->email ?? 'support' }}.
        </p>
      </div>
    </div>
  </div>
</body>
</html>

