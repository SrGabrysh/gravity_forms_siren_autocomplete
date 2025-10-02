#!/bin/bash

################################################################################
# Script d'installation rapide - Gravity Forms Siren Autocomplete
# Configuration du formulaire ID: 1
################################################################################

# Couleurs pour l'affichage
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   Gravity Forms Siren Autocomplete - Installation Config      ║"
echo "║              Formulaire ID: 1                                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Vérifier si WP-CLI est installé
if ! command -v wp &> /dev/null; then
    echo -e "${RED}❌ WP-CLI n'est pas installé.${NC}"
    echo ""
    echo "Pour installer WP-CLI :"
    echo "  https://wp-cli.org/#installing"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ WP-CLI détecté${NC}"
echo ""

# Vérifier si WordPress est trouvé
if ! wp core is-installed --quiet 2>/dev/null; then
    echo -e "${RED}❌ WordPress non trouvé dans le répertoire courant.${NC}"
    echo ""
    echo "Assurez-vous d'être dans le dossier racine de WordPress."
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ WordPress trouvé${NC}"
echo ""

# Vérifier si le plugin existe
PLUGIN_PATH="wp-content/plugins/gravity_forms_siren_autocomplete"

if [ ! -d "$PLUGIN_PATH" ]; then
    echo -e "${RED}❌ Plugin non trouvé dans $PLUGIN_PATH${NC}"
    echo ""
    echo "Installez d'abord le plugin :"
    echo "  cp -r /path/to/plugin $PLUGIN_PATH"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ Plugin trouvé${NC}"
echo ""

# Vérifier si le plugin est activé
if ! wp plugin is-active gravity-forms-siren-autocomplete --quiet 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Le plugin n'est pas activé.${NC}"
    echo ""
    read -p "Voulez-vous l'activer maintenant ? (o/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        wp plugin activate gravity-forms-siren-autocomplete
        echo -e "${GREEN}✅ Plugin activé${NC}"
        echo ""
    else
        echo -e "${YELLOW}⚠️  Activation annulée. Activez le plugin manuellement.${NC}"
        echo ""
        exit 1
    fi
else
    echo -e "${GREEN}✅ Plugin activé${NC}"
    echo ""
fi

# Vérifier si Gravity Forms est actif
if ! wp plugin is-active gravityforms --quiet 2>/dev/null; then
    echo -e "${RED}❌ Gravity Forms n'est pas activé.${NC}"
    echo ""
    echo "Ce plugin nécessite Gravity Forms pour fonctionner."
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ Gravity Forms actif${NC}"
echo ""

# Installer la configuration
echo -e "${YELLOW}📦 Installation de la configuration du formulaire ID: 1...${NC}"
echo ""

CONFIG_FILE="$PLUGIN_PATH/config_form_1.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${RED}❌ Fichier de configuration non trouvé : $CONFIG_FILE${NC}"
    echo ""
    exit 1
fi

# Exécuter la configuration
wp eval-file "$CONFIG_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Configuration installée avec succès !${NC}"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Erreur lors de l'installation de la configuration.${NC}"
    echo ""
    exit 1
fi

# Afficher le statut
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✨ Installation terminée !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "📋 Prochaines étapes :"
echo ""
echo "  1. Configurer la clé API Siren"
echo "     └─ Formulaires → Siren Autocomplete → Réglages"
echo ""
echo "  2. Configurer le champ HTML (ID: 11)"
echo "     └─ Copier le code depuis GUIDE_INSTALLATION_FORM_1.md"
echo ""
echo "  3. Tester le formulaire en frontend"
echo "     └─ Utiliser le SIRET : 89498206500019"
echo ""
echo "📖 Documentation complète :"
echo "   └─ $PLUGIN_PATH/GUIDE_INSTALLATION_FORM_1.md"
echo ""
echo -e "${GREEN}🚀 Bon développement !${NC}"
echo ""

