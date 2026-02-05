<?php
require_once __DIR__ . '/src/Config.php';
use FreqtradeDashboard\Config;

$config = Config::getInstance();
date_default_timezone_set($config->getTimezone());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nginx Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary: #1c2128;
            --border-color: #30363d;
            --text-primary: #e6edf3;
            --text-secondary: #8b949e;
            --accent-green: #3fb950;
            --accent-red: #f85149;
            --accent-blue: #58a6ff;
            --accent-yellow: #d29922;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            font-size: 110%;
        }

        .card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .stat-card {
            text-align: center;
            padding: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.2;
            color: #ffffff;
        }

        .stat-label {
            color: #a0a8b2;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.2rem;
        }

        .mini-table {
            font-size: 0.65rem;
            margin: 0;
            color: #ffffff;
        }

        .mini-table th {
            background: var(--bg-tertiary);
            color: #a0a8b2;
            font-weight: 500;
            padding: 0.25rem 0.3rem;
            border-color: var(--border-color);
        }

        .mini-table td {
            padding: 0.2rem 0.3rem;
            border-color: var(--border-color);
            vertical-align: middle;
        }

        .mini-table tbody tr:hover {
            background: var(--bg-tertiary);
        }

        .section-title {
            font-size: 0.6rem;
            color: #a0a8b2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            padding-bottom: 0.2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .ip-badge {
            background: var(--bg-tertiary);
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.6rem;
            color: #ffffff;
        }

        .badge-status-2xx {
            background-color: rgba(63, 185, 80, 0.2);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
        }

        .badge-status-3xx {
            background-color: rgba(210, 153, 34, 0.2);
            color: var(--accent-yellow);
            border: 1px solid var(--accent-yellow);
        }

        .badge-status-4xx {
            background-color: rgba(248, 81, 73, 0.2);
            color: var(--accent-red);
            border: 1px solid var(--accent-red);
        }

        .badge-status-5xx {
            background-color: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid #a78bfa;
        }

        .time-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .time-just-now { background-color: var(--accent-green); }
        .time-recent { background-color: var(--accent-blue); }
        .time-old { background-color: var(--text-secondary); }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 700px;
            width: 92%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h5 {
            margin: 0;
            color: #fff;
            font-size: 0.9rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.3rem;
            cursor: pointer;
            line-height: 1;
        }

        .modal-close:hover {
            color: #fff;
        }

        .modal-body {
            padding: 0.6rem 0.8rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.7rem;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
        }

        .info-value {
            color: #fff;
            text-align: right;
        }

        .log-line {
            background: var(--bg-tertiary);
            border-left: 3px solid var(--accent-blue);
            padding: 0.4rem 0.5rem;
            margin-bottom: 0.3rem;
            border-radius: 0 4px 4px 0;
            font-family: monospace;
            font-size: 0.55rem;
            word-break: break-all;
            color: var(--text-secondary);
        }

        .btn-view {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--accent-blue);
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-size: 0.55rem;
            cursor: pointer;
        }

        .btn-view:hover {
            background: var(--bg-tertiary);
            color: #fff;
        }

        .trades-scroll {
            max-height: 400px;
            overflow-y: auto;
        }

        .trades-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .trades-scroll::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        .trades-scroll::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }

        .text-profit { color: var(--accent-green) !important; }
        .text-loss { color: var(--accent-red) !important; }
        .text-info { color: var(--accent-blue) !important; }

        @media (max-width: 768px) {
            .hide-mobile { display: none !important; }
            .mini-table { font-size: 0.6rem; }
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-3">
        <?php
        $logFile = '/var/log/nginx/trade_access.log';
        $maxUniqueIPs = 30;
        $requestsPerIP = 20;

        if (!file_exists($logFile)) {
            echo '<div class="card p-3"><p class="text-loss mb-0">Log file not found: ' . htmlspecialchars($logFile) . '</p></div>';
            exit;
        }

        if (!is_readable($logFile)) {
            echo '<div class="card p-3"><p class="text-loss mb-0">Permission denied. Run: sudo chmod 644 ' . htmlspecialchars($logFile) . '</p></div>';
        }

        function parseNginxLine($line) {
            $pattern = '/^(\S+) (\S+) (\S+) \[([^\]]+)\] "(\S+) (\S+) (\S+)" (\d{3}) (\d+) "([^"]*)" "([^"]*)"$/';

            if (preg_match($pattern, $line, $matches)) {
                $timeStr = $matches[4];
                $timestamp = strtotime(str_replace('/', ' ', str_replace(['[', ']'], '', $timeStr)));
                return [
                    'ip' => $matches[1],
                    'remote_user' => $matches[3],
                    'time' => $timeStr,
                    'method' => $matches[5],
                    'url' => $matches[6],
                    'protocol' => $matches[7],
                    'status' => $matches[8],
                    'bytes' => $matches[9],
                    'referer' => $matches[10],
                    'user_agent' => $matches[11],
                    'timestamp' => $timestamp,
                    'raw_line' => $line
                ];
            }
            return null;
        }

        function resolveHostname($ip) {
            static $cache = [];
            if (isset($cache[$ip])) return $cache[$ip];

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $hostname = @gethostbyaddr($ip);
                $result = ($hostname !== $ip && $hostname !== false) ? $hostname : '-';
            } else {
                $result = 'Invalid';
            }

            $cache[$ip] = $result;
            return $result;
        }

        function getTimeIndicator($timestamp) {
            $diff = time() - $timestamp;
            if ($diff < 300) return ['class' => 'time-just-now', 'text' => 'now'];
            if ($diff < 3600) return ['class' => 'time-recent', 'text' => floor($diff/60) . 'm'];
            if ($diff < 86400) return ['class' => 'time-old', 'text' => floor($diff/3600) . 'h'];
            return ['class' => 'time-old', 'text' => floor($diff/86400) . 'd'];
        }

        $uniqueIPs = [];
        $ipRequests = [];
        $totalLines = 0;

        $file = @fopen($logFile, 'r');
        if ($file) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines);

            foreach ($lines as $line) {
                $parsed = parseNginxLine(trim($line));
                if ($parsed) {
                    $ip = $parsed['ip'];
                    $totalLines++;

                    if (!isset($ipRequests[$ip])) {
                        $ipRequests[$ip] = [];
                    }
                    if (count($ipRequests[$ip]) < $requestsPerIP) {
                        $ipRequests[$ip][] = $parsed;
                    }

                    if (!isset($uniqueIPs[$ip]) || $parsed['timestamp'] > $uniqueIPs[$ip]['timestamp']) {
                        $uniqueIPs[$ip] = $parsed;
                    }

                    if (count($uniqueIPs) >= $maxUniqueIPs && count($ipRequests) >= $maxUniqueIPs) {
                        break;
                    }
                }
            }
            fclose($file);
        }

        uasort($uniqueIPs, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        $uniqueCount = count($uniqueIPs);
        ?>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo $uniqueCount; ?></div>
                    <div class="stat-label">Unique IPs</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo $totalLines; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo $uniqueCount > 0 ? round($totalLines / $uniqueCount, 1) : 0; ?></div>
                    <div class="stat-label">Avg/IP</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card">
                    <div class="stat-value"><?php echo round(filesize($logFile) / 1024, 1); ?>K</div>
                    <div class="stat-label">Log Size</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">Access Log - <?php echo htmlspecialchars(basename($logFile)); ?></div>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchIP" class="form-control form-control-sm" placeholder="Search..." style="max-width: 120px; font-size: 0.6rem; padding: 0.2rem 0.4rem; background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
                            <button class="btn-view" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
                        </div>
                    </div>
                    <div class="trades-scroll">
                        <table class="table mini-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th class="hide-mobile">Hostname</th>
                                    <th>Last Access</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uniqueIPs as $ip => $entry):
                                    $timeInfo = getTimeIndicator($entry['timestamp']);
                                    $hostname = resolveHostname($ip);
                                    $statusClass = 'badge-status-' . substr($entry['status'], 0, 1) . 'xx';
                                ?>
                                <tr class="ip-row" data-ip="<?php echo htmlspecialchars($ip); ?>">
                                    <td>
                                        <span class="ip-badge"><?php echo htmlspecialchars($ip); ?></span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span style="color: var(--text-secondary);"><?php echo htmlspecialchars(substr($hostname, 0, 25)); ?></span>
                                    </td>
                                    <td>
                                        <span class="time-indicator <?php echo $timeInfo['class']; ?>"></span>
                                        <span style="color: var(--text-secondary);"><?php echo date("d M H:i", $entry['timestamp']); ?></span>
                                        <span style="color: var(--text-secondary); opacity: 0.6; font-size: 0.55rem;"> (<?php echo $timeInfo['text']; ?>)</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $statusClass; ?>" style="font-size: 0.55rem;"><?php echo $entry['status']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn-view btn-view-details"
                                                data-ip="<?php echo htmlspecialchars($ip); ?>"
                                                data-hostname="<?php echo htmlspecialchars($hostname); ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if ($uniqueCount === 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">No log entries found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="font-size: 0.55rem; color: var(--text-secondary); margin-top: 0.5rem;">
                        Updated: <?php echo date("d M Y H:i:s"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="ipModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle">IP Details</h5>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-row">
                    <span class="info-label">IP Address</span>
                    <span class="info-value"><span class="ip-badge" id="modalIP"></span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hostname</span>
                    <span class="info-value" id="modalHostname"></span>
                </div>
                <div class="section-title" style="margin-top: 0.5rem;">Last <?php echo $requestsPerIP; ?> Requests</div>
                <div class="trades-scroll" style="max-height: 300px;">
                    <table class="table mini-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Method</th>
                                <th>URL</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="modalRequests"></tbody>
                    </table>
                </div>
                <div class="section-title" style="margin-top: 0.5rem;">Raw Logs</div>
                <div id="modalRawLogs"></div>
            </div>
        </div>
    </div>

    <script>
        const ipData = <?php echo json_encode($ipRequests); ?>;

        document.querySelectorAll('.btn-view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                const ip = this.getAttribute('data-ip');
                const hostname = this.getAttribute('data-hostname');
                showIPDetails(ip, hostname);
            });
        });

        function showIPDetails(ip, hostname) {
            document.getElementById('modalTitle').textContent = 'Details: ' + ip;
            document.getElementById('modalIP').textContent = ip;
            document.getElementById('modalHostname').textContent = hostname;

            const modalRequests = document.getElementById('modalRequests');
            const modalRawLogs = document.getElementById('modalRawLogs');

            modalRequests.innerHTML = '';
            modalRawLogs.innerHTML = '';

            if (ipData[ip] && ipData[ip].length > 0) {
                ipData[ip].forEach((request, index) => {
                    const date = new Date(request.timestamp * 1000);
                    const timeStr = date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                    const statusClass = 'badge-status-' + request.status.charAt(0) + 'xx';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${timeStr}</td>
                        <td><span class="badge" style="background: var(--bg-tertiary); font-size: 0.5rem;">${request.method}</span></td>
                        <td title="${request.url}" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${request.url}</td>
                        <td><span class="badge rounded-pill ${statusClass}" style="font-size: 0.5rem;">${request.status}</span></td>
                    `;
                    modalRequests.appendChild(row);

                    if (index < 3) {
                        const logDiv = document.createElement('div');
                        logDiv.className = 'log-line';
                        logDiv.textContent = request.raw_line.substring(0, 150) + (request.raw_line.length > 150 ? '...' : '');
                        modalRawLogs.appendChild(logDiv);
                    }
                });
            }

            document.getElementById('ipModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('ipModal').classList.remove('show');
        }

        document.getElementById('ipModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('searchIP').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.ip-row').forEach(row => {
                const ip = row.getAttribute('data-ip');
                row.style.display = ip.includes(searchTerm) ? '' : 'none';
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
