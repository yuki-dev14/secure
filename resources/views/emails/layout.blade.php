<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $subject }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .wrapper { max-width: 600px; margin: 40px auto; }
    .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); padding: 36px 40px; text-align: center; }
    .header-logo { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .logo-icon { width: 48px; height: 48px; background: rgba(255,255,255,0.15); border-radius: 12px;
                 display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .header-title { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
    .header-subtitle { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; }
    .body { padding: 36px 40px; }
    .greeting { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
    .intro { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
    .alert-box { border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
    .alert-box.info    { background: #eff6ff; border-left: 4px solid #2563eb; }
    .alert-box.success { background: #f0fdf4; border-left: 4px solid #16a34a; }
    .alert-box.warning { background: #fffbeb; border-left: 4px solid #d97706; }
    .alert-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
    .detail-row { display: flex; justify-content: space-between; align-items: flex-start;
                  padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 13px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #64748b; font-weight: 500; min-width: 140px; }
    .detail-value { color: #0f172a; font-weight: 600; text-align: right; }
    .cta-btn { display: block; background: linear-gradient(135deg, #1e3a5f, #2563eb);
               color: #ffffff !important; text-decoration: none; text-align: center;
               padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600;
               margin: 28px 0; }
    .note { background: #f8fafc; border-radius: 10px; padding: 16px 20px;
            font-size: 12px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
    .note strong { color: #374151; }
    .footer { background: #f8fafc; padding: 24px 40px; text-align: center;
              border-top: 1px solid #e2e8f0; }
    .footer p { font-size: 11px; color: #94a3b8; line-height: 1.6; }
    .footer .brand { font-weight: 700; color: #1e3a5f; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <!-- Header -->
      <div class="header">
        <div class="header-logo">
          <div class="logo-icon">🛡️</div>
        </div>
        <div class="header-title">SECURE 4Ps</div>
        <div class="header-subtitle">Social Welfare ID & Compliance Unified Record Engine</div>
        <div class="header-subtitle" style="margin-top:8px;">DSWD — Lipa City, Batangas</div>
      </div>

      <!-- Body -->
      <div class="body">
        <p class="greeting">{{ $greeting }}</p>
        <p class="intro">{{ $introLine }}</p>

        @if (!empty($details))
        <div class="alert-box {{ $alertType ?? 'info' }}">
          @if (!empty($detailsTitle))
          <p class="alert-title">{{ $detailsTitle }}</p>
          @endif
          @foreach ($details as $label => $value)
          <div class="detail-row">
            <span class="detail-label">{{ $label }}</span>
            <span class="detail-value">{{ $value }}</span>
          </div>
          @endforeach
        </div>
        @endif

        @if (!empty($actionUrl))
        <a href="{{ $actionUrl }}" class="cta-btn">{{ $actionText ?? 'View Details' }}</a>
        @endif

        @if (!empty($noteLines))
        <div class="note">
          @foreach ($noteLines as $line)
          <p>{{ $line }}</p>
          @endforeach
        </div>
        @endif

        <p style="font-size:13px;color:#64748b;line-height:1.7;">
          If you have questions, please contact your assigned Barangay Social Welfare office or
          call DSWD Lipa City at <strong>(043) XXX-XXXX</strong>.
        </p>
      </div>

      <!-- Footer -->
      <div class="footer">
        <p>This is an automated message from the <span class="brand">SECURE 4Ps</span> system.</p>
        <p>Please do not reply to this email. This mailbox is not monitored.</p>
        <p style="margin-top:8px;">© {{ date('Y') }} DSWD — Lipa City Division &nbsp;·&nbsp; Batangas, Philippines</p>
      </div>
    </div>
    <p style="text-align:center;font-size:11px;color:#94a3b8;margin-top:16px;">
      You are receiving this because you are a registered 4Ps beneficiary in the SECURE system.
    </p>
  </div>
</body>
</html>
