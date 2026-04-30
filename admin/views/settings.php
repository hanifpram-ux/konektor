<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-gear"></i> Pengaturan</h1></div>
</div>

<!-- Tab Nav -->
<div class="knk-ptabs" style="margin-bottom:0;border-bottom:2px solid var(--g200)">
  <button type="button" class="knk-ptab active" data-stab="general">Umum</button>
  <button type="button" class="knk-ptab" data-stab="test-pixel"><i class="fa-solid fa-flask"></i> Test Peristiwa API</button>
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

    <!-- Debug Log -->
    <div class="knk-card">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-bug"></i> Debug Log API</span></div>
      <div class="knk-card-body">
        <div class="knk-sw-row" style="padding-top:0">
          <div class="knk-sw-row-label">
            <strong>Aktifkan Log API</strong>
            <span class="knk-hint">Rekam semua request &amp; response ke Meta CAPI, TikTok Events API, dan Snack/Kwai Event API. Lihat di menu <a href="<?php echo esc_url(admin_url('admin.php?page=konektor-log')); ?>">Log API</a>.<br><span style="color:#dc2626">Nonaktifkan di production — log menyimpan payload API ke database.</span></span>
          </div>
          <label class="knk-sw">
            <input type="checkbox" name="debug_log" value="1" <?php checked($settings['debug_log'] ?? '0', '1'); ?>>
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

<!-- Tab: Test Peristiwa -->
<div class="knk-stab-content knk-hidden" id="stab-test-pixel">
  <div style="margin-top:20px">

  <!-- Debug: cek config kampanye yang tersimpan -->
  <div class="knk-card" style="border-color:var(--warn);border-width:2px">
    <div class="knk-card-head" style="background:#fffbeb">
      <span class="knk-card-title"><i class="fa-solid fa-bug"></i> Debug Config Kampanye (cek pixel_config tersimpan di DB)</span>
    </div>
    <div class="knk-card-body">
      <p style="font-size:13px;color:var(--g600);margin:0 0 12px">Pilih kampanye lalu klik <strong>Cek Config</strong> untuk melihat apakah pixel_config (token, pixel_id, dsb) benar-benar tersimpan di database.</p>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <select id="dbg-campaign-id" class="knk-select" style="max-width:280px">
          <option value="">-- Pilih Kampanye --</option>
          <?php
          $all_camps = Konektor_Campaign::get_all();
          foreach ( $all_camps as $c ) {
              echo '<option value="' . (int)$c->id . '">' . esc_html( $c->name ) . ' (ID: ' . (int)$c->id . ')</option>';
          }
          ?>
        </select>
        <input type="text" id="dbg-test-code" class="knk-input" style="max-width:180px" placeholder="Test Event Code (opsional)">
        <button type="button" id="dbg-check-btn" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-magnifying-glass"></i> Cek + Fire Test</button>
      </div>
      <div class="knk-hint">Isi <strong>Test Event Code</strong> (misal TEST36491) untuk sekaligus fire CAPI langsung dari config kampanye — hasilnya muncul di bawah dan di Meta Test Events.</div>
      <pre id="dbg-result" style="display:none;margin-top:14px;background:var(--g900);color:#93c5fd;padding:14px;border-radius:8px;font-size:12px;overflow:auto;max-height:320px;line-height:1.6"></pre>
    </div>
  </div>

  <div class="knk-card">
    <div class="knk-card-head">
      <span class="knk-card-title"><i class="fa-solid fa-flask"></i> Test Peristiwa API — Meta / TikTok / Snack</span>
    </div>
    <div class="knk-card-body">
      <p style="font-size:13px;color:var(--g600);margin:0 0 16px">Uji kirim event langsung ke server API platform. Isi credential dan klik <strong>Kirim Test</strong>. Hasilnya akan tampil di bawah.</p>

      <!-- Platform & Event Type -->
      <div class="knk-g2" style="margin-bottom:14px">
        <div class="knk-field">
          <label class="knk-label">Platform</label>
          <select id="tp-platform" class="knk-select">
            <option value="meta">Meta (Facebook) CAPI</option>
            <option value="tiktok">TikTok Events API</option>
            <option value="snack">Snack / Kwai Event API</option>
          </select>
        </div>
        <div class="knk-field">
          <label class="knk-label">Jenis Event</label>
          <select id="tp-event-type" class="knk-select">
            <option value="page_load">Page Load (PageView)</option>
            <option value="form_submit">Form Submit (Lead)</option>
            <option value="thanks_page">Thanks Page (Purchase)</option>
          </select>
        </div>
      </div>

      <!-- Meta Fields -->
      <div id="tp-fields-meta">
        <div class="knk-g2" style="margin-bottom:14px">
          <div class="knk-field">
            <label class="knk-label">Pixel ID <span class="knk-req">*</span></label>
            <input type="text" id="tp-meta-pixel-id" class="knk-input" placeholder="1234567890">
          </div>
          <div class="knk-field">
            <label class="knk-label">Access Token <span class="knk-req">*</span></label>
            <input type="text" id="tp-meta-token" class="knk-input" placeholder="EAAxxxx...">
          </div>
        </div>
        <div class="knk-g2" style="margin-bottom:14px">
          <div class="knk-field">
            <label class="knk-label">Test Event Code <span class="knk-hint" style="display:inline">(opsional — dari Meta Test Events)</span></label>
            <input type="text" id="tp-meta-test-code" class="knk-input" placeholder="TEST12345">
            <div class="knk-hint">Isi agar event muncul di tab <em>Test Events</em> di Meta Events Manager tanpa dihitung sebagai event nyata.</div>
          </div>
          <div class="knk-field">
            <label class="knk-label">Nama Event Custom <span class="knk-hint" style="display:inline">(override)</span></label>
            <input type="text" id="tp-meta-event-override" class="knk-input" placeholder="Kosong = pakai default (PageView / Lead / Purchase)">
          </div>
        </div>
      </div>

      <!-- TikTok Fields -->
      <div id="tp-fields-tiktok" style="display:none">
        <div class="knk-g2" style="margin-bottom:14px">
          <div class="knk-field">
            <label class="knk-label">Pixel ID <span class="knk-req">*</span></label>
            <input type="text" id="tp-tiktok-pixel-id" class="knk-input" placeholder="D25CQRBC77U91...">
          </div>
          <div class="knk-field">
            <label class="knk-label">Access Token <span class="knk-req">*</span></label>
            <input type="text" id="tp-tiktok-token" class="knk-input" placeholder="xxxx...">
          </div>
        </div>
        <div class="knk-field" style="margin-bottom:14px">
          <label class="knk-label">Test Event Code <span class="knk-hint" style="display:inline">(opsional)</span></label>
          <input type="text" id="tp-tiktok-test-code" class="knk-input" placeholder="TEST12345">
          <div class="knk-hint">Isi agar event muncul di tab <em>Test Events</em> di TikTok Ads Manager.</div>
        </div>
      </div>

      <!-- Snack Fields -->
      <div id="tp-fields-snack" style="display:none">
        <div class="knk-g2" style="margin-bottom:14px">
          <div class="knk-field">
            <label class="knk-label">Pixel ID <span class="knk-req">*</span></label>
            <input type="text" id="tp-snack-pixel-id" class="knk-input" placeholder="123456">
          </div>
          <div class="knk-field">
            <label class="knk-label">Access Token <span class="knk-req">*</span></label>
            <input type="text" id="tp-snack-token" class="knk-input" placeholder="xxxx...">
          </div>
        </div>
      </div>

      <!-- Tombol -->
      <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
        <button type="button" id="tp-send-btn" class="knk-btn knk-btn-primary">
          <i class="fa-solid fa-paper-plane"></i> Kirim Test Event
        </button>
        <span id="tp-status" style="font-size:13px"></span>
      </div>

      <!-- Hasil -->
      <div id="tp-result" style="display:none;margin-top:18px">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--g500);margin-bottom:6px">Hasil Response</div>
        <div id="tp-result-banner" style="padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:10px"></div>
        <div style="font-size:11px;font-weight:600;color:var(--g500);margin-bottom:4px">HTTP Status &amp; Body:</div>
        <pre id="tp-result-body" style="background:var(--g900);color:#86efac;padding:14px;border-radius:8px;font-size:12px;overflow:auto;max-height:260px;margin:0;line-height:1.6"></pre>
      </div>

    </div>
  </div>

  </div>
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
