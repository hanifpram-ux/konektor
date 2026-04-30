<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $campaign->product_name ?: $campaign->name ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( home_url( '/' . Konektor_Helper::get_setting('base_slug','konektor') . '/assets/form.css' ) ); ?>">
<?php echo $pixel_head; ?>
</head>
<body>
<div class="konektor-page-wrap">
    <div class="konektor-form-wrap konektor-tpl-<?php echo esc_attr( $config['template'] ?? 'modern' ); ?>">
        <?php if ( $campaign->store_name || $campaign->product_name ) : ?>
        <div class="konektor-form-header">
            <?php if ( $campaign->store_name )  echo '<p class="konektor-store">'  . esc_html( $campaign->store_name )  . '</p>'; ?>
            <?php if ( $campaign->product_name ) echo '<h2 class="konektor-product">' . esc_html( $campaign->product_name ) . '</h2>'; ?>
        </div>
        <?php endif; ?>

        <form class="konektor-form" method="POST" action="<?php echo esc_url( $submit_url ); ?>">
            <input type="hidden" name="_vid" id="knk-vid-field" value="">
            <input type="hidden" name="source_url" id="knk-source-url" value="<?php echo esc_attr( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>">
            <?php foreach ( $config['fields'] as $field ) :
                if ( empty( $field['enabled'] ) ) continue;
                $name = esc_attr( $field['name'] );
                $req  = ! empty( $field['required'] ) ? 'required' : '';
                $ph   = esc_attr( $field['placeholder'] ?? $field['label'] );
                $type = esc_attr( $field['type'] ?? 'text' );
            ?>
            <div class="konektor-field">
                <label><?php echo esc_html( $field['label'] ); ?><?php if ( $req ) echo '<span class="req">*</span>'; ?></label>
                <?php if ( $type === 'textarea' ) : ?>
                    <textarea name="<?php echo $name; ?>" placeholder="<?php echo $ph; ?>" <?php echo $req; ?>></textarea>
                <?php elseif ( $type === 'select' ) : ?>
                    <select name="<?php echo $name; ?>" <?php echo $req; ?>>
                        <option value="">-- Pilih --</option>
                        <?php foreach ( $field['options'] ?? [] as $opt ) : ?>
                        <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input type="<?php echo $type; ?>" name="<?php echo $name; ?>" placeholder="<?php echo $ph; ?>" <?php echo $req; ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php foreach ( $config['extra_fields'] ?? [] as $ef ) :
                if ( empty( $ef['enabled'] ) ) continue;
                $ename = 'extra_' . sanitize_key( $ef['label'] );
                $ereq  = ! empty( $ef['required'] ) ? 'required' : '';
            ?>
            <div class="konektor-field">
                <label><?php echo esc_html( $ef['label'] ); ?><?php if($ereq) echo '<span class="req">*</span>'; ?></label>
                <input type="<?php echo esc_attr( $ef['type'] ?? 'text' ); ?>" name="<?php echo esc_attr($ename); ?>" <?php echo $ereq; ?>>
            </div>
            <?php endforeach; ?>

            <div class="konektor-field">
                <button type="submit" class="konektor-btn-submit">
                    <?php echo esc_html( $config['submit_label'] ?? 'Kirim Sekarang' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
  // Ambil atau buat VID dari cookie, inject ke hidden field
  function getCookie(n) {
    var m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)');
    return m ? m.pop() : '';
  }
  function setCookie(n, v, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 864e5);
    document.cookie = n + '=' + v + ';expires=' + d.toUTCString() + ';path=/';
  }
  var vid = getCookie('konektor_vid');
  if (!vid) {
    vid = Math.random().toString(36).slice(2) + Date.now().toString(36);
    setCookie('konektor_vid', vid, 365);
  }
  var el = document.getElementById('knk-vid-field');
  if (el) el.value = vid;
})();
</script>
</body>
</html>
