$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$compose = "compose.production.yaml"
$envFile = ".\backend\.env.production"

function Read-DotEnv {
    param([string]$Path)

    $values = @{}

    foreach ($line in Get-Content $Path -Encoding UTF8) {
        $trimmed = $line.Trim()

        if (
            $trimmed -eq "" -or
            $trimmed.StartsWith("#") -or
            -not $trimmed.Contains("=")
        ) {
            continue
        }

        $parts = $trimmed.Split("=", 2)
        $key = $parts[0].Trim()
        $value = $parts[1].Trim()

        if (
            $value.Length -ge 2 -and
            (
                ($value.StartsWith('"') -and $value.EndsWith('"')) -or
                ($value.StartsWith("'") -and $value.EndsWith("'"))
            )
        ) {
            $value = $value.Substring(1, $value.Length - 2)
        }

        $values[$key] = $value
    }

    return $values
}

function Assert-Value {
    param(
        [hashtable]$Values,
        [string]$Key,
        [scriptblock]$Predicate,
        [string]$Message
    )

    $value = if ($Values.ContainsKey($Key)) {
        [string]$Values[$Key]
    }
    else {
        ""
    }

    if (-not (& $Predicate $value)) {
        throw "$Key inválido: $Message"
    }

    Write-Host "$Key=ok"
}

Write-Host "`n=== PRODUCTION READINESS ==="
Write-Host "root=$projectRoot"

if (-not (Test-Path $compose)) {
    throw "$compose não encontrado."
}

if (-not (Test-Path $envFile)) {
    throw "backend/.env.production não encontrado."
}

if (git ls-files backend/.env.production) {
    throw "backend/.env.production está versionado."
}

Write-Host "`n=== COMPOSE ==="

docker compose -f $compose config --quiet

if ($LASTEXITCODE -ne 0) {
    throw "compose.production.yaml inválido."
}

$services = @(
    docker compose -f $compose config --services
)

foreach ($required in @(
    "backend",
    "worker",
    "scheduler",
    "nginx",
    "postgres"
)) {
    if ($services -notcontains $required) {
        throw "Serviço obrigatório ausente: $required"
    }

    Write-Host "service-$required=ok"
}

Write-Host "`n=== PRODUCTION ENV CONTRACT ==="

$env = Read-DotEnv $envFile

Assert-Value $env "APP_ENV" {
    param($v) $v -eq "production"
} "deve ser production"

Assert-Value $env "APP_DEBUG" {
    param($v) $v.ToLowerInvariant() -eq "false"
} "deve ser false"

Assert-Value $env "APP_KEY" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -ne "base64:"
} "deve conter uma chave real"

Assert-Value $env "APP_URL" {
    param($v) $v.StartsWith("https://")
} "deve começar com https://"

Assert-Value $env "POSTGRES_DB" {
    param($v) -not [string]::IsNullOrWhiteSpace($v)
} "obrigatório"

Assert-Value $env "POSTGRES_USER" {
    param($v) -not [string]::IsNullOrWhiteSpace($v)
} "obrigatório"

Assert-Value $env "POSTGRES_PASSWORD" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -notmatch "change_me|example|password"
} "use segredo forte e não-placeholder"

Assert-Value $env "APP_DB_USER" {
    param($v) -not [string]::IsNullOrWhiteSpace($v)
} "obrigatório"

Assert-Value $env "APP_DB_PASSWORD" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -notmatch "change_me|example|password"
} "use segredo forte e não-placeholder"

Assert-Value $env "DB_CONNECTION" {
    param($v) $v -eq "pgsql"
} "deve ser pgsql"

Assert-Value $env "DB_HOST" {
    param($v) $v -eq "postgres"
} "deve apontar para o serviço postgres"

Assert-Value $env "DB_DATABASE" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -eq [string]$env["POSTGRES_DB"]
} "deve coincidir com POSTGRES_DB"

Assert-Value $env "DB_USERNAME" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -eq [string]$env["APP_DB_USER"]
} "deve coincidir com APP_DB_USER"

Assert-Value $env "DB_PASSWORD" {
    param($v)
    -not [string]::IsNullOrWhiteSpace($v) -and
    $v -eq [string]$env["APP_DB_PASSWORD"]
} "deve coincidir com APP_DB_PASSWORD"

Assert-Value $env "SESSION_DRIVER" {
    param($v) $v -eq "database"
} "deve ser database"

Assert-Value $env "SESSION_SECURE_COOKIE" {
    param($v) $v.ToLowerInvariant() -eq "true"
} "deve ser true em HTTPS"

Assert-Value $env "SESSION_HTTP_ONLY" {
    param($v) $v.ToLowerInvariant() -eq "true"
} "deve ser true"

Assert-Value $env "MAIL_MAILER" {
    param($v) $v -in @(
        "smtp",
        "resend"
    )
} "deve ser smtp ou resend"

foreach ($key in @(
    "MAIL_FROM_ADDRESS",
    "MARKETING_CONTACT_EMAIL"
)) {
    Assert-Value $env $key {
        param($v) -not [string]::IsNullOrWhiteSpace($v)
    } "obrigatório antes do go-live"
}

if ($env["MAIL_MAILER"] -eq "smtp") {
    foreach ($key in @(
        "MAIL_HOST",
        "MAIL_PORT"
    )) {
        Assert-Value $env $key {
            param($v) -not [string]::IsNullOrWhiteSpace($v)
        } "obrigatório para SMTP"
    }
}

if ($env["MAIL_MAILER"] -eq "resend") {
    Assert-Value $env "RESEND_API_KEY" {
        param($v) -not [string]::IsNullOrWhiteSpace($v)
    } "obrigatório para Resend"
}

Write-Host "`n=== STATIC DEPLOY CONTRACT ==="

$deploy = Get-Content ".\scripts\deploy-production.ps1" -Raw -Encoding UTF8

foreach ($needle in @(
    "build backend worker scheduler nginx",
    "up -d --force-recreate backend worker scheduler nginx",
    "nossa-plataforma-production-scheduler-1"
)) {
    if (-not $deploy.Contains($needle)) {
        throw "Deploy não contém contrato obrigatório: $needle"
    }
}

Write-Host "scheduler-deploy=ok"

$backup = Get-Content ".\scripts\backup-production.ps1" -Raw -Encoding UTF8

if (
    $backup.Contains("psql -U postgres -d nossa_plataforma") -or
    $backup.Contains("database = `"nossa_plataforma`"")
) {
    throw "Backup ainda contém credenciais/database hardcoded."
}

Write-Host "backup-env-resolution=ok"

Write-Host "`n=== AUTOMATED READINESS GREEN ===" -ForegroundColor Green
Write-Host "PRODUCTION_READINESS_OK"
Write-Host ""
Write-Host "Ainda são obrigatórios fora deste script:"
Write-Host "- DNS principal e wildcard;"
Write-Host "- TLS/HTTPS válido no proxy/load balancer;"
Write-Host "- teste real de envio SMTP;"
Write-Host "- backup + restore em ambiente de homologação;"
Write-Host "- monitoramento externo;"
Write-Host "- provider de pagamento real antes de cobrança automática;"
Write-Host "- credenciais reais das integrações efetivamente habilitadas;"
Write-Host "- revisão jurídica dos textos públicos."
