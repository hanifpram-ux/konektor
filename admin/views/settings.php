<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-gear"></i> Pengaturan</h1></div>
</div>

<!-- Tab Nav -->
<div class="knk-ptabs" style="margin-bottom:0;border-bottom:2px solid var(--g200)">
  <button type="button" class="knk-ptab active" data-stab="general">Umum</button>
  <button type="button" class="knk-ptab" data-stab="blocked">Daftar Diblokir</button>
</div>

<!-- Tab: Umum -->
<div class="knk-stab-content active" id="stab-general">
  <form id="konektor-settings-form">
  <div style="margin-top:20px">

    <!-- URL & Routing -->
    <div class="knk-card">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-link"></i> URL &amp; Routing</span></div>
      <div class="knk-card-body">
        <div class="knk-g2">
          <div class="knk-field">
            <label class="knk-label">Base Slug URL</label>
            <input type="text" name="base_slug" value="<?php echo esc_attr($settings['base_slug'] ?? 'konektor'); ?>" class="knk-input" placeholder="konektor">
            <div class="knk-hint">
              Prefix URL kampanye. Contoh: <code><?php echo esc_html(home_url('/konektor/{slug}')); ?></code><br>
              <span style="color:var(--warn)">Setelah mengubah ini, semua URL kampanye berubah. Update tombol di landing page.</span>
            </div>
          </div>
          <div class="knk-field">
            <label class="knk-label">Slug Halaman CS Panel</label>
            <input type="text" name="cs_panel_slug" value="<?php echo esc_attr($settings['cs_panel_slug'] ?? 'cs-panel'); ?>" class="knk-input" placeholder="cs-panel">
            <div class="knk-hint">URL: <code><?php echo home_url('/'.($settings['cs_panel_slug'] ?? 'cs-panel').'/?token=...'); ?></code></div>
          </div>
        </div>
        <div class="knk-field">
          <label class="knk-label">Allowed Domain Global</label>
          <textarea name="allowed_domains_global" rows="4" class="knk-textarea" placeholder="Satu domain per baris, misal: tokoku.com"><?php
            $doms = json_decode($settings['allowed_domains_global'] ?? '[]', true);
            echo esc_textarea(implode("\n", $doms));
          ?></textarea>
          <div class="knk-hint">Kosong = semua domain diizinkan. Pengaturan per kampanye bisa override ini.</div>
        </div>
      </div>
    </div>

    <!-- Telegram -->
    <div class="knk-card">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-brands fa-telegram"></i> Telegram Bot</span></div>
      <div class="knk-card-body">
        <div class="knk-field">
          <label class="knk-label">Bot Token</label>
          <input type="text" name="telegram_bot_token" value="<?php echo esc_attr($settings['telegram_bot_token'] ?? ''); ?>" class="knk-input" placeholder="1234567890:AABBCCDDEEFFaabbccddeeff">
          <div class="knk-hint">
            Dapatkan dari @BotFather di Telegram.<br>
            Webhook URL: <code><?php echo esc_url(get_rest_url(null, 'konektor/v1/telegram-webhook')); ?></code>
          </div>
        </div>
      </div>
    </div>

    <!-- Keamanan -->
    <div class="knk-card">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-shield-halved"></i> Keamanan Data</span></div>
      <div class="knk-card-body">
        <div class="knk-sw-row" style="padding-top:0">
          <div class="knk-sw-row-label">
            <strong>Enkripsi AES-256</strong>
            <span class="knk-hint">Data nama, email, HP, alamat dienkripsi di database.</span>
          </div>
          <label class="knk-sw">
            <input type="checkbox" name="encrypt_lead_data" value="1" <?php checked($settings['encrypt_lead_data'] ?? '1', '1'); ?>>
            <span class="knk-sw-track"></span>
          </label>
        </div>
      </div>
    </div>

    <!-- Save -->
    <div class="knk-card">
      <div class="knk-card-body" style="display:flex;align-items:center;gap:14px">
        <button type="submit" class="knk-btn knk-btn-primary knk-btn-lg"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
        <span class="knk-save-status" style="font-size:13px"></span>
      </div>
    </div>

  </div>
  </form>
</div>

<!-- Tab: Blocked -->
<div class="knk-stab-content knk-hidden" id="stab-blocked">
  <div style="margin-top:20px">

  <div class="knk-ph" style="padding:0;margin-bottom:16px">
    <div class="knk-ph-left">
      <h2 style="font-size:16px;font-weight:600;margin:0"><i class="fa-solid fa-ban"></i> Daftar Customer Diblokir</h2>
    </div>
  </div>

  <?php if (empty($blocked)) : ?>
  <div class="knk-card"><div class="knk-card-body">
    <div class="knk-empty"><p>Tidak ada customer yang diblokir.</p></div>
  </div></div>
  <?php else : ?>
  <div class="knk-table-wrap">
  <table class="knk-table">
    <thead>
      <tr>
        <th>#</th>
        <th>IP Address</th>
        <th>Cookie ID</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Alasan</th>
        <th>Diblokir Oleh</th>
        <th>Tanggal</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($blocked as $b) : ?>
    <tr>
      <td style="color:var(--g400);font-size:12px"><?php echo $b->id; ?></td>
      <td style="font-family:monospace;font-size:12px"><?php echo $b->ip_address ? esc_html($b->ip_address) : '<span style="color:var(--g400)">—</span>'; ?></td>
      <td style="font-family:monospace;font-size:11px;color:var(--g500)" title="<?php echo esc_attr($b->cookie_id ?? ''); ?>">
        <?php echo $b->cookie_id ? esc_html(substr($b->cookie_id, 0, 12)) . '…' : '<span style="color:var(--g400)">—</span>'; ?>
      </td>
      <td style="font-family:monospace;font-size:12px"><?php echo $b->phone ? esc_html($b->phone) : '<span style="color:var(--g400)">—</span>'; ?></td>
      <td style="font-size:12px;color:var(--g600)"><?php echo $b->email ? esc_html($b->email) : '<span style="color:var(--g400)">—</span>'; ?></td>
      <td style="font-size:12px"><?php echo $b->reason ? esc_html($b->reason) : '<span style="color:var(--g400)">—</span>'; ?></td>
      <td style="font-size:12px"><?php echo $b->operator_name ? esc_html($b->operator_name) : '<span style="color:var(--g400)">Admin</span>'; ?></td>
      <td style="font-size:11px;color:var(--g500)"><?php echo esc_html(substr($b->blocked_at, 0, 16)); ?></td>
      <td>
        <button class="knk-btn knk-btn-ghost knk-btn-sm knk-unblock" data-id="<?php echo $b->id; ?>"><i class="fa-solid fa-unlock"></i> Hapus Blokir</button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>

  </div>
</div>

</div>
