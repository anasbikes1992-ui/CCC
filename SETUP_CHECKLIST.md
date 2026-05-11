# Setup Checklist — Colombo Cargo Connect

**Platform:** Windows 10/11  
**Last Updated:** May 1, 2026

---

## 🖥️ System Requirements

- **OS:** Windows 10 (build 2004+) or Windows 11
- **RAM:** 16 GB minimum (8 GB if you close other apps)
- **Disk:** 50 GB free (for dependencies, databases, Docker)
- **Internet:** Stable connection (for package downloads)

---

## 📦 Phase 1: Package Managers & Core Tools

### Step 1.1: Install Chocolatey (if not already installed)

Open **PowerShell as Administrator** and run:

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
```

Verify:
```powershell
choco --version
```

### Step 1.2: Install Core Dependencies

```powershell
choco install -y `
  git `
  vscode `
  nodejs-lts `
  php `
  postgresql-server `
  redis `
  composer
```

Verify each:
```powershell
git --version           # git version 2.x+
node --version          # v20.x+
npm --version           # v10.x+
php --version           # 8.3.x
postgres --version      # 16.x
redis-cli --version     # 7.x+
composer --version      # 2.x
```

---

## 💻 Phase 2: Flutter & Mobile Development

### Step 2.1: Install Flutter SDK

```powershell
choco install -y flutter
```

Verify:
```powershell
flutter --version       # Flutter 3.x
dart --version          # Dart 3.x
```

### Step 2.2: Run Flutter Doctor

```powershell
flutter doctor
```

Expected output (Green checkmarks):
```
[✓] Flutter (Channel stable, 3.x)
[✓] Android toolchain - develop for Android devices
[✓] Visual Studio - develop apps for Windows
[✓] VS Code (version x.x)
[✓] Connected device
```

**If Android toolchain is missing:**
```powershell
choco install -y android-studio
flutter config --android-studio-path "C:\Program Files\Android\Android Studio"
flutter doctor --android-licenses  # Accept all
```

### Step 2.3: Verify Flutter Devices

```powershell
flutter devices
```

You should see Android emulator(s) or connected device(s).

---

## 🐘 Phase 3: PostgreSQL & Database Setup

### Step 3.1: Start PostgreSQL Service

```powershell
# PowerShell as Administrator
Start-Service postgresql-x64-16
```

Verify service is running:
```powershell
Get-Service postgresql-x64-16 | Select-Object Status
```

### Step 3.2: Connect to PostgreSQL

```powershell
psql -U postgres -h 127.0.0.1
```

You'll be prompted for password (set during installation; default is often blank or `postgres`).

Once connected, type:
```sql
SELECT version();
\q
```

### Step 3.3: Create Development Database

```powershell
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE ccc_dev;"
psql -U postgres -h 127.0.0.1 -c "CREATE DATABASE ccc_test;"
psql -U postgres -h 127.0.0.1 -c "CREATE EXTENSION postgis ON DATABASE ccc_dev;"
psql -U postgres -h 127.0.0.1 -c "CREATE EXTENSION postgis ON DATABASE ccc_test;"
```

Verify:
```powershell
psql -U postgres -h 127.0.0.1 -d ccc_dev -c "SELECT PostGIS_version();"
```

---

## 🔴 Phase 4: Redis Setup

### Step 4.1: Start Redis Service

```powershell
# PowerShell as Administrator
Start-Service Redis
```

Verify:
```powershell
redis-cli ping
# Output: PONG
```

### Step 4.2: Test Connection

```powershell
redis-cli
> SET test_key "hello"
> GET test_key
> DEL test_key
> EXIT
```

---

## 📝 Phase 5: Project Repository Setup

### Step 5.1: Initialize Git Repository

```powershell
cd d:\CCC
git init
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### Step 5.2: Create .gitignore

Already done in DEVELOPMENT_TRACKER.md. Verify it exists:
```powershell
cat d:\CCC\.gitignore
```

If missing, create it:
```powershell
@"
node_modules/
vendor/
.env
.env.*
*.log
.DS_Store
build/
dist/
.idea/
.vscode/
*.orig
database.sqlite
.phpcs.cache
"@ | Out-File -FilePath d:\CCC\.gitignore -Encoding UTF8
```

### Step 5.3: Initial Commit

```powershell
cd d:\CCC
git add -A
git commit -m "chore: initial project setup with plan and documentation"
```

---

## 🔧 Phase 6: Laravel Backend Setup

### Step 6.1: Create Backend Directory

```powershell
cd d:\CCC
mkdir backend
cd backend
```

### Step 6.2: Scaffold Laravel 11

```powershell
composer create-project laravel/laravel . "11.*"
```

This will take 2–3 minutes.

### Step 6.3: Install Required Packages

```powershell
composer require laravel/sanctum ramsey/uuid spatie/laravel-permission `
  barryvdh/laravel-dompdf predis/predis propaganistas/laravel-phone `
  morrissimo/laravel-uuid-pivot
```

### Step 6.4: Configure .env for Development

Copy `.env.example` to `.env`:
```powershell
Copy-Item .env.example .env
```

Edit `.env` (use VS Code or notepad):
```powershell
code .env
```

Set these values:

```env
APP_NAME="Colombo Cargo Connect"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ccc_dev
DB_USERNAME=postgres
DB_PASSWORD=<your_postgres_password>

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@cargo.lk
MAIL_FROM_NAME="CCC Support"

# To be filled later:
# SUPABASE_URL=
# SUPABASE_KEY=
# WHATSAPP_PHONE_ID=
# WHATSAPP_BUSINESS_ACCOUNT_ID=
# WHATSAPP_ACCESS_TOKEN=
# WEBXPAY_MERCHANT_ID=
# WEBXPAY_API_KEY=
```

### Step 6.5: Generate App Key

```powershell
php artisan key:generate
```

### Step 6.6: Run Migrations (empty for now)

```powershell
php artisan migrate
```

You should see: "Nothing to migrate."

### Step 6.7: Test Laravel Server

```powershell
php artisan serve
```

You should see:
```
Laravel development server started: http://127.0.0.1:8000
```

Open http://127.0.0.1:8000 in browser — you should see Laravel welcome page.

Stop with `Ctrl+C`.

---

## 🌐 Phase 7: Next.js Web Project Setup

### Step 7.1: Create Web Directories

```powershell
cd d:\CCC
mkdir web-sender web-admin web-hub web-tracking
```

### Step 7.2: Initialize Next.js Projects (Sender)

```powershell
cd d:\CCC\web-sender
npx create-next-app@latest . --typescript --tailwind --app
# Answer prompts:
# - Use TypeScript? Yes
# - Use ESLint? Yes
# - Use Tailwind CSS? Yes
# - Use src/ directory? No
# - Use App Router? Yes
# - Use Turbopack? No
# - Would you like to customize import alias? No
```

Repeat for other web apps:
```powershell
cd d:\CCC\web-admin
npx create-next-app@latest . --typescript --tailwind --app

cd d:\CCC\web-hub
npx create-next-app@latest . --typescript --tailwind --app

cd d:\CCC\web-tracking
npx create-next-app@latest . --typescript --tailwind --app
```

### Step 7.3: Install shadcn/ui in web-sender

```powershell
cd d:\CCC\web-sender
npx shadcn-ui@latest init
# Answer prompts:
# - Would you like to use TypeScript? Yes
# - Which style would you prefer? Default
# - Which color for the UI? Blue (or your choice)
# - Where is your global CSS? app/globals.css
# - Would you like to configure the import alias for components? No
```

Repeat for web-admin, web-hub, web-tracking.

### Step 7.4: Test Next.js Server

```powershell
cd d:\CCC\web-sender
npm run dev
```

You should see:
```
  ▲ Next.js 15.x
  - Local: http://localhost:3000
```

Open http://localhost:3000 in browser — you should see Next.js welcome page.

Stop with `Ctrl+C`.

---

## 📱 Phase 8: Flutter Mobile Projects

### Step 8.1: Create Flutter Projects

```powershell
cd d:\CCC
flutter create --template app --platforms=android,windows,macos mobile-sender
flutter create --template app --platforms=android,windows,macos mobile-driver
```

### Step 8.2: Add Dependencies

```powershell
cd d:\CCC\mobile-sender
flutter pub add `
  provider `
  http `
  shared_preferences `
  mobile_scanner `
  qr_flutter `
  intl `
  get_it
```

```powershell
cd d:\CCC\mobile-driver
flutter pub add `
  provider `
  http `
  shared_preferences `
  mobile_scanner `
  qr_flutter `
  image_picker `
  signature `
  geolocator `
  intl `
  get_it
```

### Step 8.3: Verify Build

```powershell
cd d:\CCC\mobile-sender
flutter pub get
flutter analyze
```

Should complete with no errors.

---

## ✅ Phase 9: Final Verification Checklist

Run this in PowerShell to verify all tools:

```powershell
$tools = @(
    @("git", "git --version"),
    @("Node.js", "node --version"),
    @("npm", "npm --version"),
    @("PHP", "php --version"),
    @("PostgreSQL", "psql --version"),
    @("Redis", "redis-cli --version"),
    @("Composer", "composer --version"),
    @("Flutter", "flutter --version"),
    @("Dart", "dart --version")
)

$tools | ForEach-Object {
    $name = $_[0]
    $cmd = $_[1]
    try {
        $output = & $cmd 2>&1
        Write-Host "✓ $name" -ForegroundColor Green
        Write-Host "  $($output[0])" -ForegroundColor Gray
    } catch {
        Write-Host "✗ $name" -ForegroundColor Red
    }
}
```

Expected output:
```
✓ git
  git version 2.x.x
✓ Node.js
  v20.x.x
✓ npm
  10.x.x
✓ PHP
  PHP 8.3.x
✓ PostgreSQL
  psql (PostgreSQL) 16.x
✓ Redis
  redis-cli 7.x.x
✓ Composer
  Composer 2.x.x
✓ Flutter
  Flutter 3.x.x
✓ Dart
  Dart 3.x.x
```

### Check Database

```powershell
psql -U postgres -h 127.0.0.1 -d ccc_dev -c "SELECT PostGIS_version();"
```

Should output PostGIS version.

### Check Redis

```powershell
redis-cli ping
```

Should output `PONG`.

---

## 🚀 Phase 10: Ready to Start Development

Once all checks pass, you're ready!

1. **Navigate to project root:**
   ```powershell
   cd d:\CCC
   ```

2. **Open in VS Code:**
   ```powershell
   code .
   ```

3. **Start backend (in new terminal):**
   ```powershell
   cd backend
   php artisan serve
   ```

4. **Start a Next.js app (in another terminal):**
   ```powershell
   cd web-sender
   npm run dev
   ```

5. **Start Flutter app (in another terminal):**
   ```powershell
   cd mobile-sender
   flutter run
   ```

---

## 🔗 Useful Commands Reference

| Task | Command |
|------|---------|
| Start PostgreSQL | `Start-Service postgresql-x64-16` |
| Stop PostgreSQL | `Stop-Service postgresql-x64-16` |
| Start Redis | `Start-Service Redis` |
| Stop Redis | `Stop-Service Redis` |
| Test PostgreSQL | `psql -U postgres -h 127.0.0.1 -c "SELECT 1;"` |
| Test Redis | `redis-cli ping` |
| Run Laravel tests | `cd backend && php artisan test` |
| Run Laravel migrations | `cd backend && php artisan migrate` |
| Rollback migrations | `cd backend && php artisan migrate:rollback` |
| Seed database | `cd backend && php artisan db:seed` |
| Start Laravel dev | `cd backend && php artisan serve` |
| Start Next.js dev | `cd web-* && npm run dev` |
| Build Next.js | `cd web-* && npm run build` |
| Start Flutter app | `cd mobile-* && flutter run` |
| Build Flutter APK | `cd mobile-* && flutter build apk` |

---

## 🆘 Troubleshooting

### Laravel won't connect to PostgreSQL

**Error:** `SQLSTATE[HY000] [2002] No such file or directory`

**Solution:**
1. Verify PostgreSQL is running: `Get-Service postgresql-x64-16`
2. Check DB_HOST is `127.0.0.1` (not `localhost`)
3. Check DB_PASSWORD matches your PostgreSQL password

### Redis connection failed

**Error:** `Error: ERR Connection refused`

**Solution:**
1. Verify Redis is running: `Get-Service Redis`
2. Test with: `redis-cli ping` (should return `PONG`)
3. Check REDIS_HOST is `127.0.0.1` and REDIS_PORT is `6379`

### Flutter doctor issues

**Error:** `The Android SDK is not available in the specified folder`

**Solution:**
```powershell
flutter config --android-studio-path "C:\Program Files\Android\Android Studio"
flutter doctor --android-licenses  # Accept all licenses
flutter doctor
```

### Node.js/npm not found

**Solution:**
```powershell
choco uninstall nodejs-lts -y
choco install nodejs-lts -y
# Close and reopen PowerShell
node --version
```

### Composer not found

**Solution:**
```powershell
choco uninstall composer -y
choco install composer -y
composer --version
```

---

## 📞 Support

If stuck:
1. Check the troubleshooting section above
2. Google the error message + "Windows"
3. Check official docs:
   - Laravel: https://laravel.com/docs/11.x
   - Next.js: https://nextjs.org/docs
   - Flutter: https://flutter.dev/docs
   - PostgreSQL: https://www.postgresql.org/docs/16/
4. Ask in Claude Code session

---

**Status:** Ready for Development  
**Last Verified:** May 1, 2026
