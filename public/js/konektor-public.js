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

    // Browser-side pixel events on form submit (optional enhancement)
    $(document).on('submit', '.konektor-form', function() {
        if (typeof fbq !== 'undefined') fbq('track', 'Lead', {}, {});
        if (typeof ttq !== 'undefined') ttq.track('SubmitForm', {}, {});
        if (typeof gtag !== 'undefined') gtag('event', 'generate_lead');
    });

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

})(jQuery);
