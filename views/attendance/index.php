<div class="panel active">
  <form method="POST" action="/attendance/save">
    <!-- Filter Bar -->
    <div class="card mb20">
      <div class="flex gap16 flex-wrap" style="align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 170px;">
          <label class="form-label">Attendance Month / Day</label>
          <input type="date" name="from_date" class="form-input" value="<?= htmlspecialchars($fromDate) ?>" onchange="reloadFilter()" required>
          <div class="fs11 c-secondary" style="margin-top:4px;">Sets the calendar's month &amp; the default day.</div>
        </div>

        <div class="form-group" style="flex: 2; min-width: 220px;">
          <label class="form-label">Filter by Site</label>
          <select class="form-input" id="site_select" name="site_id" onchange="reloadFilter()">
              <option value="">All Sites</option>
              <?php foreach(($sites ?? []) as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($selectedSiteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
          </select>
          <?php if (empty($sites)): ?>
          <div class="fs11" style="margin-top:6px; color: var(--danger);">No sites assigned to you yet. Ask an Admin to assign you a site under "Site Assignments".</div>
          <?php endif; ?>
        </div>

        <div class="flex gap8" style="margin-bottom: 2px;">
          <a href="/attendance/register?month=<?= htmlspecialchars(date('Y-m', strtotime($fromDate))) ?><?= $selectedSiteId ? '&site_id=' . htmlspecialchars($selectedSiteId) : '' ?>" class="btn btn-outline" title="View the monthly attendance register (read-only)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            View Register
          </a>
          <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Register
          </button>
        </div>
      </div>
    </div>

    <!-- Attendance Register -->
    <div class="card">
      <div class="card-head">
        <div class="card-title">Worker Attendance Grid <span class="c-secondary fw6">(<?= count($workers) ?>)</span></div>
        <div class="chip b-gray">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <?= date('F Y', strtotime($fromDate)) ?>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Worker Details</th>
              <th>Site</th>
              <th>Status &amp; Days</th>
              <th>OT Hours</th>
              <th>Remarks / Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($workers)): ?>
              <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">No active workers found for this scope.</td></tr>
            <?php else: ?>
              <?php
                // No separate "Absent" status — Off Duty is used instead.
                $statusLabels = [
                  'p'   => 'Present (P)',
                  'off' => 'Off Duty (Off)',
                  'h'   => 'Half-Day (H)',
                  'pl'  => 'Paid Leave (PL)',
                  'sd'  => 'Special Duty (SD)',
                ];
                // [background, text] per status — applied to each <option> so the open
                // dropdown shows every status in its own colour (not all one tint).
                $statusColors = [
                  'p'   => ['#E7F1EC', '#2F6B4F'], // green
                  'off' => ['#FBEDEA', '#8C3324'], // red
                  'h'   => ['#FBF1E0', '#7A5310'], // amber
                  'pl'  => ['#EAF1F8', '#2E5478'], // blue
                  'sd'  => ['#F1EEF7', '#4A3F72'], // purple
                ];
              ?>
              <?php foreach ($workers as $w): ?>
                <?php
                  $status = $w['saved_status'] ?: 'p'; // default Present
                  $ot     = ($w['saved_ot'] !== null && $w['saved_ot'] !== '') ? $w['saved_ot'] : 0;
                  $note   = $w['saved_note'] ?? '';
                  $plOver = ((int)$w['pl_count']) >= 4;
                ?>
                <tr>
                  <td>
                    <div class="fw6"><?= htmlspecialchars($w['full_name']) ?></div>
                    <div class="fs11 c-secondary"><?= htmlspecialchars($w['worker_code']) ?> | <?= htmlspecialchars($w['category_name'] ?? 'General') ?></div>
                  </td>
                  <td><span class="chip b-gray"><?= htmlspecialchars($w['site_name']) ?></span></td>
                  <td>
                    <div class="flex-center gap8">
                      <select class="form-input att-select <?= $status ?>" name="attendance[<?= $w['id'] ?>][status]" style="width:140px;" onchange="this.className = 'form-input att-select ' + this.value">
                        <?php foreach ($statusLabels as $code => $label): ?>
                          <?php if ($code === 'pl' && $plOver && $status !== 'pl') continue; // hide PL once monthly cap is hit ?>
                          <?php $c = $statusColors[$code] ?? ['#fff', '#000']; ?>
                          <option value="<?= $code ?>" style="background: <?= $c[0] ?>; color: <?= $c[1] ?>;" <?= $status === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="button" class="btn btn-sm btn-icon cal-btn" title="Pick days to apply this status" onclick="openCalendar('<?= $w['id'] ?>', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                      </button>
                      <span class="chip b-green cal-count" id="count-<?= $w['id'] ?>" title="Selected days">1</span>
                    </div>
                    <input type="hidden" id="dates-<?= $w['id'] ?>" name="attendance[<?= $w['id'] ?>][dates]" value="<?= htmlspecialchars($fromDate) ?>">
                  </td>
                  <td>
                    <div class="flex-center gap8">
                      <input type="number" step="0.5" min="0" name="attendance[<?= $w['id'] ?>][ot]" class="form-input" style="width:80px;" value="<?= htmlspecialchars($ot) ?>">
                      <span class="fs11 fw6 c-secondary">hrs</span>
                    </div>
                  </td>
                  <td><input type="text" name="attendance[<?= $w['id'] ?>][note]" class="form-input" placeholder="Optional note..." value="<?= htmlspecialchars($note) ?>"></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>
</div>

<!-- Floating multi-day calendar popup (shared, repositioned per worker) -->
<div id="cal-popup" class="cal-popup">
  <div class="cal-head">
    <span id="cal-title"></span>
    <div class="cal-actions">
      <button type="button" onclick="calSelectAll()">All</button>
      <button type="button" onclick="calClear()">Clear</button>
      <button type="button" onclick="calClose()">Done</button>
    </div>
  </div>
  <div id="cal-grid" class="cal-grid"></div>
  <div class="cal-foot">Click days to mark this worker. The chosen status applies to every selected day on Save.</div>
</div>

<script>
window.ATT_ANCHOR = '<?= htmlspecialchars($fromDate) ?>';

// Changing a filter reloads the page (GET) so the grid re-renders server-side.
function reloadFilter() {
    var from = document.querySelector('input[name="from_date"]').value;
    var site = document.getElementById('site_select').value;
    var url = '/attendance?from_date=' + encodeURIComponent(from);
    if (site) { url += '&site_id=' + encodeURIComponent(site); }
    location.href = url;
}

(function () {
    var anchor   = new Date(window.ATT_ANCHOR + 'T00:00:00');
    var year     = anchor.getFullYear();
    var month    = anchor.getMonth(); // 0-based
    var monthName = ['January','February','March','April','May','June','July','August','September','October','November','December'][month];

    var activeWorker = null;
    var popup = document.getElementById('cal-popup');
    var grid  = document.getElementById('cal-grid');
    var title = document.getElementById('cal-title');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function ymd(d) { return year + '-' + pad(month + 1) + '-' + pad(d); }

    function getDates(id) {
        var v = document.getElementById('dates-' + id).value;
        return v ? v.split(',').filter(Boolean) : [];
    }
    function setDates(id, arr) {
        arr.sort();
        document.getElementById('dates-' + id).value = arr.join(',');
        var badge = document.getElementById('count-' + id);
        if (badge) {
            badge.textContent = arr.length;
            badge.className = 'chip cal-count ' + (arr.length ? 'b-green' : 'b-red');
        }
    }

    function render() {
        title.textContent = monthName + ' ' + year;
        var selected = getDates(activeWorker);
        var firstDow = new Date(year, month, 1).getDay();   // 0 = Sun
        var offset   = (firstDow + 6) % 7;                   // Monday-first
        var days     = new Date(year, month + 1, 0).getDate();

        var html = '';
        ['Mo','Tu','We','Th','Fr','Sa','Su'].forEach(function (d) { html += '<div class="cal-dow">' + d + '</div>'; });
        for (var i = 0; i < offset; i++) { html += '<div></div>'; }
        for (var d = 1; d <= days; d++) {
            var ds = ymd(d);
            html += '<div class="cal-day' + (selected.indexOf(ds) >= 0 ? ' sel' : '') + '" data-d="' + ds + '">' + d + '</div>';
        }
        grid.innerHTML = html;
    }

    grid.addEventListener('click', function (e) {
        var cell = e.target.closest('.cal-day');
        if (!cell) return;
        var ds = cell.getAttribute('data-d');
        var arr = getDates(activeWorker);
        var idx = arr.indexOf(ds);
        if (idx >= 0) { arr.splice(idx, 1); cell.classList.remove('sel'); }
        else          { arr.push(ds);        cell.classList.add('sel'); }
        setDates(activeWorker, arr);
    });

    window.openCalendar = function (id, btn) {
        activeWorker = id;
        render();
        popup.style.display = 'block';
        var r = btn.getBoundingClientRect();
        var top = r.bottom + 6, left = r.left;
        if (left + 270 > window.innerWidth)  { left = window.innerWidth - 278; }
        if (top + 330 > window.innerHeight)  { top = Math.max(8, r.top - 330); }
        popup.style.top = top + 'px';
        popup.style.left = left + 'px';
    };
    window.calSelectAll = function () {
        var days = new Date(year, month + 1, 0).getDate();
        var arr = [];
        for (var d = 1; d <= days; d++) { arr.push(ymd(d)); }
        setDates(activeWorker, arr); render();
    };
    window.calClear = function () { setDates(activeWorker, []); render(); };
    window.calClose = function () { popup.style.display = 'none'; };

    document.addEventListener('click', function (e) {
        if (popup.style.display === 'block' && !popup.contains(e.target) && !e.target.closest('.cal-btn')) {
            popup.style.display = 'none';
        }
    });
})();
</script>

<style>
.att-select { font-weight: 600; cursor: pointer; }
.att-select.p   { background: #E7F1EC !important; color: #2F6B4F !important; border-color: #BFDDCB !important; }
.att-select.off { background: #FBEDEA !important; color: #8C3324 !important; border-color: #E9C4BA !important; }
.att-select.h   { background: #FBF1E0 !important; color: #7A5310 !important; border-color: #EBD2A0 !important; }
.att-select.pl  { background: #EAF1F8 !important; color: #2E5478 !important; border-color: #B9D2E8 !important; }
.att-select.sd  { background: #F1EEF7 !important; color: #4A3F72 !important; border-color: #D2C9E8 !important; }

.cal-count { min-width: 22px; justify-content: center; }
.cal-popup {
  position: fixed; z-index: 600; display: none;
  width: 262px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
  padding: 12px;
}
.cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.cal-head span { font-weight: 700; font-size: 13px; color: var(--text); }
.cal-actions { display: flex; gap: 4px; }
.cal-actions button {
  font-size: 11px; font-weight: 600; padding: 3px 8px;
  border: 1px solid var(--border); background: var(--bg);
  border-radius: 6px; cursor: pointer; color: var(--text);
  font-family: inherit;
}
.cal-actions button:hover { background: var(--border); }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
.cal-dow { text-align: center; font-size: 10px; font-weight: 700; color: var(--text-muted); padding: 2px 0; }
.cal-day {
  text-align: center; font-size: 12px; padding: 7px 0;
  border-radius: 7px; cursor: pointer;
  border: 1px solid transparent; user-select: none;
}
.cal-day:hover { background: var(--bg); border-color: var(--border); }
.cal-day.sel { background: var(--teal); color: #fff; font-weight: 700; }
.cal-foot { font-size: 10.5px; color: var(--text-muted); margin-top: 9px; line-height: 1.35; }
</style>
