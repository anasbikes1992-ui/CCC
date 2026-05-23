# CCC -- Full Production Setup Script
# Run from D:\CCC:  .\setup-production.ps1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$VERCEL_TOKEN  = $env:VERCEL_TOKEN
$VERCEL_TEAM   = "team_40F4lbdPeR0Kbl4xlJdmnN0W"
$RAILWAY_TOKEN = $env:RAILWAY_TOKEN

$PROJECTS = @{
    "ccc-sender"   = "prj_Bs8lE6flATitCaNz3sh1JOIktxdQ"
    "ccc-admin"    = "prj_n8kbUaiVvm43WA3yt2xlZFbgTjh9"
    "ccc-tracking" = "prj_Lbau4E9JqgXY7JgujN8gaCAHPHkD"
    "ccc-hub"      = "prj_fDKZjc0tknOZjQazgA0j9OtsBJS5"
}

function vApi($method, $path, $body = $null) {
    $p = @{
        Method      = $method
        Uri         = "https://api.vercel.com$path"
        Headers     = @{ Authorization = "Bearer $VERCEL_TOKEN"; "Content-Type" = "application/json" }
        ErrorAction = "Stop"
    }
    if ($body) { $p.Body = ($body | ConvertTo-Json -Depth 10 -Compress) }
    return Invoke-RestMethod @p
}

function railwayGql([string]$query, [hashtable]$vars = @{}) {
    $payload = @{ query = $query }
    if ($vars.Count -gt 0) { $payload.variables = $vars }
    $json = $payload | ConvertTo-Json -Depth 10 -Compress
    $resp = Invoke-RestMethod `
        -Method POST `
        -Uri "https://backboard.railway.app/graphql/v2" `
        -Headers @{ Authorization = "Bearer $RAILWAY_TOKEN"; "Content-Type" = "application/json" } `
        -Body $json `
        -ErrorAction Stop
    if ($resp.errors) { throw "Railway GQL error: $($resp.errors[0].message)" }
    return $resp.data
}

function setVercelEnv($projectId, $key, $value) {
    try {
        $list = vApi "GET" "/v9/projects/$projectId/env?teamId=$VERCEL_TEAM&limit=100"
        foreach ($e in $list.envs) {
            if ($e.key -eq $key) {
                vApi "DELETE" "/v9/projects/$projectId/env/$($e.id)?teamId=$VERCEL_TEAM" | Out-Null
            }
        }
    } catch {}
    vApi "POST" "/v10/projects/$projectId/env?teamId=$VERCEL_TEAM" @(
        @{ key = $key; value = $value; type = "plain"; target = "production" },
        @{ key = $key; value = $value; type = "plain"; target = "preview" },
        @{ key = $key; value = $value; type = "plain"; target = "development" }
    ) | Out-Null
    Write-Host "    SET $key" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== STEP 1: Getting Railway info ===" -ForegroundColor Cyan

$projQuery = "query { me { projects { edges { node { id name } } } } }"
$projData  = railwayGql $projQuery

$RAILWAY_PROJECT_ID = $null
Write-Host "  Railway projects:"
foreach ($edge in $projData.me.projects.edges) {
    $p = $edge.node
    Write-Host "    - $($p.name)  [$($p.id)]"
    if (-not $RAILWAY_PROJECT_ID) { $RAILWAY_PROJECT_ID = $p.id }
    if ($p.name -match "ccc|cargo|colombo") { $RAILWAY_PROJECT_ID = $p.id }
}

$svcQuery = "query GetSvc(`$id: String!) { project(id: `$id) { services { edges { node { id name } } } } }"
$svcData  = railwayGql $svcQuery @{ id = $RAILWAY_PROJECT_ID }

$RAILWAY_SERVICE_ID = $null
Write-Host "  Services:"
foreach ($edge in $svcData.project.services.edges) {
    $s = $edge.node
    Write-Host "    - $($s.name)  [$($s.id)]"
    if (-not $RAILWAY_SERVICE_ID) { $RAILWAY_SERVICE_ID = $s.id }
    if ($s.name -match "api|backend|web|app|server") { $RAILWAY_SERVICE_ID = $s.id }
}

$domQuery = "query GetDom(`$id: String!) { service(id: `$id) { serviceInstances { edges { node { domains { serviceDomains { domain } } } } } } }"
$RAILWAY_URL = $null
try {
    $domData = railwayGql $domQuery @{ id = $RAILWAY_SERVICE_ID }
    foreach ($instEdge in $domData.service.serviceInstances.edges) {
        foreach ($d in $instEdge.node.domains.serviceDomains) {
            if ($d.domain -and -not $RAILWAY_URL) {
                $RAILWAY_URL = "https://$($d.domain)"
            }
        }
    }
} catch {
    Write-Host "  Domain query failed: $_" -ForegroundColor Yellow
}

if (-not $RAILWAY_URL) {
    Write-Host "  Could not auto-detect Railway domain." -ForegroundColor Yellow
    $RAILWAY_URL = Read-Host "  Paste your Railway backend URL (e.g. https://ccc-api.up.railway.app)"
    $RAILWAY_URL = $RAILWAY_URL.TrimEnd("/")
}

$API_BASE = "$RAILWAY_URL/api/v1"
Write-Host "  API base: $API_BASE" -ForegroundColor Green

Write-Host ""
Write-Host "=== STEP 2: Getting Vercel domains ===" -ForegroundColor Cyan

$vercelDomains = @{}
foreach ($name in $PROJECTS.Keys) {
    try {
        $info   = vApi "GET" "/v9/projects/$($PROJECTS[$name])?teamId=$VERCEL_TEAM"
        $domain = ($info.alias | Where-Object { $_ -like "*.vercel.app" } | Select-Object -First 1)
        if (-not $domain) { $domain = "$name.vercel.app" }
        $vercelDomains[$name] = "https://$domain"
    } catch {
        $vercelDomains[$name] = "https://$name.vercel.app"
    }
    Write-Host "  $name -> $($vercelDomains[$name])" -ForegroundColor Green
}

$TRACKING_URL = $vercelDomains["ccc-tracking"]
$CORS_ORIGINS = ($vercelDomains.Values | Sort-Object) -join ","
$SANCTUM_DOMS = (
    ($vercelDomains["ccc-sender"]   -replace "^https://",""),
    ($vercelDomains["ccc-admin"]    -replace "^https://",""),
    ($vercelDomains["ccc-hub"]      -replace "^https://","")
) -join ","

Write-Host ""
Write-Host "=== STEP 3: Setting Vercel env vars ===" -ForegroundColor Cyan

Write-Host "  [ccc-sender]"
setVercelEnv $PROJECTS["ccc-sender"] "NEXT_PUBLIC_API_BASE_URL" $API_BASE
setVercelEnv $PROJECTS["ccc-sender"] "NEXT_PUBLIC_TRACKING_URL" $TRACKING_URL

Write-Host "  [ccc-tracking]"
setVercelEnv $PROJECTS["ccc-tracking"] "NEXT_PUBLIC_API_BASE_URL" $API_BASE

Write-Host "  [ccc-admin]"
setVercelEnv $PROJECTS["ccc-admin"] "NEXT_PUBLIC_API_BASE_URL" $API_BASE

Write-Host "  [ccc-hub]"
setVercelEnv $PROJECTS["ccc-hub"] "NEXT_PUBLIC_API_BASE_URL" $API_BASE

Write-Host ""
Write-Host "=== STEP 4: Setting Railway env vars ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Find your Supabase SERVICE ROLE key at:" -ForegroundColor Yellow
Write-Host "  Supabase -> Project Settings -> API -> service_role (secret)" -ForegroundColor Yellow
$SB_SERVICE_KEY = Read-Host "  Paste Supabase service_role key"

$upsertMutation = "mutation UpsertVariables(`$input: VariableCollectionUpsertInput!) { variableCollectionUpsert(input: `$input) }"

$envQuery = "query GetEnv(`$id: String!) { project(id: `$id) { environments { edges { node { id name } } } } }"
$envData  = railwayGql $envQuery @{ id = $RAILWAY_PROJECT_ID }
$RAILWAY_ENV_ID = $envData.project.environments.edges[0].node.id
Write-Host "  Using environment: $($envData.project.environments.edges[0].node.name) [$RAILWAY_ENV_ID]"

$railwayVars = @{
    APP_NAME                  = "Colombo Cargo Connect"
    APP_ENV                   = "production"
    APP_DEBUG                 = "false"
    APP_KEY                   = "base64:ODppQxq1id/cMfPbnRd6A6cNwwyjUfqk7RfPg3q4Ag0="
    APP_TIMEZONE              = "Asia/Colombo"
    APP_URL                   = $RAILWAY_URL
    LOG_CHANNEL               = "stack"
    LOG_LEVEL                 = "info"
    DB_CONNECTION             = "pgsql"
    DB_HOST                   = "db.afmjlngcsxrwfmznaxpf.supabase.co"
    DB_PORT                   = "5432"
    DB_DATABASE               = "postgres"
    DB_USERNAME               = "postgres"
    DB_PASSWORD               = "E5DRpJUJqNlYSd0v"
    CACHE_STORE               = "redis"
    SESSION_DRIVER            = "redis"
    QUEUE_CONNECTION          = "redis"
    REDIS_CLIENT              = "predis"
    FILESYSTEM_DISK           = "supabase"
    SUPABASE_URL              = "https://afmjlngcsxrwfmznaxpf.supabase.co"
    SUPABASE_SERVICE_ROLE_KEY = $SB_SERVICE_KEY
    SUPABASE_ANON_KEY         = "sb_publishable_jQDUYZHtqtCT5RX8wKmd2w_DA05XE3t"
    SUPABASE_BUCKET_LABELS    = "ccc-labels"
    SUPABASE_BUCKET_PROOFS    = "ccc-proofs"
    CORS_ALLOWED_ORIGINS      = $CORS_ORIGINS
    SANCTUM_STATEFUL_DOMAINS  = $SANCTUM_DOMS
    SESSION_DOMAIN            = ".vercel.app"
    QR_TOKEN_SECRET           = "AMoP2f2p1xK9UPSkLLyMs05jCxcIqthNg7t0T1pln5A="
    QR_TOKEN_TTL_DAYS         = "30"
    WHATSAPP_API_VERSION      = "v21.0"
    NOTIFY_LK_SENDER_ID       = "CCC"
    MAIL_MAILER               = "log"
    MAIL_FROM_ADDRESS         = "noreply@cargo.lk"
    MAIL_FROM_NAME            = "Colombo Cargo Connect"
}

railwayGql $upsertMutation @{
    input = @{
        projectId     = $RAILWAY_PROJECT_ID
        serviceId     = $RAILWAY_SERVICE_ID
        environmentId = $RAILWAY_ENV_ID
        variables     = $railwayVars
        replace       = $false
    }
} | Out-Null
Write-Host "  Railway env vars set." -ForegroundColor Green
Write-Host "  NOTE: Add Redis in Railway if not done (New Service -> Redis)." -ForegroundColor Yellow

Write-Host ""
Write-Host "=== STEP 5: Triggering Vercel redeployments ===" -ForegroundColor Cyan

foreach ($name in $PROJECTS.Keys) {
    $pid = $PROJECTS[$name]
    try {
        $deps   = vApi "GET" "/v6/deployments?projectId=$pid&teamId=$VERCEL_TEAM&limit=1&target=production"
        $latest = $deps.deployments | Select-Object -First 1
        if ($latest) {
            $body = @{ deploymentId = $latest.uid; name = $name; target = "production" }
            vApi "POST" "/v13/deployments?teamId=$VERCEL_TEAM&forceNew=1" $body | Out-Null
            Write-Host "  $name -> redeploy triggered" -ForegroundColor Green
        } else {
            Write-Host "  $name -> no deployment yet; will build on next git push" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "  $name -> could not trigger (redeploy manually from vercel.com)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  All done!  Wait ~3 min for Vercel builds." -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "URLs to test:" -ForegroundColor Yellow
Write-Host "  Backend health : $RAILWAY_URL/up"
Write-Host "  Sender portal  : $($vercelDomains['ccc-sender'])"
Write-Host "  Tracking page  : $($vercelDomains['ccc-tracking'])"
Write-Host "  Admin panel    : $($vercelDomains['ccc-admin'])"
Write-Host "  Hub console    : $($vercelDomains['ccc-hub'])"
Write-Host ""
Write-Host "After Redis is linked, seed the database:" -ForegroundColor Yellow
Write-Host '  railway run php artisan db:seed'
