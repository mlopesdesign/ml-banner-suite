# scripts/package.ps1
# Empacota a pasta ml-banner-suite/ em ml-banner-suite-vX.Y.Z.zip pronto pra GitHub Release.
# Estrutura esperada: pasta interna SOMENTE ml-banner-suite/ (sem wrapper, sem build dirs).
# Separador de entrada: '/' (ZIP spec), LF nos arquivos .sh.

[CmdletBinding()]
param(
    [string]$PluginSlug = 'ml-banner-suite'
)

$ErrorActionPreference = 'Stop'

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$RootDir = (Resolve-Path "$ScriptDir\..").Path
$SrcDir = Join-Path $RootDir $PluginSlug

if (-not (Test-Path -LiteralPath $SrcDir -PathType Container)) {
    throw "ERRO: pasta $SrcDir nao encontrada."
}

# Detecta versao do header do plugin
$MainFile = Join-Path $SrcDir "$PluginSlug.php"
$content = Get-Content -LiteralPath $MainFile -Raw -Encoding UTF8
$versionMatch = [regex]::Match($content, '(?m)^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)')
if (-not $versionMatch.Success) {
    throw "ERRO: nao foi possivel detectar a versao no header."
}
$Version = $versionMatch.Groups[1].Value

$OutFile = Join-Path $RootDir "$PluginSlug-v$Version.zip"
Write-Host "Empacotando $PluginSlug v$Version..."

if (Test-Path -LiteralPath $OutFile) {
    Remove-Item -LiteralPath $OutFile -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open($OutFile, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    $files = Get-ChildItem -LiteralPath $SrcDir -Recurse -File -Force
    foreach ($f in $files) {
        # Relative path inside src, normalizado pra '/'
        $rel = $f.FullName.Substring($SrcDir.Length).TrimStart('\', '/')
        $entryName = "$PluginSlug/$rel" -replace '\\', '/'

        # Pula arquivos dev/ocultos irrelevantes
        if ($f.Name -in @('.DS_Store', 'Thumbs.db')) { continue }
        if ($f.Name -like '*~') { continue }

        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive, $f.FullName, $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
} finally {
    $archive.Dispose()
}

$hash = (Get-FileHash -LiteralPath $OutFile -Algorithm SHA256).Hash
$size = (Get-Item -LiteralPath $OutFile).Length
Write-Host "OK: $OutFile"
Write-Host "SHA-256: $hash"
Write-Host "Size: $size bytes"