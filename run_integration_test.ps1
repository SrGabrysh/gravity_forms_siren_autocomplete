# Script PowerShell pour exécuter les tests d'intégration
# Usage: .\run_integration_test.ps1

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  Tests d'Intégration - API Siren                           ║" -ForegroundColor Cyan
Write-Host "║  Gravity Forms Siren Autocomplete                          ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Vérifier si la clé API est définie dans les variables d'environnement
if (-not $env:GF_SIREN_API_KEY) {
    Write-Host "⚠️  La clé API Siren n'est pas définie." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Pour exécuter ce test, vous devez définir votre clé API:" -ForegroundColor White
    Write-Host ""
    Write-Host "  Option 1 - Variable d'environnement temporaire:" -ForegroundColor Green
    Write-Host "    `$env:GF_SIREN_API_KEY = 'votre_cle_api'" -ForegroundColor Gray
    Write-Host "    .\run_integration_test.ps1" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  Option 2 - Variable d'environnement permanente:" -ForegroundColor Green
    Write-Host "    [System.Environment]::SetEnvironmentVariable('GF_SIREN_API_KEY', 'votre_cle_api', 'User')" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  Option 3 - Modification du fichier test_api_integration.php:" -ForegroundColor Green
    Write-Host "    Éditez la ligne 20 et remplacez 'VOTRE_CLE_API' par votre clé" -ForegroundColor Gray
    Write-Host ""
    
    $response = Read-Host "Voulez-vous entrer votre clé API maintenant ? (O/N)"
    if ($response -eq 'O' -or $response -eq 'o') {
        $apiKey = Read-Host "Entrez votre clé API Siren" -AsSecureString
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($apiKey)
        $env:GF_SIREN_API_KEY = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
        Write-Host "✓ Clé API définie pour cette session" -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "❌ Test annulé. Définissez d'abord la clé API." -ForegroundColor Red
        exit 1
    }
}

Write-Host "🔑 Clé API détectée: ****" -NoNewline
Write-Host ($env:GF_SIREN_API_KEY.Substring([Math]::Max($env:GF_SIREN_API_KEY.Length - 4, 0))) -ForegroundColor Green
Write-Host ""

# Exécuter le test
Write-Host "🚀 Lancement des tests d'intégration..." -ForegroundColor Cyan
Write-Host ""

php test_api_integration.php

$exitCode = $LASTEXITCODE

Write-Host ""
if ($exitCode -eq 0) {
    Write-Host "✅ Tests terminés avec succès !" -ForegroundColor Green
} else {
    Write-Host "❌ Les tests ont échoué (code: $exitCode)" -ForegroundColor Red
}

exit $exitCode

