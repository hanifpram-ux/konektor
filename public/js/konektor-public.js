(function($) {
    'use strict';

    // Form submit
    $(document).on('submit', '.konektor-form', function(e) {
        e.preventDefault();
        var $form  = $(this);
        var $wrap  = $form.closest('.konektor-form-wrap');
        var $alert = $wrap.find('.konektor-alert');
        var $btnText = $form.find('.konektor-btn-text');
        var $btnLoad = $form.find('.konektor-btn-loading');

        $btnText.addClass('konektor-hidden');
        $btnLoad.removeClass('konektor-hidden');
        $alert.removeClass('konektor-alert-success konektor-alert-error konektor-alert-warning').addClass('konektor-hidden');

        var formData = $form.serialize();

        $.ajax({
            url:  KonektorAjax.url,
            type: 'POST',
            data: formData,
            success: function(res) {
                if (res.success) {
                    var d = res.data;
                    $form.addClass('konektor-hidden');
                    var $thanks = $wrap.find('.konektor-thanks');
                    $thanks.html('<p>' + escHtml(d.message) + '</p>').removeClass('konektor-hidden');

                    // Pixel events
                    if (typeof fbq !== 'undefined') fbq('track', 'Purchase');
                    if (typeof ttq !== 'undefined') ttq.track('PlaceAnOrder');

                    if (d.redirect_url && d.redirect_type !== 'none') {
                        var delay = parseInt(d.delay) || 3;
                        if (d.redirect_type === 'cs') {
                            $thanks.append('<p style="margin-top:16px;font-size:14px">Mengarahkan ke CS dalam ' + delay + ' detik...</p>');
                        }
                        setTimeout(function() { window.location.href = d.redirect_url; }, delay * 1000);
                    }
                } else {
                    var d = res.data;
                    if (d.blocked) {
                        $form.addClass('konektor-hidden');
                        $wrap.prepend('<div class="konektor-blocked">' + escHtml(d.message) + '</div>');
                    } else if (d.double && d.redirect_cs) {
                        $alert.html(escHtml(d.message)).addClass('konektor-alert-warning').removeClass('konektor-hidden');
                    } else {
                        $alert.html(escHtml(d.message || 'Terjadi kesalahan.')).addClass('konektor-alert-error').removeClass('konektor-hidden');
                    }
                    $btnText.removeClass('konektor-hidden');
                    $btnLoad.addClass('konektor-hidden');
                }
            },
            error: function() {
                $alert.html('Terjadi kesalahan koneksi.').addClass('konektor-alert-error').removeClass('konektor-hidden');
                $btnText.removeClass('konektor-hidden');
                $btnLoad.addClass('konektor-hidden');
            }
        });
    });

    // WA Link button
    $(document).on('click', '.konektor-wa-btn', function() {
        var $btn         = $(this);
        var campaign_id  = $btn.data('campaign');
        var origText     = $btn.text();
        $btn.text('Menghubungkan...').prop('disabled', true);

        $.ajax({
            url:  KonektorAjax.url,
            type: 'POST',
            data: { action: 'konektor_wa_click', nonce: KonektorAjax.nonce, campaign_id: campaign_id },
            success: function(res) {
                if (res.success && res.data.url) {
                    window.open(res.data.url, '_blank');
                } else if (res.data && res.data.blocked) {
                    alert(res.data.message);
                }
                $btn.text(origText).prop('disabled', false);
            },
            error: function() {
                $btn.text(origText).prop('disabled', false);
            }
        });
    });

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

})(jQuery);
