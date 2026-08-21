param(
    [string]$OutputRoot = ".\backups"
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = Join-Path $OutputRoot $timestamp

[System.IO.Directory]::CreateDirectory($backupDir) | Out-Null
$backupDir = (Resolve-Path $backupDir).Path

$dbFile = Join-Path $backupDir "database.dump"
$storageFile = Join-Path $backupDir "storage.tar.gz"
$metadataFile = Join-Path $backupDir "metadata.json"

Write-Host "`n=== VALIDAR PRODUCTION ==="

docker compose -f compose.production.yaml ps

if ($LASTEXITCODE -ne 0) {
    throw "Stack production indisponível."
}


$postgresUser = (
    docker compose -f compose.production.yaml exec -T postgres `
        printenv POSTGRES_USER
).Trim()

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($postgresUser)) {
    throw "Não foi possível resolver POSTGRES_USER do container."
}

$postgresDb = (
    docker compose -f compose.production.yaml exec -T postgres `
        printenv POSTGRES_DB
).Trim()

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($postgresDb)) {
    throw "Não foi possível resolver POSTGRES_DB do container."
}

Write-Host "postgres-user=resolved"
Write-Host "postgres-db=resolved"

Write-Host "`n=== ESTADO DO BANCO ==="

$migrations = docker compose -f compose.production.yaml exec -T postgres `
    psql -U $postgresUser -d $postgresDb -At -c `
    "SELECT count(*) FROM migrations;"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao consultar migrations."
}

$tenants = docker compose -f compose.production.yaml exec -T postgres `
    psql -U $postgresUser -d $postgresDb -At -c `
    "SELECT count(*) FROM tenants;"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao consultar tenants."
}

$users = docker compose -f compose.production.yaml exec -T postgres `
    psql -U $postgresUser -d $postgresDb -At -c `
    "SELECT count(*) FROM users;"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao consultar users."
}

Write-Host "migrations=$migrations"
Write-Host "tenants=$tenants"
Write-Host "users=$users"


# =========================================================
# DATABASE
# =========================================================

Write-Host "`n=== PG_DUMP ==="

$tempDump = "/tmp/nossa-plataforma-$timestamp.dump"

docker compose -f compose.production.yaml exec -T postgres `
    pg_dump `
    -U $postgresUser `
    -d $postgresDb `
    -Fc `
    -f $tempDump

if ($LASTEXITCODE -ne 0) {
    throw "pg_dump falhou."
}

$postgresContainer = "nossa-plataforma-production-postgres-1"

docker cp `
    "${postgresContainer}:${tempDump}" `
    $dbFile

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao copiar dump para o host."
}

docker compose -f compose.production.yaml exec -T postgres `
    rm -f $tempDump

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao remover dump temporário do container."
}


# =========================================================
# STORAGE
# =========================================================

Write-Host "`n=== BACKUP STORAGE ==="

docker run --rm `
    --mount "type=volume,source=nossa-plataforma-production_app_storage,target=/data,readonly" `
    --mount "type=bind,source=$backupDir,target=/backup" `
    alpine:3.22 `
    tar -czf /backup/storage.tar.gz -C /data .

if ($LASTEXITCODE -ne 0) {
    throw "Backup do storage falhou."
}

Write-Host "`n=== CONTAGEM STORAGE ==="

$storageFiles = docker run --rm `
    --mount "type=volume,source=nossa-plataforma-production_app_storage,target=/data,readonly" `
    alpine:3.22 `
    sh -c "find /data -type f | wc -l"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao contar arquivos do storage."
}


# =========================================================
# METADATA / CHECKSUM
# =========================================================

$gitCommit = git rev-parse HEAD

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao identificar commit Git."
}

$dbHash = (Get-FileHash $dbFile -Algorithm SHA256).Hash
$storageHash = (Get-FileHash $storageFile -Algorithm SHA256).Hash

$metadata = [ordered]@{
    created_at_utc = (Get-Date).ToUniversalTime().ToString("o")
    git_commit = $gitCommit.Trim()
    database = $postgresDb
    postgres_version = "18"
    migrations = [int]$migrations
    tenants = [int]$tenants
    users = [int]$users
    storage_files = [int]$storageFiles
    database_sha256 = $dbHash
    storage_sha256 = $storageHash
}

$metadataJson = $metadata | ConvertTo-Json

[System.IO.File]::WriteAllText(
    $metadataFile,
    $metadataJson,
    [System.Text.UTF8Encoding]::new($false)
)


# =========================================================
# VALIDAR ARQUIVOS
# =========================================================

Write-Host "`n=== VALIDAR DUMP ==="

$dumpList = @(
    docker run --rm `
        --mount "type=bind,source=$backupDir,target=/backup,readonly" `
        postgres:18 `
        pg_restore --list /backup/database.dump
)

if ($LASTEXITCODE -ne 0) {
    throw "Dump PostgreSQL inválido."
}

$dumpList |
    Select-Object -First 10

Write-Host "`n=== VALIDAR TAR ==="

$storageList = @(
    docker run --rm `
        --mount "type=bind,source=$backupDir,target=/backup,readonly" `
        alpine:3.22 `
        tar -tzf /backup/storage.tar.gz
)

if ($LASTEXITCODE -ne 0) {
    throw "Archive de storage inválido."
}

$storageList |
    Select-Object -First 20

Write-Host "`n=== BACKUP CONCLUIDO ==="
Write-Host "Diretório: $backupDir"
Write-Host "Database SHA256: $dbHash"
Write-Host "Storage SHA256: $storageHash"