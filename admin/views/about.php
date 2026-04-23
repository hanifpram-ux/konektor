<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-circle-info"></i> Tentang Konektor</h1></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

  <!-- Kolom kiri -->
  <div>

    <div class="knk-card">
      <div class="knk-card-body" style="padding:28px 32px">
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px">
          <div style="width:56px;height:56px;background:var(--p);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-link" style="color:#fff;font-size:24px"></i>
          </div>
          <div>
            <h2 style="margin:0;font-size:22px;font-weight:700;color:var(--g900)">Konektor</h2>
            <span style="font-size:12px;color:var(--g500)">CS Rotator &amp; Lead Management — v<?php echo esc_html(KONEKTOR_VERSION); ?></span>
          </div>
        </div>

        <p style="color:var(--g700);line-height:1.7;margin:0 0 20px">
          Konektor adalah plugin WordPress untuk mengelola distribusi lead ke tim CS (Customer Service) secara otomatis.
          Dukung kampanye berbasis form isian maupun link WA langsung, dilengkapi tracking pixel, rotasi CS berbobot,
          anti-double lead, notifikasi Telegram, dan panel CS mandiri.
        </p>

        <hr style="border:none;border-top:1px solid var(--g200);margin:0 0 20px">

        <h3 style="font-size:14px;font-weight:600;color:var(--g800);margin:0 0 12px">Fitur Utama</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <?php
          $features = [
            ['fa-rotate','Rotasi CS Otomatis','Distribusi lead ke CS dengan bobot prioritas dan jadwal on-duty'],
            ['fa-inbox','Manajemen Lead','Catat, filter, ekspor, dan update status lead dari satu dasbor'],
            ['fa-shield-halved','Anti Double Lead','Deteksi duplikat via cookie, fingerprint, dan IP lintas kampanye'],
            ['fa-telegram','Notifikasi Telegram','Bot Telegram kirim notifikasi real-time + perintah update status'],
            ['fa-chart-bar','Analitik','Statistik per kampanye, per operator, dan grafik harian'],
            ['fa-code','Embed Form','Sematkan form ke landing page manapun via kode HTML satu baris'],
            ['fa-link','WA Link Tracking','Rekam klik link WA beserta cookie dan IP secara otomatis'],
            ['fa-chart-line','Pixel Tracking','Meta CAPI, Google Ads, TikTok Pixel, dan Snack Video Pixel'],
          ];
          foreach ($features as [$icon,$title,$desc]) : ?>
          <div style="display:flex;gap:10px;padding:12px;background:var(--g50);border-radius:8px">
            <div style="width:32px;height:32px;background:var(--p-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fa-solid <?php echo $icon; ?>" style="color:var(--p);font-size:13px"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:13px;color:var(--g800)"><?php echo $title; ?></div>
              <div style="font-size:11px;color:var(--g500);margin-top:2px;line-height:1.4"><?php echo $desc; ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="knk-card" style="margin-top:16px">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-microchip"></i> Informasi Sistem</span></div>
      <div class="knk-card-body">
        <?php
        global $wpdb;
        $db_ver = get_option('konektor_db_version','—');
        $leads_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}konektor_leads");
        $camp_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}konektor_campaigns");
        $rows = [
          ['Versi Plugin',     KONEKTOR_VERSION],
          ['Versi Database',   $db_ver],
          ['Versi WordPress',  get_bloginfo('version')],
          ['Versi PHP',        PHP_VERSION],
          ['Total Kampanye',   $camp_count],
          ['Total Lead',       number_format($leads_count)],
        ];
        ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <?php foreach ($rows as [$label,$val]) : ?>
          <tr style="border-bottom:1px solid var(--g200)">
            <td style="padding:8px 4px;color:var(--g500);width:50%"><?php echo $label; ?></td>
            <td style="padding:8px 4px;font-weight:500;color:var(--g800);font-family:monospace"><?php echo esc_html($val); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

  </div>

  <!-- Kolom kanan -->
  <div>

    <div class="knk-card">
      <div class="knk-card-body" style="padding:24px;text-align:center">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--p),#7c3aed);margin:0 auto 16px;display:flex;align-items:center;justify-content:center">
          <i class="fa-solid fa-user" style="color:#fff;font-size:28px"></i>
        </div>
        <div style="font-size:18px;font-weight:700;color:var(--g900)">Hanif Pramono</div>
        <div style="font-size:12px;color:var(--g500);margin-top:4px">Developer &amp; Creator</div>
        <hr style="border:none;border-top:1px solid var(--g200);margin:16px 0">
        <a href="https://hanifprm.my.id" target="_blank" class="knk-btn knk-btn-primary" style="width:100%;justify-content:center;display:flex">
          <i class="fa-solid fa-globe"></i> hanifprm.my.id
        </a>
        <p style="font-size:11px;color:var(--g400);margin:12px 0 0;line-height:1.5">
          Plugin ini dibuat dengan ❤️ untuk membantu bisnis Indonesia mengelola CS dan lead lebih efisien.
        </p>
      </div>
    </div>

    <div class="knk-card" style="margin-top:16px">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-file-lines"></i> Lisensi</span></div>
      <div class="knk-card-body" style="font-size:13px;color:var(--g600);line-height:1.6">
        <p style="margin:0">Plugin ini dirilis di bawah lisensi <strong>GPL v2 or later</strong>.</p>
        <p style="margin:8px 0 0">Bebas digunakan, dimodifikasi, dan didistribusikan sesuai ketentuan lisensi GPL.</p>
      </div>
    </div>

    <div class="knk-card" style="margin-top:16px">
      <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-link"></i> Tautan</span></div>
      <div class="knk-card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="https://hanifprm.my.id" target="_blank" class="knk-btn knk-btn-ghost" style="justify-content:flex-start">
          <i class="fa-solid fa-globe"></i> Website Developer
        </a>
        <a href="<?php echo admin_url('admin.php?page=konektor-guide'); ?>" class="knk-btn knk-btn-ghost" style="justify-content:flex-start">
          <i class="fa-solid fa-book"></i> Panduan Penggunaan
        </a>
        <a href="<?php echo admin_url('admin.php?page=konektor-settings'); ?>" class="knk-btn knk-btn-ghost" style="justify-content:flex-start">
          <i class="fa-solid fa-gear"></i> Pengaturan Plugin
        </a>
      </div>
    </div>

  </div>

</div>

</div>
