<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>freqmon</title>
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
            margin-left: 0.25rem;
            opacity: 0.7;
        }
        
        .info-btn:hover {
            opacity: 1;
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
        
        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-header h5 {
            margin: 0;
            color: #fff;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #fff;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.8rem;
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
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--accent-blue);
            font-size: 0.85rem;
        }
        
        .trade-modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            max-width: 700px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .trade-chart-container {
            height: 300px;
            margin: 1rem 0;
        }
        
        .trade-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .trade-detail {
            padding: 0.5rem;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
        
        .trade-detail-label {
            font-size: 0.65rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .trade-detail-value {
            font-size: 0.85rem;
            color: #fff;
            font-weight: 500;
        }
        
        .trade-loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
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
    </style>
</head>
<body>
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
        <div class="row g-3" id="serverCards">
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

    <!-- Loading Modal -->
    <div class="modal-overlay" id="loadingModal">
        <div class="loading-modal-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading servers...</div>
            <div class="loading-progress" id="loadingProgress"></div>
        </div>
    </div>

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
        let strategyBalanceChart = null;

        function showStrategyInfo(serverNum, event) {
            event.stopPropagation();
            const server = serverData[serverNum];
            if (!server) return;
            
            const profit = server.profit || {};
            const config = server.config || {};
            const balance = server.balance || {};
            
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = `${server.name} - ${config.strategy || 'N/A'}`;
            
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

            // Get daily data for balance chart
            const dailyData = server.daily?.data || [];
            const currentBalance = balance.total || 0;

            // Build balance chart HTML
            let balanceChartHtml = '';
            if (dailyData.length > 0) {
                balanceChartHtml = `
                    <div class="section-title" style="margin: 0.75rem 0 0.5rem 0;"><i class="bi bi-graph-up me-1"></i>Balance History (Last 20 Days)</div>
                    <div style="height: 120px; margin-bottom: 0.75rem;">
                        <canvas id="strategyBalanceCanvas"></canvas>
                    </div>
                `;
            }

            modalBody.innerHTML = `
                <div class="info-summary">${summaryLine}</div>
                ${balanceChartHtml}
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

            // Create balance chart if we have daily data
            if (dailyData.length > 0) {
                // Destroy previous chart if exists
                if (strategyBalanceChart) {
                    strategyBalanceChart.destroy();
                    strategyBalanceChart = null;
                }

                // Reverse so oldest is first, take last 20 days
                const reversedDaily = [...dailyData].reverse().slice(-20);

                // Calculate the balance at each day's end
                // Start from current balance and work backwards through daily profits
                const balances = [];
                let bal = currentBalance;
                for (let i = reversedDaily.length - 1; i >= 0; i--) {
                    balances.unshift({ date: reversedDaily[i].date, balance: bal });
                    bal -= (reversedDaily[i].abs_profit || 0);
                }

                const balLabels = balances.map(b => {
                    const date = new Date(b.date);
                    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                });
                const balValues = balances.map(b => b.balance);

                const balCtx = document.getElementById('strategyBalanceCanvas');
                if (balCtx) {
                    strategyBalanceChart = new Chart(balCtx, {
                        type: 'line',
                        data: {
                            labels: balLabels,
                            datasets: [{
                                label: 'Balance',
                                data: balValues,
                                borderColor: '#58a6ff',
                                backgroundColor: 'rgba(88, 166, 255, 0.1)',
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 2,
                                tension: 0.3
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
                                    ticks: {
                                        color: '#8b949e',
                                        font: { size: 9 },
                                        maxRotation: 0,
                                        maxTicksLimit: 10
                                    }
                                },
                                y: {
                                    grid: { color: 'rgba(48, 54, 61, 0.3)' },
                                    ticks: {
                                        color: '#8b949e',
                                        font: { size: 9 },
                                        callback: v => v.toFixed(0)
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('strategyModal').classList.remove('show');
            if (strategyBalanceChart) {
                strategyBalanceChart.destroy();
                strategyBalanceChart = null;
            }
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeTradeModal();
            }
        });
        
        let tradeChart = null;

        function closeTradeModal(event) {
            if (event && event.target !== event.currentTarget) return;
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
            
            document.getElementById('tradeModalTitle').textContent = `${trade.pair} - ${trade.is_short ? 'Short' : 'Long'}`;
            document.getElementById('tradeModalBody').innerHTML = '<div class="trade-loading"><i class="bi bi-hourglass-split"></i> Loading chart...</div>';
            document.getElementById('tradeModal').classList.add('show');
            
            // Format dates for display (using original strings for display)
            const formatDateTime = (d) => {
                return d.toLocaleString('en-GB', { 
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            };
            
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
                const closeDate = parseDate(trade.close_date);
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
                const tradePriceAvg = (trade.open_rate + trade.close_rate) / 2;
                const priceDiff = Math.abs(firstPrice - tradePriceAvg) / tradePriceAvg;
                if (priceDiff > 0.1) { // More than 10% difference
                    console.warn('Price mismatch! Chart:', firstPrice, 'Trade:', tradePriceAvg, 'Diff:', (priceDiff * 100).toFixed(1) + '%');
                }
                
                labels = klines.map(k => new Date(k[0]));
                prices = labels.map((t, i) => ({ x: t, y: parseFloat(klines[i][4]) }));
                
                // Create point markers at actual trade times and prices (using exact coordinates)
                const entryPoint = [{ x: openDate, y: trade.open_rate }];
                const exitPoint = [{ x: closeDate, y: trade.close_rate }];
                
                const profitColor = (trade.profit_abs || 0) >= 0 ? '#3fb950' : '#f85149';

                document.getElementById('tradeModalBody').innerHTML = `
                    <div class="trade-chart-container">
                        <canvas id="tradeChartCanvas"></canvas>
                    </div>
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
                
                const ctx = document.getElementById('tradeChartCanvas');
                tradeChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
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
                                pointRadius: 10,
                                pointStyle: 'circle',
                                showLine: false
                            },
                            {
                                label: 'Exit',
                                data: exitPoint,
                                borderColor: '#f85149',
                                backgroundColor: '#f85149',
                                pointRadius: 10,
                                pointStyle: 'circle',
                                showLine: false
                            }
                        ]
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
            const d = new Date(dateStr);
            const day = d.getDate().toString().padStart(2, '0');
            const mon = (d.getMonth() + 1).toString().padStart(2, '0');
            const hrs = d.getHours().toString().padStart(2, '0');
            const min = d.getMinutes().toString().padStart(2, '0');
            return `${day}.${mon} ${hrs}:${min}`;
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
            
            // Get last 20 closed trades for this server
            const closedTrades = trades.filter(t => !t.is_open).slice(0, 20);
            
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
            const profitToday = trades
                .filter(t => {
                    if (!t.close_date || t.is_open) return false;
                    const closeDay = t.close_date.substring(0, 10);
                    return closeDay === todayStr;
                })
                .reduce((sum, t) => sum + (t.profit_abs || 0), 0);
            const balance = server.balance?.total || 0;
            const balanceBeforeToday = balance - profitToday;
            
            // Calculate days running from profit API or trades
            let daysText = '';
            if (profit.first_trade_humanized) {
                // Use humanized string like "2 days ago" -> extract just the time part
                daysText = `(${profit.first_trade_humanized.replace(' ago', '')})`;
            } else if (profit.first_trade_timestamp) {
                const firstTrade = new Date(profit.first_trade_timestamp * 1000);
                const now = new Date();
                const days = Math.max(1, Math.floor((now - firstTrade) / (1000 * 60 * 60 * 24)));
                daysText = `(${days}d)`;
            } else if (trades.length > 0) {
                const allDates = trades
                    .map(t => t.open_date)
                    .filter(d => d)
                    .map(d => new Date(d));
                if (allDates.length > 0) {
                    const earliest = new Date(Math.min(...allDates.map(d => d.getTime())));
                    const now = new Date();
                    const days = Math.max(1, Math.floor((now - earliest) / (1000 * 60 * 60 * 24)));
                    daysText = `(${days}d)`;
                }
            }
            
            return `
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card server-card">
                    <div class="card-body p-2">
                        <div class="server-header">
                            <div>
                                <h5 class="server-name">${escapeHtml(server.name)}</h5>
                                ${isOnline ? `<div class="strategy-name"><i class="bi bi-cpu me-1"></i>${escapeHtml(strategy)}${daysText ? ` <span class="days-badge">${daysText}</span>` : ''} <span class="info-btn" onclick="showStrategyInfo(${server.server_num}, event)"><i class="bi bi-info-circle"></i></span></div>` : ''}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                ${openTrades.length > 0 ? `<span class="open-trades-count">${openTrades.length} open</span>` : ''}
                                ${!isOnline ? `<span class="badge badge-offline"><i class="bi bi-x-circle me-1"></i>Offline</span>` : 
                                    (config.dry_run === false ? `<span class="badge badge-live"><i class="bi bi-lightning-charge me-1"></i>Live</span>` : '')}
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
                                <span class="mini-stat-value ${getProfitClass(profitPercent)}">${formatPercent(profitPercent)}</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-label">Trades</span>
                                <span class="mini-stat-value">${tradesBeforeToday}${tradesToday > 0 ? ` <span class="text-info">+${tradesToday}</span>` : ''}</span>
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
                                <span class="mini-stat-value">${profitToday !== 0 ? balanceBeforeToday.toFixed(1) + ` <span class="${profitToday >= 0 ? 'text-success' : 'text-danger'}">${profitToday >= 0 ? '+' : ''}${profitToday.toFixed(1)}</span>` : balance.toFixed(1)}</span>
                            </div>
                        </div>
                        
                        <div class="section-title"><i class="bi bi-bar-chart me-1"></i>Daily Performance</div>
                        <div class="chart-container">
                            <canvas id="chart-${server.server_num}"></canvas>
                        </div>
                        
                        <div class="section-title"><i class="bi bi-list-ul me-1"></i>Last 20 Transactions</div>
                        ${closedTrades.length > 0 ? `
                        <div class="trades-scroll">
                            <table class="table table-dark mini-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Pair</th>
                                        <th class="text-end">Profit</th>
                                        <th class="text-end">%</th>
                                        <th>Exit</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${closedTrades.sort((a, b) => new Date(b.close_date) - new Date(a.close_date)).map(t => {
                                        const rowClass = (t.profit_abs || 0) >= 0 ? 'row-profit' : 'row-loss';
                                        const closeDate = t.close_date ? formatCompactDate(t.close_date) : '-';
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
                                        <td><small style="color: #8b949e;">${closeDate}</small></td>
                                    </tr>
                                    `}).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : '<div class="no-data">No closed trades yet</div>'}
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
                            }
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                            ticks: { 
                                color: '#58a6ff',
                                font: { size: 9 },
                                stepSize: 1
                            }
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
                
                // Store server data globally for modal
                serverData = {};
                for (const [serverNum, server] of Object.entries(data.servers)) {
                    serverData[server.server_num] = server;
                }
                
                // Update server cards
                const container = document.getElementById('serverCards');
                container.innerHTML = '';
                
                for (const [serverNum, server] of Object.entries(data.servers)) {
                    container.innerHTML += createServerCard(server);
                }
                
                // Create charts for each server
                for (const [serverNum, server] of Object.entries(data.servers)) {
                    if (server.online && server.daily?.data) {
                        createChart(server.server_num, server.daily.data);
                    }
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
        
        // Initial load
        loadDashboard();
        
        // Auto refresh
        refreshInterval = setInterval(loadDashboard, REFRESH_SECONDS * 1000);
    </script>
</body>
</html>
