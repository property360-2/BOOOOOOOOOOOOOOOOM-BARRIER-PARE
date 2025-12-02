/*
 Simple Express replacement for existing PHP endpoints.
 Usage:
  - Install deps: `npm install` (run in `data/` directory)
  - Start server: `node server.js`
  - Server exposes: `/esp32_post.php`, `/api.php`, `/admin_audit.php`

 Notes:
  - Keeps same paths to make the static front-end (index.html) work without changes.
  - Stores data in `./data/` files: `esp32.json`, `esp32_audit.log`, `tags.json`, `registry.json`.
  - Configure API key via env `ESP32_API_KEY` (default `esp32-demo-key`).
*/

const express = require('express');
const fs = require('fs');
const path = require('path');
const bodyParser = require('body-parser');
const cors = require('cors');

const DATA_DIR = path.resolve(__dirname);
const ESP32_JSON = path.join(DATA_DIR, 'esp32.json');
const AUDIT_LOG = path.join(DATA_DIR, 'esp32_audit.log');
const TAGS_JSON = path.join(DATA_DIR, 'tags.json');
const REGISTRY_JSON = path.join(DATA_DIR, 'registry.json');
const USERS_JSON = path.join(DATA_DIR, 'users.json');

const API_KEY = process.env.ESP32_API_KEY || 'esp32-demo-key';

const app = express();
app.use(cors());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

function nowISO() { return new Date().toISOString().replace('T',' ').split('.')[0]; }

function readJson(file, def) {
  try { return JSON.parse(fs.readFileSync(file, 'utf8')||''); } catch(e){ return def; }
}
function writeJson(file, obj) { fs.writeFileSync(file, JSON.stringify(obj, null, 2)); }
function appendAudit(entry){ fs.appendFileSync(AUDIT_LOG, JSON.stringify(entry) + '\n'); }

// Register modular endpoints
const registerEsp32Post = require('./esp32_post');
const registerApi = require('./api');
const registerAdminAudit = require('./admin_audit');

// Optionally initialize DB when USE_DB=1
let dbInstance = null;
if (process.env.USE_DB === '1' || process.env.USE_DB === 'true'){
  try{
    const openDb = require('./db');
    dbInstance = openDb(process.env.DB_PATH);
    console.log('SQLite DB initialized');
  } catch(e){
    console.error('Failed to initialize DB:', e.message || e);
  }
}

const opts = {
  esp32Json: ESP32_JSON,
  auditLog: AUDIT_LOG,
  tagsJson: TAGS_JSON,
  registryJson: REGISTRY_JSON,
  usersJson: USERS_JSON,
  apiKey: API_KEY,
  db: dbInstance
};

registerEsp32Post(app, opts);
registerApi(app, opts);
registerAdminAudit(app, opts);

// Serve static files from data folder to make index.html work if running from `data/`
app.use('/', express.static(DATA_DIR));

const PORT = process.env.PORT || 8000;
app.listen(PORT, () => console.log(`Node server running on http://localhost:${PORT} (ESP32 API key=${API_KEY})`));
