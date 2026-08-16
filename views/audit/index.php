<div class="panel active">
    <!-- Header -->
    <div class="sec-head">
        <div class="sec-meta">Historical track of all critical system actions and data modifications.</div>
    </div>

    <!-- Audit Log Table -->
    <div class="card">
        <div class="card-head">
            <div class="card-title">System Activity Log</div>
            <div class="fs11 c-secondary">Last 100 operations</div>
        </div>
        <div class="table-wrap">
            <table class="fs13">
                <thead>
                    <tr>
                        <th style="width: 180px;">Timestamp</th>
                        <th>Administrator</th>
                        <th>Module / Section</th>
                        <th>Action Performed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: var(--text-muted);">No activity logs available yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="mono c-secondary"><?= date('d-m-Y H:i:s', strtotime($log['timestamp'])) ?></td>
                            <td class="bold"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                            <td>
                                <?php
                                    $modMap = [
                                        'Attendance' => 'b-teal',
                                        'Billing' => 'b-blue',
                                        'Config' => 'b-gray',
                                        'Users' => 'b-red',
                                        'Workers' => 'b-purple',
                                        'Leave' => 'b-amber',
                                        'Financial' => 'b-indigo',
                                        'Invoices' => 'b-indigo',
                                        'Payroll' => 'b-green',
                                        'Clients' => 'b-blue',
                                        'Sites' => 'b-teal',
                                        'Auth' => 'b-gray'
                                    ];
                                    $cls = $modMap[$log['module']] ?? 'b-gray';
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars($log['module']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
