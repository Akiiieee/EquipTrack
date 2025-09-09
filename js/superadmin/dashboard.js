let previousActiveIds = new Set();
function showToast(username, role) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = `<span class="badge">NEW</span> <span>${username} • ${role} is now active</span>`;
    container.appendChild(el);
    setTimeout(() => { el.remove(); }, 4000);
}

function checkLoginStatus() {
    fetch('../../php/superadmin/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                window.location.href = 'super_admin_login.html';
            } else {
                document.getElementById('username').textContent = data.username;
            }
        })
        .catch(() => {
            window.location.href = 'super_admin_login.html';
        });
}

function logout() {
    fetch('../../php/superadmin/logout.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => { window.location.href = 'super_admin_login.html'; })
        .catch(() => { window.location.href = 'super_admin_login.html'; });
}

function loadDashboardData() {
    fetch('../../php/superadmin/get_user_counts.php')
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                document.getElementById('totalAdmins').textContent = String(data.admins ?? 0);
                document.getElementById('totalStaff').textContent = String(data.staff ?? 0);
                document.getElementById('totalUsers').textContent = String(data.total ?? 0);
                const badge = document.getElementById('badgeTotalUsers');
                if (badge) badge.textContent = String(data.total ?? 0);
                const deptBadge = document.getElementById('badgeTotalDepartments');
                if (deptBadge && typeof data.departments !== 'undefined') {
                    deptBadge.textContent = String(data.departments ?? 0);
                }
                const deptCard = document.getElementById('totalDepartmentsCard');
                if (deptCard && typeof data.departments !== 'undefined') {
                    deptCard.textContent = String(data.departments ?? 0);
                }
            }
        })
        .catch(()=>{});

    // Fallback explicit fetch to departments list (in case counts endpoint is cached)
    fetch('../../php/superadmin/departments_list.php')
        .then(r=>r.json())
        .then(data=>{
            const deptTotal = (data && data.success) ? (data.count ?? (Array.isArray(data.departments) ? data.departments.length : 0)) : 0;
            const deptBadge = document.getElementById('badgeTotalDepartments');
            if (deptBadge) deptBadge.textContent = String(deptTotal);
            const deptCard = document.getElementById('totalDepartmentsCard');
            if (deptCard) deptCard.textContent = String(deptTotal);
        })
        .catch(()=>{});

    const activeEl = document.getElementById('activeSessions');
    if (activeEl) activeEl.textContent = '—';

    fetch('../../php/superadmin/get_activity.php')
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                document.getElementById('activeSessions').textContent = String(data.active?.total ?? '—');
                const users = (data.active && Array.isArray(data.active.users)) ? data.active.users : [];
                const currentIds = new Set(users.map(u => String(u.user_id)));
                users.forEach(u => {
                    const id = String(u.user_id);
                    if (!previousActiveIds.has(id)) {
                        showToast(u.username || 'user', u.role || '');
                    }
                });
                previousActiveIds = currentIds;

                const list = document.getElementById('activityList');
                if (Array.isArray(data.recent) && list) {
                    if (data.recent.length === 0) {
                        list.innerHTML = '<li><span class="meta">No recent activity</span></li>';
                    } else {
                        list.innerHTML = data.recent.map(rec => {
                            const details = (() => { try { return JSON.parse(rec.details || '{}'); } catch { return {}; } })();
                            const actor = rec.actor_type === 'user' ? (details.username || `User #${rec.actor_id}`) : (details.username || `Super Admin #${rec.actor_id}`);
                            const actionText = (() => {
                                if (rec.action === 'login') return `${actor} has logged in`;
                                if (rec.action === 'logout') return `${actor} has logged out`;
                                if (rec.action === 'create_user') return `${actor} created a ${details.role || 'user'}: ${details.username || ''}`;
                                return `${actor} performed ${rec.action}`;
                            })();
                            const ts = rec.created_at ? new Date(rec.created_at) : null;
                            const when = ts ? ts.toLocaleString() : '';
                            return `<li><span class="dot"></span><div><div class="text">${actionText}</div><div class="meta">${when}</div></div></li>`;
                        }).join('');
                    }
                }
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    // Load departments into select
    function loadDepartments() {
        const sel = document.getElementById('new_department');
        const deptCountEl = document.getElementById('totalDepartments');
        if (!sel) return;
        fetch('../../php/superadmin/departments_list.php')
            .then(r => r.json())
            .then(data => {
                sel.innerHTML = '';
                if (data.success && Array.isArray(data.departments)) {
                    if (deptCountEl) deptCountEl.textContent = String(data.count ?? data.departments.length);
                    if (data.departments.length === 0) {
                        sel.innerHTML = '<option value="" disabled selected>No departments yet</option>';
                    } else {
                        sel.innerHTML = '<option value="" disabled selected>Select department</option>' +
                            data.departments.map(d => `<option value="${d.department_name}">${d.department_name}</option>`).join('');
                    }
                } else {
                    if (deptCountEl) deptCountEl.textContent = '0';
                    sel.innerHTML = '<option value="" disabled selected>Failed to load</option>';
                }
            })
            .catch(() => { if (deptCountEl) deptCountEl.textContent = '0'; sel.innerHTML = '<option value="" disabled selected>Error</option>'; });
    }
    loadDepartments();

    const addDeptBtn = document.getElementById('addDeptBtn');
    if (addDeptBtn) {
        addDeptBtn.addEventListener('click', () => {
            const input = document.getElementById('deptName');
            const name = (input?.value || '').trim();
            const msg = document.getElementById('deptMsg');
            if (!name) { if (msg){ msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Please enter department name'; setTimeout(()=>{msg.style.display='none';}, 2000);} return; }
            const fd = new FormData();
            fd.append('name', name.trim());
            fetch('../../php/superadmin/departments_add.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (msg) { msg.style.display = 'block'; msg.style.color = data.success ? 'green' : '#800000'; msg.textContent = data.message || (data.success ? 'Added' : 'Failed'); setTimeout(()=>{msg.style.display='none';}, 2000); }
                    if (data.success) { if (input) input.value=''; loadDepartments(); }
                })
                .catch(() => { if (msg) { msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Network error'; setTimeout(()=>{msg.style.display='none';}, 2000);} });
        });
    }

    const renameBtn = document.getElementById('renameDeptBtn');
    if (renameBtn) {
        renameBtn.addEventListener('click', () => {
            const sel = document.getElementById('new_department');
            const oldName = sel?.value || '';
            const msg = document.getElementById('deptMsg');
            if (!oldName) { if (msg){ msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Select department to rename'; setTimeout(()=>{msg.style.display='none';}, 2000);} return; }
            const newName = prompt('Enter new name for department', oldName) || '';
            if (!newName.trim()) return;
            const fd = new FormData();
            fd.append('old_name', oldName);
            fd.append('new_name', newName.trim());
            fetch('../../php/superadmin/departments_update.php', { method:'POST', body: fd })
                .then(r=>r.json()).then(d=>{
                    if (msg) { msg.style.display='block'; msg.style.color = d.success? 'green':'#800000'; msg.textContent = d.success? 'Updated':'Failed'; setTimeout(()=>{msg.style.display='none';}, 2000); }
                    if (d.success) loadDepartments();
                }).catch(()=>{ if(msg){ msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Network error'; setTimeout(()=>{msg.style.display='none';},2000);} });
        });
    }

    const deleteBtn = document.getElementById('deleteDeptBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            const sel = document.getElementById('new_department');
            const name = sel?.value || '';
            const msg = document.getElementById('deptMsg');
            if (!name) { if (msg){ msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Select department to delete'; setTimeout(()=>{msg.style.display='none';}, 2000);} return; }
            if (!confirm('Delete department "' + name + '"?')) return;
            const fd = new FormData();
            fd.append('name', name);
            fetch('../../php/superadmin/departments_delete.php', { method:'POST', body: fd })
                .then(r=>r.json()).then(d=>{
                    if (msg) { msg.style.display='block'; msg.style.color = d.success? 'green':'#800000'; msg.textContent = d.success? 'Deleted':'Failed'; setTimeout(()=>{msg.style.display='none';}, 2000); }
                    if (d.success) loadDepartments();
                }).catch(()=>{ if(msg){ msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Network error'; setTimeout(()=>{msg.style.display='none';},2000);} });
        });
    }

    // Manage users list
    function renderUsers(users){
        const body = document.getElementById('usersBody');
        if (!body) return;
        if (!Array.isArray(users) || users.length === 0) {
            body.innerHTML = '<tr><td colspan="5" style="padding:10px; color:#666;">No users found</td></tr>';
            return;
        }
        body.innerHTML = users.map(u => {
            const uid = String(u.user_id);
            return `<tr>
                <td style="padding:8px;">${u.username}</td>
                <td style="padding:8px;"><input data-uid="${uid}" data-field="email" value="${u.email||''}" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px;"/></td>
                <td style="padding:8px;">
                    <select data-uid="${uid}" data-field="role" style="padding:6px; border:1px solid #ddd; border-radius:6px;">
                        <option value="admin" ${u.role==='admin'?'selected':''}>Admin</option>
                        <option value="staff" ${u.role==='staff'?'selected':''}>Staff</option>
                    </select>
                </td>
                <td style="padding:8px;"><input data-uid="${uid}" data-field="department" value="${u.department||''}" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px;"/></td>
                <td style="padding:8px; display:flex; gap:6px;">
                    <button class="submit-btn" data-action="save" data-uid="${uid}">Save</button>
                    <button class="submit-btn" style="background:#dc3545" data-action="delete" data-uid="${uid}">Delete</button>
                </td>
            </tr>`
        }).join('');
    }

    function loadUsers(){
        fetch('../../php/superadmin/users_list.php')
            .then(r=>r.json()).then(d=>{ if (d.success) renderUsers(d.users); });
    }
    loadUsers();

    document.getElementById('usersTable')?.addEventListener('click', function(e){
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;
        const action = target.getAttribute('data-action');
        const uid = target.getAttribute('data-uid');
        if (!action || !uid) return;
        if (action === 'save') {
            const email = document.querySelector(`input[data-uid="${uid}"][data-field="email"]`)?.value || '';
            const role = document.querySelector(`select[data-uid="${uid}"][data-field="role"]`)?.value || '';
            const dept = document.querySelector(`input[data-uid="${uid}"][data-field="department"]`)?.value || '';
            const fd = new FormData();
            fd.append('user_id', uid);
            if (email) fd.append('email', email);
            if (role) fd.append('role', role);
            if (dept) fd.append('department', dept);
            fetch('../../php/superadmin/user_update.php', { method:'POST', body: fd })
                .then(r=>r.json()).then(d=>{ if(d.success){ loadUsers(); } else { alert(d.message||'Update failed'); } });
        } else if (action === 'delete') {
            if (!confirm('Delete this user?')) return;
            const fd = new FormData();
            fd.append('user_id', uid);
            fetch('../../php/superadmin/user_delete.php', { method:'POST', body: fd })
                .then(r=>r.json()).then(d=>{ if(d.success){ loadUsers(); } else { alert(d.message||'Delete failed'); } });
        }
    });

    checkLoginStatus();
    loadDashboardData();
    setInterval(loadDashboardData, 20000);
    const addUserForm = document.getElementById('addUserForm');
    const messageEl = document.getElementById('addUserMessage');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            messageEl.textContent = '';
            const formData = new FormData(addUserForm);
            const password = formData.get('password')?.toString() || '';
            if (password.length < 6) {
                messageEl.style.color = '#800000';
                messageEl.textContent = 'Password must be at least 6 characters';
                return;
            }
            fetch('../../php/superadmin/add_user.php', { method: 'POST', body: formData })
                .then(r => r.text())
                .then(t => {
                    try {
                        const data = JSON.parse(t);
                        if (data.success) {
                            messageEl.style.color = 'green';
                            messageEl.textContent = 'User added successfully';
                            addUserForm.reset();
                        } else {
                            messageEl.style.color = '#800000';
                            messageEl.textContent = (data.message || 'Failed to add user') + (data.status ? ` (status ${data.status})` : '') + (data.detail ? `: ${data.detail}` : '');
                        }
                    } catch(err) {
                        messageEl.style.color = '#800000';
                        messageEl.textContent = 'Server error. Please try again.';
                    }
                })
                .catch(() => {
                    messageEl.style.color = '#800000';
                    messageEl.textContent = 'Network error. Please try again.';
                });
        });
    }
});


