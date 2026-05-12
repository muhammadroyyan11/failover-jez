#!/bin/bash

# Database Restore Script for Failover Panel
# Usage: ./restore.sh [backup_file.sql]

# Configuration
DB_NAME="failover_panel"
DB_USER="root"
DB_PASS="root_secret"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  Database Restore Script${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# Check if backup file provided
if [ -z "$1" ]; then
    echo -e "${YELLOW}Available backups:${NC}"
    echo ""
    ls -lh failover_panel_*.sql 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'
    echo ""
    echo -e "${RED}Usage: ./restore.sh <backup_file.sql>${NC}"
    echo -e "Example: ./restore.sh failover_panel_20260512_164826.sql"
    exit 1
fi

BACKUP_FILE="$1"

# Check if file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}Error: Backup file not found: ${BACKUP_FILE}${NC}"
    exit 1
fi

# Confirm restore
echo -e "${YELLOW}⚠️  WARNING: This will OVERWRITE the current database!${NC}"
echo -e "Backup file: ${BACKUP_FILE}"
echo -e "Database: ${DB_NAME}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Restore cancelled.${NC}"
    exit 0
fi

# Restore database
echo ""
echo -e "${YELLOW}Restoring database...${NC}"
cd ..
docker compose exec -T mysql mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < backup_db/${BACKUP_FILE} 2>/dev/null

# Check if restore successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Restore successful!${NC}"
    echo -e "  Database: ${DB_NAME}"
    echo -e "  From: ${BACKUP_FILE}"
else
    echo -e "${RED}✗ Restore failed!${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Restore Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Don't forget to clear cache:${NC}"
echo -e "  docker compose exec app php artisan optimize:clear"
