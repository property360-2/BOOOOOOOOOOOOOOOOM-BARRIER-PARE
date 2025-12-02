// Simple front-end auth using localStorage. Not secure — for demo only.
(function(){
  const STORAGE_KEY = 'rfid_users_v1';
  const SESSION_KEY = 'rfid_session_v1';

  function loadUsers(){
    try{ return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }catch(e){return []}
  }
  function saveUsers(users){ localStorage.setItem(STORAGE_KEY, JSON.stringify(users)); }

  function hash(s){ // naive hash substitute
    let h=0; 
    for(let i=0;i<s.length;i++) h=(h<<5)-h + s.charCodeAt(i) | 0; 
    return h.toString(16);
  }

  function register(username,password,role='user'){
    const users = loadUsers();
    if (users.find(u=>u.username===username)) return {ok:false,err:'exists'};
    users.push({username, password: hash(password), role});
    saveUsers(users); 
    return {ok:true};
  }

  function login(username,password){
    const users = loadUsers();
    const h = hash(password);
    const u = users.find(x=>x.username===username && x.password===h);
    if (!u) return {ok:false};
    localStorage.setItem(SESSION_KEY, JSON.stringify({username:u.username, role:u.role}));
    return {ok:true, user:{username:u.username, role:u.role}};
  }

  function logout(){ localStorage.removeItem(SESSION_KEY); }

  function getCurrentUser(){ 
    try{ return JSON.parse(localStorage.getItem(SESSION_KEY)); } 
    catch(e){return null} 
  }

  function deleteUser(username){
    const users = loadUsers();
    const idx = users.findIndex(u=>u.username===username);
    if (idx === -1) return {ok:false, err:'not-found'};

    const current = getCurrentUser();
    if (current && current.username === username) return {ok:false, err:'self-delete'};

    // prevent deleting the last admin
    const isAdmin = users[idx].role === 'admin';
    if (isAdmin){
      const adminCount = users.filter(u=>u.role==='admin').length;
      if (adminCount <= 1) return {ok:false, err:'last-admin'};
    }

    users.splice(idx,1);
    saveUsers(users);
    return {ok:true};
  }

  // 🔥 NEW: reset password function
  function resetPassword(username, newPass){
    const users = loadUsers();
    const u = users.find(x=>x.username === username);
    if (!u) return {ok:false, error:'notfound'};
    u.password = hash(newPass);
    saveUsers(users);
    return {ok:true};
  }

  // ensure an admin exists
  (function ensureAdmin(){
    const users = loadUsers();
    if (!users.find(u=>u.role==='admin')){
      users.push({username:'admin', password: hash('admin'), role:'admin'});
      saveUsers(users);
    }
  })();

  // expose all functions
  window.auth = {
    register,
    login,
    logout,
    getCurrentUser,
    loadUsers,
    deleteUser,
    resetPassword
  };
})();
