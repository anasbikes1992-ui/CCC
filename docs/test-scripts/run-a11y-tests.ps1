# Accessibility Testing Automation Script  
# Runs axe-core and Lighthouse on all key pages  

Write-Host "========================================" -ForegroundColor Cyan  
Write-Host "Accessibility Testing Suite" -ForegroundColor Cyan  
Write-Host "========================================" -ForegroundColor Cyan  
Write-Host ""  

# Create results directory  
$resultsDir = "d:\CCC\docs\test-results"  
New-Item -ItemType Directory -Path $resultsDir -Force | Out-Null  

# Check if axe-core is installed  
$axeInstalled = Get-Command axe -ErrorAction SilentlyContinue  
if (-not $axeInstalled) {  
    Write-Host "ERROR: axe-core CLI not installed" -ForegroundColor Red  
    Write-Host "Install with: npm install -g @axe-core/cli" -ForegroundColor Yellow  
    exit 1  
}  

# Check if Lighthouse is installed  
$lhInstalled = Get-Command lighthouse -ErrorAction SilentlyContinue  
if (-not $lhInstalled) {  
    Write-Host "ERROR: Lighthouse CLI not installed" -ForegroundColor Red  
    Write-Host "Install with: npm install -g lighthouse" -ForegroundColor Yellow  
    exit 1  
}  

# Pages to test  
$pages = @(  
    @{app="web-sender"; url="https://web-sender.vercel.app/"; name="landing"},  
    @{app="web-sender"; url="https://web-sender.vercel.app/login"; name="login"},  
    @{app="web-sender"; url="https://web-sender.vercel.app/register"; name="register"},  
    @{app="web-tracking"; url="https://web-tracking-sigma.vercel.app/"; name="search"},  
    @{app="web-tracking"; url="https://web-tracking-sigma.vercel.app/CCC-20251101-000001-7"; name="detail"}  
)  

# Run axe-core scans  
Write-Host "Running axe-core scans..." -ForegroundColor Yellow  
$axeViolations = 0  
foreach ($page in $pages) {  
    $outputFile = Join-Path $resultsDir "$($page.app)-$($page.name)-axe.json"  
    Write-Host "  Testing: $($page.url)" -ForegroundColor Gray  
    
    try {  
        axe $page.url --save $outputFile 2>&1 | Out-Null  
        
        # Parse results  
        $results = Get-Content $outputFile | ConvertFrom-Json  
        $violations = $results.violations.Count  
        $axeViolations += $violations  
        
        if ($violations -eq 0) {  
            Write-Host "    ✅ PASS - 0 violations" -ForegroundColor Green  
        } else {  
            Write-Host "    ❌ FAIL - $violations violations" -ForegroundColor Red  
        }  
    } catch {  
        Write-Host "    ⚠️  ERROR - Could not scan page" -ForegroundColor Yellow  
    }  
}  

Write-Host ""  
Write-Host "Running Lighthouse audits..." -ForegroundColor Yellow  
$lowScores = 0  
foreach ($page in $pages) {  
    $outputFile = Join-Path $resultsDir "$($page.app)-$($page.name)-lighthouse.json"  
    Write-Host "  Testing: $($page.url)" -ForegroundColor Gray  
    
    try {  
        lighthouse $page.url --only-categories=accessibility --output=json --output-path=$outputFile --quiet 2>&1 | Out-Null  
        
        # Parse results  
        $results = Get-Content $outputFile | ConvertFrom-Json  
        $score = [math]::Round($results.categories.accessibility.score * 100)  
        
        if ($score -ge 95) {  
            Write-Host "    ✅ PASS - Score: $score" -ForegroundColor Green  
        } elseif ($score -ge 90) {  
            Write-Host "    ⚠️  WARNING - Score: $score (target: 95+)" -ForegroundColor Yellow  
            $lowScores++  
        } else {  
            Write-Host "    ❌ FAIL - Score: $score (target: 95+)" -ForegroundColor Red  
            $lowScores++  
        }  
    } catch {  
        Write-Host "    ⚠️  ERROR - Could not scan page" -ForegroundColor Yellow  
    }  
}  

Write-Host ""  
Write-Host "========================================" -ForegroundColor Cyan  
Write-Host "Testing Summary" -ForegroundColor Cyan  
Write-Host "========================================" -ForegroundColor Cyan  
Write-Host "Pages tested: $($pages.Count)" -ForegroundColor Gray  
Write-Host "axe-core violations: $axeViolations" -ForegroundColor $(if ($axeViolations -eq 0) { "Green" } else { "Red" })  
Write-Host "Lighthouse low scores: $lowScores" -ForegroundColor $(if ($lowScores -eq 0) { "Green" } else { "Yellow" })  
Write-Host ""  

if ($axeViolations -eq 0 -and $lowScores -eq 0) {  
    Write-Host "✅ ALL TESTS PASSED" -ForegroundColor Green  
    Write-Host ""  
    Write-Host "Results saved to: $resultsDir" -ForegroundColor Cyan  
    exit 0  
} else {  
    Write-Host "❌ SOME TESTS FAILED" -ForegroundColor Red  
    Write-Host ""  
    Write-Host "Review results in: $resultsDir" -ForegroundColor Cyan  
    Write-Host "Fix violations and re-run tests" -ForegroundColor Yellow  
    exit 1  
}
