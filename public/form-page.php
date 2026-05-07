<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $campaign->product_name ?: $campaign->name ); ?></title>
<meta name="robots" content="noindex,nofollow">
<?php echo $pixel_head; ?>
<?php
// ─── Same 9-scheme color system as standalone form.php ─────────────────────
$cfg    = $config;
$cs     = $cfg['custom_style'] ?? [];
$tpl    = $cfg['template']     ?? 'modern';
$size   = $cfg['size']         ?? 'default';
$btnLabel = $cfg['submit_label'] ?? 'Kirim Sekarang';
$fields   = array_merge( $cfg['fields'] ?? [], $cfg['extra_fields'] ?? [] );

$schemes = [
    'modern'   => ['bg'=>'#ffffff','card'=>'#ffffff','accent'=>'#2563eb','text'=>'#0f172a','border'=>'#e2e8f0','btn_text'=>'#ffffff','input_bg'=>'#f8fafc','shadow'=>'0 4px 24px rgba(0,0,0,.08)'],
    'classic'  => ['bg'=>'#fff8f0','card'=>'#ffffff','accent'=>'#dc2626','text'=>'#1a1a1a','border'=>'#d4a27a','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'0 2px 16px rgba(220,38,38,.10)'],
    'minimal'  => ['bg'=>'#fafafa','card'=>'#ffffff','accent'=>'#111111','text'=>'#111111','border'=>'#e2e8f0','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'none'],
    'card'     => ['bg'=>'#eff6ff','card'=>'#ffffff','accent'=>'#2563eb','text'=>'#0f172a','border'=>'#bfdbfe','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'0 8px 32px rgba(37,99,235,.12)'],
    'gradient' => ['bg'=>'linear-gradient(135deg,#1e3a5f,#0f2027)','card'=>'rgba(255,255,255,.08)','accent'=>'#38bdf8','text'=>'#e0f2fe','border'=>'rgba(255,255,255,.20)','btn_text'=>'#0f172a','input_bg'=>'rgba(255,255,255,.10)','shadow'=>'0 8px 40px rgba(0,0,0,.3)'],
    'rose'     => ['bg'=>'#fff1f2','card'=>'#ffffff','accent'=>'#e11d48','text'=>'#0f172a','border'=>'#fecdd3','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'0 4px 20px rgba(225,29,72,.10)'],
    'forest'   => ['bg'=>'#f0fdf4','card'=>'#ffffff','accent'=>'#16a34a','text'=>'#0f172a','border'=>'#bbf7d0','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'0 4px 20px rgba(22,163,74,.10)'],
    'sunset'   => ['bg'=>'linear-gradient(135deg,#ff6b6b,#feca57)','card'=>'rgba(255,255,255,.95)','accent'=>'#ee5a24','text'=>'#2d3436','border'=>'rgba(238,90,36,.3)','btn_text'=>'#ffffff','input_bg'=>'#fff','shadow'=>'0 8px 32px rgba(238,90,36,.15)'],
    'ocean'    => ['bg'=>'linear-gradient(135deg,#0077b6,#00b4d8)','card'=>'rgba(255,255,255,.12)','accent'=>'#48cae4','text'=>'#caf0f8','border'=>'rgba(255,255,255,.25)','btn_text'=>'#003049','input_bg'=>'rgba(255,255,255,.12)','shadow'=>'0 8px 40px rgba(0,119,182,.3)'],
];
$scheme = $schemes[ $tpl ] ?? $schemes['modern'];

$bg      = ! empty( $cs['color_bg'] )       ? $cs['color_bg']       : $scheme['bg'];
$card    = ! empty( $cs['card_bg'] )         ? $cs['card_bg']        : $scheme['card'];
$accent  = ! empty( $cs['color_accent'] )   ? $cs['color_accent']   : $scheme['accent'];
$text    = ! empty( $cs['color_text'] )     ? $cs['color_text']     : $scheme['text'];
$border  = ! empty( $cs['color_border'] )   ? $cs['color_border']   : $scheme['border'];
$btnText = ! empty( $cs['color_btn_text'] ) ? $cs['color_btn_text'] : $scheme['btn_text'];
$inputBg = ! empty( $cs['input_bg'] )       ? $cs['input_bg']       : $scheme['input_bg'];
$shadow  = ! empty( $cs['shadow'] )         ? $cs['shadow']         : $scheme['shadow'];

$sizeMap = [
    'compact'   => ['max_width'=>'380px','padding'=>'20px 18px','fsize'=>'13px','btn_fs'=>'14px','btn_py'=>'11px'],
    'default'   => ['max_width'=>'480px','padding'=>'28px 24px','fsize'=>'14px','btn_fs'=>'15px','btn_py'=>'14px'],
    'large'     => ['max_width'=>'600px','padding'=>'40px 36px','fsize'=>'15px','btn_fs'=>'16px','btn_py'=>'16px'],
    'fullwidth' => ['max_width'=>'100%', 'padding'=>'28px 24px','fsize'=>'14px','btn_fs'=>'15px','btn_py'=>'14px'],
];
$sz      = $sizeMap[ $size ] ?? $sizeMap['default'];
$maxW    = ! empty( $cs['max_width'] )      ? $cs['max_width']      : $sz['max_width'];
$padding = ! empty( $cs['padding'] )        ? $cs['padding']        : $sz['padding'];
$fsize   = ! empty( $cs['font_size'] )      ? $cs['font_size']      : $sz['fsize'];
$btnFs   = ! empty( $cs['btn_font_size'] )  ? $cs['btn_font_size']  : $sz['btn_fs'];
$btnPy   = ! empty( $cs['btn_py'] )         ? $cs['btn_py']         : $sz['btn_py'];
$radius  = ! empty( $cs['border_radius'] )  ? $cs['border_radius']  : '12px';
$fontFam = ! empty( $cs['font_family'] )    ? $cs['font_family']    : '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif';

$vid = isset( $_COOKIE['konektor_vid'] ) ? sanitize_text_field( $_COOKIE['konektor_vid'] ) : '';
?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: <?php echo esc_attr( $fontFam ); ?>;
    background: <?php echo esc_attr( $bg ); ?>;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .knk-wrap { width: 100%; max-width: <?php echo esc_attr( $maxW ); ?>; }
  .knk-card {
    background: <?php echo esc_attr( $card ); ?>;
    border-radius: <?php echo esc_attr( $radius ); ?>;
    box-shadow: <?php echo esc_attr( $shadow ); ?>;
    padding: <?php echo esc_attr( $padding ); ?>;
    color: <?php echo esc_attr( $text ); ?>;
  }
  .knk-store { font-size: 12px; color: <?php echo esc_attr( $accent ); ?>; text-align: center; margin-bottom: 6px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; }
  .knk-product { font-size: 20px; font-weight: 800; text-align: center; margin-bottom: 4px; color: <?php echo esc_attr( $text ); ?>; }
  .knk-desc { font-size: 13px; color: <?php echo esc_attr( $text ); ?>; opacity: .65; text-align: center; margin-bottom: 22px; }
  .knk-divider { height: 1px; background: <?php echo esc_attr( $border ); ?>; margin: 16px 0; }
  .knk-field { margin-bottom: 14px; }
  .knk-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: <?php echo esc_attr( $text ); ?>; }
  .knk-req { color: #ef4444; margin-left: 2px; }
  .knk-input, .knk-textarea, .knk-select {
    width: 100%; padding: 10px 12px; font-size: <?php echo esc_attr( $fsize ); ?>;
    border: 1.5px solid <?php echo esc_attr( $border ); ?>; border-radius: 8px;
    background: <?php echo esc_attr( $inputBg ); ?>; color: <?php echo esc_attr( $text ); ?>;
    outline: none; font-family: inherit; transition: border-color .15s;
  }
  .knk-input:focus, .knk-textarea:focus, .knk-select:focus { border-color: <?php echo esc_attr( $accent ); ?>; }
  .knk-textarea { resize: vertical; min-height: 80px; }
  .knk-radio-wrap, .knk-check-wrap { display: inline-flex; align-items: center; gap: 6px; margin-right: 12px; margin-bottom: 4px; cursor: pointer; font-size: <?php echo esc_attr( $fsize ); ?>; color: <?php echo esc_attr( $text ); ?>; }
  .knk-btn {
    display: block; width: 100%;
    padding: <?php echo esc_attr( $btnPy ); ?> 20px;
    font-size: <?php echo esc_attr( $btnFs ); ?>; font-weight: 700;
    background: <?php echo esc_attr( $accent ); ?>; color: <?php echo esc_attr( $btnText ); ?>;
    border: none; border-radius: 8px; cursor: pointer;
    font-family: inherit; margin-top: 6px; transition: opacity .15s, transform .1s;
  }
  .knk-btn:hover { opacity: .88; transform: translateY(-1px); }
  .knk-alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 13px; font-weight: 600; }
  .knk-alert-error { background: #fee2e2; color: #b91c1c; }
  .knk-alert-warn  { background: #fef9c3; color: #854d0e; }
  .knk-hidden { display: none !important; }
  @media (max-width: 480px) { body { padding: 12px; } }
</style>
</head>
<body>

<div class="knk-wrap">
  <div class="knk-card">
    <?php if ( $campaign->store_name ) : ?>
    <p class="knk-store"><?php echo esc_html( $campaign->store_name ); ?></p>
    <?php endif; ?>
    <?php if ( $campaign->product_name ) : ?>
    <p class="knk-product"><?php echo esc_html( $campaign->product_name ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $cs['tagline'] ) ) : ?>
    <p class="knk-desc"><?php echo esc_html( $cs['tagline'] ); ?></p>
    <?php endif; ?>
    <?php if ( $campaign->store_name || $campaign->product_name ) : ?>
    <div class="knk-divider"></div>
    <?php endif; ?>

    <div class="knk-alert knk-alert-error knk-hidden" id="knk-alert"></div>

    <form action="<?php echo esc_url( $submit_url ); ?>" method="POST" id="knk-form">
      <input type="hidden" name="_vid" id="knk-vid" value="<?php echo esc_attr( $vid ); ?>">
      <input type="hidden" name="source_url" value="<?php echo esc_url( home_url( $_SERVER['REQUEST_URI'] ) ); ?>">
      <input type="hidden" name="referrer" value="<?php echo esc_url( $_SERVER['HTTP_REFERER'] ?? '' ); ?>">
      <?php
      $click_id = sanitize_text_field( $_GET['click_id'] ?? ( $_GET['clickid'] ?? '' ) );
      if ( $click_id ) :
      ?>
      <input type="hidden" name="click_id" value="<?php echo esc_attr( $click_id ); ?>">
      <?php endif; ?>

      <?php foreach ( $fields as $f ) :
        if ( empty( $f['enabled'] ) ) continue;
        $fname   = esc_attr( $f['name'] ?? '' );
        $label   = esc_html( $f['label'] ?? '' );
        $ph      = esc_attr( $f['placeholder'] ?? ( $f['label'] ?? '' ) );
        $type    = esc_attr( $f['type'] ?? 'text' );
        $req     = ! empty( $f['required'] );
        $reqAttr = $req ? ' required' : '';
        $reqMark = $req ? '<span class="knk-req">*</span>' : '';
        $opts    = $f['options'] ?? [];
        $extra   = ( $fname === 'phone' ) ? ' inputmode="numeric" autocomplete="tel"' : '';
      ?>
      <div class="knk-field">
        <label class="knk-label"><?php echo $label; ?> <?php echo $reqMark; ?></label>
        <?php if ( $type === 'textarea' ) : ?>
          <textarea name="<?php echo $fname; ?>" rows="3" placeholder="<?php echo $ph; ?>" class="knk-textarea"<?php echo $reqAttr; ?>></textarea>
        <?php elseif ( $type === 'select' && ! empty( $opts ) ) : ?>
          <select name="<?php echo $fname; ?>" class="knk-select"<?php echo $reqAttr; ?>>
            <option value="">-- Pilih --</option>
            <?php foreach ( $opts as $opt ) : ?>
            <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
            <?php endforeach; ?>
          </select>
        <?php elseif ( $type === 'radio' && ! empty( $opts ) ) : ?>
          <?php foreach ( $opts as $opt ) : ?>
          <label class="knk-radio-wrap">
            <input type="radio" name="<?php echo $fname; ?>" value="<?php echo esc_attr( $opt ); ?>"<?php echo $reqAttr; ?>>
            <?php echo esc_html( $opt ); ?>
          </label>
          <?php endforeach; ?>
        <?php elseif ( $type === 'checkbox' && ! empty( $opts ) ) : ?>
          <?php foreach ( $opts as $opt ) : ?>
          <label class="knk-check-wrap">
            <input type="checkbox" name="<?php echo $fname; ?>[]" value="<?php echo esc_attr( $opt ); ?>">
            <?php echo esc_html( $opt ); ?>
          </label>
          <?php endforeach; ?>
        <?php else : ?>
          <input type="<?php echo $type; ?>" name="<?php echo $fname; ?>" placeholder="<?php echo $ph; ?>" class="knk-input"<?php echo $reqAttr . $extra; ?>>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div class="knk-field">
        <button type="submit" class="knk-btn" id="knk-submit">
          <span id="knk-btn-text"><?php echo esc_html( $btnLabel ); ?></span>
          <span id="knk-btn-load" class="knk-hidden">Mengirim...</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  // VID cookie
  function getCookie(n) { var m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return m ? m.pop() : ''; }
  function setCookie(n, v, d) { var x = new Date(); x.setTime(x.getTime() + d*864e5); document.cookie = n+'='+v+';expires='+x.toUTCString()+';path=/'; }
  var vid = getCookie('konektor_vid');
  if (!vid) { vid = Math.random().toString(36).slice(2) + Date.now().toString(36); setCookie('konektor_vid', vid, 365); }
  var vf = document.getElementById('knk-vid');
  if (vf) vf.value = vid;

  var form    = document.getElementById('knk-form');
  var alertEl = document.getElementById('knk-alert');
  var btnText = document.getElementById('knk-btn-text');
  var btnLoad = document.getElementById('knk-btn-load');

  if (!form) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    btnText.classList.add('knk-hidden');
    btnLoad.classList.remove('knk-hidden');
    alertEl.className = 'knk-alert knk-hidden';

    var data = {};
    new FormData(form).forEach(function(v, k) { data[k] = v; });
    data.source_url = window.location.href;
    data._vid = vid;

    fetch(form.action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        // Redirect ke thanks page (server sudah build URL-nya)
        if (res.thanks_page_url) {
          window.location.href = res.thanks_page_url;
        } else {
          form.classList.add('knk-hidden');
          alertEl.textContent = res.message || 'Terima kasih!';
          alertEl.className = 'knk-alert';
          alertEl.style.background = '#dcfce7';
          alertEl.style.color = '#166534';
        }
      } else {
        alertEl.textContent = res.message || 'Terjadi kesalahan.';
        alertEl.className = 'knk-alert ' + (res.blocked ? 'knk-alert-error' : (res.double ? 'knk-alert-warn' : 'knk-alert-error'));
        btnText.classList.remove('knk-hidden');
        btnLoad.classList.add('knk-hidden');
        if (res.blocked) form.classList.add('knk-hidden');
      }
    })
    .catch(function() {
      alertEl.textContent = 'Koneksi gagal. Coba lagi.';
      alertEl.className = 'knk-alert knk-alert-error';
      btnText.classList.remove('knk-hidden');
      btnLoad.classList.add('knk-hidden');
    });
  });
})();
</script>
</body>
</html>
