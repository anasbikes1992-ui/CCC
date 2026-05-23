# Railway Backend Deployment Fix Guide

## Problem Summary
Backend API at `https://ccc-production.up.railway.app` returns:
```json
{
  "status": "error",
  "code": 404,
  "message": "Application not found",
  "request_id": "..."
}
```

This is a **Railway platform error**, not a Laravel error. It means the service is not properly deployed or accessible.

---

## Step-by-Step Resolution

### Step 1: Check Service Status

```bash
# Install Railway CLI if not already installed
npm install -g @railway/cli

# Login to Railway
railway login

# Link to your project
cd d:\CCC
railway link

# Check service status
railway status

# View recent logs
railway logs --tail 100
```

**Expected Output:**
- Service should show as "ACTIVE" or "RUNNING"
- Recent logs should show Laravel startup messages
- No crash loops or error loops

**If service is CRASHED or REMOVED:**
- Railway may have automatically removed the service due to inactivity
- You may need to recreate the service

---

### Step 2: Verify Environment Variables

```bash
# List all environment variables
railway variables

# Check critical variables
railway variables | Select-String -Pattern "APP_KEY|DB_HOST|DB_PASSWORD"
```

**Required Variables (from RAILWAY_ENV_VARS.md):**
```
APP_NAME=Colombo Cargo Connect
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:ODppQxq1id/cMfPbnRd6A6cNwwyjUfqk7RfPg3q4Ag0=
APP_URL=https://ccc-production.up.railway.app
DB_CONNECTION=pgsql
DB_HOST=<supabase-db-host>
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<supabase-db-password>
CACHE_STORE=redis
REDIS_HOST=<railway-redis-host>
REDIS_PORT=6379
```

**If any variables are missing:**
```bash
railway variables set APP_KEY="base64:ODppQxq1id/cMfPbnRd6A6cNwwyjUfqk7RfPg3q4Ag0="
railway variables set APP_URL="https://ccc-production.up.railway.app"
# ... set all missing variables
```

---

### Step 3: Redeploy from Latest Commit

```bash
# Ensure you're on main branch with latest code
git checkout main
git pull origin main

# Push to Railway (triggers rebuild)
git push railway main

# Monitor deployment logs
railway logs --follow
```

**Watch for these log messages:**
```
==> CCC Backend starting...
==> Waiting for database...
==> Database is ready.
==> Starting server on port 8080...
```

**Common Errors to Look For:**
- `SQLSTATE[08006]` - Database connection failed
- `APP_KEY is missing` - Environment variables not set
- `Class not found` - Composer dependencies not installed
- `Port already in use` - Port conflict (rare on Railway)

---

### Step 4: Test Healthcheck Endpoint

Once deployment completes:

```bash
# PowerShell
$response = Invoke-WebRequest -Uri "https://ccc-production.up.railway.app/up" -Method GET
$response.StatusCode  # Should be 200

# Or using Railway CLI
railway run curl http://localhost:8080/up
```

**Expected Response:**
```
200 OK
```

**If still 404:**
- Check Railway dashboard for service URL
- Verify custom domain settings
- Ensure healthcheck path is `/up` in railway.json

---

### Step 5: Run Database Migrations

```bash
# Run migrations on production database
railway run php artisan migrate --force

# Seed database with test data
railway run php artisan db:seed --force

# Verify database tables exist
railway run php artisan db:show --json
```

**Expected Output:**
- All migration files should show as "Ran"
- Database should have 20+ tables
- Seed data should create 8 users, 5 routes, 25+ pricing entries

---

### Step 6: Test API Endpoints

```bash
# Test auth endpoint
Invoke-RestMethod -Uri "https://ccc-production.up.railway.app/api/v1/auth/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} `
  -Body (@{phone="+94770000003"; password="password123"} | ConvertTo-Json)

# Expected: {"success":true,"data":{"user":{...},"token":"..."}}
```

---

## Alternative: Deploy to New Railway Service

If the service is truly gone, you'll need to create a new one:

### Option A: Via Railway Dashboard

1. Go to https://railway.app/dashboard
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your CCC repository
5. Set root directory to `backend`
6. Add all environment variables from `RAILWAY_ENV_VARS.md`
7. Deploy

### Option B: Via Railway CLI

```bash
cd d:\CCC\backend

# Create new project
railway init

# Add PostgreSQL (optional if using Supabase)
railway add postgresql

# Add Redis
railway add redis

# Set environment variables
railway variables set APP_ENV=production
railway variables set APP_KEY="base64:ODppQxq1id/cMfPbnRd6A6cNwwyjUfqk7RfPg3q4Ag0="
# ... set all variables from RAILWAY_ENV_VARS.md

# Deploy
railway up

# Get deployment URL
railway domain
```

---

## Quick Diagnostic Commands

```bash
# Check if Railway service exists
railway status

# View last 50 log lines
railway logs --tail 50

# Check environment variables
railway variables

# Open Railway dashboard
railway open

# SSH into container (for advanced debugging)
railway shell
```

---

## Expected Behavior After Fix

✅ `GET /up` returns `200 OK`  
✅ `POST /api/v1/auth/login` returns JWT token  
✅ Admin portal dashboard loads without 500 errors  
✅ Frontend apps can communicate with backend  
✅ Database contains seeded test data  

---

## Next Steps After Backend is Fixed

1. **Run E2E Tests:**
   ```bash
   cd tests/e2e
   npm install
   node complete-flow.test.js
   ```

2. **Test OTP Flow:**
   - Create parcel booking
   - Progress to ARRIVED_AT_DESTINATION_HUB
   - Verify OTP generated and sent via WhatsApp
   - Test OTP verification (correct/wrong/expired)
   - Complete delivery with proof

3. **Update E2E Report:**
   - Document all test results
   - Mark issues as resolved
   - Generate final sign-off report

---

## Contact & Support

**Railway Documentation:** https://docs.railway.app  
**Railway Community:** https://discord.gg/railway  
**CCC Project Docs:** See `docs/DEPLOYMENT.md`

---

**Last Updated:** May 23, 2026  
**Status:** BLOCKING ISSUE - API must be fixed before proceeding with E2E tests
