-- RFID Registration Database Schema
-- SQLite3 database for tracking RFID cards, users, and access logs

-- Users table (for dashboard login/admin)
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  role TEXT DEFAULT 'user',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- RFID Cards table (main registry)
CREATE TABLE IF NOT EXISTS rfid_cards (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  card_uid TEXT UNIQUE NOT NULL,
  card_holder_name TEXT NOT NULL,
  card_holder_email TEXT,
  card_holder_phone TEXT,
  department TEXT,
  status TEXT DEFAULT 'active',
  issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  registered_by_user_id INTEGER,
  notes TEXT,
  FOREIGN KEY (registered_by_user_id) REFERENCES users(id)
);

-- Access Registry table (In/Out logs)
CREATE TABLE IF NOT EXISTS access_registry (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  card_uid TEXT NOT NULL,
  event_type TEXT DEFAULT 'check_in',
  event_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  remarks TEXT,
  FOREIGN KEY (card_uid) REFERENCES rfid_cards(card_uid)
);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_rfid_cards_uid ON rfid_cards(card_uid);
CREATE INDEX IF NOT EXISTS idx_rfid_cards_status ON rfid_cards(status);
CREATE INDEX IF NOT EXISTS idx_access_registry_uid ON access_registry(card_uid);
CREATE INDEX IF NOT EXISTS idx_access_registry_time ON access_registry(event_time);
