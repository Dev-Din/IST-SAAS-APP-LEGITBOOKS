<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>New message — LegitBooks</title>
  <style>
    body{font-family: "Helvetica Neue", Arial, sans-serif; background:#f8fafc; margin:0; padding:26px; color:#0b1220;}
    .wrap{max-width:700px;margin:0 auto;}
    .banner{background:#392a26;color:#fff;padding:18px;border-radius:8px 8px 0 0;text-align:center;}
    .banner h2{margin:0;font-size:18px;}
    .card{background:#fff;padding:18px;border-radius:0 0 8px 8px;border:1px solid #eef2f7;}
    .meta{color:#6b7280;font-size:13px;margin-bottom:12px;text-align:right;}
    .field{margin-bottom:12px;}
    .label{font-weight:600;color:#334155;margin-bottom:6px;}
    .value{color:#0b1220;}
    .message{background:#f6f7fb;border-radius:6px;padding:12px;border:1px solid #eef4fb;}
    .footer{margin-top:14px;color:#64748b;font-size:13px;text-align:center;}
    .cta{display:inline-block;margin-top:12px;background:#392a26;color:#fff;padding:10px 14px;border-radius:6px;text-decoration:none;font-weight:600;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="banner">
      <h2>LegitBooks — New Enquiry</h2>
    </div>

    <div class="card">
      <div class="meta">{{ $submission->created_at->format('d/m/Y H:i:s') }}</div>

      <div class="field">
        <div class="label">Name</div>
        <div class="value">{{ $submission->name }}</div>
      </div>

      <div class="field">
        <div class="label">Email</div>
        <div class="value">{{ $submission->email }}</div>
      </div>

      <div class="field">
        <div class="label">Company</div>
        <div class="value">{{ $submission->company ?? '—' }}</div>
      </div>

      <div class="field">
        <div class="label">Phone</div>
        <div class="value">{{ $submission->phone ?? '—' }}</div>
      </div>

      <div class="field">
        <div class="label">Message</div>
        <div class="message">{{ $submission->message }}</div>
      </div>

      <div style="text-align:center;">
        <a class="cta" href="http://localhost:8000/">Open in App</a>
      </div>

      <div class="footer">Delivered by LegitBooks — thanks for using our service.</div>
    </div>
  </div>
</body>
</html>

