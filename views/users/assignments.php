<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Assign multiple sites to each manager to define their operational scope.</div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Manager Name</th>
            <th>Email</th>
            <th>Assigned Sites</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($managers as $m): ?>
          <tr>
            <td class="bold"><?= htmlspecialchars($m['full_name']) ?></td>
            <td class="c-secondary fs13"><?= htmlspecialchars($m['email']) ?></td>
            <td>
              <?php if(empty($m['assigned_site_ids'])): ?>
                <span class="chip" style="background: var(--gray-lighter); color: var(--text-muted);">No sites assigned</span>
              <?php else: ?>
                <div class="flex flex-wrap gap4">
                  <?php 
                    foreach($sites as $s) {
                      if(in_array($s['id'], $m['assigned_site_ids'])) {
                        echo '<span class="chip b-blue">' . htmlspecialchars($s['name']) . '</span>';
                      }
                    }
                  ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <button class="btn btn-sm" onclick='openAssignmentModal(<?= json_encode($m) ?>)'>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Mapping
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ASSIGNMENT MODAL -->
  <div class="modal-overlay" id="modal-assignments">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title">Site Assignments: <span id="assign-mgr-name" class="c-primary"></span></div>
        <button type="button" class="modal-close" onclick="closeModal('modal-assignments')">×</button>
      </div>

      <form method="POST" action="/users/assignments/save">
        <input type="hidden" name="user_id" id="assign-user-id">

        <div style="padding: 24px;">
          <div class="flex-between mb12" style="flex-wrap: wrap; gap: 8px;">
            <label class="form-label" style="margin:0;">Select Sites for this Manager</label>
            <div class="fs11 c-secondary"><b id="assign-site-count">0</b> selected · <a href="#" onclick="siteCheckboxSelectAll('assign', true); return false;" style="color:var(--primary-text);">select visible</a> · <a href="#" onclick="siteCheckboxSelectAll('assign', false); return false;" style="color:var(--primary-text);">clear all</a></div>
          </div>
          <input type="text" class="form-input mb12" id="assign-site-search" placeholder="Search sites by name..." oninput="filterSiteCheckboxes('assign')">
          <div id="assign-sites-list" class="site-picker-grid">
            <?php foreach($sites as $s): ?>
            <label class="site-picker-item" data-site-name="<?= htmlspecialchars(strtolower($s['name'])) ?>">
              <input type="checkbox" name="site_ids[]" value="<?= $s['id'] ?>" class="site-chk" id="site-<?= $s['id'] ?>" onchange="updateSiteCheckboxCount('assign')">
              <span class="fs13"><?= htmlspecialchars($s['name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div id="assign-sites-empty" class="fs12 c-secondary text-center" style="padding: 16px;" hidden>No sites match your search.</div>
          <p class="fs11 c-secondary mt16">Check all sites the manager is responsible for. Clearing all will restrict the manager to no site access.</p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-assignments')">Cancel</button>
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Update Assignments</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAssignmentModal(m) {
    document.getElementById('assign-user-id').value = m.id;
    document.getElementById('assign-mgr-name').textContent = m.full_name;
    document.getElementById('assign-site-search').value = '';

    document.querySelectorAll('#assign-sites-list .site-chk').forEach(chk => { chk.checked = false; });
    if (m.assigned_site_ids) {
        m.assigned_site_ids.forEach(sid => {
            const el = document.getElementById('site-' + sid);
            if (el) el.checked = true;
        });
    }
    filterSiteCheckboxes('assign');
    updateSiteCheckboxCount('assign');

    openModal('modal-assignments');
}
</script>
