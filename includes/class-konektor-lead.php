<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Lead {

    public static function create( $data ) {
        global $wpdb;

        $encrypt = Konektor_Helper::get_setting( 'encrypt_lead_data', '1' ) === '1';

        $phone       = Konektor_Helper::sanitize_phone( $data['phone'] ?? '' );
        $email       = sanitize_email( $data['email'] ?? '' );
        $fingerprint = Konektor_Crypto::fingerprint( $phone, $email );

        $row = [
            'campaign_id'    => (int) $data['campaign_id'],
            'operator_id'    => ! empty( $data['operator_id'] ) ? (int) $data['operator_id'] : null,
            'name'           => $encrypt ? Konektor_Crypto::encrypt( sanitize_text_field( $data['name'] ?? '' ) ) : sanitize_text_field( $data['name'] ?? '' ),
            'email'          => $encrypt ? Konektor_Crypto::encrypt( $email ) : $email,
            'phone'          => $encrypt ? Konektor_Crypto::encrypt( $phone ) : $phone,
            'address'        => $encrypt ? Konektor_Crypto::encrypt( sanitize_textarea_field( $data['address'] ?? '' ) ) : sanitize_textarea_field( $data['address'] ?? '' ),
            'quantity'       => sanitize_text_field( $data['quantity'] ?? '' ),
            'custom_message' => sanitize_textarea_field( $data['custom_message'] ?? '' ),
            'extra_data'     => ! empty( $data['extra_data'] ) ? wp_json_encode( $data['extra_data'] ) : null,
            'ip_address'     => Konektor_Helper::get_client_ip() ?: sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
            'cookie_id'      => self::resolve_vid( $data['_vid'] ?? '' ),
            'user_agent'     => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
            'referrer'       => esc_url_raw( $data['referrer'] ?? ( $_SERVER['HTTP_REFERER'] ?? '' ) ),
            'fingerprint'    => $fingerprint,
            'is_double'      => 0,
            'source_url'     => esc_url_raw( $data['source_url'] ?? '' ),
            'status'         => 'new',
        ];

        $inserted = $wpdb->insert( $wpdb->prefix . 'konektor_leads', $row );
        if ( false === $inserted && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Konektor] Lead insert error: ' . $wpdb->last_error );
        }
        return $wpdb->insert_id;
    }

    public static function resolve_vid( $vid_from_body = '' ) {
        $vid = $_COOKIE['konektor_vid'] ?? '';
        if ( ! $vid && $vid_from_body ) $vid = $vid_from_body;
        return sanitize_text_field( $vid );
    }

    /**
     * Build scope WHERE clause for double-lead detection.
     * Scope controlled by konektor setting 'double_lead_scope':
     *   'campaign' — per campaign_id (default)
     *   'domain'   — all campaigns from the same source domain
     *   'page'     — all campaigns from the exact same source URL
     *
     * Returns ['where' => string, 'params' => array]
     */
    private static function double_scope_where( $campaign_id, $source_url = '' ) {
        $scope = Konektor_Helper::get_setting( 'double_lead_scope', 'campaign' );

        if ( $scope === 'page' && $source_url ) {
            return [
                'where'  => 'source_url = %s',
                'params' => [ substr( $source_url, 0, 2000 ) ],
            ];
        }
        if ( $scope === 'domain' && $source_url ) {
            $host = parse_url( $source_url, PHP_URL_HOST );
            if ( $host ) {
                return [
                    'where'  => '(source_url LIKE %s OR source_url LIKE %s)',
                    'params' => [ 'http://' . $host . '%', 'https://' . $host . '%' ],
                ];
            }
        }
        // Default: per campaign
        return [
            'where'  => 'campaign_id = %d',
            'params' => [ (int) $campaign_id ],
        ];
    }

    /**
     * Check duplicate for form leads: fingerprint → cookie → IP.
     */
    public static function check_double( $campaign_id, $phone, $email, $vid_from_body = '', $source_url = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'konektor_leads';
        $ip    = Konektor_Helper::get_client_ip();
        $vid   = self::resolve_vid( $vid_from_body );
        $sw    = self::double_scope_where( $campaign_id, $source_url );

        // 1. Fingerprint (phone+email)
        if ( $phone !== '' || $email !== '' ) {
            $fp = Konektor_Crypto::fingerprint(
                Konektor_Helper::sanitize_phone( $phone ),
                sanitize_email( $email )
            );
            if ( $fp && $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table WHERE {$sw['where']} AND fingerprint = %s AND is_double = 0 LIMIT 1",
                ...array_merge( $sw['params'], [ $fp ] )
            ) ) ) return true;
        }

        // 2. Cookie / VID
        if ( $vid && $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE {$sw['where']} AND cookie_id = %s AND is_double = 0 LIMIT 1",
            ...array_merge( $sw['params'], [ $vid ] )
        ) ) ) return true;

        // 3. IP address
        if ( $ip && $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE {$sw['where']} AND ip_address = %s AND is_double = 0 LIMIT 1",
            ...array_merge( $sw['params'], [ $ip ] )
        ) ) ) return true;

        return false;
    }

    /**
     * Check duplicate for wa_link leads: cookie → IP.
     */
    public static function check_double_wa( $campaign_id, $vid_from_qs = '', $source_url = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'konektor_leads';
        $vid   = self::resolve_vid( $vid_from_qs );
        $ip    = Konektor_Helper::get_client_ip();
        $sw    = self::double_scope_where( $campaign_id, $source_url );

        // 1. Cookie / VID
        if ( $vid && $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE {$sw['where']} AND cookie_id = %s AND is_double = 0 LIMIT 1",
            ...array_merge( $sw['params'], [ $vid ] )
        ) ) ) return true;

        // 2. IP address
        if ( $ip && $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE {$sw['where']} AND ip_address = %s AND is_double = 0 LIMIT 1",
            ...array_merge( $sw['params'], [ $ip ] )
        ) ) ) return true;

        return false;
    }

    public static function mark_double( $id ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'konektor_leads', [ 'is_double' => 1 ], [ 'id' => $id ] );
    }

    public static function update_status( $lead_id, $status, $note = '', $operator_id = null ) {
        global $wpdb;
        $allowed = [ 'new', 'contacted', 'purchased', 'cancelled', 'blocked' ];
        if ( ! in_array( $status, $allowed ) ) return false;

        $data = [
            'status'      => $status,
            'status_note' => sanitize_textarea_field( $note ),
        ];
        $where = [ 'id' => (int) $lead_id ];

        // Verify operator owns this lead
        if ( $operator_id ) {
            $where['operator_id'] = (int) $operator_id;
        }

        return $wpdb->update( $wpdb->prefix . 'konektor_leads', $data, $where );
    }

    public static function get_all( $args = [] ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'konektor_leads';
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['campaign_id'] ) ) {
            $where   .= ' AND campaign_id = %d';
            $params[] = $args['campaign_id'];
        }
        if ( ! empty( $args['operator_id'] ) ) {
            $where   .= ' AND operator_id = %d';
            $params[] = $args['operator_id'];
        }
        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }
        if ( isset( $args['is_double'] ) ) {
            $where   .= ' AND is_double = %d';
            $params[] = (int) $args['is_double'];
        }

        $limit  = ! empty( $args['per_page'] ) ? (int) $args['per_page'] : 50;
        $offset = ! empty( $args['page'] ) ? ( (int) $args['page'] - 1 ) * $limit : 0;

        if ( ! empty( $args['camp_type'] ) ) {
            $sql = "SELECT l.* FROM {$table} l JOIN {$wpdb->prefix}konektor_campaigns c ON c.id = l.campaign_id WHERE $where AND c.type = %s ORDER BY l.id DESC LIMIT %d OFFSET %d";
            $params[] = $args['camp_type'];
            $params[] = $limit;
            $params[] = $offset;
            return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        }

        $sql    = "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}konektor_leads WHERE id = %d", $id
        ) );
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'konektor_leads', [ 'id' => (int) $id ] );
    }

    public static function mark_followed_up( $id ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'konektor_leads',
            [ 'followed_up_at' => current_time( 'mysql' ) ],
            [ 'id' => (int) $id ]
        );
    }

    public static function decrypt_lead( $lead ) {
        $encrypt = Konektor_Helper::get_setting( 'encrypt_lead_data', '1' ) === '1';
        if ( ! $encrypt ) return $lead;

        $lead->name    = Konektor_Crypto::decrypt( $lead->name );
        $lead->email   = Konektor_Crypto::decrypt( $lead->email );
        $lead->phone   = Konektor_Crypto::decrypt( $lead->phone );
        $lead->address = Konektor_Crypto::decrypt( $lead->address );
        return $lead;
    }

    public static function export_csv( $args = [] ) {
        $leads = self::get_all( array_merge( $args, [ 'per_page' => 99999 ] ) );
        $rows  = [];
        $rows[] = [ 'ID', 'Kampanye', 'Operator', 'Nama', 'Email', 'No HP', 'Alamat', 'Jumlah', 'Pesan', 'Status', 'Double', 'IP', 'Tanggal' ];

        global $wpdb;
        $campaigns = [];
        $operators = [];

        foreach ( $leads as $lead ) {
            $l = self::decrypt_lead( $lead );
            if ( ! isset( $campaigns[ $l->campaign_id ] ) ) {
                $c = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}konektor_campaigns WHERE id=%d", $l->campaign_id ) );
                $campaigns[ $l->campaign_id ] = $c;
            }
            if ( $l->operator_id && ! isset( $operators[ $l->operator_id ] ) ) {
                $o = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}konektor_operators WHERE id=%d", $l->operator_id ) );
                $operators[ $l->operator_id ] = $o;
            }

            $rows[] = [
                $l->id,
                $campaigns[ $l->campaign_id ] ?? '',
                $l->operator_id ? ( $operators[ $l->operator_id ] ?? '' ) : '',
                $l->name, $l->email, $l->phone, $l->address,
                $l->quantity, $l->custom_message,
                $l->status, $l->is_double ? 'Ya' : 'Tidak',
                $l->ip_address, $l->created_at,
            ];
        }
        return $rows;
    }

    public static function count( $args = [] ) {
        global $wpdb;
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['campaign_id'] ) ) { $where .= ' AND campaign_id = %d'; $params[] = $args['campaign_id']; }
        if ( ! empty( $args['operator_id'] ) ) { $where .= ' AND operator_id = %d'; $params[] = $args['operator_id']; }
        if ( ! empty( $args['status'] ) )       { $where .= ' AND status = %s';     $params[] = $args['status']; }

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}konektor_leads WHERE $where";
        return (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ) : $wpdb->get_var( $sql ) );
    }
}
