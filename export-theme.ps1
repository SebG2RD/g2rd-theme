$ErrorActionPreference = 'Stop'

# PSScriptRoot = dossier du theme
$source  = $PSScriptRoot
$tempDir = 'C:\Temp\G2RD-export'
$dest    = Join-Path $tempDir 'g2rd-theme'
$zipPath = Join-Path (Split-Path $source -Parent) 'g2rd-theme.zip'

Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $dest -Force | Out-Null
Write-Host ('Source : ' + $source)
Write-Host ('ZIP    : ' + $zipPath)

$excludedFileNames = @(
    'package.json', 'package-lock.json', 'webpack.config.js',
    'skills-lock.json', 'CLAUDE.md', '.gitignore', '.gitattributes',
    'export-theme.ps1'
)
$excludedFolders = @('node_modules', '.git', '.claude', '.agents')

$files   = Get-ChildItem -Path $source -Recurse -File
$total   = $files.Count
$copied  = 0
$skipped = 0

foreach ($file in $files) {
    $rel   = $file.FullName.Substring($source.Length + 1)
    $parts = $rel -split '\\'

    # Exclure les dossiers de developpement
    $skip = $false
    foreach ($part in $parts) {
        if ($excludedFolders -contains $part) { $skip = $true; break }
    }
    if ($skip) { $skipped++; continue }

    # Exclure blocks/<Bloc>/src/
    if ($parts.Count -ge 3 -and $parts[0] -eq 'blocks' -and $parts[2] -eq 'src') {
        $skipped++; continue
    }

    # Exclure les fichiers de dev par nom
    if ($excludedFileNames -contains $file.Name) { $skipped++; continue }

    # Exclure les source maps
    if ($file.Extension -eq '.map') { $skipped++; continue }

    # Copier le fichier en preservant l'arborescence
    $target    = Join-Path $dest $rel
    $targetDir = Split-Path $target -Parent
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
    Copy-Item -Path $file.FullName -Destination $target -Force
    $copied++
}

Write-Host ('Copies : ' + $copied + '  Ignores : ' + $skipped + '  Total : ' + $total)

# Supprimer l'ancien ZIP
Remove-Item $zipPath -Force -ErrorAction SilentlyContinue

# Creer le ZIP avec forward slashes (requis pour Linux / WordPress)
# Compress-Archive utilise des antislashes Windows — incompatible avec les serveurs Linux.
# On utilise directement l'API .NET ZipFile pour garantir les forward slashes.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipStream = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
$destFiles = Get-ChildItem -Path $dest -Recurse -File
foreach ($f in $destFiles) {
    # Chemin relatif depuis le dossier dest, converti en forward slashes
    $relPath   = $f.FullName.Substring($dest.Length + 1).Replace('\', '/')
    $entryName = "g2rd-theme/$relPath"
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zipStream, $f.FullName, $entryName,
        [System.IO.Compression.CompressionLevel]::Optimal
    ) | Out-Null
}
$zipStream.Dispose()

# Verifier la structure interne du ZIP
$verifyZip     = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$styleEntry    = $verifyZip.Entries | Where-Object { $_.FullName -eq 'g2rd-theme/style.css' }
$verifyZip.Dispose()

if ($styleEntry) {
    Write-Host 'Structure ZIP verifiee : g2rd-theme/style.css present (forward slashes)' -ForegroundColor Green
} else {
    Write-Host 'ERREUR : style.css introuvable dans le ZIP !' -ForegroundColor Red
    exit 1
}

$size = (Get-Item $zipPath).Length / 1MB
Write-Host ('Taille ZIP : ' + ('{0:N2}' -f $size) + ' MB')

Remove-Item -Path $tempDir -Recurse -Force
Write-Host ('Termine. ZIP disponible : ' + $zipPath)
