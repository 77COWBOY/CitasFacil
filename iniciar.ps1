param([ValidateRange(1024,65535)][int]$Port = 8080, [switch]$NoBrowser)
if (!$PSScriptRoot) { $ProjectRoot = $env:EDOC_LAUNCH_ROOT } else { $ProjectRoot = $PSScriptRoot }
if (!$ProjectRoot) { throw 'Abre el archivo CMD para iniciar.' }
. ([ScriptBlock]::Create([IO.File]::ReadAllText((Join-Path $ProjectRoot 'tools\runtime.ps1'))))
$lock = $null
try {
    try { $lock = [IO.File]::Open((Join-Path $localPath 'startup.lock'),'OpenOrCreate','ReadWrite','None') } catch { throw 'Ya hay un inicio en curso. Espera unos segundos.' }
    $web = @(Get-WebOwner $Port)
    & $php @phpArgs (Join-Path $ProjectRoot 'tools\requirements.php')
    if ($LASTEXITCODE -ne 0) { throw 'PHP no cumple los requisitos. Revisa la carpeta runtime.' }
    $dbData = Join-Path $localPath 'mysql\data'
    $statusTool = Join-Path $ProjectRoot 'tools\db-status.php'
    $statusText = & $php @phpArgs $statusTool
    $ready = $LASTEXITCODE -eq 0
    $external = $false
    foreach ($key in @('HOST','PORT','USER','PASSWORD','NAME')) { if ([Environment]::GetEnvironmentVariable('EDOC_DB_'+$key)) { $external=$true } }
    if (!$ready) {
        if ($external) { throw 'No se puede conectar con EDOC_DB_*. Inicia el servidor configurado o elimina esas variables.' }
        if (Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue) { throw 'El puerto 3308 esta ocupado por otra base de datos.' }
        New-Item -ItemType Directory -Force (Split-Path $dbData) | Out-Null
        if (!(Test-Path -LiteralPath (Join-Path $dbData 'ibdata1'))) {
            if ((Test-Path -LiteralPath $dbData) -and @(Get-ChildItem -LiteralPath $dbData -Force).Count) { throw 'Hay datos incompletos en .local\mysql\data. Se conservaron: no se reinicializaran.' }
            Write-Host 'Preparando la base local por primera vez...'
            $install = Join-Path $ProjectRoot 'runtime\mysql\bin\mysql_install_db.exe'
            & $install "--datadir=.local/mysql/data" --port=3308
            if ($LASTEXITCODE -ne 0) { throw 'No se pudo inicializar MariaDB.' }
        }
        $base = (Join-Path $ProjectRoot 'runtime\mysql').Replace('\','/')
        $dbArgs = '--no-defaults --basedir="{0}" --datadir="{1}" --port=3308 --bind-address=127.0.0.1 --log-error="{2}"' -f $base,$dbData.Replace('\','/'),'mysql-error.log'
        $dbProcess = Start-Process -FilePath $mysql -ArgumentList $dbArgs -WorkingDirectory $ProjectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $localPath 'mysql\stdout.log') -RedirectStandardError (Join-Path $localPath 'mysql\stderr.log')
        for ($i=0; $i -lt 60; $i++) {
            Start-Sleep -Milliseconds 500
            $statusText = & $php @phpArgs $statusTool
            if ($LASTEXITCODE -eq 0) { $ready=$true; break }
            if ($dbProcess.HasExited) { break }
        }
        if (!$ready) { throw 'MariaDB no inicio. Revisa .local\mysql\data\mysql-error.log.' }
    }
    $status = $statusText | ConvertFrom-Json
    if ([int]$status.recovery -ne 0 -or [int]$status.read_only -ne 0) { throw 'La base esta en recuperacion o solo lectura.' }
    if (!$external) {
        $actual = [IO.Path]::GetFullPath($status.datadir).TrimEnd('\','/')
        $expected = [IO.Path]::GetFullPath($dbData).TrimEnd('\','/')
        if ($actual -ne $expected) { throw 'El puerto 3308 no corresponde a esta carpeta.' }
    }
    & $php @phpArgs (Join-Path $ProjectRoot 'tools\setup.php')
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar la base. Se conservaron los datos existentes.' }
    if (!$web.Count) {
        $webArgs = '-c "{0}" -S 127.0.0.1:{1} -t "{2}" "{3}"' -f $ini,$Port,$ProjectRoot,(Join-Path $ProjectRoot 'router.php')
        $webProcess = Start-Process -FilePath $php -ArgumentList $webArgs -WorkingDirectory $ProjectRoot -WindowStyle Hidden -PassThru -RedirectStandardError (Join-Path $localPath 'server.log') -RedirectStandardOutput (Join-Path $localPath 'server-output.log')
    }
    $healthy=$false
    for ($i=0; $i -lt 10; $i++) {
        if ($webProcess -and $webProcess.HasExited) { break }
        try { $response=Invoke-WebRequest "http://127.0.0.1:$Port/login.php" -UseBasicParsing -TimeoutSec 2; if ($response.StatusCode -eq 200) { $healthy=$true; break } } catch {}
        Start-Sleep -Milliseconds 300
    }
    if (!$healthy) { throw 'El servidor web no responde. Revisa .local\server.log.' }
    Set-Content -LiteralPath (Join-Path $localPath 'web-port.txt') -Value $Port
    Write-Host "eDoc listo: http://127.0.0.1:$Port"
    if (!$NoBrowser) { Start-Process "http://127.0.0.1:$Port" }
} finally { if ($lock) { $lock.Dispose() } }
