<div class="panel active">
  <div class="sec-head">
    <div class="sec-meta">Client Directory & Sites</div>
    <div class="flex gap8">
      <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-client')">+ Add Client</button>
      <button class="btn btn-outline btn-sm" onclick="openAddSiteModal()">+ Add Site</button>
    </div>
  </div>

  <?php if(empty($clients)): ?>
    <div class="card"><div class="empty"><p>No clients found. Add one above.</p></div></div>
  <?php endif; ?>

  <div class="client-list">
    <?php foreach($clients as $c): ?>
    <div class="client-card">
      <div class="client-card-head">
        <div class="client-id-block">
          <div class="mono fs11 c-secondary mb4">C-<?= htmlspecialchars($c['id']) ?></div>
          <div class="bold fs15"><?= htmlspecialchars($c['company_name']) ?></div>
          <div class="fs11 c-secondary"><?= htmlspecialchars($c['gstin'] ?: 'No GSTIN') ?></div>
        </div>
        <div class="client-contact-block">
          <div class="bold fs13"><?= htmlspecialchars($c['contact_person'] ?? '') ?></div>
          <div class="fs12 c-secondary"><?= htmlspecialchars($c['email'] ?? '') ?></div>
          <div class="fs12 bold"><?= htmlspecialchars($c['mobile'] ?? '') ?></div>
        </div>
        <div class="flex gap8">
          <button class="btn btn-sm btn-outline" onclick="openAddSiteModal(<?= $c['id'] ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M12 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
            Add Site
          </button>
          <button class="btn btn-sm btn-outline" onclick="openManageSitesModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['company_name']), ENT_QUOTES) ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Manage Site
          </button>
        </div>
      </div>

      <?php if(empty($c['sites'])): ?>
        <span class="fs12 italic c-secondary">No sites registered</span>
      <?php else: ?>
        <div class="client-sites-label fs11 mb8">Locations (Sites) &middot; <?= count($c['sites']) ?></div>
        <div class="site-grid">
          <?php foreach($c['sites'] as $s): $active = !array_key_exists('is_active', $s) || $s['is_active']; ?>
            <div class="site-chip" style="<?= $active ? '' : 'opacity:.55;' ?>">
              <div class="flex-col">
                <span class="bold fs13">
                  <?= htmlspecialchars($s['name']) ?>
                  <?php if(!$active): ?><span class="badge b-red fs10">Inactive</span><?php endif; ?>
                </span>
                <?php if(!empty($s['address'])): ?><span class="fs11 c-secondary"><?= htmlspecialchars($s['address']) ?></span><?php endif; ?>
              </div>
              <div class="badge b-gray fs10">ID: <?= $s['id'] ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
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

  <!-- MANAGE SITES MODAL (per-client: delete / restore) -->
  <div class="modal-overlay" id="modal-manage-sites">
    <div class="modal modal-lg">
      <div class="modal-head">
        <div class="modal-title">Manage Sites &mdash; <span id="manage-sites-client-name"></span></div>
        <button type="button" class="modal-close" onclick="closeModal('modal-manage-sites')">×</button>
      </div>
      <div style="padding: 20px 24px;">
        <p class="fs12 c-secondary mb16">
          Deleting a site only hides it from new selections (attendance, billing, payroll, worker &amp; manager assignment). Its attendance, billing and invoice history is kept and nothing is erased. You can restore it any time.
        </p>
        <?php if(empty($clients)): ?>
          <div class="fs12 italic c-secondary">No clients registered yet.</div>
        <?php else: ?>
          <input type="text" class="form-input mb12" id="manage-sites-search" placeholder="Search sites by name or address..." oninput="filterManageSites()">
          <div id="manage-sites-empty" class="fs12 italic c-secondary" hidden>No sites match.</div>
          <div class="manage-sites-list">
            <?php foreach($clients as $c): foreach($c['sites'] as $s): $active = !array_key_exists('is_active', $s) || $s['is_active']; ?>
              <div class="manage-site-row" data-client-id="<?= $c['id'] ?>" data-search="<?= htmlspecialchars(strtolower($s['name'] . ' ' . ($s['address'] ?? ''))) ?>">
                <div>
                  <div class="bold fs13"><?= htmlspecialchars($s['name']) ?> <span class="mono fs10 c-secondary">#<?= $s['id'] ?></span></div>
                  <?php if(!empty($s['address'])): ?><div class="fs11 c-secondary"><?= htmlspecialchars($s['address']) ?></div><?php endif; ?>
                </div>
                <div class="flex gap8 items-center">
                  <?php if($active): ?>
                    <span class="badge b-green fs10">Active</span>
                    <form method="POST" action="/sites/delete" onsubmit="return confirm('Delete “<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>”? It will be hidden from new selections; its history is kept and nothing is erased.');">
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  <?php else: ?>
                    <span class="badge b-red fs10">Deleted</span>
                    <form method="POST" action="/sites/restore">
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary">Restore</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; endforeach; ?>
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

let manageSitesClientId = null;

function openManageSitesModal(clientId, companyName) {
    manageSitesClientId = clientId;
    document.getElementById('manage-sites-client-name').textContent = companyName;
    const searchInput = document.getElementById('manage-sites-search');
    if (searchInput) searchInput.value = '';
    filterManageSites();
    openModal('modal-manage-sites');
}

function filterManageSites() {
    const searchInput = document.getElementById('manage-sites-search');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const rows = document.querySelectorAll('.manage-site-row');
    let visibleCount = 0;
    rows.forEach(row => {
        const matchesClient = String(row.dataset.clientId) === String(manageSitesClientId);
        const matchesSearch = !query || row.dataset.search.includes(query);
        const match = matchesClient && matchesSearch;
        row.hidden = !match;
        if (match) visibleCount++;
    });
    document.getElementById('manage-sites-empty').hidden = visibleCount !== 0;
}
</script>
