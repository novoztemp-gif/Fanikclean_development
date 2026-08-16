<?php
// Read-only monthly MANAGER attendance register (managers × day matrix).
// Data from AttendanceController::managerRegister() -> ManagerAttendance::getMonthlyRegister().
$daysInMonth = (int)date('t', strtotime($month . '-01'));

// Manager statuses (no Absent, no OT): code -> [short label, background, text].
$codeMeta = [
    'p'   => ['P',   '#dcfce7', '#15803d'],
    'h'   => ['H',   '#fef3c7', '#b45309'],
    'off' => ['Off', '#e2e8f0', '#475569'],
    'pl'  => ['PL',  '#dbeafe', '#1d4ed8'],
    'sd'  => ['SD',  '#ede9fe', '#6d28d9'],
];
$codeName = ['p'=>'Present','h'=>'Half-Day','off'=>'Off Duty','pl'=>'Paid Leave','sd'=>'Special Duty'];
?>
<div class="panel active">
  <!-- Filter Bar -->
  <form method="GET" action="/attendance/manager/register">
    <div class="card mb20">
      <div class="flex gap16 flex-wrap" style="align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 170px;">
          <label class="form-label">Register Month</label>
          <input type="month" name="month" class="form-input" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()">
        </div>
        <div style="flex:2;"></div>
        <div style="margin-bottom: 2px;">
          <a href="/attendance/manager?date=<?= htmlspecialchars($month) ?>-01" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m19 8 2 2-2 2"/></svg>
            Mark Manager Attendance
          </a>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="card mb20" style="padding: 12px 16px;">
      <div class="flex gap16 flex-wrap" style="align-items:center;">
        <span class="fs11 fw7 c-secondary text-upper">Legend</span>
        <?php foreach($codeMeta as $c => $m): ?>
          <span class="chip" style="background: <?= $m[1] ?>; color: <?= $m[2] ?>; font-weight:700;"><?= $m[0] ?></span>
          <span class="fs11 c-secondary" style="margin-left:-8px;"><?= $codeName[$c] ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Register Matrix -->
    <div class="card">
      <div class="card-head">
        <div class="card-title">Manager Attendance Register <span class="c-secondary fw6">(<?= count($rows) ?> managers)</span></div>
        <div class="chip b-gray">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <?= date('F Y', strtotime($month . '-01')) ?>
        </div>
      </div>

      <div class="table-wrap reg-scroll">
        <?php if (empty($rows)): ?>
          <div style="text-align:center; padding: 40px; color: var(--text-muted);">No managers found.</div>
        <?php else: ?>
        <table class="reg-table">
          <thead>
            <tr>
              <th class="reg-sticky reg-name">Manager</th>
              <?php for($d = 1; $d <= $daysInMonth; $d++):
                $dow = (int)date('N', strtotime(sprintf('%s-%02d', $month, $d)));
                $isWeekend = ($dow >= 6);
              ?>
                <th class="reg-day <?= $isWeekend ? 'reg-weekend' : '' ?>" title="<?= date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) ?>"><?= $d ?></th>
              <?php endfor; ?>
              <th class="reg-tot" title="Present">P</th>
              <th class="reg-tot" title="Half-Day">H</th>
              <th class="reg-tot" title="Off Duty">Off</th>
              <th class="reg-tot" title="Paid Leave">PL</th>
              <th class="reg-tot" title="Special Duty">SD</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
            <tr>
              <td class="reg-sticky reg-name">
                <div class="fw6"><?= htmlspecialchars($r['full_name']) ?></div>
                <div class="fs11 c-secondary"><?= htmlspecialchars($r['email']) ?></div>
              </td>
              <?php for($d = 1; $d <= $daysInMonth; $d++):
                $cell = $r['days'][$d] ?? null;
                $dow = (int)date('N', strtotime(sprintf('%s-%02d', $month, $d)));
                $isWeekend = ($dow >= 6);
                if ($cell && isset($codeMeta[$cell['status']])):
                  $m = $codeMeta[$cell['status']];
                  $tip = date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) . ' — ' . $codeName[$cell['status']];
                  if (!empty($cell['note'])) { $tip .= ' · ' . $cell['note']; }
              ?>
                <td class="reg-cell" style="background: <?= $m[1] ?>; color: <?= $m[2] ?>;" title="<?= htmlspecialchars($tip) ?>"><?= $m[0] ?></td>
              <?php else: ?>
                <td class="reg-cell reg-empty <?= $isWeekend ? 'reg-weekend' : '' ?>" title="<?= date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) ?> — not marked">–</td>
              <?php endif; ?>
              <?php endfor; ?>
              <td class="reg-tot fw7" style="color:#15803d;"><?= $r['totals']['p'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#b45309;"><?= $r['totals']['h'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#475569;"><?= $r['totals']['off'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#1d4ed8;"><?= $r['totals']['pl'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#6d28d9;"><?= $r['totals']['sd'] ?: '' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div class="fs11 c-secondary" style="padding: 10px 16px;">Hover any cell for the date, status and note. "–" means no attendance was recorded that day.</div>
    </div>
  </form>
</div>

<style>
  .reg-scroll { overflow-x: auto; }
  .reg-table { border-collapse: separate; border-spacing: 0; font-size: 12px; width: max-content; min-width: 100%; }
  .reg-table th, .reg-table td { border-bottom: 1px solid var(--border); border-right: 1px solid #f1f5f9; padding: 0; text-align: center; }
  .reg-table thead th { position: sticky; top: 0; background: var(--card); z-index: 2; padding: 6px 0; font-weight: 700; color: var(--text-muted); }
  .reg-day { width: 30px; min-width: 30px; }
  .reg-weekend { background: #f8fafc; color: #94a3b8; }
  .reg-cell { width: 30px; min-width: 30px; height: 34px; font-weight: 700; }
  .reg-empty { color: #cbd5e1; background: #fff; font-weight: 400; }
  .reg-tot { width: 34px; min-width: 34px; padding: 0 4px; background: #fafafa; }
  .reg-sticky { position: sticky; left: 0; z-index: 3; background: var(--card); }
  thead .reg-sticky { z-index: 4; }
  .reg-name { min-width: 210px; max-width: 210px; text-align: left; padding: 6px 12px; border-right: 2px solid var(--border); }
  .reg-table tbody tr:hover td { background-color: #f9fafb; }
  .reg-table tbody tr:hover .reg-cell[style] { filter: brightness(0.97); }
</style>
