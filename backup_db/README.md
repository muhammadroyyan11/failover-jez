# Database Backup & Restore

Folder ini berisi backup database dan script untuk backup/restore.

## 📁 Struktur

```
backup_db/
├── backup.sh                          # Script untuk backup database
├── restore.sh                         # Script untuk restore database
├── README.md                          # Dokumentasi ini
└── failover_panel_YYYYMMDD_HHMMSS.sql # File backup (auto-generated)
```

## 🔄 Backup Database

### Manual Backup

```bash
cd backup_db
./backup.sh
```

Script akan:
- ✅ Membuat backup dengan timestamp
- ✅ Menampilkan ukuran file
- ✅ Cleanup backup lama (>7 hari)

### Backup via Docker Command

```bash
docker compose exec mysql mysqldump -u root -proot_secret failover_panel > backup_db/manual_backup.sql
```

## 📥 Restore Database

### Lihat Daftar Backup

```bash
cd backup_db
./restore.sh
```

### Restore dari Backup

```bash
cd backup_db
./restore.sh failover_panel_20260512_164826.sql
```

⚠️ **WARNING**: Restore akan menimpa database yang ada!

### Restore via Docker Command

```bash
docker compose exec -T mysql mysql -u root -proot_secret failover_panel < backup_db/failover_panel_20260512_164826.sql
```

## ⏰ Automated Backup (Cron)

Untuk backup otomatis setiap hari jam 2 pagi:

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini:
0 2 * * * cd /path/to/laravel-failover/laravel-app/backup_db && ./backup.sh >> backup.log 2>&1
```

## 📊 Monitoring Backup

### Cek Ukuran Backup

```bash
ls -lh backup_db/*.sql
```

### Cek Backup Terbaru

```bash
ls -lt backup_db/*.sql | head -5
```

### Hitung Jumlah Backup

```bash
ls -1 backup_db/*.sql | wc -l
```

## 🗑️ Cleanup Manual

### Hapus Backup Lebih dari 7 Hari

```bash
find backup_db/ -name "failover_panel_*.sql" -type f -mtime +7 -delete
```

### Hapus Semua Backup Kecuali 5 Terbaru

```bash
cd backup_db
ls -t failover_panel_*.sql | tail -n +6 | xargs rm -f
```

## 🔐 Security Notes

- ⚠️ File backup berisi data sensitif
- 🔒 Jangan commit file `.sql` ke git
- 📦 Compress backup untuk storage: `gzip failover_panel_*.sql`
- 🌐 Upload ke cloud storage untuk disaster recovery

## 📝 Backup Information

### Database Details
- **Database Name**: `failover_panel`
- **Tables**: 
  - users
  - failover_servers
  - failover_logs
  - failover_settings
  - server_metrics
  - migrations

### Backup Retention
- **Default**: 7 hari
- **Modify**: Edit `backup.sh` line `find ... -mtime +7`

## 🆘 Troubleshooting

### Error: Access Denied

```bash
# Check MySQL credentials in docker-compose.yml
grep MYSQL_ROOT_PASSWORD ../docker-compose.yml
```

### Error: File Not Found

```bash
# Make sure you're in backup_db directory
pwd
# Should show: /path/to/laravel-failover/laravel-app/backup_db
```

### Backup File is Empty

```bash
# Check MySQL container is running
docker compose ps mysql

# Check MySQL logs
docker compose logs mysql
```

## 📞 Support

Jika ada masalah dengan backup/restore, hubungi tim DevOps.
