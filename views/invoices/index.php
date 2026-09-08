<div class="panel active">
    <!-- Section 1: Data ready to be Invoiced -->
    <div class="card mb24">
        <div class="card-head">
            <div class="card-title">Pending Billing Actions</div>
            <div class="fs11 c-secondary">Ready to generate official invoices</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                  <tr>
                    <th>Client / Organization</th>
                    <th>Billing Cycle</th>
                    <th class="text-right">Subtotal ₹</th>
                    <th class="text-right">Total (incl. GST)</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingBills)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">No pending billing records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($pendingBills as $b): ?>
                    <tr>
                        <td class="bold"><?= htmlspecialchars($b['company_name']) ?></td>
                        <td><span class="chip"><?= !empty($b['from_date']) ? date('d M', strtotime($b['from_date'])) . ' – ' . date('d M Y', strtotime($b['to_date'])) : htmlspecialchars($b['month_year']) ?></span></td>
                        <td class="mono text-right">₹<?= number_format($b['subtotal'], 2) ?></td>
                        <td class="bold c-teal text-right">₹<?= number_format($b['grand_total'], 2) ?></td>
                        <td class="text-right">
                            <form method="POST" action="/invoices/generate" style="margin:0;">
                                <input type="hidden" name="billing_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                  Generate New
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Historical Sent Invoices -->
    <div class="card">
        <div class="card-head">
            <div class="card-title">Billing Ledger & Invoices</div>
            <div class="fs11 c-secondary">Track payments and receivables</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Client Organization</th>
                    <th>Issued on</th>
                    <th class="text-right">Amount ₹</th>
                    <th>Status</th>
                    <th class="text-right">Operations</th>
                  </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-muted);">No invoices have been issued yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach($invoices as $inv): ?>
                    <tr>
                        <td class="mono bold"><?= htmlspecialchars($inv['invoice_no']) ?></td>
                        <td class="fw6"><?= htmlspecialchars($inv['company_name']) ?></td>
                        <td class="c-secondary"><?= date('d M, Y', strtotime($inv['issue_date'])) ?></td>
                        <td class="bold text-right">₹<?= number_format($inv['amount'], 2) ?></td>
                        <td>
                            <span class="badge <?= $inv['status'] === 'Paid' ? 'b-green' : 'b-amber' ?>">
                                <?= htmlspecialchars($inv['status']) ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex-center gap8" style="justify-content: flex-end;">
                                <?php if($inv['status'] !== 'Paid'): ?>
                                <form method="POST" action="/invoices/pay" style="margin:0;">
                                    <input type="hidden" name="invoice_no" value="<?= $inv['invoice_no'] ?>">
                                    <button type="submit" class="btn btn-sm btn-approve" title="Mark as Paid">
                                      Settled
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="/invoices/print?inv=<?= $inv['invoice_no'] ?>" target="_blank" class="btn btn-sm" title="Print Invoice">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
