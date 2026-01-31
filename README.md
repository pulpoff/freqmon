# FreqTrade Multi-Server Dashboard

A PHP-based dashboard to monitor multiple FreqTrade trading bot instances from a single interface.

![Dashboard Preview](https://via.placeholder.com/800x400?text=FreqTrade+Dashboard)

## Features

- 📊 **Aggregated Statistics** - View total profit, trades, and win rate across all servers
- 🖥️ **Server Overview Cards** - Quick status view of each FreqTrade instance
- 📈 **Performance Chart** - Last 10 days profit/trade visualization with Chart.js
- 💹 **Recent Transactions** - Combined view of latest trades from all servers
- 🔄 **Open Trades Monitor** - Track all currently open positions
- 🔄 **Auto-Refresh** - Configurable auto-refresh interval
- 🌙 **Dark Theme** - Easy on the eyes for 24/7 monitoring
- ⚡ **AJAX Live Updates** - Real-time updates without page reload (dashboard.php)

## Requirements

- PHP 8.0+ with cURL extension
- Web server (Apache, Nginx, or PHP built-in server)
- FreqTrade instances with REST API enabled

## Installation

### 1. Clone or Download

```bash
# Create directory for the dashboard
mkdir /var/www/freqtrade-dashboard
cd /var/www/freqtrade-dashboard

# Copy files or clone from your repository
```

### 2. Configure Environment

```bash
# Copy example config
cp .env.example .env

# Edit with your server details
nano .env
```

### 3. Configure Your Servers

Edit `.env` file with your FreqTrade server details:

```env
# Format: SERVER_N=name|host:port|username|password

SERVER_1=Future55|192.168.10.100:4100|freqtrader|your_password
SERVER_2=Future60|192.168.10.100:4200|freqtrader|your_password
SERVER_3=Future65|192.168.10.100:4300|freqtrader|your_password
# ... add all 12 servers

# Dashboard Settings
REFRESH_INTERVAL=60
CACHE_TTL=30
TIMEZONE=Europe/Berlin
```

### 4. FreqTrade API Configuration

Ensure each FreqTrade instance has the REST API enabled in `config.json`:

```json
{
    "api_server": {
        "enabled": true,
        "listen_ip_address": "0.0.0.0",
        "listen_port": 4100,
        "verbosity": "error",
        "jwt_secret_key": "your-random-secret-key",
        "username": "freqtrader",
        "password": "your_password"
    }
}
```

⚠️ **Security Note**: Only expose the API within your private network. Use VPN or SSH tunnels for remote access.

### 5. Start the Dashboard

**Option A: PHP Built-in Server (Development)**

```bash
cd /var/www/freqtrade-dashboard
php -S 0.0.0.0:8888
```

**Option B: Apache**

```apache
<VirtualHost *:80>
    ServerName freqtrade.local
    DocumentRoot /var/www/freqtrade-dashboard
    
    <Directory /var/www/freqtrade-dashboard>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Option C: Nginx**

```nginx
server {
    listen 80;
    server_name freqtrade.local;
    root /var/www/freqtrade-dashboard;
    index index.php dashboard.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Usage

### Dashboard Views

| URL | Description |
|-----|-------------|
| `index.php` | Standard dashboard with meta-refresh |
| `dashboard.php` | Live dashboard with AJAX updates (recommended) |
| `api.php` | JSON API endpoint for custom integrations |

### API Endpoint

The `api.php` endpoint returns JSON data:

```bash
curl http://localhost:8888/api.php
```

Response:
```json
{
    "success": true,
    "timestamp": "2025-01-30 15:30:00",
    "data": {
        "servers": {...},
        "totals": {...},
        "transactions": [...],
        "daily": [...],
        "open_trades": [...]
    }
}
```

## File Structure

```
freqtrade-dashboard/
├── .env.example        # Configuration template
├── .env                # Your configuration (create this)
├── index.php           # Main dashboard (meta-refresh)
├── dashboard.php       # Live dashboard (AJAX)
├── api.php             # JSON API endpoint
├── README.md           # This file
└── src/
    ├── Config.php          # Configuration loader
    ├── Dashboard.php       # Dashboard data aggregator
    └── FreqtradeClient.php # FreqTrade API client
```

## FreqTrade API Endpoints Used

| Endpoint | Description |
|----------|-------------|
| `/api/v1/ping` | Health check |
| `/api/v1/token/login` | JWT authentication |
| `/api/v1/profit` | Profit summary |
| `/api/v1/daily` | Daily performance |
| `/api/v1/trades` | Trade history |
| `/api/v1/status` | Open trades |
| `/api/v1/balance` | Account balance |
| `/api/v1/show_config` | Bot configuration |
| `/api/v1/count` | Trade count |

## Troubleshooting

### Server Shows Offline

1. Check if FreqTrade is running: `docker ps` or `systemctl status freqtrade`
2. Verify API is enabled in FreqTrade config
3. Test connection: `curl http://SERVER_IP:PORT/api/v1/ping`
4. Check firewall rules

### Authentication Failed

1. Verify username/password in `.env`
2. Ensure credentials match FreqTrade `config.json`
3. Check for special characters in password (may need escaping)

### No Data Displayed

1. Check PHP error logs
2. Verify cURL extension is installed: `php -m | grep curl`
3. Test API manually with curl

## Security Recommendations

1. **Never expose the dashboard directly to the internet**
2. Use a VPN or SSH tunnel for remote access
3. Enable HTTPS if accessible outside localhost
4. Use strong, unique passwords for each FreqTrade instance
5. Consider adding authentication to the dashboard itself

## Customization

### Adding More Statistics

Edit `src/Dashboard.php` to fetch additional data:

```php
public function getPerformance(): ?array
{
    // Add performance by pair
    return $this->get('performance');
}
```

### Changing Refresh Interval

Edit `.env`:

```env
REFRESH_INTERVAL=30  # Refresh every 30 seconds
```

### Modifying Colors

Edit the CSS in `index.php` or `dashboard.php`:

```css
:root {
    --bs-body-bg: #0d1117;      /* Background color */
    --card-bg: #161b22;          /* Card background */
    --card-border: #30363d;      /* Card borders */
}
```

## Contributing

Feel free to submit issues and pull requests!

## License

MIT License - feel free to use and modify as needed.

## Credits

- [FreqTrade](https://github.com/freqtrade/freqtrade) - The trading bot
- [Bootstrap 5](https://getbootstrap.com/) - CSS framework
- [Chart.js](https://www.chartjs.org/) - Charts library
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icons
