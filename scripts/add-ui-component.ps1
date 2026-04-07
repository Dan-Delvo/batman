param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$AddArgs
)

$ErrorActionPreference = "Stop"

if (-not $AddArgs -or $AddArgs.Count -eq 0) {
    Write-Error "Usage: npm run ui:add -- <component-or-registry-url>"
    exit 1
}

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$sourceUiRoot = Join-Path $projectRoot "components\\ui"
$targetUiRoot = Join-Path $projectRoot "resources\\js\\components\\ui"

$npxArgs = @("shadcn-vue@latest", "add") + $AddArgs
& cmd /c npx @npxArgs
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

if (-not (Test-Path $sourceUiRoot)) {
    exit 0
}

New-Item -ItemType Directory -Path $targetUiRoot -Force | Out-Null

$files = Get-ChildItem -Path $sourceUiRoot -File -Recurse
foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($sourceUiRoot.Length).TrimStart('\', '/')
    $destinationPath = Join-Path $targetUiRoot $relativePath
    $destinationDir = Split-Path -Path $destinationPath -Parent

    if (-not (Test-Path $destinationDir)) {
        New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    }

    Move-Item -Path $file.FullName -Destination $destinationPath -Force
    Write-Host "Moved: components/ui/$relativePath -> resources/js/components/ui/$relativePath"
}

Get-ChildItem -Path $sourceUiRoot -Directory -Recurse |
    Sort-Object FullName -Descending |
    Where-Object { -not (Get-ChildItem -Path $_.FullName -Force) } |
    Remove-Item -Force

if (-not (Get-ChildItem -Path $sourceUiRoot -Force)) {
    Remove-Item -Path $sourceUiRoot -Force
}

$legacyRoot = Join-Path $projectRoot "components"
if ((Test-Path $legacyRoot) -and -not (Get-ChildItem -Path $legacyRoot -Force | Select-Object -First 1)) {
    Remove-Item -Path $legacyRoot -Force
}
