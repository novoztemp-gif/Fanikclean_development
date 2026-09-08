<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Review your attendance records for the selected period. These logs are maintained by system administrators.</div>
    <form method="GET" action="/attendance/my" class="flex gap12">
      <input type="month" name="month" value="<?= $month ?>" class="form-input" onchange="this.form.submit()">
    </form>
  </div>

  <?php
     $present = count(array_filter($history, fn($h) => $h['status'] == 'p'));
     $absent  = count(array_filter($history, fn($h) => $h['status'] == 'a'));
     $half    = count(array_filter($history, fn($h) => $h['status'] == 'h'));
     $off     = count(array_filter($history, fn($h) => $h['status'] == 'off'));
  ?>
  <div class="statline">
    <div class="statcell">
      <div class="stat-label">Days Present</div>
      <div class="stat-val" data-count="<?= $present ?>">0</div>
    </div>
    <div class="statcell">
      <div class="stat-label">Days Absent</div>
      <div class="stat-val" data-count="<?= $absent ?>">0</div>
    </div>
    <div class="statcell">
      <div class="stat-label">Half Days</div>
      <div class="stat-val" data-count="<?= $half ?>">0</div>
    </div>
    <div class="statcell">
      <div class="stat-label">Weekly Off</div>
      <div class="stat-val" data-count="<?= $off ?>">0</div>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Attendance Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($history)): ?>
            <tr><td colspan="4" class="text-center c-secondary p24">No attendance records found for this month.</td></tr>
          <?php endif; ?>
          <?php foreach($history as $h): ?>
          <tr>
            <td class="bold"><?= date('d M Y', strtotime($h['attendance_date'])) ?></td>
            <td class="c-secondary"><?= date('l', strtotime($h['attendance_date'])) ?></td>
            <td>
              <?php if($h['status'] == 'p'): ?>
                <span class="dot dot-ok"></span>Present
              <?php elseif($h['status'] == 'a'): ?>
                <span class="dot dot-bad"></span>Absent
              <?php elseif($h['status'] == 'h'): ?>
                <span class="dot dot-warn"></span>Half Day
              <?php elseif($h['status'] == 'off'): ?>
                <span class="badge b-gray">Weekly Off</span>
              <?php endif; ?>
            </td>
            <td class="italic fs13 c-secondary"><?= htmlspecialchars($h['note'] ?? '') ?: '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
