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

        <div class="konektor-alert konektor-hidden"></div>

        <form class="konektor-form" id="konektor-main-form">
            <?php foreach ( $config['fields'] as $field ) :
                if ( empty( $field['enabled'] ) ) continue;
                $name = esc_attr( $field['name'] );
                $label= esc_html( $field['label'] );
                $req  = ! empty( $field['required'] ) ? 'required' : '';
                $ph   = esc_attr( $field['placeholder'] ?? $field['label'] );
                $type = esc_attr( $field['type'] ?? 'text' );
            ?>
            <div class="konektor-field">
                <label><?php echo $label; ?><?php if ( $req ) echo '<span class="req">*</span>'; ?></label>
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
                $name = 'extra_' . sanitize_key( $ef['label'] );
                $req  = ! empty( $ef['required'] ) ? 'required' : '';
            ?>
            <div class="konektor-field">
                <label><?php echo esc_html( $ef['label'] ); ?><?php if($req) echo '<span class="req">*</span>'; ?></label>
                <input type="<?php echo esc_attr( $ef['type'] ?? 'text' ); ?>" name="<?php echo esc_attr($name); ?>" <?php echo $req; ?>>
            </div>
            <?php endforeach; ?>

            <div class="konektor-field">
                <button type="submit" class="konektor-btn-submit">
                    <span class="konektor-btn-text"><?php echo esc_html( $config['submit_label'] ?? 'Kirim Sekarang' ); ?></span>
                    <span class="konektor-btn-loading konektor-hidden">Mengirim...</span>
                </button>
            </div>
        </form>

        <div class="konektor-thanks konektor-hidden"></div>
    </div>
</div>

<script>
(function() {
    // ── Cookie helper ──────────────────────────────────────────────
    function getCookie(name) {
        var m = document.cookie.match('(?:^|;)\\s*' + name + '=([^;]*)');
        return m ? decodeURIComponent(m[1]) : '';
    }
    function setCookie(name, value, days) {
        document.cookie = name + '=' + encodeURIComponent(value)
            + ';path=/;max-age=' + (days * 86400) + ';SameSite=Lax';
    }
    function getOrCreateVid() {
        var vid = getCookie('konektor_vid');
        if (!vid) {
            var ch = '0123456789abcdef', v = '';
            for (var i = 0; i < 32; i++) v += ch[Math.floor(Math.random() * 16)];
            setCookie('konektor_vid', v, 365);
            vid = v;
        }
        return vid;
    }

    // Set cookie immediately on page load
    var VID = getOrCreateVid();

    var ACTION_URL  = <?php echo wp_json_encode( $submit_url ); ?>;
    var DELAY       = <?php echo (int) ( $thanks_cfg['delay_redirect'] ?? 3 ); ?>;
    var STORAGE_KEY = 'knk_form_<?php echo (int) $campaign->id; ?>';

    var form  = document.getElementById('konektor-main-form');
    var wrap  = form.closest('.konektor-form-wrap');
    var alertEl = wrap.querySelector('.konektor-alert');
    var thanks  = wrap.querySelector('.konektor-thanks');

    // ── Restore saved form data from localStorage ──────────────────
    try {
        var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        if (saved) {
            form.querySelectorAll('input,textarea,select').forEach(function(el) {
                if (el.name && saved[el.name] !== undefined) {
                    el.value = saved[el.name];
                }
            });
        }
    } catch(e) {}

    // ── Save form data to localStorage on input ────────────────────
    form.addEventListener('input', function() {
        try {
            var d = {};
            new FormData(form).forEach(function(v, k) { d[k] = v; });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(d));
        } catch(e) {}
    });

    // ── Submit ─────────────────────────────────────────────────────
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btnText = form.querySelector('.konektor-btn-text');
        var btnLoad = form.querySelector('.konektor-btn-loading');
        btnText.classList.add('konektor-hidden');
        btnLoad.classList.remove('konektor-hidden');
        alertEl.className = 'konektor-alert konektor-hidden';

        // Ensure cookie is set and include VID in payload
        // (server reads $_COOKIE, but some embed scenarios may need it in body)
        var data = { _vid: getOrCreateVid() };
        new FormData(form).forEach(function(v, k) { data[k] = v; });
        data.source_url = window.location.href;

        fetch(ACTION_URL, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:        JSON.stringify(data),
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                // Clear saved data on successful submit
                try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}

                form.classList.add('konektor-hidden');
                thanks.innerHTML = '<p>' + esc(res.message) + '</p>';
                thanks.classList.remove('konektor-hidden');

                <?php if ( ! empty( $pixel_head ) ) : ?>
                if (typeof fbq !== 'undefined') fbq('track', 'Purchase');
                if (typeof ttq !== 'undefined') ttq.track('PlaceAnOrder');
                <?php endif; ?>

                if (res.redirect_url && res.redirect_type !== 'none') {
                    thanks.innerHTML += '<p style="margin-top:12px;font-size:14px;opacity:.7">Mengarahkan dalam ' + res.delay + ' detik...</p>';
                    setTimeout(function() { window.location.href = res.redirect_url; }, (res.delay || 3) * 1000);
                }
            } else {
                alertEl.textContent = res.message || 'Terjadi kesalahan.';
                alertEl.className   = 'konektor-alert ' + (res.blocked ? 'konektor-alert-error' : (res.double ? 'konektor-alert-warning' : 'konektor-alert-error'));
                btnText.classList.remove('konektor-hidden');
                btnLoad.classList.add('konektor-hidden');
                if (res.blocked) { form.classList.add('konektor-hidden'); }
            }
        })
        .catch(function() {
            alertEl.textContent = 'Koneksi gagal. Coba lagi.';
            alertEl.className   = 'konektor-alert konektor-alert-error';
            btnText.classList.remove('konektor-hidden');
            btnLoad.classList.add('konektor-hidden');
        });
    });

    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
})();
</script>
</body>
</html>
