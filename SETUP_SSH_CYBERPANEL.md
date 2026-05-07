# Setup SSH & CyberPanel Integration

## Prerequisites

1. PPK key untuk UPCLOUD server
2. CyberPanel admin credentials untuk JH dan UPCLOUD
3. Docker sudah running

## Step 1: Install phpseclib

```bash
cd laravel-app
docker compose exec app composer require phpseclib/phpseclib:~3.0
```

## Step 2: Convert PPK Key ke OpenSSH Format

Jika kamu punya PPK key (PuTTY format), convert dulu:

```bash
# Install puttygen (jika belum ada)
sudo apt install putty-tools

# Convert PPK ke OpenSSH
puttygen upcloud.ppk -O private-openssh -o upcloud_key
chmod 600 upcloud_key
```

Jika sudah punya OpenSSH key (id_rsa, id_ed25519, dll), skip step ini.

## Step 3: Copy SSH Keys ke Container

```bash
# Buat directory ssh di storage
docker compose exec app mkdir -p /var/www/html/storage/ssh
docker compose exec app chmod 700 /var/www/html/storage/ssh

# Copy key untuk UPCLOUD
docker cp upcloud_key failover-panel:/var/www/html/storage/ssh/upcloud_key
docker compose exec app chmod 600 /var/www/html/storage/ssh/upcloud_key

# Copy key untuk JH (jika ada)
docker cp jh_key failover-panel:/var/www/html/storage/ssh/jh_key
docker compose exec app chmod 600 /var/www/html/storage/ssh/jh_key
```

## Step 4: Update .env

Edit `laravel-app/.env` atau `docker-compose.yml`:

```env
# SSH Configuration
JH_SSH_HOST=1.2.3.4
JH_SSH_PORT=22
JH_SSH_USER=root
JH_APP_PATH=/home/jezpro/public_html

UPCLOUD_SSH_HOST=5.6.7.8
UPCLOUD_SSH_PORT=22
UPCLOUD_SSH_USER=root
UPCLOUD_APP_PATH=/home/jezpro/public_html

# CyberPanel API
JH_CYBERPANEL_URL=https://1.2.3.4:8090
JH_CYBERPANEL_USER=admin
JH_CYBERPANEL_PASS=your_password

UPCLOUD_CYBERPANEL_URL=https://5.6.7.8:8090
UPCLOUD_CYBERPANEL_USER=admin
UPCLOUD_CYBERPANEL_PASS=your_password
```

## Step 5: Restart Container

```bash
docker compose restart app
```

## Step 6: Test SSH Connection

```bash
# Test SSH ke UPCLOUD
docker compose exec app php artisan tinker

# Di tinker:
$ssh = new App\Services\SshService('upcloud');
$result = $ssh->exec('uptime');
print_r($result);
```

Expected output:
```php
Array
(
    [success] => 1
    [output] => 12:34:56 up 10 days, 5:43, 1 user, load average: 0.00, 0.01, 0.05
    [exit_code] => 0
)
```

## Step 7: Test CyberPanel API

```bash
docker compose exec app php artisan tinker

# Di tinker:
$cp = new App\Services\CyberPanelService('upcloud');
$result = $cp->verifyLogin();
print_r($result);
```

Expected output:
```php
Array
(
    [status] => 1
    [loginStatus] => 1
)
```

## Troubleshooting

### SSH Connection Failed

1. **Check key permissions**:
   ```bash
   docker compose exec app ls -la /var/www/html/storage/ssh/
   # Should show: -rw------- (600)
   ```

2. **Check SSH host reachable**:
   ```bash
   docker compose exec app ping -c 3 5.6.7.8
   ```

3. **Test SSH manually**:
   ```bash
   ssh -i upcloud_key root@5.6.7.8 uptime
   ```

### CyberPanel API Failed

1. **Check URL accessible**:
   ```bash
   curl -k https://5.6.7.8:8090/api/verifyLogin
   ```

2. **Check credentials**:
   - Login ke CyberPanel web UI: https://5.6.7.8:8090
   - Verify username & password

3. **Check firewall**:
   - Port 8090 harus open dari IP failover panel

### Permission Denied

Jika SSH key permission denied:

```bash
# Fix ownership
docker compose exec app chown www-data:www-data /var/www/html/storage/ssh/*
docker compose exec app chmod 600 /var/www/html/storage/ssh/*
```

## Usage Examples

### Execute Artisan Command via SSH

```php
$ssh = new App\Services\SshService('upcloud');
$result = $ssh->artisan('down --message="Maintenance"');

if ($result['success']) {
    echo "Maintenance mode activated\n";
}
```

### Sync Storage via Rsync

```php
$ssh = new App\Services\SshService('jh');
$result = $ssh->syncStorage(
    '/home/jezpro/public_html/storage/app/public',
    '/home/jezpro/public_html/storage/app/public'
);

if ($result['success']) {
    echo "Storage synced\n";
}
```

### Restart LiteSpeed via CyberPanel

```php
$cp = new App\Services\CyberPanelService('upcloud');
$result = $cp->restartLiteSpeed();

if ($result['status'] === 1) {
    echo "LiteSpeed restarted\n";
}
```

### Get Server Status

```php
$cp = new App\Services\CyberPanelService('upcloud');
$result = $cp->getServerStatus();

print_r($result);
```

## Security Notes

1. **Never commit SSH keys** to git
2. Add to `.gitignore`:
   ```
   storage/ssh/*
   !storage/ssh/.gitkeep
   ```

3. **Use strong passwords** for CyberPanel

4. **Restrict SSH access** by IP if possible

5. **Rotate keys regularly**

## Next Steps

Setelah SSH & CyberPanel setup, kamu bisa:

1. ✅ Execute remote commands tanpa login manual
2. ✅ Sync storage otomatis saat failover
3. ✅ Restart web server via API
4. ✅ Monitor server status real-time
5. ✅ Create backups via API

Lihat `docs/SSH_CYBERPANEL_INTEGRATION.md` untuk detail lengkap.
