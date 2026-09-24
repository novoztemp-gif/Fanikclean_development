<div class="panel active">
  <div class="sec-head">
    <div class="flex gap16">
      <div class="sec-meta">Total Workers: <span class="fw7 c-teal"><?= count($workers) ?></span></div>
    </div>
    <button class="btn btn-primary" onclick="openAddWorkerModal()">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
      Add New Worker
    </button>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllWorkers" onclick="toggleAllWorkers(this)"></th>
            <th>ID / Code</th>
            <th>Full Name</th>
            <th>ESI / PF</th>
            <th>Category</th>
            <th>Site / Location</th>
            <th>Contact</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($workers as $w): ?>
          <tr>
            <td class="text-center"><input type="checkbox" class="worker-checkbox" value="<?= $w['id'] ?>" onchange="updateBulkActionBar()"></td>
            <td class="mono c-secondary fs12"><?= htmlspecialchars($w['worker_code']) ?></td>
            <td class="bold">
              <a href="/workers/profile?id=<?= $w['id'] ?>" class="c-primary" style="text-decoration:none;">
                <?= htmlspecialchars($w['full_name']) ?>
              </a>
            </td>
            <td class="fs11">
              <div class="c-secondary">ESI: <?= htmlspecialchars($w['esi_number'] ?? 'N/A') ?></div>
              <div class="c-secondary">PF: <?= htmlspecialchars($w['pf_number'] ?? 'N/A') ?></div>
            </td>
            <td>
              <?php 
                $catClass = 'b-gray';
                if ($w['category_name'] == 'Supervisor') $catClass = 'b-purple';
                if ($w['category_name'] == 'Skilled') $catClass = 'b-blue';
                if ($w['category_name'] == 'Associate') $catClass = 'b-amber';
              ?>
              <span class="badge <?= $catClass ?>"><?= htmlspecialchars($w['category_name']) ?></span>
            </td>
            <td>
              <div class="flex-center gap8">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="c-secondary"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($w['site_name'] ?? 'Unassigned') ?>
              </div>
            </td>
            <td><?= htmlspecialchars($w['mobile']) ?></td>
            <td>
              <span class="badge <?= $w['status'] == 'Active' ? 'b-green' : 'b-red' ?>">
                <?= htmlspecialchars($w['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <div class="flex flex-end gap8">
                <a href="/workers/profile?id=<?= $w['id'] ?>" class="btn btn-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
                <button class="btn btn-sm" onclick='openEditWorkerModal(<?= json_encode($w) ?>)'>
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                </button>
                <?php if($w['status'] === 'Removed'): ?>
                  <form method="POST" action="/workers/restore" style="display:contents;">
                    <input type="hidden" name="id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Restore</button>
                  </form>
                  <?php if(empty($w['has_history'])): ?>
                    <form method="POST" action="/workers/permanent-delete" style="display:contents;" onsubmit="return confirm('Permanently delete “<?= htmlspecialchars($w['full_name'], ENT_QUOTES) ?>”? This erases their profile completely and cannot be undone. (They have no attendance or payroll history, so nothing else is affected.)');">
                      <input type="hidden" name="id" value="<?= $w['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete Permanently</button>
                    </form>
                  <?php endif; ?>
                <?php else: ?>
                  <form method="POST" action="/workers/delete" style="display:contents;" onsubmit="return confirm('Remove “<?= htmlspecialchars($w['full_name'], ENT_QUOTES) ?>”? They will be hidden from new attendance, payroll and site assignment; their existing history is kept and nothing is erased. You can restore them any time.');">
                    <input type="hidden" name="id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ADD/EDIT WORKER MODAL -->
  <div class="modal-overlay" id="modal-add-worker">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title" id="worker-modal-title">Worker Profile Expansion</div>
        <button type="button" class="modal-close" onclick="closeModal('modal-add-worker')">×</button>
      </div>

      <form method="POST" action="/workers/create" id="worker-form" enctype="multipart/form-data">
        <input type="hidden" name="id" id="worker-id">
        
        <div style="padding: 24px;">
          <div class="form-section-title">Personal Information & Photo</div>
          <div class="form-grid mb24">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input class="form-input" type="text" name="full_name" id="worker-full_name" placeholder="Legal full name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Contact Number</label>
              <input class="form-input" type="tel" name="mobile" id="worker-mobile" placeholder="10-digit number" required>
            </div>
            <div class="form-group">
              <label class="form-label">Aadhaar Card</label>
              <input class="form-input" type="text" name="aadhaar" id="worker-aadhaar" placeholder="12-digit number">
            </div>
            <div class="form-group">
              <label class="form-label">Profile Photo</label>
              <input class="form-input" type="file" name="photo" id="worker-photo" accept="image/*">
            </div>
            <div class="form-group">
              <label class="form-label">Age</label>
              <input class="form-input" type="number" name="age" id="worker-age" placeholder="Age in years">
            </div>
            <div class="form-group">
              <label class="form-label">Experience</label>
              <input class="form-input" type="text" name="experience" id="worker-experience" placeholder="e.g. 5 Years">
            </div>
          </div>

          <div class="form-section-title">Statutory & Asset Details</div>
          <div class="form-grid mb24">
            <div class="form-group">
              <label class="form-label">ESI Number</label>
              <input class="form-input" type="text" name="esi_number" id="worker-esi_number" placeholder="Statutory ESI">
            </div>
            <div class="form-group">
              <label class="form-label">PF Number</label>
              <input class="form-input" type="text" name="pf_number" id="worker-pf_number" placeholder="Provident Fund ID">
            </div>
            <div class="form-group">
              <label class="form-label">Uniform Issue Date</label>
              <input class="form-input" type="date" name="uniform_issue_date" id="worker-uniform_issue_date">
            </div>
            <div class="form-group">
              <label class="form-label">Uniform Details</label>
              <input class="form-input" type="text" name="uniform_details" id="worker-uniform_details" placeholder="Sizes, Quantity, etc.">
            </div>
          </div>

          <div class="form-section-title">Emergency / Guardian Details</div>
          <div class="form-grid mb24">
            <div class="form-group">
              <label class="form-label">Guardian Name</label>
              <input class="form-input" type="text" name="guardian_name" id="worker-guardian_name" placeholder="Name of Guardian">
            </div>
            <div class="form-group">
              <label class="form-label">Guardian Phone</label>
              <input class="form-input" type="tel" name="guardian_phone" id="worker-guardian_phone" placeholder="Contact Number">
            </div>
            <div class="form-group" style="grid-column: span 2;">
              <label class="form-label">Guardian Place</label>
              <input class="form-input" type="text" name="guardian_place" id="worker-guardian_place" placeholder="City / Town / Village">
            </div>
          </div>

          <div class="form-section-title">Employment & Alignment</div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Date of Joining</label>
              <input class="form-input" type="date" name="doj" id="worker-doj" required>
            </div>
            <div class="form-group">
              <label class="form-label">Skill Category</label>
              <select class="form-input" name="category_id" id="worker-category_id">
                <?php foreach($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="grid-column: span 2;">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Client</label>
                  <select class="form-input" id="worker-client_id" onchange="filterSitesByClient()">
                    <option value="">-- Select Client First --</option>
                    <?php foreach($clients as $c): ?>
                      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Assign to Site</label>
                  <select class="form-input" name="site_id" id="worker-site_id">
                    <option value="" data-client-id="">-- No Specific Site --</option>
                    <?php foreach($sites as $site): ?>
                      <option value="<?= $site['id'] ?>" data-client-id="<?= $site['client_id'] ?>"><?= htmlspecialchars($site['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-input" name="status" id="worker-status">
                <option value="Active">Active / On Duty</option>
                <option value="Inactive">Inactive / On Leave</option>
                <option value="Removed">Terminated / Removed</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-add-worker')">Discard</button>
          <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Confirm & Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterSitesByClient() {
    const clientId = document.getElementById('worker-client_id').value;
    const siteSelect = document.getElementById('worker-site_id');
    const options = siteSelect.querySelectorAll('option');
    
    let firstValidOption = null;
    
    options.forEach(opt => {
        if (!opt.value) {
            opt.style.display = ''; // Always show "No Specific Site"
            return;
        }
        if (!clientId || opt.getAttribute('data-client-id') === clientId) {
            opt.style.display = '';
            if (!firstValidOption) firstValidOption = opt;
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Automatically select the first valid site if the current selection is hidden
    const selectedOption = siteSelect.options[siteSelect.selectedIndex];
    if (selectedOption.style.display === 'none') {
        siteSelect.value = firstValidOption ? firstValidOption.value : '';
    }
}
</script>

<!-- Bulk Action Bar -->
<div id="bulk-action-bar" style="display:none; position:fixed; bottom:0; left:250px; right:0; background:var(--surface); border-top:1px solid var(--border-color); padding:15px 24px; box-shadow:0 -4px 15px rgba(0,0,0,0.05); z-index:100; align-items:center; justify-content:space-between;">
    <div style="display:flex; align-items:center; gap:16px;">
        <span id="bulk-count" class="badge b-primary" style="font-size:14px; padding:6px 12px;">0 Selected</span>
        <span class="fs14 fw6">Workers</span>
    </div>
    <div style="display:flex; gap:12px;">
        <button onclick="openBulkModal('modal-bulk-transfer')" class="btn btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="m5 14 6-6 6 6"/><path d="M12 8V22"/><path d="M5 2h14"/></svg>
            Transfer Sites
        </button>
        <button onclick="openBulkModal('modal-bulk-uniform')" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/></svg>
            Update Uniforms
        </button>
        <button onclick="clearBulkSelection()" class="btn btn-outline" style="border-color:transparent;">Cancel</button>
    </div>
</div>

<!-- Bulk Transfer Modal -->
<div class="modal-overlay" id="modal-bulk-transfer">
  <div class="modal" style="max-width: 500px;">
    <div class="modal-head">
      <div class="modal-title">Bulk Transfer Sites</div>
      <button class="modal-close" onclick="closeModal('modal-bulk-transfer')">&times;</button>
    </div>
    <form method="POST" action="/workers/bulk/transfer" id="form-bulk-transfer">
      <div style="padding: 24px;">
        <div id="transfer-hidden-inputs"></div>
        <p class="fs13 c-secondary mb16">Moving <strong id="transfer-count-label">0</strong> workers to a new site.</p>
        <div class="form-group">
            <label class="form-label">Client</label>
            <select class="form-input" id="bulk-client_id" onchange="filterBulkSites()">
                <option value="">-- Select Client --</option>
                <?php foreach($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Destination Site</label>
            <select class="form-input" name="site_id" id="bulk-site_id" required>
                <option value="" data-client-id="">-- Select Site --</option>
                <?php foreach($sites as $s): ?>
                    <option value="<?= $s['id'] ?>" data-client-id="<?= $s['client_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('modal-bulk-transfer')">Cancel</button>
          <button type="submit" class="btn btn-primary">Confirm Transfer</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Uniform Modal -->
<div class="modal-overlay" id="modal-bulk-uniform">
  <div class="modal" style="max-width: 500px;">
    <div class="modal-head">
      <div class="modal-title">Bulk Uniform Assignment</div>
      <button class="modal-close" onclick="closeModal('modal-bulk-uniform')">&times;</button>
    </div>
    <form method="POST" action="/workers/bulk/uniform" id="form-bulk-uniform">
      <div style="padding: 24px;">
        <div id="uniform-hidden-inputs"></div>
        <p class="fs13 c-secondary mb16">Updating uniforms for <strong id="uniform-count-label">0</strong> workers.</p>
        <div class="form-group">
            <label class="form-label">Uniform Details (Size/Type)</label>
            <input type="text" name="uniform_details" class="form-input" placeholder="e.g., XL Shirt, 32 Pants">
        </div>
        <div class="form-group">
            <label class="form-label">Issue Date</label>
            <input type="date" name="uniform_issue_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('modal-bulk-uniform')">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Uniforms</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function toggleAllWorkers(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.worker-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBulkActionBar();
}

function updateBulkActionBar() {
    const checkboxes = document.querySelectorAll('.worker-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const countLabel = document.getElementById('bulk-count');
    
    if (checkboxes.length > 0) {
        bar.style.display = 'flex';
        countLabel.textContent = checkboxes.length + ' Selected';
    } else {
        bar.style.display = 'none';
        document.getElementById('selectAllWorkers').checked = false;
    }
}

function clearBulkSelection() {
    document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllWorkers').checked = false;
    updateBulkActionBar();
}

function openBulkModal(modalId) {
    // Close any existing open modals first to prevent stacking
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
    
    const checkboxes = document.querySelectorAll('.worker-checkbox:checked');
    if (checkboxes.length === 0) return;
    
    const count = checkboxes.length;
    let inputsHtml = '';
    checkboxes.forEach(cb => {
        inputsHtml += `<input type="hidden" name="worker_ids[]" value="${cb.value}">`;
    });
    
    if (modalId === 'modal-bulk-transfer') {
        document.getElementById('transfer-hidden-inputs').innerHTML = inputsHtml;
        document.getElementById('transfer-count-label').textContent = count;
    } else if (modalId === 'modal-bulk-uniform') {
        document.getElementById('uniform-hidden-inputs').innerHTML = inputsHtml;
        document.getElementById('uniform-count-label').textContent = count;
    }
    
    openModal(modalId);
}

function filterBulkSites() {
    const clientId = document.getElementById('bulk-client_id').value;
    const siteSelect = document.getElementById('bulk-site_id');
    const options = siteSelect.querySelectorAll('option');
    
    let firstValidOption = null;
    
    options.forEach(opt => {
        if (!opt.value) { 
            opt.hidden = false;
            opt.disabled = false;
            return; 
        }
        if (!clientId || opt.getAttribute('data-client-id') === clientId) {
            opt.hidden = false;
            opt.disabled = false;
            if (!firstValidOption) firstValidOption = opt;
        } else {
            opt.hidden = true;
            opt.disabled = true;
        }
    });
    
    const selected = siteSelect.options[siteSelect.selectedIndex];
    if (selected && selected.hidden) {
        siteSelect.value = firstValidOption ? firstValidOption.value : '';
    }
}
</script>
