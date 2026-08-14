# CORS Configuration Fix

## Issue
Production site was getting CORS errors:
```
Access to fetch at 'https://api.chinesechess.online/api/xiangqi/new' from origin 'https://chinesechess.online' 
has been blocked by CORS policy
```

## Root Cause
Backend `.env.production` had incorrect configuration:
- `APP_ENV=local` instead of `production`
- `APP_URL=http://localhost:8000` instead of `https://api.chinesechess.online`
- Missing CORS_ALLOWED_ORIGINS for production domains
- Duplicate CORS_ALLOWED_ORIGINS entries

## Solution Applied

### Backend Configuration (.env.production)
```env
# Production Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.chinesechess.online

# CORS Configuration - Allow frontend origins
CORS_ALLOWED_ORIGINS=https://chinesechess.online,https://www.chinesechess.online
```

### Key Points
1. ✅ APP_ENV must be `production` (not local)
2. ✅ APP_URL must be `https://api.chinesechess.online`
3. ✅ CORS_ALLOWED_ORIGINS must include both `https://chinesechess.online` AND `https://www.chinesechess.online`
4. ✅ Remove any duplicate CORS_ALLOWED_ORIGINS entries

### Files Updated
- `backend/.env.production.example` - Fixed template for future deployments
- `backend/.env.example` - Updated APP_URL to localhost:8000
- Deployment packages rebuilt with fixes

## Testing

After deploying, verify CORS is working:

```bash
# Test from production
curl -X POST https://api.chinesechess.online/api/xiangqi/new \
  -H "Content-Type: application/json" \
  -H "Origin: https://chinesechess.online" \
  -d '{}'
```

Expected: Should return valid game board (not CORS error)

## Production Checklist

When deploying to production:

- [ ] Copy `.env.production.example` to `.env.production`
- [ ] Fill in all required secrets (APP_KEY, DB credentials, etc.)
- [ ] Verify `APP_ENV=production`
- [ ] Verify `APP_DEBUG=false`
- [ ] Verify `APP_URL=https://api.chinesechess.online`
- [ ] Verify `CORS_ALLOWED_ORIGINS=https://chinesechess.online,https://www.chinesechess.online`
- [ ] Run `docker-compose up -d --build`
- [ ] Run migrations: `docker-compose exec backend php artisan migrate --force`
- [ ] Clear cache: `docker-compose exec backend php artisan config:cache`
- [ ] Test API from frontend

## Future Deployments

Future deployments should use the updated `.env.production.example` template which now has:
- ✅ Correct APP_ENV=production
- ✅ Correct APP_DEBUG=false
- ✅ Correct production URLs
- ✅ Proper CORS configuration
- ✅ Clear comments explaining configuration

No more CORS errors on production! 🎉
