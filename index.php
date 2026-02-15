<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>freqmon</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2358a6ff' viewBox='0 0 16 16'><path d='M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5h-2v12h2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3z'/></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
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
        
        .server-card {
            height: 100%;
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .server-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0;
            color: #ffffff;
        }
        
        .server-host {
            color: var(--text-secondary);
            font-size: 0.7rem;
            font-family: monospace;
        }
        
        .strategy-name {
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.2rem;
            padding-left: 0.25rem;
        }

        .strategy-clickable {
            cursor: pointer;
            transition: color 0.2s;
        }

        .strategy-clickable:hover {
            color: var(--accent-blue);
        }
        
        .days-badge {
            color: var(--text-secondary);
            font-weight: 400;
            font-size: 0.7rem;
            margin-left: 0.25rem;
        }
        
        .info-btn {
            color: var(--accent-blue);
            cursor: pointer;
            opacity: 0.7;
        }

        .info-btn:hover {
            opacity: 1;
        }

        .server-name-link {
            color: #ffffff;
            text-decoration: none;
        }

        .server-icon-link {
            color: var(--text-secondary);
            text-decoration: none;
        }

        .server-icon-link:hover {
            color: var(--accent-blue);
        }

        .server-icon {
            color: var(--text-secondary);
        }

        .server-name-link:hover {
            color: var(--accent-yellow);
        }

        .days-clickable {
            cursor: pointer;
            transition: color 0.2s;
        }

        .days-clickable:hover {
            color: var(--accent-blue);
        }

        .activity-star {
            display: none;
            color: #ffd700;
            animation: starGlow 1s ease-in-out infinite;
            margin-left: 6px;
            font-size: 0.9rem;
        }
        .activity-star.show {
            display: inline-block;
        }
        @keyframes starGlow {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

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

        #tradeModal {
            z-index: 1100;
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 400px;
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

        .info-summary {
            background: var(--bg-tertiary);
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            text-align: center;
            color: var(--accent-blue);
            font-size: 0.7rem;
        }

        .trade-modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 500px;
            width: 92%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .trade-chart-container {
            height: 180px;
            margin: 0.5rem 0;
        }

        .trade-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.3rem;
            margin-top: 0.5rem;
        }

        .trade-detail {
            padding: 0.3rem 0.4rem;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }

        .trade-detail-label {
            font-size: 0.55rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .trade-detail-value {
            font-size: 0.75rem;
            color: #fff;
            font-weight: 500;
        }

        .trade-loading {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .badge-online {
            background-color: rgba(63, 185, 80, 0.2);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
        }
        
        .badge-offline {
            background-color: rgba(248, 81, 73, 0.2);
            color: var(--accent-red);
            border: 1px solid var(--accent-red);
        }
        
        .badge-live {
            background-color: rgba(63, 185, 80, 0.2);
            color: var(--accent-green);
            border: 1px solid var(--accent-green);
        }
        
        .badge-dry {
            background-color: rgba(210, 153, 34, 0.2);
            color: var(--accent-yellow);
            border: 1px solid var(--accent-yellow);
        }
        
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.35rem;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .mini-stat {
            text-align: center;
            padding: 0.3rem;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
        
        .mini-stat-label {
            display: block;
            color: #a0a8b2;
            font-size: 0.55rem;
            text-transform: uppercase;
        }
        
        .mini-stat-value {
            font-weight: 600;
            font-size: 0.75rem;
            color: #ffffff;
        }
        
        .text-profit { color: var(--accent-green) !important; }
        .text-loss { color: var(--accent-red) !important; }
        .text-success { color: var(--accent-green) !important; }
        .text-danger { color: var(--accent-red) !important; }
        .text-info { color: var(--accent-blue) !important; }
        
        .chart-container {
            height: 100px;
            margin-bottom: 0.5rem;
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
        
        .mini-table tbody tr.row-profit {
            background: rgba(63, 185, 80, 0.15);
        }
        
        .mini-table tbody tr.row-loss {
            background: rgba(248, 81, 73, 0.15);
        }
        
        .mini-table tbody tr.row-profit:hover {
            background: rgba(63, 185, 80, 0.25);
        }
        
        .mini-table tbody tr.row-loss:hover {
            background: rgba(248, 81, 73, 0.25);
        }

        .coin-row {
            cursor: pointer;
        }

        .coin-row td:first-child {
            font-family: monospace;
            font-size: 0.7rem;
        }

        .pair-badge {
            background: var(--bg-tertiary);
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.6rem;
            color: #ffffff;
        }
        
        .trade-link {
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .trade-link:hover {
            background: var(--accent-blue);
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
        
        .open-trades-count {
            background: var(--accent-blue);
            color: white;
            font-size: 0.6rem;
            padding: 0.1rem 0.35rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .open-trades-count:hover {
            background: #4da3ff;
        }

        .coin-icon-btn {
            color: #8b949e;
            font-size: 1rem;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            padding: 0.1rem 0.2rem;
        }

        .coin-icon-btn:hover {
            color: #ffd700;
            transform: scale(1.15);
        }

        .no-data {
            color: var(--text-secondary);
            font-size: 0.7rem;
            text-align: center;
            padding: 0.5rem;
        }
        
        .trades-scroll {
            max-height: 250px;
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

        .section-title-toggle {
            font-size: 0.6rem;
            color: #a0a8b2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            padding: 0.3rem 0.2rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.2s;
        }

        .section-title-toggle:hover {
            color: #ffffff;
        }

        .section-title-toggle .chevron {
            transition: transform 0.3s;
        }

        .section-title-toggle.expanded .chevron {
            transform: rotate(180deg);
        }

        .transactions-container {
            display: none;
            overflow: hidden;
        }

        .transactions-container.show {
            display: block;
        }

        .server-card.transactions-expanded {
            grid-column: span 2;
        }

        @media (max-width: 1200px) {
            .server-card.transactions-expanded {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <div class="container-fluid px-4 py-3">
        <!-- Summary Stats -->
        <div class="row g-3 mb-4" id="summaryStats">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value" id="serversOnline">-/-</div>
                    <div class="stat-label">Servers Online</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value text-profit" id="totalProfit">+0.00</div>
                    <div class="stat-label">Total Profit</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value" id="avgProfit">+0.00%</div>
                    <div class="stat-label">Avg Profit %</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value" id="closedTrades">0</div>
                    <div class="stat-label">Closed Trades</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value text-info" id="openTrades">0</div>
                    <div class="stat-label">Open Trades</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card">
                    <div class="stat-value" id="winRate">0%</div>
                    <div class="stat-label">Win Rate</div>
                </div>
            </div>
        </div>

        <!-- Server Cards -->
        <div class="row g-3 justify-content-center" id="serverCards">
            <!-- Cards will be inserted here -->
        </div>
    </div>

    <!-- Strategy Info Modal -->
    <div class="modal-overlay" id="strategyModal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h5 id="modalTitle">Strategy Info</h5>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Info will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Trade Chart Modal -->
    <div class="modal-overlay" id="tradeModal" onclick="closeTradeModal(event)">
        <div class="trade-modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h5 id="tradeModalTitle">Trade Details</h5>
                <button class="modal-close" onclick="closeTradeModal()">&times;</button>
            </div>
            <div class="modal-body" id="tradeModalBody">
                <div class="trade-loading">Loading chart...</div>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div class="modal-overlay" id="passwordModal">
        <div class="password-modal-content">
            <div class="password-title">Authentication Required</div>
            <form id="passwordForm" onsubmit="return submitPassword(event)">
                <input type="password" id="passwordInput" class="password-input" placeholder="Enter password" autocomplete="current-password">
                <div id="passwordError" class="password-error"></div>
                <button type="submit" class="password-submit">Unlock</button>
            </form>
        </div>
    </div>

    <style>
        .password-modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            width: 280px;
            text-align: center;
        }
        .password-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #fff;
        }
        .password-input {
            width: 100%;
            padding: 0.5rem;
            font-size: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .password-input:focus {
            outline: none;
            border-color: var(--accent-blue);
        }
        .password-error {
            color: var(--accent-red);
            font-size: 0.7rem;
            min-height: 1rem;
            margin-bottom: 0.5rem;
        }
        .password-submit {
            width: 100%;
            padding: 0.5rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 4px;
            background: var(--accent-blue);
            color: #fff;
            cursor: pointer;
        }
        .password-submit:hover {
            opacity: 0.9;
        }
    </style>

    <!-- Loading Modal -->
    <div class="modal-overlay" id="loadingModal">
        <div class="loading-modal-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading servers...</div>
            <div class="loading-progress" id="loadingProgress"></div>
        </div>
    </div>

    <!-- Open Trades Modal -->
    <div class="modal-overlay" id="openTradesModal" onclick="closeOpenTradesModal(event)">
        <div class="open-trades-modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h5 id="openTradesModalTitle">Open Trades</h5>
                <button class="modal-close" onclick="closeOpenTradesModal()">&times;</button>
            </div>
            <div class="modal-body" id="openTradesModalBody">
            </div>
        </div>
    </div>

    <!-- Daily Profit Modal -->
    <div class="modal-overlay" id="dailyProfitModal" onclick="closeDailyProfitModal(event)">
        <div class="open-trades-modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h5 id="dailyProfitModalTitle">Daily Performance</h5>
                <button class="modal-close" onclick="closeDailyProfitModal()">&times;</button>
            </div>
            <div class="modal-body" id="dailyProfitModalBody">
            </div>
        </div>
    </div>

    <!-- Traded Coins Modal -->
    <div class="modal-overlay" id="tradedCoinsModal" onclick="closeTradedCoinsModal(event)">
        <div class="open-trades-modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h5 id="tradedCoinsModalTitle">Traded Coins</h5>
                <button class="modal-close" onclick="closeTradedCoinsModal()">&times;</button>
            </div>
            <div class="modal-body" id="tradedCoinsModalBody">
            </div>
        </div>
    </div>

    <style>
        .open-trades-modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 400px;
            width: 92%;
            max-height: 85vh;
            overflow-y: auto;
        }
    </style>

    <style>
        .loading-modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem 3rem;
            text-align: center;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            margin: 0 auto 1rem;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading-text {
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .loading-progress {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
    </style>

    <script>
        const charts = {};
        let refreshInterval;
        const REFRESH_SECONDS = 60;
        let serverData = {}; // Store server data globally for modal
        const tradeCache = {}; // Store trade data for chart modal
        let previousServerState = {}; // Store previous state for comparison
        let soundEnabled = true; // Default to on, updated from settings
        let coinsEnabled = false; // Default to off, updated from settings
        let configDays = 20; // Default days to show in charts, updated from settings
        let notifyDuration = 10; // Default seconds to show activity star, updated from settings

        // Chime sound using Web Audio API
        let audioContext = null;
        function playChime(type = 'close') {
            if (!soundEnabled) return;
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                const doPlay = () => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);

                    if (type === 'open') {
                        // Rising tone for trade open (C5 to E5)
                        oscillator.frequency.setValueAtTime(523, audioContext.currentTime);
                        oscillator.frequency.linearRampToValueAtTime(659, audioContext.currentTime + 0.15);
                        oscillator.type = 'sine';
                        gainNode.gain.setValueAtTime(0.25, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.3);
                    } else {
                        // Single tone for trade close (A5)
                        oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
                        oscillator.type = 'sine';
                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.5);
                    }
                };

                // Resume audio context if suspended (browser policy) then play
                if (audioContext.state === 'suspended') {
                    audioContext.resume().then(doPlay);
                } else {
                    doPlay();
                }
            } catch (e) {
                console.log('Audio not available:', e);
            }
        }

        // Show notification
        function showNotification(title, message, type = 'profit') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = 'notification';

            let icon = 'bi-check-circle-fill';
            if (type === 'loss') icon = 'bi-dash-circle-fill';
            else if (type === 'open') icon = 'bi-arrow-up-circle-fill';

            notification.innerHTML = `
                <i class="bi ${icon} notification-icon ${type}"></i>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(title)}</div>
                    <div class="notification-message">${message}</div>
                </div>
            `;

            container.appendChild(notification);
            playChime(type === 'open' ? 'open' : 'close');

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.classList.add('hiding');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Show glowing activity star on server card
        const activityStarTimers = {};
        function showActivityStar(serverNum) {
            const star = document.getElementById(`activity-star-${serverNum}`);
            if (!star) return;

            // Clear any existing timer
            if (activityStarTimers[serverNum]) {
                clearTimeout(activityStarTimers[serverNum]);
            }

            star.classList.add('show');

            // Hide after configured duration (notifyDuration in seconds)
            activityStarTimers[serverNum] = setTimeout(() => {
                star.classList.remove('show');
            }, notifyDuration * 1000);
        }

        // Generate a hash/signature for server data to detect changes
        function getServerSignature(server) {
            const closedTrades = server.trades?.trades?.filter(t => !t.is_open) || [];
            const openTrades = server.status || [];
            return {
                // Use actual closed_trade_count from profit, not array length (API returns max 50)
                closedCount: server.profit?.closed_trade_count || closedTrades.length,
                openCount: openTrades.length,
                lastClosedId: closedTrades.length > 0 ? closedTrades[0].trade_id : null,
                lastOpenIds: openTrades.map(t => t.trade_id).sort().join(','),
                balance: server.balance?.total || 0,
                profitClosed: server.profit?.profit_closed_coin || 0
            };
        }

        // Check for data changes and detect new trades
        function detectChanges(serverNum, newServer, oldState) {
            const changes = {
                hasChanges: false,
                newClosedTrades: [],
                newOpenTrades: []
            };

            if (!oldState) {
                changes.hasChanges = true;
                return changes;
            }

            const newSig = getServerSignature(newServer);

            // Check for new closed trades
            if (newSig.closedCount > oldState.closedCount) {
                changes.hasChanges = true;
                const closedTrades = (newServer.trades?.trades?.filter(t => !t.is_open) || [])
                    .sort((a, b) => new Date(b.close_date) - new Date(a.close_date));
                const newCount = newSig.closedCount - oldState.closedCount;
                changes.newClosedTrades = closedTrades.slice(0, newCount);
            }

            // Check for new open trades
            if (newSig.lastOpenIds !== oldState.lastOpenIds) {
                changes.hasChanges = true;
                const newOpenIds = new Set(newSig.lastOpenIds.split(',').filter(id => id));
                const oldOpenIds = new Set((oldState.lastOpenIds || '').split(',').filter(id => id));
                const openTrades = newServer.status || [];

                changes.newOpenTrades = openTrades.filter(t =>
                    t.trade_id && !oldOpenIds.has(String(t.trade_id))
                );
            }

            // Check for balance/profit changes
            if (Math.abs(newSig.balance - oldState.balance) > 0.01 ||
                Math.abs(newSig.profitClosed - oldState.profitClosed) > 0.01) {
                changes.hasChanges = true;
            }

            return changes;
        }

        function toggleTransactions(serverNum, toggleElement) {
            const container = document.getElementById(`transactions-${serverNum}`);
            const isExpanded = container.classList.contains('show');

            if (isExpanded) {
                container.classList.remove('show');
                toggleElement.classList.remove('expanded');
            } else {
                container.classList.add('show');
                toggleElement.classList.add('expanded');
            }
        }

        function showStrategyInfo(serverNum, event) {
            event.stopPropagation();
            const server = serverData[serverNum];
            if (!server) return;
            
            const profit = server.profit || {};
            const config = server.config || {};
            const balance = server.balance || {};
            
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.innerHTML = `<i class="bi bi-database me-1"></i>${escapeHtml(server.name)} - <i class="bi bi-cpu me-1"></i>${escapeHtml(config.strategy || 'N/A')}`;
            
            const tradingMode = config.dry_run === false ? '<span class="text-success">Live Trading</span>' : '<span class="text-warning">Dry Run</span>';
            const exchange = config.exchange || 'N/A';
            const tradingType = config.trading_mode || 'spot';
            const stakeCurrency = config.stake_currency || 'USDT';
            const stakeAmount = config.stake_amount || 'N/A';
            
            const avgProfit = profit.profit_closed_percent ? profit.profit_closed_percent.toFixed(2) + '%' : 'N/A';
            const avgProfitPerTrade = profit.profit_closed_percent && profit.closed_trade_count ? 
                (profit.profit_closed_percent / profit.closed_trade_count).toFixed(3) + '%' : 'N/A';
            const totalProfitPct = profit.profit_closed_percent ? profit.profit_closed_percent.toFixed(3) + '%' : '0%';
            const totalProfit = profit.profit_closed_coin ? profit.profit_closed_coin.toFixed(4) + ' ' + stakeCurrency : 'N/A';
            const bestPair = profit.best_pair || 'N/A';
            const winRate = profit.winrate !== undefined ? (profit.winrate * 100).toFixed(1) + '%' : 
                           (profit.winning_trades && profit.closed_trade_count ? 
                            ((profit.winning_trades / profit.closed_trade_count) * 100).toFixed(1) + '%' : 'N/A');
            const tradingVolume = profit.trading_volume ? profit.trading_volume.toFixed(2) + ' ' + stakeCurrency : 'N/A';
            const avgDuration = profit.avg_duration || 'N/A';
            const firstTrade = profit.first_trade_humanized || 'N/A';
            const latestTrade = profit.latest_trade_humanized || 'N/A';
            const tradeCount = profit.closed_trade_count || 0;
            
            // Build summary line like: Avg Profit 1.624% (∑ 9.747%) in 6 Trades, with an average duration of 0:19:12
            const summaryLine = `Avg Profit ${avgProfitPerTrade} (∑ ${totalProfitPct}) in ${tradeCount} Trades, avg duration ${avgDuration}`;

            modalBody.innerHTML = `
                <div class="info-summary">${summaryLine}</div>
                <div class="info-row">
                    <span class="info-label">Mode</span>
                    <span class="info-value">${tradingMode}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Exchange</span>
                    <span class="info-value">${escapeHtml(exchange)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trading Type</span>
                    <span class="info-value">${escapeHtml(tradingType)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Stake</span>
                    <span class="info-value">${escapeHtml(String(stakeAmount))} ${escapeHtml(stakeCurrency)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Profit</span>
                    <span class="info-value ${profit.profit_closed_coin >= 0 ? 'text-success' : 'text-danger'}">${totalProfit}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Avg Profit %</span>
                    <span class="info-value">${avgProfit}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Trades</span>
                    <span class="info-value">${tradeCount}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Win / Loss</span>
                    <span class="info-value">${profit.winning_trades || 0} / ${profit.losing_trades || 0}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Win Rate</span>
                    <span class="info-value">${winRate}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Best Pair</span>
                    <span class="info-value">${escapeHtml(bestPair)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Avg Duration</span>
                    <span class="info-value">${escapeHtml(avgDuration)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trading Volume</span>
                    <span class="info-value">${tradingVolume}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">First Trade</span>
                    <span class="info-value">${escapeHtml(firstTrade)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Latest Trade</span>
                    <span class="info-value">${escapeHtml(latestTrade)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Balance</span>
                    <span class="info-value">${(balance.total || 0).toFixed(2)} ${escapeHtml(stakeCurrency)}</span>
                </div>
            `;

            document.getElementById('strategyModal').classList.add('show');
        }

        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('strategyModal').classList.remove('show');
        }

        function showOpenTrades(serverNum, event) {
            event.stopPropagation();
            const server = serverData[serverNum];
            if (!server) return;

            const openTrades = (server.status || []).slice().sort((a, b) => {
                // Sort by open_date descending (newest first)
                const dateA = a.open_date ? new Date(a.open_date) : new Date(0);
                const dateB = b.open_date ? new Date(b.open_date) : new Date(0);
                return dateB - dateA;
            });
            const serverName = server.name || `Server ${serverNum}`;

            document.getElementById('openTradesModalTitle').textContent = `Open Trades - ${serverName}`;

            if (openTrades.length === 0) {
                document.getElementById('openTradesModalBody').innerHTML = '<div class="no-data">No open trades</div>';
            } else {
                const rows = openTrades.map(t => {
                    const arrow = t.is_short ? '<span style="font-size:1.3em;color:#d29922">↓</span>' : '<span style="font-size:1.3em;color:#58a6ff">↑</span>';
                    const lev = t.leverage && t.leverage > 1 ? parseFloat(t.leverage) : 0;
                    const leverage = lev > 1 ? ` x${lev % 1 === 0 ? lev : lev.toFixed(1)}` : '';
                    const profitPct = t.profit_pct || 0;
                    const profitAbs = t.profit_abs || 0;
                    const openDuration = (() => {
                        if (!t.open_date) return '-';
                        let str = t.open_date.replace(' ', 'T');
                        if (!str.includes('Z') && !str.includes('+')) str += 'Z';
                        return Math.round((new Date() - new Date(str)) / 60000) + ' min';
                    })();
                    const rowClass = profitPct >= 0 ? 'row-profit' : 'row-loss';

                    // Store in tradeCache for chart viewing
                    const tradeId = 's' + serverNum + '_open_' + (t.trade_id || t.pair.replace(/[^a-zA-Z0-9]/g, '_'));
                    t._serverNum = serverNum;
                    t._isOpen = true;
                    tradeCache[tradeId] = t;

                    return `
                        <tr class="${rowClass}">
                            <td><span class="pair-badge trade-link" onclick="event.stopPropagation(); showTradeChart('${tradeId}')">${arrow} ${getCoinName(t.pair)}${leverage}</span></td>
                            <td class="text-end ${getProfitClass(profitAbs)}">${formatProfit(profitAbs)}</td>
                            <td class="text-end ${getProfitClass(profitPct)}">${profitPct.toFixed(1)}%</td>
                            <td><small style="color: #8b949e;">${t.current_rate?.toFixed(4) || '-'}</small></td>
                            <td><small style="color: #8b949e;">${openDuration}</small></td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('openTradesModalBody').innerHTML = `
                    <div class="trades-scroll">
                        <table class="table table-dark mini-table mb-0">
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">%</th>
                                    <th>Rate</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            document.getElementById('openTradesModal').classList.add('show');
        }

        function closeOpenTradesModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('openTradesModal').classList.remove('show');
        }

        function showDailyProfit(serverNum, event) {
            event.stopPropagation();
            const server = serverData[serverNum];
            if (!server) return;

            const daily = server.daily?.data || [];
            const config = server.config || {};
            const profit = server.profit || {};
            const serverName = server.name || `Server ${serverNum}`;
            const stakeCurrency = config.stake_currency || 'USDT';
            const startingBalance = (server.balance?.total || 0) - (profit.profit_closed_coin || 0);

            document.getElementById('dailyProfitModalTitle').textContent = `Daily Performance - ${serverName}`;

            if (daily.length === 0) {
                document.getElementById('dailyProfitModalBody').innerHTML = '<div class="no-data">No daily data available</div>';
            } else {
                // Get first trade date string to filter out days before strategy started
                let firstTradeDateStr = null;
                if (profit.first_trade_timestamp) {
                    // Detect if timestamp is in seconds or milliseconds (ms timestamps are > 1e12)
                    const ts = profit.first_trade_timestamp > 1e12 ? profit.first_trade_timestamp : profit.first_trade_timestamp * 1000;
                    const firstTradeDate = new Date(ts);
                    // Get date string in YYYY-MM-DD format (UTC)
                    firstTradeDateStr = firstTradeDate.toISOString().split('T')[0];
                }

                // Fallback: find first day with actual trades from daily data
                if (!firstTradeDateStr) {
                    const daysWithTrades = daily
                        .filter(d => (d.trade_count || 0) > 0)
                        .sort((a, b) => a.date.localeCompare(b.date));
                    if (daysWithTrades.length > 0) {
                        firstTradeDateStr = daysWithTrades[0].date;
                    }
                }

                // Filter to only show days since first trade, sort descending, take up to configDays
                const sortedDaily = [...daily]
                    .filter(d => {
                        if (!firstTradeDateStr) return true;
                        // Compare date strings directly (YYYY-MM-DD format)
                        return d.date >= firstTradeDateStr;
                    })
                    .sort((a, b) => new Date(b.date) - new Date(a.date))
                    .slice(0, configDays);

                const rows = sortedDaily.map(d => {
                    const profitValue = d.abs_profit || 0;
                    const tradeCount = d.trade_count || 0;
                    const profitPct = startingBalance > 0 ? ((profitValue / startingBalance) * 100) : 0;
                    const profitClass = profitValue > 0 ? 'text-success' : (profitValue < 0 ? 'text-danger' : 'text-muted');
                    const rowClass = profitValue > 0 ? 'row-profit' : (profitValue < 0 ? 'row-loss' : '');
                    const date = new Date(d.date);
                    const dateStr = date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' });

                    return `
                        <tr class="${rowClass}">
                            <td class="text-center">${tradeCount}</td>
                            <td class="text-end ${profitClass}">${profitValue >= 0 ? '+' : ''}${profitValue.toFixed(2)}</td>
                            <td class="text-end ${profitClass}">${profitPct >= 0 ? '+' : ''}${profitPct.toFixed(2)}%</td>
                            <td class="text-end"><small style="color: #8b949e;">${dateStr}</small></td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('dailyProfitModalBody').innerHTML = `
                    <div class="trades-scroll">
                        <table class="table table-dark mini-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Trades</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">%</th>
                                    <th class="text-end">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            document.getElementById('dailyProfitModal').classList.add('show');
        }

        function closeDailyProfitModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('dailyProfitModal').classList.remove('show');
        }

        function showTradedCoins(serverNum, event) {
            event.stopPropagation();
            const server = serverData[serverNum];
            if (!server) return;

            const serverName = server.name || `Server ${serverNum}`;
            const performance = server.performance || [];

            document.getElementById('tradedCoinsModalTitle').textContent = `Traded Coins - ${serverName}`;

            if (performance.length === 0) {
                document.getElementById('tradedCoinsModalBody').innerHTML = '<div class="no-data">No traded coins data available</div>';
            } else {
                // Sort by profit percentage descending
                const sortedPerf = [...performance].sort((a, b) => (b.profit_pct || 0) - (a.profit_pct || 0));

                const rows = sortedPerf.map(p => {
                    const profitPct = p.profit_pct || 0;
                    const profitAbs = p.profit || 0;
                    const count = p.count || 0;
                    const pair = p.pair || '-';
                    const profitClass = profitAbs >= 0 ? 'text-success' : 'text-danger';
                    const rowClass = profitAbs >= 0 ? 'row-profit' : 'row-loss';

                    return `
                        <tr class="${rowClass}">
                            <td><span class="pair-badge">${getCoinName(pair)}</span></td>
                            <td class="text-end ${profitClass}">${profitPct.toFixed(2)}</td>
                            <td class="text-end ${profitClass}">${profitAbs.toFixed(5)}</td>
                            <td class="text-end">${count}</td>
                        </tr>
                    `;
                }).join('');

                document.getElementById('tradedCoinsModalBody').innerHTML = `
                    <div class="trades-scroll" style="max-height: 350px;">
                        <table class="table table-dark mini-table mb-0">
                            <thead>
                                <tr>
                                    <th>Coin</th>
                                    <th class="text-end">Profit %</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            document.getElementById('tradedCoinsModal').classList.add('show');
        }

        function closeTradedCoinsModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('tradedCoinsModal').classList.remove('show');
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeTradeModal();
                closeOpenTradesModal();
                closeDailyProfitModal();
                closeTradedCoinsModal();
            }
        });
        
        let tradeChart = null;

        function closeTradeModal(event) {
            if (event) {
                event.stopPropagation();
                if (event.target !== event.currentTarget) return;
            }
            document.getElementById('tradeModal').classList.remove('show');
            if (tradeChart) {
                tradeChart.destroy();
                tradeChart = null;
            }
        }
        
        async function showTradeChart(tradeId) {
            const trade = tradeCache[tradeId];
            if (!trade) {
                console.error('Trade not found:', tradeId);
                return;
            }

            const isOpenTrade = trade._isOpen === true;
            const titleSuffix = isOpenTrade ? ' (Open)' : '';
            const arrow = trade.is_short ? '<span style="color:#d29922">↓</span>' : '<span style="color:#58a6ff">↑</span>';
            const lev = trade.leverage && trade.leverage > 1 ? parseFloat(trade.leverage) : 0;
            const leverage = lev > 1 ? ` x${lev % 1 === 0 ? lev : lev.toFixed(1)}` : '';
            document.getElementById('tradeModalTitle').innerHTML = `${arrow} ${trade.pair}${leverage}${titleSuffix}`;
            document.getElementById('tradeModalBody').innerHTML = '<div class="trade-loading"><i class="bi bi-hourglass-split"></i> Loading chart...</div>';
            document.getElementById('tradeModal').classList.add('show');
            try {
                // Parse dates - ensure consistent timezone handling
                // FreqTrade typically returns dates in UTC, but format might vary
                const parseDate = (dateStr) => {
                    if (!dateStr) return new Date();
                    // Handle both "2026-01-21 14:30:03" and "2026-01-21T14:30:03" formats
                    let str = dateStr.replace(' ', 'T');
                    // If no timezone specified, assume UTC
                    if (!str.includes('Z') && !str.includes('+')) {
                        str += 'Z';
                    }
                    return new Date(str);
                };

                const openDate = parseDate(trade.open_date);
                const closeDate = isOpenTrade ? new Date() : parseDate(trade.close_date);
                const duration = closeDate - openDate;
                
                // Determine timeframe based on trade duration
                let timeframe = '1m';
                if (duration > 86400000) timeframe = '1h'; // > 1 day
                else if (duration > 7200000) timeframe = '15m'; // > 2 hours
                else if (duration > 3600000) timeframe = '5m'; // > 1 hour
                
                // Calculate time range around the trade
                const candleDurations = { '1m': 60000, '5m': 300000, '15m': 900000, '1h': 3600000 };
                const candleMs = candleDurations[timeframe];
                const paddingTime = Math.max(duration * 2, candleMs * 30);
                
                const startTime = new Date(openDate.getTime() - paddingTime);
                const endTime = new Date(closeDate.getTime() + paddingTime);
                
                let labels, prices;
                
                // Use Binance API for chart data
                console.log('Fetching chart data from Binance...');
                console.log('Trade:', trade.pair, 'Open:', openDate.toISOString(), 'Close:', closeDate.toISOString());
                
                const intervalMap = { '1m': '1m', '5m': '5m', '15m': '15m', '1h': '1h' };
                const interval = intervalMap[timeframe] || '5m';
                
                // Build symbol - handle USDT futures format (e.g., "INJ/USDT:USDT" -> "INJUSDT")
                let symbol = trade.pair
                    .split(':')[0]  // Get base part before : (e.g., "INJ/USDT")
                    .replace('/', ''); // Remove slash -> "INJUSDT"
                
                console.log('Querying symbol:', symbol, 'interval:', interval);
                
                let klines = null;
                const isFutures = trade.pair.includes(':');
                
                // Try futures first for futures pairs
                if (isFutures) {
                    try {
                        const futuresUrl = `https://fapi.binance.com/fapi/v1/klines?symbol=${symbol}&interval=${interval}&startTime=${startTime.getTime()}&endTime=${endTime.getTime()}&limit=500`;
                        console.log('Trying futures:', futuresUrl);
                        const futuresResponse = await fetch(futuresUrl);
                        if (futuresResponse.ok) {
                            klines = await futuresResponse.json();
                            if (Array.isArray(klines) && klines.length > 0) {
                                console.log('Got', klines.length, 'candles from futures API');
                            }
                        }
                    } catch (e) {
                        console.log('Binance futures failed:', e);
                    }
                }
                
                // Fallback to spot if futures failed or not a futures pair
                if (!Array.isArray(klines) || klines.length === 0) {
                    try {
                        const spotUrl = `https://api.binance.com/api/v3/klines?symbol=${symbol}&interval=${interval}&startTime=${startTime.getTime()}&endTime=${endTime.getTime()}&limit=500`;
                        console.log('Trying spot:', spotUrl);
                        const spotResponse = await fetch(spotUrl);
                        if (spotResponse.ok) {
                            klines = await spotResponse.json();
                            console.log('Got', klines?.length || 0, 'candles from spot API');
                        }
                    } catch (e) {
                        console.log('Binance spot failed:', e);
                    }
                }
                
                if (!Array.isArray(klines) || klines.length === 0) {
                    throw new Error('No chart data available from Binance');
                }
                
                // Verify data roughly matches trade prices (sanity check)
                const firstPrice = parseFloat(klines[0][4]);
                const currentPrice = isOpenTrade ? trade.current_rate : trade.close_rate;
                const tradePriceAvg = (trade.open_rate + currentPrice) / 2;
                const priceDiff = Math.abs(firstPrice - tradePriceAvg) / tradePriceAvg;
                if (priceDiff > 0.1) { // More than 10% difference
                    console.warn('Price mismatch! Chart:', firstPrice, 'Trade:', tradePriceAvg, 'Diff:', (priceDiff * 100).toFixed(1) + '%');
                }

                labels = klines.map(k => new Date(k[0]));
                prices = labels.map((t, i) => ({ x: t, y: parseFloat(klines[i][4]) }));

                // Create point markers at actual trade times and prices (using exact coordinates)
                const entryPoint = [{ x: openDate, y: trade.open_rate }];
                const exitPoint = isOpenTrade ? [] : [{ x: closeDate, y: trade.close_rate }];

                const profitColor = (trade.profit_abs || 0) >= 0 ? '#3fb950' : '#f85149';

                // Different details for open vs closed trades
                const detailsHtml = isOpenTrade ? `
                    <div class="trade-details">
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Time</div>
                            <div class="trade-detail-value">${formatDateTime(openDate)}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Duration</div>
                            <div class="trade-detail-value">${Math.round(duration / 60000)} min</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Price</div>
                            <div class="trade-detail-value">${trade.open_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Current Price</div>
                            <div class="trade-detail-value">${trade.current_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Unrealized P/L</div>
                            <div class="trade-detail-value" style="color: ${profitColor}">${formatProfit(trade.profit_abs)} (${(trade.profit_pct || 0).toFixed(2)}%)</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Stake</div>
                            <div class="trade-detail-value">${trade.stake_amount?.toFixed(2) || 'N/A'}</div>
                        </div>
                    </div>
                ` : `
                    <div class="trade-details">
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Time</div>
                            <div class="trade-detail-value">${formatDateTime(openDate)}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Time</div>
                            <div class="trade-detail-value">${formatDateTime(closeDate)}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Price</div>
                            <div class="trade-detail-value">${trade.open_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Price</div>
                            <div class="trade-detail-value">${trade.close_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Profit</div>
                            <div class="trade-detail-value" style="color: ${profitColor}">${formatProfit(trade.profit_abs)} (${(trade.profit_pct || 0).toFixed(2)}%)</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Duration</div>
                            <div class="trade-detail-value">${trade.trade_duration || Math.round(duration / 60000) + ' min'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Stake</div>
                            <div class="trade-detail-value">${trade.stake_amount?.toFixed(2) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Reason</div>
                            <div class="trade-detail-value">${escapeHtml(trade.exit_reason || 'N/A')}</div>
                        </div>
                    </div>
                `;

                document.getElementById('tradeModalBody').innerHTML = `
                    <div class="trade-chart-container">
                        <canvas id="tradeChartCanvas"></canvas>
                    </div>
                    ${detailsHtml}
                `;

                // Build datasets - only include exit point for closed trades
                const datasets = [
                    {
                        label: 'Price',
                        data: prices,
                        borderColor: '#8b949e',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        tension: 0.1
                    },
                    {
                        label: 'Entry',
                        data: entryPoint,
                        borderColor: '#3fb950',
                        backgroundColor: '#3fb950',
                        pointRadius: 8,
                        pointStyle: 'circle',
                        showLine: false
                    }
                ];

                if (!isOpenTrade) {
                    datasets.push({
                        label: 'Exit',
                        data: exitPoint,
                        borderColor: '#f85149',
                        backgroundColor: '#f85149',
                        pointRadius: 8,
                        pointStyle: 'circle',
                        showLine: false
                    });
                }

                const ctx = document.getElementById('tradeChartCanvas');
                tradeChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: null },
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    displayFormats: { minute: 'HH:mm', hour: 'HH:mm', day: 'MMM d' }
                                },
                                grid: { color: 'rgba(48, 54, 61, 0.5)' },
                                ticks: { color: '#8b949e', maxTicksLimit: 8 }
                            },
                            y: {
                                grid: { color: 'rgba(48, 54, 61, 0.5)' },
                                ticks: { color: '#8b949e' }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Failed to load chart:', error);
                // Use trade dates directly in error display
                const errorOpenDate = trade.open_date || 'N/A';
                const errorCloseDate = trade.close_date || 'N/A';
                const errorDuration = trade.trade_duration || 'N/A';
                document.getElementById('tradeModalBody').innerHTML = `
                    <div class="trade-loading" style="color: var(--accent-red);">
                        <i class="bi bi-exclamation-triangle"></i> Could not load chart data: ${escapeHtml(error.message || 'Unknown error')}
                    </div>
                    <div class="trade-details">
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Time</div>
                            <div class="trade-detail-value">${escapeHtml(errorOpenDate)}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Time</div>
                            <div class="trade-detail-value">${escapeHtml(errorCloseDate)}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Entry Price</div>
                            <div class="trade-detail-value">${trade.open_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Price</div>
                            <div class="trade-detail-value">${trade.close_rate?.toFixed(6) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Profit</div>
                            <div class="trade-detail-value" style="color: ${(trade.profit_abs || 0) >= 0 ? '#3fb950' : '#f85149'}">${formatProfit(trade.profit_abs)} (${(trade.profit_pct || 0).toFixed(2)}%)</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Duration</div>
                            <div class="trade-detail-value">${errorDuration}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Stake</div>
                            <div class="trade-detail-value">${trade.stake_amount?.toFixed(2) || 'N/A'}</div>
                        </div>
                        <div class="trade-detail">
                            <div class="trade-detail-label">Exit Reason</div>
                            <div class="trade-detail-value">${escapeHtml(trade.exit_reason || 'N/A')}</div>
                        </div>
                    </div>
                `;
            }
        }
        
        function formatProfit(value, decimals = 2) {
            const num = parseFloat(value) || 0;
            const sign = num >= 0 ? '+' : '';
            return sign + num.toFixed(decimals);
        }
        
        function formatPercent(value) {
            const num = parseFloat(value) || 0;
            const sign = num >= 0 ? '+' : '';
            return sign + num.toFixed(2) + '%';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatCompactDate(dateStr) {
            if (!dateStr) return '-';
            // FreqTrade returns dates in UTC - ensure proper parsing
            let str = dateStr.replace(' ', 'T');
            if (!str.includes('Z') && !str.includes('+')) {
                str += 'Z';  // Append Z to indicate UTC
            }
            const d = new Date(str);
            const day = d.getDate().toString().padStart(2, '0');
            const mon = (d.getMonth() + 1).toString().padStart(2, '0');
            const hrs = d.getHours().toString().padStart(2, '0');
            const min = d.getMinutes().toString().padStart(2, '0');
            return `${day}.${mon} ${hrs}:${min}`;
        }

        function formatDateTime(d) {
            if (!d || !(d instanceof Date) || isNaN(d)) return '-';
            const year = d.getFullYear();
            const mon = (d.getMonth() + 1).toString().padStart(2, '0');
            const day = d.getDate().toString().padStart(2, '0');
            const hrs = d.getHours().toString().padStart(2, '0');
            const min = d.getMinutes().toString().padStart(2, '0');
            const sec = d.getSeconds().toString().padStart(2, '0');
            return `${year}-${mon}-${day} ${hrs}:${min}:${sec}`;
        }
        
        function getCoinName(pair) {
            if (!pair) return '-';
            // Extract base coin: "TON/USDT:USDT" -> "TON", "BTC/USDT" -> "BTC"
            return pair.split('/')[0];
        }
        
        function getProfitClass(value) {
            return parseFloat(value) >= 0 ? 'text-profit' : 'text-loss';
        }
        
        function createServerCard(server) {
            const isOnline = server.online;
            const profit = server.profit || {};
            const config = server.config || {};
            const daily = server.daily?.data || [];
            const trades = server.trades?.trades || [];
            const openTrades = server.status || [];

            // Get last 20 closed trades for this server (sorted by close_date, newest first)
            const closedTrades = trades
                .filter(t => !t.is_open)
                .sort((a, b) => new Date(b.close_date) - new Date(a.close_date))
                .slice(0, 20);
            
            const profitValue = profit.profit_closed_coin || 0;
            const profitPercent = profit.profit_closed_percent || 0;
            const closedCount = profit.closed_trade_count || 0;
            const winningTrades = profit.winning_trades || 0;
            const losingTrades = profit.losing_trades || 0;
            const strategy = config.strategy || '-';
            
            // Calculate trades done today
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + 
                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                String(today.getDate()).padStart(2, '0');
            const tradesToday = trades.filter(t => {
                if (!t.close_date || t.is_open) return false;
                const closeDay = t.close_date.substring(0, 10);
                return closeDay === todayStr;
            }).length;
            const tradesBeforeToday = closedCount - tradesToday;
            
            // Calculate today's profit
            const todayTrades = trades.filter(t => {
                if (!t.close_date || t.is_open) return false;
                const closeDay = t.close_date.substring(0, 10);
                return closeDay === todayStr;
            });
            const profitToday = todayTrades.reduce((sum, t) => sum + (t.profit_abs || 0), 0);
            const profitPctToday = todayTrades.reduce((sum, t) => sum + (t.profit_pct || 0), 0);
            const balance = server.balance?.total || 0;
            const balanceBeforeToday = balance - profitToday;
            
            // Calculate days running from first trade timestamp
            let daysText = '';
            if (profit.first_trade_timestamp) {
                // Detect if timestamp is in seconds or milliseconds (ms timestamps are > 1e12)
                const ts = profit.first_trade_timestamp > 1e12 ? profit.first_trade_timestamp : profit.first_trade_timestamp * 1000;
                const firstTrade = new Date(ts);
                const now = new Date();
                // Count calendar days (set both to start of day UTC and count difference)
                const firstDay = new Date(Date.UTC(firstTrade.getFullYear(), firstTrade.getMonth(), firstTrade.getDate()));
                const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
                const days = Math.round((today - firstDay) / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end day
                if (days > 0) {
                    daysText = `(${days} day${days !== 1 ? 's' : ''})`;
                }
            } else if (trades.length > 0) {
                const allDates = trades
                    .map(t => t.open_date)
                    .filter(d => d)
                    .map(d => new Date(d));
                if (allDates.length > 0) {
                    const earliest = new Date(Math.min(...allDates.map(d => d.getTime())));
                    const now = new Date();
                    const firstDay = new Date(Date.UTC(earliest.getFullYear(), earliest.getMonth(), earliest.getDate()));
                    const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
                    const days = Math.round((today - firstDay) / (1000 * 60 * 60 * 24)) + 1;
                    daysText = `(${days} day${days !== 1 ? 's' : ''})`;
                }
            }
            
            return `
            <div class="col-12 col-md-6 col-xl-3" id="server-card-${server.server_num}">
                <div class="card server-card">
                    <div class="card-body p-2">
                        <div class="server-header">
                            <div>
                                <h5 class="server-name">${server.url ? `<a href="${escapeHtml(server.url)}" target="_blank" class="server-icon-link" onclick="event.stopPropagation()"><i class="bi bi-database me-1"></i></a><a href="${escapeHtml(server.url)}" target="_blank" class="server-name-link" onclick="event.stopPropagation()">${escapeHtml(server.name)}</a>` : `<i class="bi bi-database me-1 server-icon"></i>${escapeHtml(server.name)}`}<span class="activity-star" id="activity-star-${server.server_num}"><i class="bi bi-star-fill"></i></span></h5>
                                ${isOnline ? `<div class="strategy-name"><span class="strategy-clickable" onclick="showStrategyInfo(${server.server_num}, event)"><i class="bi bi-cpu me-1"></i>${escapeHtml(strategy)}</span>${daysText ? `<span class="days-badge days-clickable" onclick="showDailyProfit(${server.server_num}, event)">${daysText}</span>` : ''}</div>` : ''}
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    ${openTrades.length > 0 ? `<span class="open-trades-count" onclick="showOpenTrades(${server.server_num}, event)">${openTrades.length} open</span>` : ''}
                                    ${!isOnline ? `<span class="badge badge-offline"><i class="bi bi-x-circle me-1"></i>Offline</span>` :
                                        (config.dry_run === false ? `<span class="badge badge-live"><i class="bi bi-lightning-charge me-1"></i>Live</span>` : '')}
                                </div>
                                ${isOnline && coinsEnabled ? `<span class="coin-icon-btn" onclick="showTradedCoins(${server.server_num}, event)" title="Traded Coins"><i class="bi bi-currency-exchange"></i></span>` : ''}
                            </div>
                        </div>
                        
                        ${isOnline ? `
                        <div class="mini-stats">
                            <div class="mini-stat">
                                <span class="mini-stat-label">Profit</span>
                                <span class="mini-stat-value ${getProfitClass(profitValue)}">${formatProfit(profitValue)}</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Profit %</span>
                                <span class="mini-stat-value ${getProfitClass(profitPercent)}">${formatPercent(profitPercent)}${profitPctToday !== 0 ? ` <span class="${profitPctToday >= 0 ? 'text-success' : 'text-danger'}">${profitPctToday >= 0 ? '+' : ''}${profitPctToday.toFixed(1)}%</span>` : ''}</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Trades</span>
                                <span class="mini-stat-value">${closedCount}${tradesToday > 0 ? ` <span class="text-info">+${tradesToday}</span>` : ''}</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Win/Loss</span>
                                <span class="mini-stat-value">${winningTrades}/${losingTrades}</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Win Rate</span>
                                <span class="mini-stat-value">${closedCount > 0 ? Math.round(winningTrades / closedCount * 100) : 0}%</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Balance</span>
                                <span class="mini-stat-value">${balance.toFixed(1)}${profitToday !== 0 ? ` <span class="${profitToday >= 0 ? 'text-success' : 'text-danger'}">${profitToday >= 0 ? '+' : ''}${profitToday.toFixed(1)}</span>` : ''}</span>
                            </div>
                        </div>

                        <div class="section-title"><i class="bi bi-graph-up me-1"></i>Balance History</div>
                        <div class="chart-container">
                            <canvas id="balance-chart-${server.server_num}"></canvas>
                        </div>

                        <div class="section-title"><i class="bi bi-bar-chart me-1"></i>Daily Performance</div>
                        <div class="chart-container">
                            <canvas id="chart-${server.server_num}"></canvas>
                        </div>

                        <div class="section-title-toggle" onclick="toggleTransactions(${server.server_num}, this)">
                            <span><i class="bi bi-list-ul me-1"></i>Last 20 Transactions ${closedTrades.length > 0 ? `(${closedTrades.length})` : ''}</span>
                            <i class="bi bi-chevron-down chevron"></i>
                        </div>
                        <div class="transactions-container" id="transactions-${server.server_num}">
                        ${closedTrades.length > 0 ? `
                        <div class="trades-scroll">
                            <table class="table table-dark mini-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Pair</th>
                                        <th class="text-end">Profit</th>
                                        <th class="text-end">%</th>
                                        <th>Exit</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${closedTrades.map(t => {
                                        const rowClass = (t.profit_abs || 0) >= 0 ? 'row-profit' : 'row-loss';
                                        const tradeDuration = (() => {
                                            if (t.trade_duration) return t.trade_duration;
                                            if (!t.open_date || !t.close_date) return '-';
                                            let openStr = t.open_date.replace(' ', 'T');
                                            if (!openStr.includes('Z') && !openStr.includes('+')) openStr += 'Z';
                                            let closeStr = t.close_date.replace(' ', 'T');
                                            if (!closeStr.includes('Z') && !closeStr.includes('+')) closeStr += 'Z';
                                            return Math.round((new Date(closeStr) - new Date(openStr)) / 60000) + ' min';
                                        })();
                                        const lev = t.leverage && t.leverage > 1 ? parseFloat(t.leverage) : 0;
                                        const leverage = lev > 1 ? ` x${lev % 1 === 0 ? lev : lev.toFixed(2)}` : '';
                                        const arrow = t.is_short ? '<span style="font-size:1.3em;color:#d29922">↓</span>' : '<span style="font-size:1.3em;color:#58a6ff">↑</span>';
                                        const exitReason = (t.exit_reason || '-').substring(0, 10);
                                        const tradeId = 's' + server.server_num + '_' + (t.trade_id || (t.pair + '_' + t.open_date).replace(/[^a-zA-Z0-9]/g, '_'));
                                        t._serverNum = server.server_num;
                                        tradeCache[tradeId] = t;
                                        return `
                                    <tr class="${rowClass}">
                                        <td><span class="pair-badge trade-link" onclick="event.stopPropagation(); showTradeChart('${tradeId}')">${arrow} ${getCoinName(t.pair)}${leverage}</span></td>
                                        <td class="text-end ${getProfitClass(t.profit_abs)}">${formatProfit(t.profit_abs)}</td>
                                        <td class="text-end ${getProfitClass(t.profit_pct)}">${(t.profit_pct || 0).toFixed(1)}%</td>
                                        <td><small style="color: #8b949e;">${escapeHtml(exitReason)}</small></td>
                                        <td><small style="color: #8b949e;">${tradeDuration}</small></td>
                                    </tr>
                                    `}).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : '<div class="no-data">No closed trades yet</div>'}
                        </div>
                        ` : `
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-wifi-off fs-2"></i>
                            <div class="mt-2">Unable to connect</div>
                        </div>
                        `}
                    </div>
                </div>
            </div>
            `;
        }
        
        function createChart(serverId, dailyData) {
            const ctx = document.getElementById(`chart-${serverId}`);
            if (!ctx) return;

            // Destroy existing chart
            if (charts[serverId]) {
                charts[serverId].destroy();
            }

            // Reverse data so oldest is on left, newest on right
            const reversedData = [...dailyData].reverse();

            const labels = reversedData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
            });

            const profits = reversedData.map(d => d.abs_profit || 0);
            const trades = reversedData.map(d => d.trade_count || 0);

            const colors = profits.map(p => p >= 0 ? 'rgba(63, 185, 80, 0.7)' : 'rgba(248, 81, 73, 0.7)');

            // Calculate axis alignment so zeros line up
            const profitMin = Math.min(0, ...profits);
            const profitMax = Math.max(0, ...profits);
            const tradeMax = Math.max(1, ...trades);

            // Calculate y1 min to align zeros: if profit goes negative,
            // we need to extend trade axis into negative to keep zeros aligned
            let y1Min = 0;
            if (profitMin < 0) {
                // Calculate ratio: how much of the profit range is below zero
                const negativeRatio = Math.abs(profitMin) / (profitMax - profitMin);
                // Apply same ratio to trade axis
                y1Min = -tradeMax * negativeRatio / (1 - negativeRatio);
            }

            charts[serverId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Profit',
                            data: profits,
                            backgroundColor: colors,
                            borderRadius: 3,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Trades',
                            data: trades,
                            type: 'line',
                            borderColor: 'rgba(88, 166, 255, 0.8)',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 2,
                            tension: 0.3,
                            yAxisID: 'y1',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    events: [],
                    interaction: {
                        mode: null
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#8b949e',
                                font: { size: 9 },
                                maxRotation: 0
                            }
                        },
                        y: {
                            position: 'left',
                            grid: { color: 'rgba(48, 54, 61, 0.3)' },
                            ticks: {
                                color: '#8b949e',
                                font: { size: 9 },
                                callback: v => v.toFixed(1)
                            },
                            afterFit: (axis) => { axis.width = 45; }
                        },
                        y1: {
                            position: 'right',
                            min: y1Min,
                            grid: { display: false },
                            ticks: {
                                color: '#58a6ff',
                                font: { size: 9 },
                                stepSize: 1,
                                callback: v => v >= 0 ? Math.round(v) : ''  // Only show positive values
                            },
                            afterFit: (axis) => { axis.width = 20; }
                        }
                    }
                }
            });
        }

        const balanceCharts = {};

        function createBalanceChart(serverId, trades, currentBalance, dailyData) {
            const ctx = document.getElementById(`balance-chart-${serverId}`);
            if (!ctx) return;

            // Destroy existing chart
            if (balanceCharts[serverId]) {
                balanceCharts[serverId].destroy();
            }

            // Get closed trades sorted by close_date ascending (oldest first)
            const closedTrades = (trades || [])
                .filter(t => !t.is_open && t.close_date)
                .sort((a, b) => new Date(a.close_date) - new Date(b.close_date));

            if (closedTrades.length === 0) {
                // No trades, show flat line at current balance
                balanceCharts[serverId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Now'],
                        datasets: [{
                            label: 'Balance',
                            data: [currentBalance],
                            borderColor: '#58a6ff',
                            backgroundColor: 'rgba(88, 166, 255, 0.1)',
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        events: [],
                        plugins: { legend: { display: false }, tooltip: { enabled: false } }
                    }
                });
                return;
            }

            // Use same date range as daily performance chart
            const now = new Date();
            let startDate;

            if (dailyData && dailyData.length > 0) {
                // Get earliest date from daily data (already sorted oldest first after reverse)
                const sortedDaily = [...dailyData].sort((a, b) => a.date.localeCompare(b.date));
                startDate = new Date(sortedDaily[0].date);
                startDate.setHours(0, 0, 0, 0);
            } else {
                // Fallback to configDays if no daily data
                startDate = new Date(now);
                startDate.setDate(startDate.getDate() - configDays);
                startDate.setHours(0, 0, 0, 0);
            }

            // Generate all 1-hour slots
            const slots = [];
            const slotTime = new Date(startDate);
            while (slotTime <= now) {
                slots.push(new Date(slotTime));
                slotTime.setHours(slotTime.getHours() + 1);
            }

            // Calculate starting balance by subtracting all trade profits from current balance
            const tradesInRange = closedTrades.filter(t => new Date(t.close_date) >= startDate);
            const totalProfitInRange = tradesInRange.reduce((sum, t) => sum + (t.profit_abs || 0), 0);
            let runningBalance = currentBalance - totalProfitInRange;

            // Calculate balance at each slot
            const balanceData = [];
            let tradeIdx = 0;

            // Find first trade in range
            while (tradeIdx < closedTrades.length && new Date(closedTrades[tradeIdx].close_date) < startDate) {
                tradeIdx++;
            }

            for (const slot of slots) {
                const slotEnd = new Date(slot);
                slotEnd.setHours(slotEnd.getHours() + 1);

                // Add profits from trades closed in this slot
                while (tradeIdx < closedTrades.length) {
                    const tradeDate = new Date(closedTrades[tradeIdx].close_date);
                    if (tradeDate < slotEnd) {
                        runningBalance += (closedTrades[tradeIdx].profit_abs || 0);
                        tradeIdx++;
                    } else {
                        break;
                    }
                }

                balanceData.push({ date: slot, balance: runningBalance });
            }

            // Create labels - show date for first slot of each day, empty otherwise
            const labels = balanceData.map((b, i) => {
                const d = b.date;
                const h = d.getHours();
                if (h === 0 || i === 0) {
                    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                }
                return '';
            });
            const values = balanceData.map(b => b.balance);

            balanceCharts[serverId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Balance',
                        data: values,
                        borderColor: '#58a6ff',
                        backgroundColor: 'rgba(88, 166, 255, 0.1)',
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    events: [],
                    interaction: { mode: null },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { display: false }
                        },
                        y: {
                            position: 'left',
                            grid: { color: 'rgba(48, 54, 61, 0.3)' },
                            ticks: {
                                color: '#8b949e',
                                font: { size: 9 },
                                callback: v => v.toFixed(0)
                            },
                            afterFit: (axis) => { axis.width = 45; }
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                            ticks: {
                                display: false
                            },
                            afterFit: (axis) => { axis.width = 20; }
                        }
                    }
                }
            });
        }

        let isFirstLoad = true;

        async function loadDashboard() {
            const loadingModal = document.getElementById('loadingModal');
            const loadingProgress = document.getElementById('loadingProgress');

            // Show loading modal only on first load
            if (isFirstLoad) {
                loadingModal.classList.add('show');
                loadingProgress.textContent = 'Connecting to API...';
            }

            try {
                if (isFirstLoad) loadingProgress.textContent = 'Fetching server data...';
                const response = await fetch('api.php');
                const result = await response.json();

                if (!result.success) {
                    console.error('API error:', result.error);
                    if (isFirstLoad) {
                        loadingProgress.textContent = 'Error: ' + (result.error || 'Unknown error');
                    }
                    return;
                }

                if (isFirstLoad) loadingProgress.textContent = 'Processing data...';
                const data = result.data;
                const settings = result.settings || {};

                // Update settings
                soundEnabled = settings.sound_enabled !== false;
                coinsEnabled = settings.coins_enabled === true;
                configDays = settings.days || 20;
                notifyDuration = settings.notify_duration || 10;

                // Show/hide summary stats based on settings
                const summaryStats = document.getElementById('summaryStats');
                if (settings.summary_enabled) {
                    summaryStats.style.display = '';
                    // Update summary stats
                    const totals = data.totals;
                    document.getElementById('serversOnline').textContent =
                        `${totals.servers_online}/${totals.servers_total}`;

                    const totalProfit = totals.total_profit || 0;
                    const profitEl = document.getElementById('totalProfit');
                    profitEl.textContent = formatProfit(totalProfit);
                    profitEl.className = `stat-value ${getProfitClass(totalProfit)}`;

                    const avgProfit = totals.total_profit_percent || 0;
                    const avgEl = document.getElementById('avgProfit');
                    avgEl.textContent = formatPercent(avgProfit);
                    avgEl.className = `stat-value ${getProfitClass(avgProfit)}`;

                    document.getElementById('closedTrades').textContent = totals.total_trades_closed || 0;
                    document.getElementById('openTrades').textContent = totals.total_open_trades || 0;
                    document.getElementById('winRate').textContent = (totals.win_rate || 0) + '%';
                } else {
                    summaryStats.style.display = 'none';
                }

                const container = document.getElementById('serverCards');
                const serversToUpdate = [];
                const newServerState = {};
                const serversWithActivity = []; // Track servers with new trades

                // Check each server for changes
                for (const [serverNum, server] of Object.entries(data.servers)) {
                    const sNum = server.server_num;
                    const oldState = previousServerState[sNum];
                    const changes = detectChanges(sNum, server, oldState);

                    // Store new state signature
                    newServerState[sNum] = getServerSignature(server);

                    // Show notifications for new trades (only after first load)
                    if (!isFirstLoad && server.online) {
                        const serverName = server.name || `Server ${sNum}`;
                        const strategy = server.config?.strategy || 'Unknown';

                        // Notify for new closed trades
                        for (const trade of changes.newClosedTrades) {
                            const coin = getCoinName(trade.pair);
                            const profit = trade.profit_abs || 0;
                            const pct = (trade.profit_pct || 0).toFixed(1);
                            const type = profit >= 0 ? 'profit' : 'loss';
                            const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
                            showNotification(
                                `${serverName} - ${strategy}`,
                                `Closed <strong>${coin}</strong> ${trade.is_short ? 'SHORT' : 'LONG'}: <span class="notification-profit ${profitClass}">${formatProfit(profit)} (${pct}%)</span>`,
                                type
                            );
                        }

                        // Notify for new open trades
                        for (const trade of changes.newOpenTrades) {
                            const coin = getCoinName(trade.pair);
                            showNotification(
                                `${serverName} - ${strategy}`,
                                `Opened <strong>${coin}</strong> ${trade.is_short ? 'SHORT' : 'LONG'}`,
                                'open'
                            );
                        }

                        // Track servers with new trades for activity star
                        if (changes.newClosedTrades.length > 0 || changes.newOpenTrades.length > 0) {
                            serversWithActivity.push(server.server_num);
                        }
                    }

                    if (changes.hasChanges) {
                        serversToUpdate.push(server);
                    }
                }

                // Update previous state
                previousServerState = newServerState;

                // Store server data globally for modal
                serverData = {};
                for (const [serverNum, server] of Object.entries(data.servers)) {
                    serverData[server.server_num] = server;
                }

                // On first load or if all servers need updating, rebuild everything
                if (isFirstLoad || serversToUpdate.length === Object.keys(data.servers).length) {
                    container.innerHTML = '';
                    for (const [serverNum, server] of Object.entries(data.servers)) {
                        container.innerHTML += createServerCard(server);
                    }
                    // Create charts for each server
                    for (const [serverNum, server] of Object.entries(data.servers)) {
                        if (server.online && server.daily?.data) {
                            const currentBalance = server.balance?.total || 0;
                            createBalanceChart(server.server_num, server.trades?.trades || [], currentBalance, server.daily.data);
                            createChart(server.server_num, server.daily.data);
                        }
                    }
                } else if (serversToUpdate.length > 0) {
                    // Only update changed cards
                    for (const server of serversToUpdate) {
                        const cardWrapper = document.getElementById(`server-card-${server.server_num}`);
                        if (cardWrapper) {
                            // Create temporary container to parse new HTML
                            const temp = document.createElement('div');
                            temp.innerHTML = createServerCard(server);
                            const newCard = temp.firstElementChild;

                            // Replace the card
                            cardWrapper.replaceWith(newCard);

                            // Recreate charts for this server
                            if (server.online && server.daily?.data) {
                                const currentBalance = server.balance?.total || 0;
                                createBalanceChart(server.server_num, server.trades?.trades || [], currentBalance);
                                createChart(server.server_num, server.daily.data);
                            }
                        }
                    }
                }
                // If no changes, do nothing (no refresh)

                // Show activity stars for servers with new trades (after DOM updates)
                for (const serverNum of serversWithActivity) {
                    showActivityStar(serverNum);
                }

                // Hide loading modal
                if (isFirstLoad) {
                    loadingModal.classList.remove('show');
                    isFirstLoad = false;
                }

            } catch (error) {
                console.error('Failed to load dashboard:', error);
                if (isFirstLoad) {
                    loadingProgress.textContent = 'Error loading data. Retrying...';
                }
            }
        }
        
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function setCookie(name, value, days) {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Strict`;
        }

        async function checkAuth() {
            const response = await fetch('api.php?action=check_auth');
            const result = await response.json();
            return result.auth_required;
        }

        async function submitPassword(e) {
            e.preventDefault();
            const password = document.getElementById('passwordInput').value;
            const errorEl = document.getElementById('passwordError');

            const response = await fetch('api.php?action=verify_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password })
            });
            const result = await response.json();

            if (result.success) {
                setCookie('freqmon_auth', btoa(password), 30);
                document.getElementById('passwordModal').classList.remove('show');
                startDashboard();
            } else {
                errorEl.textContent = 'Invalid password';
                document.getElementById('passwordInput').value = '';
                document.getElementById('passwordInput').focus();
            }
            return false;
        }

        function startDashboard() {
            loadDashboard();
            refreshInterval = setInterval(loadDashboard, REFRESH_SECONDS * 1000);
        }

        async function initApp() {
            const authRequired = await checkAuth();

            if (authRequired) {
                const savedAuth = getCookie('freqmon_auth');
                if (savedAuth) {
                    const response = await fetch('api.php?action=verify_password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ password: atob(savedAuth) })
                    });
                    const result = await response.json();
                    if (result.success) {
                        startDashboard();
                        return;
                    }
                }
                document.getElementById('passwordModal').classList.add('show');
                document.getElementById('passwordInput').focus();
            } else {
                startDashboard();
            }
        }

        initApp();
    </script>

    <footer class="site-footer">
        <a href="https://github.com/pulpoff/freqmon" target="_blank">&copy;2026 freqmon</a>
    </footer>

    <style>
        .site-footer {
            text-align: center;
            padding: 1rem 0;
            margin-top: 1rem;
        }
        .site-footer a {
            color: var(--text-secondary);
            font-size: 0.7rem;
            text-decoration: none;
        }
        .site-footer a:hover {
            color: var(--accent-blue);
        }

        /* Notification styles */
        .notification-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 350px;
        }

        .notification {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification.hiding {
            animation: slideOut 0.3s ease-in forwards;
        }

        .notification-icon {
            font-size: 1.2rem;
        }

        .notification-icon.profit {
            color: var(--accent-green);
        }

        .notification-icon.loss {
            color: var(--accent-red);
        }

        .notification-icon.open {
            color: var(--accent-blue);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .notification-message {
            font-size: 0.85rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .notification-profit {
            font-weight: 600;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</body>
</html>
