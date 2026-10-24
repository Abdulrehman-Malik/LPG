# Perfect LPG - run by the uninstaller. Removes the services, backup task, firewall rule and shortcuts.
# The database (data\), backups\, config\ and app\.env are kept, so reinstalling reconnects to the same data.
param([Parameter(Mandatory = $true)][string]$InstallDir)

$ErrorActionPreference = 'Continue'
$InstallDir = $InstallDir.TrimEnd('\')

foreach ($name in 'PerfectLPG-Apache', 'PerfectLPG-MySQL') {
    if (Get-Service $name -ErrorAction SilentlyContinue) {
        Stop-Service $name -Force -ErrorAction SilentlyContinue
        & sc.exe delete $name | Out-Null
    }
}
Unregister-ScheduledTask -TaskName 'Perfect LPG Daily Backup' -Confirm:$false -ErrorAction SilentlyContinue
Get-NetFirewallRule -Group 'Perfect LPG' -ErrorAction SilentlyContinue | Remove-NetFirewallRule
Remove-Item (Join-Path $env:PUBLIC 'Desktop\Perfect LPG.url') -ErrorAction SilentlyContinue
Remove-Item (Join-Path $env:ProgramData 'Microsoft\Windows\Start Menu\Programs\Perfect LPG.url') -ErrorAction SilentlyContinue
exit 0
