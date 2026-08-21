@echo off
setlocal

cd /d "%~dp0"

if "%~1"=="" (
    echo Uso:
    echo validate-production-restore.cmd backups\YYYYMMDD-HHMMSS
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\validate-production-restore.ps1" -BackupDirectory "%~1"

exit /b %ERRORLEVEL%