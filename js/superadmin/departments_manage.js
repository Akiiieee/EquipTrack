function logout(){ fetch('../../php/superadmin/logout.php',{method:'POST'}).then(()=>{window.location.href='super_admin_login.html';}); }
function checkLogin(){ fetch('../../php/superadmin/check_session.php').then(r=>r.json()).then(d=>{ if(!d.logged_in){window.location.href='super_admin_login.html';} else { document.getElementById('username') && (document.getElementById('username').textContent=d.username); } }); }
checkLogin();

function loadDepartments(){
    const sel = document.getElementById('new_department');
    const countEl = null;
    if (!sel) return;
    fetch('../../php/superadmin/departments_list.php')
        .then(r=>r.json()).then(data=>{
            sel.innerHTML='';
            if (data.success && Array.isArray(data.departments)){
                if (data.departments.length===0){ sel.innerHTML='<option value="" disabled selected>No departments yet</option>'; }
                else { sel.innerHTML = data.departments.map(d=>`<option value="${d.department_name}">${d.department_name}</option>`).join(''); }
            } else { sel.innerHTML='<option value="" disabled selected>Failed to load</option>'; }
        }).catch(()=>{ sel.innerHTML='<option value="" disabled selected>Error</option>'; });
}
loadDepartments();

document.getElementById('addDeptBtn')?.addEventListener('click', ()=>{
    const input = document.getElementById('deptName');
    const name = (input?.value||'').trim();
    const msg = document.getElementById('deptMsg');
    if (!name){ if(msg){msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Enter name'; setTimeout(()=>{msg.style.display='none';},2000);} return; }
    const fd = new FormData(); fd.append('name', name);
    fetch('../../php/superadmin/departments_add.php', {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{ if (msg){msg.style.display='block'; msg.style.color=d.success?'green':'#800000'; msg.textContent=d.message|| (d.success?'Added':'Failed'); setTimeout(()=>{msg.style.display='none';},2000);} if(d.success){ input.value=''; loadDepartments(); } });
});

document.getElementById('renameDeptBtn')?.addEventListener('click', ()=>{
    const sel = document.getElementById('new_department');
    const oldName = sel?.value || '';
    const msg = document.getElementById('deptMsg');
    if (!oldName){ if(msg){msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Select department'; setTimeout(()=>{msg.style.display='none';},2000);} return; }
    const newName = prompt('Enter new name', oldName) || '';
    if (!newName.trim()) return;
    const fd = new FormData(); fd.append('old_name', oldName); fd.append('new_name', newName.trim());
    fetch('../../php/superadmin/departments_update.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if (msg){msg.style.display='block'; msg.style.color=d.success?'green':'#800000'; msg.textContent=d.success?'Updated':'Failed'; setTimeout(()=>{msg.style.display='none';},2000);} if(d.success) loadDepartments(); });
});

document.getElementById('deleteDeptBtn')?.addEventListener('click', ()=>{
    const sel = document.getElementById('new_department');
    const name = sel?.value || '';
    const msg = document.getElementById('deptMsg');
    if (!name){ if(msg){msg.style.display='block'; msg.style.color='#800000'; msg.textContent='Select department'; setTimeout(()=>{msg.style.display='none';},2000);} return; }
    if (!confirm('Delete ' + name + '?')) return;
    const fd = new FormData(); fd.append('name', name);
    fetch('../../php/superadmin/departments_delete.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if (msg){msg.style.display='block'; msg.style.color=d.success?'green':'#800000'; msg.textContent=d.success?'Deleted':'Failed'; setTimeout(()=>{msg.style.display='none';},2000);} if(d.success) loadDepartments(); });
});


