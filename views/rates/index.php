<div class="panel active">
    <!-- Header -->
    <div class="sec-head">
        <div class="sec-meta">Configure base salary rates for each worker category.</div>
    </div>

    <!-- Rate Master Table -->
    <div class="card mb24">
        <div class="card-head">
            <div class="card-title">Worker Category Rates</div>
            <div class="fs11 c-secondary">System-wide default daily rates</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th class="text-right">Current Daily Rate (Basic)</th>
                        <th class="text-right">Standard 8hr OT Rate</th>
                        <th class="text-right">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $cat): 
                        $otRate = $cat['default_rate'] / 8;
                    ?>
                    <tr>
                        <td class="bold"><?= htmlspecialchars($cat['name']) ?></td>
                        <td class="mono fw7 c-teal text-right">₹<?= number_format($cat['default_rate'], 2) ?></td>
                        <td class="mono fs12 c-secondary text-right">₹<?= number_format($otRate, 2) ?>/hr</td>
                        <td class="text-right">
                            <button class="btn btn-sm" onclick="openRateModal('<?= $cat['id'] ?>', '<?= $cat['name'] ?>', '<?= $cat['default_rate'] ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Update
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Config Info -->
    <div class="card">
        <div class="card-head">
            <div class="card-title">Taxation & GST Settings</div>
        </div>
        <div style="padding: 24px;">
            <div class="flex-between mb16">
                <div>
                    <div class="fw6">Standard Service Tax (GST)</div>
                    <div class="fs12 c-secondary">Applied globally to all billing runs unless client-exempt.</div>
                </div>
                <div class="badge b-blue" style="font-size: 14px; padding: 6px 16px;">18.00%</div>
            </div>
            <div class="flex-between">
                <div>
                    <div class="fw6">CGST / SGST Split</div>
                    <div class="fs12 c-secondary">Applicable for Local (Tamil Nadu) billing.</div>
                </div>
                <div class="badge b-gray" style="font-size: 14px; padding: 6px 16px;">9.00% + 9.00%</div>
            </div>
        </div>
    </div>

    <!-- UPDATE RATE MODAL -->
    <div class="modal-overlay" id="modal-rate">
        <div class="modal">
            <div class="modal-head">
                <div class="modal-title">Update Category Rate</div>
                <button type="button" class="modal-close" onclick="closeModal('modal-rate')">×</button>
            </div>
            <form method="POST" action="/rates/updateDefault">
                <input type="hidden" name="id" id="rate-cat-id">
                <div style="padding: 24px;">
                    <div class="form-group mb16">
                        <label class="form-label" id="rate-cat-name-label">Category</label>
                        <div class="flex-center gap8">
                            <span class="fs14">₹</span>
                            <input class="form-input" type="number" step="0.01" name="rate" id="rate-cat-val" required>
                        </div>
                    </div>
                    <p class="fs11 c-secondary mb16">Note: Changing the default rate will NOT affect already computed payrolls but will apply to all future attendance marking.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="border:none; background:transparent;" onclick="closeModal('modal-rate')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRateModal(id, name, val) {
    document.getElementById('rate-cat-id').value = id;
    document.getElementById('rate-cat-name-label').textContent = 'Daily Rate for ' + name;
    document.getElementById('rate-cat-val').value = val;
    openModal('modal-rate');
}
</script>
