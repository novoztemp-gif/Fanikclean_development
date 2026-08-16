<div class="panel active">
    <!-- Header -->
    <div class="sec-head">
        <div class="sec-meta">Track historical billing versus actual payment collections.</div>
    </div>

    <!-- Summary Stats -->
    <?php
        $totalBilled = array_sum(array_column($ledger, 'total_billed'));
        $totalCollected = array_sum(array_column($ledger, 'total_collected'));
        $outstanding = $totalBilled - $totalCollected;
    ?>
    <div class="stats-grid mb24">
        <div class="stat-card">
            <div class="stat-label">Total Invoiced</div>
            <div class="stat-val">₹<?= number_format($totalBilled, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Collected</div>
            <div class="stat-val" style="color: var(--primary);">₹<?= number_format($totalCollected, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Outstanding</div>
            <div class="stat-val" style="color: var(--danger);">₹<?= number_format($outstanding, 2) ?></div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card">
        <div class="card-head">
            <div class="card-title">Client Receivables Ledger</div>
            <div class="fs11 c-secondary">Billed vs Collected analysis</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client Organization</th>
                        <th>Total Invoiced (Life-time)</th>
                        <th>Total Collected</th>
                        <th>Outstanding Amount</th>
                        <th>Collection %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ledger)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">No financial data available yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($ledger as $row): 
                            $collPct = ($row['total_billed'] > 0) ? ($row['total_collected'] / $row['total_billed'] * 100) : 0;
                            $diff = $row['total_billed'] - $row['total_collected'];
                        ?>
                        <tr>
                            <td class="bold"><?= htmlspecialchars($row['company_name']) ?></td>
                            <td class="mono">₹<?= number_format($row['total_billed'], 2) ?></td>
                            <td class="mono c-teal">₹<?= number_format($row['total_collected'], 2) ?></td>
                            <td class="mono <?= $diff > 0 ? 'c-red' : 'c-secondary' ?>">
                                ₹<?= number_format($diff, 2) ?>
                            </td>
                            <td>
                                <div class="flex-center gap8">
                                    <div style="flex: 1; height: 6px; background: var(--bg); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?= min($collPct, 100) ?>%; height: 100%; background: <?= $collPct >= 100 ? 'var(--primary)' : 'var(--warning)' ?>;"></div>
                                    </div>
                                    <span class="fs11 fw6"><?= round($collPct, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
