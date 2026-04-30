<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-book"></i> Panduan Penggunaan</h1></div>
</div>

<?php
$sections = [
  [
    'icon'  => 'fa-rocket',
    'title' => 'Mulai Cepat (Quick Start)',
    'steps' => [
      ['Buka <strong>Pengaturan</strong> → isi <em>Base Slug</em> (cth: <code>lp</code>) dan simpan.', null],
      ['Buka <strong>Operator / CS</strong> → klik <em>Tambah Operator</em> → isi nama, tipe (WhatsApp/Email/dll), dan kontak/nomor HP.', null],
      ['Buka <strong>Kampanye</strong> → klik <em>Kampanye Baru</em> → pilih tipe <em>Form</em> atau <em>Link</em>.', null],
      ['Assign operator ke kampanye, atur bobot jika perlu.', null],
      ['Salin URL kampanye atau kode embed HTML, tempel ke landing page Anda.', null],
      ['Lead masuk akan muncul di <strong>Manajemen Lead</strong> dan notifikasi dikirim via Telegram jika sudah dikonfigurasi.', null],
    ],
  ],
  [
    'icon'  => 'fa-bullhorn',
    'title' => 'Tipe Kampanye',
    'items' => [
      ['Form', 'Menampilkan form isian (nama, HP, email, dll). Cocok untuk landing page yang butuh data customer. Bisa disematkan via embed code atau diakses langsung via URL.'],
      ['Link', 'Redirect langsung ke WhatsApp CS. Klik direkam otomatis beserta IP dan cookie. Cocok untuk tombol "Chat WA" di landing page.'],
    ],
    'note' => 'URL kampanye: <code>https://situsanda.com/{base-slug}/{slug-kampanye}</code>',
  ],
  [
    'icon'  => 'fa-code',
    'title' => 'Embed Form ke Landing Page',
    'steps' => [
      ['Buka kampanye → salin <strong>Kode Embed HTML</strong> dari banner di atas halaman edit.', null],
      ['Tempel kode tersebut ke halaman HTML landing page Anda, di dalam <code>&lt;body&gt;</code>.', null],
      ['Form akan muncul dan submit langsung ke server Konektor. Cookie VID otomatis diset oleh JS embed untuk tracking.', null],
      ['Untuk Link, tombol sudah siap pakai — klik dicegat JS, cookie dan source URL direkam sebelum redirect.', null],
    ],
  ],
  [
    'icon'  => 'fa-rotate',
    'title' => 'Rotasi CS & Bobot',
    'steps' => [
      ['Setiap kampanye bisa di-assign beberapa operator CS.', null],
      ['Bobot (1–10) menentukan seberapa sering operator tersebut dipilih. Bobot 2 berarti 2x lebih sering dari bobot 1.', null],
      ['Operator dengan status <em>On</em> dan dalam jadwal kerja akan diprioritaskan. Jika semua off-duty, semua akan dipakai sebagai fallback.', null],
      ['Atur jadwal kerja per operator di menu <strong>Operator / CS → Edit</strong>.', null],
    ],
  ],
  [
    'icon'  => 'fa-shield-halved',
    'title' => 'Anti Double Lead',
    'steps' => [
      ['Aktifkan <em>Deteksi Double Lead</em> di pengaturan kampanye.', null],
      ['Sistem mendeteksi duplikat via 3 metode: <strong>Cookie</strong> (utama), <strong>Fingerprint</strong> (SHA256 phone+email), dan <strong>IP Address</strong>.', null],
      ['Cookie <code>konektor_vid</code> diset otomatis di browser saat halaman form dimuat atau link WA diklik.', null],
      ['Deteksi bersifat <em>lintas kampanye</em> — jika user sudah submit di kampanye A, kampanye B di halaman yang sama juga terdeteksi.', null],
      ['Lead double tetap dicatat tapi ditandai kolom <em>Double</em> = Ya.', null],
    ],
  ],
  [
    'icon'  => 'fa-telegram',
    'title' => 'Notifikasi Telegram Bot',
    'steps' => [
      ['Buat bot di Telegram via <strong>@BotFather</strong> → <code>/newbot</code> → salin token.', null],
      ['Masukkan token di <strong>Pengaturan → Token Telegram Bot</strong>.', null],
      ['Setiap operator isi <em>Telegram Chat ID</em> di halaman edit operator. Cara dapat Chat ID: chat dengan <strong>@userinfobot</strong>.', null],
      ['Notifikasi otomatis terkirim saat ada lead baru. Untuk Link, notif menampilkan IP dan sumber klik.', null],
      ['Perintah bot yang tersedia:', [
        '<code>/leads</code> — lihat 5 lead terbaru',
        '<code>/status {id} {new|contacted|purchased|cancelled}</code> — update status lead',
        '<code>/block {id} {alasan}</code> — blokir customer',
      ]],
    ],
  ],
  [
    'icon'  => 'fa-message',
    'title' => 'Pesan Follow-Up ke Customer',
    'steps' => [
      ['Khusus kampanye <strong>Form</strong> (tidak tersedia untuk Link).', null],
      ['Isi <em>Pesan Follow-Up ke Customer</em> di halaman edit kampanye.', null],
      ['Shortcode yang tersedia: <code>[cname]</code> <code>[cphone]</code> <code>[cemail]</code> <code>[caddress]</code> <code>[catatan]</code> <code>[product]</code> <code>[quantity]</code> <code>[oname]</code>', null],
      ['Tombol <em>Follow-Up</em> muncul di Panel CS per lead. Klik akan membuka WA ke nomor customer dengan pesan yang sudah diisi shortcode.', null],
      ['Bot Telegram juga mengirimkan link follow-up otomatis di notifikasi lead baru.', null],
    ],
  ],
  [
    'icon'  => 'fa-chart-bar',
    'title' => 'Tracking Pixel',
    'items' => [
      ['Meta / Facebook', 'Pixel browser + CAPI server-side. CAPI aktif untuk form submit dan WA click. Isi Pixel ID, Access Token, dan pilih event per tahap.'],
      ['Google Ads / GTM', 'GTM container dan Google Ads conversion tracking. Isi Conversion ID (AW-...) dan label per event.'],
      ['TikTok Pixel', 'Pixel TikTok Ads standar. Isi Pixel ID dan pilih event dari dropdown.'],
      ['Snack Video', 'Pixel Kwai/Snack Video. Isi Pixel ID dan pilih event. Kode pixel disisipkan otomatis.'],
    ],
    'note' => 'Pixel tidak ditembak untuk lead yang terdeteksi sebagai double.',
  ],
  [
    'icon'  => 'fa-user-shield',
    'title' => 'Blokir Customer',
    'steps' => [
      ['Aktifkan <em>Blokir Customer</em> di pengaturan kampanye.', null],
      ['Untuk blokir dari tabel Lead: klik tombol <em>Blokir</em> merah di baris lead, isi alasan.', null],
      ['Pemblokiran menyimpan IP, cookie ID, fingerprint, dan nomor HP customer.', null],
      ['Customer yang diblokir tidak bisa mengakses kampanye dan mendapat pesan blokir yang bisa dikustomisasi.', null],
      ['Kelola daftar blokir di <strong>Pengaturan → Daftar Customer Diblokir</strong>.', null],
    ],
  ],
  [
    'icon'  => 'fa-id-card',
    'title' => 'Panel CS',
    'steps' => [
      ['Setiap operator punya link Panel CS unik berdasarkan token — tidak perlu login WordPress.', null],
      ['Panel CS menampilkan lead yang di-assign ke operator tersebut.', null],
      ['CS bisa update status lead, follow-up customer via WA, dan melihat detail lead.', null],
      ['Generate token panel di <strong>Operator / CS → Edit → Generate Token Panel</strong> atau otomatis dibuat saat notif Telegram dikirim.', null],
    ],
  ],
];
?>

<?php foreach ($sections as $i => $sec) : ?>
<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-head">
    <span class="knk-card-title">
      <i class="fa-solid <?php echo $sec['icon']; ?>" style="color:var(--p)"></i>
      <?php echo ($i+1) . '. ' . $sec['title']; ?>
    </span>
  </div>
  <div class="knk-card-body" style="padding:16px 20px">

    <?php if (!empty($sec['steps'])) : ?>
    <ol style="margin:0;padding-left:18px;color:var(--g700);font-size:13px;line-height:1.7">
      <?php foreach ($sec['steps'] as [$text,$subitems]) : ?>
      <li style="margin-bottom:6px"><?php echo $text; ?>
        <?php if ($subitems) : ?>
        <ul style="margin-top:4px;padding-left:16px">
          <?php foreach ($subitems as $sub) : ?>
          <li style="margin-bottom:2px"><?php echo $sub; ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <?php if (!empty($sec['items'])) : ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <?php foreach ($sec['items'] as [$label,$desc]) : ?>
      <div style="background:var(--g50);border-radius:8px;padding:12px">
        <div style="font-weight:600;font-size:13px;color:var(--g800);margin-bottom:4px"><?php echo $label; ?></div>
        <div style="font-size:12px;color:var(--g600);line-height:1.5"><?php echo $desc; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($sec['note'])) : ?>
    <p class="knk-hint" style="margin-top:12px;padding:8px 12px;background:var(--p-lt);border-radius:6px;color:var(--p-dk)">
      <i class="fa-solid fa-circle-info"></i> <?php echo $sec['note']; ?>
    </p>
    <?php endif; ?>

  </div>
</div>
<?php endforeach; ?>

<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-body" style="padding:16px 20px;text-align:center;color:var(--g500);font-size:13px">
    Dibuat oleh <a href="https://hanifprm.my.id" target="_blank" style="color:var(--p);font-weight:600">Hanif Pramono</a> &mdash;
    <a href="<?php echo admin_url('admin.php?page=konektor-about'); ?>" style="color:var(--p)">Tentang Plugin</a>
  </div>
</div>

</div>
