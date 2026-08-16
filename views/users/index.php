<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Manage administrative access and site assignments.</div>
    <button class="btn btn-primary" onclick="openAddUserModal()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
      Add New System User
    </button>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>System Operator</th>
            <th>Email Access</th>
            <th>Platform Role</th>
            <th>Assigned Scope</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td class="bold"><?= htmlspecialchars($u['full_name']) ?></td>
            <td class="c-secondary fs13"><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="badge <?= $u['role_id'] == 1 ? 'b-indigo' : 'b-amber' ?>">
                <?= htmlspecialchars($u['role_name']) ?>
              </span>
            </td>
            <td>
              <?php if ($u['role_id'] == 1): ?>
                <span class="chip b-indigo" title="Admins have access to every site">All Sites (Admin)</span>
              <?php elseif (!empty($u['assigned_site_count'])): ?>
                <span class="chip b-green" title="<?= htmlspecialchars($u['assigned_site_names']) ?>">
                  <?= (int)$u['assigned_site_count'] ?> site<?= $u['assigned_site_count'] > 1 ? 's' : '' ?>
                </span>
              <?php else: ?>
                <span class="chip b-red" title="Assign sites at Site Assignments">No sites</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $u['status'] == 'Active' ? 'b-green' : 'b-red' ?>">
                <?= htmlspecialchars($u['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <div class="flex flex-end gap8">
                <a href="/users/profile?id=<?= $u['id'] ?>" class="btn btn-sm" title="View Profile">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
                <button class="btn btn-sm" onclick='openEditUserModal(<?= json_encode($u) ?>)' title="Configure">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MANAGE USER MODAL -->
  <div class="modal-overlay" id="modal-edit-user">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-title" id="user-modal-title">System User Controls</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-edit-user')">×</button>
      </div>

      <form method="POST" action="/users/update" id="user-form">
        <input type="hidden" name="id" id="edit-user-id">
        
        <div style="padding: 24px;">
          <div class="form-group mb16">
            <label class="form-label">Full Name</label>
            <input class="form-input" type="text" name="full_name" id="edit-user-name" placeholder="Staff Name" required>
          </div>
          
          <div class="form-section-title mt24 mb16" style="font-weight: 600; color: var(--text);">Emergency / Guardian Details</div>
          <div class="form-grid mb16">
            <div class="form-group">
              <label class="form-label">Guardian Name</label>
              <input class="form-input" type="text" name="guardian_name" id="edit-user-guardian_name" placeholder="Name of Guardian">
            </div>
            <div class="form-group">
              <label class="form-label">Guardian Phone</label>
              <input class="form-input" type="tel" name="guardian_phone" id="edit-user-guardian_phone" placeholder="Contact Number">
            </div>
            <div class="form-group" style="grid-column: span 2;">
              <label class="form-label">Guardian Place</label>
              <input class="form-input" type="text" name="guardian_place" id="edit-user-guardian_place" placeholder="City / Town / Village">
            </div>
          </div>
          
          <div class="form-section-title mt24 mb16" style="font-weight: 600; color: var(--text);">System Configuration</div>

          <div class="form-grid mb16">
            <div class="form-group">
              <label class="form-label">System Role</label>
              <select class="form-input" name="role_id" id="edit-user-role">
                <?php foreach($roles as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-input" name="status" id="edit-user-status">
                <option value="Active">Active</option>
                <option value="Inactive">Suspended</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Assigned Sites (For Managers)</label>
            <select class="form-input" name="site_ids[]" id="edit-user-sites" multiple size="6" style="height:auto;">
              <?php foreach($sites as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="fs11 c-secondary mt8">Hold Ctrl (Windows) or Cmd (Mac) to select multiple sites. Admins access all sites regardless of selection. Bulk-edit is also available on the Site Assignments screen.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-edit-user')">Discard</button>
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Confirm Updates</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ADD USER MODAL -->
  <div class="modal-overlay" id="modal-add-user">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-title">Create New System User</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-add-user')">×</button>
      </div>

      <form method="POST" action="/users/create" id="add-user-form">
        <div style="padding: 24px;">
          <div class="form-group mb16">
            <label class="form-label">Full Name</label>
            <input class="form-input" type="text" name="full_name" placeholder="Enter Full Name" required>
          </div>
          
          <div class="form-group mb16">
            <label class="form-label">Email Address</label>
            <input class="form-input" type="email" name="email" placeholder="email@example.com" required>
          </div>
          
          <div class="form-section-title mt24 mb16" style="font-weight: 600; color: var(--text);">Emergency / Guardian Details</div>
          <div class="form-grid mb16">
            <div class="form-group">
              <label class="form-label">Guardian Name</label>
              <input class="form-input" type="text" name="guardian_name" id="add-user-guardian_name" placeholder="Name of Guardian">
            </div>
            <div class="form-group">
              <label class="form-label">Guardian Phone</label>
              <input class="form-input" type="tel" name="guardian_phone" id="add-user-guardian_phone" placeholder="Contact Number">
            </div>
            <div class="form-group" style="grid-column: span 2;">
              <label class="form-label">Guardian Place</label>
              <input class="form-input" type="text" name="guardian_place" id="add-user-guardian_place" placeholder="City / Town / Village">
            </div>
          </div>
          
          <div class="form-section-title mt24 mb16" style="font-weight: 600; color: var(--text);">System Configuration</div>

          <div class="form-grid mb16">
            <div class="form-group">
              <label class="form-label">Temporary Password</label>
              <input class="form-input" type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label class="form-label">System Role</label>
              <select class="form-input" name="role_id" required>
                <?php foreach($roles as $r): ?>
                  <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Assigned Sites (For Managers)</label>
            <select class="form-input" name="site_ids[]" multiple size="6" style="height:auto;">
              <?php foreach($sites as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="fs11 c-secondary mt8">Optional now — you can also assign sites later from the Site Assignments screen. Admins access all sites regardless.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-add-user')">Cancel</button>
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Register User</button>
        </div>
      </form>
    </div>
  </div>
</div>
