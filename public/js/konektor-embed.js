/**
 * Konektor Embed Script
 * Usage: <script src="https://site.com/konektor/assets/form.js"
 *                 data-action="https://site.com/konektor/{slug}/submit"
 *                 data-campaign="1"
 *                 data-container="#konektor-form-wrap"></script>
 *
 * Semua config form (fields, template, label) di-fetch dari server.
 */
(function () {
    var script    = document.currentScript;
    var actionUrl = script.getAttribute('data-action');
    var container = script.getAttribute('data-container') || '#konektor-form-wrap';
    var el        = document.querySelector(container);

    if (!el || !actionUrl) return;

    // Derive config URL: replace /submit with /config
    var configUrl = actionUrl.replace(/\/submit\/?$/, '/config');

    fetch(configUrl)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            renderForm(el, actionUrl, data.form_config, data.thanks_config);
        })
        .catch(function() {
            el.innerHTML = '<p style="color:red">Gagal memuat form.</p>';
        });

    function renderForm(el, actionUrl, cfg, thanksCfg) {
        var tpl      = cfg.template || 'modern';
        var fields   = cfg.fields || [];
        var extras   = cfg.extra_fields || [];
        var btnLabel = cfg.submit_label || 'Kirim Sekarang';

        var html = '<div class="konektor-form-wrap konektor-tpl-' + tpl + '">';
        if (cfg.store_name || cfg.product_name) {
            html += '<div class="konektor-form-header">';
            if (cfg.store_name)   html += '<p class="konektor-store">' + esc(cfg.store_name) + '</p>';
            if (cfg.product_name) html += '<h2 class="konektor-product">' + esc(cfg.product_name) + '</h2>';
            html += '</div>';
        }
        html += '<div class="konektor-alert konektor-hidden"></div>';
        html += '<form class="konektor-form" id="konektor-embed-form" novalidate>';

        fields.forEach(function(f) {
            if (!f.enabled) return;
            html += fieldHtml(f.name, f.label, f.type, f.required, f.placeholder, f.options);
        });
        extras.forEach(function(f) {
            if (!f.enabled) return;
            var name = 'extra_' + f.label.toLowerCase().replace(/\s+/g,'_');
            html += fieldHtml(name, f.label, f.type, f.required, f.placeholder, f.options);
        });

        html += '<div class="konektor-field">';
        html += '<button type="submit" class="konektor-btn-submit">';
        html += '<span class="konektor-btn-text">' + esc(btnLabel) + '</span>';
        html += '<span class="konektor-btn-loading konektor-hidden">Mengirim...</span>';
        html += '</button></div></form>';
        html += '<div class="konektor-thanks konektor-hidden"></div>';
        html += '</div>';

        el.innerHTML = html;

        // Bind submit
        var form   = el.querySelector('#konektor-embed-form');
        var alertEl= el.querySelector('.konektor-alert');
        var thanksEl= el.querySelector('.konektor-thanks');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btnText = form.querySelector('.konektor-btn-text');
            var btnLoad = form.querySelector('.konektor-btn-loading');
            btnText.classList.add('konektor-hidden');
            btnLoad.classList.remove('konektor-hidden');
            alertEl.className = 'konektor-alert konektor-hidden';

            var data = {};
            new FormData(form).forEach(function(v, k) { data[k] = v; });
            data.source_url = window.location.href;

            fetch(actionUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(data),
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    form.classList.add('konektor-hidden');
                    thanksEl.innerHTML = '<p>' + esc(res.message) + '</p>';
                    thanksEl.classList.remove('konektor-hidden');
                    if (res.redirect_url && res.redirect_type !== 'none') {
                        thanksEl.innerHTML += '<p style="margin-top:12px;font-size:13px;opacity:.7">Mengarahkan dalam ' + (res.delay||3) + ' detik...</p>';
                        setTimeout(function() { window.location.href = res.redirect_url; }, (res.delay||3) * 1000);
                    }
                } else {
                    alertEl.textContent = res.message || 'Terjadi kesalahan.';
                    alertEl.className   = 'konektor-alert ' + (res.blocked ? 'konektor-alert-error' : (res.double ? 'konektor-alert-warning' : 'konektor-alert-error'));
                    btnText.classList.remove('konektor-hidden');
                    btnLoad.classList.add('konektor-hidden');
                    if (res.blocked) form.classList.add('konektor-hidden');
                }
            })
            .catch(function() {
                alertEl.textContent = 'Koneksi gagal. Coba lagi.';
                alertEl.className   = 'konektor-alert konektor-alert-error';
                btnText.classList.remove('konektor-hidden');
                btnLoad.classList.add('konektor-hidden');
            });
        });
    }

    function fieldHtml(name, label, type, required, placeholder, options) {
        var req  = required ? 'required' : '';
        var ph   = placeholder || label;
        var html = '<div class="konektor-field">';
        html    += '<label>' + esc(label) + (required ? '<span class="req">*</span>' : '') + '</label>';

        if (type === 'textarea') {
            html += '<textarea name="' + esc(name) + '" placeholder="' + esc(ph) + '" ' + req + '></textarea>';
        } else if (type === 'select' && options && options.length) {
            html += '<select name="' + esc(name) + '" ' + req + '><option value="">-- Pilih --</option>';
            options.forEach(function(o) { html += '<option value="' + esc(o) + '">' + esc(o) + '</option>'; });
            html += '</select>';
        } else if (type === 'radio' && options && options.length) {
            options.forEach(function(o) {
                html += '<label class="konektor-radio"><input type="radio" name="' + esc(name) + '" value="' + esc(o) + '" ' + req + '> ' + esc(o) + '</label>';
            });
        } else if (type === 'checkbox' && options && options.length) {
            options.forEach(function(o) {
                html += '<label class="konektor-checkbox"><input type="checkbox" name="' + esc(name) + '[]" value="' + esc(o) + '"> ' + esc(o) + '</label>';
            });
        } else {
            html += '<input type="' + esc(type||'text') + '" name="' + esc(name) + '" placeholder="' + esc(ph) + '" ' + req + '>';
        }

        html += '</div>';
        return html;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
})();
