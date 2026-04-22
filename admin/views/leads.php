<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-inbox"></i> Manajemen Lead</h1></div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin-ajax.php?action=konektor_admin_export_leads&nonce='.wp_create_nonce('konektor_admin_nonce').'&'.http_build_query($_GET)); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-download"></i> Export CSV</a>
  </div>
</div>

<!-- Filter Bar -->
<div class="knk-card" style="margin-bottom:16px">
  <div class="knk-card-body" style="padding:12px 16px">
    <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
      <input type="hidden" name="page" value="konektor-leads">
      <select name="campaign_id" class="knk-select" style="width:auto;min-width:160px">
        <option value="">Semua Kampanye</option>
        <?php foreach ($campaigns as $c) : ?>
        <option value="<?php echo $c->id; ?>" <?php selected($_GET['campaign_id'] ?? '', $c->id); ?>><?php echo esc_html($c->name); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="operator_id" class="knk-select" style="width:auto;min-width:160px">
        <option value="">Semua Operator</option>
        <?php foreach ($operators as $op) : ?>
        <option value="<?php echo $op->id; ?>" <?php selected($_GET['operator_id'] ?? '', $op->id); ?>><?php echo esc_html($op->name); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="knk-select" style="width:auto;min-width:130px">
        <option value="">Semua Status</option>
        <?php foreach (['new'=>'Baru','contacted'=>'Contacted','purchased'=>'Purchased','cancelled'=>'Cancelled','blocked'=>'Diblokir'] as $s=>$label) : ?>
        <option value="<?php echo $s; ?>" <?php selected($_GET['status'] ?? '', $s); ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="knk-btn knk-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="<?php echo admin_url('admin.php?page=konektor-leads'); ?>" class="knk-btn knk-btn-ghost">Reset</a>
    </form>
  </div>
</div>

<!-- Table -->
<?php if (empty($leads)) : ?>
<div class="knk-card"><div class="knk-card-body">
  <div class="knk-empty"><p>Tidak ada lead ditemukan.</p></div>
</div></div>
<?php else : ?>
<?php
$status_labels = ['new'=>'Baru','contacted'=>'Contacted','purchased'=>'Purchased','cancelled'=>'Cancelled','blocked'=>'Diblokir'];
$status_colors = ['new'=>'blue','contacted'=>'gray','purchased'=>'green','cancelled'=>'gray','blocked'=>'red'];
?>
<div class="knk-table-wrap">
<table class="knk-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama / Tipe</th>
      <th>No HP</th>
      <th>IP &amp; Cookie</th>
      <th>Kampanye</th>
      <th>Operator</th>
      <th>Status</th>
      <th>Double</th>
      <th>Tanggal</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($leads as $lead) :
    $l        = Konektor_Lead::decrypt_lead(clone $lead);
    $camp_obj = reset(array_filter($campaigns, fn($c) => $c->id == $lead->campaign_id));
    $op_obj   = reset(array_filter($operators,  fn($o) => $o->id == $lead->operator_id));
    $sc       = $status_colors[$lead->status] ?? 'gray';
    $sl       = $status_labels[$lead->status] ?? $lead->status;
  ?>
  <tr>
    <td style="color:var(--g400);font-size:12px"><?php echo $lead->id; ?></td>
    <td>
      <?php if ($l->name) : ?>
        <div style="font-weight:500;font-size:13px"><?php echo esc_html($l->name); ?></div>
        <?php if ($l->email) : ?>
          <div style="font-size:11px;color:var(--g500)"><?php echo esc_html($l->email); ?></div>
        <?php endif; ?>
      <?php else : ?>
        <span class="knk-badge knk-badge-green"><i class="fa-brands fa-whatsapp"></i> WA Click</span>
      <?php endif; ?>
    </td>
    <td style="font-family:monospace;font-size:12px"><?php echo $l->phone ? esc_html($l->phone) : '<span style="color:var(--g400)">—</span>'; ?></td>
    <td style="font-size:11px">
      <?php if ($lead->ip_address) : ?>
        <div style="font-family:monospace;color:var(--g700)"><?php echo esc_html($lead->ip_address); ?></div>
      <?php endif; ?>
      <?php if (!empty($lead->cookie_id)) : ?>
        <div style="color:var(--g500);margin-top:2px" title="Cookie ID"><?php echo esc_html(substr($lead->cookie_id, 0, 12)); ?>…</div>
      <?php endif; ?>
    </td>
    <td style="font-size:12px"><?php echo $camp_obj ? esc_html($camp_obj->name) : '<span style="color:var(--g400)">—</span>'; ?></td>
    <td style="font-size:12px"><?php echo $op_obj ? esc_html($op_obj->name) : '<span style="color:var(--g400)">—</span>'; ?></td>
    <td>
      <select class="knk-lead-status knk-select-sm" data-id="<?php echo $lead->id; ?>">
        <?php foreach ($status_labels as $sv=>$sl_opt) : ?>
        <option value="<?php echo $sv; ?>" <?php selected($lead->status, $sv); ?>><?php echo $sl_opt; ?></option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <?php echo $lead->is_double
        ? '<span class="knk-badge knk-badge-yellow">Double</span>'
        : '<span style="font-size:12px;color:var(--g400)">—</span>'; ?>
    </td>
    <td style="font-size:11px;color:var(--g500);white-space:nowrap"><?php echo esc_html(substr($lead->created_at, 0, 16)); ?></td>
    <td style="white-space:nowrap">
      <button class="knk-btn knk-btn-ghost knk-btn-sm knk-view-lead" data-id="<?php echo $lead->id; ?>"><i class="fa-solid fa-circle-info"></i> Detail</button>
      <?php if ($lead->status !== 'blocked') : ?>
      <button class="knk-btn knk-btn-danger knk-btn-sm knk-block-lead" data-id="<?php echo $lead->id; ?>" style="margin-left:4px"><i class="fa-solid fa-ban"></i> Blokir</button>
      <?php endif; ?>
      <button class="knk-btn knk-btn-ghost knk-btn-sm knk-delete-lead" data-id="<?php echo $lead->id; ?>" style="margin-left:4px;color:var(--err)"><i class="fa-solid fa-trash"></i></button>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($total > 50) : ?>
<div class="knk-pager" style="margin-top:16px">
  <?php
  $pages = ceil($total / 50);
  for ($p = 1; $p <= $pages; $p++) :
    $url = add_query_arg('paged', $p);
  ?>
  <a href="<?php echo esc_url($url); ?>" class="knk-btn knk-btn-sm <?php echo $p == $page ? 'knk-btn-primary' : 'knk-btn-ghost'; ?>"><?php echo $p; ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Lead Detail Modal -->
<div id="knk-lead-modal" class="knk-modal-bg knk-hidden">
  <div class="knk-modal" style="width:520px">
    <div class="knk-modal-head">
      <span class="knk-modal-title"><i class="fa-solid fa-circle-info"></i> Detail Lead</span>
      <button class="knk-modal-x" id="knk-lead-modal-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="knk-modal-body" id="knk-lead-detail-body">
      <div style="text-align:center;padding:20px;color:var(--g500)">Memuat...</div>
    </div>
  </div>
</div>

</div>
