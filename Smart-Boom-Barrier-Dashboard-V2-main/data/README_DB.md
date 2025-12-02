SQLite database for RFID Dashboard

Files:
- `data/schema.sql` — SQL schema to create `authorized_users` and `access_logs` tables.
- `data/init_db.php` — simple PHP initializer that creates `data/rfid.db` and seeds a demo user if authorized_users is empty.

How to initialize (PowerShell):

```powershell
# from project root
php .\data\init_db.php
```

The script prints JSON with `ok:true` on success. After the DB is created you can modify `api.php` to use `data/rfid.db` (SQLite) for reads/writes, or I can update `api.php` to detect and use the DB while keeping JSON fallbacks.

Notes:
- `authorized_users` columns: `id`, `uid` (e.g. "1A:2B:3C:4D" or serial), `name`, `access_level`.
- `access_logs` columns: `id`, `uid`, `status` ("Authorized" / "Denied"), `timestamp`.
