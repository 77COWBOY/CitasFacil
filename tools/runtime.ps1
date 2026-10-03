$ErrorActionPreference = 'Stop'
$ProjectRoot = [IO.Path]::GetFullPath($ProjectRoot).TrimEnd('\','/')
$localPath = Join-Path $ProjectRoot '.local'
$php = Join-Path $ProjectRoot 'runtime\php\php.exe'
$mysql = Join-Path $ProjectRoot 'runtime\mysql\bin\mysqld.exe'
if (!(Test-Path -LiteralPath $php) -or !(Test-Path -LiteralPath $mysql)) { throw 'Falta la carpeta runtime. Copia el proyecto completo.' }
New-Item -ItemType Directory -Force $localPath,(Join-Path $localPath 'sessions') | Out-Null
Set-Location -LiteralPath $ProjectRoot
$extensionPath = 'runtime/php/ext'
$sessionPath = (Join-Path $localPath 'sessions').Replace('\','/')
$ini = Join-Path $localPath 'php.ini'
@"
extension_dir="$extensionPath"
extension=mysqli
extension=mbstring
extension=curl
date.timezone=America/Managua
session.save_path="$sessionPath"
session.use_strict_mode=1
session.cookie_httponly=1
display_errors=Off
log_errors=On
default_charset=UTF-8
"@ | Set-Content -LiteralPath $ini -Encoding UTF8
$phpArgs = @('-c',$ini)
function Get-WebOwner([int]$ListenPort) {
    $listeners = @(Get-NetTCPConnection -State Listen -LocalPort $ListenPort -ErrorAction SilentlyContinue)
    foreach ($listener in $listeners) {
        $proc = Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
        if ($proc.ExecutablePath -ne $php -or !$proc.CommandLine.Contains('"' + (Join-Path $ProjectRoot 'router.php') + '"')) { throw "El puerto $ListenPort pertenece a otro programa o a otra copia. Usa iniciar.ps1 -Port 8081." }
        $proc
    }
}
