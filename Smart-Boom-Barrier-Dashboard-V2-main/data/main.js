(() => {
  const STORAGE = 'rfid_allowed_v1';
  const REG_KEY = 'rfid_registry_v1';

  // ------------------- STORAGE -------------------
  function loadAllowed(){ try{ return JSON.parse(localStorage.getItem(STORAGE) || '[]'); }catch(e){return []} }
  function saveAllowed(arr){ localStorage.setItem(STORAGE, JSON.stringify(arr)); }

  // We'll keep both the original tags (for display) and a normalized set for matching
  let allowedRaw = loadAllowed().length ? loadAllowed() : ["TAG-D9BBB311","TAG-D9BBB311","TAG-1003","EMP-42"];
  const allowedNorm = new Set();

  function normalizeTag(t){ return String(t||'').toUpperCase().replace(/[^A-Z0-9]/g,''); }
  function buildNormSet(){
    allowedNorm.clear();
    for (const t of allowedRaw) allowedNorm.add(normalizeTag(t));
  }

  // initial build
  buildNormSet();

  function loadRegistry(){ try{ return JSON.parse(localStorage.getItem(REG_KEY) || '{}'); }catch(e){return {}} }
  function saveRegistry(o){ localStorage.setItem(REG_KEY, JSON.stringify(o)); }

  // ------------------- ELEMENTS -------------------
  const tagInput = document.getElementById('tagInput');
  const scanBtn = document.getElementById('scanBtn');
  const randomBtn = document.getElementById('randomBtn');
  const allowedTagsEl = document.getElementById('allowedTags');
  const logEl = document.getElementById('log');
  const statusEl = document.getElementById('status');
  const lastTagEl = document.getElementById('lastTag');
  const gate = document.getElementById('gate');
  const clearLogBtn = document.getElementById('clearLog');
  const registryEl = document.getElementById('registry');

// ------------------- MODAL -------------------
const modal = document.createElement('div');
modal.id = 'confirmModal';
modal.style.cssText = `
  display:none;
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.5);
  justify-content:center;
  align-items:center;
  z-index:1000;
`;

modal.innerHTML = `
  <div style="background:#111;padding:20px;border-radius:8px;min-width:300px;text-align:center;color:inherit;">
    <p id="confirmText" style="margin-bottom:16px;">Are you sure?</p>
    <div style="margin-top:16px; display:flex; justify-content:center; gap:12px;">
      <button id="confirmYes" style="padding:8px 12px;border-radius:8px;border:0;background:linear-gradient(90deg,#3b82f6,#60a5fa);color:#012;cursor:pointer;">Yes</button>
      <button id="confirmNo" style="padding:8px 12px;border-radius:8px;border:0;background:rgba(255,255,255,0.03);color:#fff;cursor:pointer;">No</button>
    </div>
  </div>
`;

document.body.appendChild(modal);




  function showConfirm(message) {
    return new Promise((resolve)=>{
      const text = document.getElementById('confirmText');
      const yesBtn = document.getElementById('confirmYes');
      const noBtn = document.getElementById('confirmNo');

      text.textContent = message;
      modal.style.display = 'flex';

      function cleanUp(){
        yesBtn.removeEventListener('click', yesFn);
        noBtn.removeEventListener('click', noFn);
        modal.style.display = 'none';
      }

      function yesFn(){ cleanUp(); resolve(true); }
      function noFn(){ cleanUp(); resolve(false); }

      yesBtn.addEventListener('click', yesFn);
      noBtn.addEventListener('click', noFn);
    });
  }

  // ------------------- RENDER FUNCTIONS -------------------
  function renderAllowed() {
    allowedTagsEl.innerHTML = '';
    for (const t of allowedRaw){
      const el = document.createElement('div');
      el.className = 'tag';
      el.textContent = t;
      allowedTagsEl.appendChild(el);
    }
  }

  function renderRegistry(){
    if (!registryEl) return;
    const reg = loadRegistry();
    registryEl.innerHTML = '';

    const ids = Object.keys(reg).sort();
    if (ids.length === 0){
      registryEl.textContent = 'No entries';
      return;
    }

    for (const id of ids){
      const entry = reg[id];
      const d = document.createElement('div');
      d.className = 'tag registry-row';
      d.dataset.tag = id;
      d.style.position = 'relative';
      d.style.padding = '8px';

      const inT = entry.in ? new Date(entry.in).toLocaleString() : '—';
      const outT = entry.out ? new Date(entry.out).toLocaleString() : '—';

      let extra = '';
      if (entry.in && entry.out){
        const dur = Math.max(0, new Date(entry.out) - new Date(entry.in));
        extra = ` — ${Math.round(dur/60000)} min`;
      }

      const statusText = entry.in && !entry.out ? 'IN' : (entry.out ? 'OUT' : '—');

      const badge = `
        <span style="margin-left:8px;padding:4px 8px;border-radius:8px;
        background:rgba(255,255,255,0.05);font-size:12px;color:var(--muted)">
        ${statusText}</span>`;

      d.innerHTML = `<strong>${id}</strong>${badge}
      <div style="font-size:12px;color:var(--muted);margin-top:6px">
        In: ${inT} | Out: ${outT}${extra}
      </div>`;

      const btns = document.createElement('div');
      btns.style.position='absolute';
      btns.style.right='8px';
      btns.style.top='8px';

      const del = document.createElement('button');
      del.textContent = 'Delete';
      del.className = 'btn registry-del';

      btns.appendChild(del);
      d.appendChild(btns);
      registryEl.appendChild(d);
    }
  }

  // ------------------- LOG -------------------
  function log(msg, type='info'){
    const row = document.createElement('div');
    row.className = 'log-row ' + 
      (type==='success' ? 'success' : type==='fail' ? 'fail' : '');
    row.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
    logEl.prepend(row);
  }

  function setStatus(text, cls){
    statusEl.textContent = text;
    statusEl.style.color = cls || '';
  }

  // ------------------- BARRIER -------------------
  let barrierOpen = false;
  let autoCloseTimer = null;

  function openBarrier() {
    if (barrierOpen) return;
    barrierOpen = true;
    gate.style.transform = 'translateX(-50%) rotate(-70deg)';
    setStatus('Open', 'var(--success)');
    log('Barrier opened', 'success');

    if (autoCloseTimer) clearTimeout(autoCloseTimer);
    autoCloseTimer = setTimeout(closeBarrier, 2000);
  }

  function closeBarrier(){
    if (!barrierOpen) return;
    barrierOpen = false;
    gate.style.transform = 'translateX(-50%) rotate(0deg)';
    setStatus('Closed', 'var(--muted)');
    log('Barrier closed');
    lastTagEl.textContent = '—';
  }

  // ------------------- SCAN -------------------
  const SCAN_COOLDOWN_MS = 3000; // ms
  let lastScanTime = 0;
  let lastScanId = '';

  function isScanAllowed(id){
    const now = Date.now();
    if (lastScanId === id && (now - lastScanTime) < SCAN_COOLDOWN_MS) return false;
    lastScanId = id;
    lastScanTime = now;
    return true;
  }

  function disableScanTemporarily(){
    scanBtn.disabled = true;
    setTimeout(()=>{ scanBtn.disabled = false; }, SCAN_COOLDOWN_MS);
  }

  function doScan(tag) {
    const id = (tag || tagInput.value || '').trim();
    if (!id){
      setStatus('No tag', 'var(--danger)');
      log('Error: No tag provided', 'fail');
      return;
    }

    if (!isScanAllowed(id)){
      log(`Ignored duplicate scan: ${id}`);
      return;
    }

    disableScanTemporarily();

    lastTagEl.textContent = id;
    setStatus('Reading...');
    log(`Read tag ${id}`);

    setTimeout(()=>{
      if (!allowedNorm.has(normalizeTag(id))){
        setStatus('Access Denied', 'var(--danger)');
        log(`Access denied: ${id}`, 'fail');
        if (navigator.vibrate) navigator.vibrate(100);
        return;
      }

      setStatus('Authorized', 'var(--success)');
      log(`Authorized: ${id}`, 'success');

      const reg = loadRegistry();
      const now = new Date().toISOString();
      const entry = reg[id] || { in: null, out: null };

      if (!entry.in){
        entry.in = now;
        entry.out = null;
        log(`${id} checked IN`, 'success');
      }
      else if (entry.in && !entry.out){
        entry.out = now;
        log(`${id} checked OUT`, 'success');
      }
      else {
        entry.in = now;
        entry.out = null;
        log(`${id} checked IN`, 'success');
      }

      reg[id] = entry;
      saveRegistry(reg);
      renderRegistry();
      openBarrier();
    }, 650);
  }

  // ------------------- BUTTONS -------------------
  scanBtn.addEventListener('click', ()=> doScan());
  randomBtn.addEventListener('click', ()=>{
    const pool = ['TAG-D9BBB311','TAG-D9BBB311','TAG-1003','TAG-9000','GUEST-1','EMP-42','Z-777'];
    const pick = pool[Math.floor(Math.random()*pool.length)];
    tagInput.value = pick;
    doScan(pick);
  });
  clearLogBtn.addEventListener('click', ()=> logEl.innerHTML='');

  // ------------------- DELETE (ADMIN ONLY) -------------------
  if (registryEl){
    registryEl.addEventListener('click', async (e)=>{
      const btn = e.target.closest('button');
      if (!btn) return;
      const row = e.target.closest('.registry-row');
      if (!row) return;

      const tag = row.dataset.tag;

      const user = window.auth && window.auth.getCurrentUser ? window.auth.getCurrentUser() : null;
      if (!user || user.role !== 'admin') {
          alert("Only admin can delete entries.");
          return;
      }

      if (btn.classList.contains('registry-del')){
          const confirmed = await showConfirm(`Delete registry entry for ${tag}?`);
          if (!confirmed) return;

          const reg = loadRegistry();
          delete reg[tag];
          saveRegistry(reg);
          renderRegistry();
      }
    });
  }

  // ------------------- INIT -------------------
  // Try to load tags from backend (server) and fall back to localStorage/defaults
  (function initTags(){
    if (window.backend && typeof window.backend.getTags === 'function'){
      window.backend.getTags().then(res=>{
        if (res && res.ok && Array.isArray(res.tags) && res.tags.length){
          allowedRaw = res.tags;
          saveAllowed(allowedRaw);
          buildNormSet();
          renderAllowed();
        } else {
          // keep local defaults
          renderAllowed();
        }
      }).catch(()=> renderAllowed());
    } else {
      renderAllowed();
    }

    renderRegistry();
    setStatus('Idle');
  })();

  // expose for debug
  window._rfid = {
    allowedRaw,
    allowedNorm,
    normalizeTag,
    doScan,
    openBarrier,
    closeBarrier
  };

  // ------------------- WEBSOCKET (live scans) -------------------
  (function(){
    const DEFAULT_WS = 'ws://192.168.4.1/ws';
    let wsUrl = localStorage.getItem('rfid_ws_url') || DEFAULT_WS;
    let socket = null;
    let reconnectMs = 2000;

    function setStatus(text){
      const el = document.getElementById('ws_status');
      if (el) el.textContent = text;
    }

    function startWS(){
      try {
        socket = new WebSocket(wsUrl);
      } catch(e){
        console.warn('WS create failed', e);
        setStatus('Error');
        return setTimeout(startWS, reconnectMs);
      }

      socket.onopen = () => {
        console.log('WebSocket connected to', wsUrl);
        setStatus('Connected');
      };

      socket.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          const tag = data.tag || data.uid || data.rfid;
          if (tag) {
            // update small RFID display
            const rv = document.getElementById('rfid_value');
            if (rv) rv.innerHTML = tag;

            // prepend to RFID history box if present
            const history = document.getElementById('rfid_list');
            if (history) {
              const entry = document.createElement('div');
              entry.style.padding = '4px 0';
              entry.textContent = new Date().toLocaleTimeString() + ' — ' + tag;
              history.prepend(entry);
            }

            // show briefly in input and perform the normal scan flow
            const inp = document.getElementById('tagInput');
            if (inp) inp.value = tag;
            doScan(tag);

            // also add to main log for consistency
            const log = document.getElementById('log');
            if (log) {
              const entry = document.createElement('div');
              entry.textContent = new Date().toLocaleTimeString() + ' — ' + tag;
              log.prepend(entry);
            }
          }
        } catch(e){
          console.log('WS invalid JSON', event.data);
        }
      };

      socket.onclose = () => {
        console.log('WebSocket closed, reconnecting...');
        setStatus('Reconnecting...');
        setTimeout(startWS, reconnectMs);
      };

      socket.onerror = (e) => {
        console.warn('WebSocket error', e);
      };
    }

    // start automatically
    startWS();
  })();
})();
