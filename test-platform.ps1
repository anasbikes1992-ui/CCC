$base = "https://ccc-production-30a5.up.railway.app/api/v1"
$h = @{"Content-Type"="application/json"}
$global:pass = 0; $global:fail = 0

function Check($label, $ok, $detail="") {
  if ($ok) { Write-Host "  [PASS] $label $detail" -ForegroundColor Green; $global:pass++ }
  else      { Write-Host "  [FAIL] $label $detail" -ForegroundColor Red;   $global:fail++ }
}

Write-Host "`n========================================"
Write-Host "  CCC Platform Test Suite"
Write-Host "========================================"

Write-Host "`n[1] HEALTH"
try { $r = Invoke-RestMethod "https://ccc-production-30a5.up.railway.app/up" -TimeoutSec 10; Check "GET /up" $true } catch { Check "GET /up" $false $_.Exception.Message }
try { $r = Invoke-RestMethod "https://ccc-production-30a5.up.railway.app/api/health" -TimeoutSec 10; Check "GET /api/health" ($null -ne $r) } catch { Check "GET /api/health" $false $_.Exception.Message }

Write-Host "`n[2] AUTHENTICATION"
$tokens = @{}
$logins = @(
  @{phone="+94777777001"; label="Customer"; role="customer"},
  @{phone="+94777777003"; label="Driver";   role="driver"},
  @{phone="+94771234567"; label="Admin";    role="admin_super"}
)
foreach ($u in $logins) {
  try {
    $b = "{`"phone`":`"$($u.phone)`",`"password`":`"password`"}"
    $r = Invoke-RestMethod "$base/auth/login" -Method POST -Body $b -Headers $h -TimeoutSec 15
    $ok = ($r.success -and $r.data.user.role -eq $u.role)
    Check "Login $($u.label)" $ok "role=$($r.data.user.role)"
    $tokens[$u.label] = $r.data.token
  } catch { Check "Login $($u.label)" $false $_.Exception.Message }
  Start-Sleep -Milliseconds 400
}

Write-Host "`n[3] AUTH/ME"
if ($tokens["Customer"]) {
  $ah = @{"Content-Type"="application/json"; "Authorization"="Bearer $($tokens['Customer'])"}
  try { $r = Invoke-RestMethod "$base/auth/me" -Headers $ah -TimeoutSec 10; Check "GET /auth/me" $r.success "name=$($r.data.user.full_name)" } catch { Check "GET /auth/me" $false $_.Exception.Message }
}

Write-Host "`n[4] CUSTOMER ENDPOINTS"
if ($tokens["Customer"]) {
  $ah = @{"Content-Type"="application/json"; "Authorization"="Bearer $($tokens['Customer'])"}
  try { $r = Invoke-RestMethod "$base/customer/parcels" -Headers $ah -TimeoutSec 10; Check "GET /customer/parcels" $r.success "count=$($r.data.parcels.Count)"; if ($r.data.parcels.Count -gt 0) { $global:pNum = $r.data.parcels[0].parcel_number } } catch { Check "GET /customer/parcels" $false $_.Exception.Message }
  try { $r = Invoke-RestMethod "$base/customer/disputes" -Headers $ah -TimeoutSec 10; Check "GET /customer/disputes" $r.success } catch { Check "GET /customer/disputes" $false $_.Exception.Message }
  try { $r = Invoke-RestMethod "$base/customer/tickets" -Headers $ah -TimeoutSec 10; Check "GET /customer/tickets" $r.success } catch { Check "GET /customer/tickets" $false $_.Exception.Message }
}

Write-Host "`n[5] DRIVER ENDPOINTS"
if ($tokens["Driver"]) {
  $ah = @{"Content-Type"="application/json"; "Authorization"="Bearer $($tokens['Driver'])"}
  try { $r = Invoke-RestMethod "$base/driver/trips" -Headers $ah -TimeoutSec 10; Check "GET /driver/trips" $r.success "count=$($r.data.trips.Count)" } catch { Check "GET /driver/trips" $false $_.Exception.Message }
}

Write-Host "`n[6] ADMIN ENDPOINTS"
if ($tokens["Admin"]) {
  $ah = @{"Content-Type"="application/json"; "Authorization"="Bearer $($tokens['Admin'])"}
  $tests = @("dashboard/stats","users?limit=5","trips?limit=5","parcels?limit=5","hubs","routes","lorries","pricing","drivers?limit=5")
  foreach ($ep in $tests) {
    try { $r = Invoke-RestMethod "$base/admin/$ep" -Headers $ah -TimeoutSec 10; Check "GET /admin/$ep" $r.success } catch { Check "GET /admin/$ep" $false $_.Exception.Message }
    Start-Sleep -Milliseconds 200
  }
} else { Write-Host "  (skipped � no admin token)" -ForegroundColor DarkGray }

Write-Host "`n[7] PUBLIC TRACKING"
if ($global:pNum) {
  try { $r = Invoke-RestMethod "$base/public/parcels/$($global:pNum)/track" -TimeoutSec 10; Check "GET /public/parcels/{n}/track" $r.success "status=$($r.data.parcel.status)" } catch { Check "GET /public/parcels/{n}/track" $false $_.Exception.Message }
} else { Write-Host "  (no parcels seeded, skip)" -ForegroundColor DarkGray }

Write-Host "`n[8] ACCESS CONTROL"
try { Invoke-RestMethod "$base/customer/parcels" -TimeoutSec 10 | Out-Null; Check "Unauthenticated -> 401" $false } catch { Check "Unauthenticated -> 401" ($_.Exception.Response.StatusCode.value__ -eq 401) "HTTP $($_.Exception.Response.StatusCode.value__)" }
if ($tokens["Customer"]) {
  $ah = @{"Content-Type"="application/json"; "Authorization"="Bearer $($tokens['Customer'])"}
  try { Invoke-RestMethod "$base/admin/users" -Headers $ah -TimeoutSec 10 | Out-Null; Check "Customer -> admin 403" $false } catch { Check "Customer -> admin 403" ($_.Exception.Response.StatusCode.value__ -eq 403) "HTTP $($_.Exception.Response.StatusCode.value__)" }
}

Write-Host "`n========================================"
Write-Host "  RESULTS: $global:pass PASSED   $global:fail FAILED"
Write-Host "========================================`n"
