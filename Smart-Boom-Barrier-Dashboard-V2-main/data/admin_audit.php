<?php
// Simple admin viewer for the NDJSON audit file
// Usage: open in browser. Optional query params: ?limit=100&search=TAG-1001

$auditFile = __DIR__ . '/data/esp32_audit.log';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!file_exists($auditFile)) {
    $entries = [];
    $noFile = true;
} else {
    $lines = file($auditFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // iterate from the end for recent entries
    $entries = [];
    for ($i = count($lines) - 1; $i >= 0 && count($entries) < $limit; $i--) {
        $line = $lines[$i];
        $obj = json_decode($line, true);
        if (!$obj) continue;
        if ($search !== '') {
            // simple substring search over uid and ip
            if (stripos($obj['uid'] ?? '', $search) === false && stripos($obj['ip'] ?? '', $search) === false) {
                continue;
            }
        }
        $entries[] = $obj;
    }
    $noFile = false;
}

// Calculate statistics
$totalEntries = count($entries);
$authorizedCount = 0;
$unauthorizedCount = 0;
$uniqueUIDs = [];
$recentIP = '';

foreach ($entries as $e) {
    if (isset($e['authorized']) && $e['authorized']) {
        $authorizedCount++;
    } else {
        $unauthorizedCount++;
    }
    if (isset($e['uid']) && !in_array($e['uid'], $uniqueUIDs)) {
        $uniqueUIDs[] = $e['uid'];
    }
    if (!$recentIP && isset($e['ip'])) {
        $recentIP = $e['ip'];
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Audit Log - ESP32 RFID Access System</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="modern-theme.css" />
    <style>
        .page-header {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(6, 182, 212, 0.05));
            border-bottom: 1px solid var(--glass-border);
            padding: var(--space-xl) 0;
            margin-bottom: var(--space-xl);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-xl);
        }

        .header-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: var(--space-sm);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .header-subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1));
        }

        .stat-icon.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
        }

        .stat-icon.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
        }

        .stat-icon.accent {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(6, 182, 212, 0.1));
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .filter-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-bottom: var(--space-xl);
            box-shadow: var(--shadow-md);
        }

        .filter-form {
            display: flex;
            gap: var(--space-md);
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .export-buttons {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
            margin-bottom: var(--space-lg);
        }

        .table-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
            overflow-x: auto;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .audit-table thead {
            background: rgba(59, 130, 246, 0.1);
        }

        .audit-table th {
            padding: var(--space-md);
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--glass-border);
            white-space: nowrap;
        }

        .audit-table td {
            padding: var(--space-md);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-secondary);
        }

        .audit-table tbody tr {
            transition: background var(--transition-fast);
        }

        .audit-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
        }

        .status-badge.authorized {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-light);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .status-badge.unauthorized {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .uid-code {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-light);
        }

        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: var(--space-md);
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div id="modernNav"></div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="header-title">
                <span><span class="material-icons" style="vertical-align: middle;">assignment</span> Audit Log Viewer</span>
            </h1>
            <p class="header-subtitle">ESP32 RFID access audit log - Showing <?= $totalEntries ?> recent entries (newest first)</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><span class="material-icons">dashboard</span></div>
                    <div class="stat-info">
                        <div class="stat-label">Total Entries</div>
                        <div class="stat-value"><?= $totalEntries ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><span class="material-icons">check_circle</span></div>
                    <div class="stat-info">
                        <div class="stat-label">Authorized</div>
                        <div class="stat-value"><?= $authorizedCount ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><span class="material-icons">cancel</span></div>
                    <div class="stat-info">
                        <div class="stat-label">Unauthorized</div>
                        <div class="stat-value"><?= $unauthorizedCount ?></div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon accent"><span class="material-icons">label</span></div>
                    <div class="stat-info">
                        <div class="stat-label">Unique UIDs</div>
                        <div class="stat-value"><?= count($uniqueUIDs) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="/api.php?action=export_audit_excel" class="btn btn-success">
                <span class="material-icons">download</span>
                <span>Export Audit Log to Excel</span>
            </a>
            <a href="/api.php?action=export_registry_excel" class="btn btn-primary">
                <span class="material-icons">table_chart</span>
                <span>Export Registry to Excel</span>
            </a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="get" class="filter-form">
                <div class="filter-group">
                    <label class="form-label" for="search">Search UID/IP</label>
                    <input
                        id="search"
                        name="search"
                        class="form-input"
                        type="text"
                        placeholder="Enter UID or IP address"
                        value="<?= h($search) ?>"
                    />
                </div>

                <div class="filter-group">
                    <label class="form-label" for="limit">Limit</label>
                    <input
                        id="limit"
                        name="limit"
                        class="form-input"
                        type="number"
                        min="10"
                        max="1000"
                        step="10"
                        value="<?= h($limit) ?>"
                    />
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">search</span>
                    <span>Apply Filter</span>
                </button>

                <?php if ($search || $limit != 200): ?>
                    <a href="admin_audit.php" class="btn btn-secondary">
                        <span class="material-icons">refresh</span>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Audit Table -->
        <?php if ($noFile): ?>
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon"><span class="material-icons" style="font-size: 64px;">assignment</span></div>
                    <h2>No Audit File Found</h2>
                    <p>File path: <?= h($auditFile) ?></p>
                </div>
            </div>
        <?php elseif (empty($entries)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon"><span class="material-icons" style="font-size: 64px;">search_off</span></div>
                    <h2>No Entries Found</h2>
                    <p>Try adjusting your search filters or increasing the limit.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                            <th>UID</th>
                            <th>Authorized</th>
                            <th>Distance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                            <tr>
                                <td><?= h($e['timestamp'] ?? 'N/A') ?></td>
                                <td><?= h($e['ip'] ?? 'N/A') ?></td>
                                <td><span class="uid-code"><?= h($e['uid'] ?? 'N/A') ?></span></td>
                                <td>
                                    <?php if (isset($e['authorized']) && $e['authorized']): ?>
                                        <span class="status-badge authorized">✓ Yes</span>
                                    <?php else: ?>
                                        <span class="status-badge unauthorized">✗ No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($e['distance'] ?? 'N/A') ?></td>
                                <td><?= h($e['status'] ?? 'N/A') ?> (<?= h($e['http_code'] ?? 'N/A') ?>)</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="auth.js"></script>
    <script src="navigation.js"></script>
    <script>
        // Auth guard - admin only
        (function(){
            try {
                const user = auth.getCurrentUser();
                if (!user || user.role !== 'admin') {
                    alert('Admin access required');
                    location.href = 'login.html';
                }
            } catch(e) {
                location.href = 'login.html';
            }
        })();
    </script>
</body>
</html>

<?php
// EOF
?>
