$ts = Get-Date -Format 'yyyy-MM-dd_HHmm'
$dest = "archive/backups/pre_action_$ts.zip"
$paths = @('app','resources','routes','database','config','artisan','composer.json','package.json','tailwind.config.js','vite.config.js')
Compress-Archive -Path $paths -DestinationPath $dest -Force
Write-Host $dest
