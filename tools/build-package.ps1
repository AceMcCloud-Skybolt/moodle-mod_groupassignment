param(
    [string]$OutputPath = "dist\groupassign.zip"
)

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$stagingRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("groupassign-package-" + [System.Guid]::NewGuid().ToString("N"))
$pluginRoot = Join-Path $stagingRoot "groupassign"
$outputFullPath = Join-Path $repoRoot $OutputPath

$excludeDirectories = @(
    ".git",
    "dist",
    "docs",
    "tools"
)

$excludeFiles = @(
    ".gitignore"
)

New-Item -ItemType Directory -Force -Path $pluginRoot | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path $outputFullPath -Parent) | Out-Null

try {
    Get-ChildItem -LiteralPath $repoRoot -Force | ForEach-Object {
        if ($_.PSIsContainer -and $excludeDirectories -contains $_.Name) {
            return
        }
        if (-not $_.PSIsContainer -and $excludeFiles -contains $_.Name) {
            return
        }

        Copy-Item -LiteralPath $_.FullName -Destination $pluginRoot -Recurse -Force
    }

    if (Test-Path -LiteralPath $outputFullPath) {
        Remove-Item -LiteralPath $outputFullPath -Force
    }

    Compress-Archive -Path $pluginRoot -DestinationPath $outputFullPath -CompressionLevel Optimal
    Write-Host "Created $outputFullPath"
} finally {
    if (Test-Path -LiteralPath $stagingRoot) {
        Remove-Item -LiteralPath $stagingRoot -Recurse -Force
    }
}
