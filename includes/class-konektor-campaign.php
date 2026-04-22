<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Campaign {

    public static function get_all( $args = [] ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'konektor_campaigns';
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['type'] ) ) {
            $where   .= ' AND type = %s';
            $params[] = $args['type'];
        }
        if ( isset( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY id DESC";
        return $params ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) : $wpdb->get_results( $sql );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}konektor_campaigns WHERE id = %d", $id
        ) );
    }

    public static function get_by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}konektor_campaigns WHERE slug = %s AND status = 'active'",
            sanitize_title( $slug )
        ) );
    }

    public static function get_url( $campaign ) {
        $base = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        return home_url( "/{$base}/{$campaign->slug}" );
    }

    public static function get_submit_url( $campaign ) {
        $base = Konektor_Helper::get_setting( 'base_slug', 'konektor' );
        return home_url( "/{$base}/{$campaign->slug}/submit" );
    }

    public static function generate_unique_slug( $name, $exclude_id = 0 ) {
        global $wpdb;
        $base = sanitize_title( $name );
        $slug = $base;
        $i    = 1;
        while ( true ) {
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}konektor_campaigns WHERE slug = %s AND id != %d",
                $slug, $exclude_id
            ) );
            if ( ! $existing ) break;
            $slug = $base . '-' . ( $i++ );
        }
        return $slug;
    }

    public static function save( $data, $id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'konektor_campaigns';

        // Slug: gunakan custom jika ada, otherwise generate dari nama
        $slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : self::generate_unique_slug( $data['name'], $id );
        if ( empty( $slug ) ) $slug = self::generate_unique_slug( $data['name'], $id );

        $row = [
            'name'                => sanitize_text_field( $data['name'] ),
            'slug'                => $slug,
            'type'                => in_array( $data['type'], ['form','wa_link'] ) ? $data['type'] : 'form',
            'store_name'          => sanitize_text_field( $data['store_name'] ?? '' ),
            'product_name'        => sanitize_text_field( $data['product_name'] ?? '' ),
            'form_config'         => ! empty( $data['form_config'] ) ? wp_json_encode( $data['form_config'] ) : null,
            'thanks_page_config'  => ! empty( $data['thanks_page_config'] ) ? wp_json_encode( $data['thanks_page_config'] ) : null,
            'pixel_config'        => ! empty( $data['pixel_config'] ) ? wp_json_encode( $data['pixel_config'] ) : null,
            'double_lead_enabled' => ! empty( $data['double_lead_enabled'] ) ? 1 : 0,
            'double_lead_message' => sanitize_textarea_field( $data['double_lead_message'] ?? '' ),
            'followup_message'    => sanitize_textarea_field( $data['followup_message'] ?? '' ),
            'allowed_domains'     => ! empty( $data['allowed_domains'] ) ? wp_json_encode( $data['allowed_domains'] ) : '[]',
            'block_enabled'       => ! empty( $data['block_enabled'] ) ? 1 : 0,
            'block_message'       => sanitize_textarea_field( $data['block_message'] ?? '' ),
            'status'              => in_array( $data['status'] ?? 'active', ['active','inactive'] ) ? $data['status'] : 'active',
        ];

        if ( $id ) {
            $wpdb->update( $table, $row, [ 'id' => $id ] );
        } else {
            $wpdb->insert( $table, $row );
            $id = $wpdb->insert_id;
        }

        // Update operator assignments
        if ( isset( $data['operators'] ) && is_array( $data['operators'] ) ) {
            $wpdb->delete( $wpdb->prefix . 'konektor_campaign_operators', [ 'campaign_id' => $id ] );
            foreach ( $data['operators'] as $op ) {
                $wpdb->insert( $wpdb->prefix . 'konektor_campaign_operators', [
                    'campaign_id' => $id,
                    'operator_id' => (int) $op['id'],
                    'weight'      => max( 1, min( 10, (int) ( $op['weight'] ?? 1 ) ) ),
                ] );
            }
        }

        return $id;
    }

    public static function delete( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'konektor_campaigns', [ 'id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'konektor_campaign_operators', [ 'campaign_id' => $id ] );
    }

    public static function get_operators( $campaign_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT o.*, co.weight
             FROM {$wpdb->prefix}konektor_operators o
             JOIN {$wpdb->prefix}konektor_campaign_operators co ON co.operator_id = o.id
             WHERE co.campaign_id = %d ORDER BY o.id ASC",
            $campaign_id
        ) );
    }

    // Script kecil untuk set/baca cookie konektor_vid di browser visitor
    private static function get_cookie_script() {
        return "<script>\n"
             . "(function(){var n='konektor_vid',c=document.cookie.match('(?:^|;)\\\\s*'+n+'=([^;]*)');if(!c){var v='';var ch='0123456789abcdef';for(var i=0;i<32;i++)v+=ch[Math.floor(Math.random()*16)];document.cookie=n+'='+v+';path=/;max-age=31536000;SameSite=Lax';}})()\n"
             . "</script>\n";
    }

    public static function get_wa_embed_code( $campaign ) {
        $url        = self::get_url( $campaign );
        $store      = esc_html( $campaign->store_name ?? '' );
        $product    = esc_html( $campaign->product_name ?? '' );
        $camp_name  = esc_html( $campaign->name );

        $css = "<style>\n"
             . ".knk-wa-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:480px;margin:0 auto;padding:28px 24px;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center}\n"
             . ".knk-wa-wrap *{box-sizing:border-box}\n"
             . ".knk-wa-store{margin:0 0 4px;font-size:12px;color:#64748b}\n"
             . ".knk-wa-product{margin:0 0 20px;font-size:20px;font-weight:800;color:#0f172a}\n"
             . ".knk-wa-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px 20px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;font-family:inherit}\n"
             . ".knk-wa-btn:hover{background:#15803d}\n"
             . ".knk-wa-icon{width:22px;height:22px;fill:#fff}\n"
             . "</style>\n";

        $wa_icon = '<svg class="knk-wa-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.852L0 24l6.335-1.508A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.002-1.368l-.36-.214-3.72.885.916-3.617-.234-.372A9.818 9.818 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>';

        $header = '';
        if ( $store )   $header .= "  <p class=\"knk-wa-store\">{$store}</p>\n";
        if ( $product ) $header .= "  <p class=\"knk-wa-product\">{$product}</p>\n";

        $cookie_js = self::get_cookie_script();

        $click_js = "<script>\n"
            . "(function(){\n"
            . "  var btn=document.currentScript.previousElementSibling.querySelector('.knk-wa-btn');\n"
            . "  if(!btn)return;\n"
            . "  btn.addEventListener('click',function(e){\n"
            . "    e.preventDefault();\n"
            . "    var href=btn.getAttribute('href');\n"
            . "    // get/create cookie before redirect so server sees it\n"
            . "    var vid=(document.cookie.match('(?:^|;)\\\\s*konektor_vid=([^;]*)')||[])[1]||'';\n"
            . "    if(!vid){var ch='0123456789abcdef',v='';for(var i=0;i<32;i++)v+=ch[Math.floor(Math.random()*16)];document.cookie='konektor_vid='+v+';path=/;max-age=31536000;SameSite=Lax';vid=v;}\n"
            . "    // send _vid and _src so server records cookie + source URL even cross-domain\n"
            . "    var dest=href+(href.indexOf('?')>=0?'&':'?')+'_vid='+encodeURIComponent(vid)+'&_src='+encodeURIComponent(window.location.href);\n"
            . "    window.location.href=dest;\n"
            . "  });\n"
            . "})()\n"
            . "</script>\n";

        return "<!-- Konektor WA Link: {$camp_name} -->\n"
             . $css
             . $cookie_js
             . "<div class=\"knk-wa-wrap\">\n"
             . $header
             . "  <a href=\"" . esc_url( $url ) . "\" class=\"knk-wa-btn\">\n"
             . "    {$wa_icon}\n"
             . "    Hubungi via WhatsApp\n"
             . "  </a>\n"
             . "</div>\n"
             . $click_js
             . "<!-- End Konektor WA Link -->";
    }

    public static function get_embed_code( $campaign ) {
        $cfg        = self::get_form_config( $campaign );
        $submit_url = self::get_submit_url( $campaign );
        $tpl        = $cfg['template'] ?? 'modern';
        $fields     = array_merge( $cfg['fields'] ?? [], $cfg['extra_fields'] ?? [] );
        $btn_label  = esc_html( $cfg['submit_label'] ?? 'Kirim Sekarang' );

        $schemes = [
            'modern'   => [ '#fff',    '#2563eb', '#0f172a', '#e2e8f0', '#fff' ],
            'classic'  => [ '#fff',    '#dc2626', '#1a1a1a', '#ccc',    '#fff' ],
            'minimal'  => [ '#fafafa', '#111',    '#111',    '#e2e8f0', '#fff' ],
            'card'     => [ '#f8fafc', '#2563eb', '#0f172a', '#e2e8f0', '#fff' ],
            'gradient' => [ '#1e3a5f', '#38bdf8', '#fff',    'rgba(255,255,255,.25)', '#1e3a5f' ],
        ];
        [ $bg, $accent, $text, $border, $btn_text ] = $schemes[ $tpl ] ?? $schemes['modern'];
        $inp_bg = $tpl === 'gradient' ? 'rgba(255,255,255,.1)' : '#fff';

        // CSS block — classes instead of inline styles
        $css = "<style>\n"
             . ".knk-form{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:480px;margin:0 auto;padding:28px 24px;background:{$bg};border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);color:{$text};box-sizing:border-box}\n"
             . ".knk-form *{box-sizing:border-box}\n"
             . ".knk-form-header{margin-bottom:18px;text-align:center}\n"
             . ".knk-form-store{margin:0;font-size:12px;opacity:.7}\n"
             . ".knk-form-product{margin:6px 0 0;font-size:20px;font-weight:800}\n"
             . ".knk-form-field{margin-bottom:14px}\n"
             . ".knk-form-label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px;color:{$text};opacity:.8}\n"
             . ".knk-form-req{color:#ef4444}\n"
             . ".knk-form-input,.knk-form-textarea,.knk-form-select{width:100%;padding:10px 12px;font-size:14px;border:1.5px solid {$border};border-radius:7px;background:{$inp_bg};color:{$text};outline:none;font-family:inherit}\n"
             . ".knk-form-textarea{resize:vertical;min-height:80px}\n"
             . ".knk-form-btn{width:100%;padding:13px;font-size:15px;font-weight:700;cursor:pointer;border:none;border-radius:8px;background:{$accent};color:{$btn_text};font-family:inherit;margin-top:4px}\n"
             . ".knk-form-btn:hover{opacity:.92}\n"
             . "</style>\n";

        // Form fields HTML
        $fh = '';
        foreach ( $fields as $f ) {
            if ( empty( $f['enabled'] ) ) continue;
            $name = esc_attr( $f['name'] ?? sanitize_title( $f['label'] ?? '' ) );
            $lbl  = esc_html( $f['label'] ?? '' );
            $type = esc_attr( $f['type'] ?? 'text' );
            $req  = ! empty( $f['required'] );
            $ra   = $req ? ' required' : '';
            $rl   = $req ? ' <span class="knk-form-req">*</span>' : '';
            $fh  .= "  <div class=\"knk-form-field\">\n";
            $fh  .= "    <label class=\"knk-form-label\">{$lbl}{$rl}</label>\n";
            if ( $type === 'textarea' ) {
                $fh .= "    <textarea name=\"{$name}\" rows=\"3\" placeholder=\"{$lbl}\" class=\"knk-form-textarea\"{$ra}></textarea>\n";
            } elseif ( $type === 'select' && ! empty( $f['options'] ) ) {
                $fh .= "    <select name=\"{$name}\" class=\"knk-form-select\"{$ra}>\n";
                $fh .= "      <option value=\"\">-- Pilih --</option>\n";
                foreach ( (array) $f['options'] as $opt ) {
                    $fh .= '      <option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . "</option>\n";
                }
                $fh .= "    </select>\n";
            } else {
                $fh .= "    <input type=\"{$type}\" name=\"{$name}\" placeholder=\"{$lbl}\" class=\"knk-form-input\"{$ra}>\n";
            }
            $fh .= "  </div>\n";
        }

        $header = '';

        return "<!-- Konektor Form: " . esc_html( $campaign->name ) . " -->\n"
             . $css
             . self::get_cookie_script()
             . "<form method=\"POST\" action=\"" . esc_url( $submit_url ) . "\" accept-charset=\"UTF-8\" class=\"knk-form\">\n"
             . "  <input type=\"hidden\" name=\"_html_embed\" value=\"1\">\n"
             . $header
             . $fh
             . "  <div class=\"knk-form-field\">\n"
             . "    <button type=\"submit\" class=\"knk-form-btn\">{$btn_label}</button>\n"
             . "  </div>\n"
             . "</form>\n"
             . "<!-- End Konektor Form -->";
    }

    public static function get_form_config( $campaign ) {
        $cfg = isset( $campaign->form_config ) && $campaign->form_config
            ? json_decode( $campaign->form_config, true )
            : [];
        // Merge with defaults so all keys always exist
        $defaults = self::default_form_config();
        if ( empty( $cfg['fields'] ) ) $cfg['fields'] = $defaults['fields'];
        if ( empty( $cfg['template'] ) ) $cfg['template'] = $defaults['template'];
        if ( empty( $cfg['submit_label'] ) ) $cfg['submit_label'] = $defaults['submit_label'];
        return $cfg;
    }

    public static function get_thanks_config( $campaign ) {
        $cfg = isset( $campaign->thanks_page_config ) && $campaign->thanks_page_config
            ? json_decode( $campaign->thanks_page_config, true )
            : [];
        $defaults = self::default_thanks_config();
        foreach ( $defaults as $k => $v ) {
            if ( ! isset( $cfg[ $k ] ) ) $cfg[ $k ] = $v;
        }
        return $cfg;
    }

    private static function default_form_config() {
        return [
            'template'     => 'modern',
            'submit_label' => 'Kirim Sekarang',
            'fields'       => [
                [ 'name' => 'name',    'label' => 'Nama',        'type' => 'text',     'required' => true,  'enabled' => true ],
                [ 'name' => 'phone',   'label' => 'No HP',       'type' => 'tel',      'required' => true,  'enabled' => true ],
                [ 'name' => 'email',   'label' => 'Email',       'type' => 'email',    'required' => false, 'enabled' => false ],
                [ 'name' => 'address', 'label' => 'Alamat',      'type' => 'textarea', 'required' => false, 'enabled' => false ],
                [ 'name' => 'quantity','label' => 'Jumlah',      'type' => 'number',   'required' => false, 'enabled' => false ],
                [ 'name' => 'custom_message', 'label' => 'Pesan', 'type' => 'textarea','required' => false, 'enabled' => false ],
            ],
        ];
    }

    private static function default_thanks_config() {
        return [
            'description'      => 'Terima kasih! Pesanan Anda sedang kami proses.',
            'redirect_type'    => 'cs',
            'redirect_url'     => '',
            'redirect_cs_type' => 'whatsapp',
            'custom_message'   => 'Halo [oname], saya [cname] ingin memesan [product] sebanyak [quantity].',
            'delay_redirect'   => 3,
        ];
    }
}
