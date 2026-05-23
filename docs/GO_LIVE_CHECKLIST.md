# Production Go-Live Checklist

**Purpose:** Complete checklist to verify all systems are ready for production launch.

**Project:** Colombo Cargo Connect (CCC)  
**Go-Live Date:** [TBD]  
**Prepared By:** [Your Name]  
**Last Updated:** May 23, 2026

---

## Pre-Launch Requirements

### Business Readiness

- [ ] **Legal & Compliance**
  - [ ] Business registered with authorities
  - [ ] Required licenses obtained
  - [ ] Insurance policies active (cargo, liability)
  - [ ] Terms of Service finalized and reviewed by legal
  - [ ] Privacy Policy compliant with Data Protection Act 2022
  - [ ] Cookie Policy documented
  - [ ] Refund Policy documented

- [ ] **Financial Setup**
  - [ ] Business bank account opened
  - [ ] Payment gateway merchant account approved
  - [ ] Accounting system set up
  - [ ] Pricing finalized and documented
  - [ ] Tax registration complete

- [ ] **Operations**
  - [ ] Hub locations secured (Colombo, Kandy, Galle, etc.)
  - [ ] Lorries acquired/leased (minimum 3-5 vehicles)
  - [ ] Drivers hired and trained (minimum 5-10 drivers)
  - [ ] Hub staff hired and trained (2-3 per hub)
  - [ ] Standard Operating Procedures (SOPs) documented
  - [ ] Warehouse management processes defined

- [ ] **Marketing**
  - [ ] Brand identity finalized (logo, colors, messaging)
  - [ ] Website content written
  - [ ] Marketing materials prepared (flyers, posters, social media)
  - [ ] Launch announcement prepared
  - [ ] Social media accounts created
  - [ ] Customer support channels established

---

## Infrastructure Checklist

### 1. Database (Supabase)

- [ ] **Production Database Created**
  - [ ] Project provisioned on Pro plan
  - [ ] PostGIS extension enabled
  - [ ] All migrations run successfully
  - [ ] Seed data loaded (routes, hubs, initial pricing)
  - [ ] Connection pooling (PgBouncer) configured

- [ ] **Backup Strategy**
  - [ ] Automated daily full backups enabled
  - [ ] Point-in-time recovery (PITR) enabled
  - [ ] Backup retention: 30 days
  - [ ] Backup restore tested successfully
  - [ ] Backup storage location verified

- [ ] **Storage Buckets**
  - [ ] `delivery-proofs` bucket created with policies
  - [ ] `parcel-labels` bucket created with policies
  - [ ] `documents` bucket created with policies
  - [ ] File upload tested from backend
  - [ ] File retrieval tested via signed URLs

- [ ] **Performance**
  - [ ] Database indexed properly (see RUNBOOK.md)
  - [ ] Slow query logging enabled
  - [ ] Query performance baseline established
  - [ ] Connection limits configured

---

### 2. Backend API (Railway)

- [ ] **Deployment**
  - [ ] Production project created on Railway Pro
  - [ ] GitHub repository connected
  - [ ] Auto-deploy enabled on `main` branch
  - [ ] Build succeeds without errors
  - [ ] Health endpoint returns 200 OK: `/api/health`

- [ ] **Environment Variables**
  - [ ] All required env vars configured (see `RAILWAY_ENV_VARS.md`)
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_KEY` generated and set
  - [ ] Database credentials configured
  - [ ] Redis credentials configured
  - [ ] Supabase credentials configured
  - [ ] WhatsApp credentials configured
  - [ ] SMS credentials configured
  - [ ] Payment gateway credentials configured
  - [ ] FCM credentials configured
  - [ ] Sentry DSN configured

- [ ] **Custom Domain**
  - [ ] Domain `api.colombocargo.lk` configured
  - [ ] DNS CNAME record added
  - [ ] SSL certificate provisioned
  - [ ] HTTPS enforced (HTTP redirects to HTTPS)

- [ ] **Services**
  - [ ] Redis add-on provisioned
  - [ ] Queue worker running (`php artisan queue:work`)
  - [ ] Queue processing jobs successfully
  - [ ] Scheduler running (if using `php artisan schedule:work`)

- [ ] **API Functionality**
  - [ ] Authentication endpoints working (login, register, logout)
  - [ ] Booking creation endpoint working
  - [ ] Payment processing endpoint working
  - [ ] Scan endpoints working
  - [ ] Tracking endpoint working
  - [ ] All CRUD endpoints tested

- [ ] **Rate Limiting**
  - [ ] API rate limiting configured (60 requests/min default)
  - [ ] Login rate limiting configured (10 attempts/min)
  - [ ] Rate limit headers returned correctly

- [ ] **CORS**
  - [ ] All web app origins whitelisted
  - [ ] Preflight requests handled correctly
  - [ ] Credentials allowed for authenticated requests

---

### 3. Web Applications (Vercel)

- [ ] **Web Sender Portal**
  - [ ] Deployed to Vercel
  - [ ] Custom domain configured: `colombocargo.lk` or `app.colombocargo.lk`
  - [ ] SSL certificate active
  - [ ] Environment variables configured
  - [ ] Auto-deploy enabled
  - [ ] Login tested
  - [ ] Booking flow tested
  - [ ] Payment flow tested
  - [ ] Tracking tested

- [ ] **Web Admin Console**
  - [ ] Deployed to Vercel
  - [ ] Custom domain configured: `admin.colombocargo.lk`
  - [ ] SSL certificate active
  - [ ] Environment variables configured
  - [ ] Login tested (admin user)
  - [ ] Trip management tested
  - [ ] User management tested
  - [ ] Pricing management tested
  - [ ] Reports accessible

- [ ] **Web Hub Console**
  - [ ] Deployed to Vercel
  - [ ] Custom domain configured: `hub.colombocargo.lk`
  - [ ] SSL certificate active
  - [ ] Environment variables configured
  - [ ] Login tested (hub staff user)
  - [ ] Scan IN tested
  - [ ] Scan OUT tested
  - [ ] Manifest generation tested

- [ ] **Web Tracking Page**
  - [ ] Deployed to Vercel
  - [ ] Custom domain configured: `track.colombocargo.lk`
  - [ ] SSL certificate active
  - [ ] ISR enabled (30-second revalidation)
  - [ ] Environment variables configured
  - [ ] Public tracking tested (no login required)
  - [ ] Real-time status updates working

---

### 4. Mobile Driver App (Flutter)

- [ ] **Build & Distribution**
  - [ ] Production API URL configured in `config.dart`
  - [ ] Release APK built successfully
  - [ ] APK signed with production keystore
  - [ ] Uploaded to Firebase App Distribution
  - [ ] Distributed to test drivers for field testing
  - [ ] iOS build (if applicable)

- [ ] **Functionality**
  - [ ] Login tested
  - [ ] Trip list loads correctly
  - [ ] QR scanner working
  - [ ] Camera permissions granted
  - [ ] Scan events POST successfully
  - [ ] Delivery verification working (NIC + signature + photo)
  - [ ] Push notifications received
  - [ ] Offline mode gracefully handled

---

### 5. External Services

- [ ] **WhatsApp Cloud API**
  - [ ] Business verification complete
  - [ ] Production phone number added
  - [ ] All message templates approved
  - [ ] Webhook configured and verified
  - [ ] Test notification sent successfully
  - [ ] Message delivery confirmed

- [ ] **Notify.lk SMS**
  - [ ] Account approved
  - [ ] KYC completed
  - [ ] Sender ID approved
  - [ ] API credentials configured
  - [ ] Test SMS sent successfully

- [ ] **WebxPay Payment Gateway**
  - [ ] Merchant account approved
  - [ ] KYC documents submitted and verified
  - [ ] Production API credentials received
  - [ ] Webhook configured
  - [ ] Test payment in sandbox successful
  - [ ] Live payment tested (small amount)

- [ ] **Firebase Cloud Messaging**
  - [ ] Production Firebase project created
  - [ ] Android app added
  - [ ] `google-services.json` configured
  - [ ] FCM server key added to backend
  - [ ] Test notification sent successfully

- [ ] **Sentry Error Tracking**
  - [ ] Backend project created
  - [ ] Frontend projects created (4 apps)
  - [ ] SDK installed in all projects
  - [ ] Test error captured successfully
  - [ ] Alerts configured (email, Slack)

- [ ] **Better Stack Uptime Monitoring**
  - [ ] Account created
  - [ ] Monitors added for all endpoints
  - [ ] SMS alerts configured
  - [ ] Email alerts configured
  - [ ] Test alert received
  - [ ] Status page created (optional)

---

## Testing Checklist

### 1. Unit & Integration Tests

- [ ] **Backend Tests**
  - [ ] All Pest tests passing: `vendor\bin\pest`
  - [ ] Test coverage ≥ 70%
  - [ ] Service class tests passing
  - [ ] Controller tests passing
  - [ ] Database integration tests passing

- [ ] **Frontend Tests**
  - [ ] Unit tests passing (if implemented)
  - [ ] Component tests passing (if implemented)

---

### 2. End-to-End Tests

- [ ] **Journey 1: Customer Books Parcel**
  - [ ] Customer registers account
  - [ ] Customer logs in
  - [ ] Customer creates booking
  - [ ] Customer selects trip
  - [ ] Customer pays with card
  - [ ] Booking confirmed
  - [ ] Label PDF generated
  - [ ] WhatsApp notification received
  - [ ] SMS notification received
  - [ ] Tracking page shows status

- [ ] **Journey 2: Driver Picks Up & Delivers Parcel**
  - [ ] Driver logs in to mobile app
  - [ ] Driver views assigned trip
  - [ ] Driver scans parcel at pickup
  - [ ] Scan event logged
  - [ ] Status updated to PICKED_UP
  - [ ] Driver scans at hub (RECEIVED_AT_ORIGIN_HUB)
  - [ ] Driver scans when loading (LOADED_ON_LORRY)
  - [ ] Trip departs (IN_TRANSIT)
  - [ ] Driver scans at destination hub (ARRIVED_AT_DESTINATION_HUB)
  - [ ] Driver scans for delivery (OUT_FOR_DELIVERY)
  - [ ] Driver captures NIC + signature + photo
  - [ ] Status updated to DELIVERED
  - [ ] Delivery proof stored
  - [ ] Delivery notification sent

- [ ] **Journey 3: Hub Operations**
  - [ ] Hub staff logs in
  - [ ] Staff scans incoming parcels
  - [ ] Staff assigns parcels to lorry
  - [ ] Staff generates manifest
  - [ ] Staff scans outgoing parcels

- [ ] **Journey 4: Admin Operations**
  - [ ] Admin logs in
  - [ ] Admin creates new route
  - [ ] Admin schedules trip
  - [ ] Admin assigns driver to trip
  - [ ] Admin updates pricing
  - [ ] Admin views reports
  - [ ] Admin handles dispute

---

### 3. Performance Tests

- [ ] **Load Testing**
  - [ ] API handles 500 concurrent users
  - [ ] API p95 response time < 200ms under normal load
  - [ ] API p95 response time < 500ms under peak load
  - [ ] No errors during load test
  - [ ] Database connection pool stable

- [ ] **Stress Testing**
  - [ ] System handles spike from 100 to 2000 users
  - [ ] Graceful degradation under extreme load
  - [ ] Recovery after load removed

---

### 4. Security Testing

- [ ] **Automated Security Scan**
  - [ ] OWASP ZAP scan completed
  - [ ] 0 critical vulnerabilities
  - [ ] 0 high vulnerabilities
  - [ ] Medium/low vulnerabilities documented

- [ ] **Manual Security Review**
  - [ ] No hardcoded credentials in codebase
  - [ ] All API endpoints require authentication (except public tracking)
  - [ ] Authorization checks on all resources
  - [ ] SQL injection prevented (parameterized queries verified)
  - [ ] XSS prevented (input sanitization verified)
  - [ ] CSRF not applicable (stateless API)
  - [ ] File uploads validated (type, size)
  - [ ] NIC data encrypted at rest
  - [ ] NIC data masked in logs
  - [ ] Passwords hashed with bcrypt
  - [ ] JWT tokens use secure algorithm (HS256 or RS256)
  - [ ] Rate limiting active on all endpoints
  - [ ] HTTPS enforced everywhere
  - [ ] Security headers configured (HSTS, X-Content-Type-Options, etc.)

---

### 5. Cross-Browser Testing

- [ ] **Web Sender Portal**
  - [ ] Chrome (latest)
  - [ ] Firefox (latest)
  - [ ] Safari (latest)
  - [ ] Edge (latest)
  - [ ] Mobile Chrome (Android)
  - [ ] Mobile Safari (iOS)

- [ ] **Web Admin Console**
  - [ ] Chrome (latest)
  - [ ] Firefox (latest)
  - [ ] Edge (latest)

- [ ] **Web Hub Console**
  - [ ] Chrome (latest)
  - [ ] Firefox (latest)

- [ ] **Web Tracking Page**
  - [ ] Chrome (latest)
  - [ ] Firefox (latest)
  - [ ] Safari (latest)
  - [ ] Mobile browsers

---

### 6. Device Testing

- [ ] **Mobile Driver App**
  - [ ] Android 10+ (3 devices minimum)
  - [ ] Various screen sizes (5", 6", 6.5"+)
  - [ ] Camera works on all devices
  - [ ] Signature capture works
  - [ ] Photo upload works
  - [ ] Push notifications received

---

## Monitoring & Operations Checklist

- [ ] **Error Tracking (Sentry)**
  - [ ] Backend errors captured
  - [ ] Frontend errors captured (4 apps)
  - [ ] Critical alerts configured
  - [ ] On-call rotation established

- [ ] **Uptime Monitoring (Better Stack)**
  - [ ] All services monitored
  - [ ] 1-minute check interval configured
  - [ ] SMS alerts active
  - [ ] Email alerts active

- [ ] **Application Performance Monitoring**
  - [ ] Sentry Performance enabled
  - [ ] Key transactions monitored (booking, payment, scan)
  - [ ] Performance budgets configured

- [ ] **Business Metrics Dashboard**
  - [ ] Dashboard accessible
  - [ ] Key metrics visible:
    - [ ] Bookings per day
    - [ ] Revenue per day
    - [ ] Active trips
    - [ ] Delivery success rate
    - [ ] Customer count
  - [ ] Daily reports configured

- [ ] **Log Aggregation**
  - [ ] Railway logs accessible
  - [ ] Vercel logs accessible
  - [ ] Log retention: 7-30 days

---

## Documentation Checklist

- [ ] **Technical Documentation**
  - [ ] API documentation published (Swagger/Postman)
  - [ ] Database schema documented
  - [ ] Architecture diagram created
  - [ ] Deployment runbook complete
  - [ ] Troubleshooting guide complete
  - [ ] Environment variables documented

- [ ] **User Documentation**
  - [ ] Customer FAQ created
  - [ ] Booking guide created
  - [ ] Tracking guide created
  - [ ] Driver training materials prepared
  - [ ] Hub staff training materials prepared
  - [ ] Admin training materials prepared

- [ ] **Operations Documentation**
  - [ ] Standard Operating Procedures (SOPs) documented
  - [ ] Emergency procedures documented
  - [ ] Escalation procedures documented
  - [ ] Contact list updated

---

## Communication Checklist

- [ ] **Internal Communication**
  - [ ] All stakeholders notified of go-live date
  - [ ] Operations team briefed
  - [ ] Support team trained
  - [ ] On-call schedule published

- [ ] **External Communication**
  - [ ] Launch announcement prepared
  - [ ] Social media posts scheduled
  - [ ] Press release drafted (if applicable)
  - [ ] Email to pilot users sent

---

## Launch Day Checklist

### Pre-Launch (T-24 hours)

- [ ] All critical issues resolved
- [ ] Code freeze in effect (no new deployments except hotfixes)
- [ ] Database backup taken
- [ ] All services health-checked
- [ ] Monitoring alerts tested
- [ ] On-call team briefed
- [ ] Support team on standby

### Launch (T-0)

- [ ] Remove beta/pilot restrictions
- [ ] Enable public sign-up
- [ ] Publish launch announcement
- [ ] Post on social media
- [ ] Send press release (if applicable)
- [ ] Monitor error rates (every hour for first 24 hours)
- [ ] Monitor sign-up rates
- [ ] Monitor booking rates
- [ ] Monitor payment success rates
- [ ] Monitor server load

### Post-Launch (T+24 hours)

- [ ] Review first 24 hours metrics
- [ ] Document any issues encountered
- [ ] Address any critical bugs immediately
- [ ] Gather initial user feedback
- [ ] Send thank you message to early adopters

### Post-Launch (T+7 days)

- [ ] Week 1 review meeting
- [ ] Analyze user behavior
- [ ] Identify pain points
- [ ] Prioritize improvements
- [ ] Update roadmap

---

## Rollback Plan

**If critical issue discovered after launch:**

1. **Immediate Actions:**
   - [ ] Post status update: "We're investigating an issue"
   - [ ] Assess impact (partial or full outage)
   - [ ] Notify management

2. **Rollback Procedure:**
   - [ ] Railway: `railway rollback`
   - [ ] Vercel: Promote previous deployment
   - [ ] Database: No rollback (forward-fix only)
   - [ ] Verify rollback successful

3. **Post-Rollback:**
   - [ ] Post resolution update
   - [ ] Investigate root cause
   - [ ] Fix issue in development
   - [ ] Re-test thoroughly
   - [ ] Schedule re-launch

---

## Sign-Off

**Approvals Required:**

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Technical Lead | [Your Name] | ___________ | _____ |
| Project Manager | [PM Name] | ___________ | _____ |
| Operations Manager | [Ops Name] | ___________ | _____ |
| CTO | [CTO Name] | ___________ | _____ |

**Go-Live Decision:**

- [ ] **GO** - All critical items complete, proceed with launch
- [ ] **NO GO** - Critical items incomplete, delay launch
- [ ] **CONDITIONAL GO** - Proceed with noted exceptions

**Notes:**
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

---

## Post-Launch Monitoring

**Day 1-7 Metrics to Track:**

- Sign-ups per day
- Bookings per day
- Payment success rate
- Delivery success rate
- API error rate
- App crash rate
- User-reported issues
- Customer satisfaction (ratings)

**Success Criteria:**

- [ ] 0 critical outages
- [ ] API uptime > 99.5%
- [ ] Payment success rate > 95%
- [ ] Customer satisfaction > 4.0/5.0
- [ ] < 5 critical bugs reported

---

**Checklist Version:** 1.0  
**Last Updated:** May 23, 2026  
**Status:** Ready for Review
