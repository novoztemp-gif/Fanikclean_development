<?php
// Layout (header + sidebar + footer) is provided by Controller::view().
$isAdmin = ((int)$user['role_id'] === 1);

// Assigned scope from user_site_assignments (single source of truth).
if ($isAdmin) {
    $scopeLabel = 'All Sites (Admin)';
} elseif (!empty($user['assigned_site_names'])) {
    $scopeLabel = $user['assigned_site_names'];
} else {
    $scopeLabel = 'No sites assigned';
}
?>

<div class="panel active pf-wrap">

  <!-- ============================= HERO HEADER ============================= -->
  <div class="pf-hero">
    <div class="pf-hero-banner"></div>
    <div class="pf-hero-body">
      <div class="pf-photo pf-photo-ph">
        <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>

      <div class="pf-hero-id">
        <div class="pf-name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="pf-tags">
          <span class="pf-code">SYS-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></span>
          <span class="badge <?= $isAdmin ? 'b-indigo' : 'b-amber' ?>"><?= htmlspecialchars($user['role_name']) ?></span>
          <span class="badge <?= $user['status'] == 'Active' ? 'b-green' : 'b-red' ?>">
            <?= htmlspecialchars($user['status']) ?>
          </span>
        </div>
        <div class="pf-meta">
          <span class="pf-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <strong><?= htmlspecialchars($scopeLabel) ?></strong>
          </span>
          <span class="pf-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <strong><?= htmlspecialchars($user['email']) ?></strong>
          </span>
          <span class="pf-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            Registered <strong><?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '—' ?></strong>
          </span>
        </div>
      </div>

      <div class="pf-hero-actions">
        <a href="/users" class="btn btn-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          Users
        </a>
        <button class="btn btn-sm" onclick="history.back()">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit
        </button>
        <button class="btn btn-primary btn-sm" onclick="window.print()">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
          Export PDF
        </button>
      </div>
    </div>
  </div>

  <!-- ============================= INFO CARDS ============================= -->
  <div class="pf-info-grid">

    <!-- System Alignment -->
    <div class="card">
      <div class="pf-card-title">
        <span class="pf-icn" style="background:var(--teal-bg);">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </span>
        System Alignment
      </div>
      <div class="pf-row"><span class="pf-row-label">Platform Role</span><span class="pf-row-val"><?= htmlspecialchars($user['role_name']) ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Assigned Site Scope</span><span class="pf-row-val"><?= htmlspecialchars($scopeLabel) ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Account Status</span><span class="pf-row-val"><?= $user['status'] === 'Active' ? 'Active / Permitted' : 'Suspended' ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Last Login</span><span class="pf-row-val"><?= $user['last_login'] ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Never logged in' ?></span></div>
    </div>

    <!-- Account & Contact -->
    <div class="card">
      <div class="pf-card-title">
        <span class="pf-icn" style="background:var(--amber-bg);">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </span>
        Account &amp; Contact
      </div>
      <div class="pf-row"><span class="pf-row-label">Email Access</span><span class="pf-row-val"><?= htmlspecialchars($user['email']) ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Phone</span><span class="pf-row-val"><?= htmlspecialchars($user['phone'] ?: 'Not set') ?></span></div>
      <div class="pf-row"><span class="pf-row-label">System User ID</span><span class="pf-row-val mono">SYS-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Registered On</span><span class="pf-row-val"><?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '—' ?></span></div>
    </div>

    <!-- Guardian -->
    <div class="card">
      <div class="pf-card-title">
        <span class="pf-icn" style="background:var(--red-bg);">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </span>
        Emergency / Guardian
      </div>
      <div class="pf-row"><span class="pf-row-label">Guardian Name</span><span class="pf-row-val"><?= htmlspecialchars($user['guardian_name'] ?: 'Not provided') ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Guardian Phone</span><span class="pf-row-val"><?= htmlspecialchars($user['guardian_phone'] ?: 'Not provided') ?></span></div>
      <div class="pf-row"><span class="pf-row-label">Place</span><span class="pf-row-val"><?= htmlspecialchars($user['guardian_place'] ?: 'Not provided') ?></span></div>
    </div>
  </div>
</div>

<style>
/* ===== User profile (scoped) — mirrors the worker profile ===== */
.pf-wrap { max-width: 1120px; }

.pf-hero {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 22px;
}
.pf-hero-banner {
  height: 92px;
  background: linear-gradient(115deg, var(--slate-dark) 0%, var(--slate-light) 62%, var(--teal) 130%);
}
.pf-hero-body {
  display: flex;
  align-items: flex-end;
  gap: 22px;
  padding: 0 26px 22px;
  margin-top: -44px;
  flex-wrap: wrap;
}
.pf-photo {
  width: 104px; height: 104px;
  border-radius: 22px;
  border: 4px solid var(--card);
  background: var(--bg);
  object-fit: cover;
  box-shadow: var(--shadow);
  flex-shrink: 0;
}
.pf-photo-ph {
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted);
}
.pf-hero-id { flex: 1; min-width: 240px; padding-bottom: 2px; }
.pf-name {
  font-size: 22px; font-weight: 800;
  letter-spacing: -.4px; color: var(--text);
  margin-bottom: 9px;
}
.pf-tags { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 13px; }
.pf-code {
  font-family: 'DM Mono', monospace;
  font-size: 12px; font-weight: 700;
  color: var(--teal-text); background: var(--teal-bg);
  padding: 3px 10px; border-radius: 8px;
}
.pf-meta { display: flex; gap: 22px; flex-wrap: wrap; }
.pf-meta-item {
  display: flex; align-items: center; gap: 7px;
  font-size: 13px; color: var(--text-muted);
}
.pf-meta-item svg { color: var(--text-muted); }
.pf-meta-item strong { color: var(--text); font-weight: 600; }
.pf-hero-actions { display: flex; gap: 10px; padding-bottom: 4px; flex-wrap: wrap; }

.pf-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 18px;
  margin-bottom: 18px;
}
.pf-card-title {
  display: flex; align-items: center; gap: 10px;
  font-size: 13.5px; font-weight: 700; color: var(--text);
  margin-bottom: 6px; padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
}
.pf-icn {
  width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.pf-row {
  display: flex; align-items: center; justify-content: space-between;
  gap: 14px; padding: 11px 0;
  border-bottom: 1px solid #f1f5f9;
}
.pf-row:last-child { border-bottom: none; padding-bottom: 2px; }
.pf-row-label { font-size: 12.5px; color: var(--text-muted); }
.pf-row-val { font-size: 13px; font-weight: 600; color: var(--text); text-align: right; word-break: break-word; }
.pf-row-val.mono { font-family: 'DM Mono', monospace; letter-spacing: .2px; }

/* ===== Print / Export PDF ===== */
@media print {
  .sidebar, .topbar, .pf-hero-actions, .modal-overlay, #toast { display: none !important; }
  body, .app, .main, .content { background: #fff !important; margin: 0 !important; padding: 0 !important; }
  .pf-hero, .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
  .pf-hero-banner { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .pf-info-grid { grid-template-columns: 1fr 1fr !important; }
}
</style>
