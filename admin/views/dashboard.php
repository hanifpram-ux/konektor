<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left">
    <h1><i class="fa-solid fa-link"></i> Konektor</h1>
    <span style="font-size:12px;color:var(--g500)">by <a href="https://hanifprm.my.id" target="_blank" style="color:var(--p)">Hanif Pramono</a></span>
  </div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new'); ?>" class="knk-btn knk-btn-primary"><i class="fa-solid fa-plus"></i> Kampanye Baru</a>
    <a href="<?php echo admin_url('admin.php?page=konektor-operators&action=new'); ?>"  class="knk-btn knk-btn-ghost"><i class="fa-solid fa-plus"></i> Operator</a>
  </div>
</div>

<div class="knk-stats">
  <div class="knk-stat knk-stat-blue">
    <span class="knk-stat-n"><?php echo number_format($summary['total_leads']); ?></span>
    <span class="knk-stat-l">Total Leads</span>
  </div>
  <div class="knk-stat knk-stat-blue">
    <span class="knk-stat-n"><?php echo number_format($summary['new_leads']); ?></span>
    <span class="knk-stat-l">Lead Baru</span>
  </div>
  <div class="knk-stat knk-stat-green">
    <span class="knk-stat-n"><?php echo number_format($summary['purchased']); ?></span>
    <span class="knk-stat-l">Purchased</span>
  </div>
  <div class="knk-stat knk-stat-yellow">
    <span class="knk-stat-n"><?php echo number_format($summary['double_leads']); ?></span>
    <span class="knk-stat-l">Double Lead</span>
  </div>
  <div class="knk-stat knk-stat-red">
    <span class="knk-stat-n"><?php echo number_format($summary['blocked']); ?></span>
    <span class="knk-stat-l">Diblokir</span>
  </div>
  <div class="knk-stat knk-stat-gray">
    <span class="knk-stat-n"><?php echo number_format($summary['page_loads']); ?></span>
    <span class="knk-stat-l">Page Views</span>
  </div>
  <div class="knk-stat knk-stat-gray">
    <span class="knk-stat-n"><?php echo number_format($summary['form_submits']); ?></span>
    <span class="knk-stat-l">Form Submit</span>
  </div>
</div>

<div class="knk-quick">
  <a href="<?php echo admin_url('admin.php?page=konektor-leads'); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-inbox"></i> Lihat Semua Lead</a>
  <a href="<?php echo admin_url('admin.php?page=konektor-analytics'); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-chart-bar"></i> Analitik</a>
  <a href="<?php echo admin_url('admin.php?page=konektor-settings'); ?>" class="knk-btn knk-btn-ghost"><i class="fa-solid fa-gear"></i> Pengaturan</a>
</div>

</div>
