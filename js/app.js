// ---- Modal Helpers ----
function openModal(id)  { 
    document.getElementById(id).classList.add('open'); 
    document.body.style.overflow='hidden'; 
}

function closeModal(id) { 
    document.getElementById(id).classList.remove('open'); 
    document.body.style.overflow=''; 
}

document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

// ---- Toast Notifications ----
let toastTimer;
function toast(msg, type='success') {
    const el = document.getElementById('toast');
    const ic = document.getElementById('toast-icon');
    if (!el || !ic) return;
    
    document.getElementById('toast-msg').textContent = msg;
    el.className = type;
    
    if (type === 'success') {
        ic.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
    } else if (type === 'warn') {
        ic.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    } else {
        ic.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
    }
    
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
}

// ---- Worker Management ----
function openAddWorkerModal() {
    const form = document.getElementById('worker-form');
    if (!form) return;
    form.reset();
    form.action = '/workers/create';
    document.getElementById('worker-id').value = '';
    document.getElementById('worker-modal-title').textContent = 'Add New Worker';
    
    const clientSelect = document.getElementById('worker-client_id');
    if (clientSelect) clientSelect.value = '';
    if (typeof filterSitesByClient === 'function') filterSitesByClient();
    
    openModal('modal-add-worker');
}

function openEditWorkerModal(w) {
    const form = document.getElementById('worker-form');
    if (!form) return;
    form.action = '/workers/update';
    document.getElementById('worker-modal-title').textContent = 'Edit Worker Profile';
    
    document.getElementById('worker-id').value = w.id;
    document.getElementById('worker-full_name').value = w.full_name;
    document.getElementById('worker-mobile').value = w.mobile;
    document.getElementById('worker-aadhaar').value = w.aadhaar || '';
    document.getElementById('worker-doj').value = w.doj;
    document.getElementById('worker-category_id').value = w.category_id;
    document.getElementById('worker-status').value = w.status;
    
    // New Fields
    document.getElementById('worker-esi_number').value = w.esi_number || '';
    document.getElementById('worker-pf_number').value = w.pf_number || '';
    document.getElementById('worker-age').value = w.age || '';
    document.getElementById('worker-experience').value = w.experience || '';
    document.getElementById('worker-uniform_issue_date').value = w.uniform_issue_date || '';
    document.getElementById('worker-uniform_details').value = w.uniform_details || '';
    
    document.getElementById('worker-guardian_name').value = w.guardian_name || '';
    document.getElementById('worker-guardian_phone').value = w.guardian_phone || '';
    document.getElementById('worker-guardian_place').value = w.guardian_place || '';
    
    // Handle Client & Site Relationship
    const siteSelect = document.getElementById('worker-site_id');
    const clientSelect = document.getElementById('worker-client_id');
    
    if (w.site_id && siteSelect && clientSelect) {
        const option = siteSelect.querySelector(`option[value="${w.site_id}"]`);
        if (option) {
            clientSelect.value = option.getAttribute('data-client-id') || '';
        }
    } else if (clientSelect) {
        clientSelect.value = '';
    }
    
    if (typeof filterSitesByClient === 'function') {
        filterSitesByClient();
    }
    
    if (siteSelect) {
        siteSelect.value = w.site_id || '';
    }
    
    openModal('modal-add-worker');
}

// ---- User Management ----
function openAddUserModal() {
    openModal('modal-add-user');
}

function openEditUserModal(u) {
    const form = document.getElementById('user-form');
    if (!form) return;
    document.getElementById('edit-user-id').value = u.id;
    document.getElementById('edit-user-name').value = u.full_name;
    document.getElementById('edit-user-email').value = u.email;
    document.getElementById('edit-user-role').value = u.role_id;

    // Pre-check the manager's assigned sites in the checkbox grid.
    var sitesList = document.getElementById('edituser-sites-list');
    if (sitesList) {
        var assigned = (u.assigned_site_ids_csv || '').split(',').filter(Boolean);
        sitesList.querySelectorAll('.site-chk').forEach(function (chk) {
            chk.checked = assigned.indexOf(chk.value) !== -1;
        });
        document.getElementById('edituser-site-search').value = '';
        filterSiteCheckboxes('edituser');
        updateSiteCheckboxCount('edituser');
    }

    document.getElementById('edit-user-guardian_name').value = u.guardian_name || '';
    document.getElementById('edit-user-guardian_phone').value = u.guardian_phone || '';
    document.getElementById('edit-user-guardian_place').value = u.guardian_place || '';

    openModal('modal-edit-user');
}

// ---- Site-picker: search + grid checkboxes (Site Assignments, User edit modal) ----
// Each instance is addressed by a prefix, e.g. "assign" -> #assign-sites-list,
// #assign-site-search, #assign-site-count, #assign-sites-empty.
function filterSiteCheckboxes(prefix) {
    var search = document.getElementById(prefix + '-site-search');
    var q = search ? search.value.trim().toLowerCase() : '';
    var list = document.getElementById(prefix + '-sites-list');
    if (!list) return;
    var visibleCount = 0;
    list.querySelectorAll('.site-picker-item').forEach(function (item) {
        var match = !q || (item.dataset.siteName || '').indexOf(q) !== -1;
        item.hidden = !match;
        if (match) visibleCount++;
    });
    var empty = document.getElementById(prefix + '-sites-empty');
    if (empty) empty.hidden = visibleCount !== 0;
}

function updateSiteCheckboxCount(prefix) {
    var list = document.getElementById(prefix + '-sites-list');
    if (!list) return;
    var count = list.querySelectorAll('.site-chk:checked').length;
    var el = document.getElementById(prefix + '-site-count');
    if (el) el.textContent = count;
}

function siteCheckboxSelectAll(prefix, select) {
    var list = document.getElementById(prefix + '-sites-list');
    if (!list) return;
    list.querySelectorAll('.site-picker-item').forEach(function (item) {
        if (item.hidden) return; // only affect what the current search shows
        var chk = item.querySelector('.site-chk');
        if (chk) chk.checked = select;
    });
    updateSiteCheckboxCount(prefix);
}

// ---- Credential management ----
function openSetPasswordModal(userId, fullName) {
    closeModal('modal-credentials');
    document.getElementById('set-password-form').reset();
    document.getElementById('setpw-user-id').value = userId;
    document.getElementById('setpw-user-name').textContent = fullName;
    openModal('modal-set-password');
}

function validateSetPasswordForm() {
    var pw = document.getElementById('setpw-password').value;
    var confirm = document.getElementById('setpw-password-confirm').value;
    if (pw !== confirm) {
        toast('Passwords do not match', 'error');
        return false;
    }
    if (pw.length < 6) {
        toast('Password must be at least 6 characters', 'error');
        return false;
    }
    return true;
}

// ---- Topbar user menu ----
function toggleUserMenu() {
    document.getElementById('user-menu').classList.toggle('open');
}
document.addEventListener('click', function (e) {
    var menu = document.getElementById('user-menu');
    if (menu && menu.classList.contains('open') && !menu.contains(e.target)) {
        menu.classList.remove('open');
    }
});

// ---- Sidebar "Finance" show all / hide ----
function toggleFinanceNav() {
    var box = document.getElementById('finance-collapse');
    var label = document.getElementById('finance-toggle-label');
    var chevron = document.querySelector('#finance-toggle svg');
    if (!box) return;
    var willShow = box.hidden;
    box.hidden = !willShow;
    label.textContent = willShow ? 'Hide' : 'Show all';
    chevron.style.transform = willShow ? 'rotate(180deg)' : 'rotate(0deg)';
}

// ---- Stat strip count-up ----
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.stat-val[data-count]').forEach(function (el, i) {
        var target = parseInt(el.dataset.count, 10) || 0;
        var prefix = el.dataset.prefix || '';
        var start = performance.now();
        var dur = 700 + Math.min(i, 3) * 120;
        var delay = i * 70;
        setTimeout(function () {
            function frame(now) {
                var t = Math.min(1, (now - start) / dur);
                var eased = 1 - Math.pow(1 - t, 3);
                el.textContent = prefix + Math.round(target * eased).toLocaleString('en-IN');
                if (t < 1) requestAnimationFrame(frame);
            }
            start = performance.now();
            requestAnimationFrame(frame);
        }, delay);
    });
});

// ---- Sidebar nav hover spotlight ----
// A soft highlight glides between nav items within a section on hover,
// resting on the active item (if any) the rest of the time.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nav-section').forEach(function (section) {
        var spot = section.querySelector('.nav-spot');
        var items = section.querySelectorAll('.nav-item');
        if (!spot || !items.length) return;

        function place(el) {
            spot.style.transform = 'translateY(' + el.offsetTop + 'px)';
            spot.style.height = el.offsetHeight + 'px';
        }
        var activeEl = section.querySelector('.nav-item.active');
        if (activeEl) place(activeEl);

        items.forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                section.classList.add('hovering');
                place(item);
            });
        });
        section.addEventListener('mouseleave', function () {
            section.classList.remove('hovering');
            if (activeEl) place(activeEl);
        });
    });
});
