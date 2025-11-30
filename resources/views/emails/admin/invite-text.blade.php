Hi {{ $invitation->first_name }},

{{ $tenantName }} has invited you to join LegitBooks as {{ $invitation->role_name ?? 'an Admin' }}.

YOUR TEMPORARY CREDENTIALS:
Email: {{ $invitation->email }}
Temporary Password: {{ $tempPassword }}

IMPORTANT: You will be required to change this password when you first sign in.

@if(!empty($invitation->permissions))
ASSIGNED PERMISSIONS:
@foreach($invitation->permissions as $permission)
- {{ $permission }}
@endforeach

@endif
To accept this invitation and create your admin account, please visit:

{{ $acceptUrl }}

EXPIRY NOTICE: Your invitation remains active for 14 days from the date it was sent (expires on {{ $invitation->expires_at->format('F d, Y') }}).

---
This email was sent from {{ $tenantName }} via LegitBooks
If you did not expect this invitation, please ignore this email.

