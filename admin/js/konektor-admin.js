(function($) {
    'use strict';

    var nonce   = KonektorAdmin.nonce;
    var ajaxUrl = KonektorAdmin.ajax_url;

    // ════════════════════════════════════════
    // PIXEL TABS (inside campaign edit)
    // ════════════════════════════════════════
    $(document).on('click', '.knk-pixel-tab', function() {
        var tab = $(this).data('ptab');
        $(this).closest('.knk-card__body').find('.knk-pixel-tab').removeClass('active');
        $(this).addClass('active');
        $(this).closest('.knk-card__body').find('.knk-pixel-content').removeClass('active');
        $(this).closest('.knk-card__body').find('[data-ptab="' + tab + '"].knk-pixel-content').addClass('active');
    });

    // ════════════════════════════════════════
    // REDIRECT TYPE TOGGLE
    // ════════════════════════════════════════
    $(document).on('change', '#redirect-type', function() {
        var val = $(this).val();
        $('#redirect-url-row').toggleClass('knk-hidden', val !== 'url');
        $('#redirect-cs-row').toggleClass('knk-hidden',  val !== 'cs');
    });

    // ════════════════════════════════════════
    // TOGGLE MESSAGES (double lead, block)
    // ════════════════════════════════════════
    $(document).on('change', '#double-lead-toggle', function() {
        $('#double-lead-msg').toggleClass('knk-hidden', !this.checked);
    });
    $(document).on('change', '#block-toggle', function() {
        $('#block-msg').toggleClass('knk-hidden', !this.checked);
    });

    // ════════════════════════════════════════
    // WORK HOURS TOGGLE (operator page)
    // ════════════════════════════════════════
    $(document).on('change', '#work-hours-toggle', function() {
        $('#work-hours-section').toggle(this.checked);
    });

    // ════════════════════════════════════════
    // AUTO-SLUG FROM NAME
    // ════════════════════════════════════════
    $(document).on('input', '#camp-name-input', function() {
        var slugField = $('#camp-slug');
        if (slugField.val() === '' || slugField.data('auto') !== false) {
            slugField.val(slugify($(this).val())).data('auto', true);
        }
    });
    $(document).on('input', '#camp-slug', function() {
        $(this).data('auto', false);
        $(this).val(slugify($(this).val(), true));
    });
    function slugify(str, keepTyping) {
        str = str.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
        if (!keepTyping) str = str.replace(/-+/g, '-').replace(/^-|-$/g, '');
        return str;
    }

    // ════════════════════════════════════════
    // TEMPLATE PREVIEW
    // ════════════════════════════════════════
    $(document).on('click', '.knk-tpl-thumb', function() {
        var tpl = $(this).data('tpl');
        // Update select
        $('#form-template-select').val(tpl);
        // Update active thumb
        $('.knk-tpl-thumb').removeClass('active');
        $(this).addClass('active');
        // Update preview
        refreshPreview();
    });
    $(document).on('change', '#form-template-select', function() {
        var tpl = $(this).val();
        $('.knk-tpl-thumb').removeClass('active');
        $('.knk-tpl-thumb[data-tpl="' + tpl + '"]').addClass('active');
        refreshPreview();
    });
    // Also refresh preview when fields are toggled
    $(document).on('change', '.knk-field-toggle', function() {
        refreshPreview();
    });

    function refreshPreview() {
        var tpl        = $('#form-template-select').val() || 'modern';
        var submitLabel= $('input[name="form_config[submit_label]"]').val() || 'Kirim Sekarang';
        var storeName  = $('input[name="store_name"]').val();
        var productName= $('input[name="product_name"]').val();

        // Collect enabled fields
        var fields = [];
        $('#knk-field-list .knk-field-row').each(function() {
            var enabled = $(this).find('.knk-field-toggle').is(':checked');
            var name    = $(this).find('input[name$="[name]"]').val();
            var label   = $(this).find('.knk-field-row__name').text();
            var type    = $(this).find('.knk-field-row__type').text();
            if (enabled) fields.push({label: label, type: type, required: false});
        });

        var html = '<div class="knk-preview-inner konektor-tpl-' + esc(tpl) + '">';
        html += '<div class="konektor-form">';
        if (storeName || productName) {
            html += '<div class="konektor-form-header">';
            if (storeName)   html += '<p class="konektor-store">'   + esc(storeName)   + '</p>';
            if (productName) html += '<h2 class="konektor-product">' + esc(productName) + '</h2>';
            html += '</div>';
        }
        fields.forEach(function(f) {
            html += '<div class="konektor-field">';
            html += '<label>' + esc(f.label) + '</label>';
            if (f.type === 'textarea') {
                html += '<textarea placeholder="' + esc(f.label) + '"></textarea>';
            } else {
                html += '<input type="' + esc(f.type||'text') + '" placeholder="' + esc(f.label) + '">';
            }
            html += '</div>';
        });
        html += '<div class="konektor-field"><button type="button" class="konektor-btn-submit" style="pointer-events:none">' + esc(submitLabel) + '</button></div>';
        html += '</div></div>';

        $('#knk-form-preview').html(html);
    }

    // Refresh on name/product changes too
    $(document).on('input', 'input[name="store_name"], input[name="product_name"], input[name="form_config[submit_label]"]', function() {
        refreshPreview();
    });

    // ════════════════════════════════════════
    // EXTRA FIELD BUILDER
    // ════════════════════════════════════════
    var extraIdx = $('.knk-extra-row').length;
    $(document).on('click', '#knk-add-field', function() {
        var idx = extraIdx++;
        var html = '<div class="knk-extra-row" data-idx="' + idx + '">' +
            '<input type="text" name="form_config[extra_fields][' + idx + '][label]" class="knk-input knk-input--sm" placeholder="Label Field">' +
            '<select name="form_config[extra_fields][' + idx + '][type]" class="knk-select knk-select--sm">' +
            '<option value="text">Text</option><option value="tel">Telepon</option><option value="email">Email</option>' +
            '<option value="number">Angka</option><option value="textarea">Textarea</option>' +
            '<option value="select">Dropdown</option><option value="radio">Radio</option><option value="checkbox">Checkbox</option>' +
            '</select>' +
            '<label class="knk-cb-label"><input type="checkbox" name="form_config[extra_fields][' + idx + '][required]" value="1"> Wajib</label>' +
            '<label class="knk-cb-label"><input type="checkbox" name="form_config[extra_fields][' + idx + '][enabled]" value="1" checked> Tampil</label>' +
            '<input type="text" name="form_config[extra_fields][' + idx + '][options]" class="knk-input knk-input--sm" placeholder="Opsi (pisah koma)">' +
            '<button type="button" class="knk-btn knk-btn--danger knk-btn--icon knk-remove-extra">✕</button>' +
        '</div>';
        $('#knk-extra-fields').append(html);
    });
    $(document).on('click', '.knk-remove-extra', function() {
        $(this).closest('.knk-extra-row').remove();
    });

    // ════════════════════════════════════════
    // CAMPAIGN FORM SAVE  ← FIXED
    // ════════════════════════════════════════
    $(document).on('submit', '#konektor-campaign-form', function(e) {
        e.preventDefault();
        var $form   = $(this);
        var $status = $('#knk-save-status');
        var id      = parseInt($form.data('id')) || 0;

        $status.text('Menyimpan...').removeClass('success error').addClass('');

        var payload = buildCampaignPayload($form, id);

        $.ajax({
            url:      ajaxUrl,
            type:     'POST',
            data:     payload,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $status.text('✅ Tersimpan!').addClass('success');
                    if (!id && res.data && res.data.id) {
                        var newId = res.data.id;
                        $form.data('id', newId);
                        var url = new URL(window.location.href);
                        url.searchParams.set('action', 'edit');
                        url.searchParams.set('id', newId);
                        window.history.replaceState({}, '', url.toString());
                    }
                } else {
                    $status.text('❌ Gagal: ' + (res.data && res.data.message ? res.data.message : 'Unknown error')).addClass('error');
                }
            },
            error: function(xhr) {
                $status.text('❌ Error: ' + xhr.status).addClass('error');
            }
        });
    });

    function buildCampaignPayload($form, id) {
        var payload  = { action: 'konektor_admin_save_campaign', nonce: nonce, id: id };
        var pixelMeta = {}, pixelGoogle = {}, pixelTiktok = {};
        var operators = {};
        var formFields = {};
        var extraFields = {};
        var formCfgOther = {};

        // Use serializeArray — handles text/select/textarea
        var raw = $form.serializeArray();
        raw.forEach(function(item) {
            var k = item.name, v = item.value;

            // pixel_config[meta|google|tiktok][field]
            var mPixel = k.match(/^pixel_config\[(meta|google|tiktok)\]\[(\w+)\]$/);
            if (mPixel) {
                if (mPixel[1] === 'meta')   pixelMeta[mPixel[2]]   = v;
                if (mPixel[1] === 'google') pixelGoogle[mPixel[2]] = v;
                if (mPixel[1] === 'tiktok') pixelTiktok[mPixel[2]] = v;
                return;
            }
            // operators[id][field]
            var mOp = k.match(/^operators\[(\d+)\]\[(\w+)\]$/);
            if (mOp) {
                if (!operators[mOp[1]]) operators[mOp[1]] = {};
                operators[mOp[1]][mOp[2]] = v;
                return;
            }
            // form_config[fields][i][field]
            var mField = k.match(/^form_config\[fields\]\[(\d+)\]\[(\w+)\]$/);
            if (mField) {
                var fi = mField[1], ff = mField[2];
                if (!formFields[fi]) formFields[fi] = {};
                formFields[fi][ff] = v;
                return;
            }
            // form_config[extra_fields][i][field]
            var mExtra = k.match(/^form_config\[extra_fields\]\[(\d+)\]\[(\w+)\]$/);
            if (mExtra) {
                var ei = mExtra[1], ef = mExtra[2];
                if (!extraFields[ei]) extraFields[ei] = {};
                extraFields[ei][ef] = v;
                return;
            }
            // form_config[template|submit_label]
            var mCfg = k.match(/^form_config\[(\w+)\]$/);
            if (mCfg) {
                formCfgOther[mCfg[1]] = v;
                return;
            }
            // everything else
            payload[k] = v;
        });

        // Manually capture checkboxes that may be unchecked (not in serializeArray)
        // double_lead_enabled, block_enabled
        payload.double_lead_enabled = $form.find('input[name="double_lead_enabled"]').is(':checked') ? 1 : 0;
        payload.block_enabled       = $form.find('input[name="block_enabled"]').is(':checked') ? 1 : 0;

        // Build form_config.fields array with enabled/required from checkboxes
        var fieldsArr = [];
        $form.find('#knk-field-list .knk-field-row').each(function(i) {
            var $row = $(this);
            fieldsArr.push({
                name:     $row.find('input[name$="[name]"]').val(),
                label:    $row.find('input[name$="[label]"]').val(),
                type:     $row.find('input[name$="[type]"]').val(),
                enabled:  $row.find('.knk-field-toggle').is(':checked') ? 1 : 0,
                required: $row.find('input[name$="[required]"]').is(':checked') ? 1 : 0,
            });
        });

        // Build extra fields
        var extrasArr = [];
        $form.find('#knk-extra-fields .knk-extra-row').each(function() {
            var $row = $(this);
            var label = $row.find('input[name$="[label]"]').val();
            if (!label) return;
            var optsRaw = $row.find('input[name$="[options]"]').val();
            extrasArr.push({
                label:    label,
                type:     $row.find('select').val(),
                required: $row.find('input[name$="[required]"]').is(':checked') ? 1 : 0,
                enabled:  $row.find('input[name$="[enabled]"]').is(':checked') ? 1 : 0,
                options:  optsRaw ? optsRaw.split(',').map(s => s.trim()).filter(Boolean) : [],
            });
        });

        payload.form_config = JSON.stringify(Object.assign(formCfgOther, {
            fields:       fieldsArr,
            extra_fields: extrasArr,
        }));

        // Operators
        var opList = [];
        Object.keys(operators).forEach(function(opId) {
            var o = operators[opId];
            if (o.selected === '1') {
                opList.push({ id: parseInt(o.id), weight: parseInt(o.weight) || 1 });
            }
        });
        // Also check via DOM for checkboxes (serializeArray won't include unchecked)
        opList = [];
        $form.find('.knk-op-toggle').each(function() {
            if ($(this).is(':checked')) {
                var $row  = $(this).closest('.knk-op-row');
                var opId  = parseInt($row.find('input[name$="[id]"]').val());
                var opWt  = parseInt($row.find('.knk-input-weight').val()) || 1;
                opList.push({ id: opId, weight: opWt });
            }
        });
        payload.operators = JSON.stringify(opList);

        payload.pixel_config = JSON.stringify({ meta: pixelMeta, google: pixelGoogle, tiktok: pixelTiktok });

        // Allowed domains
        var domsRaw = payload['allowed_domains_raw'] || '';
        payload.allowed_domains = JSON.stringify(
            domsRaw.split('\n').map(function(s){ return s.trim(); }).filter(Boolean)
        );
        delete payload['allowed_domains_raw'];

        // Normalize slug
        if (payload.slug) {
            payload.slug = payload.slug.toLowerCase()
                .replace(/[^a-z0-9\-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }

        // thanks_page_config is sent as individual fields (thanks_page_config[key])
        // already captured in payload[k] = v above — stringify it
        var thanksCfg = {};
        Object.keys(payload).forEach(function(k) {
            var m = k.match(/^thanks_page_config\[(\w+)\]$/);
            if (m) { thanksCfg[m[1]] = payload[k]; delete payload[k]; }
        });
        payload.thanks_page_config = JSON.stringify(thanksCfg);

        return payload;
    }

    // ════════════════════════════════════════
    // DELETE CAMPAIGN
    // ════════════════════════════════════════
    $(document).on('click', '.knk-delete-campaign, .konektor-delete-campaign', function(e) {
        e.preventDefault();
        if (!confirm('Hapus kampanye ini? Data lead tidak ikut terhapus.')) return;
        $.post(ajaxUrl, { action: 'konektor_admin_delete_campaign', nonce: nonce, id: $(this).data('id') }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ════════════════════════════════════════
    // OPERATOR FORM SAVE
    // ════════════════════════════════════════
    $(document).on('submit', '#konektor-operator-form', function(e) {
        e.preventDefault();
        var $form   = $(this);
        var $status = $form.find('.konektor-save-status, .knk-save-status');
        var id      = parseInt($form.data('id')) || 0;
        var raw     = $form.serializeArray();

        var workHoursMap = {};
        var data = { action: 'konektor_admin_save_operator', nonce: nonce, id: id };

        raw.forEach(function(item) {
            var m = item.name.match(/^work_hours\[(\d+)\]\[(\w+)\]$/);
            if (m) {
                if (!workHoursMap[m[1]]) workHoursMap[m[1]] = {};
                workHoursMap[m[1]][m[2]] = item.value;
            } else {
                data[item.name] = item.value;
            }
        });

        var workHours = [];
        Object.keys(workHoursMap).forEach(function(idx) {
            var slot = workHoursMap[idx];
            if (slot.active === '1') workHours.push({ day: slot.day, start: slot.start, end: slot.end });
        });
        data.work_hours         = JSON.stringify(workHours);
        data.work_hours_enabled = $form.find('input[name="work_hours_enabled"]').is(':checked') ? 1 : 0;

        $status.text('Menyimpan...').removeClass('success error');
        $.ajax({
            url: ajaxUrl, type: 'POST', data: data, dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $status.text('✅ Tersimpan!').addClass('success');
                    if (!id && res.data && res.data.id) {
                        var url = new URL(window.location.href);
                        url.searchParams.set('action', 'edit');
                        url.searchParams.set('id', res.data.id);
                        window.history.replaceState({}, '', url.toString());
                        $form.data('id', res.data.id);
                    }
                } else { $status.text('❌ Gagal.').addClass('error'); }
            },
            error: function() { $status.text('❌ Error koneksi.').addClass('error'); }
        });
    });

    $(document).on('click', '.knk-delete-operator, .konektor-delete-operator', function(e) {
        e.preventDefault();
        if (!confirm('Hapus operator ini?')) return;
        $.post(ajaxUrl, { action: 'konektor_admin_delete_operator', nonce: nonce, id: $(this).data('id') }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ════════════════════════════════════════
    // SETTINGS SAVE
    // ════════════════════════════════════════
    $(document).on('submit', '#konektor-settings-form', function(e) {
        e.preventDefault();
        var $status = $(this).find('.knk-save-status, .konektor-save-status');
        var data    = { action: 'konektor_admin_save_settings', nonce: nonce };
        $(this).serializeArray().forEach(function(v) { data[v.name] = v.value; });

        var domsRaw = data['allowed_domains_global'] || '';
        data.allowed_domains_global = JSON.stringify(
            domsRaw.split('\n').map(function(s){ return s.trim(); }).filter(Boolean)
        );
        data.encrypt_lead_data = $(this).find('input[name="encrypt_lead_data"]').is(':checked') ? '1' : '0';

        $status.text('Menyimpan...');
        $.ajax({
            url: ajaxUrl, type: 'POST', data: data, dataType: 'json',
            success: function(res) { $status.text(res.success ? '✅ Tersimpan!' : '❌ Gagal.'); }
        });
    });

    // ════════════════════════════════════════
    // LEAD STATUS
    // ════════════════════════════════════════
    $(document).on('change', '.knk-lead-status, .konektor-lead-status', function() {
        $.post(ajaxUrl, {
            action: 'konektor_admin_update_lead',
            nonce:  nonce,
            id:     $(this).data('id'),
            status: $(this).val()
        });
    });

    // ════════════════════════════════════════
    // UNBLOCK
    // ════════════════════════════════════════
    $(document).on('click', '.knk-unblock, .konektor-unblock', function(e) {
        e.preventDefault();
        if (!confirm('Hapus blokir ini?')) return;
        $.post(ajaxUrl, { action: 'konektor_admin_unblock', nonce: nonce, id: $(this).data('id') }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ════════════════════════════════════════
    // GENERATE PANEL TOKEN
    // ════════════════════════════════════════
    $(document).on('click', '.knk-gen-token, .konektor-gen-token', function(e) {
        e.preventDefault();
        $.post(ajaxUrl, { action: 'konektor_admin_gen_token', nonce: nonce, operator_id: $(this).data('id') }, function(res) {
            if (res.success) {
                $('#knk-token-url, #konektor-token-url').val(res.data.url);
                $('#knk-token-modal, #konektor-token-modal').removeClass('konektor-hidden knk-hidden');
            }
        });
    });
    $(document).on('click', '.knk-token-modal-close, .konektor-modal-close', function() {
        $('#knk-token-modal, #konektor-token-modal').addClass('knk-hidden konektor-hidden');
    });
    $(document).on('click', '#knk-token-url, #konektor-token-url', function() { $(this).select(); });
    $(document).on('click', '#knk-token-copy', function() {
        var $inp = $('#knk-token-url');
        $inp.select();
        navigator.clipboard.writeText($inp.val());
        $(this).text('✓ Tersalin');
        setTimeout(function() { $('#knk-token-copy').text('⎘ Salin'); }, 2000);
    });

    // ════════════════════════════════════════
    // LEGACY TABS (other pages)
    // ════════════════════════════════════════
    $(document).on('click', '.konektor-tab', function() {
        var tab  = $(this).data('tab');
        var $ctx = $(this).closest('.konektor-wrap, .wrap');
        $ctx.find('.konektor-tab').removeClass('active');
        $(this).addClass('active');
        $ctx.find('.konektor-tab-content').removeClass('active');
        $ctx.find('[data-tab="' + tab + '"].konektor-tab-content').addClass('active');
    });

    // Settings tabs (new knk-ptab style)
    $(document).on('click', '.knk-ptab[data-stab]', function() {
        var tab = $(this).data('stab');
        var $ctx = $(this).closest('.knk-wrap');
        $ctx.find('.knk-ptab[data-stab]').removeClass('active');
        $(this).addClass('active');
        $ctx.find('.knk-stab-content').addClass('knk-hidden');
        $('#stab-' + tab).removeClass('knk-hidden');
    });

    // Lead detail modal
    $(document).on('click', '.knk-view-lead', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#knk-lead-detail-body').html('<div style="text-align:center;padding:20px;color:var(--g500)">Memuat...</div>');
        $('#knk-lead-modal').removeClass('knk-hidden');
        $.post(ajaxUrl, { action: 'konektor_admin_get_lead', nonce: nonce, id: id }, function(res) {
            if (res.success && res.data) {
                var l = res.data;
                var html = '<table style="width:100%;font-size:13px;border-collapse:collapse">';
                var rows = [
                    ['ID', l.id], ['Nama', l.name], ['No HP', l.phone], ['Email', l.email || '—'],
                    ['Alamat', l.address || '—'], ['Jumlah', l.quantity || '—'], ['Pesan', l.custom_message || '—'],
                    ['Kampanye', l.campaign_name || '—'], ['Operator', l.operator_name || '—'],
                    ['Status', l.status], ['Double Lead', l.is_double ? 'Ya' : 'Tidak'],
                    ['IP', l.ip_address || '—'], ['Tanggal', l.created_at]
                ];
                rows.forEach(function(r) {
                    html += '<tr style="border-bottom:1px solid var(--g100)">'
                        + '<td style="padding:8px 12px;font-weight:600;color:var(--g600);width:120px">' + r[0] + '</td>'
                        + '<td style="padding:8px 12px">' + (r[1] || '—') + '</td></tr>';
                });
                html += '</table>';
                $('#knk-lead-detail-body').html(html);
            } else {
                $('#knk-lead-detail-body').html('<p style="color:var(--err);text-align:center">Gagal memuat data lead.</p>');
            }
        });
    });
    $(document).on('click', '#knk-lead-modal-close, #knk-lead-modal', function(e) {
        if (e.target === this) $('#knk-lead-modal').addClass('knk-hidden');
    });

    // ════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════
    function esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }

})(jQuery);
