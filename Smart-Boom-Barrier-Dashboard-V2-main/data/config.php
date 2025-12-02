<?php
// Basic configuration for the ESP32 endpoint and audit rotation.
// Edit values here and keep this file outside public version control if possible.
return [
    // Replace with a secure random string and keep secret
    'ESP32_API_KEY' => 'REPLACE_WITH_SECRET',

    // Maximum bytes for the audit log before rotation (default 5 MB)
    'AUDIT_MAX_BYTES' => 5 * 1024 * 1024,

    // Archive directory for rotated audit logs (absolute path)
    'AUDIT_ARCHIVE_DIR' => __DIR__ . '/data/audit_archive'

    // MySQL database settings for RFID scan logging
    ,'DB_HOST' => 'localhost'
    ,'DB_NAME' => 'dashboard'
    ,'DB_USER' => 'root'
    ,'DB_PASS' => ''
    ,'DB_CHARSET' => 'utf8mb4'
];

?>