param([int]$Port = 0)
if (!$PSScriptRoot) { $ProjectRoot = $env:EDOC_LAUNCH_ROOT } else { $ProjectRoot = $PSScriptRoot }
if (!$ProjectRoot) { throw 'Abre el archivo CMD para iniciar.' }
. ([ScriptBlock]::Create([IO.File]::ReadAllText((Join-Path $ProjectRoot 'tools\runtime.ps1'))))
if (!$Port) { $Port=8080; if (Test-Path (Join-Path $localPath 'web-port.txt')) { $Port=[int](Get-Content (Join-Path $localPath 'web-port.txt')) } }
$web = @(Get-WebOwner $Port)
$status = & $php @phpArgs (Join-Path $ProjectRoot 'tools\db-status.php')
if ($LASTEXITCODE -eq 0) {
    & $php @phpArgs (Join-Path $ProjectRoot 'tools\backup.php')
    if ($LASTEXITCODE -ne 0) { throw 'Fallo el respaldo. No se detuvo el sistema.' }
    $info=$status | ConvertFrom-Json
    $expected=[IO.Path]::GetFullPath((Join-Path $localPath 'mysql\data')).TrimEnd('\','/')
    $actual=[IO.Path]::GetFullPath($info.datadir).TrimEnd('\','/')
    if ($actual -eq $expected -and [int]$info.port -eq 3308) {
        & $php @phpArgs (Join-Path $ProjectRoot 'tools\stop-db.php')
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo detener la base local.' }
        for ($i=0; $i -lt 60; $i++) {
            if (!(Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue)) { break }
            Start-Sleep -Milliseconds 500
        }
        if (Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue) { throw 'La base sigue cerrandose; espera antes de copiar la carpeta.' }
    } else { Write-Host 'La base externa se conserva encendida.' }
}
foreach ($proc in $web) { Stop-Process -Id $proc.ProcessId }
Write-Host 'eDoc detenido. Ya puedes copiar la carpeta completa.'
