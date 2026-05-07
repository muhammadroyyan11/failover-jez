# Laravel Failover Panel

Panel manajemen failover untuk server production dengan fitur database replication monitoring dan management.

## 🎯 Features

### ✅ Failover Management
- Manual failover antara server primary dan replica
- Real-time server health monitoring
- DNS switching via Cloudflare API
- Failover logs dengan detail lengkap

### ✅ Dynamic Server Management
- Add/Edit/Delete servers via UI (tidak perlu edit .env)
- Priority system untuk failover order
- Toggle active/inactive status
- Promote server to primary

### ✅ Database Replication Management
- **Test Database Connection** - Verify koneksi ke database
- **Check Replication Status** - Monitor master/slave status real-time
- Support database terpisah dari web server
- Master binlog position monitoring
- Slave replication lag monitoring
- Replication health indicators

### ✅ SSH & CyberPanel Integration
- SSH connection dengan password atau key authentication
- Remote command execution
- CyberPanel API integration untuk web server management
- LiteSpeed restart capability

### ✅ Security
- Bearer token + HMAC signature authentication
- Encrypted passwords (DB, SSH, Replication, CyberPanel)
- Superadmin role-based access
- Replay attack prevention

---

## 📋 Requirements

- Docker & Docker Compose
- PHP 8.2+
- MySQL 8.0+
- Composer
- Git

---

## 🚀 Installation

### 1. Clone Repository

```bash
git clone https://github.com/muhammadroyyan11/failover-jez.git
cd failover-jez
```

### 2. Copy Environment File

```bash
cp .env.example .env
```

Edit `.env` dan sesuaikan konfigurasi:
```env
APP_NAME="Failover Panel"
APP_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=failover_panel
DB_USERNAME=root
DB_PASSWORD=secret

# Cloudflare DNS
CLOUDFLARE_ZONE_ID=your_zone_id
CLOUDFLARE_RECORD_ID=your_record_id
CLOUDFLARE_API_TOKEN=your_api_token

# Agent Authentication
FAILOVER_AGENT_TOKEN=your_secure_token_here
FAILOVER_HMAC_SECRET=your_hmac_secret_here
```

### 3. Start Docker Containers

```bash
docker compose up -d
```

### 4. Install Dependencies

```bash
docker compose exec app composer install
```

### 5. Generate Application Key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run Migrations

```bash
docker compose exec app php artisan migrate --seed
```

### 7. Access Panel

```
http://localhost:3000
```

**Default Login:**
- Email: `admin@jezpro.id`
- Password: `Admin@12345`

---

## 📁 Project Structure

```
laravel-app/
├── app/
│   ├── Console/              # Artisan commands
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/        # Admin controllers
│   │   │   │   ├── FailoverController.php
│   │   │   │   └── ServerController.php
│   │   │   ├── Api/          # API controllers
│   │   │   └── Auth/         # Authentication
│   │   └── Middleware/       # Custom middleware
│   ├── Models/               # Eloquent models
│   │   ├── FailoverServer.php
│   │   ├── FailoverSetting.php
│   │   └── FailoverLog.php
│   ├── Services/             # Business logic
│   │   ├── DatabaseReplicationService.php
│   │   ├── FailoverService.php
│   │   ├── CloudflareDnsService.php
│   │   ├── ServerAgentClient.php
│   │   ├── SshService.php
│   │   └── CyberPanelService.php
│   └── Providers/
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── failover/     # Failover views
│       │   └── servers/      # Server management views
│       └── auth/             # Login views
├── routes/
│   ├── web.php               # Web routes
│   └── failover.php          # Failover routes
├── docker-compose.yml        # Docker configuration
└── Dockerfile                # Docker image
```

---

## 🎯 Usage

### 1. Manage Servers

**Add New Server:**
1. Go to **Manage Servers** → **Add New Server**
2. Fill in server details:
   - Server Name: `jh` (unique identifier)
   - Display Label: `VPS JH`
   - IP Address: `103.245.39.246`
   - Agent URL: `https://jezpro.id`
   - Role: `primary` or `replica`
   - Priority: `100` (higher = preferred)

**SSH Configuration:**
- SSH Host: `103.245.39.246`
- SSH Port: `22`
- SSH User: `root`
- SSH Password: `***` (encrypted)
- App Path: `/home/jezpro.id/public_html`

**Database Configuration:**
- DB Host: `103.245.39.246` (use external IP, NOT localhost)
- DB Port: `3306`
- DB Username: `root`
- DB Password: `***` (encrypted)
- Database Name: `jez_erp`
- DB Role: `master` or `slave`
- Replication User: `repl_user`
- Replication Password: `***` (encrypted)

**CyberPanel Configuration:**
- CyberPanel URL: `https://103.245.39.246:8090`
- Username: `admin`
- Password: `***` (encrypted)

### 2. Test Database Connection

1. Edit server
2. Scroll to **Database Configuration** section
3. Click **"Test DB Connection"**
4. Alert akan muncul dengan hasil:
   - ✅ Success: Shows MySQL version
   - ❌ Failed: Shows error message

### 3. Check Replication Status

1. Edit server (yang sudah diisi DB config)
2. Click **"Check Replication Status"**
3. Alert akan muncul dengan:
   - **Master**: Binlog file & position
   - **Slave**: IO/SQL running status, seconds behind master

### 4. Monitor Dashboard

Dashboard menampilkan:
- Server status (online/offline)
- System metrics (CPU, Memory, Disk)
- Replication status
- DNS current target
- Recent failover logs

### 5. Execute Failover

1. Go to **Dashboard**
2. Click **"Failover to [Server]"**
3. Complete checklist:
   - ✅ Backup database
   - ✅ Verify replication lag < 10s
   - ✅ Notify team
   - ✅ Check DNS propagation
   - ✅ Confirm downtime window
4. Enter password confirmation
5. Click **"Execute Failover"**

---

## 🔧 Configuration

### Docker Networking

**⚠️ Important for Database Connection:**

Jangan gunakan `localhost` atau `127.0.0.1` untuk DB Host karena akan connect ke container itu sendiri.

**Gunakan:**
- **External IP**: `103.245.39.246` (recommended)
- **Host Machine**: `host.docker.internal` (Mac/Windows) atau `172.17.0.1` (Linux)

### Environment Variables

```env
# Failover Settings
FAILOVER_JH_IP=103.245.39.246
FAILOVER_UPCLOUD_IP=45.76.123.456
FAILOVER_PRIMARY_DOMAIN=jezpro.id
FAILOVER_STANDBY_DOMAIN=jezpro.com

# Agent URLs
FAILOVER_JH_AGENT_URL=https://jezpro.id
FAILOVER_UPCLOUD_AGENT_URL=https://jezpro.com

# Authentication
FAILOVER_AGENT_TOKEN=your_secure_token_here
FAILOVER_HMAC_SECRET=your_hmac_secret_here

# Cloudflare
CLOUDFLARE_ZONE_ID=your_zone_id
CLOUDFLARE_RECORD_ID=your_record_id
CLOUDFLARE_API_TOKEN=your_api_token
```

---

## 🗄️ Database Schema

### Table: `failover_servers`

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `name` | varchar | Unique identifier (jh, upcloud) |
| `label` | varchar | Display name (VPS JH) |
| `ip_address` | varchar | Server IP |
| `agent_url` | varchar | Agent API URL |
| `domain` | varchar | Domain name |
| `role` | enum | primary/replica |
| `is_active` | boolean | Active status |
| `priority` | integer | Failover priority (0-999) |
| `ssh_host` | varchar | SSH host |
| `ssh_port` | integer | SSH port |
| `ssh_user` | varchar | SSH username |
| `ssh_password` | text | SSH password (encrypted) |
| `app_path` | varchar | Application path |
| `db_host` | varchar | Database host |
| `db_port` | integer | Database port |
| `db_username` | varchar | Database username |
| `db_password` | text | Database password (encrypted) |
| `db_database` | varchar | Database name |
| `db_role` | enum | standalone/master/slave |
| `replication_user` | varchar | Replication username |
| `replication_password` | text | Replication password (encrypted) |
| `replication_io_running` | boolean | IO thread status |
| `replication_sql_running` | boolean | SQL thread status |
| `seconds_behind_master` | integer | Replication lag |
| `cyberpanel_url` | varchar | CyberPanel URL |
| `cyberpanel_user` | varchar | CyberPanel username |
| `cyberpanel_pass` | text | CyberPanel password (encrypted) |

---

## 🔐 Security

### Password Encryption

Semua password fields di-encrypt menggunakan Laravel's `encrypt()`:
- ✅ `db_password`
- ✅ `replication_password`
- ✅ `ssh_password`
- ✅ `cyberpanel_pass`

### Authentication

Agent API menggunakan:
1. **Bearer Token** - Static token untuk identifikasi
2. **HMAC Signature** - Request signing dengan timestamp
3. **Replay Attack Prevention** - Timestamp tolerance 60 detik

---

## 📊 API Endpoints

### Agent API (Install di Production Server)

```bash
# Health Check
GET /api/agent/health
Authorization: Bearer {token}
X-Agent-Timestamp: {timestamp}
X-Agent-Signature: {hmac_signature}

# System Status
GET /api/agent/system-status

# Replication Status
GET /api/agent/replication-status
```

### Panel API

```bash
# Test Database Connection
POST /admin/servers/{server}/test-db

# Check Replication Status
POST /admin/servers/{server}/check-replication

# Setup Replication
POST /admin/servers/{server}/setup-replication

# Promote Database to Master
POST /admin/servers/{server}/promote-db
```

---

## 🐛 Troubleshooting

### Error: "No such file or directory"

**Penyebab:** DB Host diisi `localhost` atau `127.0.0.1`

**Solusi:** Gunakan IP external (`103.245.39.246`) atau `host.docker.internal`

### Error: "Connection refused"

**Penyebab:** Database tidak bisa diakses dari Docker container

**Solusi:**
1. Cek firewall di database server
2. Pastikan MySQL bind-address = `0.0.0.0` (bukan `127.0.0.1`)
3. Grant access: `GRANT ALL ON *.* TO 'root'@'%' IDENTIFIED BY 'password';`

### Replication Position Berbeda

**Normal!** Position selalu berubah setiap ada transaksi baru. Yang penting:
- ✅ File name sama (mysql-bin.000053)
- ✅ Connection berhasil
- ✅ Tidak ada error

---

## 📝 License

This project is proprietary software for Jez Pro internal use.

---

## 👨‍💻 Author

**Muhammad Royyan**
- GitHub: [@muhammadroyyan11](https://github.com/muhammadroyyan11)

---

## 🙏 Acknowledgments

- Laravel Framework
- Docker
- Cloudflare API
- Bootstrap 5
- MySQL Replication
