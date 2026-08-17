# Database Export & Import Guide

## Export Database from Existing Server

### Using docker-compose (Recommended)

```bash
# Export with all data
docker-compose exec -T mysql mysqldump -u chin_chinesechess -p"J&!HcGuPS53GMW5f" chin_chinesechess > xiangqi-full-export.sql

# Or using the export script
chmod +x export-database.sh
./export-database.sh
```

### Manual Export (Direct MySQL)

```bash
mysqldump -h localhost -u chin_chinesechess -p"password" chin_chinesechess > xiangqi-full-export.sql
```

## Import Database on New Server

### Option 1: Using docker-compose

```bash
# Copy export file to new server
scp xiangqi-full-export.sql user@new-server:/var/www/xiangqi-en/

# SSH into new server
ssh user@new-server

# Import the database
docker-compose exec -T mysql mysql -u chin_chinesechess -p"password" chin_chinesechess < xiangqi-full-export.sql

# Verify import
docker-compose exec backend php artisan tinker
>>> User::count()
=> 10
>>> Puzzle::count()
=> 4
```

### Option 2: Docker Compose Up with Custom Init

```bash
# Place xiangqi-full-export.sql in a volumes mount
# Update docker-compose.yml to mount the SQL file:

services:
  mysql:
    volumes:
      - mysql_data:/var/lib/mysql
      - ./xiangqi-full-export.sql:/docker-entrypoint-initdb.d/01-xiangqi.sql
```

## Database Files

| File | Purpose | Use Case |
|------|---------|----------|
| `xiangqi-production-import.sql` | Schema only (no data) | Fresh production install |
| `xiangqi-full-export.sql` | Schema + all data | Copy existing database to new server |
| `export-database.sh` | Export script | Automated backups |

## Database Backup Strategy

### Automated Backups (Recommended)

```bash
#!/bin/bash
# Create cron job for daily backups

BACKUP_DIR="/backups/xiangqi"
mkdir -p $BACKUP_DIR

# Daily backup at 2 AM
0 2 * * * cd /var/www/xiangqi-en && \
  docker-compose exec -T mysql mysqldump -u chin_chinesechess -p"password" chin_chinesechess \
  > $BACKUP_DIR/xiangqi-$(date +\%Y\%m\%d).sql
```

### Manual Backup

```bash
# Quick backup
docker-compose exec -T mysql mysqldump -u chin_chinesechess -p"password" chin_chinesechess > backup-$(date +%Y%m%d).sql

# Backup with compression
docker-compose exec -T mysql mysqldump -u chin_chinesechess -p"password" chin_chinesechess | gzip > backup-$(date +%Y%m%d).sql.gz
```

## Restore from Backup

```bash
# Restore from plain SQL
docker-compose exec -T mysql mysql -u chin_chinesechess -p"password" chin_chinesechess < backup-20260814.sql

# Restore from compressed backup
gunzip < backup-20260814.sql.gz | docker-compose exec -T mysql mysql -u chin_chinesechess -p"password" chin_chinesechess
```

## Troubleshooting

### Import Takes Too Long

If the import is slow, you can disable keys temporarily:

```bash
# Edit the SQL file to add:
SET FOREIGN_KEY_CHECKS=0;
-- ... sql content ...
SET FOREIGN_KEY_CHECKS=1;
```

### Permission Denied on SQL File

```bash
# Make sure the MySQL user can read the file
chmod 644 xiangqi-full-export.sql
```

### Database Already Exists

```bash
# Drop existing database and import fresh
docker-compose exec -T mysql mysql -u root -p"ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS chin_chinesechess;"
docker-compose exec -T mysql mysql -u chin_chinesechess -p"password" chin_chinesechess < xiangqi-full-export.sql
```

## Verification

After import, verify the data:

```bash
# Check row counts
docker-compose exec backend php artisan tinker

# In tinker console:
>>> App\Models\User::count()
=> 10  # Should have 10 seeded users

>>> App\Models\Puzzle::count()
=> 4   # Should have 4 puzzles

>>> DB::table('personal_access_tokens')->count()
=> 0   # Tokens should be empty (will be recreated on login)
```

## Database Structure

### Tables Included

- `users` - Player accounts with ratings, wins/losses, points
- `personal_access_tokens` - API authentication tokens
- `rooms` - Game rooms and matches
- `puzzles` - Chess puzzle problems
- `co_up_games` - Cooperative game data
- `point_transactions` - Points transaction history
- `payout_requests` - Withdrawal requests
- `cache` - Laravel cache table
- `jobs` - Queue jobs
- `migrations` - Migration history

### Data Included

- 10 sample players with ratings (Dragon Master: 2850, Phoenix Champion: 2720, etc.)
- 4 practice puzzles (Easy and Medium difficulty)
- Empty games/rooms (will be created during gameplay)
- No token data (recreated on login)

---

**Always backup before making changes!** 🔒
