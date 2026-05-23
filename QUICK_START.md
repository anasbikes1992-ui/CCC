# Quick Start Guide — Advanced Development

**Purpose:** Fast-track guide to start working on advanced development phases.

**Last Updated:** May 23, 2026

---

## 📍 Where Are We?

✅ **Core Development Complete** (Phases 0-6)
- Backend API functional
- Web apps deployed (staging)
- Mobile driver app built
- All major features implemented

🔄 **Now Starting: Advanced Development** (Phases A-H)
- Production infrastructure
- Testing & quality assurance
- External service integrations
- Security hardening
- Performance optimization
- Monitoring setup
- Documentation
- Go-live preparation

---

## 🚀 Quick Start: Choose Your Phase

### Phase A: Production Infrastructure Setup

**Best for:** DevOps, setting up production environments

**Start here if:** You want to deploy to production

**Prerequisites:**
- Supabase account (for production database)
- Railway account (for backend hosting)
- Vercel account (for web apps)
- Domain names purchased

**Quick Start:**
```powershell
# 1. Read the guides
code D:\CCC\docs\EXTERNAL_SERVICES_SETUP.md
code D:\CCC\docs\RUNBOOK.md

# 2. Set up Supabase production
# Follow: EXTERNAL_SERVICES_SETUP.md → Section 1

# 3. Set up Railway production
# Follow: EXTERNAL_SERVICES_SETUP.md → Section 2

# 4. Deploy web apps to Vercel
cd D:\CCC\web-sender
vercel --prod
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase A
- [EXTERNAL_SERVICES_SETUP.md](../docs/EXTERNAL_SERVICES_SETUP.md)
- [RUNBOOK.md](../docs/RUNBOOK.md) → Deployment Procedures

---

### Phase B: Testing & Quality Assurance

**Best for:** QA engineers, developers improving test coverage

**Start here if:** You want to measure and improve test coverage

**Prerequisites:**
- PCOV extension installed (or Xdebug)
- Backend tests passing

**Quick Start:**
```powershell
# 1. Install PCOV for coverage
cd D:\CCC\backend
composer require --dev pcov/clobber
vendor\bin\pcov clobber

# 2. Run tests with coverage
vendor\bin\pest --coverage --min=70

# 3. Generate HTML coverage report
vendor\bin\pest --coverage-html coverage-report

# 4. Open report
start coverage-report\index.html

# 5. Write tests for uncovered code
# See: backend/tests/ directory
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase B
- [backend/tests/](../backend/tests/)
- [backend/phpunit.xml](../backend/phpunit.xml)

---

### Phase C: Missing Feature Implementation

**Best for:** Backend developers integrating external services

**Start here if:** You want to complete WhatsApp, SMS, or payment integrations

**Prerequisites:**
- WhatsApp Business Account created
- Notify.lk account created
- WebxPay merchant account approved
- Firebase project created

**Quick Start:**
```powershell
# 1. Read integration guides
code D:\CCC\docs\EXTERNAL_SERVICES_SETUP.md

# 2. Set up WhatsApp Cloud API
# Follow: EXTERNAL_SERVICES_SETUP.md → Section 4

# 3. Configure backend
cd D:\CCC\backend
railway variables set WHATSAPP_PHONE_NUMBER_ID=...
railway variables set WHATSAPP_ACCESS_TOKEN=...

# 4. Test notification
php artisan tinker
>>> app(\App\Services\WhatsAppService::class)->sendBookingConfirmation($parcel);

# 5. Repeat for SMS, Payment, FCM
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase C
- [EXTERNAL_SERVICES_SETUP.md](../docs/EXTERNAL_SERVICES_SETUP.md)
- [backend/app/Services/WhatsAppService.php](../backend/app/Services/WhatsAppService.php)

---

### Phase D: Security Hardening

**Best for:** Security engineers, senior developers

**Start here if:** You want to audit and improve security

**Prerequisites:**
- OWASP ZAP installed (or similar security scanner)
- Understanding of common vulnerabilities (OWASP Top 10)

**Quick Start:**
```powershell
# 1. Run automated security scan
# Install OWASP ZAP: https://www.zaproxy.org/download/
# Run scan against staging: https://staging-api.colombocargo.lk

# 2. Manual security review
code D:\CCC\advancedev.md  # Phase D checklist

# 3. Review authorization checks
cd D:\CCC\backend
# Search for controllers without auth middleware:
Select-String -Path "app/Http/Controllers/*.php" -Pattern "function" | 
  Where-Object { $_.Line -notmatch "middleware.*auth" }

# 4. Review encryption
# Check: app/Models/User.php → NIC field uses Crypt::encryptString()

# 5. Fix vulnerabilities
# Follow advancedev.md → Phase D for checklist
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase D
- [security.md](../docs/SECURITY.md) (if exists)
- OWASP Top 10: https://owasp.org/Top10/

---

### Phase E: Performance Optimization

**Best for:** Performance engineers, backend developers

**Start here if:** You want to optimize speed and scalability

**Prerequisites:**
- Load testing tool (k6, Artillery, or Apache JMeter)
- Access to production database

**Quick Start:**
```powershell
# 1. Run baseline load test
# Install k6: https://k6.io/docs/get-started/installation/
k6 run loadtest.js  # Create this file from advancedev.md examples

# 2. Identify slow queries
# Railway logs:
railway logs | Select-String "slow query"

# 3. Add missing indexes
# See advancedev.md → Phase E.1 for index SQL

# 4. Implement caching
cd D:\CCC\backend\app\Services
# Update services to use Cache::remember()

# 5. Re-test and measure improvement
k6 run loadtest.js
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase E
- [DB_SCHEMA.md](../docs/DB_SCHEMA.md)
- k6 docs: https://k6.io/docs/

---

### Phase F: Monitoring & Operations

**Best for:** DevOps, SREs, operations team

**Start here if:** You want to set up monitoring and alerting

**Prerequisites:**
- Sentry account created
- Better Stack account created
- Access to production environments

**Quick Start:**
```powershell
# 1. Set up Sentry error tracking
cd D:\CCC\backend
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN

# Add to Railway:
railway variables set SENTRY_LARAVEL_DSN=https://...@sentry.io/...

# 2. Set up Better Stack uptime monitoring
# Visit: https://betterstack.com/uptime
# Add monitors for all endpoints (see advancedev.md → Phase F.2)

# 3. Set up business metrics dashboard
# Create Metabase/Grafana dashboard
# Use SQL queries from advancedev.md → Phase F.4

# 4. Test alerts
# Trigger test error:
curl https://api.colombocargo.lk/api/test-error

# Check Sentry for captured error
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase F
- [RUNBOOK.md](../docs/RUNBOOK.md) → Monitoring section
- Sentry: https://sentry.io
- Better Stack: https://betterstack.com

---

### Phase G: Documentation & Polish

**Best for:** Technical writers, developers documenting features

**Start here if:** You want to create training materials or improve UX

**Prerequisites:**
- Access to all deployed apps
- Screen recording software (for training videos)

**Quick Start:**
```powershell
# 1. Generate API documentation
cd D:\CCC\backend
composer require darkaonline/l5-swagger
php artisan l5-swagger:generate

# Open: http://localhost:8000/api/documentation

# 2. Create user training videos
# Record screencasts for:
# - How to book a parcel (sender portal)
# - How to scan parcels (driver app)
# - How to manage trips (admin console)

# 3. Write FAQ documents
code D:\CCC\docs\FAQ.md

# 4. UX polish
cd D:\CCC\web-sender
# Review all user flows
# Add loading states
# Add error states
# Add empty states
# Improve mobile responsiveness
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase G
- [API_SPEC.md](../docs/API_SPEC.md)
- [RUNBOOK.md](../docs/RUNBOOK.md)

---

### Phase H: Go-Live Preparation

**Best for:** Project managers, QA leads

**Start here if:** You're preparing for production launch

**Prerequisites:**
- All previous phases (A-G) substantially complete
- Business requirements met (legal, financial, operations)

**Quick Start:**
```powershell
# 1. Open go-live checklist
code D:\CCC\docs\GO_LIVE_CHECKLIST.md

# 2. Run through checklist systematically
# Mark items complete as you verify them

# 3. Run final E2E tests
# Test all 4 critical user journeys (see checklist)

# 4. Perform final security scan
# Run OWASP ZAP or similar

# 5. Schedule go-live meeting
# Get sign-off from all stakeholders

# 6. Prepare rollback plan
# Document in GO_LIVE_CHECKLIST.md

# 7. Launch!
# Follow Launch Day procedures in checklist
```

**Reference:**
- [advancedev.md](../advancedev.md) → Phase H
- [GO_LIVE_CHECKLIST.md](../docs/GO_LIVE_CHECKLIST.md)
- [RUNBOOK.md](../docs/RUNBOOK.md) → Emergency Procedures

---

## 📊 Progress Tracking

Use this table to track your progress:

| Phase | Status | Started | Completed | Notes |
|-------|--------|---------|-----------|-------|
| A: Infrastructure | ⏳ Not Started | — | — | |
| B: Testing | ⏳ Not Started | — | — | |
| C: Features | ⏳ Not Started | — | — | |
| D: Security | ⏳ Not Started | — | — | |
| E: Performance | ⏳ Not Started | — | — | |
| F: Monitoring | ⏳ Not Started | — | — | |
| G: Documentation | 🔄 In Progress | May 23, 2026 | — | Runbook, setup guide, checklist created |
| H: Go-Live | ⏳ Not Started | — | — | |

**Legend:**
- ⏳ Not Started
- 🔄 In Progress
- ✅ Complete
- ⚠️ Blocked

---

## 🎯 Recommended Order

**For small team (1-3 people):**
1. Phase A → Phase C → Phase F → Phase H
2. Run Phases B, D, E, G in parallel as capacity allows

**For larger team (4+ people):**
- **Team 1 (Infrastructure):** Phase A → Phase F
- **Team 2 (Backend):** Phase B → Phase C → Phase E
- **Team 3 (Security/Docs):** Phase D → Phase G
- **Everyone:** Phase H (go-live)

**Critical Path:**
```
Phase A (Infrastructure) 
    ↓
Phase C (External Services)
    ↓
Phase F (Monitoring)
    ↓
Phase H (Go-Live)
```

Everything else can run in parallel.

---

## 🛠️ Common Commands

### Check Current Status
```powershell
# Backend status
cd D:\CCC\backend
railway status
railway logs --tail 50

# Web apps status
vercel ls

# Run local tests
vendor\bin\pest

# Check test coverage
vendor\bin\pest --coverage
```

### Deploy to Production
```powershell
# Backend
cd D:\CCC\backend
git push origin main  # Auto-deploys via Railway

# Web apps
cd D:\CCC\web-sender
vercel --prod

cd D:\CCC\web-admin
vercel --prod

cd D:\CCC\web-hub
vercel --prod

cd D:\CCC\web-tracking
vercel --prod
```

### Rollback
```powershell
# Backend
railway rollback

# Web apps
# Go to Vercel UI → Select previous deployment → Promote to Production
```

---

## 📞 Need Help?

**Stuck on a phase?**
1. Read [advancedev.md](../advancedev.md) for detailed instructions
2. Check [RUNBOOK.md](../docs/RUNBOOK.md) for troubleshooting
3. Review [EXTERNAL_SERVICES_SETUP.md](../docs/EXTERNAL_SERVICES_SETUP.md) for service setup

**Common Issues:**
- **PCOV installation fails:** Use Xdebug instead, or skip coverage measurement temporarily
- **Railway SSL errors:** SSL certificates are auto-provisioned, wait 5-10 minutes
- **Vercel build fails:** Check build logs, ensure environment variables are set
- **WhatsApp not sending:** Verify templates are approved in Meta Business Manager
- **Payment failing:** Test in sandbox first, check WebxPay credentials

**Still stuck?**
- Check project docs in `docs/` directory
- Review code comments in relevant service files
- Consult external service documentation (links in EXTERNAL_SERVICES_SETUP.md)

---

## 🎓 Learning Resources

**Laravel:**
- Official Docs: https://laravel.com/docs/11.x
- Laracasts: https://laracasts.com

**Next.js:**
- Official Docs: https://nextjs.org/docs
- App Router Guide: https://nextjs.org/docs/app

**Flutter:**
- Official Docs: https://flutter.dev/docs
- Cookbook: https://docs.flutter.dev/cookbook

**DevOps:**
- Railway Docs: https://docs.railway.app
- Vercel Docs: https://vercel.com/docs
- Supabase Docs: https://supabase.com/docs

**Testing:**
- Pest Docs: https://pestphp.com/docs
- k6 Docs: https://k6.io/docs

**Security:**
- OWASP Top 10: https://owasp.org/Top10/
- Laravel Security: https://laravel.com/docs/11.x/security

---

**Last Updated:** May 23, 2026  
**Status:** Ready for Advanced Development
