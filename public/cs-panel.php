<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="theme-color" content="#1e293b">
<title>Panel CS – Konektor</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --blue:#3b82f6;--green:#10b981;--amber:#f59e0b;--red:#ef4444;
  --slate:#0f172a;--muted:#64748b;--border:#e2e8f0;
  --bg:#f8fafc;--white:#fff;--radius:16px;--shadow:0 2px 8px rgba(0,0,0,.08);
}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--slate);-webkit-font-smoothing:antialiased;min-height:100dvh}

/* ── LOGIN ──────────────────────────── */
.login{display:flex;align-items:center;justify-content:center;min-height:100dvh;padding:20px}
.login-box{background:var(--white);border-radius:20px;padding:36px 28px;max-width:360px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.1)}
.login-icon{font-size:40px;margin-bottom:14px}
.login-box h2{font-size:20px;font-weight:800;margin-bottom:8px}
.login-box p{font-size:13px;color:var(--muted)}

/* ── HEADER ─────────────────────────── */
.hdr{background:linear-gradient(135deg,#1e293b,#0f172a);color:#fff;padding:13px 16px;position:sticky;top:0;z-index:30}
.hdr-in{display:flex;align-items:center;gap:10px}
.hdr-av{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;flex-shrink:0}
.hdr-name{font-size:15px;font-weight:700;line-height:1.2}
.hdr-sub{font-size:11px;opacity:.5;margin-top:1px}
.hdr-badge{background:var(--blue);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-left:auto}

/* ── STATS ──────────────────────────── */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:14px 14px 0}
.stat{background:var(--white);border-radius:14px;padding:13px 8px;text-align:center;box-shadow:var(--shadow);position:relative;overflow:hidden}
.stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.stat.s1::before{background:var(--blue)}
.stat.s2::before{background:var(--amber)}
.stat.s3::before{background:var(--green)}
.stat-n{font-size:26px;font-weight:900;line-height:1}
.stat-l{font-size:10px;color:var(--muted);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.4px}

/* ── FILTERS ────────────────────────── */
.filters{padding:10px 14px 4px;display:flex;gap:7px;flex-wrap:wrap}
.fi-search{
  flex:1;min-width:0;padding:10px 12px 10px 36px;border:1.5px solid var(--border);
  border-radius:10px;font-size:14px;font-family:inherit;background:var(--white)
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 11px center;
  outline:none;
}
.fi-search:focus{border-color:var(--blue)}
.fi-sel{padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-family:inherit;background:var(--white);outline:none;color:var(--slate)}
.fi-sel:focus{border-color:var(--blue)}

/* ── TABS ───────────────────────────── */
.tabs-wrap{padding:8px 14px 4px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.tabs-wrap::-webkit-scrollbar{display:none}
.tabs{display:flex;gap:6px;width:max-content}
.tab{padding:7px 14px;border-radius:20px;font-size:13px;font-weight:600;white-space:nowrap;border:1.5px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;-webkit-tap-highlight-color:transparent;transition:all .15s}
.tab.active{background:var(--blue);color:#fff;border-color:var(--blue)}
.tab-cnt{font-size:11px;background:rgba(0,0,0,.1);padding:1px 7px;border-radius:10px;font-weight:700;margin-left:4px}
.tab.active .tab-cnt{background:rgba(255,255,255,.25)}

/* ── LIST ───────────────────────────── */
.list{padding:8px 14px 100px}

/* ── LOADING / EMPTY ────────────────── */
.loading{text-align:center;padding:50px 20px;color:var(--muted)}
.loading-spin{width:32px;height:32px;border:3px solid var(--border);border-top-color:var(--blue);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 12px}
@keyframes spin{to{transform:rotate(360deg)}}
.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-icon{font-size:52px;margin-bottom:12px;display:block}

/* ── LEAD CARD ──────────────────────── */
.lcard{background:var(--white);border-radius:var(--radius);margin-bottom:10px;box-shadow:var(--shadow);overflow:hidden;border:1px solid var(--border)}
.lcard.blocked{border-left:4px solid var(--red)}
.lcard.purchased{border-left:4px solid var(--green)}
.lcard-head{padding:14px 14px 10px;display:flex;align-items:flex-start;gap:11px}
.lav{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:19px;font-weight:800;color:#fff;flex-shrink:0;position:relative}
.lav-dot{position:absolute;bottom:1px;right:1px;width:12px;height:12px;border-radius:50%;border:2px solid var(--white)}
.linfo{flex:1;min-width:0}
.lname{font-size:16px;font-weight:800;line-height:1.25;word-break:break-word}
.lphone{font-size:14px;font-weight:600;color:var(--blue);margin-top:4px;display:inline-flex;align-items:center;gap:5px;text-decoration:none}
.lcamp{font-size:11px;color:var(--muted);margin-top:3px}
.fu-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;margin-top:6px}
.fu-done{background:#dcfce7;color:#166534}
.fu-pending{background:#fef3c7;color:#92400e}
.spill{padding:5px 11px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;border:none;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;gap:4px;-webkit-tap-highlight-color:transparent}

/* ── DETAIL ─────────────────────────── */
.ldetail{padding:0 14px 10px;display:flex;flex-direction:column;gap:5px;border-top:1px solid #f8fafc}
.drow{font-size:13px;display:flex;gap:8px;align-items:flex-start}
.dkey{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.3px;min-width:52px;flex-shrink:0;padding-top:1px}
.dval{color:var(--slate);word-break:break-word;line-height:1.5}

/* ── ACTION BAR ─────────────────────── */
.lactions{display:flex;border-top:1px solid var(--border)}
.abtn{flex:1;padding:13px 4px;border:none;background:none;font-size:10px;font-weight:700;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px;color:var(--muted);border-right:1px solid var(--border);text-decoration:none;-webkit-tap-highlight-color:transparent;transition:background .12s}
.abtn:last-child{border-right:none}
.abtn:active{background:var(--bg)}
.abtn-icon{font-size:20px;line-height:1}
.abtn.wa{color:var(--green)}.abtn.fu{color:var(--blue)}.abtn.fu.done{color:var(--muted);opacity:.6}
.abtn.stat{color:var(--amber)}.abtn.blk{color:var(--red)}.abtn.unblk{color:var(--green)}
.lfoot{padding:6px 14px 10px;font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:4px}

/* ── PAGINATION ─────────────────────── */
.pages{display:flex;justify-content:center;gap:6px;margin-top:4px;flex-wrap:wrap}
.pbtn{min-width:40px;padding:9px 12px;border-radius:10px;border:1.5px solid var(--border);background:var(--white);font-size:14px;font-weight:600;color:var(--slate);text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;-webkit-tap-highlight-color:transparent}
.pbtn.active{background:var(--blue);color:#fff;border-color:var(--blue)}
.page-info{text-align:center;font-size:12px;color:var(--muted);margin-bottom:10px}

/* ── BOTTOM SHEET ───────────────────── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:flex-end;justify-content:center;z-index:100;backdrop-filter:blur(2px)}
.overlay.open{display:flex}
.sheet{background:var(--white);border-radius:22px 22px 0 0;width:100%;max-width:540px;padding-bottom:max(env(safe-area-inset-bottom,0px),16px);max-height:88dvh;overflow-y:auto;animation:su .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes su{from{transform:translateY(50px);opacity:0}to{transform:none;opacity:1}}
.shdrag{width:44px;height:4px;background:#e2e8f0;border-radius:4px;margin:10px auto 0}
.shhead{padding:16px 20px 12px;border-bottom:1px solid #f1f5f9}
.shhead h3{font-size:17px;font-weight:800}
.shhead p{font-size:12px;color:var(--muted);margin-top:3px}
.shbody{padding:14px 16px}
.shopt{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1.5px solid var(--border);border-radius:13px;cursor:pointer;margin-bottom:8px;transition:border-color .15s,background .15s;-webkit-tap-highlight-color:transparent}
.shopt.sel{border-color:var(--blue);background:#eff6ff}
.shopt-icon{font-size:22px;width:34px;text-align:center;flex-shrink:0}
.shopt-lbl{font-size:14px;font-weight:700}
.shopt-sub{font-size:11px;color:var(--muted);margin-top:1px}
.shinput{width:100%;padding:13px 14px;border:1.5px solid var(--border);border-radius:12px;font-size:14px;font-family:inherit;margin-top:10px;outline:none}
.shinput:focus{border-color:var(--blue)}
.shfoot{display:flex;gap:8px;padding:12px 16px 4px;border-top:1px solid var(--border)}
.sbtn{flex:1;padding:14px;border-radius:13px;font-size:15px;font-weight:700;cursor:pointer;border:none}
.sbtn-cancel{background:#f1f5f9;color:var(--slate)}
.sbtn-save{background:var(--blue);color:#fff}
.sbtn-red{background:var(--red);color:#fff}
</style>
</head>
<body>

<?php if ( ! $operator ) : ?>
<div class="login">
  <div class="login-box">
    <div class="login-icon">🔒</div>
    <h2>Akses Ditolak</h2>
    <p>Token tidak valid atau sudah tidak berlaku.<br>Hubungi admin untuk mendapatkan link baru.</p>
  </div>
</div>
<?php else : ?>

<!-- Header -->
<div class="hdr">
  <div class="hdr-in">
    <div class="hdr-av"><?php echo strtoupper( substr( $operator->name ?? '?', 0, 1 ) ); ?></div>
    <div>
      <div class="hdr-name"><?php echo esc_html( $operator->name ); ?></div>
      <div class="hdr-sub">Panel CS · Konektor</div>
    </div>
    <span class="hdr-badge" id="hdr-total">– Lead</span>
  </div>
</div>

<!-- Stats -->
<div class="stats">
  <div class="stat s1"><div class="stat-n" id="sn-new" style="color:var(--blue)">–</div><div class="stat-l">Baru</div></div>
  <div class="stat s2"><div class="stat-n" id="sn-cont" style="color:var(--amber)">–</div><div class="stat-l">Dihubungi</div></div>
  <div class="stat s3"><div class="stat-n" id="sn-buy" style="color:var(--green)">–</div><div class="stat-l">Beli</div></div>
</div>

<!-- Filters -->
<div class="filters">
  <input type="text" class="fi-search" id="fi-search" placeholder="Cari nama, HP, email...">
  <select class="fi-sel" id="fi-camp"><option value="">Semua Kampanye</option></select>
</div>

<!-- Tabs -->
<div class="tabs-wrap">
  <div class="tabs">
    <button class="tab active" data-status="" onclick="setTab(this,'')">Semua <span class="tab-cnt" id="tc-all">–</span></button>
    <button class="tab" data-status="new" onclick="setTab(this,'new')">Baru <span class="tab-cnt" id="tc-new">–</span></button>
    <button class="tab" data-status="contacted" onclick="setTab(this,'contacted')">Dihubungi <span class="tab-cnt" id="tc-contacted">–</span></button>
    <button class="tab" data-status="purchased" onclick="setTab(this,'purchased')">Beli <span class="tab-cnt" id="tc-purchased">–</span></button>
  </div>
</div>

<!-- Lead List -->
<div class="list">
  <div id="page-info" class="page-info" style="display:none"></div>
  <div id="lead-list"><div class="loading"><div class="loading-spin"></div><div>Memuat...</div></div></div>
  <div class="pages" id="pages"></div>
</div>

<!-- Status Sheet -->
<div class="overlay" id="statusSheet">
  <div class="sheet">
    <div class="shdrag"></div>
    <div class="shhead"><h3>Ubah Status Lead</h3><p>Pilih status baru</p></div>
    <div class="shbody">
      <div class="shopt sel" id="sopt-new"       data-val="new"       onclick="selSt('new')">       <span class="shopt-icon">🆕</span><div><div class="shopt-lbl" style="color:var(--blue)">Baru</div></div></div>
      <div class="shopt"     id="sopt-contacted"  data-val="contacted" onclick="selSt('contacted')"> <span class="shopt-icon">📞</span><div><div class="shopt-lbl" style="color:var(--amber)">Dihubungi</div></div></div>
      <div class="shopt"     id="sopt-purchased"  data-val="purchased" onclick="selSt('purchased')"> <span class="shopt-icon">✅</span><div><div class="shopt-lbl" style="color:var(--green)">Beli</div></div></div>
      <div class="shopt"     id="sopt-cancelled"  data-val="cancelled" onclick="selSt('cancelled')"> <span class="shopt-icon">❌</span><div><div class="shopt-lbl" style="color:var(--muted)">Batal</div></div></div>
      <input type="text" class="shinput" id="sNote" placeholder="Catatan (opsional)">
    </div>
    <div class="shfoot">
      <button class="sbtn sbtn-cancel" onclick="closeSheet('statusSheet')">Batal</button>
      <button class="sbtn sbtn-save" onclick="doStatusSave()">Simpan</button>
    </div>
  </div>
</div>

<!-- Block Sheet -->
<div class="overlay" id="blockSheet">
  <div class="sheet">
    <div class="shdrag"></div>
    <div class="shhead"><h3>Blokir Lead</h3><p>Pilih metode pemblokiran</p></div>
    <div class="shbody">
      <div class="shopt sel" id="blk-both"   onclick="selBlk('both')"  ><span class="shopt-icon">🔒</span><div><div class="shopt-lbl">IP + Cookie</div><div class="shopt-sub">Paling kuat</div></div></div>
      <div class="shopt"     id="blk-ip"     onclick="selBlk('ip')"    ><span class="shopt-icon">🌐</span><div><div class="shopt-lbl">IP saja</div><div class="shopt-sub">Blokir IP yang sama</div></div></div>
      <div class="shopt"     id="blk-cookie" onclick="selBlk('cookie')" ><span class="shopt-icon">🍪</span><div><div class="shopt-lbl">Cookie / Browser saja</div></div></div>
      <input type="text" class="shinput" id="bReason" placeholder="Alasan (opsional)">
    </div>
    <div class="shfoot">
      <button class="sbtn sbtn-cancel" onclick="closeSheet('blockSheet')">Batal</button>
      <button class="sbtn sbtn-red" onclick="doBlock()">🚫 Blokir</button>
    </div>
  </div>
</div>

<script>
(function(){
var PANEL_URL = '<?php echo esc_js( home_url( "/" . Konektor_Helper::get_setting( "base_slug", "konektor" ) . "/cs-panel/" ) ); ?>';
var TOKEN     = '<?php echo esc_js( sanitize_text_field( $_GET["token"] ?? "" ) ); ?>';
var smeta = {
  new:       {label:"Baru",       color:"var(--blue)", bg:"#eff6ff", dot:"var(--blue)",  icon:"🆕"},
  contacted: {label:"Dihubungi",  color:"var(--amber)",bg:"#fffbeb", dot:"var(--amber)", icon:"📞"},
  purchased: {label:"Beli",       color:"var(--green)",bg:"#ecfdf5", dot:"var(--green)", icon:"✅"},
  cancelled: {label:"Batal",      color:"var(--muted)",bg:"#f9fafb", dot:"#9ca3af",      icon:"❌"},
  blocked:   {label:"Blokir",     color:"var(--red)",  bg:"#fef2f2", dot:"var(--red)",   icon:"🚫"},
};
var colors = ["#3b82f6","#10b981","#f59e0b","#8b5cf6","#06b6d4","#ec4899"];
var curPage=1, totalPages=1, curStatus='', _lid=null, _selSt=null;

// ── API ────────────────────────────────
function post(act, extra, cb) {
  var fd = new FormData();
  fd.append('act', act); fd.append('token', TOKEN);
  if (extra) Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); });
  fetch(PANEL_URL+'?token='+encodeURIComponent(TOKEN), {method:'POST', body:fd})
    .then(function(r){return r.json();}).then(cb)
    .catch(function(){ cb({success:false}); });
}

// ── Escape ─────────────────────────────
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}

// ── Sheets ─────────────────────────────
function closeSheet(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(function(el){
  el.addEventListener('click',function(e){ if(e.target===el) el.classList.remove('open'); });
});

// ── Tabs ───────────────────────────────
window.setTab = function(btn, status) {
  document.querySelectorAll('.tab').forEach(function(t){ t.classList.remove('active'); });
  btn.classList.add('active');
  curStatus = status;
  loadLeads(1);
};

// ── Stats ──────────────────────────────
function loadStats() {
  post('get_leads',{page:1},function(d){ if(d.success){ document.getElementById('hdr-total').textContent=(d.total||0)+' Lead'; document.getElementById('tc-all').textContent=d.total||0; } });
  ['new','contacted','purchased'].forEach(function(s){
    post('get_leads',{status:s,page:1},function(d){
      if(!d.success)return;
      var n=d.total||0;
      var el=document.getElementById('tc-'+s); if(el)el.textContent=n;
      if(s==='new'){ var e2=document.getElementById('sn-new'); if(e2)e2.textContent=n; }
      if(s==='contacted'){ var e2=document.getElementById('sn-cont'); if(e2)e2.textContent=n; }
      if(s==='purchased'){ var e2=document.getElementById('sn-buy'); if(e2)e2.textContent=n; }
    });
  });
}

// ── Load campaigns ─────────────────────
function loadCampaigns(){
  post('get_campaigns',{},function(r){
    if(!r.success)return;
    var sel=document.getElementById('fi-camp');
    (r.campaigns||[]).forEach(function(c){ var o=document.createElement('option');o.value=c.id;o.textContent=c.name;sel.appendChild(o); });
  });
}

// ── Load leads ─────────────────────────
function loadLeads(page) {
  curPage=page;
  var search=document.getElementById('fi-search').value;
  var camp=document.getElementById('fi-camp').value;
  document.getElementById('lead-list').innerHTML='<div class="loading"><div class="loading-spin"></div><div>Memuat...</div></div>';
  document.getElementById('pages').innerHTML='';

  post('get_leads',{page:curPage, status:curStatus, campaign_id:camp}, function(data){
    if(!data.success){ document.getElementById('lead-list').innerHTML='<div class="empty"><span class="empty-icon">⚠️</span><p>Gagal memuat data.</p></div>'; return; }
    totalPages=data.pages||1;

    var leads=data.leads||[];
    if(search){ var q=search.toLowerCase(); leads=leads.filter(function(l){ return (l.name||'').toLowerCase().includes(q)||(l.phone||'').includes(q)||(l.email||'').includes(q); }); }

    renderLeads(leads, data.total||0);
    renderPages(data.total||0);
  });
}

// ── Render leads ───────────────────────
function renderLeads(leads, total){
  var container=document.getElementById('lead-list');
  var pi=document.getElementById('page-info');
  if(totalPages>1){ pi.style.display='block'; pi.textContent='Halaman '+curPage+' dari '+totalPages+' · '+total+' lead'; }
  else pi.style.display='none';

  if(!leads.length){
    container.innerHTML='<div class="empty"><span class="empty-icon">📭</span><p style="font-weight:600;font-size:15px;margin-bottom:6px">Tidak ada lead</p></div>';
    return;
  }
  var html='';
  leads.forEach(function(l){
    var sm=smeta[l.status]||smeta.new;
    var ci=Math.abs(hashCode(l.name||''))%colors.length;
    var av=colors[ci];
    var init=(l.name||'?').charAt(0).toUpperCase();
    var hasWa=!!(l.phone&&l.status!=='blocked');
    var fuDone=!!l.followed_up_at;
    var waHref=l.phone?'https://wa.me/'+formatPhone(l.phone):'';
    var isBlocked=l.status==='blocked';

    html+='<div class="lcard'+(isBlocked?' blocked':(l.status==='purchased'?' purchased':''))+'" id="lc-'+l.id+'">';

    // Head
    html+='<div class="lcard-head">';
    html+='<div class="lav" style="background:'+av+'">'+esc(init)+'<div class="lav-dot" id="dot-'+l.id+'" style="background:'+sm.dot+'"></div></div>';
    html+='<div class="linfo">';
    html+='<div class="lname">'+esc(l.name||'Tanpa Nama')+'</div>';
    if(l.phone) html+='<a href="tel:'+esc(formatPhone(l.phone))+'" class="lphone"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.13 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.04 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'+esc(l.phone)+'</a>';
    html+='<div class="lcamp">'+esc(l.campaign||'')+'</div>';
    if(fuDone){ html+='<div class="fu-badge fu-done">✓ Follow-Up '+(l.followed_up_at||'').slice(11,16)+'</div>'; }
    else if(!isBlocked){ html+='<div class="fu-badge fu-pending">⏱ Belum Follow-Up</div>'; }
    html+='</div>';

    // Status pill
    if(!isBlocked){
      html+='<button class="spill" id="sp-'+l.id+'" style="background:'+sm.bg+';color:'+sm.color+'" onclick="openStatus('+l.id+',\''+l.status+'\')">'+sm.icon+' '+sm.label+'</button>';
    } else {
      html+='<span class="spill" style="background:'+sm.bg+';color:'+sm.color+';cursor:default">🚫 Blokir</span>';
    }
    html+='</div>';

    // Detail
    var det='';
    if(l.email)   det+='<div class="drow"><span class="dkey">Email</span><span class="dval">'+esc(l.email)+'</span></div>';
    if(l.address) det+='<div class="drow"><span class="dkey">Alamat</span><span class="dval">'+esc(l.address)+'</span></div>';
    if(l.quantity)det+='<div class="drow"><span class="dkey">Jumlah</span><span class="dval">'+esc(l.quantity)+'</span></div>';
    if(l.message) det+='<div class="drow"><span class="dkey">Pesan</span><span class="dval">'+esc(l.message)+'</span></div>';
    if(det) html+='<div class="ldetail">'+det+'</div>';

    // Actions
    html+='<div class="lactions">';
    if(hasWa&&waHref) html+='<a href="'+waHref+'" class="abtn wa" target="_blank"><span class="abtn-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></span><span>WA</span></a>';
    if(!isBlocked&&(l.phone||l.email)) html+='<button class="abtn fu'+(fuDone?' done':'')+'" id="fu-'+l.id+'" onclick="doFollowUp('+l.id+')"'+(fuDone?' disabled':'')+'><span class="abtn-icon">'+(fuDone?'✓':'📲')+'</span><span>'+(fuDone?'Done':'Follow Up')+'</span></button>';
    if(!isBlocked){
      html+='<button class="abtn stat" onclick="openStatus('+l.id+',\''+l.status+'\')"><span class="abtn-icon">🏷️</span><span>Status</span></button>';
      html+='<button class="abtn blk" onclick="openBlock('+l.id+')"><span class="abtn-icon">🚫</span><span>Blokir</span></button>';
    } else {
      html+='<button class="abtn unblk" onclick="doUnblock('+l.id+')" style="flex:4"><span class="abtn-icon">🔓</span><span>Unblock</span></button>';
    }
    html+='</div>';

    html+='<div class="lfoot"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'+esc((l.date||'').slice(0,16))+'</div>';
    html+='</div>';
  });
  container.innerHTML=html;
}

// ── Pagination ─────────────────────────
function renderPages(total){
  var pag=document.getElementById('pages');
  if(totalPages<=1){ pag.innerHTML=''; return; }
  var h='';
  if(curPage>1) h+='<button class="pbtn" onclick="loadLeads('+(curPage-1)+')">‹</button>';
  var f=Math.max(1,curPage-2), t=Math.min(totalPages,curPage+2);
  if(f>1){ h+='<button class="pbtn" onclick="loadLeads(1)">1</button>'; if(f>2) h+='<span class="pbtn" style="cursor:default;border-color:transparent;background:none">…</span>'; }
  for(var i=f;i<=t;i++) h+='<button class="pbtn'+(i===curPage?' active':'')+'" onclick="loadLeads('+i+')">'+i+'</button>';
  if(t<totalPages){ if(t<totalPages-1) h+='<span class="pbtn" style="cursor:default;border-color:transparent;background:none">…</span>'; h+='<button class="pbtn" onclick="loadLeads('+totalPages+')">'+totalPages+'</button>'; }
  if(curPage<totalPages) h+='<button class="pbtn" onclick="loadLeads('+(curPage+1)+')">›</button>';
  pag.innerHTML=h;
}

// ── Status ─────────────────────────────
window.selSt = function(v){
  _selSt=v;
  document.querySelectorAll('[id^=sopt-]').forEach(function(el){ el.classList.toggle('sel',el.dataset.val===v); });
};
window.openStatus = function(id,cur){ _lid=id; selSt(cur); document.getElementById('sNote').value=''; document.getElementById('statusSheet').classList.add('open'); };
window.doStatusSave = function(){
  if(!_selSt) return;
  post('update_status',{lead_id:_lid,status:_selSt,note:document.getElementById('sNote').value},function(res){
    if(res.success){
      var sm=smeta[_selSt];
      var p=document.getElementById('sp-'+_lid); if(p&&sm){ p.textContent=sm.icon+' '+sm.label; p.style.background=sm.bg; p.style.color=sm.color; p.setAttribute('onclick','openStatus('+_lid+',\''+_selSt+'\')'); }
      var dot=document.getElementById('dot-'+_lid); if(dot&&sm) dot.style.background=sm.dot;
      var card=document.getElementById('lc-'+_lid); if(card){ card.classList.toggle('purchased',_selSt==='purchased'); }
      closeSheet('statusSheet');
      loadStats();
    }
  });
};

// ── Block ───────────────────────────────
window.selBlk = function(v){ ['both','ip','cookie'].forEach(function(x){ var el=document.getElementById('blk-'+x); if(el) el.classList.toggle('sel',x===v); }); };
window.openBlock = function(id){ _lid=id; selBlk('both'); document.getElementById('bReason').value=''; document.getElementById('blockSheet').classList.add('open'); };
window.doBlock = function(){
  var sel=document.querySelector('#blockSheet .shopt.sel');
  var by=sel?sel.id.replace('blk-',''):'both';
  post('block_lead',{lead_id:_lid,reason:document.getElementById('bReason').value,block_by:by},function(res){
    if(res.success){ closeSheet('blockSheet'); loadLeads(curPage); loadStats(); }
    else alert(res.message||'Gagal memblokir.');
  });
};
window.doUnblock = function(id){
  if(!confirm('Cabut blokir lead ini?')) return;
  // Unblock belum ada di AJAX handler, reload halaman
  alert('Hubungi admin untuk mencabut blokir.');
};

// ── Follow Up ───────────────────────────
window.doFollowUp = function(id){
  var btn=document.getElementById('fu-'+id); if(!btn||btn.disabled) return;
  btn.disabled=true;
  var lbl=btn.querySelector('span:last-child'); if(lbl) lbl.textContent='...';
  post('follow_up',{lead_id:id},function(res){
    if(res.success){
      btn.classList.add('done');
      var ico=btn.querySelector('.abtn-icon'); if(ico) ico.textContent='✓';
      if(lbl) lbl.textContent='Done';
      var card=document.getElementById('lc-'+id);
      if(card){ var old=card.querySelector('.fu-badge'); if(old){ old.className='fu-badge fu-done'; var n=new Date(); old.textContent='✓ Follow-Up '+String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0'); } }
      if(res.url) window.open(res.url,'_blank');
    } else { btn.disabled=false; if(lbl) lbl.textContent='Follow Up'; alert(res.message||'Gagal.'); }
  });
};

// ── Helpers ─────────────────────────────
function formatPhone(p){ if(!p)return''; p=p.replace(/\D/g,''); if(p.startsWith('0'))p='62'+p.slice(1); return p; }
function hashCode(s){ var h=0; for(var i=0;i<s.length;i++){ h=((h<<5)-h)+s.charCodeAt(i); h|=0; } return h; }

// ── Search debounce ──────────────────────
var searchTimer;
document.getElementById('fi-search').addEventListener('input',function(){ clearTimeout(searchTimer); searchTimer=setTimeout(function(){ loadLeads(1); },350); });
document.getElementById('fi-camp').addEventListener('change',function(){ loadLeads(1); });

// ── Init ─────────────────────────────────
loadCampaigns();
loadLeads(1);
loadStats();
})();
</script>
<?php endif; ?>
</body>
</html>
