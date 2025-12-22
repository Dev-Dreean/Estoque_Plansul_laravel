$st = git status --porcelain
if ($st) {
  git add .
  git commit -m "fix: UTF-8 e ícones (patrimonio)"
  git push origin main
} else {
  Write-Host "NO_CHANGES"
}
