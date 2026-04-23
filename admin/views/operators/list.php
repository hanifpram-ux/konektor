<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap knk-wrap">

<div class="knk-ph">
  <div class="knk-ph-left"><h1><i class="fa-solid fa-users"></i> Operator / CS</h1></div>
  <div class="knk-ph-right">
    <a href="<?php echo admin_url('admin.php?page=konektor-operators&action=new'); ?>" class="knk-btn knk-btn-primary"><i class="fa-solid fa-plus"></i> Operator Baru</a>
  </div>
</div>

<?php if ( empty($operators) ) : ?>
<div class="knk-card"><div class="knk-card-body">
  <div class="knk-empty">
    <p>Belum ada operator.</p>
    <a href="<?php echo admin_url('admin.php?page=konektor-operators&action=new'); ?>" class="knk-btn knk-btn-primary"><i class="fa-solid fa-plus"></i> Tambah Operator Pertama</a>
  </div>
</div></div>
<?php else : ?>
<div class="knk-table-wrap">
<table class="knk-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Nama</th>
      <th>Tipe &amp; Kontak</th>
      <th>Jam Kerja</th>
      <th>Status</th>
      <th>Kampanye</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($operators as $op) :
    $camps = Konektor_Operator::get_campaigns_for_operator($op->id);
    $type_colors = ['whatsapp'=>'green','email'=>'blue','telegram'=>'blue','line'=>'green'];
    $type_color  = $type_colors[$op->type] ?? 'gray';
    $status_badge = $op->status === 'on'
      ? '<span class="knk-badge knk-badge-green">Aktif</span>'
      : '<span class="knk-badge knk-badge-gray">Nonaktif</span>';
  ?>
  <tr>
    <td style="color:var(--g400);font-size:12px"><?php echo $op->id; ?></td>
    <td>
      <div class="knk-table-name"><?php echo esc_html($op->name); ?></div>
      <?php if ($op->telegram_chat_id) : ?>
        <div style="font-size:11px;color:var(--g500);margin-top:2px"><i class="fa-brands fa-telegram"></i> <?php echo esc_html($op->telegram_chat_id); ?></div>
      <?php endif; ?>
    </td>
    <td>
      <span class="knk-badge knk-badge-<?php echo $type_color; ?>"><?php echo ucfirst($op->type); ?></span>
      <div style="font-size:11px;color:var(--g600);margin-top:4px;font-family:monospace"><?php echo esc_html($op->value); ?></div>
    </td>
    <td>
      <?php if ($op->work_hours_enabled) : ?>
        <span class="knk-badge knk-badge-blue"><i class="fa-regular fa-clock"></i> Terjadwal</span>
      <?php else : ?>
        <span style="font-size:12px;color:var(--g500)">24 Jam</span>
      <?php endif; ?>
    </td>
    <td><?php echo $status_badge; ?></td>
    <td>
      <?php if (empty($camps)) : ?>
        <span style="font-size:12px;color:var(--g400)">—</span>
      <?php else : ?>
        <div style="display:flex;flex-wrap:wrap;gap:4px">
        <?php foreach ($camps as $c) : ?>
          <a href="<?php echo admin_url('admin.php?page=konektor-campaigns&action=edit&id='.$c->id); ?>" class="knk-chip"><?php echo esc_html($c->name); ?></a>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </td>
    <td>
      <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
        <a href="<?php echo admin_url('admin.php?page=konektor-operators&action=edit&id='.$op->id); ?>" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="<?php echo admin_url('admin.php?page=konektor-leads&operator_id='.$op->id); ?>" class="knk-btn knk-btn-ghost knk-btn-sm"><i class="fa-solid fa-inbox"></i> Leads</a>
        <button class="knk-btn knk-btn-ghost knk-btn-sm knk-gen-token" data-id="<?php echo $op->id; ?>"><i class="fa-solid fa-link"></i> Panel CS</button>
        <button class="knk-btn knk-btn-danger knk-btn-sm knk-delete-operator" data-id="<?php echo $op->id; ?>"><i class="fa-solid fa-trash"></i></button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

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
