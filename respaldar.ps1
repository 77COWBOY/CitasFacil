if (!$PSScriptRoot) { $ProjectRoot = $env:EDOC_LAUNCH_ROOT } else { $ProjectRoot = $PSScriptRoot }
if (!$ProjectRoot) { throw 'Abre el archivo CMD para iniciar.' }
. ([ScriptBlock]::Create([IO.File]::ReadAllText((Join-Path $ProjectRoot 'tools\runtime.ps1'))))
& $php @phpArgs (Join-Path $ProjectRoot 'tools\backup.php')
if ($LASTEXITCODE -ne 0) { throw 'No se pudo respaldar. Inicia eDoc primero.' }
