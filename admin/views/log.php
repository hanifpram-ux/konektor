<?php if ( ! defined( 'ABSPATH' ) ) exit;

$platform = sanitize_text_field( $_GET['platform'] ?? '' );
$success  = $_GET['success'] ?? '';
$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page = 50;

$logs  = Konektor_Logger::get_logs( compact( 'platform', 'success', 'page', 'per_page' ) );
$total = Konektor_Logger::count( compact( 'platform', 'success' ) );
$pages = (int) ceil( $total / $per_page );

$debug_on = Konektor_Helper::get_setting( 'debug_log', '0' ) === '1';
?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left">
    <h1><i class="fa-solid fa-bug"></i> Log API</h1>
    <p class="knk-ph-desc">Debug request/response ke Meta CAPI, TikTok Events API, dan Snack/Kwai Event API.</p>
  </div>
  <div class="knk-ph-right">
    <?php if ( ! $debug_on ) : ?>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=konektor-settings' ) ); ?>" class="knk-btn-secondary">
      <i class="fa-solid fa-toggle-off"></i> Log Nonaktif — Aktifkan di Pengaturan
    </a>
    <?php else : ?>
    <form method="post" style="display:inline">
      <?php wp_nonce_field( 'knk_clear_log' ); ?>
      <button type="submit" name="knk_clear_log" class="knk-btn-danger" onclick="return confirm('Hapus semua log?')">
        <i class="fa-solid fa-trash"></i> Hapus Semua Log
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if ( ! $debug_on ) : ?>
<div class="notice notice-warning" style="margin:0 0 20px">
  <p><strong>Log API nonaktif.</strong> Aktifkan di <a href="<?php echo esc_url( admin_url( 'admin.php?page=konektor-settings' ) ); ?>">Pengaturan</a> → toggle Debug Log, lalu lakukan transaksi untuk merekam log baru.</p>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-body">
    <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="page" value="konektor-log">
      <div>
        <label class="knk-label">Platform</label>
        <select name="platform" class="knk-select" style="min-width:180px">
          <option value="">Semua Platform</option>
          <option value="Meta CAPI" <?php selected($platform,'Meta CAPI'); ?>>Meta CAPI</option>
          <option value="TikTok Events API" <?php selected($platform,'TikTok Events API'); ?>>TikTok Events API</option>
          <option value="Snack/Kwai Event API" <?php selected($platform,'Snack/Kwai Event API'); ?>>Snack/Kwai Event API</option>
        </select>
      </div>
      <div>
        <label class="knk-label">Status</label>
        <select name="success" class="knk-select">
          <option value="">Semua Status</option>
          <option value="1" <?php selected($success,'1'); ?>>✅ Sukses</option>
          <option value="0" <?php selected($success,'0'); ?>>❌ Gagal</option>
        </select>
      </div>
      <button type="submit" class="knk-btn-primary">Filter</button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=konektor-log' ) ); ?>" class="knk-btn-secondary">Reset</a>
    </form>
  </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:12px;margin-bottom:16px">
  <?php
  $all     = Konektor_Logger::count();
  $ok      = Konektor_Logger::count( [ 'success' => 1 ] );
  $fail    = Konektor_Logger::count( [ 'success' => 0 ] );
  ?>
  <div class="knk-card" style="flex:1;padding:16px;text-align:center">
    <div style="font-size:24px;font-weight:800"><?php echo number_format($all); ?></div>
    <div style="font-size:12px;color:#6b7280">Total Log</div>
  </div>
  <div class="knk-card" style="flex:1;padding:16px;text-align:center">
    <div style="font-size:24px;font-weight:800;color:#16a34a"><?php echo number_format($ok); ?></div>
    <div style="font-size:12px;color:#6b7280">Sukses</div>
  </div>
  <div class="knk-card" style="flex:1;padding:16px;text-align:center">
    <div style="font-size:24px;font-weight:800;color:#dc2626"><?php echo number_format($fail); ?></div>
    <div style="font-size:12px;color:#6b7280">Gagal</div>
  </div>
</div>

<!-- Table -->
<div class="knk-card">
  <div class="knk-card-body" style="padding:0">
    <?php if ( empty( $logs ) ) : ?>
    <div style="padding:40px;text-align:center;color:#6b7280">
      <i class="fa-solid fa-inbox" style="font-size:32px;margin-bottom:12px;display:block;opacity:.4"></i>
      Belum ada log. <?php echo $debug_on ? 'Lakukan submit form atau klik link kampanye untuk merekam log.' : 'Aktifkan Debug Log di Pengaturan.'; ?>
    </div>
    <?php else : ?>
    <table class="widefat knk-table" style="border:none">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Platform</th>
          <th>Event</th>
          <th>Status</th>
          <th>Response</th>
          <th>Waktu</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $logs as $log ) :
          $ok_row   = $log->success ? '#dcfce7' : '#fee2e2';
          $resp_obj = json_decode( $log->response, true );
          $resp_short = is_array( $resp_obj )
            ? ( $resp_obj['error']['message'] ?? $resp_obj['message'] ?? ( $log->success ? 'OK' : 'Error' ) )
            : substr( $log->response, 0, 120 );
        ?>
        <tr style="background:<?php echo esc_attr($ok_row); ?>">
          <td style="font-size:11px;color:#6b7280"><?php echo (int)$log->id; ?></td>
          <td><strong><?php echo esc_html( $log->platform ); ?></strong></td>
          <td><code><?php echo esc_html( $log->event_name ); ?></code></td>
          <td>
            <?php if ( $log->success ) : ?>
            <span style="color:#16a34a;font-weight:700">✅ <?php echo (int)$log->status_code; ?></span>
            <?php else : ?>
            <span style="color:#dc2626;font-weight:700">❌ <?php echo (int)$log->status_code ?: 'Error'; ?></span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;max-width:300px;word-break:break-all"><?php echo esc_html( $resp_short ); ?></td>
          <td style="font-size:11px;color:#6b7280;white-space:nowrap"><?php echo esc_html( $log->created_at ); ?></td>
          <td>
            <button type="button" class="knk-btn-xs" onclick="knkShowLog(<?php echo (int)$log->id; ?>)">Detail</button>
          </td>
        </tr>
        <!-- Hidden detail row -->
        <tr id="knk-log-<?php echo (int)$log->id; ?>" style="display:none">
          <td colspan="7" style="padding:0">
            <div style="padding:16px;background:#f8fafc;border-top:1px solid #e5e7eb">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                  <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px">ENDPOINT</p>
                  <code style="font-size:11px;word-break:break-all"><?php echo esc_html( $log->endpoint ); ?></code>
                  <p style="font-size:11px;font-weight:700;color:#6b7280;margin:12px 0 6px">PAYLOAD</p>
                  <pre style="font-size:11px;background:#1e293b;color:#e2e8f0;padding:12px;border-radius:6px;overflow:auto;max-height:300px"><?php
                    $p = json_decode( $log->payload, true );
                    echo esc_html( $p ? json_encode( $p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $log->payload );
                  ?></pre>
                </div>
                <div>
                  <p style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:6px">RESPONSE (HTTP <?php echo (int)$log->status_code; ?>)</p>
                  <pre style="font-size:11px;background:#1e293b;color:#e2e8f0;padding:12px;border-radius:6px;overflow:auto;max-height:300px"><?php
                    $r = json_decode( $log->response, true );
                    echo esc_html( $r ? json_encode( $r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $log->response );
                  ?></pre>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Pagination -->
<?php if ( $pages > 1 ) : ?>
<div style="margin-top:16px;display:flex;gap:8px;justify-content:center">
  <?php for ( $i = 1; $i <= $pages; $i++ ) :
    $url = add_query_arg( [ 'paged' => $i, 'platform' => $platform, 'success' => $success ], admin_url( 'admin.php?page=konektor-log' ) );
  ?>
  <a href="<?php echo esc_url($url); ?>" class="knk-btn-<?php echo $i === $page ? 'primary' : 'secondary'; ?>"><?php echo $i; ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

</div>

<script>
function knkShowLog(id) {
  var row = document.getElementById('knk-log-' + id);
  if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
