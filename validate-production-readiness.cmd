@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\validate-production-readiness.ps1"
