$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host "`n=== RELEASE CANDIDATE VALIDATION ===" -ForegroundColor Cyan
Write-Host "root=$root"

if (-not (Test-Path (Join-Path $root 'compose.yaml'))) {
    throw 'compose.yaml nao encontrado.'
}

Write-Host "`n=== DOCKER SERVICES ==="
docker compose up -d --build
if ($LASTEXITCODE -ne 0) { throw 'docker compose up falhou.' }
docker compose ps

Write-Host "`n=== LARAVEL CACHE RESET ==="
docker compose exec -T backend php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'optimize:clear falhou.' }

Write-Host "`n=== DATABASE MIGRATIONS (LOCAL) ==="
docker compose exec -T backend php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'migrate falhou.' }

Write-Host "`n=== ROUTES ==="
docker compose exec -T backend php artisan route:list
if ($LASTEXITCODE -ne 0) { throw 'route:list falhou.' }

Write-Host "`n=== FULL SAFE TEST SUITE ===" -ForegroundColor Yellow
$testSafe = Join-Path $root 'test-safe.cmd'
if (-not (Test-Path $testSafe)) { throw 'test-safe.cmd nao encontrado.' }
& $testSafe
if ($LASTEXITCODE -ne 0) { throw 'Suite de testes falhou.' }

Write-Host "`n=== SOURCE WHITESPACE CHECK ==="
$gitDir = Join-Path $root '.git'
if (Test-Path $gitDir) {
    git diff --check
    if ($LASTEXITCODE -ne 0) { throw 'git diff --check falhou.' }
    Write-Host 'git-diff-check=ok'
}
else {
    Write-Host 'git-diff-check=skipped (sanitized RC without .git)'
}

Write-Host "`n=== RELEASE CANDIDATE AUTOMATED VALIDATION GREEN ===" -ForegroundColor Green
Write-Host 'Nenhum deploy ou commit foi realizado.'
Write-Host 'Agora execute o checklist manual de RELEASE-CANDIDATE.md.'
