# Script per aggiungere e committare tutte le modifiche
cd "C:\Users\franc\OneDrive\Desktop\FP-Newspaper"

Write-Host "=== FP-NEWSPAPER GIT COMMIT ===" -ForegroundColor Cyan
Write-Host ""

# Verifica repository
if (-not (Test-Path .git)) {
    Write-Host "⚠ Repository Git non trovato!" -ForegroundColor Red
    exit 1
}

# Stato iniziale
Write-Host "📊 STATO INIZIALE:" -ForegroundColor Yellow
git status --short
Write-Host ""

# Aggiungi tutti i file
Write-Host "➕ Aggiunta file..." -ForegroundColor Cyan
git add -A

# Nuovo stato
Write-Host "`n📊 STATO DOPO ADD:" -ForegroundColor Yellow
git status --short
Write-Host ""

# Commit
Write-Host "💾 Creazione commit..." -ForegroundColor Cyan
$commitMsg = "docs: Organizza documentazione e sposta audit reports in docs/audits

- Spostati tutti gli audit reports in docs/audits/
- Aggiunti CHANGELOG.md, CONTRIBUTING.md, DOCUMENTATION.md
- Aggiunti LICENSE e readme.txt per WordPress.org
- Aggiornato README.md con documentazione completa
- Aggiunta sezione docs/audits/README.md

Closes: organizzazione documentazione"
git commit -m $commitMsg

# Risultato finale
Write-Host "`n✅ COMMIT COMPLETATO!" -ForegroundColor Green
Write-Host "`n📋 ULTIMO COMMIT:" -ForegroundColor Yellow
git log -1 --oneline


