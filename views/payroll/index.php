<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Automated salary computation and payout tracking.</div>
  </div>

  <div class="card mb20">
    <div class="card-head">
      <div class="card-title">Payroll Filters & Processing</div>
    </div>
    
    <div class="flex gap24 flex-wrap p20 bg-light" style="border-top: 1px solid var(--border-color);">
      <!-- Filter Form -->
      <form method="GET" action="/payroll" class="flex gap16 flex-wrap" style="flex: 1; align-items: flex-end;">
          <div class="form-group" style="width: 180px;">
              <label class="form-label">Review Month</label>
              <input type="month" name="month" class="form-input" value="<?= $selectedMonth ?>" onchange="this.form.submit()">
          </div>
          <div class="form-group" style="width: 200px;">
              <label class="form-label">Filter by Client</label>
              <select name="client_id" id="payroll-client" class="form-input" onchange="filterPayrollSites(); this.form.submit()">
                  <option value="">-- All Clients --</option>
                  <?php foreach($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedClientId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['company_name']) ?></option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div class="form-group" style="width: 220px;">
              <label class="form-label">Filter by Site</label>
              <select name="site_id" id="payroll-site" class="form-input" onchange="this.form.submit()">
                  <option value="">-- All Accessible Sites --</option>
                  <?php foreach($sites as $s): ?>
                    <option value="<?= $s['id'] ?>" data-client-id="<?= $s['client_id'] ?>" <?= $selectedSiteId == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                  <?php endforeach; ?>
              </select>
          </div>
      </form>

      <!-- Action Button -->
      <form method="POST" action="/payroll/approve" style="margin:0; align-self: flex-end;">
          <input type="hidden" name="month_year" value="<?= $selectedMonth ?>">
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="m5 14 6-6 6 6"/><path d="M12 8V22"/><path d="M5 2h14"/></svg>
            Compute <?= date('M Y', strtotime($selectedMonth)) ?>
          </button>
      </form>
    </div>
  </div>

  <?php
    $totalGross = 0; $totalOT = 0; $totalNet = 0;
    foreach($payrolls as $p) {
        $totalGross += $p['basic_pay'];
        $totalOT += $p['ot_pay'];
        $totalNet += $p['net_pay'];
    }
  ?>

  <!-- Payroll Stats -->
  <div class="stats-grid mb20">
    <div class="stat-card">
      <div class="stat-label">Gross Basic Salary</div>
      <div class="stat-val">₹<?= number_format($totalGross, 2) ?></div>
      <div class="fs11 c-secondary mt4">Based on <?= count($payrolls) ?> employees</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total OT Allowance</div>
      <div class="stat-val">₹<?= number_format($totalOT, 2) ?></div>
      <div class="fs11 c-secondary mt4">All sites included</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Net Payable Amount</div>
      <div class="stat-val" style="color: var(--primary);">₹<?= number_format($totalNet, 2) ?></div>
      <div class="fs11 fw6 c-primary mt4">Archived for Bank Transfer</div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-title">Payroll Registry: <?= date('F Y', strtotime($selectedMonth)) ?></div>
      <a href="/payroll/export?month=<?= $selectedMonth ?>&site_id=<?= $selectedSiteId ?>&client_id=<?= $selectedClientId ?>" class="btn btn-sm btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M10 9h1"/></svg>
        Download Excel
      </a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Employee & Site</th>
            <th>Month</th>
            <th>Category</th>
            <th class="text-right">Attendance</th>
            <th class="text-right">Basic Pay</th>
            <th class="text-right">OT Pay</th>
            <th class="text-right">Net Salary</th>
            <th class="text-right">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($payrolls)): ?>
          <tr><td colspan="8" style="text-align:center; padding: 40px; color: var(--text-muted);">No payroll data generated for the selected scope.</td></tr>
          <?php else: ?>
          <?php foreach($payrolls as $p): ?>
          <tr>
            <td>
              <div class="bold fs14"><?= htmlspecialchars($p['name']) ?></div>
              <div class="fs11 c-secondary flex items-center gap4">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($p['site_name']) ?>
              </div>
            </td>
            <td><span class="chip fs11"><?= htmlspecialchars($p['month_year']) ?></span></td>
            <td><span class="badge b-gray fs11"><?= htmlspecialchars($p['category_name']) ?></span></td>
            <td class="text-right"><span class="fw7"><?= number_format($p['days_worked'], 1) ?></span> <span class="fs11 c-secondary">days</span></td>
            <td class="mono fs13 text-right">₹<?= number_format($p['basic_pay'], 2) ?></td>
            <td class="mono fs13 text-right">
              <div class="fs11 c-secondary"><?= number_format($p['ot_days'] * 8, 1) ?> hrs</div>
              <div class="fw6">₹<?= number_format($p['ot_pay'], 2) ?></div>
            </td>
            <td class="bold fs15 text-right" style="color: var(--primary);">₹<?= number_format($p['net_pay'], 2) ?></td>
            <td class="text-right">
              <span class="badge <?= $p['status'] == 'Approved' || $p['status'] == 'Paid' ? 'b-green' : 'b-amber' ?>">
                <?= htmlspecialchars($p['status'] ?? 'Pending') ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function filterPayrollSites() {
    const clientId = document.getElementById('payroll-client').value;
    const siteSelect = document.getElementById('payroll-site');
    const options = siteSelect.querySelectorAll('option');
    
    options.forEach(opt => {
        if (!opt.value) { opt.style.display = ''; return; }
        if (!clientId || opt.getAttribute('data-client-id') === clientId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Automatically reset site selection if the selected site doesn't match the new client
    const selected = siteSelect.options[siteSelect.selectedIndex];
    if (selected && selected.style.display === 'none') {
        siteSelect.value = '';
    }
}

// Initial filter application on page load
document.addEventListener('DOMContentLoaded', filterPayrollSites);
</script>
