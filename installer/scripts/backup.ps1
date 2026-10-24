# Perfect LPG - daily database backup, run by the "Perfect LPG Daily Backup" scheduled task as SYSTEM.
# Dumps land in <InstallDir>\backups, where the application's Backup screen also lists them.
param(
    [Parameter(Mandatory = $true)][string]$InstallDir,
    [int]$KeepDays = 30
)

$ErrorActionPreference = 'Stop'
$InstallDir = $InstallDir.TrimEnd('\')
$BackupDir = Join-Path $InstallDir 'backups'
$RootCnf = Join-Path $InstallDir 'config\mysql-root.cnf'
$Dump = Join-Path $InstallDir 'mysql\bin\mysqldump.exe'
$Log = Join-Path $InstallDir 'logs\backup.log'

$file = Join-Path $BackupDir ('auto-perfect_lpg-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.sql')
try {
    # 'Continue' here: Windows PowerShell 5.1 turns redirected stderr into errors, fatal under 'Stop'.
    $ErrorActionPreference = 'Continue'
    & $Dump "--defaults-extra-file=$RootCnf" --single-transaction --routines --triggers --events --hex-blob --default-character-set=utf8mb4 "--result-file=$file" perfect_lpg 2>&1 | Out-Null
    $ErrorActionPreference = 'Stop'
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $file) -or (Get-Item $file).Length -lt 1024) { throw 'mysqldump failed or produced an empty file.' }
    Get-ChildItem $BackupDir -Filter 'auto-perfect_lpg-*.sql' | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } | Remove-Item -Force
    Add-Content $Log ((Get-Date -Format s) + ' OK ' + $file)
} catch {
    if (Test-Path $file) { Remove-Item $file -Force }
    Add-Content $Log ((Get-Date -Format s) + ' FAILED ' + $_.Exception.Message)
    exit 1
}
