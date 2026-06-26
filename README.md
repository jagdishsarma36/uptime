# UptimeGuard

A professional, self-hosted uptime monitoring platform built with Laravel 13. Monitor HTTP/HTTPS endpoints, SSL certificates, and domain registrations with real-time alerts via email, Slack, and webhooks.

## Features

### Monitoring
- **HTTP/HTTPS** — status code validation, response time tracking, keyword matching
- **SSL Certificate** — expiry tracking with configurable alert thresholds (default 20 days)
- **Domain Registration** — WHOIS-based expiry monitoring across 20+ TLDs
- **TCP** — port connectivity checks
- **DNS** — record resolution checks

### Alerting
- **Email** — SMTP-based notifications
- **Slack** — incoming webhook integration
- **Webhook** — custom HTTP POST with JSON payload
- **Throttling** — configurable cooldown between alerts per monitor
- **Per-monitor control** — enable/disable alerts for down, up, SSL expiry, domain expiry independently

### Status Pages
- **Public status pages** with statuspage.io-style design
- **Password protection** for private pages
- **Incident management** with timeline updates and status transitions
- **Custom domains** support (planned)

### Team & Multi-Tenancy
- **Multi-team** workspace with invite system
- **Role-based access** — super-admin, admin, member roles via Spatie Permission
- **Super-admin** bypasses all plan limits
- **Team switching** from the dashboard

### API & Embeds
- **REST API v1** with Sanctum token authentication
- **Embeddable uptime badges** (SVG)
- **Status badge** endpoints for external embedding

### Admin
- **Admin settings panel** for email, Slack, and alert configuration
- **DB-backed settings** with `.env` sync for mail transport
- **Audit log** for tracking changes

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| PHP | >= 8.3 |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Real-time UI | Livewire 4 |
| Auth | Laravel Breeze (Blade) |
| API Auth | Laravel Sanctum |
| Permissions | Spatie Laravel Permission |
| Database | SQLite (dev) / MySQL / PostgreSQL |
| Queue | Database driver (Redis for production) |
| Scheduler | Laravel Task Scheduler (cron) |

## Requirements

- PHP 8.3+ with extensions: `openssl`, `pdo`, `mbstring`, `curl`, `whois` (or socket support)
- Composer
- Node.js & NPM (for asset compilation)
- SQLite (default) or MySQL/PostgreSQL

## Installation

```bash
# Clone the repository
git clone <your-repo-url>
cd uptime-monitor

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (dev)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed default admin user and sample data
php artisan db:seed

# Compile frontend assets
npm run build

# Start the development server
php artisan serve
```

Visit `http://localhost:8000` and log in with:

```
Email:    admin@uptimeguard.io
Password: password
```

## Configuration

### Environment Variables

Key variables in `.env`:

```env
# Database (SQLite by default, switch to MySQL for production)
DB_CONNECTION=sqlite

# Mail (set to smtp for real email delivery)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=UptimeGuard

# Queue (switch to redis/sqs for production)
QUEUE_CONNECTION=database

# Monitoring defaults
MONITOR_HTTP_TIMEOUT=10
MONITOR_SSL_CHECK_INTERVAL_HOURS=24
MONITOR_DOMAIN_CHECK_INTERVAL_HOURS=48
MONITOR_DEFAULT_ALERT_THRESHOLD_DAYS=20
MONITOR_MAX_BATCH_SIZE=100
MONITOR_RETENTION_DAYS=60
```

### Admin Settings

Navigate to **Admin > Settings** in the sidebar (super-admin only) to configure:

- **Email** — SMTP host, port, credentials, from address
- **Slack** — Webhook URL
- **Alerts** — Global defaults for alert thresholds, check intervals, SSL/domain expiry days

Settings are stored in the database and synced to `.env` for mail configuration.

## Scheduled Tasks

The Laravel scheduler runs every minute via cron:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This dispatches:
- **HTTP checks** — based on each monitor's `check_interval_seconds`
- **SSL checks** — every 24h per monitor (configurable)
- **Domain checks** — every 48h per monitor (configurable)
- **Log cleanup** — daily, removes records older than retention period

## Queue Workers

For production, run queue workers to process background jobs:

```bash
php artisan queue:work --queue=checks,ssl-checks,domain-checks,alerts
```

## API Usage

Generate a personal access token:

```bash
php artisan tinker
>>> $user = App\Models\User::first();
>>> $token = $user->createToken('api-token')->plainTextToken;
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/monitors` | List monitors |
| POST | `/api/v1/monitors` | Create monitor |
| GET | `/api/v1/monitors/{id}` | Get monitor |
| PUT | `/api/v1/monitors/{id}` | Update monitor |
| DELETE | `/api/v1/monitors/{id}` | Delete monitor |
| POST | `/api/v1/monitors/{id}/toggle-pause` | Toggle pause |
| POST | `/api/v1/monitors/{id}/check-now` | Run immediate check |
| GET | `/api/v1/monitors/{id}/logs` | Get check history |

### Example

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost:8000/api/v1/monitors
```

## Database Schema

| Table | Purpose |
|-------|---------|
| `monitors` | Monitor configurations |
| `check_results` | HTTP check history |
| `monitor_ssl` | SSL certificate check history |
| `monitor_domain` | Domain expiry check history |
| `monitor_groups` | Grouping for monitors |
| `notification_channels` | Alert channel configs |
| `notification_log` | Sent alert history |
| `status_pages` | Public status page configs |
| `incidents` | Incident records |
| `incident_updates` | Incident timeline entries |
| `maintenance_windows` | Scheduled maintenance |
| `settings` | Key-value admin settings |
| `audit_log` | Change audit trail |
| `teams` / `team_user` | Multi-tenant teams |
| `model_has_roles` / `model_has_permissions` | Spatie RBAC |

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/SettingsController.php    # Admin email/slack/alert config
│   ├── Api/MonitorController.php       # REST API endpoints
│   ├── DashboardController.php         # Dashboard with auto-checks
│   ├── MonitorController.php           # Monitor CRUD + check-now
│   ├── PublicStatusController.php      # Public status pages
│   ├── StatusPageController.php        # Status page management
│   ├── IncidentController.php          # Incident management
│   └── TeamController.php              # Team management
├── Jobs/
│   ├── HttpCheckJob.php                # HTTP check (queued)
│   ├── SslCheckJob.php                 # SSL check (queued)
│   ├── DomainCheckJob.php              # WHOIS domain check (queued)
│   ├── SendAlertJob.php                # Alert dispatcher (queued)
│   ├── DispatchCheckJobs.php           # Scheduler entry point
│   └── CleanupOldLogs.php              # Log retention cleanup
├── Models/
│   ├── Monitor.php                     # Core monitor model
│   ├── MonitorSsl.php                  # SSL check records
│   ├── MonitorDomain.php               # Domain check records
│   ├── CheckResult.php                 # HTTP check results
│   ├── NotificationChannel.php         # Alert channel config
│   ├── StatusPage.php                  # Status page config
│   ├── Incident.php                    # Incident records
│   ├── Setting.php                     # Key-value settings
│   └── Team.php                        # Multi-tenant teams
├── Services/
│   ├── CheckService.php                # Synchronous HTTP/SSL/domain checks
│   ├── UptimeCalculator.php            # Time-weighted uptime math
│   └── TeamService.php                 # Team CRUD + invitations
└── Providers/
    └── SettingsServiceProvider.php      # Loads DB settings into mail config
```

## Roles & Permissions

| Role | Access |
|------|--------|
| `super-admin` | Full access, no plan limits, admin settings |
| `admin` | Team management, all monitors |
| `member` | Assigned monitors only |

## Default Credentials

| User | Email | Password | Role |
|------|-------|----------|------|
| Admin | `admin@uptimeguard.io` | `password` | super-admin |

> Change the default password immediately in production.

## Roadmap

- [ ] Stripe billing integration
- [ ] Custom domains for status pages
- [ ] Dark mode toggle
- [ ] 2FA (TOTP) support
- [ ] Webhook integrations (Discord, Microsoft Teams, Zapier)
- [ ] Docker Compose deployment
- [ ] Feature / Browser / Load testing suite
- [ ] WHOIS library for improved domain parsing

## License

MIT License. See [LICENSE](LICENSE) for details.
