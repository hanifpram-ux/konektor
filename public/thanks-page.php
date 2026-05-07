<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $campaign->product_name ?: $campaign->name ); ?></title>
<?php echo $pixel_head; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: #f8fafc;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 24px;
}
.tk-card {
  width: 100%; max-width: 440px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,.08);
  padding: 44px 36px 40px;
  text-align: center;
  color: #0f172a;
}
.tk-icon-wrap {
  width: 72px; height: 72px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px;
  background: <?php echo esc_attr( $icon_bg ); ?>;
}
.tk-icon-wrap svg {
  width: 36px; height: 36px;
  stroke: <?php echo esc_attr( $accent ); ?>;
  fill: none; stroke-width: 2.5;
  stroke-linecap: round; stroke-linejoin: round;
}
.tk-heading {
  font-size: 22px; font-weight: 800;
  color: #0f172a; margin-bottom: 10px; line-height: 1.3;
}
.tk-msg {
  font-size: 14px; line-height: 1.7;
  color: #0f172a; opacity: .68;
  margin-bottom: 28px;
}
.tk-double {
  background: #fef9c3; border: 1px solid #fde68a;
  border-radius: 8px; padding: 10px 14px;
  font-size: 13px; color: #92400e;
  margin-bottom: 20px; line-height: 1.55;
}
.tk-progress-wrap {
  height: 3px; background: rgba(0,0,0,.07);
  border-radius: 999px; overflow: hidden; margin-bottom: 10px;
}
.tk-progress-bar {
  height: 100%; width: 0;
  background: <?php echo esc_attr( $accent ); ?>;
  border-radius: 999px;
}
.tk-countdown {
  font-size: 12px; font-weight: 600;
  color: <?php echo esc_attr( $accent ); ?>;
  margin-bottom: 20px;
}
.tk-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 9px;
  width: 100%; padding: 14px 20px;
  background: <?php echo esc_attr( $accent ); ?>; color: #fff;
  border: none; border-radius: 10px;
  font-size: 15px; font-weight: 700;
  cursor: pointer; text-decoration: none; font-family: inherit;
  transition: opacity .18s, transform .12s;
}
.tk-btn:hover { opacity: .88; transform: translateY(-1px); }
.tk-btn svg { width: 18px; height: 18px; flex-shrink: 0; fill: currentColor; }
@media (max-width: 480px) {
  body { padding: 16px; }
  .tk-card { padding: 36px 24px 32px; }
}
</style>
</head>
<body>
<div class="tk-card">

  <div class="tk-icon-wrap">
    <?php if ( $is_double ) : ?>
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
    <?php else : ?>
    <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
    <?php endif; ?>
  </div>

  <?php if ( $is_double ) : ?>
  <div class="tk-double"><?php echo esc_html( $campaign->double_lead_message ?: 'Anda sudah pernah mengisi data sebelumnya.' ); ?></div>
  <?php endif; ?>

  <h1 class="tk-heading"><?php echo esc_html( $title ); ?></h1>
  <?php if ( $message ) : ?>
  <p class="tk-msg"><?php echo nl2br( esc_html( $message ) ); ?></p>
  <?php endif; ?>

  <?php if ( $redirect_url ) : ?>
  <div class="tk-progress-wrap"><div class="tk-progress-bar" id="tkBar"></div></div>
  <p class="tk-countdown">Menghubungkan dalam <strong id="tkSec"><?php echo (int) $delay; ?></strong> detik...</p>

  <a href="<?php echo esc_url( $redirect_url ); ?>" class="tk-btn" id="tkBtn">
    <?php
    if ( strpos( $redirect_url, 'wa.me' ) !== false || strpos( $redirect_url, 'whatsapp' ) !== false ) :
    ?>
    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.852L0 24l6.335-1.508A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.002-1.368l-.36-.214-3.72.885.916-3.617-.234-.372A9.818 9.818 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
    <?php echo esc_html( $btn_label ?: 'Chat via WhatsApp' ); ?>
    <?php elseif ( strpos( $redirect_url, 't.me' ) !== false || strpos( $redirect_url, 'telegram' ) !== false ) : ?>
    <svg viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-1.97 9.284c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.92 14.41l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.836.176z"/></svg>
    <?php echo esc_html( $btn_label ?: 'Chat via Telegram' ); ?>
    <?php elseif ( strpos( $redirect_url, 'mailto:' ) !== false ) : ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="fill:none"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
    <?php echo esc_html( $btn_label ?: 'Hubungi via Email' ); ?>
    <?php elseif ( strpos( $redirect_url, 'line.me' ) !== false ) : ?>
    <svg viewBox="0 0 24 24"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
    <?php echo esc_html( $btn_label ?: 'Chat via LINE' ); ?>
    <?php else : ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="fill:none"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    <?php echo esc_html( $btn_label ?: 'Lanjutkan' ); ?>
    <?php endif; ?>
  </a>

  <script>
  (function(){
    var delay = <?php echo (int) $delay; ?>;
    var url   = <?php echo wp_json_encode( $redirect_url ); ?>;
    var bar   = document.getElementById('tkBar');
    var sec   = document.getElementById('tkSec');
    var total = delay * 1000, elapsed = 0, iv = 50;
    if (delay <= 0) { location.href = url; return; }
    var t = setInterval(function(){
      elapsed += iv;
      var pct = Math.min(elapsed / total * 100, 100);
      if (bar) bar.style.width = pct + '%';
      if (sec) sec.textContent = Math.max(0, Math.ceil((total - elapsed) / 1000));
      if (elapsed >= total) { clearInterval(t); location.href = url; }
    }, iv);
  })();
  </script>

  <?php endif; ?>

</div>
</body>
</html>
