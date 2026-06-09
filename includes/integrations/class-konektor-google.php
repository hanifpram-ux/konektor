<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Google {

    public static function get_config( $campaign ) {
        $pixel = Konektor_Campaign::decode_json_field( $campaign->pixel_config ?? null );
        $cfg = $pixel['google'] ?? [];
        // Backward compatibility: if 'enabled' key doesn't exist, treat as enabled (old campaigns)
        if ( array_key_exists( 'enabled', $cfg ) && empty( $cfg['enabled'] ) ) return [];
        return $cfg;
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

                $conv_params = "'send_to':'{$send_to}'";
                if ( $value !== null ) {
                    $conv_params .= ",'value':{$value},'currency':'{$currency}'";
                }

                // sessionStorage key prevents conversion from re-firing on back-navigation
                $g_sess_key = 'knk_g_' . (int) $campaign->id . '_' . $event_type;

                $output .= <<<HTML
<!-- Google Ads Conversion Tracking -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-{$conv_id}"></script>
<script>
  window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
  gtag('js',new Date());gtag('config','AW-{$conv_id}');
  (function(){var _k='{$g_sess_key}';if(sessionStorage.getItem(_k))return;sessionStorage.setItem(_k,'1');gtag('event','conversion',{{$conv_params}});})();
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

            // sessionStorage guard for GA4 conversion events (not page_view)
            $ga4_sess_key = 'knk_g4_' . (int) $campaign->id . '_' . $event_type;
            $ga4_event_line = '';
            if ( $ga4_event && $ga4_event !== 'page_view' ) {
                $ga4_event_line = "(function(){var _k='{$ga4_sess_key}';if(sessionStorage.getItem(_k))return;sessionStorage.setItem(_k,'1');gtag('event','{$ga4_event}');})();";
            }

            $output .= <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$ga4}"></script>
<script>
  window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
  gtag('js',new Date());gtag('config','{$ga4}');{$ga4_event_line}
</script>
<!-- End Google Analytics 4 -->

HTML;
        }

        return $output;
    }
}
