<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Client Directory & Sites</div>
    <div class="flex gap8">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-client')">+ Add Client</button>
      <button class="btn btn-outline btn-sm" onclick="openModal('modal-add-site')">+ Add Site</button>
      <button class="btn btn-outline btn-sm" onclick="openModal('modal-manage-sites')">Manage Sites</button>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Client Information</th>
            <th>Contact Details</th>
            <th>Locations (Sites)</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($clients)): ?>
            <tr><td colspan="4" style="text-align:center;">No clients found. Add one above.</td></tr>
          <?php endif; ?>
          <?php foreach($clients as $c): ?>
          <tr style="border-top: 2px solid var(--gray-lighter);">
            <td>
              <div class="mono fs11 c-secondary mb4">C-<?= htmlspecialchars($c['id']) ?></div>
              <div class="bold fs15"><?= htmlspecialchars($c['company_name']) ?></div>
              <div class="fs11 c-secondary"><?= htmlspecialchars($c['gstin'] ?: 'No GSTIN') ?></div>
            </td>
            <td>
              <div class="bold fs13"><?= htmlspecialchars($c['contact_person']) ?></div>
              <div class="fs12 c-secondary"><?= htmlspecialchars($c['email']) ?></div>
              <div class="fs12 bold"><?= htmlspecialchars($c['mobile']) ?></div>
            </td>
            <td>
              <?php if(empty($c['sites'])): ?>
                <span class="fs12 italic c-secondary">No sites registered</span>
              <?php else: ?>
                <div class="flex flex-col gap8">
                  <?php foreach($c['sites'] as $s): $active = !array_key_exists('is_active', $s) || $s['is_active']; ?>
                    <div class="p8 border rounded flex justify-between items-center bg-light" style="<?= $active ? '' : 'opacity:.55;' ?>">
                      <div class="flex flex-col">
                        <span class="bold fs13">
                          <?= htmlspecialchars($s['name']) ?>
                          <?php if(!$active): ?><span class="badge b-red fs10">Inactive</span><?php endif; ?>
                        </span>
                        <span class="fs11 c-secondary"><?= htmlspecialchars($s['address']) ?></span>
                      </div>
                      <div class="badge b-gray fs10">ID: <?= $s['id'] ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <button class="btn btn-sm btn-outline mb8" onclick="openAddSiteModal(<?= $c['id'] ?>)" style="width: 100%;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M12 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                Add Site
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ADD CLIENT MODAL -->
  <div class="modal-overlay" id="modal-add-client">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title">Add New Client</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-add-client')">×</button>
      </div>

      <form method="POST" action="/clients/create">
        <div class="form-section-title">Client Details</div>
        <div class="form-grid mb16">
          <div class="form-group"><label class="form-label">Company Name</label><input class="form-input" type="text" name="company_name" placeholder="e.g. Sunrise Hospital" required></div>
          <div class="form-group"><label class="form-label">Contact Person</label><input class="form-input" type="text" name="contact_person" placeholder="Full name"></div>
          <div class="form-group"><label class="form-label">Mobile</label><input class="form-input" type="tel" name="mobile" placeholder="10-digit"></div>
          <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" placeholder="contact@company.com"></div>
          <div class="form-group"><label class="form-label">GSTIN</label><input class="form-input" type="text" name="gstin" placeholder="29XXXXX1234F1Z5"></div>
          <div class="form-group"><label class="form-label">Address</label><input class="form-input" type="text" name="address" placeholder="Full address"></div>
        </div>

        <div class="form-section-title">Contract Options</div>
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Contract Start</label><input class="form-input" name="contract_start" type="date"></div>
          <div class="form-group"><label class="form-label">Contract End</label><input class="form-input" name="contract_end" type="date"></div>
          <div class="form-group"><label class="form-label">Billing Cycle</label>
            <select class="form-input" name="billing_cycle">
              <option value="Monthly">Monthly</option>
              <option value="Fortnightly">Fortnightly</option>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="margin-top: 20px;">
          <button type="button" class="btn btn-sm" onclick="closeModal('modal-add-client')">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Client</button>
        </div>
      </form>

    </div>
  </div>

  <!-- ADD SITE MODAL -->
  <div class="modal-overlay" id="modal-add-site">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-title">Add New Operational Site</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-add-site')">×</button>
      </div>
      <form method="POST" action="/sites/create">
        <div style="padding: 24px;">
          <div class="form-group mb16">
            <label class="form-label">Parent Client</label>
            <select class="form-input" name="client_id" id="site-client-select" required>
              <option value="">-- Select Client --</option>
              <?php foreach($clients as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb16">
            <label class="form-label">Site Identifier (Name)</label>
            <input class="form-input" type="text" name="name" required placeholder="e.g. Main Plant, Wing B">
          </div>
          <div class="form-group">
            <label class="form-label">Physical Location (Address)</label>
            <input class="form-input" type="text" name="address" placeholder="Full address of this site">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm" style="border:none; background:transparent;" onclick="closeModal('modal-add-site')">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 24px;">Deploy Site</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MANAGE SITES MODAL (deactivate / reactivate) -->
  <div class="modal-overlay" id="modal-manage-sites">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title">Manage Sites</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-manage-sites')">×</button>
      </div>
      <div style="padding: 20px 24px;">
        <p class="fs12 c-secondary mb16">
          Deactivating a site hides it from all new selections (attendance, billing, payroll, worker &amp; manager assignment) but keeps its history. You can reactivate it any time. Nothing is deleted.
        </p>
        <?php
          $allSites = [];
          foreach($clients as $c) {
            foreach($c['sites'] as $s) {
              $s['company_name'] = $c['company_name'];
              $allSites[] = $s;
            }
          }
        ?>
        <?php if(empty($allSites)): ?>
          <div class="fs12 italic c-secondary">No sites registered yet. Use “+ Add Site” first.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Site</th>
                  <th>Client</th>
                  <th>Status</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($allSites as $s): $active = !array_key_exists('is_active', $s) || $s['is_active']; ?>
                  <tr>
                    <td>
                      <div class="bold fs13"><?= htmlspecialchars($s['name']) ?> <span class="mono fs10 c-secondary">#<?= $s['id'] ?></span></div>
                      <div class="fs11 c-secondary"><?= htmlspecialchars($s['address'] ?: '—') ?></div>
                    </td>
                    <td class="fs12"><?= htmlspecialchars($s['company_name']) ?></td>
                    <td>
                      <?php if($active): ?>
                        <span class="badge b-green fs10">Active</span>
                      <?php else: ?>
                        <span class="badge b-red fs10">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if($active): ?>
                        <form method="POST" action="/sites/delete" style="display:inline;" onsubmit="return confirm('Deactivate “<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>”? It will be hidden from new selections but its history is kept.');">
                          <input type="hidden" name="id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline">Deactivate</button>
                        </form>
                      <?php else: ?>
                        <form method="POST" action="/sites/restore" style="display:inline;">
                          <input type="hidden" name="id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-primary">Reactivate</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" onclick="closeModal('modal-manage-sites')">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function openAddSiteModal(clientId = null) {
    if (clientId) {
        document.getElementById('site-client-select').value = clientId;
    } else {
        document.getElementById('site-client-select').value = '';
    }
    openModal('modal-add-site');
}
</script>
