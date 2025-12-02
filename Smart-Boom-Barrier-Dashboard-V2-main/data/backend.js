// Simple backend wrapper: tries server API then falls back to localStorage methods
(function(){
  const API = '/api.php';

  async function post(action, body){
    try{
      const fd = new URLSearchParams(body);
      fd.append('action', action);
      const res = await fetch(API, {method:'POST', body:fd});
      if (!res.ok) throw new Error('Network');
      return await res.json();
    }catch(e){
      return { ok:false, err:'network' };
    }
  }

  // fallback localStorage implementations (partial)
  function ls_getTags(){ try{ return JSON.parse(localStorage.getItem('rfid_allowed_v1')||'[]'); }catch(e){return []} }
  function ls_getRegistry(){ try{ return JSON.parse(localStorage.getItem('rfid_registry_v1')||'{}'); }catch(e){return {}} }

  window.backend = {
    async getTags(){ const r = await post('get_tags', {}); if (r.ok) return r; return {ok:true,tags:ls_getTags()}; },
    async addTag(tag){ const r = await post('add_tag', {tag}); if (r.ok) return r; const t = ls_getTags(); if (!t.includes(tag)) t.push(tag); localStorage.setItem('rfid_allowed_v1', JSON.stringify(t)); return {ok:true,tags:t}; },
    async removeTag(tag){ const r = await post('remove_tag', {tag}); if (r.ok) return r; let t = ls_getTags(); t = t.filter(x=>x!==tag); localStorage.setItem('rfid_allowed_v1', JSON.stringify(t)); return {ok:true,tags:t}; },
    async getRegistry(){ const r = await post('get_registry', {}); if (r.ok) return r; return {ok:true,registry:ls_getRegistry()}; },
    async toggleRegistry(tag, deviceKey){ const r = await post('toggle_registry', Object.assign({}, deviceKey?{device_key:deviceKey}:{}, {tag})); if (r.ok) return r; const reg = ls_getRegistry(); const now = new Date().toISOString(); if (!reg[tag] || (reg[tag].in && reg[tag].out)){ reg[tag] = {in:now,out:null}; } else if (reg[tag].in && !reg[tag].out){ reg[tag].out=now; } localStorage.setItem('rfid_registry_v1', JSON.stringify(reg)); return {ok:true,registry:reg}; },
    async deleteRegistryEntry(tag){ const r = await post('delete_registry_entry', {tag}); if (r.ok) return r; const reg = ls_getRegistry(); delete reg[tag]; localStorage.setItem('rfid_registry_v1', JSON.stringify(reg)); return {ok:true,registry:reg}; },
    async clearRegistry(){ const r = await post('clear_registry', {}); if (r.ok) return r; localStorage.setItem('rfid_registry_v1', JSON.stringify({})); return {ok:true,registry:{}}; },
    async getCustomers(){ return await post('get_customers', {}); },
    async getVehiclesInside(){ return await post('get_vehicles_inside', {}); }
  };
})();
