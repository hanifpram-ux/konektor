<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Operator {

    public static function get_all( $args = [] ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'konektor_operators';
        $where   = '1=1';
        $params  = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['type'] ) ) {
            $where   .= ' AND type = %s';
            $params[] = $args['type'];
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY id ASC";
        if ( $params ) return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        return $wpdb->get_results( $sql );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}konektor_operators WHERE id = %d", $id
        ) );
    }

    public static function save( $data, $id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'konektor_operators';
        $row   = [
            'name'              => sanitize_text_field( $data['name'] ),
            'type'              => sanitize_text_field( $data['type'] ),
            'value'             => sanitize_text_field( $data['value'] ),
            'status'            => in_array( $data['status'], ['on','off'] ) ? $data['status'] : 'on',
            'work_hours_enabled'=> ! empty( $data['work_hours_enabled'] ) ? 1 : 0,
            'work_hours'        => ! empty( $data['work_hours'] ) ? wp_json_encode( $data['work_hours'] ) : null,
            'telegram_chat_id'  => sanitize_text_field( $data['telegram_chat_id'] ?? '' ),
            'notes'             => sanitize_textarea_field( $data['notes'] ?? '' ),
        ];
        if ( $id ) {
            $wpdb->update( $table, $row, [ 'id' => $id ] );
            return $id;
        }
        $wpdb->insert( $table, $row );
        return $wpdb->insert_id;
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'konektor_operators', [ 'id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'konektor_campaign_operators', [ 'operator_id' => $id ] );
    }

    public static function get_campaigns_for_operator( $operator_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT c.id, c.name, c.type, co.weight
             FROM {$wpdb->prefix}konektor_campaigns c
             JOIN {$wpdb->prefix}konektor_campaign_operators co ON co.campaign_id = c.id
             WHERE co.operator_id = %d",
            $operator_id
        ) );
    }

    public static function is_on_duty( $operator ) {
        if ( ! $operator->work_hours_enabled ) return true;
        $hours = json_decode( $operator->work_hours, true );
        if ( empty( $hours ) ) return true;

        $now_day  = strtolower( current_time( 'l' ) );
        $now_time = current_time( 'H:i' );

        foreach ( $hours as $slot ) {
            if ( strtolower( $slot['day'] ) === $now_day ) {
                if ( $now_time >= $slot['start'] && $now_time <= $slot['end'] ) return true;
            }
        }
        return false;
    }

    public static function generate_panel_token( $operator_id ) {
        global $wpdb;
        // Delete any existing token for this operator (one token per operator)
        $wpdb->delete( $wpdb->prefix . 'konektor_operator_tokens', [ 'operator_id' => $operator_id ] );
        $token = Konektor_Crypto::generate_token();
        $wpdb->insert( $wpdb->prefix . 'konektor_operator_tokens', [
            'operator_id' => $operator_id,
            'token'       => $token,
            'expires_at'  => null, // permanent
        ] );
        return $token;
    }

    public static function verify_panel_token( $token ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT ot.*, o.id AS operator_id, o.name, o.type, o.value
             FROM {$wpdb->prefix}konektor_operator_tokens ot
             JOIN {$wpdb->prefix}konektor_operators o ON o.id = ot.operator_id
             WHERE ot.token = %s",
            $token
        ) );
    }
}
