<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Manage administrative access and site assignments.</div>
    <div class="flex gap8">
      <button class="btn btn-outline" onclick="openModal('modal-credentials')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
        Credential Management
      </button>
      <button class="btn btn-primary" onclick="openAddUserModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
        Add New System User
      </button>
    </div>
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
                <?= $u['status'] == 'Inactive' ? 'Suspended' : htmlspecialchars($u['status']) ?>
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
                <?php $isSelf = (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0); ?>
                <?php if ($u['status'] === 'Active'): ?>
                  <?php if ($isSelf): ?>
                    <span class="fs11 c-secondary" style="align-self:center;" title="You can't suspend your own account">This is you</span>
                  <?php else: ?>
                    <form method="POST" action="/users/suspend" onsubmit="return confirm('Suspend <?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>? They will be blocked from logging in and hidden from assignment lists, but their data is kept. This can be undone.');">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" title="Suspend">Suspend</button>
                    </form>
                  <?php endif; ?>
                <?php elseif ($u['status'] === 'Inactive'): ?>
                  <form method="POST" action="/users/reactivate">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary" title="Reactivate">Reactivate</button>
                  </form>
                  <?php if (!$isSelf): ?>
                    <form method="POST" action="/users/delete" onsubmit="return confirm('Permanently delete <?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>? They will never be able to log in or be restored again. Their attendance, billing and audit history is kept and nothing is erased.');">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
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
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title" id="user-modal-title">System User Controls</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-edit-user')">×</button>
      </div>

      <form method="POST" action="/users/update" id="user-form">
        <input type="hidden" name="id" id="edit-user-id">
        
        <div style="padding: 24px;">
          <div class="form-grid mb16">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input class="form-input" type="text" name="full_name" id="edit-user-name" placeholder="Staff Name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email (Login Username)</label>
              <input class="form-input" type="email" name="email" id="edit-user-email" placeholder="email@example.com" required>
            </div>
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

          <div class="form-group mb16">
            <label class="form-label">System Role</label>
            <select class="form-input" name="role_id" id="edit-user-role">
              <?php foreach($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="fs11 c-secondary mb16">Use the Suspend / Delete buttons on the user list to change account status.</p>

          <div class="form-group">
            <div class="flex-between mb12" style="flex-wrap: wrap; gap: 8px;">
              <label class="form-label" style="margin:0;">Assigned Sites (For Managers)</label>
              <div class="fs11 c-secondary"><b id="edituser-site-count">0</b> selected · <a href="#" onclick="siteCheckboxSelectAll('edituser', true); return false;" style="color:var(--primary-text);">select visible</a> · <a href="#" onclick="siteCheckboxSelectAll('edituser', false); return false;" style="color:var(--primary-text);">clear all</a></div>
            </div>
            <input type="text" class="form-input mb12" id="edituser-site-search" placeholder="Search sites by name..." oninput="filterSiteCheckboxes('edituser')">
            <div id="edituser-sites-list" class="site-picker-grid">
              <?php foreach($sites as $s): ?>
                <label class="site-picker-item" data-site-name="<?= htmlspecialchars(strtolower($s['name'])) ?>">
                  <input type="checkbox" name="site_ids[]" value="<?= $s['id'] ?>" class="site-chk" id="edituser-site-<?= $s['id'] ?>" onchange="updateSiteCheckboxCount('edituser')">
                  <span class="fs13"><?= htmlspecialchars($s['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div id="edituser-sites-empty" class="fs12 c-secondary text-center" style="padding: 16px;" hidden>No sites match your search.</div>
            <p class="fs11 c-secondary mt8">Admins access all sites regardless of selection. Bulk-edit is also available on the Site Assignments screen.</p>
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

  <!-- CREDENTIAL MANAGEMENT WINDOW -->
  <div class="modal-overlay" id="modal-credentials">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title">Credential Management</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-credentials')">×</button>
      </div>
      <div style="padding: 0 24px 24px;">
        <p class="fs12 c-secondary mb16">
          Set a new login password for any Manager. Whatever you enter here becomes their password immediately — there's no separate temporary password, and no way to view an existing password (it's stored hashed, not in plain text).
        </p>
        <?php $managers = array_filter($users, fn($u) => (int)$u['role_id'] === 2); ?>
        <?php if (empty($managers)): ?>
          <div class="fs12 italic c-secondary">No managers registered yet.</div>
        <?php else: ?>
          <div class="manage-sites-list">
            <?php foreach($managers as $u): ?>
              <div class="manage-site-row">
                <div>
                  <div class="bold fs13"><?= htmlspecialchars($u['full_name']) ?></div>
                  <div class="fs11 c-secondary"><?= htmlspecialchars($u['email']) ?></div>
                </div>
                <div class="flex gap8 items-center">
                  <span class="badge <?= $u['status'] == 'Active' ? 'b-green' : 'b-red' ?>">
                    <?= $u['status'] == 'Inactive' ? 'Suspended' : htmlspecialchars($u['status']) ?>
                  </span>
                  <button type="button" class="btn btn-sm btn-outline" onclick="openSetPasswordModal(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['full_name']), ENT_QUOTES) ?>)">
                    Reset Password
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" onclick="closeModal('modal-credentials')">Close</button>
      </div>
    </div>
  </div>

  <!-- SET PASSWORD MODAL -->
  <div class="modal-overlay" id="modal-set-password">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-title">Reset Password — <span id="setpw-user-name"></span></div>
        <button type="button" class="modal-close" onclick="closeModal('modal-set-password')">×</button>
      </div>
      <form method="POST" action="/users/reset-password" id="set-password-form" onsubmit="return validateSetPasswordForm()">
        <input type="hidden" name="id" id="setpw-user-id">
        <div style="padding: 24px;">
          <div class="form-group mb16">
            <label class="form-label">New Password</label>
            <input class="form-input" type="password" name="password" id="setpw-password" placeholder="At least 6 characters" minlength="6" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input class="form-input" type="password" name="password_confirm" id="setpw-password-confirm" placeholder="Re-enter password" minlength="6" required>
          </div>
          <p class="fs11 c-secondary mt16">This immediately replaces their current password. They'll need to sign in with the new one next time.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-set-password')">Cancel</button>
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Set Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
