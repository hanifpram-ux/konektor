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
            'type'                => in_array( $data['type'], ['form','wa_link','link'] ) ? $data['type'] : 'form',
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

    // JS helper: baca/buat visitor ID cookie
    private static function get_cookie_js( $campaign ) {
        return "  function _knkVid(){var m=document.cookie.match('(?:^|;)\\\\s*konektor_vid=([^;]*)');if(m)return decodeURIComponent(m[1]);var ch='0123456789abcdef',v='';for(var i=0;i<32;i++)v+=ch[Math.floor(Math.random()*16)];document.cookie='konektor_vid='+v+';path=/;max-age=31536000;SameSite=Lax';return v;}\n";
    }

    /**
     * Buat pixel ping JS dengan dedup — kalau pixel ID sama sudah di-fire oleh embed lain
     * di halaman yang sama, skip. Key berdasarkan kombinasi pixel ID semua platform.
     */
    private static function get_pixel_ping( $campaign ) {
        $meta_cfg   = Konektor_Meta::get_config( $campaign );
        $tiktok_cfg = Konektor_Tiktok::get_config( $campaign );
        $snack_cfg  = Konektor_Snack::get_config( $campaign );

        // Buat key unik dari kombinasi pixel ID yang aktif
        $key_parts = array_filter([
            $meta_cfg['pixel_id']   ?? '',
            $tiktok_cfg['pixel_id'] ?? '',
            $snack_cfg['pixel_id']  ?? '',
        ]);

        // Kalau tidak ada pixel yang dikonfigurasi, skip ping
        if ( empty( $key_parts ) ) return '';

        $dedup_key = esc_js( 'knk_px_' . md5( implode( '|', $key_parts ) ) );
        $pixel_url = esc_js( home_url( '/' . Konektor_Helper::get_setting( 'base_slug', 'konektor' ) . '/' . $campaign->slug . '/pixel/' ) );

        // Cek global flag — kalau pixel ID combo ini sudah di-fire di halaman ini, skip
        return "<script>(function(){"
             . "if(window['{$dedup_key}'])return;"
             . "window['{$dedup_key}']=1;"
             . "var u='" . $pixel_url . "';"
             . "fetch(u+'?url='+encodeURIComponent(location.href),{method:'GET',mode:'no-cors',credentials:'omit'}).catch(function(){});"
             . "})();</script>\n";
    }

    public static function get_wa_embed_code( $campaign ) {
        $url       = self::get_url( $campaign );
        $camp_name = esc_html( $campaign->name );
        $store     = esc_html( $campaign->store_name ?? '' );

        $cfg     = self::get_form_config( $campaign );
        $cs      = $cfg['custom_style'] ?? [];
        $acc     = $cs['color_accent']   ?: '#16a34a';
        $btxt    = $cs['color_btn_text'] ?: '#ffffff';
        $maxw    = $cs['max_width']      ?: '480px';
        $rad     = $cs['border_radius']  ?: '12px';
        $pad     = $cs['padding']        ?: '28px 24px';
        $bfsz    = $cs['btn_font_size']  ?: '15px';
        $bg_c    = $cs['color_bg']       ?: '#ffffff';
        $btn_lbl = esc_html( $cfg['submit_label'] ?? 'Hubungi Sekarang' );
        $url_esc = esc_url( $url );

        $wa_icon = '<svg style="width:20px;height:20px;fill:currentColor;flex-shrink:0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.852L0 24l6.335-1.508A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.002-1.368l-.36-.214-3.72.885.916-3.617-.234-.372A9.818 9.818 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>';

        $css = "<style>"
             . ".knk-wa{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:{$maxw};margin:0 auto;padding:{$pad};background:{$bg_c};border-radius:{$rad};box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center;box-sizing:border-box}"
             . ".knk-wa *{box-sizing:border-box}"
             . ".knk-wa-store{margin:0 0 16px;font-size:12px;color:#64748b}"
             . ".knk-wa-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px 20px;background:{$acc};color:{$btxt};border:none;border-radius:8px;font-size:{$bfsz};font-weight:700;cursor:pointer;text-decoration:none;font-family:inherit;transition:opacity .2s}"
             . ".knk-wa-btn:hover{opacity:.88}"
             . "</style>\n";

        $js = "<script>(function(){\n"
            . self::get_cookie_js( $campaign )
            . "  var wrap=document.currentScript.previousElementSibling;\n"
            . "  var btn=wrap.querySelector('.knk-wa-btn');\n"
            . "  if(!btn)return;\n"
            . "  btn.addEventListener('click',function(e){\n"
            . "    e.preventDefault();\n"
            . "    var vid=_knkVid(),href=btn.getAttribute('href');\n"
            . "    window.location.href=href+(href.indexOf('?')>=0?'&':'?')+'_vid='+encodeURIComponent(vid)+'&_src='+encodeURIComponent(window.location.href);\n"
            . "  });\n"
            . "})()</script>\n";

        $google_html = Konektor_Google::get_script( $campaign, 'page_load' );
        $pixel_ping  = self::get_pixel_ping( $campaign );

        $header = $store ? "<p class=\"knk-wa-store\">{$store}</p>\n" : '';

        return "<!-- Konektor Link: {$camp_name} -->\n"
             . $google_html . $pixel_ping . $css
             . "<div class=\"knk-wa\">\n"
             . $header
             . "  <a href=\"{$url_esc}\" class=\"knk-wa-btn\">{$wa_icon} {$btn_lbl}</a>\n"
             . "</div>\n"
             . $js
             . "<!-- End Konektor Link -->";
    }

    public static function get_embed_code( $campaign ) {
        $cfg        = self::get_form_config( $campaign );
        $submit_url = esc_url( self::get_submit_url( $campaign ) );
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

        $cs        = $cfg['custom_style'] ?? [];
        $bg        = $cs['color_bg']      ?: $bg;
        $accent    = $cs['color_accent']  ?: $accent;
        $text      = $cs['color_text']    ?: $text;
        $border    = $cs['color_border']  ?: $border;
        $btn_text  = $cs['color_btn_text']?: $btn_text;
        $max_w     = $cs['max_width']     ?: '480px';
        $radius    = $cs['border_radius'] ?: '12px';
        $padding   = $cs['padding']       ?: '28px 24px';
        $fsize     = $cs['font_size']     ?: '14px';
        $btn_fsize = $cs['btn_font_size'] ?: '15px';

        $css = "<style>"
             . ".knk-form{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:{$max_w};margin:0 auto;padding:{$padding};background:{$bg};border-radius:{$radius};box-shadow:0 2px 16px rgba(0,0,0,.1);color:{$text};box-sizing:border-box}"
             . ".knk-form *{box-sizing:border-box}"
             . ".knk-form-field{margin-bottom:16px}"
             . ".knk-form-label{display:block;font-size:14px;font-weight:600;margin-bottom:6px;color:{$text}}"
             . ".knk-form-req{color:#ef4444;margin-left:2px}"
             . ".knk-form-input,.knk-form-textarea,.knk-form-select{width:100%;padding:12px 14px;font-size:{$fsize};border:1.5px solid {$border};border-radius:8px;background:{$inp_bg};color:{$text};outline:none;font-family:inherit;transition:border-color .15s}"
             . ".knk-form-input:focus,.knk-form-textarea:focus,.knk-form-select:focus{border-color:{$accent}}"
             . ".knk-form-textarea{resize:vertical;min-height:80px}"
             . ".knk-form-btn{width:100%;padding:15px;font-size:{$btn_fsize};font-weight:700;cursor:pointer;border:none;border-radius:8px;background:{$accent};color:{$btn_text};font-family:inherit;margin-top:4px;transition:opacity .15s}"
             . ".knk-form-btn:hover:not(:disabled){opacity:.88}"
             . ".knk-form-btn:disabled{opacity:.45;cursor:not-allowed}"
             . ".knk-form-err{display:none;padding:10px 14px;border-radius:7px;margin-bottom:12px;font-size:13px;font-weight:600;background:#fee2e2;color:#b91c1c}"
             . ".knk-form-err.show{display:block}"
             . "</style>\n";

        $fh = '';
        foreach ( $fields as $f ) {
            if ( empty( $f['enabled'] ) ) continue;
            $name = esc_attr( $f['name'] ?? sanitize_title( $f['label'] ?? '' ) );
            $lbl  = esc_html( $f['label'] ?? '' );
            $ph   = esc_attr( $f['placeholder'] ?? $f['label'] ?? '' );
            $type = esc_attr( $f['type'] ?? 'text' );
            $req  = ! empty( $f['required'] );
            $ra   = $req ? ' required' : '';
            $rl   = $req ? ' <span class="knk-form-req">*</span>' : '';
            $fh  .= "<div class=\"knk-form-field\"><label class=\"knk-form-label\">{$lbl}{$rl}</label>";
            if ( $type === 'textarea' ) {
                $fh .= "<textarea name=\"{$name}\" rows=\"3\" placeholder=\"{$ph}\" class=\"knk-form-textarea\"{$ra}></textarea>";
            } elseif ( $type === 'select' && ! empty( $f['options'] ) ) {
                $fh .= "<select name=\"{$name}\" class=\"knk-form-select\"{$ra}><option value=\"\">-- Pilih --</option>";
                foreach ( (array) $f['options'] as $opt ) {
                    $fh .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
                }
                $fh .= "</select>";
            } else {
                $extra = ( $name === 'phone' ) ? ' inputmode="numeric" autocomplete="tel"' : '';
                $fh .= "<input type=\"{$type}\" name=\"{$name}\" placeholder=\"{$ph}\" class=\"knk-form-input\"{$ra}{$extra}>";
            }
            $fh .= "</div>\n";
        }

        $header = '';
        if ( $campaign->store_name ) {
            $header .= '<p class="knk-form-label" style="text-align:center;margin-bottom:4px">' . esc_html( $campaign->store_name ) . '</p>';
        }
        if ( $campaign->product_name ) {
            $header .= '<p style="text-align:center;font-size:18px;font-weight:800;margin-bottom:16px">' . esc_html( $campaign->product_name ) . '</p>';
        }

        $submit_url_js  = esc_js( self::get_submit_url( $campaign ) );
        $meta_cfg_js    = Konektor_Meta::get_config( $campaign );
        $tiktok_cfg_js  = Konektor_Tiktok::get_config( $campaign );
        $google_cfg_js  = Konektor_Google::get_config( $campaign );

        $js = "<script>(function(){\n"
            . self::get_cookie_js( $campaign )
            . "  var url='" . $submit_url_js . "';\n"
            . "  var wrap=document.currentScript.previousElementSibling;\n"
            . "  var form=wrap.querySelector('.knk-form');\n"
            . "  var err=wrap.querySelector('.knk-form-err');\n"
            . "  var btn=form?form.querySelector('.knk-form-btn'):null;\n"
            . "  if(!form||!btn)return;\n"
            // ── Format phone: hanya angka, strip non-digit, awalan 0 / 62
            . "  var phoneEl=form.querySelector('input[name=phone]');\n"
            . "  if(phoneEl){\n"
            . "    phoneEl.addEventListener('input',function(){\n"
            . "      var v=this.value.replace(/[^0-9]/g,'');\n"
            . "      this.value=v;\n"
            . "    });\n"
            . "    phoneEl.addEventListener('blur',function(){\n"
            . "      var v=this.value.replace(/[^0-9]/g,'');\n"
            . "      if(v.startsWith('62'))v='0'+v.slice(2);\n"
            . "      this.value=v;\n"
            . "    });\n"
            . "  }\n"
            // ── Disable/enable tombol berdasarkan field required
            . "  function checkReady(){\n"
            . "    var ok=true;\n"
            . "    form.querySelectorAll('[required]').forEach(function(el){if(!el.value.trim())ok=false;});\n"
            . "    btn.disabled=!ok;\n"
            . "  }\n"
            . "  form.addEventListener('input',checkReady);\n"
            . "  form.addEventListener('change',checkReady);\n"
            . "  checkReady();\n"
            // ── Submit via fetch JSON
            . "  form.addEventListener('submit',function(e){\n"
            . "    e.preventDefault();\n"
            . "    btn.disabled=true;err.className='knk-form-err';\n"
            . "    var data={_vid:_knkVid(),source_url:window.location.href};\n"
            . "    new FormData(form).forEach(function(v,k){data[k]=v;});\n"
            . "    fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify(data)})\n"
            . "    .then(function(r){return r.json();})\n"
            . "    .then(function(res){\n"
            . "      if(res.thanks_page_url){\n"
            . "        if(!res.double){\n"
            // Tembak pixel form_submit sebelum redirect — hanya untuk lead baru
            . "          if(window.fbq)fbq('track'," . wp_json_encode( $meta_cfg_js['form_submit_event'] ?? 'Lead' ) . ");\n"
            . "          if(window.ttq)ttq.track(" . wp_json_encode( $tiktok_cfg_js['form_submit_event'] ?? 'SubmitForm' ) . ");\n"
            . "          if(window.gtag&&" . wp_json_encode( $google_cfg_js['conversion_id'] ?? '' ) . "){\n"
            . "            gtag('event','conversion',{'send_to':'AW-" . esc_js( $google_cfg_js['conversion_id'] ?? '' ) . "/" . esc_js( $google_cfg_js['form_submit_label'] ?? '' ) . "'});\n"
            . "          }\n"
            . "        }\n"
            . "        window.location.href=res.thanks_page_url;return;\n"
            . "      }\n"
            . "      err.textContent=res.message||'Terjadi kesalahan.';err.className='knk-form-err show';checkReady();\n"
            . "    })\n"
            . "    .catch(function(){err.textContent='Koneksi gagal, coba lagi.';err.className='knk-form-err show';checkReady();});\n"
            . "  });\n"
            . "})()</script>\n";

        // Google Ads — satu-satunya yang tetap browser (industry standard: butuh gclid dari browser)
        $google_html = Konektor_Google::get_script( $campaign, 'page_load' );

        $pixel_ping = self::get_pixel_ping( $campaign );

        return "<!-- Konektor Form: " . esc_html( $campaign->name ) . " -->\n"
             . $google_html . $pixel_ping . $css
             . "<div class=\"knk-form-wrap\">\n"
             . "  <div class=\"knk-form-err\"></div>\n"
             . "  <form class=\"knk-form\" novalidate>\n"
             . $header . $fh
             . "    <div class=\"knk-form-field\"><button type=\"submit\" class=\"knk-form-btn\">{$btn_label}</button></div>\n"
             . "  </form>\n"
             . "</div>\n"
             . $js
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
