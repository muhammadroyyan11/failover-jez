#!/bin/bash

# Script untuk mendapatkan Cloudflare Zone ID dan Record ID
# Usage: ./get-cloudflare-ids.sh YOUR_API_TOKEN jezpro.id

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Usage: $0 <CLOUDFLARE_API_TOKEN> <DOMAIN>"
    echo "Example: $0 y_12345abcdef jezpro.id"
    exit 1
fi

API_TOKEN="$1"
DOMAIN="$2"

echo "=========================================="
echo "Cloudflare Configuration Finder"
echo "=========================================="
echo ""

# Get Zone ID
echo "🔍 Mencari Zone ID untuk domain: $DOMAIN"
ZONE_RESPONSE=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=$DOMAIN" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json")

ZONE_ID=$(echo $ZONE_RESPONSE | jq -r '.result[0].id')

if [ "$ZONE_ID" == "null" ] || [ -z "$ZONE_ID" ]; then
    echo "❌ Error: Tidak dapat menemukan Zone ID"
    echo "Response: $ZONE_RESPONSE"
    exit 1
fi

echo "✅ Zone ID: $ZONE_ID"
echo ""

# Get DNS Record ID
echo "🔍 Mencari DNS Record ID untuk A record: $DOMAIN"
RECORD_RESPONSE=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?type=A&name=$DOMAIN" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json")

RECORD_ID=$(echo $RECORD_RESPONSE | jq -r '.result[0].id')
RECORD_IP=$(echo $RECORD_RESPONSE | jq -r '.result[0].content')

if [ "$RECORD_ID" == "null" ] || [ -z "$RECORD_ID" ]; then
    echo "❌ Error: Tidak dapat menemukan Record ID"
    echo "Response: $RECORD_RESPONSE"
    exit 1
fi

echo "✅ Record ID: $RECORD_ID"
echo "✅ Current IP: $RECORD_IP"
echo ""

# Output untuk .env
echo "=========================================="
echo "Copy ke .env file:"
echo "=========================================="
echo ""
echo "CLOUDFLARE_API_TOKEN=$API_TOKEN"
echo "CLOUDFLARE_ZONE_ID=$ZONE_ID"
echo "CLOUDFLARE_RECORD_ID=$RECORD_ID"
echo "CLOUDFLARE_DOMAIN=$DOMAIN"
echo ""
echo "=========================================="
echo "✅ Selesai!"
echo "=========================================="
