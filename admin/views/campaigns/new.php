<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left">
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns'); ?>" class="knk-breadcrumb"><i class="fa-solid fa-arrow-left"></i> Kampanye</a>
    <h1>Kampanye Baru</h1>
  </div>
</div>

<div style="max-width:680px;margin:0 auto;padding:20px 0">
  <p style="text-align:center;color:var(--g500);font-size:14px;margin-bottom:32px">Pilih jenis kampanye yang ingin dibuat</p>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Form Order -->
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new&type=form'); ?>"
       style="display:block;text-decoration:none;border:2px solid var(--g200);border-radius:14px;padding:32px 24px;background:#fff;text-align:center;transition:.2s;cursor:pointer"
       onmouseover="this.style.borderColor='var(--p)';this.style.boxShadow='0 0 0 4px var(--p-lt)'"
       onmouseout="this.style.borderColor='var(--g200)';this.style.boxShadow='none'">
      <div style="font-size:44px;margin-bottom:16px;line-height:1;color:var(--p)"><i class="fa-solid fa-clipboard-list"></i></div>
      <div style="font-size:18px;font-weight:700;color:var(--g900);margin-bottom:8px">Form Order</div>
      <div style="font-size:13px;color:var(--g500);line-height:1.6">
        Halaman form lengkap dengan builder field.<br>
        Lead masuk ke database dengan data nama, HP, email, alamat, dll.<br>
        Setelah submit, redirect ke CS via rotator.
      </div>
      <div style="margin-top:20px">
        <span style="display:inline-flex;align-items:center;gap:6px;background:var(--p);color:#fff;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600">
          <i class="fa-solid fa-arrow-right"></i> Buat Form
        </span>
      </div>
    </a>

    <!-- WA Link -->
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new&type=wa_link'); ?>"
       style="display:block;text-decoration:none;border:2px solid var(--g200);border-radius:14px;padding:32px 24px;background:#fff;text-align:center;transition:.2s;cursor:pointer"
       onmouseover="this.style.borderColor='#16a34a';this.style.boxShadow='0 0 0 4px #dcfce7'"
       onmouseout="this.style.borderColor='var(--g200)';this.style.boxShadow='none'">
      <div style="font-size:44px;margin-bottom:16px;line-height:1;color:#16a34a"><i class="fa-brands fa-whatsapp"></i></div>
      <div style="font-size:18px;font-weight:700;color:var(--g900);margin-bottom:8px">Link WA Langsung</div>
      <div style="font-size:13px;color:var(--g500);line-height:1.6">
        URL yang langsung redirect ke WhatsApp CS.<br>
        Cocok untuk tombol di Elementor / landing page.<br>
        IP, cookie &amp; user agent visitor otomatis tercatat.
      </div>
      <div style="margin-top:20px">
        <span style="display:inline-flex;align-items:center;gap:6px;background:#16a34a;color:#fff;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600">
          <i class="fa-solid fa-arrow-right"></i> Buat WA Link
        </span>
      </div>
    </a>

  </div>

  <!-- Feature comparison -->
  <div style="margin-top:32px;background:#fff;border:1px solid var(--g200);border-radius:12px;overflow:hidden">
    <div style="padding:14px 20px;background:var(--g50);border-bottom:1px solid var(--g200)">
      <span style="font-size:13px;font-weight:700;color:var(--g700)">Perbandingan Fitur</span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="background:var(--g50)">
          <th style="padding:10px 16px;text-align:left;font-weight:600;color:var(--g600);border-bottom:1px solid var(--g200)">Fitur</th>
          <th style="padding:10px 16px;text-align:center;font-weight:600;color:var(--p);border-bottom:1px solid var(--g200)"><i class="fa-solid fa-clipboard-list"></i> Form</th>
          <th style="padding:10px 16px;text-align:center;font-weight:600;color:#16a34a;border-bottom:1px solid var(--g200)"><i class="fa-brands fa-whatsapp"></i> WA Link</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rows = [
          ['Catat nama, HP, email, alamat',    true,  false],
          ['Catat IP address visitor',         true,  true],
          ['Catat cookie ID visitor',          true,  true],
          ['Catat user agent browser',         true,  true],
          ['Catat referrer URL',               true,  true],
          ['Rotator CS berbobot',              true,  true],
          ['Deteksi double lead',              true,  false],
          ['Blokir customer',                  true,  true],
          ['Pixel Meta / Google / TikTok',     true,  false],
          ['Thanks page custom',               true,  false],
          ['Notifikasi Telegram',              true,  true],
        ];
        foreach ($rows as $i => [$label, $form, $wa]) : ?>
        <tr style="border-bottom:1px solid var(--g100)<?php echo $i === count($rows)-1 ? ';border-bottom:none' : ''; ?>">
          <td style="padding:9px 16px;color:var(--g700)"><?php echo $label; ?></td>
          <td style="padding:9px 16px;text-align:center"><?php echo $form ? '<i class="fa-solid fa-check" style="color:#16a34a;font-weight:700"></i>' : '<span style="color:var(--g300)">—</span>'; ?></td>
          <td style="padding:9px 16px;text-align:center"><?php echo $wa   ? '<i class="fa-solid fa-check" style="color:#16a34a;font-weight:700"></i>' : '<span style="color:var(--g300)">—</span>'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>
</div>
