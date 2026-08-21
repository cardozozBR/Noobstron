param(
    [Parameter(Mandatory = $true)]
    [string]$BackupDirectory
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$backupDir = (Resolve-Path $BackupDirectory).Path

$dbFile = Join-Path $backupDir "database.dump"
$storageFile = Join-Path $backupDir "storage.tar.gz"
$metadataFile = Join-Path $backupDir "metadata.json"

foreach ($file in @(
    $dbFile,
    $storageFile,
    $metadataFile
)) {
    if (-not (Test-Path $file)) {
        throw "Arquivo ausente: $file"
    }
}

$metadata = Get-Content $metadataFile -Raw -Encoding UTF8 |
    ConvertFrom-Json

Write-Host "`n=== VALIDAR CHECKSUMS ==="

$dbHash = (Get-FileHash $dbFile -Algorithm SHA256).Hash
$storageHash = (Get-FileHash $storageFile -Algorithm SHA256).Hash

if ($dbHash -ne $metadata.database_sha256) {
    throw "Checksum do database.dump inválido."
}

if ($storageHash -ne $metadata.storage_sha256) {
    throw "Checksum do storage.tar.gz inválido."
}

Write-Host "CHECKSUMS_OK"

$suffix = Get-Date -Format "yyyyMMddHHmmss"

$dbContainer = "nossa-plataforma-restore-$suffix"
$dbVolume = "nossa-plataforma-restore-db-$suffix"
$storageVolume = "nossa-plataforma-restore-storage-$suffix"

try {

    Write-Host "`n=== CRIAR POSTGRES TEMPORARIO ==="

    docker volume create $dbVolume | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao criar volume temporário do PostgreSQL."
    }

    docker run -d `
        --name $dbContainer `
        -e POSTGRES_PASSWORD=restore_validation_only `
        -e POSTGRES_DB=restore_validation `
        -v "${dbVolume}:/var/lib/postgresql" `
        postgres:18 | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao iniciar PostgreSQL temporário."
    }

    $ready = $false

    for ($i = 1; $i -le 30; $i++) {

        docker exec $dbContainer `
            pg_isready `
            -U postgres `
            -d restore_validation *> $null

        if ($LASTEXITCODE -eq 0) {
            $ready = $true
            break
        }

        Start-Sleep -Seconds 2
    }

    if (-not $ready) {
        throw "PostgreSQL temporário não ficou pronto."
    }

    Write-Host "POSTGRES_RESTORE_READY"


    # =====================================================
    # DATABASE RESTORE
    # =====================================================

    Write-Host "`n=== RESTAURAR DATABASE ==="

    docker cp `
        $dbFile `
        "${dbContainer}:/tmp/database.dump"

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao copiar database.dump."
    }

    docker exec $dbContainer `
        pg_restore `
        -U postgres `
        -d restore_validation `
        --no-owner `
        --no-privileges `
        /tmp/database.dump

    if ($LASTEXITCODE -ne 0) {
        throw "pg_restore falhou."
    }

    Write-Host "`n=== VALIDAR DATABASE RESTAURADO ==="

    $migrations = docker exec $dbContainer `
        psql -U postgres -d restore_validation -At -c `
        "SELECT count(*) FROM migrations;"

    $tenants = docker exec $dbContainer `
        psql -U postgres -d restore_validation -At -c `
        "SELECT count(*) FROM tenants;"

    $users = docker exec $dbContainer `
        psql -U postgres -d restore_validation -At -c `
        "SELECT count(*) FROM users;"

    Write-Host "migrations=$migrations"
    Write-Host "tenants=$tenants"
    Write-Host "users=$users"

    if ([int]$migrations -ne [int]$metadata.migrations) {
        throw "Contagem de migrations divergente."
    }

    if ([int]$tenants -ne [int]$metadata.tenants) {
        throw "Contagem de tenants divergente."
    }

    if ([int]$users -ne [int]$metadata.users) {
        throw "Contagem de users divergente."
    }

    Write-Host "DATABASE_RESTORE_OK"


    # =====================================================
    # STORAGE RESTORE
    # =====================================================

    Write-Host "`n=== RESTAURAR STORAGE ==="

    docker volume create $storageVolume | Out-Null

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao criar volume temporário do storage."
    }

    docker run --rm `
        --mount "type=volume,source=$storageVolume,target=/restore" `
        --mount "type=bind,source=$backupDir,target=/backup,readonly" `
        alpine:3.22 `
        tar -xzf /backup/storage.tar.gz -C /restore

    if ($LASTEXITCODE -ne 0) {
        throw "Restauração do storage falhou."
    }

    $restoredFiles = docker run --rm `
        --mount "type=volume,source=$storageVolume,target=/restore,readonly" `
        alpine:3.22 `
        sh -c "find /restore -type f | wc -l"

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao validar storage restaurado."
    }

    Write-Host "storage_files=$restoredFiles"

    if ([int]$restoredFiles -ne [int]$metadata.storage_files) {
        throw "Quantidade de arquivos restaurados divergente."
    }

    Write-Host "STORAGE_RESTORE_OK"

    Write-Host "`n=== RESTORE VALIDADO ==="
    Write-Host "RESTORE_VALIDATION_OK"
}
finally {

    Write-Host "`n=== LIMPEZA DE VALIDACAO ==="

    docker rm -f $dbContainer *> $null
    docker volume rm $dbVolume *> $null
    docker volume rm $storageVolume *> $null
}