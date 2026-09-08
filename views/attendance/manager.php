<div class="panel active">
  <form method="POST" action="/attendance/manager/save">
    <input type="hidden" name="attendance_date" value="<?= $date ?>">

    <!-- Filter / action bar -->
    <div class="card mb20">
      <div class="flex gap16 flex-wrap" style="align-items: flex-end;">
        <div class="form-group mb0" style="flex: 1; min-width: 200px;">
          <label class="form-label">Attendance Month / Day</label>
          <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($date) ?>" onchange="reloadDate(this.value)">
          <div class="fs11 c-secondary" style="margin-top:4px;">Sets the calendar's month &amp; the default day.</div>
        </div>
        <div style="margin-bottom: 2px;">
          <div class="chip b-blue"><?= date('l, d F Y', strtotime($date)) ?></div>
        </div>
        <div class="flex gap8" style="margin-left: auto; margin-bottom: 2px;">
          <a href="/attendance/manager/register?month=<?= htmlspecialchars(date('Y-m', strtotime($date))) ?>" class="btn btn-outline" title="View the monthly manager attendance register (read-only)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            View Register
          </a>
          <button type="submit" class="btn btn-primary" style="padding: 10px 32px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Manager Attendance
          </button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Manager Name</th>
              <th>Status &amp; Days</th>
              <th>Notes / Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($managers as $m):
                // Find existing status for this specific date
                $existing = array_filter($history, function($h) use ($m, $date) {
                    return $h['user_id'] == $m['id'] && $h['attendance_date'] == $date;
                });
                $status = !empty($existing) ? array_values($existing)[0]['status'] : 'p';
                $note = !empty($existing) ? array_values($existing)[0]['note'] : '';
                $count = $plCounts[$m['id']] ?? 0;
            ?>
            <tr>
              <td class="bold fs14"><?= htmlspecialchars($m['full_name']) ?></td>
              <td>
                <div class="flex-center gap8">
                  <select name="manager_attendance[<?= $m['id'] ?>][status]"
                          class="form-input att-select <?= $status ?>"
                          style="width: 170px;"
                          onchange="this.className = 'form-input att-select ' + this.value">
                    <option value="p" style="background:#E7F1EC; color:#2F6B4F;" <?= $status == 'p' ? 'selected' : '' ?>>Present (P)</option>
                    <option value="off" style="background:#FBEDEA; color:#8C3324;" <?= $status == 'off' ? 'selected' : '' ?>>Off Duty (Off)</option>
                    <option value="h" style="background:#FBF1E0; color:#7A5310;" <?= $status == 'h' ? 'selected' : '' ?>>Half Day (H)</option>
                    <?php if ($count < 4 || $status == 'pl'): ?>
                        <option value="pl" style="background:#EAF1F8; color:#2E5478;" <?= $status == 'pl' ? 'selected' : '' ?>>Paid Leave (PL)</option>
                    <?php endif; ?>
                    <option value="sd" style="background:#F1EEF7; color:#4A3F72;" <?= $status == 'sd' ? 'selected' : '' ?>>Special Duty (SD)</option>
                  </select>
                  <button type="button" class="btn btn-sm btn-icon cal-btn" title="Pick days to apply this status" onclick="openCalendar('<?= $m['id'] ?>', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  </button>
                  <span class="chip b-green cal-count" id="count-<?= $m['id'] ?>" title="Selected days">1</span>
                </div>
                <input type="hidden" id="dates-<?= $m['id'] ?>" name="manager_attendance[<?= $m['id'] ?>][dates]" value="<?= htmlspecialchars($date) ?>">
              </td>
              <td>
                <input type="text" name="manager_attendance[<?= $m['id'] ?>][note]"
                       class="form-input"
                       value="<?= htmlspecialchars($note ?? '') ?>"
                       placeholder="Optional remarks...">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="flex gap16 fs12 c-secondary items-center" style="padding: 16px 20px; border-top: 1px solid var(--border);">
          <span class="flex items-center gap4"><span class="badge b-green" style="width:12px; height:12px; border-radius:2px; padding:0;"></span> Present</span>
          <span class="flex items-center gap4"><span class="badge b-gray" style="width:12px; height:12px; border-radius:2px; padding:0;"></span> Off Duty</span>
          <span class="flex items-center gap4"><span class="badge b-amber" style="width:12px; height:12px; border-radius:2px; padding:0;"></span> Half Day</span>
          <span class="flex items-center gap4"><span class="badge b-blue" style="width:12px; height:12px; border-radius:2px; padding:0;"></span> Paid Leave</span>
          <span class="flex items-center gap4"><span class="badge b-purple" style="width:12px; height:12px; border-radius:2px; padding:0;"></span> Special Duty (2x)</span>
      </div>
    </div>
  </form>
</div>

<!-- Floating multi-day calendar popup (shared, repositioned per manager) -->
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
  <div class="cal-foot">Click days to mark this manager. The chosen status applies to every selected day on Save.</div>
</div>

<script>
window.ATT_ANCHOR = '<?= htmlspecialchars($date) ?>';

// Changing the date reloads the page (GET) so the grid + calendar month re-render.
function reloadDate(value) {
    if (value) { location.href = '/attendance/manager?date=' + encodeURIComponent(value); }
}

(function () {
    var anchor   = new Date(window.ATT_ANCHOR + 'T00:00:00');
    var year     = anchor.getFullYear();
    var month    = anchor.getMonth(); // 0-based
    var monthName = ['January','February','March','April','May','June','July','August','September','October','November','December'][month];

    var activeRow = null;
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
        var selected = getDates(activeRow);
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
        var arr = getDates(activeRow);
        var idx = arr.indexOf(ds);
        if (idx >= 0) { arr.splice(idx, 1); cell.classList.remove('sel'); }
        else          { arr.push(ds);        cell.classList.add('sel'); }
        setDates(activeRow, arr);
    });

    window.openCalendar = function (id, btn) {
        activeRow = id;
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
        setDates(activeRow, arr); render();
    };
    window.calClear = function () { setDates(activeRow, []); render(); };
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
