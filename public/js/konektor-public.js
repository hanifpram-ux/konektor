(function($) {
    'use strict';

    // ── VID cookie (shared across standalone, embed, and shortcode) ──
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

    // Inject _vid hidden field into every konektor form on the page
    $(document).ready(function() {
        $('.konektor-form').each(function() {
            var $form = $(this);
            if (!$form.find('input[name="_vid"]').length) {
                $form.append('<input type="hidden" name="_vid" value="' + escHtml(vid) + '">');
            }
        });
    });

    // AJAX form submission with pixel_mode-aware browser pixel calls
    $(document).on('submit', '.konektor-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var data  = $form.serialize();
        data += '&action=konektor_submit_form&nonce=' + (KonektorAjax.nonce || '');

        $.post(KonektorAjax.url, data, function(res) {
            if (!res || !res.success) return;
            var d = res.data || {};
            // Only fire browser pixel if server did NOT already fire CAPI for that platform
            if (!d.double) {
                if (!d.capi_meta   && typeof fbq  !== 'undefined') try { fbq('track', 'Lead'); }        catch(x) {}
                if (!d.capi_tiktok && typeof ttq  !== 'undefined') try { ttq.track('SubmitForm'); }     catch(x) {}
                if (                  typeof gtag !== 'undefined') try { gtag('event', 'generate_lead'); } catch(x) {}
            }
            if (d.redirect_url) {
                var delay = (typeof d.delay === 'number') ? d.delay * 1000 : 3000;
                setTimeout(function() { window.location.href = d.redirect_url; }, delay);
            }
        });
    });

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

})(jQuery);
