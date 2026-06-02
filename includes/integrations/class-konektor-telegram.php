<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Telegram {

    private static function bot_token() {
        return Konektor_Helper::get_setting( 'telegram_bot_token', '' );
    }

    public static function send_message( $chat_id, $text, $reply_markup = null ) {
        $token = self::bot_token();
        if ( ! $token || ! $chat_id ) return false;

        $body = [
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
        if ( $reply_markup ) {
            $body['reply_markup'] = wp_json_encode( $reply_markup );
        }

        $url      = "https://api.telegram.org/bot{$token}/sendMessage";
        $response = wp_remote_post( $url, [
            'body'    => $body,
            'timeout' => 10,
        ] );

        return ! is_wp_error( $response );
    }

    public static function answer_callback_query( $callback_query_id, $text ) {
        $token = self::bot_token();
        if ( ! $token || ! $callback_query_id ) return false;

        $url      = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
        $response = wp_remote_post( $url, [
            'body'    => [
                'callback_query_id' => $callback_query_id,
                'text'              => $text,
                'show_alert'        => true,
            ],
            'timeout' => 10,
        ] );

        return ! is_wp_error( $response );
    }

    public static function edit_message_text( $chat_id, $message_id, $text, $reply_markup = null ) {
        $token = self::bot_token();
        if ( ! $token || ! $chat_id || ! $message_id ) return false;

        $body = [
            'chat_id'    => $chat_id,
            'message_id' => $message_id,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
        if ( $reply_markup ) {
            $body['reply_markup'] = wp_json_encode( $reply_markup );
        }

        $url      = "https://api.telegram.org/bot{$token}/editMessageText";
        $response = wp_remote_post( $url, [
            'body'    => $body,
            'timeout' => 10,
        ] );

        return ! is_wp_error( $response );
    }

    public static function notify_lead( $lead, $operator, $campaign ) {
        if ( ! $operator->telegram_chat_id ) return;

        $decrypt     = Konektor_Lead::decrypt_lead( clone $lead );
        $is_wa_click = empty( $decrypt->name ) && empty( $decrypt->phone );
        $cam_name    = $campaign->name ?? '';

        if ( $is_wa_click ) {
            // WA Link — tidak ada data form, tampilkan info tracking
            $text  = "<b>WA Link Diklik - {$cam_name}</b>\n";
            if ( $lead->ip_address ) $text .= "IP: {$lead->ip_address}\n";
            if ( $lead->source_url ) $text .= "Sumber: {$lead->source_url}\n";
            $text .= 'Waktu: ' . date( 'd/m/Y H:i', strtotime( $lead->created_at ) );
            self::send_message( $operator->telegram_chat_id, $text );
            return;
        }

        // Form lead — tampilkan hanya field yang enabled sesuai form_config
        $form_cfg    = Konektor_Campaign::get_form_config( $campaign );
        $all_fields  = $form_cfg['fields']       ?? [];
        $extra_fields = $form_cfg['extra_fields'] ?? [];

        $field_map = [
            'name'           => $decrypt->name           ?? '',
            'phone'          => $decrypt->phone          ?? '',
            'email'          => $decrypt->email          ?? '',
            'address'        => $decrypt->address        ?? '',
            'quantity'       => $lead->quantity          ?? '',
            'custom_message' => $lead->custom_message    ?? '',
        ];

        $text = "<b>Lead Baru - {$cam_name}</b>\n";

        foreach ( $all_fields as $field ) {
            if ( empty( $field['enabled'] ) ) continue;
            $key   = $field['name']  ?? '';
            $label = $field['label'] ?? $key;
            $val   = isset( $field_map[ $key ] ) ? trim( $field_map[ $key ] ) : '';
            if ( $val === '' ) continue;
            $text .= "{$label}: {$val}\n";
        }

        // Extra / custom fields dari extra_data
        if ( ! empty( $extra_fields ) ) {
            $extra_data = [];
            if ( ! empty( $lead->extra_data ) ) {
                $decoded = json_decode( $lead->extra_data, true );
                if ( is_array( $decoded ) ) $extra_data = $decoded;
            }
            foreach ( $extra_fields as $ef ) {
                if ( empty( $ef['enabled'] ) ) continue;
                $key   = $ef['name']  ?? '';
                $label = $ef['label'] ?? $key;
                $val   = isset( $extra_data[ $key ] ) ? trim( (string) $extra_data[ $key ] ) : '';
                if ( $val === '' ) continue;
                $text .= "{$label}: {$val}\n";
            }
        }

        $text .= 'Waktu: ' . date( 'd/m/Y H:i', strtotime( $lead->created_at ) );

        $buttons = [];

        // Tombol Follow-Up + Dihubungi dalam satu baris
        if ( ! empty( $decrypt->phone ) ) {
            $followup_url = Konektor_Rotator::get_followup_url( $campaign, $decrypt, $operator );
            $row = [];
            if ( $followup_url ) {
                $row[] = [ 'text' => '📲 Follow-Up Customer', 'url' => $followup_url ];
            }
            $row[]    = [ 'text' => '📞 Dihubungi', 'callback_data' => 'status:contacted:' . $lead->id ];
            $buttons[] = $row;
        }

        $reply_markup = ! empty( $buttons ) ? [ 'inline_keyboard' => $buttons ] : null;
        self::send_message( $operator->telegram_chat_id, $text, $reply_markup );
    }

    /**
     * Kirim rekap lead kemarin ke semua operator yang punya telegram_chat_id.
     * Dipanggil via WP cron harian jam 14.00 WIB.
     */
    public static function send_daily_recap() {
        global $wpdb;

        $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
        $date_from = $yesterday . ' 00:00:00';
        $date_to   = $yesterday . ' 23:59:59';

        $operators = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}konektor_operators WHERE status = 'on'"
        );

        foreach ( $operators as $op ) {
            if ( empty( $op->telegram_chat_id ) ) continue;

            $leads = $wpdb->get_results( $wpdb->prepare(
                "SELECT l.*, c.name AS campaign_name
                 FROM {$wpdb->prefix}konektor_leads l
                 LEFT JOIN {$wpdb->prefix}konektor_campaigns c ON c.id = l.campaign_id
                 WHERE l.operator_id = %d AND l.created_at >= %s AND l.created_at <= %s
                 ORDER BY l.id ASC",
                $op->id, $date_from, $date_to
            ) );

            if ( empty( $leads ) ) continue;

            $day_label = date( 'd/m/Y', strtotime( $yesterday ) );
            $text  = "<b>Rekap Lead Kemarin - {$day_label}</b>\n";
            $text .= 'Total: ' . count( $leads ) . " lead\n\n";

            $buttons = [];
            foreach ( $leads as $idx => $lead ) {
                $dec   = Konektor_Lead::decrypt_lead( clone $lead );
                $no    = $idx + 1;
                $name  = trim( $dec->name  ?? '' );
                $phone = trim( $dec->phone ?? '' );
                $camp  = trim( $lead->campaign_name ?? '' );
                $stat  = $lead->status ?? 'new';

                $stat_label = [ 'new' => '🆕', 'contacted' => '📞', 'purchased' => '✅', 'cancelled' => '❌' ][ $stat ] ?? '❔';

                $text .= "{$no}. {$stat_label} <b>{$name}</b>";
                if ( $phone ) $text .= " | {$phone}";
                if ( $camp )  $text .= "\n    📋 {$camp}";
                $text .= "\n";

                $short_name = mb_substr( $name ?: "Lead {$no}", 0, 15 );
                $buttons[]  = [
                    [ 'text' => "✅ {$short_name} - Beli",  'callback_data' => 'status:purchased:'  . $lead->id ],
                    [ 'text' => "❌ {$short_name} - Batal", 'callback_data' => 'status:cancelled:' . $lead->id ],
                ];
            }

            self::send_message( $op->telegram_chat_id, $text, [ 'inline_keyboard' => $buttons ] );
        }
    }

    private static function get_cs_panel_url( $operator ) {
        global $wpdb;
        $token = $wpdb->get_var( $wpdb->prepare(
            "SELECT token FROM {$wpdb->prefix}konektor_operator_tokens WHERE operator_id = %d LIMIT 1",
            $operator->id
        ) );
        if ( ! $token ) {
            $token = Konektor_Operator::generate_panel_token( $operator->id );
        }
        $base = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        return home_url( "/{$base}/cs-panel/?token={$token}" );
    }

    /**
     * Handle webhook from Telegram (for CS to update lead status via bot commands)
     */
    public static function handle_webhook( $data ) {
        global $wpdb;

        // Handle callback queries (inline keyboard buttons)
        if ( ! empty( $data['callback_query'] ) ) {
            $callback      = $data['callback_query'];
            $callback_id   = $callback['id']   ?? '';
            $callback_data = $callback['data'] ?? '';
            $message       = $callback['message'] ?? [];
            $chat_id       = $callback['from']['id'] ?? ( $message['chat']['id'] ?? '' );
            $message_id    = $message['message_id'] ?? '';

            // Cari operator berdasarkan chat_id untuk validasi kepemilikan lead
            $operator = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}konektor_operators WHERE telegram_chat_id = %s LIMIT 1",
                (string) $chat_id
            ) );

            if ( ! $operator ) {
                self::answer_callback_query( $callback_id, '❌ Operator Telegram tidak terdaftar.' );
                return;
            }

            if ( strpos( $callback_data, 'followup:' ) === 0 ) {
                $lead_id = (int) substr( $callback_data, 9 );
                $lead    = Konektor_Lead::get( $lead_id );
                if ( $lead && (int) $lead->operator_id === (int) $operator->id ) {
                    Konektor_Lead::mark_followed_up( $lead_id );
                    if ( $lead->status === 'new' ) {
                        Konektor_Lead::update_status( $lead_id, 'contacted', 'Follow-up via Telegram', $operator->id );
                    }
                    self::answer_callback_query( $callback_id, '✅ Status diperbarui — Telah Di-Follow Up!' );
                    if ( $chat_id && $message_id ) {
                        $original_text = $message['text'] ?? '';
                        $updated_text  = $original_text . "\n\n✅ <b>Sudah Follow-Up</b>";
                        self::edit_message_text( $chat_id, $message_id, $updated_text, [ 'inline_keyboard' => [] ] );
                    }
                } else {
                    self::answer_callback_query( $callback_id, '❌ Lead tidak ditemukan atau bukan milik Anda.' );
                }

            } elseif ( strpos( $callback_data, 'status:' ) === 0 ) {
                $parts   = explode( ':', $callback_data, 3 );
                $status  = $parts[1] ?? '';
                $lead_id = isset( $parts[2] ) ? (int) $parts[2] : 0;
                $allowed = [ 'contacted', 'purchased', 'cancelled' ];

                if ( ! in_array( $status, $allowed, true ) ) {
                    self::answer_callback_query( $callback_id, '❌ Status tidak valid.' );
                } else {
                    $lead = Konektor_Lead::get( $lead_id );
                    if ( $lead && (int) $lead->operator_id === (int) $operator->id ) {
                        if ( $lead->status === $status ) {
                            self::answer_callback_query( $callback_id, 'ℹ️ Status sudah ' . $status . '.' );
                        } else {
                            $ok = Konektor_Lead::update_status( $lead_id, $status, 'Update status via Telegram', $operator->id );
                            if ( $ok ) {
                                self::answer_callback_query( $callback_id, '✅ Status berhasil diubah menjadi ' . ucfirst( $status ) . '.' );
                            } else {
                                self::answer_callback_query( $callback_id, '❌ Gagal memperbarui status lead.' );
                            }
                        }
                    } else {
                        self::answer_callback_query( $callback_id, '❌ Lead tidak ditemukan atau bukan milik Anda.' );
                    }
                }
            }
            return;
        }

        if ( empty( $data['message'] ) ) return;

        $msg     = $data['message'];
        $chat_id = $msg['chat']['id'] ?? '';
        $text    = trim( $msg['text'] ?? '' );

        if ( ! $chat_id || ! $text ) return;

        // Find operator by chat_id
        global $wpdb;
        $operator = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}konektor_operators WHERE telegram_chat_id = %s",
            $chat_id
        ) );

        if ( ! $operator ) {
            self::send_message( $chat_id, 'Anda tidak terdaftar sebagai operator.' );
            return;
        }

        // Commands: /status {lead_id} {status}
        if ( preg_match( '/^\/status\s+(\d+)\s+(\w+)$/i', $text, $m ) ) {
            $lead_id = (int) $m[1];
            $status  = strtolower( $m[2] );
            $result  = Konektor_Lead::update_status( $lead_id, $status, '', $operator->id );
            $reply   = $result ? "✅ Status lead #{$lead_id} diupdate ke: {$status}" : "❌ Gagal. Periksa ID lead dan status yang valid.";
            self::send_message( $chat_id, $reply );
            return;
        }

        // /block {lead_id} {reason}
        if ( preg_match( '/^\/block\s+(\d+)\s*(.*)$/i', $text, $m ) ) {
            $lead_id = (int) $m[1];
            $reason  = trim( $m[2] );
            Konektor_Blocker::also_block_lead( $lead_id, $operator->id, $reason );
            self::send_message( $chat_id, "🚫 Lead #{$lead_id} telah diblokir." );
            return;
        }

        // /leads
        if ( $text === '/leads' ) {
            $leads  = Konektor_Lead::get_all( [ 'operator_id' => $operator->id, 'status' => 'new', 'per_page' => 5 ] );
            $reply  = "📋 <b>Lead terbaru Anda (status: new):</b>\n";
            foreach ( $leads as $l ) {
                $d      = Konektor_Lead::decrypt_lead( clone $l );
                $reply .= "\n#{$l->id} - {$d->name} | {$d->phone} | {$l->created_at}";
            }
            if ( empty( $leads ) ) $reply .= "\nTidak ada lead baru.";
            self::send_message( $chat_id, $reply );
            return;
        }

        $help  = "Perintah tersedia:\n";
        $help .= "/leads - Lihat lead terbaru\n";
        $help .= "/status {id} {new|contacted|purchased|cancelled} - Update status\n";
        $help .= "/block {id} {alasan} - Blokir customer";
        self::send_message( $chat_id, $help );
    }
}
