function logout(){
    fetch('../../php/superadmin/logout.php',{method:'POST'}).then(()=>{window.location.href='super_admin_login.html';});
}
function checkLogin(){
    fetch('../../php/superadmin/check_session.php').then(r=>r.json()).then(d=>{if(!d.logged_in){window.location.href='super_admin_login.html';}else{document.getElementById('username').textContent=d.username;}});
}
checkLogin();

let previousActiveIds = new Set();

function renderUsers(users){
    const body = document.getElementById('usersBody');
    if (!Array.isArray(users) || users.length === 0){ body.innerHTML = '<tr><td colspan="5" style="padding:10px; color:#666;">No users found</td></tr>'; return; }
    body.innerHTML = users.map(u=>{
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
    fetch('../../php/superadmin/users_list.php').then(r=>r.json()).then(d=>{ if(d.success) renderUsers(d.users); });
}
loadUsers();

document.getElementById('usersTable')?.addEventListener('click', function(e){
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    const action = target.getAttribute('data-action');
    const uid = target.getAttribute('data-uid');
    if (!action || !uid) return;
    if (action === 'save'){
        const email = document.querySelector(`input[data-uid="${uid}"][data-field="email"]`)?.value || '';
        const role = document.querySelector(`select[data-uid="${uid}"][data-field="role"]`)?.value || '';
        const dept = document.querySelector(`input[data-uid="${uid}"][data-field="department"]`)?.value || '';
        const fd = new FormData(); fd.append('user_id', uid); if(email) fd.append('email', email); if(role) fd.append('role', role); if(dept) fd.append('department', dept);
        fetch('../../php/superadmin/user_update.php',{method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success){ loadUsers(); } else { alert(d.message||'Update failed'); }});
    } else if (action === 'delete'){
        if (!confirm('Delete this user?')) return;
        const fd = new FormData(); fd.append('user_id', uid);
        fetch('../../php/superadmin/user_delete.php',{method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success){ loadUsers(); } else { alert(d.message||'Delete failed'); }});
    }
});


