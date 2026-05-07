<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$has_telegram = (bool) Konektor_Helper::get_setting( 'telegram_bot_token', '' );
$has_operator = count( Konektor_Operator::get_all() ) > 0;
$has_campaign = count( Konektor_Campaign::get_all() ) > 0;

$cs_slug  = Konektor_Helper::get_setting( 'cs_panel_slug', 'cs-panel' );
$site_url = rtrim( home_url(), '/' );
$base_slug = Konektor_Helper::get_setting( 'base_slug', 'lp' );
$example_url = $site_url . '/' . $base_slug . '/nama-kampanye';
$cs_url      = $site_url . '/' . $cs_slug . '/TOKEN';

$u = function( $page ) { return admin_url( 'admin.php?page=' . $page ); };

$badge_class = [
    'done'     => 'knk-badge-success',
    'required' => 'knk-badge-blue',
    'optional' => 'knk-badge-gray',
    'info'     => 'knk-badge-gray',
];
$badge_label = [
    'done'     => 'Selesai',
    'required' => 'Wajib',
    'optional' => 'Opsional',
    'info'     => 'Info',
];

$steps = [
    [
        'num'    => 1,
        'title'  => 'Atur Telegram Bot (Opsional)',
        'badge'  => $has_telegram ? 'done' : 'optional',
        'icon'   => 'fa-telegram',
        'desc'   => 'Setup bot Telegram untuk menerima notifikasi lead baru secara real-time di HP Anda.',
        'action' => $u( 'konektor-settings' ),
        'action_label' => 'Pengaturan',
        'items'  => [
            'Buka Telegram, cari <strong>@BotFather</strong>',
            'Ketik <code>/newbot</code>, ikuti instruksi, beri nama bot',
            'Salin <strong>Bot Token</strong> yang diberikan BotFather',
            'Paste token di <strong>Pengaturan → Telegram Bot Token</strong>, klik Simpan',
            'Untuk mendapatkan Chat ID operator: cari <strong>@userinfobot</strong> di Telegram, klik Start',
            'Chat ID diisi di profil setiap operator saat menambah/edit operator',
            'Klik <strong>Daftarkan Webhook</strong> di halaman Pengaturan agar callback Telegram aktif',
            'Setelah webhook aktif, operator dapat update status lead langsung dari tombol di notifikasi Telegram',
        ],
        'note' => 'Tanpa Telegram, lead tetap masuk dan bisa dikelola dari panel admin dan panel CS. Telegram hanya untuk notifikasi instan.',
        'cmds' => [
            '<code>/leads</code> — lihat 5 lead terbaru milik operator',
            '<code>/status {id} {new|contacted|purchased|cancelled}</code> — update status lead',
            '<code>/block {id} {alasan}</code> — blokir customer',
        ],
    ],
    [
        'num'    => 2,
        'title'  => 'Tambahkan Operator CS',
        'badge'  => $has_operator ? 'done' : 'required',
        'icon'   => 'fa-users',
        'desc'   => 'Operator CS adalah orang yang menerima dan menangani lead. Bisa WhatsApp, Telegram, Email, atau LINE.',
        'action' => $u( 'konektor-operators' ),
        'action_label' => 'Tambah Operator',
        'items'  => [
            'Buka menu <strong>Operator / CS</strong>',
            'Klik <strong>Tambah Operator</strong>',
            'Isi nama, tipe kontak (WhatsApp/Telegram/Email/LINE), dan nomor/username',
            'Jika pakai Telegram: isi <strong>Telegram Chat ID</strong> (dari @userinfobot)',
            'Atur <strong>jadwal jam kerja</strong> — operator tidak menerima lead di luar jadwal',
            'Klik <strong>Simpan</strong>',
            'Buka kembali operator yang dibuat, klik <strong>Generate Token Panel</strong> untuk membuat link akses CS panel',
        ],
        'note' => 'Anda bisa menambahkan banyak operator. Lead didistribusikan otomatis dengan weighted round-robin berdasarkan bobot yang diatur per kampanye.',
        'cmds' => [],
    ],
    [
        'num'    => 3,
        'title'  => 'Buat Kampanye',
        'badge'  => $has_campaign ? 'done' : 'required',
        'icon'   => 'fa-bullhorn',
        'desc'   => 'Kampanye adalah halaman form atau tombol WA yang dibagikan ke calon customer melalui iklan atau landing page.',
        'action' => $u( 'konektor-campaigns' ),
        'action_label' => 'Kampanye Baru',
        'items'  => [
            'Buka menu <strong>Kampanye</strong>, klik <strong>Kampanye Baru</strong>',
            'Tab <strong>Informasi Dasar</strong>: isi nama produk, nama toko, pilih tipe (<em>Form</em> atau <em>WA Link</em>)',
            'Tab <strong>Form & Tampilan</strong>: pilih template warna (9 pilihan), aktifkan field yang diperlukan (nama, HP, email, alamat, catatan, produk)',
            'Tab <strong>Link & Tampilan</strong> (jika WA Link): pilih template, icon tombol, warna kustom',
            'Tab <strong>Halaman Thanks</strong>: atur pesan terima kasih, tema warna, dan redirect ke CS via WA/Telegram/dll',
            'Tab <strong>Pixel & Tracking</strong>: isi Pixel ID Meta/TikTok/Google/Snack jika ada tracking iklan',
            'Tab <strong>Operator CS</strong>: pilih operator yang menerima lead, atur bobot distribusi (1-10)',
            'Tab <strong>Pengaturan Lanjut</strong>: aktifkan deteksi double lead, blokir customer, batasi domain',
            'Klik <strong>Simpan Kampanye</strong>',
        ],
        'note' => null,
        'cmds' => [],
    ],
    [
        'num'    => 4,
        'title'  => 'Bagikan URL atau Embed Kode',
        'badge'  => 'info',
        'icon'   => 'fa-code',
        'desc'   => 'Setelah kampanye disimpan, salin URL atau kode embed untuk dipasang di iklan atau landing page.',
        'action' => $u( 'konektor-campaigns' ),
        'action_label' => 'Lihat Kampanye',
        'items'  => [
            'Di halaman <strong>Kampanye</strong>, klik ikon <strong>&lt;/&gt;</strong> (Kode Embed) pada kampanye',
            'Tab <strong>URL Langsung</strong>: salin URL untuk dibagikan langsung (iklan, bio link, QR code)',
            'Tab <strong>Form Embed</strong>: salin kode HTML untuk ditempel di dalam <code>&lt;body&gt;</code> landing page — pixel sudah termasuk otomatis',
            'Tab <strong>Tombol / Link</strong>: salin kode tombol WA untuk website atau blog',
            'Untuk tracking iklan berbayar: tambahkan parameter <code>?click_id=XXXX</code> di URL kampanye untuk SnackVideo/Kwai',
            'Untuk Meta/TikTok: parameter akan ditangkap otomatis dari cookie browser (<code>_fbp</code>, <code>_fbc</code>, <code>_ttp</code>)',
        ],
        'note' => 'Kode embed otomatis menyertakan pixel Meta, TikTok, Google, dan Snack sesuai konfigurasi kampanye. Cross-domain diizinkan secara otomatis.',
        'cmds' => [],
    ],
    [
        'num'    => 5,
        'title'  => 'Atur Pixel & Tracking Konversi',
        'badge'  => 'optional',
        'icon'   => 'fa-chart-bar',
        'desc'   => 'Hubungkan kampanye dengan platform iklan untuk tracking konversi yang akurat — server-side dan browser-side.',
        'action' => null,
        'action_label' => null,
        'items'  => [
            '<strong>Meta / Facebook CAPI</strong>: isi Pixel ID dan Access Token dari Meta Business Suite → Events Manager → Conversions API. Event: PageView, Lead, Purchase, dll',
            '<strong>TikTok Events API v1.3</strong>: isi Pixel ID dan Access Token dari TikTok Ads Manager → Assets → Events. Event: ViewContent, SubmitForm, Purchase, dll',
            '<strong>Google Ads</strong>: isi Conversion ID (format: <code>AW-xxxxxxxx</code>) dan label konversi per event (Page Load, Submit, Thanks Page)',
            '<strong>Google Tag Manager</strong>: isi GTM ID (format: <code>GTM-XXXXX</code>) — akan diinjeksi di semua halaman kampanye',
            '<strong>Google Analytics 4</strong>: isi GA4 Measurement ID (format: <code>G-XXXXXXXXXX</code>)',
            '<strong>SnackVideo/Kwai Ads</strong>: isi Pixel ID dan Access Token dari Kwai For Business → Pixel. Gunakan test_click_id saat testing',
            'Setiap platform bisa diaktifkan/nonaktifkan per kampanye secara independen',
            'Event per tahap: <em>Page Load</em> (saat form dimuat), <em>Form Submit</em> (saat form dikirim), <em>Thanks Page</em> (saat halaman terima kasih tampil)',
            'Test event: isi <em>Test Event Code</em> (Meta/TikTok) atau aktifkan <em>Test Mode</em> (Snack) untuk verifikasi tanpa mempengaruhi data produksi',
        ],
        'note' => 'Server-side tracking bekerja meskipun pengunjung menggunakan ad blocker. Browser-side pixel hanya diinjeksi jika token server-side belum diisi (hindari penembakan ganda).',
        'cmds' => [],
    ],
    [
        'num'    => 6,
        'title'  => 'Kelola Lead Masuk',
        'badge'  => 'info',
        'icon'   => 'fa-file-lines',
        'desc'   => 'Pantau dan kelola semua lead yang masuk dari berbagai kampanye dalam satu tempat.',
        'action' => $u( 'konektor-leads' ),
        'action_label' => 'Lihat Leads',
        'items'  => [
            'Buka menu <strong>Leads</strong> untuk melihat semua lead masuk',
            'Filter berdasarkan kampanye, operator, atau status lead',
            'Klik nama lead untuk melihat detail: alamat, catatan, IP, source URL, referrer',
            'Update status lead: <em>Baru → Dihubungi → Beli / Batal / Diblokir</em>',
            'Tambah catatan internal pada setiap lead',
            'Tombol <strong>Follow-Up</strong> membuka WA ke customer dengan pesan template yang sudah diisi shortcode',
            'Gunakan <strong>Export CSV</strong> di pojok kanan atas untuk ekspor data ke Excel',
            'Lead yang terdeteksi duplikat ditandai badge <em>Double</em> — tetap disimpan tapi tidak memicu pixel',
        ],
        'note' => null,
        'cmds' => [],
    ],
    [
        'num'    => 7,
        'title'  => 'Panel CS untuk Agent',
        'badge'  => 'info',
        'icon'   => 'fa-id-card',
        'desc'   => 'Setiap operator mendapat link unik untuk mengakses lead mereka — tanpa perlu login ke WordPress.',
        'action' => $u( 'konektor-operators' ),
        'action_label' => 'Lihat Operator',
        'items'  => [
            'Buka <strong>Operator / CS</strong>, klik <strong>Edit</strong> pada operator yang diinginkan',
            'Klik <strong>Generate Token Panel</strong> untuk membuat link akses unik',
            'Salin link panel dan bagikan ke agent CS yang bersangkutan',
            'Agent CS bisa melihat lead mereka, update status, dan chat WA langsung dari panel tanpa login WordPress',
            'Generate ulang token kapan saja untuk menonaktifkan token lama (keamanan)',
            'Bot Telegram juga menyertakan link follow-up otomatis di setiap notifikasi lead baru',
        ],
        'note' => 'URL panel CS: <code>' . esc_html( $cs_url ) . '</code>',
        'cmds' => [],
    ],
    [
        'num'    => 8,
        'title'  => 'Analitik & Laporan',
        'badge'  => 'info',
        'icon'   => 'fa-chart-line',
        'desc'   => 'Pantau performa kampanye, konversi, dan kinerja setiap operator dalam satu dashboard.',
        'action' => $u( 'konektor-analytics' ),
        'action_label' => 'Lihat Analitik',
        'items'  => [
            'Buka menu <strong>Analitik</strong>',
            'Pilih rentang waktu preset (7/14/30/60/90 hari) atau gunakan <strong>Custom Date Range</strong> untuk tanggal bebas',
            'Lihat grafik lead harian (bar chart dan line chart)',
            'Kartu statistik: Total Leads, Purchased, Double Lead, Form Submit, Page Views, Konversi Global',
            'Tabel <em>Per Kampanye</em>: breakdown lead, purchased, double, page views, submit, dan persentase konversi',
            'Tabel <em>Per Operator</em>: total lead, purchased, pending per CS agent',
            'Export data lead ke CSV kapan saja dari tombol Export di pojok kanan atas',
        ],
        'note' => null,
        'cmds' => [],
    ],
];
?>

<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-book"></i> Panduan Setup</h1><p style="margin:2px 0 0;color:var(--g500);font-size:13px;">Ikuti langkah-langkah ini untuk menggunakan Konektor secara optimal</p></div>
</div>

<!-- Progress summary -->
<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-body" style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;padding:16px 20px">
    <div style="flex:1;min-width:0">
      <div style="font-size:14px;font-weight:600;margin-bottom:4px;color:var(--g800)">Status Setup</div>
      <div style="font-size:13px;color:var(--g500)">
        <?php $done = ( $has_telegram ? 1 : 0 ) + ( $has_operator ? 1 : 0 ) + ( $has_campaign ? 1 : 0 );
        if ( $done >= 2 ) : ?>Konektor sudah siap digunakan. Semua konfigurasi dasar terpasang.
        <?php else : ?><?php echo ( 2 - $done ); ?> langkah wajib belum selesai.<?php endif; ?>
      </div>
    </div>
    <div style="display:flex;gap:20px;flex-shrink:0">
      <?php foreach ( [
        ['Telegram', $has_telegram],
        ['Operator', $has_operator],
        ['Kampanye', $has_campaign],
      ] as [$lbl, $ok] ) : ?>
      <div style="text-align:center">
        <div style="font-size:20px;color:<?php echo $ok ? 'var(--ok)' : 'var(--g300)'; ?>">
          <i class="fa-solid <?php echo $ok ? 'fa-circle-check' : 'fa-circle'; ?>"></i>
        </div>
        <div style="font-size:11px;color:var(--g500);margin-top:2px"><?php echo $lbl; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- URL info card -->
<div class="knk-card" style="margin-bottom:16px;border-color:rgba(99,102,241,.2);background:rgba(99,102,241,.03)">
  <div class="knk-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px 20px">
    <div>
      <div style="font-size:11px;font-weight:600;color:var(--g500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">URL Kampanye</div>
      <code style="font-size:12px;color:var(--p);word-break:break-all;display:block"><?php echo esc_html( $example_url ); ?></code>
      <div style="font-size:11px;color:var(--g400);margin-top:4px">Base slug diatur di <a href="<?php echo $u( 'konektor-settings' ); ?>" style="color:var(--p)">Pengaturan</a></div>
    </div>
    <div>
      <div style="font-size:11px;font-weight:600;color:var(--g500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Panel CS</div>
      <code style="font-size:12px;color:var(--p);word-break:break-all;display:block"><?php echo esc_html( $cs_url ); ?></code>
      <div style="font-size:11px;color:var(--g400);margin-top:4px">Token unik per operator, dibuat dari menu Operator / CS</div>
    </div>
  </div>
</div>

<!-- Steps -->
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
<?php foreach ( $steps as $step ) :
    $is_done = $step['badge'] === 'done';
?>
<div class="knk-card" style="overflow:hidden">
  <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;"
       onclick="knkToggle(<?php echo $step['num']; ?>)">
    <!-- Number circle -->
    <div style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;
         <?php echo $is_done ? 'background:rgba(34,197,94,.12);color:var(--ok)' : 'background:var(--g100);color:var(--g500)'; ?>">
      <?php if ( $is_done ) : ?><i class="fa-solid fa-check"></i><?php else : echo $step['num']; ?><?php endif; ?>
    </div>
    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:14px;font-weight:600;color:var(--g800)"><?php echo esc_html( $step['title'] ); ?></span>
        <span class="knk-badge <?php echo $badge_class[ $step['badge'] ]; ?>"><?php echo $badge_label[ $step['badge'] ]; ?></span>
      </div>
      <p style="margin:3px 0 0;font-size:12px;color:var(--g500);line-height:1.5"><?php echo esc_html( $step['desc'] ); ?></p>
    </div>
    <?php if ( $step['action'] ) : ?>
    <a href="<?php echo esc_url( $step['action'] ); ?>" class="knk-btn knk-btn-ghost" style="flex-shrink:0;font-size:12px;" onclick="event.stopPropagation()">
      <i class="fa-solid <?php echo esc_attr( $step['icon'] ); ?>"></i> <?php echo esc_html( $step['action_label'] ); ?>
    </a>
    <?php endif; ?>
    <div id="knk-chev-<?php echo $step['num']; ?>" style="color:var(--g400);flex-shrink:0;transition:transform .2s">
      <i class="fa-solid fa-chevron-down"></i>
    </div>
  </div>
  <div id="knk-body-<?php echo $step['num']; ?>" style="display:none;border-top:1px solid var(--g200);padding:16px 20px 20px">
    <ol style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:9px">
      <?php foreach ( $step['items'] as $idx => $item ) : ?>
      <li style="display:flex;gap:10px;align-items:flex-start">
        <span style="width:20px;height:20px;border-radius:50%;background:var(--g100);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--g500);flex-shrink:0;margin-top:2px"><?php echo $idx + 1; ?></span>
        <span style="font-size:13px;color:var(--g700);line-height:1.6"><?php echo $item; ?></span>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php if ( ! empty( $step['cmds'] ) ) : ?>
    <div style="margin-top:12px;padding:12px 14px;background:var(--g50);border-radius:8px;">
      <div style="font-size:11px;font-weight:600;color:var(--g500);text-transform:uppercase;margin-bottom:8px">Perintah Bot Telegram</div>
      <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:4px">
        <?php foreach ( $step['cmds'] as $cmd ) : ?>
        <li style="font-size:12px;color:var(--g600)"><?php echo $cmd; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <?php if ( $step['note'] ) : ?>
    <div style="margin-top:12px;padding:10px 14px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);border-radius:8px;font-size:12px;color:#92400e;display:flex;gap:8px;align-items:flex-start">
      <i class="fa-solid fa-circle-info" style="margin-top:2px;flex-shrink:0"></i>
      <span><?php echo $step['note']; ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- FAQ -->
<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-circle-question" style="color:var(--p)"></i> Pertanyaan Umum</span></div>
  <div class="knk-card-body" style="padding:8px 0 0">
  <div style="padding:4px 0">
    <?php
    $faqs = [
        ['Apakah Konektor bisa digunakan tanpa Telegram?',
         'Ya. Telegram hanya untuk notifikasi real-time. Lead tetap masuk dan bisa dikelola dari panel admin maupun panel CS tanpa Telegram sama sekali.'],
        ['Berapa banyak operator dan kampanye yang bisa dibuat?',
         'Tidak ada batasan. Tambahkan operator dan kampanye sebanyak yang dibutuhkan. Bobot distribusi lead (1–10) dapat diatur per operator per kampanye.'],
        ['Bagaimana cara kerja deteksi lead duplikat?',
         'Konektor mendeteksi duplikat via 3 metode: Cookie konektor_vid (utama), Fingerprint SHA-256 kombinasi nomor HP+email, dan IP address. Lead duplikat tetap disimpan tapi ditandai Double = Ya dan tidak memicu pixel.'],
        ['Apakah data lead aman?',
         'Ya. Data PII (nama, HP, email, alamat) dienkripsi menggunakan AES-256-CBC sebelum disimpan ke database. Aktifkan di Pengaturan → Enkripsi Data Lead.'],
        ['Apakah pixel tracking bekerja jika pengunjung pakai ad blocker?',
         'Ya! Konektor menggunakan server-side tracking (CAPI) untuk Meta dan TikTok Events API. Event dikirim langsung dari server ke platform iklan tanpa melalui browser pengunjung, sehingga tidak terpengaruh ad blocker.'],
        ['Bagaimana cara embed form ke halaman yang berbeda domain?',
         'Salin kode embed dari halaman Kampanye dan tempel ke dalam &lt;body&gt; halaman manapun. CORS sudah dikonfigurasi otomatis — form dapat di-submit dari domain yang berbeda dengan domain WordPress Anda.'],
        ['Bagaimana cara backup atau export data lead?',
         'Gunakan tombol Export CSV di halaman Leads atau Analitik. Pilih filter yang diinginkan (per kampanye, status, tanggal) lalu klik export untuk mendapatkan file CSV yang bisa dibuka di Excel.'],
        ['Apa perbedaan tipe Form dan WA Link?',
         'Form menampilkan halaman isian data (nama, HP, email, dll) — cocok untuk landing page yang butuh data customer sebelum diarahkan ke CS. WA Link adalah tombol yang langsung redirect ke WhatsApp CS dengan lead tercatat otomatis (nama, IP, source URL, cookie).'],
    ];
    foreach ( $faqs as [$q, $a] ) : ?>
    <details style="border-top:1px solid var(--g200);padding:0">
      <summary style="padding:14px 20px;font-size:13px;font-weight:500;color:var(--g700);cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center">
        <?php echo esc_html( $q ); ?>
        <i class="fa-solid fa-chevron-down" style="color:var(--g400);font-size:11px;transition:transform .2s;flex-shrink:0"></i>
      </summary>
      <div style="padding:0 20px 14px;font-size:13px;color:var(--g500);line-height:1.7"><?php echo $a; ?></div>
    </details>
    <?php endforeach; ?>
  </div><!-- /faq list -->
</div><!-- /faq card -->

<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-body" style="padding:14px 20px;text-align:center;color:var(--g400);font-size:13px">
    Dibuat oleh <a href="https://hanifprm.my.id" target="_blank" style="color:var(--p);font-weight:600">Hanif Pramono</a> &mdash;
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=konektor-about' ) ); ?>" style="color:var(--p)">Tentang Plugin</a>
  </div>
</div>

</div><!-- .knk-wrap -->

<script>
var knkOpen = {};
function knkToggle(n) {
  var body = document.getElementById('knk-body-' + n);
  var chev = document.getElementById('knk-chev-' + n);
  var open = knkOpen[n];
  body.style.display      = open ? 'none' : 'block';
  chev.style.transform    = open ? 'rotate(0deg)' : 'rotate(180deg)';
  knkOpen[n] = !open;
}
// Auto-open first incomplete step
document.addEventListener('DOMContentLoaded', function() {
  <?php if ( ! $has_operator ) : ?>knkToggle(2);
  <?php elseif ( ! $has_campaign ) : ?>knkToggle(3);
  <?php else : ?>knkToggle(4);
  <?php endif; ?>
});
</script>
