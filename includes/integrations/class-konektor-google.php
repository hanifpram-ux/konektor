<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Google {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['google'] ?? [];
    }

    public static function get_script( $campaign, $event_type ) {
        $cfg = self::get_config( $campaign );

        $output = '';

        // GTM
        if ( ! empty( $cfg['gtm_id'] ) ) {
            $gtm = esc_js( $cfg['gtm_id'] );
            $output .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$gtm}');</script>";
        }

        // Google Ads Conversion
        if ( ! empty( $cfg['conversion_id'] ) ) {
            $conv_id   = esc_js( $cfg['conversion_id'] );
            $label_map = [
                'page_load'   => $cfg['page_load_label']   ?? '',
                'form_submit' => $cfg['form_submit_label']  ?? '',
                'thanks_page' => $cfg['thanks_page_label']  ?? '',
            ];
            $label = $label_map[ $event_type ] ?? '';
            if ( $label ) {
                $label   = esc_js( $label );
                $output .= "<script async src='https://www.googletagmanager.com/gtag/js?id=AW-{$conv_id}'></script>";
                $output .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','AW-{$conv_id}');gtag('event','conversion',{'send_to':'AW-{$conv_id}/{$label}'});</script>";
            }
        }

        return $output;
    }
}
