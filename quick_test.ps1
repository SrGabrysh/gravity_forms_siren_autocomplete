# Quick test - Récupère la clé depuis wp-config.php et lance le test
# Usage: .\quick_test.ps1

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  Quick Test - Récupération automatique de la clé API      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Chercher wp-config.php dans les dossiers parents possibles
$searchPaths = @(
    "..\..\..\wp-config.php",
    "..\..\..\..\wp-config.php",
    "..\..\..\..\..\wp-config.php",
    "E:\Mon Drive\00 - Dev\01 - Codes\Sites web\TB-Formation\dev_plugin_wc_qualiopi_steps\wp-config.php"
)

$wpConfigPath = $null
foreach ($path in $searchPaths) {
    if (Test-Path $path) {
        $wpConfigPath = $path
        break
    }
}

if ($wpConfigPath) {
    Write-Host "✓ Fichier wp-config.php trouvé: $wpConfigPath" -ForegroundColor Green
    
    # Lire le contenu
    $content = Get-Content $wpConfigPath -Raw
    
    # Extraire la clé API
    if ($content -match "define\(\s*'GF_SIREN_API_KEY',\s*'([^']+)'\s*\)") {
        $apiKey = $matches[1]
        $env:GF_SIREN_API_KEY = $apiKey
        
        Write-Host "✓ Clé API récupérée: ****" -NoNewline
        Write-Host ($apiKey.Substring([Math]::Max($apiKey.Length - 4, 0))) -ForegroundColor Green
        Write-Host ""
        Write-Host "🚀 Lancement des tests d'intégration..." -ForegroundColor Cyan
        Write-Host ""
        
        # Lancer le test
        php test_api_integration.php
        
        $exitCode = $LASTEXITCODE
        Write-Host ""
        
        if ($exitCode -eq 0) {
            Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
            Write-Host "║  ✅ TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS !              ║" -ForegroundColor Green
            Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
        } else {
            Write-Host "❌ Les tests ont échoué" -ForegroundColor Red
        }
        
        exit $exitCode
        
    } else {
        Write-Host "❌ Clé API non trouvée dans wp-config.php" -ForegroundColor Red
        Write-Host ""
        Write-Host "Assurez-vous d'avoir ajouté cette ligne dans votre wp-config.php:" -ForegroundColor Yellow
        Write-Host "  define( 'GF_SIREN_API_KEY', 'votre_clé_api_ici' );" -ForegroundColor Gray
        exit 1
    }
} else {
    Write-Host "❌ Fichier wp-config.php non trouvé" -ForegroundColor Red
    Write-Host ""
    Write-Host "Le script a cherché dans:" -ForegroundColor Yellow
    foreach ($path in $searchPaths) {
        Write-Host "  - $path" -ForegroundColor Gray
    }
    Write-Host ""
    Write-Host "Utilisez plutôt:" -ForegroundColor Yellow
    Write-Host "  `$env:GF_SIREN_API_KEY = 'votre_cle_api'" -ForegroundColor Gray
    Write-Host "  php test_api_integration.php" -ForegroundColor Gray
    exit 1
}

