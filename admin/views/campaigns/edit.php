<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_new      = ! $campaign;
$preset_type = $preset_type ?? ( $campaign->type ?? 'form' );
$form_cfg    = Konektor_Campaign::get_form_config( $campaign ?? (object)[] );
$thanks_cfg  = Konektor_Campaign::get_thanks_config( $campaign ?? (object)[] );
$pixel_cfg   = ($campaign && $campaign->pixel_config) ? json_decode($campaign->pixel_config, true) : [];
$meta_cfg    = $pixel_cfg['meta']   ?? [];
$google_cfg  = $pixel_cfg['google'] ?? [];
$tiktok_cfg  = $pixel_cfg['tiktok'] ?? [];
$snack_cfg   = $pixel_cfg['snack']  ?? [];
$assigned    = $assigned ?? [];
$base_slug   = Konektor_Helper::get_setting('base_slug', 'konektor');
$rtype       = $thanks_cfg['redirect_type'] ?? 'cs';
$cstyle      = $form_cfg['custom_style'] ?? [];

// Template thumb renderer
function knk_tpl_thumb($val) {
  $cfg = [
    'modern'   => ['#2563eb','#fff','#e2e8f0'],
    'classic'  => ['#dc2626','#fff','#ccc'],
    'minimal'  => ['#111','#fff','#e2e8f0'],
    'card'     => ['linear-gradient(135deg,#2563eb,#7c3aed)','#f8fafc','#e2e8f0'],
    'gradient' => ['#fff','#1e3a5f','rgba(255,255,255,.3)'],
  ];
  [$btn,$bg,$inp] = $cfg[$val] ?? $cfg['modern'];
  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 76 52" width="76" height="52">'
    . '<rect width="76" height="52" fill="' . $bg . '"/>'
    . '<rect x="8" y="10" width="60" height="6" rx="2" fill="' . $inp . '"/>'
    . '<rect x="8" y="20" width="60" height="6" rx="2" fill="' . $inp . '"/>'
    . '<rect x="8" y="30" width="40" height="6" rx="2" fill="' . $inp . '"/>'
    . '<rect x="8" y="40" width="60" height="8" rx="3" fill="' . $btn . '"/>'
    . '</svg>';
}

// Form preview renderer
function knk_form_preview($cfg, $campaign) {
  $tpl    = $cfg['template']    ?? 'modern';
  $fields = $cfg['fields']      ?? [];
  $label  = $cfg['submit_label'] ?? 'Kirim Sekarang';
  ob_start(); ?>
  <div class="konektor-form-wrap konektor-tpl-<?php echo esc_attr($tpl); ?>">
    <div class="konektor-form">
      <?php if ($campaign && ($campaign->store_name || $campaign->product_name)) : ?>
      <div class="konektor-form-header">
        <?php if (!empty($campaign->store_name))   echo '<p class="konektor-store">'   . esc_html($campaign->store_name)   . '</p>'; ?>
        <?php if (!empty($campaign->product_name)) echo '<h2 class="konektor-product">' . esc_html($campaign->product_name) . '</h2>'; ?>
      </div>
      <?php endif; ?>
      <?php foreach ($fields as $f) :
        if (empty($f['enabled'])) continue;
        $req = !empty($f['required']); ?>
      <div class="konektor-field">
        <label><?php echo esc_html($f['label']); ?><?php if ($req) echo '<span class="req">*</span>'; ?></label>
        <?php if (($f['type']??'text') === 'textarea') : ?>
          <textarea placeholder="<?php echo esc_attr($f['label']); ?>"></textarea>
        <?php else : ?>
          <input type="<?php echo esc_attr($f['type']??'text'); ?>" placeholder="<?php echo esc_attr($f['label']); ?>">
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <div class="konektor-field">
        <button type="button" class="konektor-btn-submit"><?php echo esc_html($label); ?></button>
      </div>
    </div>
  </div>
  <?php return ob_get_clean();
}
?>

<!-- Operator data for JS search -->
<script type="application/json" id="knk-op-data"><?php
$op_data = array_map(fn($o) => ['id' => (int)$o->id, 'name' => $o->name, 'type' => $o->type], $operators ?? []);
echo wp_json_encode($op_data);
?></script>

<div class="wrap knk-wrap">

<!-- Page Header -->
<div class="knk-ph">
  <div class="knk-ph-left">
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns'); ?>" class="knk-breadcrumb"><i class="fa-solid fa-arrow-left"></i> Kampanye</a>
    <h1><?php echo $is_new ? 'Kampanye Baru' : 'Edit: <em>' . esc_html($campaign->name) . '</em>'; ?></h1>
  </div>
  <div class="knk-ph-right">
    <span class="knk-status" id="knk-save-status"></span>
    <button type="submit" form="konektor-campaign-form" class="knk-btn knk-btn-primary knk-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
  </div>
</div>

<?php if ($campaign) :
  $camp_url   = Konektor_Campaign::get_url($campaign);
  $submit_url = Konektor_Campaign::get_submit_url($campaign);
  $embed_code = Konektor_Campaign::get_embed_code($campaign);
?>
<div class="knk-url-banner">
  <div class="knk-url-banner-icon">
    <?php echo $campaign->type === 'wa_link'
      ? '<i class="fa-solid fa-link" style="font-size:22px;color:var(--p)"></i>'
      : '<i class="fa-solid fa-clipboard-list" style="font-size:22px;color:var(--p)"></i>'; ?>
  </div>
  <div class="knk-url-banner-body">
    <?php if ($campaign->type === 'wa_link') :
        $wa_embed_code = Konektor_Campaign::get_wa_embed_code($campaign);
    ?>
      <div class="knk-url-banner-label">URL Link — pasang di tombol Elementor / landing page</div>
      <div class="knk-url-banner-row">
        <input class="knk-url-field" readonly value="<?php echo esc_url($camp_url); ?>" onclick="this.select()">
        <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent='Disalin'"><i class="fa-regular fa-copy"></i> Salin</button>
        <a href="<?php echo esc_url($camp_url); ?>" target="_blank" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka</a>
      </div>
      <button type="button" class="knk-embed-toggle" onclick="this.nextElementSibling.classList.toggle('open');this.innerHTML=this.nextElementSibling.classList.contains('open')?'<i class=\'fa-solid fa-chevron-up\'></i> Sembunyikan kode':'<i class=\'fa-solid fa-chevron-down\'></i> Lihat embed code (termasuk pixel)'"><i class="fa-solid fa-chevron-down"></i> Lihat embed code (termasuk pixel)</button>
      <div class="knk-embed-wrap">
        <div style="display:flex;justify-content:flex-end;gap:6px;margin-bottom:6px">
          <span style="font-size:11px;color:var(--g400);align-self:center"><i class="fa-solid fa-circle-info"></i> Pixel ikut tersimpan dalam kode ini setelah disimpan</span>
          <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm" onclick="navigator.clipboard.writeText(this.closest('.knk-embed-wrap').querySelector('textarea').value);this.innerHTML='<i class=\'fa-solid fa-check\'></i> Disalin'"><i class="fa-regular fa-copy"></i> Salin</button>
        </div>
        <textarea class="knk-embed-box" readonly onclick="this.select()"><?php echo esc_html($wa_embed_code); ?></textarea>
      </div>
    <?php else : ?>
      <div class="knk-url-banner-label">Form Submit URL — pakai sebagai action="" form HTML</div>
      <div class="knk-url-banner-row">
        <input class="knk-url-field" readonly value="<?php echo esc_url($submit_url); ?>" onclick="this.select()">
        <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent='Disalin'"><i class="fa-regular fa-copy"></i> Salin</button>
      </div>
      <button type="button" class="knk-embed-toggle" onclick="this.nextElementSibling.classList.toggle('open');this.innerHTML=this.nextElementSibling.classList.contains('open')?'<i class=\'fa-solid fa-chevron-up\'></i> Sembunyikan kode':'<i class=\'fa-solid fa-chevron-down\'></i> Lihat embed code (termasuk pixel)'"><i class="fa-solid fa-chevron-down"></i> Lihat embed code (termasuk pixel)</button>
      <div class="knk-embed-wrap">
        <div style="display:flex;justify-content:flex-end;gap:6px;margin-bottom:6px">
          <span style="font-size:11px;color:var(--g400);align-self:center"><i class="fa-solid fa-circle-info"></i> Pixel ikut tersimpan dalam kode ini setelah disimpan</span>
          <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm" onclick="navigator.clipboard.writeText(this.closest('.knk-embed-wrap').querySelector('textarea').value);this.innerHTML='<i class=\'fa-solid fa-check\'></i> Disalin'"><i class="fa-regular fa-copy"></i> Salin</button>
        </div>
        <textarea class="knk-embed-box" readonly onclick="this.select()"><?php echo esc_html($embed_code); ?></textarea>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<form id="konektor-campaign-form" data-id="<?php echo $campaign ? $campaign->id : 0; ?>">

<div class="knk-cols">

<!-- ══════════ MAIN COLUMN ══════════ -->
<div class="knk-cols-main">

  <!-- Info Kampanye -->
  <div class="knk-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-box"></i> Info Kampanye</h2></div>
    <div class="knk-card-body">
      <div class="knk-g2">
        <div class="knk-field">
          <label class="knk-label">Nama Kampanye <span class="knk-req">*</span></label>
          <input type="text" name="name" class="knk-input" id="camp-name" required
            value="<?php echo esc_attr($campaign->name ?? ''); ?>" placeholder="cth: Promo Ramadan 2025">
        </div>
        <div class="knk-field">
          <label class="knk-label">Tipe</label>
          <select name="type" id="camp-type" class="knk-select">
            <option value="form"    <?php selected($preset_type, 'form'); ?>><i class="fa-solid fa-clipboard-list"></i> Form Order</option>
            <option value="wa_link" <?php selected($preset_type, 'wa_link'); ?>>Link</option>
          </select>
          <?php if ($is_new) : ?>
          <div class="knk-hint"><a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new'); ?>" style="color:var(--g500)"><i class="fa-solid fa-arrow-left"></i> Ganti jenis kampanye</a></div>
          <?php endif; ?>
        </div>
        <div class="knk-field">
          <label class="knk-label">Nama Toko / Brand</label>
          <input type="text" name="store_name" class="knk-input" id="prev-store"
            value="<?php echo esc_attr($campaign->store_name ?? ''); ?>" placeholder="cth: Toko Herbal">
        </div>
        <div class="knk-field">
          <label class="knk-label">Nama Produk</label>
          <input type="text" name="product_name" class="knk-input" id="prev-product"
            value="<?php echo esc_attr($campaign->product_name ?? ''); ?>" placeholder="cth: Obat Batuk Herbal">
        </div>
      </div>
      <div class="knk-field">
        <label class="knk-label">Slug URL <span class="knk-req">*</span></label>
        <div class="knk-input-group">
          <span class="knk-input-prefix"><?php echo esc_html(home_url("/{$base_slug}/")); ?></span>
          <input type="text" name="slug" id="camp-slug" class="knk-input"
            value="<?php echo esc_attr($campaign->slug ?? ''); ?>" placeholder="promo-ramadan">
        </div>
        <p class="knk-hint">Otomatis dari nama jika kosong. Huruf kecil, angka, tanda hubung saja.</p>
      </div>
    </div>
  </div>

  <!-- Form Builder (hanya form type) -->
  <div class="knk-card" id="form-builder-card">
    <div class="knk-card-head">
      <h2><i class="fa-solid fa-screwdriver-wrench"></i> Form Builder</h2>
      <div class="knk-card-head-tools">
        <span style="font-size:12px;color:var(--g500)">Template:</span>
        <select name="form_config[template]" id="form-tpl-select" class="knk-select knk-select-sm">
          <?php foreach (['modern'=>'Modern','classic'=>'Classic','minimal'=>'Minimal','card'=>'Card','gradient'=>'Gradient'] as $v => $l) : ?>
          <option value="<?php echo $v; ?>" <?php selected($form_cfg['template'] ?? 'modern', $v); ?>><?php echo $l; ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="form_config[submit_label]" class="knk-input knk-input-sm" style="width:160px"
          value="<?php echo esc_attr($form_cfg['submit_label'] ?? 'Kirim Sekarang'); ?>" placeholder="Teks tombol">
      </div>
    </div>
    <div class="knk-card-body">

      <!-- Template Thumbnails -->
      <div class="knk-tpl-bar">
        <?php foreach (['modern','classic','minimal','card','gradient'] as $v) : ?>
        <div class="knk-tpl-thumb <?php echo ($form_cfg['template'] ?? 'modern') === $v ? 'active' : ''; ?>" data-tpl="<?php echo $v; ?>">
          <?php echo knk_tpl_thumb($v); ?>
          <span class="knk-tpl-label"><?php echo ucfirst($v); ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Field List -->
      <div class="knk-section-head" style="margin-top:20px">
        <h3>Field Form</h3>
        <span style="font-size:11px;color:var(--g500)">Centang = tampil di form</span>
      </div>
      <div id="knk-field-list">
        <?php foreach ($form_cfg['fields'] as $i => $f) :
          $on  = !empty($f['enabled']);
          $req = !empty($f['required']);
        ?>
        <div class="knk-field-row <?php echo $on ? 'is-on' : ''; ?>">
          <span class="knk-field-row-drag"><i class="fa-solid fa-grip-vertical" style="color:var(--g300)"></i></span>
          <label class="knk-sw">
            <input type="checkbox" name="form_config[fields][<?php echo $i; ?>][enabled]" value="1" <?php checked($on); ?> class="knk-field-enable-cb">
            <span class="knk-sw-track"></span>
          </label>
          <input type="hidden" name="form_config[fields][<?php echo $i; ?>][name]" value="<?php echo esc_attr($f['name']); ?>">
          <input type="hidden" name="form_config[fields][<?php echo $i; ?>][type]" value="<?php echo esc_attr($f['type']); ?>">
          <div class="knk-field-row-info">
            <input type="text" name="form_config[fields][<?php echo $i; ?>][label]"
              class="knk-input knk-input-sm knk-field-label-input"
              value="<?php echo esc_attr($f['label']); ?>"
              placeholder="Label">
            <input type="text" name="form_config[fields][<?php echo $i; ?>][placeholder]"
              class="knk-input knk-input-sm knk-field-label-input"
              value="<?php echo esc_attr($f['placeholder'] ?? $f['label']); ?>"
              placeholder="Placeholder">
            <span class="knk-field-row-type"><?php echo esc_html($f['type']); ?></span>
          </div>
          <label class="knk-field-row-req">
            <input type="checkbox" name="form_config[fields][<?php echo $i; ?>][required]" value="1" <?php checked($req); ?>>
            Wajib
          </label>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Extra Fields -->
      <div class="knk-section-head" style="margin-top:20px">
        <h3>Field Tambahan</h3>
        <button type="button" id="knk-add-field" class="knk-btn knk-btn-outline knk-btn-sm"><i class="fa-solid fa-plus"></i> Tambah Field</button>
      </div>
      <div id="knk-extra-fields">
        <?php foreach ($form_cfg['extra_fields'] ?? [] as $ei => $ef) : ?>
        <div class="knk-extra-row">
          <input type="text" name="form_config[extra_fields][<?php echo $ei; ?>][label]"
            class="knk-input knk-input-sm" style="flex:1;min-width:100px" placeholder="Label"
            value="<?php echo esc_attr($ef['label'] ?? ''); ?>">
          <select name="form_config[extra_fields][<?php echo $ei; ?>][type]" class="knk-select knk-select-sm">
            <?php foreach (['text','tel','email','number','textarea','select','radio','checkbox'] as $ft) : ?>
            <option value="<?php echo $ft; ?>" <?php selected($ef['type']??'text', $ft); ?>><?php echo ucfirst($ft); ?></option>
            <?php endforeach; ?>
          </select>
          <label style="font-size:12px;display:flex;align-items:center;gap:3px;white-space:nowrap">
            <input type="checkbox" name="form_config[extra_fields][<?php echo $ei; ?>][required]" value="1" <?php checked(!empty($ef['required'])); ?>> Wajib
          </label>
          <label style="font-size:12px;display:flex;align-items:center;gap:3px;white-space:nowrap">
            <input type="checkbox" name="form_config[extra_fields][<?php echo $ei; ?>][enabled]" value="1" <?php checked(!empty($ef['enabled'])); ?>> Tampil
          </label>
          <input type="text" name="form_config[extra_fields][<?php echo $ei; ?>][options]"
            class="knk-input knk-input-sm" style="flex:1;min-width:100px" placeholder="Opsi (pisah koma)"
            value="<?php echo esc_attr(implode(',',$ef['options']??[])); ?>">
          <button type="button" class="knk-btn knk-btn-danger knk-btn-icon knk-rm-extra" title="Hapus"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

  <!-- Kustomisasi Tampilan -->
  <div class="knk-card" id="custom-style-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-palette"></i> Kustomisasi Tampilan</h2></div>
    <div class="knk-card-body">
      <div class="knk-g2" style="gap:10px">
        <?php
        $cs_defs = [
          ['color_bg',       'Warna Background',   'color', '#ffffff'],
          ['color_accent',   'Warna Tombol',       'color', '#2563eb'],
          ['color_text',     'Warna Teks',         'color', '#0f172a'],
          ['color_border',   'Warna Border Input', 'color', '#e2e8f0'],
          ['color_btn_text', 'Warna Teks Tombol',  'color', '#ffffff'],
          ['max_width',      'Lebar Maksimum',     'text',  '480px'],
          ['border_radius',  'Border Radius',      'text',  '12px'],
          ['padding',        'Padding',            'text',  '28px 24px'],
          ['font_size',      'Ukuran Font Input',  'text',  '14px'],
          ['btn_font_size',  'Ukuran Font Tombol', 'text',  '15px'],
        ];
        foreach ($cs_defs as [$key, $label, $type, $default]) :
          $val = $cstyle[$key] ?? '';
        ?>
        <div class="knk-field">
          <label class="knk-label"><?php echo $label; ?></label>
          <div style="display:flex;align-items:center;gap:6px">
            <?php if ($type === 'color') : ?>
            <input type="color" class="knk-color-sync" data-target="knk-cs-<?php echo $key; ?>"
              value="<?php echo esc_attr($val ?: $default); ?>"
              style="width:36px;height:32px;padding:2px;border:1.5px solid var(--g300);border-radius:6px;cursor:pointer;background:none;flex-shrink:0">
            <?php endif; ?>
            <input type="text" name="form_config[custom_style][<?php echo $key; ?>]"
              id="knk-cs-<?php echo $key; ?>"
              class="knk-input knk-input-sm knk-mono"
              value="<?php echo esc_attr($val); ?>"
              placeholder="<?php echo esc_attr($default); ?>">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="knk-hint" style="margin-top:10px">Kosongkan untuk menggunakan nilai default template. Warna bisa diisi kode HEX (<code>#fff</code>), RGB (<code>rgb(0,0,0)</code>), atau nama warna CSS.</p>
    </div>
  </div>

  <!-- Thanks Page -->
  <div class="knk-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-circle-check"></i> Thanks Page &amp; Redirect</h2></div>
    <div class="knk-card-body">

      <!-- Follow-Up Message (form only) -->
      <?php if ($preset_type !== 'wa_link') : ?>
      <div class="knk-card" style="background:var(--g50,#f8fafc);border:1.5px solid #e2e8f0;box-shadow:none;margin-bottom:8px">
        <div class="knk-card-head" style="background:transparent;border-bottom:1px solid #e2e8f0">
          <h2 style="font-size:13px"><i class="fa-solid fa-paper-plane" style="color:var(--p)"></i> Pesan Follow-Up ke Customer</h2>
        </div>
        <div class="knk-card-body" style="padding:14px 16px">
          <div class="knk-field">
            <label class="knk-label">Pesan Follow-Up (WA)</label>
            <textarea name="followup_message" class="knk-input" rows="3"
              placeholder="Halo [cname], kami ingin menindaklanjuti pesanan [product] Anda..."><?php echo esc_textarea($campaign->followup_message ?? ''); ?></textarea>
            <div class="knk-chips" style="margin-top:6px">
              <?php foreach (['[cname]','[cphone]','[cemail]','[product]','[quantity]','[oname]'] as $sc) : ?>
              <span class="knk-chip" title="Klik salin" onclick="navigator.clipboard.writeText('<?php echo $sc; ?>')"><?php echo $sc; ?></span>
              <?php endforeach; ?>
            </div>
            <p class="knk-hint">Dikirim otomatis sebagai link WA ke nomor customer saat CS klik Follow-Up di panel atau via Telegram.</p>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="knk-field">
        <label class="knk-label">Pesan Terima Kasih</label>
        <textarea name="thanks_page_config[description]" class="knk-input" rows="2"
          placeholder="cth: Terima kasih! Pesanan Anda sedang kami proses."><?php echo esc_textarea($thanks_cfg['description'] ?? ''); ?></textarea>
      </div>
      <div class="knk-g2">
        <div class="knk-field">
          <label class="knk-label">Aksi Setelah Submit</label>
          <select name="thanks_page_config[redirect_type]" id="redirect-type" class="knk-select">
            <option value="none" <?php selected($rtype, 'none'); ?>>Tidak Redirect</option>
            <option value="cs"   <?php selected($rtype, 'cs');   ?>>Redirect ke CS (rotator)</option>
            <option value="url"  <?php selected($rtype, 'url');  ?>>Redirect ke URL Tertentu</option>
          </select>
        </div>
        <div class="knk-field">
          <label class="knk-label">Delay Redirect (detik)</label>
          <input type="number" name="thanks_page_config[delay_redirect]" class="knk-input"
            value="<?php echo (int)($thanks_cfg['delay_redirect'] ?? 3); ?>" min="0" max="30">
        </div>
      </div>

      <div id="redirect-url-row" class="knk-field <?php echo $rtype !== 'url' ? 'knk-hidden' : ''; ?>">
        <label class="knk-label">URL Tujuan</label>
        <input type="url" name="thanks_page_config[redirect_url]" class="knk-input"
          value="<?php echo esc_attr($thanks_cfg['redirect_url'] ?? ''); ?>" placeholder="https://...">
      </div>


      <div class="knk-field">
        <label class="knk-label">Pesan ke CS</label>
        <textarea name="thanks_page_config[custom_message]" class="knk-input" rows="3"
          placeholder="<?php echo $preset_type === 'wa_link' ? 'Halo [oname], ada yang ingin bertanya tentang [product]...' : 'Halo [oname], saya [cname] ingin pesan [product]...'; ?>"><?php echo esc_textarea($thanks_cfg['custom_message'] ?? ''); ?></textarea>
        <div class="knk-chips">
          <?php
          $chips = $preset_type === 'wa_link'
            ? ['[product]','[oname]']
            : ['[cname]','[cemail]','[cphone]','[caddress]','[catatan]','[product]','[quantity]','[oname]'];
          foreach ($chips as $sc) : ?>
          <span class="knk-chip" title="Klik salin" onclick="navigator.clipboard.writeText('<?php echo $sc; ?>')"><?php echo $sc; ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($preset_type === 'wa_link') : ?>
        <p class="knk-hint">Kampanye Link tidak memiliki data customer — hanya shortcode <code>[product]</code> dan <code>[oname]</code> yang tersedia.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Pixel / Tracking -->
  <?php
  $meta_events = ['PageView','ViewContent','Search','AddToCart','AddToWishlist','InitiateCheckout','AddPaymentInfo','Lead','CompleteRegistration','Contact','CustomizeProduct','Donate','FindLocation','Schedule','StartTrial','SubmitApplication','Subscribe','Purchase'];
  $tiktok_events = ['PageView','ViewContent','ClickButton','Search','AddToWishlist','AddToCart','InitiateCheckout','AddPaymentInfo','PlaceAnOrder','CompletePayment','CompleteRegistration','Contact','Download','RequestPhone','SubmitForm','Subscribe'];
  function knk_evt_select($name, $val, $opts, $extra='') {
    $s = '<select name="'.$name.'" class="knk-select" '.$extra.'>';
    $s .= '<option value="">— Tidak Digunakan —</option>';
    foreach ($opts as $o) {
      $s .= '<option value="'.esc_attr($o).'"'.(($val??'')===$o?' selected':'').'>'.esc_html($o).'</option>';
    }
    $s .= '</select>';
    return $s;
  }
  $is_wa = $preset_type === 'wa_link';
  ?>
  <div class="knk-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-chart-bar"></i> Integrasi Tracking</h2></div>
    <div class="knk-card-body">
      <div class="knk-ptabs">
        <button type="button" class="knk-ptab active" data-ptab="meta">Meta / Facebook</button>
        <button type="button" class="knk-ptab" data-ptab="google">Google / GTM</button>
        <button type="button" class="knk-ptab" data-ptab="tiktok">TikTok</button>
        <button type="button" class="knk-ptab" data-ptab="snack">Snack Video</button>
      </div>

      <!-- Meta -->
      <div class="knk-pcontent active" data-ptab="meta">
        <div class="knk-g2">
          <div class="knk-field"><label class="knk-label">Pixel ID</label><input type="text" name="pixel_config[meta][pixel_id]" class="knk-input" value="<?php echo esc_attr($meta_cfg['pixel_id']??''); ?>" placeholder="123456789"></div>
          <div class="knk-field"><label class="knk-label">Token CAPI</label><input type="text" name="pixel_config[meta][token]" class="knk-input" value="<?php echo esc_attr($meta_cfg['token']??''); ?>" placeholder="EAA..."></div>
          <div class="knk-field"><label class="knk-label">Test Event Code</label><input type="text" name="pixel_config[meta][test_event_code]" class="knk-input" value="<?php echo esc_attr($meta_cfg['test_event_code']??''); ?>" placeholder="TEST12345"></div>
          <div class="knk-field"><label class="knk-label">Currency</label><input type="text" name="pixel_config[meta][currency]" class="knk-input" value="<?php echo esc_attr($meta_cfg['currency']??'IDR'); ?>"></div>
          <div class="knk-field"><label class="knk-label">Value (Purchase)</label><input type="number" name="pixel_config[meta][value]" class="knk-input" value="<?php echo esc_attr($meta_cfg['value']??'0'); ?>"></div>
        </div>
        <div class="knk-g<?php echo $is_wa ? '2' : '3'; ?>">
          <div class="knk-field">
            <label class="knk-label">Event Page Load</label>
            <?php echo knk_evt_select('pixel_config[meta][page_load_event]', $meta_cfg['page_load_event']??'PageView', $meta_events); ?>
          </div>
          <div class="knk-field">
            <label class="knk-label"><?php echo $is_wa ? 'Event WA Click' : 'Event Form Submit'; ?></label>
            <?php echo knk_evt_select('pixel_config[meta][form_submit_event]', $meta_cfg['form_submit_event']??'Lead', $meta_events); ?>
          </div>
          <?php if (!$is_wa) : ?>
          <div class="knk-field">
            <label class="knk-label">Event Thanks Page</label>
            <?php echo knk_evt_select('pixel_config[meta][thanks_page_event]', $meta_cfg['thanks_page_event']??'Purchase', $meta_events); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Google -->
      <div class="knk-pcontent" data-ptab="google">
        <div class="knk-g2">
          <div class="knk-field"><label class="knk-label">Conversion ID (AW-...)</label><input type="text" name="pixel_config[google][conversion_id]" class="knk-input" value="<?php echo esc_attr($google_cfg['conversion_id']??''); ?>" placeholder="AW-123456789"></div>
          <div class="knk-field"><label class="knk-label">GTM ID</label><input type="text" name="pixel_config[google][gtm_id]" class="knk-input" value="<?php echo esc_attr($google_cfg['gtm_id']??''); ?>" placeholder="GTM-XXXXXX"></div>
          <div class="knk-field">
            <label class="knk-label">Label Event Page Load</label>
            <input type="text" name="pixel_config[google][page_load_label]" class="knk-input" value="<?php echo esc_attr($google_cfg['page_load_label']??''); ?>" placeholder="cth: page_view">
          </div>
          <div class="knk-field">
            <label class="knk-label"><?php echo $is_wa ? 'Label WA Click' : 'Label Form Submit'; ?></label>
            <input type="text" name="pixel_config[google][form_submit_label]" class="knk-input" value="<?php echo esc_attr($google_cfg['form_submit_label']??''); ?>" placeholder="cth: generate_lead">
          </div>
          <?php if (!$is_wa) : ?>
          <div class="knk-field">
            <label class="knk-label">Label Thanks Page</label>
            <input type="text" name="pixel_config[google][thanks_page_label]" class="knk-input" value="<?php echo esc_attr($google_cfg['thanks_page_label']??''); ?>" placeholder="cth: purchase">
          </div>
          <?php endif; ?>
        </div>
        <p class="knk-hint" style="margin-top:8px">Label konversi diambil dari Google Ads / GTM — isi sesuai yang sudah dibuat di akun Anda.</p>
      </div>

      <!-- TikTok -->
      <div class="knk-pcontent" data-ptab="tiktok">
        <p class="knk-hint" style="margin-bottom:12px">Menggunakan <strong>TikTok Events API</strong> (server-to-server). Pixel ID + Access Token dari TikTok Ads Manager → Assets → Web Events → Settings.</p>
        <div class="knk-g2">
          <div class="knk-field">
            <label class="knk-label">TikTok Pixel ID</label>
            <input type="text" name="pixel_config[tiktok][pixel_id]" class="knk-input" value="<?php echo esc_attr($tiktok_cfg['pixel_id']??''); ?>" placeholder="CXXXXXXXXXX">
          </div>
          <div class="knk-field">
            <label class="knk-label">Access Token <span class="knk-hint">(Generate di Settings pixel)</span></label>
            <input type="text" name="pixel_config[tiktok][access_token]" class="knk-input" value="<?php echo esc_attr($tiktok_cfg['access_token']??''); ?>" placeholder="xxxxx...">
          </div>
          <div class="knk-field">
            <label class="knk-label">Currency</label>
            <input type="text" name="pixel_config[tiktok][currency]" class="knk-input" value="<?php echo esc_attr($tiktok_cfg['currency']??'IDR'); ?>" placeholder="IDR">
          </div>
          <div class="knk-field">
            <label class="knk-label">Value (opsional)</label>
            <input type="number" step="any" name="pixel_config[tiktok][value]" class="knk-input" value="<?php echo esc_attr($tiktok_cfg['value']??''); ?>" placeholder="0">
          </div>
          <div class="knk-field">
            <label class="knk-label">Test Event Code <span class="knk-hint">(opsional, dari Events Manager)</span></label>
            <input type="text" name="pixel_config[tiktok][test_event_code]" class="knk-input" value="<?php echo esc_attr($tiktok_cfg['test_event_code']??''); ?>" placeholder="TEST12345">
          </div>
        </div>
        <div class="knk-g<?php echo $is_wa ? '2' : '3'; ?>">
          <div class="knk-field">
            <label class="knk-label">Event Page Load</label>
            <?php echo knk_evt_select('pixel_config[tiktok][page_load_event]', $tiktok_cfg['page_load_event']??'PageView', $tiktok_events); ?>
          </div>
          <div class="knk-field">
            <label class="knk-label"><?php echo $is_wa ? 'Event WA Click' : 'Event Form Submit'; ?></label>
            <?php echo knk_evt_select('pixel_config[tiktok][form_submit_event]', $tiktok_cfg['form_submit_event']??($is_wa?'ClickButton':'SubmitForm'), $tiktok_events); ?>
          </div>
          <?php if (!$is_wa) : ?>
          <div class="knk-field">
            <label class="knk-label">Event Thanks Page</label>
            <?php echo knk_evt_select('pixel_config[tiktok][thanks_page_event]', $tiktok_cfg['thanks_page_event']??'CompletePayment', $tiktok_events); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Snack Video / Kwai -->
      <div class="knk-pcontent" data-ptab="snack">
        <p class="knk-hint" style="margin-bottom:12px">Menggunakan <strong>Kwai Event API</strong> (server-to-server). Buat di Kwai for Business → Web Events → Event API, lalu salin Pixel ID dan Access Token.</p>
        <div class="knk-g2">
          <div class="knk-field">
            <label class="knk-label">Pixel ID</label>
            <input type="text" name="pixel_config[snack][pixel_id]" class="knk-input" value="<?php echo esc_attr($snack_cfg['pixel_id']??''); ?>" placeholder="Zp6x5j5sUuaSdnCgsJl7hA">
          </div>
          <div class="knk-field">
            <label class="knk-label">Access Token</label>
            <input type="text" name="pixel_config[snack][access_token]" class="knk-input" value="<?php echo esc_attr($snack_cfg['access_token']??''); ?>" placeholder="YUr9HVpJhC2iDtwF/Pws2A==">
          </div>
          <div class="knk-field">
            <label class="knk-label">Currency</label>
            <input type="text" name="pixel_config[snack][currency]" class="knk-input" value="<?php echo esc_attr($snack_cfg['currency']??'IDR'); ?>" placeholder="IDR">
          </div>
          <div class="knk-field">
            <label class="knk-label">Value (opsional)</label>
            <input type="number" step="any" name="pixel_config[snack][value]" class="knk-input" value="<?php echo esc_attr($snack_cfg['value']??''); ?>" placeholder="0">
          </div>
        </div>
        <?php
        $snack_events = [
          'EVENT_CONTENT_VIEW'          => 'EVENT_CONTENT_VIEW — Page viewed',
          'EVENT_FORM_SUBMIT'           => 'EVENT_FORM_SUBMIT — Form submitted',
          'EVENT_COMPLETE_REGISTRATION' => 'EVENT_COMPLETE_REGISTRATION — Registration completed',
          'EVENT_PURCHASE'              => 'EVENT_PURCHASE — Payment completed',
          'EVENT_COMPLETE_TRANSACTION'  => 'EVENT_COMPLETE_TRANSACTION — Transaction completed',
          'EVENT_PLACE_ORDER'           => 'EVENT_PLACE_ORDER — Order placed',
          'EVENT_INITIATED_CHECKOUT'    => 'EVENT_INITIATED_CHECKOUT — Checkout started',
          'EVENT_ADD_TO_CART'           => 'EVENT_ADD_TO_CART — Added to cart',
          'EVENT_ADD_PAYMENT_INFO'      => 'EVENT_ADD_PAYMENT_INFO — Payment info added',
          'EVENT_BUTTON_CLICK'          => 'EVENT_BUTTON_CLICK — Button clicked',
          'EVENT_CONTACT'               => 'EVENT_CONTACT — Contact/consultation',
          'EVENT_SUBSCRIBE'             => 'EVENT_SUBSCRIBE — Subscribed',
          'EVENT_SEARCH'                => 'EVENT_SEARCH — Search made',
          'EVENT_DOWNLOAD'              => 'EVENT_DOWNLOAD — Download clicked',
          'EVENT_ADD_TO_WISHLIST'       => 'EVENT_ADD_TO_WISHLIST — Added to wishlist',
          'EVENT_FIRST_DEPOSIT'         => 'EVENT_FIRST_DEPOSIT — First deposit',
          'EVENT_CREDIT_APPROVAL'       => 'EVENT_CREDIT_APPROVAL — Credit approved',
          'EVENT_LOAN_APPLICATION'      => 'EVENT_LOAN_APPLICATION — Loan application',
          'EVENT_LOAN_CREDIT'           => 'EVENT_LOAN_CREDIT — Loan approval',
          'EVENT_LOAN_DISBURSAL'        => 'EVENT_LOAN_DISBURSAL — Loan disbursement',
          'EVENT_CREDIT_CARD_APPLICATION' => 'EVENT_CREDIT_CARD_APPLICATION — Credit card application',
          'EVENT_PURCHASE_1_DAY'        => 'EVENT_PURCHASE_1_DAY — Purchase 1 day',
          'EVENT_PURCHASE_2_DAY'        => 'EVENT_PURCHASE_2_DAY — Purchase 2 day',
          'EVENT_PURCHASE_3_DAY'        => 'EVENT_PURCHASE_3_DAY — Purchase 3 day',
          'EVENT_PURCHASE_7_DAY'        => 'EVENT_PURCHASE_7_DAY — Purchase 7 day',
          'EVENT_KEY_INAPP_EVENT'       => 'EVENT_KEY_INAPP_EVENT — Key in-app event',
          'EVENT_KEY_INAPP_EVENT_1'     => 'EVENT_KEY_INAPP_EVENT_1',
          'EVENT_KEY_INAPP_EVENT_2'     => 'EVENT_KEY_INAPP_EVENT_2',
          'EVENT_KEY_INAPP_EVENT_3'     => 'EVENT_KEY_INAPP_EVENT_3',
          'EVENT_VALUE_PRODUCE'         => 'EVENT_VALUE_PRODUCE — Value produce',
          'EVENT_AD_VIEW'               => 'EVENT_AD_VIEW — Ad view (In-Web)',
          'EVENT_AD_CLICK'              => 'EVENT_AD_CLICK — Ad click (In-Web)',
        ];
        function knk_snack_select($name, $val, $opts) {
          $s = '<select name="'.esc_attr($name).'" class="knk-select">';
          foreach ($opts as $k => $label) {
            $s .= '<option value="'.esc_attr($k).'"'.($val===$k?' selected':'').'>'.esc_html($label).'</option>';
          }
          return $s.'</select>';
        }
        ?>
        <div class="knk-g<?php echo $is_wa ? '2' : '3'; ?>">
          <div class="knk-field">
            <label class="knk-label">Event Page Load</label>
            <?php echo knk_snack_select('pixel_config[snack][page_load_event]', $snack_cfg['page_load_event']??'EVENT_CONTENT_VIEW', $snack_events); ?>
          </div>
          <div class="knk-field">
            <label class="knk-label"><?php echo $is_wa ? 'Event WA Click' : 'Event Form Submit'; ?></label>
            <?php echo knk_snack_select('pixel_config[snack][form_submit_event]', $snack_cfg['form_submit_event']??'EVENT_FORM_SUBMIT', $snack_events); ?>
          </div>
          <?php if (!$is_wa) : ?>
          <div class="knk-field">
            <label class="knk-label">Event Thanks Page</label>
            <?php echo knk_snack_select('pixel_config[snack][thanks_page_event]', $snack_cfg['thanks_page_event']??'EVENT_COMPLETE_REGISTRATION', $snack_events); ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Test Mode -->
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;margin-top:8px">
          <p style="font-size:13px;font-weight:700;margin-bottom:8px;color:#92400e">🧪 Test Mode</p>
          <p class="knk-hint" style="margin-bottom:10px">
            Cara test: buka tab <strong>Test Events</strong> di Kwai for Business, salin kode test (click_id), tempel di bawah.
            Kunjungi landing page dengan <code>?click_id=KODE_TEST</code>, lakukan konversi, cek hasilnya di dashboard.
          </p>
          <div class="knk-sw-row" style="margin-bottom:10px">
            <div class="knk-sw-row-label">
              <strong>Aktifkan Test Mode</strong>
              <span class="knk-hint">trackFlag=true, testFlag=true</span>
            </div>
            <label class="knk-switch">
              <input type="checkbox" name="pixel_config[snack][test_mode]" value="1" id="snack-test-toggle" <?php checked(!empty($snack_cfg['test_mode'])); ?>>
              <span class="knk-switch-slider"></span>
            </label>
          </div>
          <div id="snack-test-clickid-row" style="<?php echo empty($snack_cfg['test_mode']) ? 'display:none' : ''; ?>">
            <label class="knk-label">Test Click ID <span style="font-weight:400;opacity:.7">(dari tab Test Events di Kwai for Business)</span></label>
            <input type="text" name="pixel_config[snack][test_click_id]" class="knk-input" value="<?php echo esc_attr($snack_cfg['test_click_id']??''); ?>" placeholder="rY3vrGB7ICRbG4poTCqDtA">
          </div>
        </div>
        <script>
        (function(){
          var tog=document.getElementById('snack-test-toggle');
          var row=document.getElementById('snack-test-clickid-row');
          if(tog&&row)tog.addEventListener('change',function(){row.style.display=this.checked?'':'none';});
        })();
        </script>
      </div>

    </div>
  </div>

</div><!-- /main -->

<!-- ══════════ SIDEBAR ══════════ -->
<div class="knk-cols-side">

  <!-- Settings card (sticky) -->
  <div class="knk-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-gear"></i> Pengaturan</h2></div>
    <div class="knk-card-body">
      <div class="knk-field">
        <label class="knk-label">Status Kampanye</label>
        <select name="status" class="knk-select">
          <option value="active"   <?php selected($campaign->status ?? 'active', 'active'); ?>>Aktif</option>
          <option value="inactive" <?php selected($campaign->status ?? 'active', 'inactive'); ?>>Nonaktif</option>
        </select>
      </div>
      <hr class="knk-divider">
      <div class="knk-sw-row">
        <div class="knk-sw-row-label">
          <strong>Deteksi Double Lead</strong>
          <span class="knk-hint">Lead sama tidak masuk 2x</span>
        </div>
        <label class="knk-sw">
          <input type="checkbox" name="double_lead_enabled" value="1" id="double-lead-toggle" <?php checked($campaign->double_lead_enabled ?? 1); ?>>
          <span class="knk-sw-track"></span>
        </label>
      </div>
      <div id="double-lead-msg" class="knk-field <?php echo empty($campaign->double_lead_enabled ?? 1) ? 'knk-hidden' : ''; ?>">
        <textarea name="double_lead_message" class="knk-input" rows="2"
          placeholder="Pesan untuk double lead..."><?php echo esc_textarea($campaign->double_lead_message ?? ''); ?></textarea>
      </div>
      <div class="knk-sw-row">
        <div class="knk-sw-row-label">
          <strong>Blokir Customer</strong>
          <span class="knk-hint">By IP, cookie &amp; fingerprint</span>
        </div>
        <label class="knk-sw">
          <input type="checkbox" name="block_enabled" value="1" id="block-toggle" <?php checked($campaign->block_enabled ?? 1); ?>>
          <span class="knk-sw-track"></span>
        </label>
      </div>
      <div id="block-msg" class="knk-field <?php echo empty($campaign->block_enabled ?? 1) ? 'knk-hidden' : ''; ?>">
        <textarea name="block_message" class="knk-input" rows="2"
          placeholder="Pesan untuk user yang diblokir..."><?php echo esc_textarea($campaign->block_message ?? ''); ?></textarea>
      </div>
      <hr class="knk-divider">
      <div class="knk-field">
        <label class="knk-label">Allowed Domains</label>
        <textarea name="allowed_domains_raw" class="knk-input" rows="3"
          placeholder="Satu domain per baris&#10;kosong = semua domain boleh"><?php
          echo esc_textarea(implode("\n", json_decode($campaign->allowed_domains ?? '[]', true)));
        ?></textarea>
        <p class="knk-hint">Kosong = semua domain diizinkan.</p>
      </div>
    </div>
  </div>

  <!-- Operator CS — search/add one by one -->
  <div class="knk-card">
    <div class="knk-card-head">
      <h2><i class="fa-solid fa-user-group"></i> Operator CS</h2>
      <span style="font-size:11px;color:var(--g500)">Bobot 1–10</span>
    </div>
    <?php if (empty($operators)) : ?>
    <div class="knk-card-body">
      <div class="knk-empty">
        <p>Belum ada operator.</p>
        <a href="<?php echo admin_url('admin.php?page=konektor-operators&action=new'); ?>" class="knk-btn knk-btn-outline knk-btn-sm"><i class="fa-solid fa-plus"></i> Tambah Operator</a>
      </div>
    </div>
    <?php else : ?>
    <div class="knk-op-search-wrap">
      <div class="knk-op-search-rel">
        <input type="text" id="knk-op-search-input" class="knk-op-search-input" placeholder="Cari dan tambah CS...">
        <div id="knk-op-dropdown" class="knk-op-dropdown"></div>
      </div>
    </div>
    <div id="knk-op-assigned">
      <?php
      $tc = ['whatsapp'=>'green','telegram'=>'blue','email'=>'gray','line'=>'green'];
      if (empty($assigned)) :
      ?>
      <div class="knk-op-assigned-empty"><i class="fa-solid fa-user-plus" style="font-size:20px;margin-bottom:6px;display:block;opacity:.3"></i>Belum ada CS. Cari dan tambahkan di atas.</div>
      <?php else :
        foreach ($assigned as $op) :
          $color = $tc[$op->type] ?? 'gray';
          $fi    = $op->type === 'whatsapp' ? 'fa-brands fa-whatsapp' : 'fa-solid fa-user';
          $idx   = $op->id . '_' . time();
      ?>
      <div class="knk-op-item is-on">
        <input type="hidden" name="operators[<?php echo $op->id; ?>][id]" value="<?php echo $op->id; ?>">
        <div class="knk-op-meta">
          <i class="<?php echo $fi; ?>" style="color:var(--g400)"></i>
          <span class="knk-op-name"><?php echo esc_html($op->name); ?></span>
          <span class="knk-badge knk-badge-<?php echo $color; ?>"><?php echo ucfirst($op->type); ?></span>
        </div>
        <div class="knk-op-weight-wrap">
          <span class="knk-op-weight-label">Bobot</span>
          <input type="number" name="operators[<?php echo $op->id; ?>][weight]" value="<?php echo $op->weight ?? 1; ?>" min="1" max="10" class="knk-weight-input">
        </div>
        <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm knk-op-remove" title="Hapus"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Preview -->
  <div class="knk-card" id="form-preview-card">
    <div class="knk-card-head"><h2><i class="fa-solid fa-eye"></i> Preview Form</h2></div>
    <div class="knk-preview-label">Template: <span id="prev-tpl-name"><?php echo ucfirst($form_cfg['template'] ?? 'modern'); ?></span></div>
    <div class="knk-preview-wrap" id="knk-form-preview">
      <?php echo knk_form_preview($form_cfg, $campaign); ?>
    </div>
  </div>

</div><!-- /sidebar -->

</div><!-- /knk-cols -->
</form>
</div>
