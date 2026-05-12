#!/bin/bash

# Database Backup Script for Failover Panel
# Usage: ./backup.sh

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$(dirname "$0")"
BACKUP_FILE="failover_panel_${TIMESTAMP}.sql"
DB_NAME="failover_panel"
DB_USER="root"
DB_PASS="root_secret"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  Database Backup Script${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# Check if running from correct directory
if [ ! -f "../docker-compose.yml" ]; then
    echo -e "${RED}Error: docker-compose.yml not found!${NC}"
    echo "Please run this script from backup_db directory"
    exit 1
fi

# Create backup
echo -e "${YELLOW}Creating backup...${NC}"
cd ..
docker compose exec -T mysql mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} > backup_db/${BACKUP_FILE} 2>/dev/null

# Check if backup successful
if [ $? -eq 0 ] && [ -s "backup_db/${BACKUP_FILE}" ]; then
    FILESIZE=$(du -h "backup_db/${BACKUP_FILE}" | cut -f1)
    echo -e "${GREEN}✓ Backup successful!${NC}"
    echo -e "  File: ${BACKUP_FILE}"
    echo -e "  Size: ${FILESIZE}"
    echo -e "  Location: backup_db/${BACKUP_FILE}"
else
    echo -e "${RED}✗ Backup failed!${NC}"
    rm -f "backup_db/${BACKUP_FILE}"
    exit 1
fi

# Cleanup old backups (keep last 7 days)
echo ""
echo -e "${YELLOW}Cleaning up old backups (keeping last 7 days)...${NC}"
find backup_db/ -name "failover_panel_*.sql" -type f -mtime +7 -delete
REMAINING=$(ls -1 backup_db/failover_panel_*.sql 2>/dev/null | wc -l)
echo -e "${GREEN}✓ Cleanup complete. ${REMAINING} backup(s) remaining.${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Backup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
