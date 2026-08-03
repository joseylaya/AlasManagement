<?php
// ─────────────────────────────────────────────────────────────────────────────
// ALAS OS — Lightweight SQLite Database Browser
// Self-contained, no dependencies. Local dev only.
// ─────────────────────────────────────────────────────────────────────────────

// Load APP_ENV from .env
$envFile = __DIR__ . '/../.env';
$appEnv  = 'production';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if (str_starts_with($line, 'APP_ENV=')) {
            $appEnv = trim(substr($line, 8));
        }
    }
}
$dbAdminEnabled = file_exists($envFile)
    && str_contains((string) file_get_contents($envFile), 'DBADMIN_ENABLED=true');

if (!in_array($appEnv, ['local', 'development', 'dev', 'testing']) && ! $dbAdminEnabled) {
    http_response_code(403); die('<h1>403 Forbidden — DB Admin disabled in production.</h1>');
}

// ── Config ───────────────────────────────────────────────────────────────────
$DB_PATH = realpath(__DIR__ . '/../database/database.sqlite');

if (!$DB_PATH || !file_exists($DB_PATH)) {
    die('<p style="font-family:monospace;color:red">SQLite file not found: ' . htmlspecialchars(__DIR__ . '/../database/database.sqlite') . '</p>');
}

// ── Connect ──────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('<p style="color:red">DB connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function getTables($pdo) {
    return $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
               ->fetchAll(PDO::FETCH_COLUMN);
}

function getRowCount($pdo, $table) {
    try {
        return $pdo->query("SELECT COUNT(*) FROM " . quoteId($table))->fetchColumn();
    } catch (Exception $e) { return '?'; }
}

function getColumns($pdo, $table) {
    return $pdo->query("PRAGMA table_info(" . quoteId($table) . ")")->fetchAll(PDO::FETCH_ASSOC);
}

function quoteId($name) {
    return '"' . str_replace('"', '""', $name) . '"';
}

// ── Request Handling ─────────────────────────────────────────────────────────
$action  = $_GET['action'] ?? 'tables';
$table   = $_GET['table'] ?? '';
$page    = max(0, (int)($_GET['page'] ?? 0));
$perPage = 50;
$message = '';
$error   = '';

// Execute custom SQL
$sqlResult   = null;
$sqlColumns  = [];
$sqlQuery    = $_POST['sql'] ?? '';
if ($action === 'sql' && $sqlQuery) {
    try {
        $stmt = $pdo->query($sqlQuery);
        if ($stmt) {
            $sqlResult  = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sqlColumns = $sqlResult ? array_keys($sqlResult[0]) : [];
            $message = 'Query executed. Rows: ' . count($sqlResult);
        } else {
            $message = 'Query executed (no result set).';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Delete a row
if ($action === 'delete' && $table && $_POST) {
    $where = $_POST['where'] ?? '';
    if ($where) {
        try {
            $pdo->exec("DELETE FROM " . quoteId($table) . " WHERE rowid = " . (int)$where);
            $message = 'Row deleted.';
            $action = 'browse';
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
}

// Tables list
$tables = getTables($pdo);
$selectedTable = $table && in_array($table, $tables) ? $table : '';

// Browse data
$rows    = [];
$columns = [];
if ($action === 'browse' && $selectedTable) {
    $columns = getColumns($pdo, $selectedTable);
    $total   = getRowCount($pdo, $selectedTable);
    $offset  = $page * $perPage;
    try {
        $rows = $pdo->query("SELECT rowid as __rowid, * FROM " . quoteId($selectedTable) . " LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ALAS OS · DB Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#F2F2F2;--white:#fff;--border:#E8E8E8;--border2:#F0F0F0;
  --text:#111;--muted:#888;--accent:#111;
  --green-bg:#ECFDF5;--green-border:#6EE7B7;--green-text:#065F46;
  --red-bg:#FEF2F2;--red-border:#FCA5A5;--red-text:#991B1B;
}
html,body{height:100%;font-family:'Inter',sans-serif;font-size:13px;background:var(--bg);color:var(--text)}
a{color:var(--text);text-decoration:none}
a:hover{text-decoration:underline}
.layout{display:flex;height:100vh;overflow:hidden}

/* Sidebar */
.sidebar{width:220px;flex-shrink:0;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.sidebar-header{padding:16px 16px 12px;border-bottom:1px solid var(--border2)}
.sidebar-logo{font-size:14px;font-weight:800;letter-spacing:-.3px}
.sidebar-sub{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}
.sidebar-nav{flex:1;overflow-y:auto;padding:10px 8px}
.nav-section{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;padding:8px 8px 4px}
.nav-item{display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-radius:7px;font-size:12px;font-weight:500;color:var(--muted);cursor:pointer;margin-bottom:1px;transition:background .1s,color .1s}
.nav-item:hover,.nav-item.active{background:#F5F5F5;color:var(--text)}
.nav-item .badge{font-size:10px;font-weight:600;background:#F0F0F0;color:var(--muted);padding:1px 6px;border-radius:20px}
.sidebar-footer{border-top:1px solid var(--border2);padding:12px 16px;font-size:11px;color:var(--muted)}
.db-path{font-size:10px;color:var(--muted);word-break:break-all;margin-top:3px;line-height:1.4}

/* Main */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{height:50px;background:var(--white);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:12px;flex-shrink:0}
.topbar-title{font-size:14px;font-weight:700;letter-spacing:-.2px}
.topbar-sep{color:var(--border);font-size:18px}
.topbar-sub{font-size:12px;color:var(--muted);font-weight:500}
.tab-bar{display:flex;height:38px;background:var(--white);border-bottom:1px solid var(--border);padding:0 20px;gap:0;flex-shrink:0}
.tab{height:100%;display:flex;align-items:center;padding:0 14px;font-size:12px;font-weight:600;border-bottom:2px solid transparent;color:var(--muted);cursor:pointer;transition:color .15s,border-color .15s}
.tab:hover{color:var(--text)}
.tab.active{color:var(--text);border-bottom-color:var(--text)}
.content{flex:1;overflow:auto;padding:20px}

/* Cards */
.card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border2);display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700}

/* Messages */
.msg{padding:10px 14px;border-radius:8px;font-size:12px;font-weight:500;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.msg.success{background:var(--green-bg);border:1px solid var(--green-border);color:var(--green-text)}
.msg.error{background:var(--red-bg);border:1px solid var(--red-border);color:var(--red-text)}

/* Tables */
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#F8F8F8;font-size:11px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:.04em;padding:9px 12px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap;position:sticky;top:0;z-index:1}
.data-table td{padding:9px 12px;border-bottom:1px solid var(--border2);font-size:12px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.data-table tr:last-child td{border-bottom:none}
.data-table tbody tr:hover td{background:#FAFAFA}
.null-val{color:var(--muted);font-style:italic;font-size:11px}
.pk-val{font-weight:700;color:#444}

/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:18px}
.stat-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.stat-label{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
.stat-value{font-size:22px;font-weight:800;margin-top:4px;letter-spacing:-.5px}
.stat-sub{font-size:11px;color:var(--muted);margin-top:2px}

/* SQL editor */
.sql-editor{width:100%;height:120px;font-family:'SF Mono','Monaco','Courier New',monospace;font-size:12px;padding:12px;border:1px solid var(--border);border-radius:8px;resize:vertical;outline:none;background:#FAFAFA;color:var(--text);line-height:1.5}
.sql-editor:focus{border-color:#AAAAAA;background:var(--white)}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:opacity .15s;text-decoration:none}
.btn-primary{background:var(--text);color:#fff}
.btn-primary:hover{opacity:.8;text-decoration:none;color:#fff}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{background:#F5F5F5;color:var(--text);text-decoration:none}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.btn-danger:hover{background:#FEE2E2;text-decoration:none;color:#DC2626}

/* Pagination */
.pagination{display:flex;align-items:center;gap:8px;margin-top:14px}
.page-info{font-size:12px;color:var(--muted)}

/* Empty state */
.empty{text-align:center;padding:48px 20px;color:var(--muted)}
.empty-icon{font-size:32px;margin-bottom:8px}
.empty-title{font-size:14px;font-weight:600;color:#555;margin-bottom:4px}
.empty-sub{font-size:12px}

/* Structure table */
.struct-table{width:100%;border-collapse:collapse}
.struct-table th{background:#F8F8F8;font-size:11px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:.04em;padding:8px 12px;text-align:left;border-bottom:1px solid var(--border)}
.struct-table td{padding:8px 12px;border-bottom:1px solid var(--border2);font-size:12px}
.type-badge{display:inline-block;background:#F0F0F0;color:#555;padding:1px 7px;border-radius:4px;font-size:10px;font-weight:600;font-family:monospace}
.pk-badge{background:#EEF2FF;color:#4F46E5}
.nn-badge{background:#FFF7ED;color:#C2410C}
</style>
</head>
<body>
<div class="layout">

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">ALAS OS</div>
      <div class="sidebar-sub">Database Admin</div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Actions</div>
      <a href="?action=tables" class="nav-item <?= $action==='tables'?'active':'' ?>">
        📊 Overview
      </a>
      <a href="?action=sql" class="nav-item <?= $action==='sql'?'active':'' ?>">
        ⌨️ SQL Console
      </a>

      <div class="nav-section" style="margin-top:10px">Tables</div>
      <?php foreach ($tables as $t): ?>
      <a href="?action=browse&table=<?= urlencode($t) ?>" class="nav-item <?= ($selectedTable===$t&&$action==='browse')?'active':'' ?>">
        <span><?= h($t) ?></span>
        <span class="badge"><?= getRowCount($pdo, $t) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      SQLite · Local
      <div class="db-path"><?= h(basename($DB_PATH)) ?></div>
    </div>
  </aside>

  <!-- ── MAIN ── -->
  <main class="main">
    <div class="topbar">
      <span class="topbar-title">DB Admin</span>
      <span class="topbar-sep">/</span>
      <span class="topbar-sub">
        <?php if ($action==='browse' && $selectedTable): ?>
          <?= h($selectedTable) ?>
        <?php elseif ($action==='sql'): ?>
          SQL Console
        <?php elseif ($action==='structure' && $selectedTable): ?>
          <?= h($selectedTable) ?> · Structure
        <?php else: ?>
          Overview
        <?php endif; ?>
      </span>
    </div>

    <?php if ($selectedTable && in_array($action, ['browse','structure'])): ?>
    <div class="tab-bar">
      <a href="?action=browse&table=<?= urlencode($selectedTable) ?>" class="tab <?= $action==='browse'?'active':'' ?>">Browse</a>
      <a href="?action=structure&table=<?= urlencode($selectedTable) ?>" class="tab <?= $action==='structure'?'active':'' ?>">Structure</a>
    </div>
    <?php endif; ?>

    <div class="content">

      <?php if ($message): ?>
        <div class="msg success">✓ <?= h($message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="msg error">✗ <?= h($error) ?></div>
      <?php endif; ?>

      <?php /* ════ OVERVIEW ════ */ if ($action === 'tables'): ?>

        <div class="stats-grid">
          <?php foreach ($tables as $t): $cnt = getRowCount($pdo, $t); ?>
          <div class="stat-card">
            <div class="stat-label"><?= h($t) ?></div>
            <div class="stat-value"><?= number_format($cnt) ?></div>
            <div class="stat-sub">
              <a href="?action=browse&table=<?= urlencode($t) ?>">Browse →</a>
              &nbsp;·&nbsp;
              <a href="?action=structure&table=<?= urlencode($t) ?>">Structure</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Database Info</span>
          </div>
          <div style="padding:16px 18px">
            <table class="struct-table">
              <tr><td style="color:var(--muted);width:160px;font-weight:500">File</td><td><?= h($DB_PATH) ?></td></tr>
              <tr><td style="color:var(--muted);font-weight:500">Size</td><td><?= number_format(filesize($DB_PATH) / 1024, 1) ?> KB</td></tr>
              <tr><td style="color:var(--muted);font-weight:500">Tables</td><td><?= count($tables) ?></td></tr>
              <tr><td style="color:var(--muted);font-weight:500">SQLite version</td><td><?= $pdo->query('SELECT sqlite_version()')->fetchColumn() ?></td></tr>
            </table>
          </div>
        </div>

      <?php /* ════ BROWSE ════ */ elseif ($action === 'browse' && $selectedTable): ?>

        <div class="card">
          <div class="card-header">
            <span class="card-title"><?= h($selectedTable) ?> <span style="font-weight:400;color:var(--muted)">(<?= getRowCount($pdo, $selectedTable) ?> rows)</span></span>
            <div style="display:flex;gap:8px">
              <?php if ($page > 0): ?>
                <a href="?action=browse&table=<?= urlencode($selectedTable) ?>&page=<?= $page-1 ?>" class="btn btn-ghost">← Prev</a>
              <?php endif; ?>
              <?php if (count($rows) === $perPage): ?>
                <a href="?action=browse&table=<?= urlencode($selectedTable) ?>&page=<?= $page+1 ?>" class="btn btn-ghost">Next →</a>
              <?php endif; ?>
            </div>
          </div>

          <?php if (empty($rows)): ?>
            <div class="empty">
              <div class="empty-icon">📭</div>
              <div class="empty-title">No rows yet</div>
              <div class="empty-sub">This table is empty.</div>
            </div>
          <?php else: ?>
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr>
                  <?php foreach (array_keys($rows[0]) as $col): ?>
                    <?php if ($col === '__rowid') continue; ?>
                    <th><?= h($col) ?></th>
                  <?php endforeach; ?>
                  <th style="width:60px">Del</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                  <?php foreach ($row as $col => $val): ?>
                    <?php if ($col === '__rowid') continue; ?>
                    <td title="<?= h($val) ?>">
                      <?php if ($val === null): ?>
                        <span class="null-val">NULL</span>
                      <?php elseif ($col === 'id'): ?>
                        <span class="pk-val"><?= h($val) ?></span>
                      <?php else: ?>
                        <?= h(mb_strimwidth((string)$val, 0, 80, '…')) ?>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                  <td>
                    <form method="post" action="?action=delete&table=<?= urlencode($selectedTable) ?>&page=<?= $page ?>" onsubmit="return confirm('Delete row #<?= h($row['__rowid']) ?>?')">
                      <input type="hidden" name="where" value="<?= h($row['__rowid']) ?>">
                      <button type="submit" class="btn btn-danger" style="padding:3px 9px;font-size:11px">✕</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div style="padding:12px 16px;border-top:1px solid var(--border2);display:flex;align-items:center;gap:8px">
            <?php if ($page > 0): ?>
              <a href="?action=browse&table=<?= urlencode($selectedTable) ?>&page=<?= $page-1 ?>" class="btn btn-ghost">← Prev</a>
            <?php endif; ?>
            <span class="page-info">Page <?= $page+1 ?> · rows <?= $page*$perPage+1 ?>–<?= $page*$perPage+count($rows) ?></span>
            <?php if (count($rows) === $perPage): ?>
              <a href="?action=browse&table=<?= urlencode($selectedTable) ?>&page=<?= $page+1 ?>" class="btn btn-ghost">Next →</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

      <?php /* ════ STRUCTURE ════ */ elseif ($action === 'structure' && $selectedTable): ?>

        <div class="card">
          <div class="card-header">
            <span class="card-title"><?= h($selectedTable) ?> · Structure</span>
          </div>
          <div style="overflow-x:auto">
            <table class="struct-table">
              <thead>
                <tr>
                  <th>#</th><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th><th>Flags</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (getColumns($pdo, $selectedTable) as $col): ?>
                <tr>
                  <td style="color:var(--muted)"><?= h($col['cid']) ?></td>
                  <td><strong><?= h($col['name']) ?></strong></td>
                  <td><span class="type-badge"><?= h($col['type'] ?: 'any') ?></span></td>
                  <td><?= $col['notnull'] ? '<span style="color:#C2410C">NOT NULL</span>' : '<span style="color:var(--muted)">nullable</span>' ?></td>
                  <td><?= $col['dflt_value'] !== null ? h($col['dflt_value']) : '<span style="color:var(--muted)">—</span>' ?></td>
                  <td><?= $col['pk'] ? '<span class="type-badge pk-badge">PK</span>' : '' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Indexes -->
        <?php
        $indexes = $pdo->query("PRAGMA index_list(" . quoteId($selectedTable) . ")")->fetchAll(PDO::FETCH_ASSOC);
        if ($indexes):
        ?>
        <div class="card" style="margin-top:14px">
          <div class="card-header"><span class="card-title">Indexes</span></div>
          <div style="overflow-x:auto">
            <table class="struct-table">
              <thead><tr><th>Name</th><th>Unique</th><th>Columns</th></tr></thead>
              <tbody>
                <?php foreach ($indexes as $idx):
                  $idxCols = $pdo->query("PRAGMA index_info(" . quoteId($idx['name']) . ")")->fetchAll(PDO::FETCH_ASSOC);
                  $colNames = implode(', ', array_column($idxCols, 'name'));
                ?>
                <tr>
                  <td><?= h($idx['name']) ?></td>
                  <td><?= $idx['unique'] ? '<span style="color:#059669;font-weight:600">Yes</span>' : 'No' ?></td>
                  <td><code><?= h($colNames) ?></code></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      <?php /* ════ SQL CONSOLE ════ */ elseif ($action === 'sql'): ?>

        <div class="card">
          <div class="card-header">
            <span class="card-title">SQL Console</span>
            <span style="font-size:11px;color:var(--muted)">SQLite · <?= h(basename($DB_PATH)) ?></span>
          </div>
          <div style="padding:16px 18px">
            <form method="post" action="?action=sql">
              <textarea name="sql" class="sql-editor" placeholder="SELECT * FROM users LIMIT 10;"><?= h($sqlQuery) ?></textarea>
              <div style="margin-top:10px;display:flex;gap:8px;align-items:center">
                <button type="submit" class="btn btn-primary">▶ Run Query</button>
                <span style="font-size:11px;color:var(--muted)">Ctrl+Enter also works</span>
              </div>
            </form>
          </div>
        </div>

        <?php if ($sqlResult !== null): ?>
        <div class="card" style="margin-top:14px">
          <div class="card-header">
            <span class="card-title">Results <span style="font-weight:400;color:var(--muted)">(<?= count($sqlResult) ?> rows)</span></span>
          </div>
          <?php if (empty($sqlResult)): ?>
            <div class="empty"><div class="empty-icon">✓</div><div class="empty-title">Query executed — no rows returned</div></div>
          <?php else: ?>
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead><tr><?php foreach ($sqlColumns as $c): ?><th><?= h($c) ?></th><?php endforeach; ?></tr></thead>
              <tbody>
                <?php foreach ($sqlResult as $row): ?>
                <tr>
                  <?php foreach ($row as $val): ?>
                  <td title="<?= h($val) ?>">
                    <?php if ($val === null): ?>
                      <span class="null-val">NULL</span>
                    <?php else: ?>
                      <?= h(mb_strimwidth((string)$val, 0, 120, '…')) ?>
                    <?php endif; ?>
                  </td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <script>
        document.querySelector('.sql-editor').addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
        </script>

      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
