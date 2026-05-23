# ============================================================
# CCC — Commit all fixes and push to GitHub
# Run this from D:\CCC in PowerShell:
#   cd D:\CCC
#   .\deploy-push.ps1
# ============================================================

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

Write-Host "=== CCC Deploy Push ===" -ForegroundColor Cyan

# 1. Remove stale git lock if present
$lockFile = ".git\index.lock"
if (Test-Path $lockFile) {
    Write-Host "Removing stale git lock file..." -ForegroundColor Yellow
    Remove-Item $lockFile -Force
}

# 2. Stage only the files changed by the code audit / fixes
$filesToAdd = @(
    # web-hub fixes
    "web-hub/lib/api.ts",
    "web-hub/app/(hub)/scan/page.tsx",
    "web-hub/lib/utils.ts",
    "web-hub/next.config.ts",
    "web-hub/vercel.json",
    "web-hub/.env.example",
    # web-sender fixes
    "web-sender/.env.example",
    "web-sender/app/layout.tsx",
    "web-sender/app/dashboard/page.tsx",
    "web-sender/next.config.ts",
    "web-sender/vercel.json",
    # web-admin fixes
    "web-admin/middleware.ts",
    "web-admin/lib/api.ts",
    "web-admin/app/login/page.tsx",
    "web-admin/next.config.ts",
    "web-admin/vercel.json",
    "web-admin/.env.example",
    # web-tracking fixes
    "web-tracking/.env.example",
    "web-tracking/next.config.ts",
    "web-tracking/vercel.json",
    # backend fixes
    "backend/Dockerfile",
    "backend/start-railway.sh",
    "backend/app/Http/Requests/Auth/LoginRequest.php",
    # docs
    "docs/DEPLOYMENT.md"
)

Write-Host "Staging changed files..." -ForegroundColor Yellow
foreach ($f in $filesToAdd) {
    if (Test-Path $f) {
        git add $f
        Write-Host "  + $f" -ForegroundColor Green
    } else {
        Write-Host "  ? $f (not found, skipping)" -ForegroundColor DarkYellow
    }
}

# 3. Commit
$commitMsg = @"
fix: audit fixes — scan enum, auth, env vars, middleware, dockerfile

- web-hub: fix ARRIVED_AT_DESTINATION_HUB scan event name (was RECEIVED_AT_DESTINATION_HUB)
- web-hub: rename NEXT_PUBLIC_API_URL → NEXT_PUBLIC_API_BASE_URL
- web-admin: restore auth middleware (was disabled as .bak — all routes were unprotected)
- web-admin: remove dead loginWithEmail(), fix env var name
- web-admin: add vercel.json and correct next.config.ts
- web-sender: fix broken /track/[id] links → external tracking URL
- web-sender: use NEXT_PUBLIC_TRACKING_URL env var for tracking app URL
- web-sender/tracking: fix malformed .env.example (missing newline)
- all Next.js apps: add vercel.json, add eslint.ignoreDuringBuilds to next.config.ts
- backend/Dockerfile: add missing pcntl + zip extensions, fix storage perms
- backend/start-railway.sh: add DB readiness wait loop, production caching
- backend/LoginRequest: add attributes() so error says 'phone or email'
- docs/DEPLOYMENT.md: fix incorrect env var names for web-admin and web-hub
"@

git commit -m $commitMsg
Write-Host "Committed." -ForegroundColor Green

# 4. Push
Write-Host "Pushing to origin/master..." -ForegroundColor Yellow
git push origin master
Write-Host "Push complete! Vercel will auto-deploy all 4 apps." -ForegroundColor Cyan

Write-Host ""
Write-Host "=== NEXT STEP: Set Vercel Environment Variables ===" -ForegroundColor Cyan
Write-Host "Go to https://vercel.com/anas-projects-7ceb7b61 and set these for each project:"
Write-Host ""
Write-Host "ccc-sender (web-sender):" -ForegroundColor Yellow
Write-Host "  NEXT_PUBLIC_API_BASE_URL = https://<your-railway-url>/api/v1"
Write-Host "  NEXT_PUBLIC_TRACKING_URL = https://ccc-tracking.vercel.app"
Write-Host ""
Write-Host "ccc-tracking (web-tracking):" -ForegroundColor Yellow
Write-Host "  NEXT_PUBLIC_API_BASE_URL = https://<your-railway-url>/api/v1"
Write-Host ""
Write-Host "ccc-admin (web-admin):" -ForegroundColor Yellow
Write-Host "  NEXT_PUBLIC_API_BASE_URL = https://<your-railway-url>/api/v1"
Write-Host ""
Write-Host "ccc-hub (web-hub):" -ForegroundColor Yellow
Write-Host "  NEXT_PUBLIC_API_BASE_URL = https://<your-railway-url>/api/v1"
Write-Host ""
Write-Host "After setting env vars, re-deploy each project from the Vercel dashboard."
