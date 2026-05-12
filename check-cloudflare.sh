#!/bin/bash

# Script untuk cek Cloudflare credentials
# Usage: ./check-cloudflare.sh

# Load dari .env
source .env

echo "========================================"
echo "CLOUDFLARE CREDENTIALS CHECK"
echo "========================================"
echo ""

# Check if credentials exist
if [ -z "$CLOUDFLARE_API_TOKEN" ] || [ -z "$CLOUDFLARE_ZONE_ID" ] || [ -z "$CLOUDFLARE_RECORD_ID" ]; then
    echo "❌ Error: Missing credentials in .env"
    exit 1
fi

echo "📋 Configuration:"
echo "API Token: ${CLOUDFLARE_API_TOKEN:0:20}..."
echo "Zone ID: $CLOUDFLARE_ZONE_ID"
echo "Record ID: $CLOUDFLARE_RECORD_ID"
echo "Domain: $CLOUDFLARE_DOMAIN"
echo ""

# Test Zone ID
echo "🔍 Testing Zone ID..."
ZONE_RESPONSE=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID" \
  -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json")

ZONE_SUCCESS=$(echo $ZONE_RESPONSE | jq -r '.success')

if [ "$ZONE_SUCCESS" == "true" ]; then
    ZONE_NAME=$(echo $ZONE_RESPONSE | jq -r '.result.name')
    echo "✅ Zone ID is valid: $ZONE_NAME"
else
    echo "❌ Zone ID is invalid"
    echo $ZONE_RESPONSE | jq .
    exit 1
fi

echo ""

# Test Record ID
echo "🔍 Testing Record ID..."
RECORD_RESPONSE=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/dns_records/$CLOUDFLARE_RECORD_ID" \
  -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
  -H "Content-Type: application/json")

RECORD_SUCCESS=$(echo $RECORD_RESPONSE | jq -r '.success')

if [ "$RECORD_SUCCESS" == "true" ]; then
    RECORD_NAME=$(echo $RECORD_RESPONSE | jq -r '.result.name')
    RECORD_IP=$(echo $RECORD_RESPONSE | jq -r '.result.content')
    RECORD_TYPE=$(echo $RECORD_RESPONSE | jq -r '.result.type')
    RECORD_PROXIED=$(echo $RECORD_RESPONSE | jq -r '.result.proxied')
    
    echo "✅ Record ID is valid"
    echo ""
    echo "DNS Record Details:"
    echo "  Name: $RECORD_NAME"
    echo "  Type: $RECORD_TYPE"
    echo "  IP: $RECORD_IP"
    echo "  Proxied: $RECORD_PROXIED"
else
    echo "❌ Record ID is invalid"
    echo $RECORD_RESPONSE | jq .
    exit 1
fi

echo ""
echo "========================================"
echo "✅ All credentials are correct!"
echo "========================================"
