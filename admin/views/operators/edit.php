<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left">
    <h1><?php echo $operator ? '<i class="fa-solid fa-pen"></i> Edit Operator' : '<i class="fa-solid fa-plus"></i> Operator Baru'; ?></h1>
    <?php if ($operator) : ?>
      <span style="font-size:12px;color:var(--g500)"><?php echo esc_html($operator->name); ?></span>
    <?php endif; ?>
  </div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin.php?page=konektor-operators'); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
  </div>
</div>

<form id="konektor-operator-form" data-id="<?php echo $operator ? $operator->id : 0; ?>">

<div class="knk-cols">

<!-- Kolom Utama -->
<div class="knk-col-main">

  <!-- Info Dasar -->
  <div class="knk-card">
    <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-user"></i> Info Operator</span></div>
    <div class="knk-card-body">

      <div class="knk-field">
        <label class="knk-label">Nama CS <span class="knk-req">*</span></label>
        <input type="text" name="name" value="<?php echo esc_attr($operator->name ?? ''); ?>" required class="knk-input" placeholder="Misal: Siti Agen">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="knk-field">
          <label class="knk-label">Tipe <span class="knk-req">*</span></label>
          <select name="type" id="op-type" class="knk-select">
            <?php foreach (['whatsapp'=>'WhatsApp','email'=>'Email','telegram'=>'Telegram','line'=>'LINE'] as $t=>$label) : ?>
            <option value="<?php echo $t; ?>" <?php selected($operator->type ?? 'whatsapp', $t); ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="knk-field">
          <label class="knk-label">Status</label>
          <select name="status" class="knk-select">
            <option value="on"  <?php selected($operator->status ?? 'on', 'on'); ?>>Aktif</option>
            <option value="off" <?php selected($operator->status ?? 'on', 'off'); ?>>Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="knk-field">
        <label class="knk-label">Kontak / Value <span class="knk-req">*</span></label>
        <input type="text" name="value" value="<?php echo esc_attr($operator->value ?? ''); ?>" class="knk-input" placeholder="No WA / Email / @username">
        <div class="knk-hint" id="op-type-hint">Format WA: <code>628xxxxxxx</code> | Telegram: <code>@username</code> | LINE: ID Line</div>
      </div>

      <div class="knk-field">
        <label class="knk-label">Telegram Chat ID</label>
        <input type="text" name="telegram_chat_id" value="<?php echo esc_attr($operator->telegram_chat_id ?? ''); ?>" class="knk-input" placeholder="Untuk notifikasi lead via bot">
        <div class="knk-hint">Chat ID bukan username. Dapatkan dari @userinfobot di Telegram.</div>
      </div>

    </div>
  </div>

  <!-- Jam Kerja -->
  <div class="knk-card">
    <div class="knk-card-head">
      <span class="knk-card-title"><i class="fa-regular fa-clock"></i> Jam Kerja</span>
      <label class="knk-sw">
        <input type="checkbox" name="work_hours_enabled" value="1" id="work-hours-toggle" <?php checked($operator->work_hours_enabled ?? 0); ?>>
        <span class="knk-sw-track"></span>
      </label>
    </div>
    <div class="knk-card-body" id="work-hours-section" <?php echo ($operator->work_hours_enabled ?? 0) ? '' : 'style="display:none"'; ?>>
      <div class="knk-hint" style="margin-bottom:12px">Centang hari yang aktif. Di luar jadwal, operator tidak akan menerima lead.</div>
      <?php
      $days   = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
      $day_id = ['monday'=>'Senin','tuesday'=>'Selasa','wednesday'=>'Rabu','thursday'=>'Kamis','friday'=>'Jumat','saturday'=>'Sabtu','sunday'=>'Minggu'];
      $wh     = [];
      if ($operator && $operator->work_hours) {
          foreach (json_decode($operator->work_hours, true) as $s) {
              $wh[$s['day']] = $s;
          }
      }
      ?>
      <div style="border:1px solid var(--g200);border-radius:8px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--g100)">
              <th style="padding:8px 12px;text-align:left;font-weight:600;color:var(--g600)">Hari</th>
              <th style="padding:8px 12px;text-align:center;font-weight:600;color:var(--g600)">Aktif</th>
              <th style="padding:8px 12px;text-align:left;font-weight:600;color:var(--g600)">Mulai</th>
              <th style="padding:8px 12px;text-align:left;font-weight:600;color:var(--g600)">Selesai</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($days as $i => $day) :
            $slot    = $wh[$day] ?? [];
            $enabled = !empty($slot);
          ?>
          <tr style="border-top:1px solid var(--g200)">
            <td style="padding:8px 12px;font-weight:500"><?php echo $day_id[$day]; ?></td>
            <td style="padding:8px 12px;text-align:center">
              <input type="checkbox" name="work_hours[<?php echo $i; ?>][active]" value="1" <?php checked($enabled); ?> class="knk-wh-active">
            </td>
            <td style="padding:8px 12px"><input type="time" name="work_hours[<?php echo $i; ?>][start]" value="<?php echo esc_attr($slot['start'] ?? '08:00'); ?>" class="knk-input" style="width:120px;padding:4px 8px"></td>
            <td style="padding:8px 12px"><input type="time" name="work_hours[<?php echo $i; ?>][end]"   value="<?php echo esc_attr($slot['end']   ?? '17:00'); ?>" class="knk-input" style="width:120px;padding:4px 8px"></td>
            <input type="hidden" name="work_hours[<?php echo $i; ?>][day]" value="<?php echo $day; ?>">
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($operator && !empty($campaigns)) : ?>
  <!-- Kampanye -->
  <div class="knk-card">
    <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-bullhorn"></i> Kampanye Terdaftar</span></div>
    <div class="knk-card-body">
      <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($campaigns as $c) : ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--g50);border-radius:6px">
          <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=edit&id='.$c->id); ?>" style="font-size:13px;font-weight:500;color:var(--p)"><?php echo esc_html($c->name); ?></a>
          <span class="knk-badge knk-badge-gray">Bobot: <?php echo $c->weight; ?></span>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /col-main -->

<!-- Sidebar -->
<div class="knk-col-side">
  <div class="knk-card">
    <div class="knk-card-head"><span class="knk-card-title">Simpan</span></div>
    <div class="knk-card-body" style="display:flex;flex-direction:column;gap:10px">
      <button type="submit" class="knk-btn knk-btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Simpan Operator</button>
      <a href="<?php echo admin_url('admin.php?page=konektor-operators'); ?>" class="knk-btn knk-btn-ghost" style="width:100%;text-align:center">Batal</a>
      <span class="knk-save-status" style="font-size:12px;text-align:center"></span>
    </div>
  </div>

  <?php if ($operator) : ?>
  <div class="knk-card" style="margin-top:16px">
    <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-link"></i> Panel CS</span></div>
    <div class="knk-card-body">
      <p style="font-size:13px;color:var(--g600);margin:0 0 10px">Generate URL panel CS untuk operator ini.</p>
      <button type="button" class="knk-btn knk-btn-ghost knk-btn-sm knk-gen-token" data-id="<?php echo $operator->id; ?>" style="width:100%"><i class="fa-solid fa-key"></i> Generate URL Panel</button>
    </div>
  </div>
  <?php endif; ?>
</div><!-- /col-side -->

</div><!-- /cols -->
</form>

<!-- Modal Token -->
<div id="knk-token-modal" class="knk-modal-bg knk-hidden">
  <div class="knk-modal">
    <div class="knk-modal-head">
      <span class="knk-modal-title"><i class="fa-solid fa-link"></i> URL Panel CS</span>
      <button class="knk-modal-x knk-token-modal-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="knk-modal-body">
      <p style="font-size:13px;color:var(--g600);margin:0 0 10px">Kirimkan URL ini ke operator. Berlaku selamanya.</p>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="text" id="knk-token-url" class="knk-input" readonly style="font-size:12px;font-family:monospace">
        <button class="knk-btn knk-btn-ghost knk-btn-sm" id="knk-token-copy"><i class="fa-regular fa-copy"></i> Salin</button>
      </div>
    </div>
  </div>
</div>

</div>
