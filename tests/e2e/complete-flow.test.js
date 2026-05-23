/**
 * CCC E2E Flow Test
 * Tests complete parcel lifecycle from booking to delivery
 */

const { chromium } = require('playwright');

// Test configuration
const CONFIG = {
  baseUrl: 'https://web-sender.vercel.app',
  adminUrl: 'https://web-admin-rho-sepia.vercel.app',
  trackingUrl: 'https://web-tracking-sigma.vercel.app',
  apiUrl: 'https://ccc-production.up.railway.app',
  
  // Test credentials
  sender: {
    email: 'sender@test.com',
    phone: '+94771111111',
    password: 'password'
  },
  driver: {
    email: 'driver@test.com',
    phone: '+94773333333',
    password: 'password'
  },
  admin: {
    email: 'admin@ccc.lk',
    phone: '+94771234567',
    password: 'password'
  },
  
  // Test data
  parcel: {
    receiverName: 'Test Receiver',
    receiverPhone: '+94779999999',
    receiverAddress: 'Test Address, Kandy',
    size: 'M',
    weight: 5,
    route: 'CMB-KDY'
  }
};

const testResults = {
  passed: [],
  failed: [],
  warnings: [],
  parcelNumber: null,
  otp: null
};

async function log(message, type = 'info') {
  const timestamp = new Date().toISOString();
  const prefix = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
  console.log(`[${timestamp}] ${prefix} ${message}`);
}

async function testStep(name, fn) {
  try {
    await log(`Testing: ${name}`, 'info');
    await fn();
    testResults.passed.push(name);
    await log(`✓ ${name}`, 'success');
    return true;
  } catch (error) {
    testResults.failed.push({ name, error: error.message });
    await log(`✗ ${name}: ${error.message}`, 'error');
    return false;
  }
}

async function main() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    await log('=== CCC E2E Test Suite Starting ===');
    await log(`Testing against: ${CONFIG.baseUrl}`);

    // TEST 1: Sender Registration/Login
    await testStep('Navigate to sender portal', async () => {
      await page.goto(CONFIG.baseUrl);
      await page.waitForLoadState('networkidle');
    });

    await testStep('Login as sender', async () => {
      // Try to find login form
      const emailField = page.locator('input[type="email"], input[name="email"]').first();
      const passwordField = page.locator('input[type="password"], input[name="password"]').first();
      
      if (await emailField.isVisible({ timeout: 5000 }).catch(() => false)) {
        await emailField.fill(CONFIG.sender.email);
        await passwordField.fill(CONFIG.sender.password);
        await page.locator('button:has-text("Login"), button:has-text("Sign In")').first().click();
        await page.waitForLoadState('networkidle');
      } else {
        await log('Already logged in or login page not found', 'warning');
      }
    });

    // TEST 2: Create Booking
    await testStep('Navigate to booking page', async () => {
      const bookButton = page.locator('button:has-text("Book"), a:has-text("Book"), button:has-text("New Booking")').first();
      if (await bookButton.isVisible({ timeout: 5000 }).catch(() => false)) {
        await bookButton.click();
        await page.waitForLoadState('networkidle');
      } else {
        // Try navigating directly
        await page.goto(`${CONFIG.baseUrl}/book`);
        await page.waitForLoadState('networkidle');
      }
    });

    await testStep('Fill booking form - Route selection', async () => {
      // Select route
      const routeSelector = page.locator('select[name="route"], button:has-text("CMB"), div:has-text("Colombo")').first();
      await routeSelector.click({ timeout: 10000 });
      await page.locator('text=Kandy').first().click();
    });

    await testStep('Fill booking form - Package details', async () => {
      // Select size
      await page.locator(`button:has-text("${CONFIG.parcel.size}"), div:has-text("Medium")`).first().click();
      
      // Enter weight
      const weightField = page.locator('input[name="weight"], input[placeholder*="weight"]').first();
      await weightField.fill(CONFIG.parcel.weight.toString());
    });

    await testStep('Fill booking form - Receiver details', async () => {
      await page.locator('input[name="receiver_name"], input[placeholder*="Receiver"]').first().fill(CONFIG.parcel.receiverName);
      await page.locator('input[name="receiver_phone"], input[placeholder*="phone"]').nth(1).fill(CONFIG.parcel.receiverPhone);
      await page.locator('input[name="receiver_address"], textarea[name="address"]').first().fill(CONFIG.parcel.receiverAddress);
    });

    await testStep('Calculate price', async () => {
      const calculateBtn = page.locator('button:has-text("Calculate"), button:has-text("Get Price")').first();
      await calculateBtn.click();
      await page.waitForTimeout(2000);
      
      // Verify price is shown
      const priceDisplay = page.locator('text=/LKR\\s+\\d+/, text=/Rs\\.?\\s*\\d+/').first();
      const priceVisible = await priceDisplay.isVisible({ timeout: 5000 }).catch(() => false);
      if (!priceVisible) {
        throw new Error('Price not displayed after calculation');
      }
    });

    await testStep('Complete booking', async () => {
      const bookBtn = page.locator('button:has-text("Book Now"), button:has-text("Confirm")').first();
      await bookBtn.click();
      await page.waitForTimeout(3000);
      
      // Extract parcel number from confirmation
      const parcelNumberPattern = /CCC-\d{8}-\d{6}-\d/;
      const pageContent = await page.content();
      const match = pageContent.match(parcelNumberPattern);
      if (match) {
        testResults.parcelNumber = match[0];
        await log(`Created parcel: ${testResults.parcelNumber}`, 'success');
      } else {
        throw new Error('Parcel number not found in confirmation');
      }
    });

    // TEST 3: Track Parcel
    if (testResults.parcelNumber) {
      await testStep('Track parcel publicly', async () => {
        await page.goto(`${CONFIG.trackingUrl}/track/${testResults.parcelNumber}`);
        await page.waitForLoadState('networkidle');
        
        const statusElement = page.locator('text=/BOOKED|PENDING|CONFIRMED/i').first();
        const statusVisible = await statusElement.isVisible({ timeout: 5000 }).catch(() => false);
        if (!statusVisible) {
          throw new Error('Parcel status not visible on tracking page');
        }
      });
    }

    // TEST 4: Admin Operations (would require admin login)
    await testStep('Check admin panel access', async () => {
      const adminPage = await context.newPage();
      await adminPage.goto(CONFIG.adminUrl);
      await adminPage.waitForLoadState('networkidle');
      
      // Try to login as admin
      const emailField = adminPage.locator('input[type="email"]').first();
      if (await emailField.isVisible({ timeout: 3000 }).catch(() => false)) {
        await emailField.fill(CONFIG.admin.email);
        await adminPage.locator('input[type="password"]').first().fill(CONFIG.admin.password);
        await adminPage.locator('button:has-text("Login")').first().click();
        await adminPage.waitForTimeout(2000);
      }
      
      await adminPage.close();
    });

    // TEST 5: API Health Check
    await testStep('Verify API health', async () => {
      const response = await page.request.get(`${CONFIG.apiUrl}/up`);
      if (!response.ok()) {
        throw new Error(`API health check failed: ${response.status()}`);
      }
    });

    // TEST 6: API Authentication
    await testStep('Test API login endpoint', async () => {
      const response = await page.request.post(`${CONFIG.apiUrl}/api/v1/auth/login`, {
        data: {
          phone: CONFIG.sender.phone,
          password: CONFIG.sender.password
        }
      });
      
      if (!response.ok()) {
        throw new Error(`API login failed: ${response.status()}`);
      }
      
      const data = await response.json();
      if (!data.success || !data.data.token) {
        throw new Error('API login response missing token');
      }
      
      await log(`API token received: ${data.data.token.substring(0, 20)}...`, 'info');
    });

    // Generate Report
    await log('=== Test Results Summary ===');
    await log(`Total Passed: ${testResults.passed.length}`, 'success');
    await log(`Total Failed: ${testResults.failed.length}`, testResults.failed.length > 0 ? 'error' : 'info');
    await log(`Total Warnings: ${testResults.warnings.length}`, 'warning');
    
    if (testResults.parcelNumber) {
      await log(`\n📦 Created Parcel: ${testResults.parcelNumber}`);
    }

    if (testResults.failed.length > 0) {
      await log('\n❌ Failed Tests:', 'error');
      testResults.failed.forEach(fail => {
        log(`  - ${fail.name}: ${fail.error}`, 'error');
      });
    }

    await log('\n✅ Passed Tests:', 'success');
    testResults.passed.forEach(pass => {
      log(`  - ${pass}`, 'success');
    });

  } catch (error) {
    await log(`Fatal error: ${error.message}`, 'error');
    console.error(error);
  } finally {
    await page.waitForTimeout(3000); // Keep browser open for 3 seconds
    await browser.close();
  }

  // Return results
  return testResults;
}

// Run tests
main()
  .then(results => {
    console.log('\n' + '='.repeat(60));
    console.log('TEST EXECUTION COMPLETE');
    console.log('='.repeat(60));
    process.exit(results.failed.length === 0 ? 0 : 1);
  })
  .catch(error => {
    console.error('Test suite failed:', error);
    process.exit(1);
  });
