# CCC Project Environment Helper
# Run: . .\ccc-env.ps1  (dot-source to apply to current session)
# This sets PATH for PHP, Composer, PostgreSQL, Flutter for this session only.

$PHP    = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64"
$COMPOSER = "C:\laragon\bin\composer"
$PG     = "D:\Postgres\bin"
$FLUTTER = "D:\Flutter\bin"
$DOCKER  = "D:\DockerExtracted\resources\bin"

# Add to PATH for this session
$env:PATH = "$PHP;$COMPOSER;$PG;$FLUTTER;$DOCKER;" + $env:PATH

# Shortcuts
Set-Alias php      "$PHP\php.exe"       -Force
Set-Alias composer "$COMPOSER\composer.bat" -Force
Set-Alias psql     "$PG\psql.exe"       -Force
Set-Alias flutter  "$FLUTTER\flutter.bat"  -Force
Set-Alias docker   "$DOCKER\docker.exe"     -Force

Write-Host "✅ CCC env loaded:" -ForegroundColor Green
Write-Host "   php      -> $(php -r 'echo PHP_VERSION;')" -ForegroundColor Cyan
Write-Host "   composer -> $(composer --version --no-ansi 2>&1 | Select-Object -First 1)" -ForegroundColor Cyan
Write-Host "   psql     -> $(psql --version)" -ForegroundColor Cyan
Write-Host "   flutter  -> $((flutter --version 2>&1 | Select-String 'Flutter')[0])" -ForegroundColor Cyan
Write-Host "   docker   -> $(docker --version)" -ForegroundColor Cyan
