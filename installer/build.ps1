# Builds installer\output\PerfectLPG-Setup-<version>.exe. The installer carries only the application
# (tracked + new, non-ignored files, with production Composer dependencies); Apache, PHP, MySQL and
# the VC++ runtime are downloaded on the client PC during setup (see scripts\resolve.ps1).
# Needs: git, composer, Inno Setup 6 (winget install JRSoftware.InnoSetup).
$ErrorActionPreference = 'Stop'

$Here = $PSScriptRoot
$Repo = Split-Path $Here -Parent
$Stage = Join-Path $Here '.stage'

function Step([string]$text) { Write-Host ('==> ' + $text) -ForegroundColor Cyan }

$iscc = @("${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe", "$env:ProgramFiles\Inno Setup 6\ISCC.exe", "$env:LOCALAPPDATA\Programs\Inno Setup 6\ISCC.exe") |
    Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $iscc) { throw 'Inno Setup 6 was not found. Install it with: winget install JRSoftware.InnoSetup' }

if (Test-Path $Stage) { Remove-Item $Stage -Recurse -Force }
$appStage = Join-Path $Stage 'app'

Step 'Staging the application'
$exclude = '^(tests|installer|node_modules|docs|\.github|\.githooks)/|^(package(-lock)?\.json|playwright\.config\.mjs|phpunit\.dist\.xml|env|\.gitattributes|\.gitignore)$|^[^/]+\.md$'
Push-Location $Repo
try {
    $tracked = @(git ls-files)
    $files = @(git ls-files --cached --others --exclude-standard) | Where-Object {
        ($_ -notmatch $exclude) -and (($_ -notlike 'writable/*') -or ($tracked -contains $_))
    }
    foreach ($f in $files) {
        if (-not (Test-Path -LiteralPath $f -PathType Leaf)) { continue }
        $dest = Join-Path $appStage $f
        New-Item -ItemType Directory -Force (Split-Path $dest -Parent) | Out-Null
        Copy-Item -LiteralPath $f $dest
    }
    $commit = (git rev-parse --short HEAD).Trim()
    $dirty = [bool](git status --porcelain --untracked-files=no)
} finally { Pop-Location }

# Composer reports progress on stderr, which Windows PowerShell 5.1 turns into errors when output is redirected.
$ErrorActionPreference = 'Continue'
& composer install --working-dir $appStage --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist
$ErrorActionPreference = 'Stop'
if ($LASTEXITCODE -ne 0) { throw 'composer install failed.' }
Copy-Item (Join-Path $Repo 'public\favicon.ico') (Join-Path $Stage 'app.ico')

$version = (Get-Date -Format 'yyyy.M.d') + '.' + [int](Get-Date -Format 'HHmm')
Step ('Compiling setup ' + $version + ' (' + $commit + $(if ($dirty) { ', uncommitted changes' } else { '' }) + ')')
& $iscc /Qp "/DAppVersion=$version" "/DStage=$Stage" (Join-Path $Here 'PerfectLPG.iss')
if ($LASTEXITCODE -ne 0) { throw 'Inno Setup compile failed.' }

$exe = Join-Path $Here ('output\PerfectLPG-Setup-' + $version + '.exe')
Step ('Built ' + $exe + ' (' + [math]::Round((Get-Item $exe).Length / 1MB, 1) + ' MB)')
