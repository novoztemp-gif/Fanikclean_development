<div class="panel active">
    <!-- Row 1: Key Metrics -->
    <div class="statline">
        <div class="statcell">
            <div class="stat-label">Active Workers</div>
            <div class="stat-val" data-count="<?= (int)($insights['total_workers'] ?? 0) ?>">0</div>
            <div class="stat-sub"><span class="stat-tag up">+<?= $insights['new_workers'] ?></span> added this month</div>
        </div>

        <div class="statcell">
            <div class="stat-label">Sites Managed</div>
            <div class="stat-val" data-count="<?= (int)($insights['total_sites'] ?? 0) ?>">0</div>
            <div class="stat-sub">across <?= $insights['client_count'] ?> clients</div>
        </div>

        <div class="statcell">
            <div class="stat-label">Present Today</div>
            <div class="stat-val accent" data-count="<?= (int)($presentToday ?? 0) ?>">0</div>
            <div class="stat-sub">out of <?= (int)($insights['total_workers'] ?? 0) ?> active workers</div>
        </div>

        <div class="statcell">
            <div class="stat-label">Attendance Edits Pending</div>
            <div class="stat-val" data-count="<?= (int)($insights['pending_attendance'] ?? 0) ?>">0</div>
            <div class="stat-sub"><a href="/attendance" style="color:var(--primary-text); text-decoration:none; font-weight:600;">Review &rarr;</a></div>
        </div>
    </div>

    <!-- Row 2: Attendance Trend & Workforce -->
    <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Attendance — last 7 days</div>
                <a href="/attendance/register" class="card-action">Full register &rarr;</a>
            </div>
            <div style="display: flex; gap: 32px; margin-bottom: 20px;">
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">Present <span class="c-secondary fw6" style="text-transform:none;">(this month)</span></div>
                    <div class="fs24 fw8 c-teal"><?= number_format($insights['attendance_summary']['present'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">Half-Day</div>
                    <div class="fs24 fw8 c-amber"><?= number_format($insights['attendance_summary']['half_day'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="fs11 fw7 c-secondary mb4 text-upper">OT Hours</div>
                    <div class="fs24 fw8 c-primary"><?= number_format($insights['attendance_summary']['ot_hours'] ?? 0) ?></div>
                </div>
            </div>
            <div style="height: 180px; position: relative;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div class="card-title">Workforce by category</div>
            </div>
            <?php $workforceTotal = array_sum(array_column($workforceDist, 'count')); ?>
            <?php if ($workforceTotal === 0): ?>
                <div class="fs12 c-secondary" style="padding: 10px 0;">No active workers yet.</div>
            <?php else: ?>
                <div style="height: 190px; position: relative;">
                    <canvas id="workforceChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row 3: Attendance status & Sites overview -->
    <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 20px;">
        <div class="card">
            <div class="card-head">
                <div class="card-title">Attendance status <span class="c-secondary fw6" style="text-transform:none; font-size:11px;">(this month)</span></div>
            </div>
            <?php $attTotal = array_sum([$insights['attendance_summary']['present'] ?? 0, $insights['attendance_summary']['absent'] ?? 0, $insights['attendance_summary']['half_day'] ?? 0, $insights['attendance_summary']['off_duty'] ?? 0]); ?>
            <?php if ($attTotal === 0): ?>
                <div class="fs12 c-secondary" style="padding: 10px 0;">No attendance marked this month yet.</div>
            <?php else: ?>
                <div style="height: 200px; position: relative;">
                    <canvas id="attStatusChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head">
                <div class="card-title">Sites overview</div>
                <a href="/clients" class="card-action">All sites &rarr;</a>
            </div>
            <?php if (empty($siteHeadcounts)): ?>
                <div class="fs12 c-secondary" style="padding: 10px 0;">No sites in scope yet.</div>
            <?php else: ?>
                <div style="height: 200px; position: relative;">
                    <canvas id="sitesChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');

    const trendData = <?= json_encode($insights['attendance_trends'] ?? []) ?>;

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
                backgroundColor: 'rgba(47, 107, 79, 0.16)',
                borderColor: '#2F6B4F',
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

    const sitesCanvas = document.getElementById('sitesChart');
    if (sitesCanvas) {
        const siteData = <?= json_encode($siteHeadcounts ?? []) ?>;
        new Chart(sitesCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: siteData.map(s => s.name),
                datasets: [{
                    label: 'Active workers',
                    data: siteData.map(s => parseInt(s.headcount, 10)),
                    backgroundColor: '#8A6A32',
                    borderRadius: 3,
                    maxBarThickness: 18
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: '#F0F0EF' } },
                    y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    const donutOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 11 } } }
        },
        cutout: '62%'
    };

    const workforceCanvas = document.getElementById('workforceChart');
    if (workforceCanvas) {
        const wf = <?= json_encode($workforceDist ?? []) ?>;
        new Chart(workforceCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: wf.map(d => d.name),
                datasets: [{
                    data: wf.map(d => parseInt(d.count, 10)),
                    backgroundColor: ['#8A6A32', '#3B6EA5', '#4A3F72', '#2F6B4F', '#B7791F'],
                    borderColor: '#FFFFFF',
                    borderWidth: 2
                }]
            },
            options: donutOpts
        });
    }

    const attStatusCanvas = document.getElementById('attStatusChart');
    if (attStatusCanvas) {
        const s = <?= json_encode($insights['attendance_summary'] ?? []) ?>;
        new Chart(attStatusCanvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Half-Day', 'Off Duty'],
                datasets: [{
                    data: [
                        parseInt(s.present || 0, 10),
                        parseInt(s.absent || 0, 10),
                        parseInt(s.half_day || 0, 10),
                        parseInt(s.off_duty || 0, 10)
                    ],
                    backgroundColor: ['#2F6B4F', '#A5402E', '#B7791F', '#B9BCC0'],
                    borderColor: '#FFFFFF',
                    borderWidth: 2
                }]
            },
            options: donutOpts
        });
    }
});
</script>
