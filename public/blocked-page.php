<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akses Diblokir</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px}
.knk-blk{max-width:420px;width:100%;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.10);padding:40px 32px;text-align:center}
.knk-blk-icon{width:72px;height:72px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 24px}
.knk-blk-icon svg{width:36px;height:36px;stroke:#dc2626;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
.knk-blk-title{font-size:20px;font-weight:800;color:#111827;margin-bottom:12px}
.knk-blk-msg{font-size:14px;color:#6b7280;line-height:1.7}
</style>
</head>
<body>
<div class="knk-blk">
  <div class="knk-blk-icon">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
  </div>
  <h1 class="knk-blk-title">Akses Diblokir</h1>
  <p class="knk-blk-msg"><?php echo esc_html( $message ); ?></p>
</div>
</body>
</html>
