<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel CS – Konektor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f0f2f5;color:#333}
.kp-login{display:flex;justify-content:center;align-items:center;min-height:100vh}
.kp-login-box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1);max-width:380px;width:100%;text-align:center}
.kp-login-box h2{margin-bottom:20px;color:#2563eb;display:flex;align-items:center;justify-content:center;gap:10px}
.kp-header{background:#2563eb;color:#fff;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.kp-header-left{display:flex;align-items:center;gap:10px}
.kp-header h1{font-size:18px;font-weight:700}
.kp-header-op{font-size:13px;opacity:.85}
.kp-wrap{max-width:1000px;margin:24px auto;padding:0 16px}
.kp-filters{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.kp-filters select,.kp-filters input[type="text"]{padding:7px 11px;border:1.5px solid #ddd;border-radius:6px;font-size:13px;outline:none}
.kp-filters select:focus,.kp-filters input:focus{border-color:#2563eb}
.kp-table-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}
.kp-table{width:100%;border-collapse:collapse;font-size:13px}
.kp-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#64748b;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.kp-table td{padding:11px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.kp-table tbody tr:last-child td{border-bottom:none}
.kp-table tbody tr:hover td{background:#f8fafc}
.kp-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap}
.kp-badge-new{background:#dbeafe;color:#1d4ed8}
.kp-badge-contacted{background:#fef9c3;color:#854d0e}
.kp-badge-purchased{background:#dcfce7;color:#166534}
.kp-badge-cancelled{background:#fee2e2;color:#991b1b}
.kp-badge-blocked{background:#f3f4f6;color:#6b7280}
.kp-badge-double{background:#fef3c7;color:#92400e}
.kp-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;font-family:inherit;transition:.15s}
.kp-btn-primary{background:#2563eb;color:#fff}.kp-btn-primary:hover{background:#1d4ed8}
.kp-btn-danger{background:#ef4444;color:#fff}.kp-btn-danger:hover{background:#dc2626}
.kp-btn-ghost{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0}.kp-btn-ghost:hover{background:#e2e8f0}
.kp-btn-sm{padding:4px 9px;font-size:12px;border-radius:5px}
select.kp-status-sel{border:1.5px solid #e2e8f0;border-radius:5px;padding:4px 22px 4px 8px;font-size:12px;color:#1e293b;background:#fff;cursor:pointer;outline:none}
select.kp-status-sel:focus{border-color:#2563eb}
.kp-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;justify-content:center;align-items:center}
.kp-modal-bg.active{display:flex}
.kp-modal{background:#fff;border-radius:12px;padding:28px;max-width:480px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.15)}
.kp-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.kp-modal-head h3{margin:0;font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
.kp-modal-x{background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0}
.kp-modal-x:hover{color:#374151}
.kp-modal textarea,.kp-modal input[type="text"]{width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:8px 12px;font-size:14px;margin-bottom:12px;outline:none;font-family:inherit}
.kp-modal textarea:focus,.kp-modal input:focus{border-color:#2563eb}
.kp-empty{text-align:center;padding:48px;color:#9ca3af;font-size:13px}
.kp-empty i{font-size:32px;margin-bottom:12px;display:block;opacity:.4}
.kp-pagination{display:flex;gap:6px;margin-top:16px;justify-content:center;flex-wrap:wrap}
.kp-pagination button{padding:6px 14px;border:1.5px solid #e2e8f0;background:#fff;border-radius:6px;cursor:pointer;font-size:13px;transition:.15s}
.kp-pagination button.active{background:#2563eb;color:#fff;border-color:#2563eb}
.kp-pagination button:hover:not(.active){background:#f1f5f9}
.kp-phone-link{color:#2563eb;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
.kp-phone-link:hover{text-decoration:underline}
.kp-stat-row{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.kp-stat{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;flex:1;min-width:120px;text-align:center;border-top:3px solid #2563eb}
.kp-stat-n{display:block;font-size:24px;font-weight:800;color:#0f172a}
.kp-stat-l{display:block;font-size:11px;color:#64748b;margin-top:3px}
.kp-loading{text-align:center;padding:32px;color:#64748b}
.kp-loading i{font-size:24px;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.kp-camp-filter{padding:7px 11px;border:1.5px solid #ddd;border-radius:6px;font-size:13px;min-width:160px}
.kp-btn-followup{background:#f0fdf4;color:#166534;border:1.5px solid #86efac}.kp-btn-followup:hover{background:#dcfce7}
.kp-btn-followup.done{background:#f8fafc;color:#94a3b8;border-color:#e2e8f0}
.kp-followup-badge{display:inline-flex;align-items:center;gap:3px;font-size:10px;color:#16a34a;background:#dcfce7;border-radius:20px;padding:2px 7px;margin-top:3px;white-space:nowrap}
</style>
</head>
<body>
<?php
if ( ! $operator ) :
?>
<div class="kp-login">
    <div class="kp-login-box">
        <h2><i class="fa-solid fa-lock" style="color:#2563eb"></i> Panel CS</h2>
        <p style="color:#ef4444;margin-bottom:16px">Token tidak valid atau sudah tidak berlaku. Hubungi admin untuk mendapatkan link baru.</p>
        <p style="font-size:13px;color:#6b7280">Powered by Konektor</p>
    </div>
</div>
<?php else: ?>
<div class="kp-header">
    <div class="kp-header-left">
        <i class="fa-solid fa-link" style="font-size:18px;opacity:.9"></i>
        <div>
            <h1>Panel CS</h1>
            <div class="kp-header-op"><?php echo esc_html( $operator->name ); ?></div>
        </div>
    </div>
    <span style="font-size:12px;opacity:.7">Konektor</span>
</div>
<div class="kp-wrap">

    <div class="kp-stat-row" id="kp-stats" style="display:none">
        <div class="kp-stat" style="border-top-color:#2563eb"><span class="kp-stat-n" id="stat-total">–</span><span class="kp-stat-l">Total</span></div>
        <div class="kp-stat" style="border-top-color:#3b82f6"><span class="kp-stat-n" id="stat-new">–</span><span class="kp-stat-l">Baru</span></div>
        <div class="kp-stat" style="border-top-color:#d97706"><span class="kp-stat-n" id="stat-contacted">–</span><span class="kp-stat-l">Dihubungi</span></div>
        <div class="kp-stat" style="border-top-color:#16a34a"><span class="kp-stat-n" id="stat-purchased">–</span><span class="kp-stat-l">Beli</span></div>
    </div>

    <div class="kp-filters">
        <select id="filter-campaign" class="kp-camp-filter">
            <option value="">Semua Kampanye</option>
        </select>
        <select id="filter-status">
            <option value="">Semua Status</option>
            <option value="new">Baru</option>
            <option value="contacted">Dihubungi</option>
            <option value="purchased">Beli</option>
            <option value="cancelled">Batal</option>
            <option value="blocked">Diblokir</option>
        </select>
        <input type="text" id="filter-search" placeholder="Cari nama / HP...">
        <button class="kp-btn kp-btn-primary" id="btn-load"><i class="fa-solid fa-rotate"></i> Muat Ulang</button>
    </div>

    <div class="kp-table-wrap">
        <table class="kp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama / Tipe</th>
                    <th>HP</th>
                    <th>Pesan</th>
                    <th>Kampanye</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Follow-Up</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="lead-tbody">
                <tr><td colspan="9" class="kp-loading"><i class="fa-solid fa-spinner"></i></td></tr>
            </tbody>
        </table>
    </div>

    <div class="kp-pagination" id="kp-pagination"></div>
</div>

<!-- Block Modal -->
<div class="kp-modal-bg" id="block-modal">
    <div class="kp-modal">
        <div class="kp-modal-head">
            <h3><i class="fa-solid fa-ban" style="color:#ef4444"></i> Blokir Customer</h3>
            <button class="kp-modal-x" id="btn-cancel-block"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <input type="hidden" id="block-lead-id">
        <textarea id="block-reason" rows="3" placeholder="Alasan pemblokiran..."></textarea>
        <div style="display:flex;gap:10px">
            <button class="kp-btn kp-btn-danger" id="btn-confirm-block"><i class="fa-solid fa-ban"></i> Blokir Sekarang</button>
            <button class="kp-btn kp-btn-ghost" id="btn-cancel-block2"><i class="fa-solid fa-xmark"></i> Batal</button>
        </div>
    </div>
</div>

<script>
(function(){
var PANEL_URL = '<?php echo esc_js( home_url( '/' . Konektor_Helper::get_setting( 'base_slug', 'konektor' ) . '/cs-panel/' ) ); ?>';
var TOKEN = '<?php echo esc_js( sanitize_text_field( $_GET['token'] ?? '' ) ); ?>';
var currentPage = 1;
var totalPages  = 1;
var statsLoaded = false;

function post(act, extra, cb) {
    var fd = new FormData();
    fd.append('act', act);
    fd.append('token', TOKEN); // unused server-side (already in GET), but safe
    if (extra) Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); });
    fetch(PANEL_URL + '?token=' + encodeURIComponent(TOKEN), {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(cb)
        .catch(function(){ cb({success:false}); });
}

var statusLabels = {new:'Baru',contacted:'Dihubungi',purchased:'Beli',cancelled:'Batal',blocked:'Diblokir'};
var statusClasses = {new:'kp-badge-new',contacted:'kp-badge-contacted',purchased:'kp-badge-purchased',cancelled:'kp-badge-cancelled',blocked:'kp-badge-blocked'};

function loadCampaigns() {
    post('get_campaigns', {}, function(r) {
        if (!r.success) return;
        var sel = document.getElementById('filter-campaign');
        (r.campaigns || []).forEach(function(c) {
            var o = document.createElement('option');
            o.value = c.id; o.textContent = c.name;
            sel.appendChild(o);
        });
    });
}

function loadLeads(page) {
    currentPage = page || 1;
    var status     = document.getElementById('filter-status').value;
    var campaignId = document.getElementById('filter-campaign').value;
    var search     = document.getElementById('filter-search').value;

    document.getElementById('lead-tbody').innerHTML = '<tr><td colspan="9" class="kp-loading"><i class="fa-solid fa-spinner"></i></td></tr>';

    post('get_leads', {page: currentPage, status: status, campaign_id: campaignId}, function(data) {
        if (!data.success) {
            document.getElementById('lead-tbody').innerHTML = '<tr><td colspan="9" class="kp-empty"><i class="fa-solid fa-circle-exclamation"></i><br>Gagal memuat data.</td></tr>';
            return;
        }
        totalPages = data.pages || 1;
        if (!statsLoaded) { loadStats(); statsLoaded = true; }
        renderLeads(data.leads || [], search);
        renderPagination(data.total || 0);
    });
}

function loadStats() {
    post('get_leads', {per_page: 0, page: 1}, function(d) {
        if (!d.success) return;
        document.getElementById('kp-stats').style.display = 'flex';
        document.getElementById('stat-total').textContent = d.total || 0;
    });
    ['new','contacted','purchased'].forEach(function(s) {
        post('get_leads', {status: s, page: 1}, function(d) {
            var el = document.getElementById('stat-' + s);
            if (el && d.success) el.textContent = d.total || 0;
        });
    });
}

function renderLeads(leads, search) {
    var tbody = document.getElementById('lead-tbody');
    if (search) {
        search = search.toLowerCase();
        leads = leads.filter(function(l){ return (l.name||'').toLowerCase().includes(search)||(l.phone||'').includes(search); });
    }
    if (!leads.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="kp-empty"><i class="fa-solid fa-inbox"></i><br>Tidak ada lead.</td></tr>';
        return;
    }
    tbody.innerHTML = leads.map(function(l) {
        var badge = '<span class="kp-badge ' + (statusClasses[l.status]||'kp-badge-blocked') + '">' + (statusLabels[l.status]||l.status) + '</span>';
        if (l.is_double) badge += ' <span class="kp-badge kp-badge-double">Double</span>';

        var sel = '<select class="kp-status-sel" onchange="updateStatus(' + l.id + ',this.value)">';
        ['new','contacted','purchased','cancelled'].forEach(function(s){
            sel += '<option value="' + s + '"' + (l.status===s?' selected':'') + '>' + statusLabels[s] + '</option>';
        });
        sel += '</select>';

        var phone = l.phone
            ? '<a href="https://wa.me/'+formatPhone(l.phone)+'" target="_blank" class="kp-phone-link"><i class="fa-brands fa-whatsapp"></i>'+esc(l.phone)+'</a>'
            : '—';

        var name = l.name
            ? esc(l.name)
            : '<span style="font-size:11px;color:#94a3b8"><i class="fa-solid fa-link"></i> WA Click</span>';

        var msg = l.message || '';
        if (msg.length > 60) msg = msg.slice(0,60) + '…';

        // Follow-up column — only show if lead has phone number
        var fuDone    = !!l.followed_up_at;
        var fuDate    = fuDone ? esc((l.followed_up_at||'').slice(0,16)) : '';
        var fuBtnCls  = 'kp-btn kp-btn-sm kp-btn-followup' + (fuDone ? ' done' : '');
        var fuBtnTxt  = fuDone
            ? '<i class="fa-solid fa-check"></i> Follow-Up'
            : '<i class="fa-solid fa-paper-plane"></i> Follow-Up';
        var fuBadge   = fuDone ? '<div class="kp-followup-badge"><i class="fa-solid fa-check"></i> ' + fuDate + '</div>' : '';
        var fuBtn     = l.phone
            ? '<button class="' + fuBtnCls + '" onclick="doFollowUp(' + l.id + ',this)">' + fuBtnTxt + '</button>' + fuBadge
            : '<span style="font-size:11px;color:#cbd5e1">—</span>';

        return '<tr>'
            + '<td style="color:#94a3b8;font-size:12px">' + l.id + '</td>'
            + '<td>' + name + '</td>'
            + '<td>' + phone + '</td>'
            + '<td style="font-size:12px;color:#475569">' + esc(msg) + '</td>'
            + '<td><span style="font-size:12px;color:#475569">' + esc(l.campaign||'') + '</span></td>'
            + '<td>' + badge + '<br><div style="margin-top:5px">' + sel + '</div></td>'
            + '<td style="font-size:11px;color:#94a3b8;white-space:nowrap">' + esc((l.date||'').slice(0,16)) + '</td>'
            + '<td style="white-space:nowrap">' + fuBtn + '</td>'
            + '<td><button class="kp-btn kp-btn-danger kp-btn-sm" onclick="openBlock(' + l.id + ')"><i class="fa-solid fa-ban"></i></button></td>'
            + '</tr>';
    }).join('');
}

function renderPagination(total) {
    var pag = document.getElementById('kp-pagination');
    if (totalPages <= 1) { pag.innerHTML = ''; return; }
    var h = '';
    for (var i = 1; i <= totalPages; i++) {
        h += '<button onclick="loadLeads(' + i + ')" class="' + (i===currentPage?'active':'') + '">' + i + '</button>';
    }
    pag.innerHTML = h;
}

function formatPhone(p) {
    if (!p) return '';
    p = p.replace(/\D/g,'');
    if (p.startsWith('0')) p = '62' + p.slice(1);
    return p;
}

function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s||''));
    return d.innerHTML;
}

window.updateStatus = function(leadId, status) {
    post('update_status', {lead_id: leadId, status: status, note: ''}, function(){});
};

window.doFollowUp = function(leadId, btn) {
    btn.disabled = true;
    post('follow_up', {lead_id: leadId}, function(r) {
        if (r.success) {
            // Mark button as done
            btn.classList.add('done');
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Follow-Up';
            // Append timestamp badge
            var now = new Date();
            var ts = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0')
                   + ' '+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
            var badge = document.createElement('div');
            badge.className = 'kp-followup-badge';
            badge.innerHTML = '<i class="fa-solid fa-check"></i> ' + ts;
            btn.parentNode.appendChild(badge);
            // Open WA link if available
            if (r.url) window.open(r.url, '_blank');
        }
        btn.disabled = false;
    });
};

window.openBlock = function(leadId) {
    document.getElementById('block-lead-id').value = leadId;
    document.getElementById('block-reason').value = '';
    document.getElementById('block-modal').classList.add('active');
};

document.getElementById('btn-confirm-block').addEventListener('click', function() {
    var id     = document.getElementById('block-lead-id').value;
    var reason = document.getElementById('block-reason').value;
    post('block_lead', {lead_id: id, reason: reason}, function() {
        document.getElementById('block-modal').classList.remove('active');
        loadLeads(currentPage);
    });
});

function closeBlock() { document.getElementById('block-modal').classList.remove('active'); }
document.getElementById('btn-cancel-block').addEventListener('click', closeBlock);
document.getElementById('btn-cancel-block2').addEventListener('click', closeBlock);

document.getElementById('btn-load').addEventListener('click', function(){ loadLeads(1); });
document.getElementById('filter-status').addEventListener('change', function(){ loadLeads(1); });
document.getElementById('filter-campaign').addEventListener('change', function(){ loadLeads(1); });

loadCampaigns();
loadLeads(1);
})();
</script>
<?php endif; ?>
</body>
</html>
