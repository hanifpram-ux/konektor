<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Tiktok {

    public static function get_config( $campaign ) {
        $pixel = $campaign->pixel_config ? json_decode( $campaign->pixel_config, true ) : [];
        return $pixel['tiktok'] ?? [];
    }

    public static function get_script( $campaign, $event_type ) {
        $cfg      = self::get_config( $campaign );
        $pixel_id = esc_js( $cfg['pixel_id'] ?? '' );
        if ( ! $pixel_id ) return '';

        $cfg_key_map = [
            'page_load'   => 'page_load_event',
            'form_submit' => 'form_submit_event',
            'thanks_page' => 'thanks_page_event',
        ];
        $default_map = [
            'page_load'   => 'PageView',
            'form_submit' => 'SubmitForm',
            'thanks_page' => 'PlaceAnOrder',
        ];
        $cfg_key = $cfg_key_map[ $event_type ] ?? '';
        $event   = esc_js( ( $cfg_key && ! empty( $cfg[ $cfg_key ] ) ) ? $cfg[ $cfg_key ] : ( $default_map[ $event_type ] ?? 'PageView' ) );
        if ( $event === '' ) return '';

        // TikTok pixel base code
        $script  = "!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];";
        $script .= "ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};";
        $script .= "for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);";
        $script .= "ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript',o.async=!0,o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};";
        $script .= "ttq.load('{$pixel_id}');ttq.page();";
        $script .= "}(window,document,'ttq');ttq.track('{$event}');";

        return '<script>' . $script . '</script>';
    }
}
