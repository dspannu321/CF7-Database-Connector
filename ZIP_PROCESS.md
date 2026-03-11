$pluginDir = "E:\personal\software\php\wordpress plugins\CF7-Database-Connector"
$zipPath = "E:\personal\software\php\wordpress plugins\CF7-Database-Connector.zip"
$rootName = "CF7-Database-Connector"
$excludeNames = @('.git', 'formbridge-cursor-development-brief.md', 'FORMBRIDGE_IMPROVEMENTS.md','ZIP_PROCESS.md', 'FORMBRIDGE_PROGRESS.md', 'PRE_DEPLOYMENT_CHECKLIST.md','PLUGIN_CHECK.md')
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')
try {
    Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object {
        $rel = $_.FullName.Substring($pluginDir.Length).TrimStart('\').Replace('\', '/')
        $parts = $rel.Split('/')
        $excluded = $false
        foreach ($p in $parts) { if ($excludeNames -contains $p) { $excluded = $true; break } }
        -not $excluded
    } | ForEach-Object {
        $rel = $_.FullName.Substring($pluginDir.Length).TrimStart('\').Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rootName + '/' + $rel, 'Optimal') | Out-Null
    }
} finally { $zip.Dispose() }
Write-Host "Zip created: $zipPath"