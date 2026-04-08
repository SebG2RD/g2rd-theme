$source  = 'c:\Users\gerar\Downloads\Developpement Web\G2RD-theme'
$tempDir = 'C:\Temp\G2RD-export'
$dest    = "$tempDir\G2RD-theme"
$zipPath = 'c:\Users\gerar\Downloads\G2RD-theme.zip'

Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $dest -Force | Out-Null
Write-Host 'Temp dir created.'

$excludedFileNames = @(
    'package.json',
    'package-lock.json',
    'webpack.config.js',
    'skills-lock.json',
    'CLAUDE.md',
    '.gitignore',
    '.gitattributes',
    'export-theme.ps1'
)

$excludedFolders = @('node_modules', '.git', '.claude')

$files   = Get-ChildItem -Path $source -Recurse -File
$total   = $files.Count
$copied  = 0
$skipped = 0

foreach ($file in $files) {
    $rel   = $file.FullName.Substring($source.Length + 1)
    $parts = $rel -split '\\'

    $skip = $false
    foreach ($part in $parts) {
        if ($excludedFolders -contains $part) { $skip = $true; break }
    }
    if ($skip) { $skipped++; continue }

    if ($parts.Count -ge 3 -and $parts[0] -eq 'blocks' -and $parts[2] -eq 'src') {
        $skipped++; continue
    }

    if ($excludedFileNames -contains $file.Name) { $skipped++; continue }
    if ($file.Extension -eq '.map') { $skipped++; continue }

    $target    = Join-Path $dest $rel
    $targetDir = Split-Path $target -Parent
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
    Copy-Item -Path $file.FullName -Destination $target -Force
    $copied++
}

Write-Host "Copied: $copied  Skipped: $skipped  Total: $total"

Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
Compress-Archive -Path $dest -DestinationPath $zipPath -Force

$size = (Get-Item $zipPath).Length / 1MB
Write-Host ("ZIP size: {0:N2} MB" -f $size)

Remove-Item -Path $tempDir -Recurse -Force
Write-Host "Done: $zipPath"
