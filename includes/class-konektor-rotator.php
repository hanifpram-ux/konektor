<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Rotator {

    /**
     * Pick the next operator for a campaign using weighted round-robin
     * respecting on-duty status.
     */
    public static function pick( $campaign_id ) {
        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT o.*, co.weight
             FROM {$wpdb->prefix}konektor_operators o
             JOIN {$wpdb->prefix}konektor_campaign_operators co ON co.operator_id = o.id
             WHERE co.campaign_id = %d AND o.status = 'on'
             ORDER BY o.id ASC",
            $campaign_id
        ) );

        if ( empty( $rows ) ) return null;

        // Filter by on-duty
        $available = array_filter( $rows, function( $o ) { return Konektor_Operator::is_on_duty( $o ); } );
        if ( empty( $available ) ) $available = $rows; // fallback to all if none on duty

        // Build weighted pool
        $pool = [];
        foreach ( $available as $op ) {
            $w = max( 1, min( 10, (int) $op->weight ) );
            for ( $i = 0; $i < $w; $i++ ) {
                $pool[] = $op;
            }
        }

        // Weighted random pick
        $idx      = array_rand( $pool );
        $selected = $pool[ $idx ];

        return $selected;
    }

    /**
     * Build a follow-up link for CS to contact the customer.
     * Uses campaign->followup_message with shortcode substitution.
     * Returns WA link if customer has phone, otherwise mailto: if customer has email.
     */
    public static function get_followup_url( $campaign, $lead, $operator = null ) {
        $message = Konektor_Helper::parse_shortcodes(
            $campaign->followup_message ?? '',
            [
                'name'         => $lead->name     ?? '',
                'email'        => $lead->email    ?? '',
                'phone'        => $lead->phone    ?? '',
                'address'      => $lead->address  ?? '',
                'custom_message' => $lead->custom_message ?? '',
                'product_name' => $campaign->product_name ?? '',
                'quantity'     => $lead->quantity ?? '',
                'operator_name'=> $operator ? $operator->name : '',
            ]
        );

        if ( ! empty( $lead->phone ) ) {
            return Konektor_Helper::wa_url( $lead->phone, $message );
        }
        if ( ! empty( $lead->email ) ) {
            $subject = rawurlencode( 'Follow-up: ' . ( $campaign->product_name ?? '' ) );
            return 'mailto:' . rawurlencode( $lead->email ) . '?subject=' . $subject . '&body=' . rawurlencode( $message );
        }
        return '';
    }

    public static function get_redirect_url( $operator, $campaign, $lead_data ) {
        $message = Konektor_Helper::parse_shortcodes(
            $campaign->thanks_page_config ? json_decode( $campaign->thanks_page_config, true )['custom_message'] ?? '' : '',
            array_merge( $lead_data, [ 'operator_name' => $operator->name ] )
        );

        switch ( $operator->type ) {
            case 'whatsapp':
                return Konektor_Helper::wa_url( $operator->value, $message );
            case 'email':
                $subject = urlencode( 'Halo, saya tertarik dengan ' . ( $lead_data['product_name'] ?? 'produk Anda' ) );
                return 'mailto:' . sanitize_email( $operator->value ) . '?subject=' . $subject . '&body=' . rawurlencode( $message );
            case 'telegram':
                return 'https://t.me/' . ltrim( $operator->value, '@' );
            case 'line':
                return 'https://line.me/R/ti/p/' . ltrim( $operator->value, '@' );
            default:
                return '';
        }
    }
}
