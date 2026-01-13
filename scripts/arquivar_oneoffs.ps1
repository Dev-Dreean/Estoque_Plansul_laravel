$files = @(
    'docs/SINCRONIZACAO_UF_2025-12-11.txt',
    'scripts/check_local_uf.php',
    'scripts/sync_uf_kinghost.sh',
    'scripts/sync_uf_kinghost_simple.php',
    'app/Http/Controllers/PatrimonioController.php.bak',
    'c:\temp\update_uf_kinghost.sql'
)

$existing = $files | Where-Object { Test-Path $_ }
if ($existing.Count -eq 0) {
    Write-Output 'NO_FILES_TO_ARCHIVE'
    exit 0
}

$ts = Get-Date -Format 'yyyy-MM-dd_HHmm'
$archiveDir = 'archive/backups'
if (-not (Test-Path $archiveDir)) { New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null }
$dest = Join-Path $archiveDir ("pre_action_$ts.zip")

Write-Output "Archiving $($existing.Count) files to $dest"
Compress-Archive -Path $existing -DestinationPath $dest -Force

# Remove originals (if present)
foreach ($f in $existing) {
    try { Remove-Item -LiteralPath $f -Force -ErrorAction SilentlyContinue } catch {}
}

# Stage git changes and commit if any
git add -A
$porcelain = git status --porcelain
if ($porcelain -ne '') {
    git commit -m "chore: archive one-off UF sync files ($ts)"
    git push origin main
    Write-Output "GIT_COMMIT_DONE"
}
else {
    Write-Output "NO_GIT_CHANGES"
}

Write-Output "ARCHIVE_PATH=$dest"

exit 0
