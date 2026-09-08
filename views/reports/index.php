<div class="panel active">
    <div class="sec-head">
        <div class="sec-meta">Generate and view system-wide analytical reports.</div>
    </div>

    <!-- Operational Reports -->
    <div class="form-section-title">Operational Reports</div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px;">
        <div class="report-card" onclick="location.href='/attendance'">
            <div class="report-icon" style="background:var(--teal-bg); color:var(--teal);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="report-name">Monthly Attendance Summary</div>
            <div class="report-desc">View site-wise attendance logs and OT hours.</div>
            <div class="fs12 fw7 c-teal"><?= $insights['attendance_summary']['present'] ?> Records Logged</div>
        </div>

        <div class="report-card" onclick="location.href='/workers'">
            <div class="report-icon" style="background:var(--primary-bg); color:var(--primary);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="report-name">Workforce Distribution</div>
            <div class="report-desc">Analyze employee counts across Skill & Site categories.</div>
            <div class="fs12 fw7 c-secondary"><?= $insights['total_workers'] ?> Active Personnel</div>
        </div>
    </div>

    <!-- Financial Reports -->
    <div class="form-section-title">Financial Reports</div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px;">
        <div class="report-card" onclick="location.href='/financial'">
            <div class="report-icon" style="background:var(--blue-bg); color:var(--info);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="report-name">Client Receivables Ledger</div>
            <div class="report-desc">Analyze total billed vs. total collected revenue.</div>
            <div class="fs12 fw7" style="color:var(--info);">Outstanding: ₹<?= number_format($insights['outstanding_amount']) ?></div>
        </div>

        <div class="report-card" onclick="location.href='/payroll'">
            <div class="report-icon" style="background:var(--purple-bg); color:#8b5cf6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="report-name">Payroll Settlement Status</div>
            <div class="report-desc">Monthly salary disbursement and attendance-pay reconciliation.</div>
            <div class="fs12 fw7 c-secondary">Settlement: Current Month</div>
        </div>

        <div class="report-card" onclick="location.href='/invoices'">
            <div class="report-icon" style="background: #f1f5f9; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="report-name">Invoice Aging Report</div>
            <div class="report-desc">Track status and payment delays for all client invoices.</div>
            <div class="badge b-red"><?= $insights['unpaid_invoices_count'] ?> Unpaid</div>
        </div>
    </div>

    <!-- Administrative Reports -->
    <div class="form-section-title">Administrative Reports</div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="report-card" onclick="location.href='/audit'">
            <div class="report-icon" style="background:var(--red-bg); color:var(--red);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="report-name">System Privacy & Audit Log</div>
            <div class="report-desc">Monitor administrative actions and critical data changes.</div>
            <div class="fs12 fw7 c-red">Security Enabled</div>
        </div>
    </div>
</div>
