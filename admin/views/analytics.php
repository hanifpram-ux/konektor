<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-chart-bar"></i> Analitik</h1></div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin-ajax.php?action=konektor_admin_export_leads&nonce='.wp_create_nonce('konektor_admin_nonce')); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-download"></i> Export CSV</a>
  </div>
</div>

<!-- Stat Cards -->
<div class="knk-stats">
  <div class="knk-stat knk-stat-blue">
    <span class="knk-stat-n"><?php echo number_format($summary['total_leads']); ?></span>
    <span class="knk-stat-l">Total Leads</span>
  </div>
  <div class="knk-stat knk-stat-green">
    <span class="knk-stat-n"><?php echo number_format($summary['purchased']); ?></span>
    <span class="knk-stat-l">Purchased</span>
  </div>
  <div class="knk-stat knk-stat-yellow">
    <span class="knk-stat-n"><?php echo number_format($summary['double_leads']); ?></span>
    <span class="knk-stat-l">Double Lead</span>
  </div>
  <div class="knk-stat knk-stat-gray">
    <span class="knk-stat-n"><?php echo number_format($summary['form_submits']); ?></span>
    <span class="knk-stat-l">Form Submit</span>
  </div>
  <div class="knk-stat knk-stat-gray">
    <span class="knk-stat-n"><?php echo number_format($summary['page_loads']); ?></span>
    <span class="knk-stat-l">Page Views</span>
  </div>
  <?php
  $cvr_global = ($summary['page_loads'] > 0)
    ? round(($summary['form_submits'] / $summary['page_loads']) * 100, 1) : 0;
  ?>
  <div class="knk-stat knk-stat-blue">
    <span class="knk-stat-n"><?php echo $cvr_global; ?>%</span>
    <span class="knk-stat-l">Konversi Global</span>
  </div>
</div>

<!-- Chart -->
<div class="knk-card" style="margin-top:4px">
  <div class="knk-card-head"><span class="knk-card-title"><i class="fa-solid fa-chart-line"></i> Lead per Hari (30 hari terakhir)</span></div>
  <div class="knk-card-body" style="padding:16px 20px">
    <div style="position:relative;height:200px">
      <canvas id="konektor-daily-chart"></canvas>
    </div>
  </div>
</div>

<script>
var konektorDailyData = <?php echo wp_json_encode(array_map(fn($r) => ['date'=>$r->date,'leads'=>(int)$r->leads], $daily)); ?>;
</script>

<!-- Per Kampanye -->
<div class="knk-card" style="margin-top:16px">
  <div class="knk-card-head"><span class="knk-card-title">Per Kampanye</span></div>
  <?php if (empty($per_campaign)) : ?>
  <div class="knk-card-body"><div class="knk-empty"><p>Belum ada data kampanye.</p></div></div>
  <?php else : ?>
  <div class="knk-table-wrap" style="border-radius:0 0 12px 12px">
  <table class="knk-table">
    <thead>
      <tr>
        <th>Kampanye</th>
        <th>Tipe</th>
        <th>Total Lead</th>
        <th>Purchased</th>
        <th>Double</th>
        <th>Page Views</th>
        <th>Submit</th>
        <th>Konversi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($per_campaign as $row) :
      $cvr = $row->page_loads > 0 ? round(($row->form_submits / $row->page_loads) * 100, 1) : 0;
    ?>
    <tr>
      <td>
        <a href="<?php echo admin_url('admin.php?page=konektor-leads&campaign_id='.$row->id); ?>" style="font-weight:500;color:var(--p)"><?php echo esc_html($row->name); ?></a>
      </td>
      <td>
        <?php echo $row->type === 'form'
          ? '<span class="knk-badge knk-badge-blue">Form</span>'
          : '<span class="knk-badge knk-badge-green">Link</span>'; ?>
      </td>
      <td><?php echo number_format($row->total_leads); ?></td>
      <td><span style="color:var(--ok);font-weight:500"><?php echo number_format($row->purchased); ?></span></td>
      <td><span style="color:var(--warn)"><?php echo number_format($row->doubles); ?></span></td>
      <td style="color:var(--g600)"><?php echo number_format($row->page_loads); ?></td>
      <td style="color:var(--g600)"><?php echo number_format($row->form_submits); ?></td>
      <td>
        <span style="font-weight:600;color:<?php echo $cvr >= 5 ? 'var(--ok)' : ($cvr >= 2 ? 'var(--p)' : 'var(--err)'); ?>"><?php echo $cvr; ?>%</span>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- Per Operator -->
<div class="knk-card" style="margin-top:16px">
  <div class="knk-card-head"><span class="knk-card-title">Per Operator</span></div>
  <?php if (empty($per_operator)) : ?>
  <div class="knk-card-body"><div class="knk-empty"><p>Belum ada data operator.</p></div></div>
  <?php else : ?>
  <div class="knk-table-wrap" style="border-radius:0 0 12px 12px">
  <table class="knk-table">
    <thead>
      <tr>
        <th>Operator</th>
        <th>Tipe</th>
        <th>Total Lead</th>
        <th>Purchased</th>
        <th>Pending</th>
        <th>Konversi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($per_operator as $row) :
      $op_cvr = $row->total_leads > 0 ? round(($row->purchased / $row->total_leads) * 100, 1) : 0;
    ?>
    <tr>
      <td>
        <a href="<?php echo admin_url('admin.php?page=konektor-leads&operator_id='.$row->id); ?>" style="font-weight:500;color:var(--p)"><?php echo esc_html($row->name); ?></a>
      </td>
      <td><span class="knk-badge knk-badge-gray"><?php echo esc_html($row->type); ?></span></td>
      <td><?php echo number_format($row->total_leads); ?></td>
      <td><span style="color:var(--ok);font-weight:500"><?php echo number_format($row->purchased); ?></span></td>
      <td style="color:var(--g600)"><?php echo number_format($row->pending); ?></td>
      <td>
        <span style="font-weight:600;color:<?php echo $op_cvr >= 30 ? 'var(--ok)' : ($op_cvr >= 15 ? 'var(--p)' : 'var(--g500)'); ?>"><?php echo $op_cvr; ?>%</span>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

</div>
