<?php
// Read-only monthly attendance register (muster roll). Data comes from
// AttendanceController::register() -> Attendance::getMonthlyRegister().
$daysInMonth = (int)date('t', strtotime($month . '-01'));
$firstDow    = (int)date('N', strtotime($month . '-01')); // 1=Mon .. 7=Sun (unused directly, kept for clarity)

// Status code -> [short label, background, text colour]. Mirrors the marking grid.
// No "Absent" status — Off Duty is used instead.
$codeMeta = [
    'p'   => ['P',   '#dcfce7', '#15803d'],
    'off' => ['Off', '#fee2e2', '#b91c1c'],
    'h'   => ['H',   '#fef3c7', '#b45309'],
    'pl'  => ['PL',  '#dbeafe', '#1d4ed8'],
    'sd'  => ['SD',  '#ede9fe', '#6d28d9'],
];
?>
<div class="panel active">
  <!-- Filter Bar -->
  <form method="GET" action="/attendance/register">
    <div class="card mb20">
      <div class="flex gap16 flex-wrap" style="align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 170px;">
          <label class="form-label">Register Month</label>
          <input type="month" name="month" class="form-input" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()">
        </div>

        <div class="form-group" style="flex: 2; min-width: 220px;">
          <label class="form-label">Filter by Site</label>
          <select class="form-input" name="site_id" onchange="this.form.submit()">
            <option value="">All Sites</option>
            <?php foreach(($sites ?? []) as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($selectedSiteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($sites)): ?>
            <div class="fs11" style="margin-top:6px; color: var(--danger);">No sites assigned to you yet. Ask an Admin to assign you a site under "Site Assignments".</div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom: 2px;">
          <a href="/attendance?from_date=<?= htmlspecialchars($month) ?>-01<?= $selectedSiteId ? '&site_id=' . htmlspecialchars($selectedSiteId) : '' ?>" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
            Mark Attendance
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
          <span class="fs11 c-secondary" style="margin-left:-8px;">
            <?= ['p'=>'Present','a'=>'Absent','h'=>'Half-Day','off'=>'Off Duty','pl'=>'Paid Leave','sd'=>'Special Duty'][$c] ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Register Matrix -->
    <div class="card">
      <div class="card-head">
        <div class="card-title">Attendance Register <span class="c-secondary fw6">(<?= count($rows) ?> workers)</span></div>
        <div class="chip b-gray">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <?= date('F Y', strtotime($month . '-01')) ?>
        </div>
      </div>

      <div class="table-wrap reg-scroll">
        <?php if (empty($rows)): ?>
          <div style="text-align:center; padding: 40px; color: var(--text-muted);">No workers found for this scope.</div>
        <?php else: ?>
        <table class="reg-table">
          <thead>
            <tr>
              <th class="reg-sticky reg-name">Worker</th>
              <?php for($d = 1; $d <= $daysInMonth; $d++):
                $dow = (int)date('N', strtotime(sprintf('%s-%02d', $month, $d)));
                $isWeekend = ($dow >= 6);
              ?>
                <th class="reg-day <?= $isWeekend ? 'reg-weekend' : '' ?>" title="<?= date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) ?>"><?= $d ?></th>
              <?php endfor; ?>
              <th class="reg-tot" title="Present">P</th>
              <th class="reg-tot" title="Off Duty">Off</th>
              <th class="reg-tot" title="Half-Day">H</th>
              <th class="reg-tot" title="Paid Leave">PL</th>
              <th class="reg-tot" title="Special Duty">SD</th>
              <th class="reg-tot" title="Overtime hours">OT</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
            <tr>
              <td class="reg-sticky reg-name">
                <div class="fw6"><?= htmlspecialchars($r['full_name']) ?></div>
                <div class="fs11 c-secondary"><?= htmlspecialchars($r['worker_code']) ?> · <?= htmlspecialchars($r['category_name'] ?? 'General') ?> · <?= htmlspecialchars($r['site_name']) ?></div>
              </td>
              <?php for($d = 1; $d <= $daysInMonth; $d++):
                $cell = $r['days'][$d] ?? null;
                $dow = (int)date('N', strtotime(sprintf('%s-%02d', $month, $d)));
                $isWeekend = ($dow >= 6);
                if ($cell && isset($codeMeta[$cell['status']])):
                  $m = $codeMeta[$cell['status']];
                  $tip = date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) . ' — ' .
                         ['p'=>'Present','a'=>'Absent','h'=>'Half-Day','off'=>'Off Duty','pl'=>'Paid Leave','sd'=>'Special Duty'][$cell['status']];
                  if ((float)($cell['ot_hours'] ?? 0) > 0) { $tip .= ' · OT ' . rtrim(rtrim((string)$cell['ot_hours'], '0'), '.') . 'h'; }
                  if (!empty($cell['note'])) { $tip .= ' · ' . $cell['note']; }
              ?>
                <td class="reg-cell" style="background: <?= $m[1] ?>; color: <?= $m[2] ?>;" title="<?= htmlspecialchars($tip) ?>"><?= $m[0] ?><?php if((float)($cell['ot_hours'] ?? 0) > 0): ?><span class="reg-otdot" title="Has OT">•</span><?php endif; ?></td>
              <?php else: ?>
                <td class="reg-cell reg-empty <?= $isWeekend ? 'reg-weekend' : '' ?>" title="<?= date('D, d M', strtotime(sprintf('%s-%02d', $month, $d))) ?> — not marked">–</td>
              <?php endif; ?>
              <?php endfor; ?>
              <td class="reg-tot fw7" style="color:#15803d;"><?= $r['totals']['p'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#b91c1c;"><?= $r['totals']['off'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#b45309;"><?= $r['totals']['h'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#1d4ed8;"><?= $r['totals']['pl'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#6d28d9;"><?= $r['totals']['sd'] ?: '' ?></td>
              <td class="reg-tot fw7" style="color:#3b82f6;"><?= $r['totals']['ot'] ? rtrim(rtrim(number_format($r['totals']['ot'], 1), '0'), '.') : '' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div class="fs11 c-secondary" style="padding: 10px 16px;">Hover any cell for the date, OT and note. "–" means no attendance was recorded that day. A "•" marks days with overtime.</div>
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
  .reg-cell { width: 30px; min-width: 30px; height: 34px; font-weight: 700; position: relative; }
  .reg-empty { color: #cbd5e1; background: #fff; font-weight: 400; }
  .reg-otdot { position: absolute; top: 1px; right: 3px; font-size: 12px; line-height: 1; color: #3b82f6; }
  .reg-tot { width: 34px; min-width: 34px; padding: 0 4px; background: #fafafa; }
  /* sticky worker-name column */
  .reg-sticky { position: sticky; left: 0; z-index: 3; background: var(--card); }
  thead .reg-sticky { z-index: 4; }
  .reg-name { min-width: 210px; max-width: 210px; text-align: left; padding: 6px 12px; border-right: 2px solid var(--border); }
  .reg-table tbody tr:hover td { background-color: #f9fafb; }
  .reg-table tbody tr:hover .reg-cell[style] { filter: brightness(0.97); }
</style>
