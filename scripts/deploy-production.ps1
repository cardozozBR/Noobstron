param(
    [switch]$SkipBackup,
    [switch]$SkipGitPull
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$compose = "compose.production.yaml"

function Assert-LastExitCode {
    param([string]$Message)

    if ($LASTEXITCODE -ne 0) {
        throw $Message
    }
}

Write-Host "`n=== PREFLIGHT ==="

if (-not (Test-Path $compose)) {
    throw "compose.production.yaml não encontrado."
}

if (-not (Test-Path ".\backend\.env.production")) {
    throw "backend/.env.production não encontrado."
}

if (git ls-files backend/.env.production) {
    throw "ERRO: backend/.env.production está versionado."
}

docker compose -f $compose config --quiet
Assert-LastExitCode "Compose production inválido."

Write-Host "COMPOSE_CONFIG_OK"

Write-Host "`n=== GIT ==="

$branch = git branch --show-current
Assert-LastExitCode "Falha ao identificar branch."

Write-Host "branch=$branch"

if (-not $SkipGitPull) {
    $dirty = git status --porcelain

    if ($dirty) {
        throw "Working tree não está limpa. Deploy cancelado."
    }

    git fetch origin
    Assert-LastExitCode "git fetch falhou."

    git pull --ff-only origin $branch
    Assert-LastExitCode "git pull falhou."
}
else {
    Write-Host "SKIP_GIT_PULL"
}

$commit = git rev-parse --short HEAD
Assert-LastExitCode "Falha ao identificar commit."

Write-Host "commit=$commit"

if (-not $SkipBackup) {
    Write-Host "`n=== BACKUP PRE-DEPLOY ==="

    & "$projectRoot\backup-production.cmd"

    if ($LASTEXITCODE -ne 0) {
        throw "Backup pre-deploy falhou."
    }

    Write-Host "BACKUP_PRE_DEPLOY_OK"
}
else {
    Write-Host "SKIP_BACKUP"
}

Write-Host "`n=== BUILD ==="

docker compose -f $compose build backend worker scheduler nginx
Assert-LastExitCode "Build falhou."

Write-Host "BUILD_OK"

Write-Host "`n=== POSTGRES ==="

docker compose -f $compose up -d postgres
Assert-LastExitCode "Falha ao iniciar PostgreSQL."

$postgresHealthy = $false

for ($i = 1; $i -le 60; $i++) {
    $info = docker inspect nossa-plataforma-production-postgres-1 |
        ConvertFrom-Json

    if ($info[0].State.Health.Status -eq "healthy") {
        $postgresHealthy = $true
        break
    }

    Start-Sleep -Seconds 2
}

if (-not $postgresHealthy) {
    throw "PostgreSQL não ficou healthy."
}

Write-Host "POSTGRES_HEALTHY_OK"

Write-Host "`n=== MIGRATIONS ==="

docker compose -f $compose run --rm backend php artisan migrate --force
Assert-LastExitCode "Migrations falharam."

Write-Host "MIGRATIONS_OK"

Write-Host "`n=== SUBIR SERVICOS ==="

docker compose -f $compose up -d --force-recreate backend worker scheduler nginx
Assert-LastExitCode "Falha ao subir serviços."

Write-Host "`n=== HEALTHCHECKS ==="

$healthyContainers = @(
    "nossa-plataforma-production-backend-1",
    "nossa-plataforma-production-nginx-1",
    "nossa-plataforma-production-postgres-1",
    "nossa-plataforma-production-worker-1"
)

foreach ($container in $healthyContainers) {
    $healthy = $false

    for ($i = 1; $i -le 60; $i++) {
        $info = docker inspect $container |
            ConvertFrom-Json

        if (
            $info[0].State.Status -eq "running" -and
            $null -ne $info[0].State.Health -and
            $info[0].State.Health.Status -eq "healthy"
        ) {
            $healthy = $true
            break
        }

        Start-Sleep -Seconds 2
    }

    if (-not $healthy) {
        docker compose -f $compose ps
        throw "$container não ficou healthy."
    }

    Write-Host "$container=healthy"
}

$schedulerContainer =
    "nossa-plataforma-production-scheduler-1"

$schedulerRunning = $false

for ($i = 1; $i -le 30; $i++) {
    $info = docker inspect $schedulerContainer |
        ConvertFrom-Json

    if ($info[0].State.Status -eq "running") {
        $schedulerRunning = $true
        break
    }

    Start-Sleep -Seconds 2
}

if (-not $schedulerRunning) {
    docker compose -f $compose ps
    docker compose -f $compose logs --tail=100 scheduler
    throw "$schedulerContainer não permaneceu running."
}

Write-Host "$schedulerContainer=running"
Write-Host "ALL_SERVICES_OK"

Write-Host "`n=== HTTP HEALTH ==="

$response = Invoke-WebRequest `
    -Uri "http://127.0.0.1:8080/up" `
    -UseBasicParsing `
    -TimeoutSec 15

if ($response.StatusCode -ne 200) {
    throw "/up não retornou 200."
}

Write-Host "HTTP_UP_OK"

Write-Host "`n=== DATABASE FINAL ==="

docker compose -f $compose exec -T backend php artisan migrate:status
Assert-LastExitCode "migrate:status falhou."

Write-Host "DATABASE_OK"

Write-Host "`n=== QUEUE FINAL ==="

docker compose -f $compose exec -T worker `
    php artisan queue:monitor default --max=1000

Assert-LastExitCode "queue:monitor falhou."

docker compose -f $compose exec -T backend php artisan queue:failed
Assert-LastExitCode "queue:failed falhou."

Write-Host "QUEUE_OK"

Write-Host "`n=== SERVICOS ==="

docker compose -f $compose ps

Write-Host "`n=== DEPLOY CONCLUIDO ==="
Write-Host "commit=$commit"
Write-Host "DEPLOY_OK"