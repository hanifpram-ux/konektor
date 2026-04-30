<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-bullhorn"></i> Kampanye</h1></div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new'); ?>" class="knk-btn knk-btn-primary"><i class="fa-solid fa-plus"></i> Kampanye Baru</a>
  </div>
</div>

<?php if ( empty($campaigns) ) : ?>
<div class="knk-card"><div class="knk-card-body">
  <div class="knk-empty">
    <p>Belum ada kampanye.</p>
    <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=new'); ?>" class="knk-btn knk-btn-primary"><i class="fa-solid fa-plus"></i> Buat Kampanye Pertama</a>
  </div>
</div></div>
<?php else : ?>
<div class="knk-table-wrap">
<table class="knk-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama &amp; Produk</th>
      <th>Tipe</th>
      <th>Status</th>
      <th>URL Kampanye</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($campaigns as $c) :
    $url = Konektor_Campaign::get_url($c);
    $type_badge = $c->type === 'form'
      ? '<span class="knk-badge knk-badge-blue"><i class="fa-solid fa-clipboard-list"></i> Form</span>'
      : '<span class="knk-badge knk-badge-green"><i class="fa-solid fa-link"></i> Link</span>';
    $status_badge = $c->status === 'active'
      ? '<span class="knk-badge knk-badge-green">Aktif</span>'
      : '<span class="knk-badge knk-badge-gray">Nonaktif</span>';
  ?>
  <tr>
    <td style="color:var(--g400);font-size:12px"><?php echo $c->id; ?></td>
    <td>
      <div class="knk-table-name"><?php echo esc_html($c->name); ?></div>
      <?php if ($c->product_name) : ?>
        <div style="font-size:11px;color:var(--g500);margin-top:2px"><?php echo esc_html($c->product_name); ?></div>
      <?php endif; ?>
    </td>
    <td><?php echo $type_badge; ?></td>
    <td><?php echo $status_badge; ?></td>
    <td>
      <div style="display:flex;align-items:center;gap:6px;max-width:280px">
        <code style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px"><?php echo esc_html($url); ?></code>
        <button class="knk-btn knk-btn-ghost knk-btn-sm" onclick="navigator.clipboard.writeText('<?php echo esc_js($url); ?>');this.innerHTML='<i class=\'fa-solid fa-check\'></i>'"><i class="fa-regular fa-copy"></i></button>
        <a href="<?php echo esc_url($url); ?>" target="_blank" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
      </div>
    </td>
    <td>
      <div style="display:flex;gap:6px;align-items:center">
        <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=edit&id='.$c->id); ?>" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="<?php echo admin_url('admin.php?page=konektor-leads&campaign_id='.$c->id); ?>" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-inbox"></i> Leads</a>
        <button class="knk-btn knk-btn-danger knk-btn-sm knk-delete-campaign" data-id="<?php echo $c->id; ?>"><i class="fa-solid fa-trash"></i></button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

</div>
