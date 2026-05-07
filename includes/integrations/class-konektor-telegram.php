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

        $decrypt   = Konektor_Lead::decrypt_lead( clone $lead );
        $panel_url = self::get_cs_panel_url( $operator );

        $is_wa_click = empty( $decrypt->name ) && empty( $decrypt->phone );

        $text = "🔔 <b>" . ( $is_wa_click ? 'WA Link Diklik!' : 'Lead Baru!' ) . "</b>\n\n";
        $text .= "📦 <b>Kampanye:</b> {$campaign->name}\n";

        if ( $is_wa_click ) {
            // WA Link — no form data, show tracking info instead
            if ( $lead->ip_address ) $text .= "🌐 <b>IP:</b> {$lead->ip_address}\n";
            if ( $lead->source_url ) $text .= "🔗 <b>Sumber:</b> {$lead->source_url}\n";
        } else {
            $text .= "👤 <b>Nama:</b> {$decrypt->name}\n";
            $text .= "📱 <b>HP:</b> {$decrypt->phone}\n";
            if ( $decrypt->email )          $text .= "📧 <b>Email:</b> {$decrypt->email}\n";
            if ( $decrypt->address )        $text .= "📍 <b>Alamat:</b> {$decrypt->address}\n";
            if ( $decrypt->quantity )       $text .= "🛒 <b>Jumlah:</b> {$decrypt->quantity}\n";
            if ( $decrypt->custom_message ) $text .= "💬 <b>Pesan:</b> {$decrypt->custom_message}\n";
        }

        $text .= "\n🕐 " . current_time( 'Y-m-d H:i' );

        $reply_markup = null;

        // Follow-up link langsung ke customer (jika ada nomor HP dan pesan follow-up)
        if ( $decrypt->phone && ! empty( $campaign->followup_message ) ) {
            $followup_url = Konektor_Rotator::get_followup_url( $campaign, $decrypt, $operator );
            if ( $followup_url ) {
                $inline_keyboard = [
                    [
                        [ 'text' => '📲 Follow-Up Customer', 'url' => $followup_url ],
                    ],
                    [
                        [ 'text' => '✅ Tandai Sudah Follow-Up', 'callback_data' => 'followup:' . $lead->id ],
                    ],
                ];
                $reply_markup = [ 'inline_keyboard' => $inline_keyboard ];
            }
        }

        if ( $panel_url ) $text .= "\n🔗 <a href='{$panel_url}'>Buka Panel CS</a>";

        self::send_message( $operator->telegram_chat_id, $text, $reply_markup );
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
        // Handle callback queries (inline keyboard buttons)
        if ( ! empty( $data['callback_query'] ) ) {
            $callback = $data['callback_query'];
            $callback_id = $callback['id'] ?? '';
            $callback_data = $callback['data'] ?? '';
            $message = $callback['message'] ?? [];
            $chat_id = $message['chat']['id'] ?? '';
            $message_id = $message['message_id'] ?? '';

            if ( strpos( $callback_data, 'followup:' ) === 0 ) {
                $lead_id = (int) substr( $callback_data, 9 );
                Konektor_Lead::mark_followed_up( $lead_id );

                self::answer_callback_query( $callback_id, "✅ Lead #{$lead_id} sudah di-follow-up." );

                if ( $chat_id && $message_id ) {
                    $original_text = $message['text'] ?? '';
                    $updated_text  = $original_text . "\n\n✅ <b>Sudah Follow-Up</b>";
                    self::edit_message_text( $chat_id, $message_id, $updated_text, [ 'inline_keyboard' => [] ] );
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
