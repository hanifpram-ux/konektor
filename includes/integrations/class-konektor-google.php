<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Google {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['google'] ?? [];
    }

    public static function get_script( $campaign, $event_type ) {
        $cfg    = self::get_config( $campaign );
        $output = '';

        // ── Google Tag Manager
        if ( ! empty( $cfg['gtm_id'] ) ) {
            $gtm     = esc_js( trim( $cfg['gtm_id'] ) );
            $output .= <<<HTML
<!-- Google Tag Manager -->
<script>
  (function(w, d, s, l, i) {
    w[l] = w[l] || [];
    w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s),
        dl = l != 'dataLayer' ? '&l=' + l : '';
    j.async = true;
    j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
    f.parentNode.insertBefore(j, f);
  })(window, document, 'script', 'dataLayer', '{$gtm}');
</script>
<!-- End Google Tag Manager -->

HTML;
        }

        // ── Google Ads Conversion Tracking
        if ( ! empty( $cfg['conversion_id'] ) ) {
            $conv_id = esc_js( trim( $cfg['conversion_id'] ) );

            $label_map = [
                'page_load'   => $cfg['page_load_label']   ?? '',
                'form_submit' => $cfg['form_submit_label'] ?? '',
                'thanks_page' => $cfg['thanks_page_label'] ?? '',
            ];
            $label = trim( $label_map[ $event_type ] ?? '' );

            if ( $label ) {
                $label    = esc_js( $label );
                $send_to  = "AW-{$conv_id}/{$label}";
                $value    = ! empty( $cfg['value'] ) ? (float) $cfg['value'] : null;
                $currency = esc_js( trim( $cfg['currency'] ?? 'IDR' ) );

                $conv_params = "  'send_to': '{$send_to}'";
                if ( $value !== null ) {
                    $conv_params .= ",\n  'value': {$value},\n  'currency': '{$currency}'";
                }

                $output .= <<<HTML
<!-- Google Ads Conversion Tracking -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-{$conv_id}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag() { dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', 'AW-{$conv_id}');
  gtag('event', 'conversion', {
{$conv_params}
  });
</script>
<!-- End Google Ads Conversion Tracking -->

HTML;
            }
        }

        // ── Google Analytics 4 (GA4)
        if ( ! empty( $cfg['ga4_id'] ) ) {
            $ga4 = esc_js( trim( $cfg['ga4_id'] ) );

            $event_map = [
                'page_load'   => 'page_view',
                'form_submit' => 'generate_lead',
                'thanks_page' => 'purchase',
            ];
            $ga4_event = $event_map[ $event_type ] ?? 'page_view';

            $ga4_event_line = $ga4_event !== 'page_view'
                ? "  gtag('event', '{$ga4_event}');\n"
                : '';

            $output .= <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$ga4}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag() { dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', '{$ga4}');
{$ga4_event_line}</script>
<!-- End Google Analytics 4 -->

HTML;
        }

        return $output;
    }
}
