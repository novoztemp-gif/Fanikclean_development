<div class="panel active">
    <!-- Row 1: Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon b-green">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="c-teal"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-label">Active Workers</div>
            <div class="stat-val"><?= number_format($insights['total_workers']) ?></div>
            <div class="stat-sub">
                <span class="stat-tag up">+<?= $insights['new_workers'] ?></span> added this month
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon b-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="b-blue-text"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>
            </div>
            <div class="stat-label">Sites Managed</div>
            <div class="stat-val"><?= $insights['total_sites'] ?></div>
            <div class="stat-sub">across <?= $insights['client_count'] ?> clients</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon b-amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="c-amber"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-val">₹<?= number_format($insights['monthly_revenue']) ?></div>
            <div class="stat-sub">
                <span class="stat-tag <?= $insights['revenue_growth'] >= 0 ? 'up' : 'down' ?>">
                    <?= $insights['revenue_growth'] >= 0 ? '+' : '' ?><?= number_format($insights['revenue_growth'], 1) ?>%
                </span> vs last month
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon b-red">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="c-red"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            </div>
            <div class="stat-label">Outstanding</div>
            <div class="stat-val">₹<?= number_format($insights['outstanding_amount']) ?></div>
            <div class="stat-sub">
                <span class="c-red fw7"><?= $insights['unpaid_invoices_count'] ?> Invoices</span> unpaid
            </div>
        </div>
    </div>

    <!-- Row 2: Attendance Trend & Invoices -->
    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Attendance this month</div>
                <a href="/attendance" class="card-action">Full register &rarr;</a>
            </div>
            <div style="display: flex; gap: 32px; margin-bottom: 20px;">
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">Present</div>
                    <div class="fs24 fw8 c-teal"><?= number_format($insights['attendance_summary']['present']) ?></div>
                </div>
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">Half-Day</div>
                    <div class="fs24 fw8 c-amber"><?= number_format($insights['attendance_summary']['half_day']) ?></div>
                </div>
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">OT Hours</div>
                    <div class="fs24 fw8" style="color:#3b82f6;"><?= number_format($insights['attendance_summary']['ot_hours']) ?></div>
                </div>
            </div>
            <div style="height: 180px; position: relative;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="card-head" style="padding: 20px 22px 10px;">
                <div class="card-title">Recent invoices</div>
                <a href="/invoices" class="card-action">View all &rarr;</a>
            </div>
            <div class="table-wrap">
                <table style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Amount</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($insights['recent_invoices'] as $inv): ?>
                        <tr>
                            <td class="bold"><?= htmlspecialchars($inv['company_name']) ?></td>
                            <td class="fw6">₹<?= number_format($inv['amount']) ?></td>
                            <td class="text-right">
                                <span class="badge <?= $inv['status'] == 'Paid' ? 'b-green' : ($inv['status'] == 'Unpaid' ? 'b-red' : 'b-amber') ?>">
                                    <?= $inv['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($insights['recent_invoices'])): ?>
                            <tr><td colspan="3" class="c-secondary" style="padding:20px; text-align:center;">No recent invoices</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Row 3: Workforce & Approvals -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Workforce breakdown</div>
            </div>
            <div style="padding: 10px 0;">
                <?php 
                    $dist = (new Dashboard())->getWorkerRoleDistribution($siteScope ?? null);
                    $total = array_sum(array_column($dist, 'count'));
                    foreach($dist as $d): 
                        $pct = $total > 0 ? ($d['count'] / $total * 100) : 0;
                ?>
                <div class="prog-row">
                    <div class="prog-lbl"><?= htmlspecialchars($d['name']) ?></div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width: <?= $pct ?>%; background: var(--primary);"></div>
                    </div>
                    <div class="prog-val"><?= $d['count'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="card-head" style="padding: 20px 22px 10px;">
                <div class="card-title">Pending approvals</div>
            </div>
            <div style="padding: 0 22px 22px;">
                <div class="mini-row" style="cursor:pointer;" onclick="location.href='/leave'">
                    <span class="mini-label">Leave requests</span>
                    <span class="badge b-amber"><?= $insights['pending_leave'] ?> pending</span>
                </div>
                <div class="mini-row" style="cursor:pointer;" onclick="location.href='/attendance'">
                    <span class="mini-label">Attendance edits</span>
                    <span class="badge b-amber"><?= $insights['pending_attendance'] ?> pending</span>
                </div>
                <div class="mini-row" style="cursor:pointer;" onclick="location.href='/billing'">
                    <span class="mini-label">Invoice approvals</span>
                    <span class="badge b-amber"><?= $insights['pending_billing'] ?> pending</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    // Fetch data for the chart from current session/PHP 
    // In a real scenario, we might pass this via a separate API or JSON encoded var
    const trendData = <?= json_encode((new Dashboard())->getAttendanceTrends($siteScope ?? null)) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData.map(d => {
                const date = new Date(d.attendance_date);
                return date.toLocaleDateString('en-US', { weekday: 'short' });
            }),
            datasets: [{
                label: 'Attendance %',
                data: trendData.map(d => parseFloat(d.percentage)),
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 4,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, display: false },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>
