/**
 * Konektor Embed Script
 * Usage: <script src="…/konektor/assets/form.js"
 *                 data-action="…/konektor/{slug}/submit"
 *                 data-container="#my-form-wrap"></script>
 *
 * Form config (fields, template, colors) di-fetch dari /config endpoint.
 * Warna sepenuhnya mengikuti pengaturan kampanye — konsisten dengan halaman form standalone.
 */
(function () {
    var script    = document.currentScript;
    var actionUrl = script.getAttribute('data-action');
    var container = script.getAttribute('data-container') || '#konektor-form-wrap';
    var el        = document.querySelector(container);

    if (!el || !actionUrl) return;

    var configUrl = actionUrl.replace(/\/submit\/?$/, '/config');

    // ── VID cookie ───────────────────────────────────────────────────────
    function getCookie(n) { var m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return m ? m.pop() : ''; }
    function setCookie(n, v, days) { var d = new Date(); d.setTime(d.getTime() + days * 864e5); document.cookie = n + '=' + v + ';expires=' + d.toUTCString() + ';path=/'; }
    var vid = getCookie('konektor_vid');
    if (!vid) { vid = Math.random().toString(36).slice(2) + Date.now().toString(36); setCookie('konektor_vid', vid, 365); }

    // ── Server-side page_load pixel ──────────────────────────────────────
    var pixelUrl = actionUrl.replace(/\/submit\/?$/, '/pixel');
    var clickId  = '';
    try { var qs = new URLSearchParams(window.location.search); clickId = qs.get('click_id') || qs.get('clickid') || ''; } catch (e) {}
    var pqs = '?url=' + encodeURIComponent(window.location.href);
    if (clickId) pqs += '&click_id=' + encodeURIComponent(clickId);
    if (vid)     pqs += '&_vid='     + encodeURIComponent(vid);
    fetch(pixelUrl + pqs, { mode: 'cors' }).catch(function () {});

    // ── Fetch config then render ─────────────────────────────────────────
    fetch(configUrl)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) return;
            renderForm(el, actionUrl, data.form_config, data.type);
        })
        .catch(function () {
            el.innerHTML = '<p style="color:#b91c1c;font-size:14px;padding:12px;">Gagal memuat form.</p>';
        });

    function renderForm(el, actionUrl, cfg, campType) {
        var tpl      = cfg.template     || 'modern';
        var size     = cfg.size         || 'default';
        var btnLabel = cfg.submit_label || 'Kirim Sekarang';
        var cs       = cfg.custom_style || {};
        var fields   = [].concat(cfg.fields || [], cfg.extra_fields || []);

        // ── Same scheme system as standalone form.php ────────────────────
        var schemes = {
            modern:        { bg:'#ffffff',   card:'#ffffff',               accent:'#2563eb', text:'#0f172a', border:'#e2e8f0',              btnText:'#ffffff', inputBg:'#f8fafc', shadow:'0 4px 24px rgba(0,0,0,.08)' },
            classic:       { bg:'#fff8f0',   card:'#ffffff',               accent:'#dc2626', text:'#1a1a1a', border:'#d4a27a',              btnText:'#ffffff', inputBg:'#fff',    shadow:'0 2px 16px rgba(220,38,38,.10)' },
            classic_flat:  { bg:'#b7d09a',   card:'#b7d09a',               accent:'#FF5000', text:'#000000', border:'#ccc',                 btnText:'#ffffff', inputBg:'#fff',    shadow:'none' },
            minimal:       { bg:'#fafafa',   card:'#ffffff',               accent:'#111111', text:'#111111', border:'#e2e8f0',              btnText:'#ffffff', inputBg:'#fff',    shadow:'none' },
            card:          { bg:'#eff6ff',   card:'#ffffff',               accent:'#2563eb', text:'#0f172a', border:'#bfdbfe',              btnText:'#ffffff', inputBg:'#fff',    shadow:'0 8px 32px rgba(37,99,235,.12)' },
            gradient:      { bg:'linear-gradient(135deg,#1e3a5f,#0f2027)', card:'rgba(255,255,255,.08)', accent:'#38bdf8', text:'#e0f2fe', border:'rgba(255,255,255,.20)', btnText:'#0f172a', inputBg:'rgba(255,255,255,.10)', shadow:'0 8px 40px rgba(0,0,0,.3)' },
            rose:          { bg:'#fff1f2',   card:'#ffffff',               accent:'#e11d48', text:'#0f172a', border:'#fecdd3',              btnText:'#ffffff', inputBg:'#fff',    shadow:'0 4px 20px rgba(225,29,72,.10)' },
            forest:        { bg:'#f0fdf4',   card:'#ffffff',               accent:'#16a34a', text:'#0f172a', border:'#bbf7d0',              btnText:'#ffffff', inputBg:'#fff',    shadow:'0 4px 20px rgba(22,163,74,.10)' },
            sunset:        { bg:'linear-gradient(135deg,#ff6b6b,#feca57)',  card:'rgba(255,255,255,.95)', accent:'#ee5a24', text:'#2d3436', border:'rgba(238,90,36,.3)',  btnText:'#ffffff', inputBg:'#fff',    shadow:'0 8px 32px rgba(238,90,36,.15)' },
            ocean:         { bg:'linear-gradient(135deg,#0077b6,#00b4d8)',  card:'rgba(255,255,255,.12)', accent:'#48cae4', text:'#caf0f8', border:'rgba(255,255,255,.25)', btnText:'#003049', inputBg:'rgba(255,255,255,.12)', shadow:'0 8px 40px rgba(0,119,182,.3)' },
        };
        var sizes = {
            compact:   { maxW:'380px', pad:'20px 18px', fsize:'13px', bfsize:'14px', bpy:'11px' },
            'default': { maxW:'480px', pad:'28px 24px', fsize:'14px', bfsize:'15px', bpy:'14px' },
            large:     { maxW:'600px', pad:'40px 36px', fsize:'15px', bfsize:'16px', bpy:'16px' },
            fullwidth: { maxW:'100%',  pad:'28px 24px', fsize:'14px', bfsize:'15px', bpy:'14px' },
        };
        var sc = schemes[tpl] || schemes.modern;
        var sz = sizes[size]  || sizes['default'];

        var bg      = cs.color_bg       || sc.bg;
        var card    = cs.card_bg        || sc.card;
        var accent  = cs.color_accent   || sc.accent;
        var text    = cs.color_text     || sc.text;
        var border  = cs.color_border   || sc.border;
        var btnText = cs.color_btn_text || sc.btnText;
        var inputBg = cs.input_bg       || sc.inputBg;
        var shadow  = cs.shadow         || sc.shadow;
        var maxW    = cs.max_width      || sz.maxW;
        var pad     = cs.padding        || sz.pad;
        var fsize   = cs.font_size      || sz.fsize;
        var bfsize  = cs.btn_font_size  || sz.bfsize;
        var bpy     = cs.btn_py         || sz.bpy;
        var isClassicFlat = (tpl === 'classic_flat');
        var radius  = cs.border_radius  || (isClassicFlat ? '3px' : '12px');
        var tagline = cs.tagline        || '';

        // ── Inject scoped <style> ─────────────────────────────────────────
        var uid = 'knk' + Math.random().toString(36).slice(2, 7);
        var style = document.createElement('style');
        style.textContent = [
            '#' + uid + ' .konektor-embed-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;width:100%;}',
            '#' + uid + ' .knk-embed-card{background:' + card + ';border-radius:' + radius + ';box-shadow:' + shadow + ';padding:' + pad + ';color:' + text + ';}',
            '#' + uid + ' .knk-embed-store{font-size:12px;color:' + accent + ';text-align:center;margin-bottom:6px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;}',
            '#' + uid + ' .knk-embed-product{font-size:20px;font-weight:800;text-align:center;margin-bottom:4px;color:' + text + ';}',
            '#' + uid + ' .knk-embed-desc{font-size:13px;color:' + text + ';opacity:.65;text-align:center;margin-bottom:22px;}',
            '#' + uid + ' .knk-embed-divider{height:1px;background:' + border + ';margin:12px 0 16px;}',
            '#' + uid + ' .knk-embed-field{margin-bottom:14px;}',
            '#' + uid + ' .knk-embed-input,#' + uid + ' .knk-embed-textarea,#' + uid + ' .knk-embed-select{width:100%;' + (isClassicFlat ? 'height:36px;padding:0 8px;' : 'padding:10px 12px;') + 'font-size:' + fsize + ';border:' + (isClassicFlat ? '1px' : '1.5px') + ' solid ' + border + ';border-radius:' + (isClassicFlat ? '3px' : '8px') + ';background:' + inputBg + ';color:' + text + ';outline:none;font-family:inherit;transition:border-color .15s' + (isClassicFlat ? ',box-shadow .15s' : '') + ';}',
            '#' + uid + ' .knk-embed-input:focus,#' + uid + ' .knk-embed-textarea:focus,#' + uid + ' .knk-embed-select:focus{border-color:' + (isClassicFlat ? '#81bd4b' : accent) + ';' + (isClassicFlat ? 'box-shadow:0 0 0 2px rgba(129,189,75,0.2);' : '') + '}',
            '#' + uid + ' .knk-embed-textarea{resize:vertical;min-height:80px;' + (isClassicFlat ? 'height:auto;padding:8px;' : '') + '}',
            '#' + uid + ' .knk-embed-radio-wrap,#' + uid + ' .knk-embed-check-wrap{display:inline-flex;align-items:center;gap:6px;margin-right:12px;margin-bottom:4px;cursor:pointer;font-size:' + fsize + ';color:' + text + ';}',
            '#' + uid + ' .knk-embed-label{display:block;font-size:13px;font-weight:' + (isClassicFlat ? 'bold' : '600') + ';margin-bottom:5px;color:' + text + ';}',
            '#' + uid + ' .knk-embed-req{color:' + (isClassicFlat ? '#B30000' : '#ef4444') + ';margin-left:2px;}',
            '#' + uid + ' .knk-embed-btn{display:block;width:100%;' + (isClassicFlat ? 'height:50px;padding:0 20px;' : 'padding:' + bpy + ' 20px;') + 'font-size:' + bfsize + ';font-weight:700;background:' + accent + ';color:' + btnText + ';border:none;border-radius:' + (isClassicFlat ? '3px' : '8px') + ';cursor:pointer;font-family:inherit;margin-top:6px;transition:opacity .15s,transform .1s;}',
            '#' + uid + ' .knk-embed-btn:hover{' + (isClassicFlat ? 'opacity:1;background:#e04800;transform:none;' : 'opacity:.88;transform:translateY(-1px);') + '}',
            '#' + uid + ' .knk-embed-alert{padding:10px 14px;border-radius:' + (isClassicFlat ? '4px' : '8px') + ';margin-bottom:14px;font-size:13px;font-weight:600;' + (isClassicFlat ? 'text-align:center;' : '') + '}',
            '#' + uid + ' .knk-embed-alert-error{background:' + (isClassicFlat ? '#ffebee' : '#fee2e2') + ';color:' + (isClassicFlat ? '#c62828' : '#b91c1c') + ';' + (isClassicFlat ? 'border:1px solid #ef5350;' : '') + '}',
            '#' + uid + ' .knk-embed-alert-warn{background:#fef9c3;color:#854d0e;}',
            '#' + uid + ' .knk-hidden{display:none!important;}',
        ].join('');
        document.head.appendChild(style);

        // ── Build fields HTML ─────────────────────────────────────────────
        var fieldsHtml = '';
        fields.forEach(function (f) {
            if (!f.enabled) return;
            var fname = esc(f.name || '');
            var label = esc(f.label || '');
            var ph    = esc(f.placeholder || f.label || '');
            var type  = f.type || 'text';
            var req   = f.required ? ' required' : '';
            var reqMk = f.required ? '<span class="knk-embed-req">*</span>' : '';
            var opts  = f.options || [];
            var extra = (fname === 'phone') ? ' inputmode="numeric" autocomplete="tel"' : '';

            fieldsHtml += '<div class="knk-embed-field">';
            fieldsHtml += '<label class="knk-embed-label">' + label + ' ' + reqMk + '</label>';
            if (type === 'textarea') {
                fieldsHtml += '<textarea name="' + fname + '" rows="3" placeholder="' + ph + '" class="knk-embed-textarea"' + req + '></textarea>';
            } else if (type === 'select' && opts.length) {
                fieldsHtml += '<select name="' + fname + '" class="knk-embed-select"' + req + '><option value="">-- Pilih --</option>';
                opts.forEach(function (o) { fieldsHtml += '<option value="' + esc(o) + '">' + esc(o) + '</option>'; });
                fieldsHtml += '</select>';
            } else if (type === 'radio' && opts.length) {
                opts.forEach(function (o) { fieldsHtml += '<label class="knk-embed-radio-wrap"><input type="radio" name="' + fname + '" value="' + esc(o) + '"' + req + '> ' + esc(o) + '</label>'; });
            } else if (type === 'checkbox' && opts.length) {
                opts.forEach(function (o) { fieldsHtml += '<label class="knk-embed-check-wrap"><input type="checkbox" name="' + fname + '[]" value="' + esc(o) + '"> ' + esc(o) + '</label>'; });
            } else {
                fieldsHtml += '<input type="' + esc(type) + '" name="' + fname + '" placeholder="' + ph + '" class="knk-embed-input"' + req + extra + '>';
            }
            fieldsHtml += '</div>';
        });

        // ── Store / product header ─────────────────────────────────────────
        var headerHtml = '';
        if (cfg.store_name)   headerHtml += '<p class="knk-embed-store">' + esc(cfg.store_name) + '</p>';
        if (cfg.product_name) headerHtml += '<p class="knk-embed-product">' + esc(cfg.product_name) + '</p>';
        if (tagline)          headerHtml += '<p class="knk-embed-desc">' + esc(tagline) + '</p>';
        if (headerHtml)       headerHtml += '<div class="knk-embed-divider"></div>';

        // ── Inject HTML ───────────────────────────────────────────────────
        el.id = uid;
        el.innerHTML =
            '<div class="konektor-embed-wrap">' +
              '<div class="knk-embed-card">' +
                headerHtml +
                '<div class="knk-embed-alert knk-embed-alert-error knk-hidden" id="' + uid + '-alert"></div>' +
                '<form id="' + uid + '-form" novalidate>' +
                  fieldsHtml +
                  '<div class="knk-embed-field">' +
                    '<button type="submit" class="knk-embed-btn">' +
                      '<span id="' + uid + '-txt">' + esc(btnLabel) + '</span>' +
                      '<span id="' + uid + '-load" class="knk-hidden">Mengirim...</span>' +
                    '</button>' +
                  '</div>' +
                '</form>' +
              '</div>' +
            '</div>';

        // ── Bind submit ───────────────────────────────────────────────────
        var form    = document.getElementById(uid + '-form');
        var alertEl = document.getElementById(uid + '-alert');
        var btnTxt  = document.getElementById(uid + '-txt');
        var btnLoad = document.getElementById(uid + '-load');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            btnTxt.classList.add('knk-hidden');
            btnLoad.classList.remove('knk-hidden');
            alertEl.className = 'knk-embed-alert knk-embed-alert-error knk-hidden';

            var data = {};
            new FormData(form).forEach(function (v, k) { data[k] = v; });
            data.source_url = window.location.href;
            data.referrer   = document.referrer;
            data._vid       = vid;
            if (clickId) data.click_id = clickId;

            fetch(actionUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(data),
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    // Browser-side pixel (form_submit) — skip platform if CAPI already fired server-side
                    if (!res.double) {
                        if (!res.capi_meta   && typeof fbq  !== 'undefined') try { fbq('track', 'Lead'); } catch (x) {}
                        if (!res.capi_tiktok && typeof ttq  !== 'undefined') try { ttq.track('SubmitForm'); } catch (x) {}
                        if (                    typeof gtag !== 'undefined') try { gtag('event', 'generate_lead'); } catch (x) {}
                    }
                    // Selalu redirect ke thanks page (server sudah build URL)
                    if (res.thanks_page_url) {
                        window.location.href = res.thanks_page_url;
                    } else {
                        // Fallback: tampilkan pesan inline
                        form.classList.add('knk-hidden');
                        alertEl.textContent = res.message || 'Terima kasih!';
                        alertEl.className = 'knk-embed-alert';
                        alertEl.style.background = '#dcfce7';
                        alertEl.style.color = '#166534';
                    }
                } else {
                    alertEl.textContent = res.message || 'Terjadi kesalahan.';
                    alertEl.className = 'knk-embed-alert ' + (res.blocked ? 'knk-embed-alert-error' : (res.double ? 'knk-embed-alert-warn' : 'knk-embed-alert-error'));
                    btnTxt.classList.remove('knk-hidden');
                    btnLoad.classList.add('knk-hidden');
                    if (res.blocked) form.classList.add('knk-hidden');
                }
            })
            .catch(function () {
                alertEl.textContent = 'Koneksi gagal. Coba lagi.';
                alertEl.className = 'knk-embed-alert knk-embed-alert-error';
                btnTxt.classList.remove('knk-hidden');
                btnLoad.classList.add('knk-hidden');
            });
        });
    }

    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
})();
