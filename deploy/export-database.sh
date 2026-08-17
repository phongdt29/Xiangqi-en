#!/bin/bash

# Export Xiangqi database for backup/deployment
# Usage: ./export-database.sh

DB_USER="${DB_USERNAME:-xiangqi}"
DB_PASSWORD="${DB_PASSWORD:-change-me}"
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_DATABASE:-xiangqi}"
EXPORT_FILE="xiangqi-full-export-$(date +%Y%m%d-%H%M%S).sql"

echo "Exporting database: $DB_NAME"
echo "Export file: $EXPORT_FILE"

# Export using docker-compose if available
if command -v docker-compose &> /dev/null; then
    echo "Using docker-compose..."
    docker-compose exec -T mysql mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$EXPORT_FILE"
else
    echo "Using mysql client directly..."
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$EXPORT_FILE"
fi

if [ -f "$EXPORT_FILE" ]; then
    FILE_SIZE=$(du -h "$EXPORT_FILE" | cut -f1)
    echo "✓ Export successful!"
    echo "File: $EXPORT_FILE ($FILE_SIZE)"
else
    echo "✗ Export failed!"
    exit 1
fi
