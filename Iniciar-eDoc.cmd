@echo off
setlocal
set "EDOC_LAUNCH_ROOT=%~dp0"
powershell.exe -NoProfile -Command "& ([ScriptBlock]::Create([IO.File]::ReadAllText((Join-Path $env:EDOC_LAUNCH_ROOT 'iniciar.ps1'))))" %*
if errorlevel 1 (
  pause
  exit /b 1
)
