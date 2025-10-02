#!/usr/bin/env python3
"""
Script d'installation et diagnostic du plugin Gravity Forms Siren Autocomplete
via SSH avec WP-CLI

Utilise ssh_access.py pour exécuter des commandes sur le serveur
"""

import sys
import os
import json

# Ajouter le dossier Access au path pour importer ssh_access
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..', '..', 'Access'))

try:
    from ssh_access import SSHWordPressManager
except ImportError:
    print("❌ Impossible d'importer ssh_access.py")
    print("💡 Assurez-vous que le fichier existe dans Access/ssh_access.py")
    sys.exit(1)

class GFSirenInstaller:
    """Installateur du plugin Gravity Forms Siren Autocomplete"""
    
    def __init__(self):
        self.ssh_manager = SSHWordPressManager(verbose=True)
        self.errors = []
        self.warnings = []
        
    def run(self):
        """Exécute l'installation complète"""
        print("=" * 80)
        print("🚀 INSTALLATION GRAVITY FORMS SIREN AUTOCOMPLETE")
        print("=" * 80)
        print()
        
        # Connexion SSH
        print("📡 Connexion au serveur...")
        self.ssh_manager.connect()
        print()
        
        # 1. Vérifier l'API Key
        self.check_api_key()
        
        # 2. Installer le mapping du formulaire
        self.install_form_mapping()
        
        # 3. Vérifier le plugin
        self.check_plugin_status()
        
        # 4. Afficher le résumé
        self.show_summary()
        
    def check_api_key(self):
        """Vérifie que la clé API est configurée dans wp-config.php"""
        print("🔑 Vérification de la clé API...")
        print("-" * 80)
        
        result = self.ssh_manager.execute_wp_cli(
            "wp config get GF_SIREN_API_KEY"
        )
        
        if result['success'] and result['output']:
            print(f"✅ Clé API configurée : {result['output'][:20]}...")
        else:
            self.errors.append("Clé API non trouvée dans wp-config.php")
            print("❌ Clé API non trouvée !")
            print("💡 Ajoutez cette ligne dans wp-config.php :")
            print("   define( 'GF_SIREN_API_KEY', 'votre_cle_api' );")
        
        print()
    
    def install_form_mapping(self):
        """Installe le mapping du formulaire ID: 1"""
        print("📦 Installation du mapping du formulaire ID: 1...")
        print("-" * 80)
        
        # Configuration du mapping pour le formulaire ID: 1
        mapping_config = {
            'form_id': 1,
            'form_name': 'Test de positionnement Révélation Digitale',
            'enable_plugin': True,
            'enable_button': True,
            'button_position': 'after',
            'siret': '1',
            'denomination': '12',
            'adresse': '8.1',
            'ville': '8.3',
            'code_postal': '8.5',
            'pays': '8.6',
            'mentions_legales': '13',
            'prenom': '7.3',
            'nom': '7.6',
            'forme_juridique': '',
            'code_ape': '',
            'libelle_ape': '',
            'date_creation': '',
            'statut_actif': '',
            'type_entreprise': ''
        }
        
        # Convertir en JSON pour WP-CLI
        mapping_json = json.dumps(mapping_config).replace('"', '\\"')
        
        # Récupérer les settings actuels
        result = self.ssh_manager.execute_wp_cli(
            "wp option get gf_siren_settings --format=json"
        )
        
        if result['success'] and result['output']:
            try:
                settings = json.loads(result['output'])
            except:
                settings = {}
        else:
            settings = {}
        
        # Ajouter le mapping
        if 'form_mappings' not in settings:
            settings['form_mappings'] = {}
        
        settings['form_mappings']['1'] = mapping_config
        
        # Sauvegarder les settings
        settings_json = json.dumps(settings).replace('"', '\\"')
        
        result = self.ssh_manager.execute_wp_cli(
            f'wp option update gf_siren_settings "{settings_json}" --format=json'
        )
        
        if result['success']:
            print("✅ Mapping du formulaire ID: 1 installé avec succès !")
            print()
            print("📋 Récapitulatif du mapping :")
            print("   - Form ID: 1")
            print("   - SIRET : Champ 1")
            print("   - Nom entreprise : Champ 12")
            print("   - Adresse : Champ 8.1")
            print("   - Ville : Champ 8.3")
            print("   - Code postal : Champ 8.5")
            print("   - Pays : Champ 8.6")
            print("   - Mentions légales : Champ 13")
            print("   - Prénom représentant : Champ 7.3")
            print("   - Nom représentant : Champ 7.6")
        else:
            self.errors.append(f"Échec installation mapping : {result['error']}")
            print(f"❌ Erreur : {result['error']}")
        
        print()
    
    def check_plugin_status(self):
        """Vérifie l'état du plugin"""
        print("🔍 Vérification du plugin...")
        print("-" * 80)
        
        # Vérifier que le plugin est activé
        result = self.ssh_manager.execute_wp_cli(
            "wp plugin list --field=name,status --format=csv"
        )
        
        if result['success']:
            plugins = result['output']
            if 'gravity_forms_siren_autocomplete' in plugins:
                if 'active' in plugins:
                    print("✅ Plugin activé")
                else:
                    self.warnings.append("Plugin non activé")
                    print("⚠️ Plugin installé mais non activé")
                    print("💡 Activez-le avec : wp plugin activate gravity_forms_siren_autocomplete")
            else:
                self.errors.append("Plugin non trouvé")
                print("❌ Plugin non trouvé dans la liste des plugins")
        
        # Vérifier que Gravity Forms est actif
        result = self.ssh_manager.execute_wp_cli(
            "wp plugin is-active gravityforms"
        )
        
        if result['success']:
            print("✅ Gravity Forms activé")
        else:
            self.errors.append("Gravity Forms non actif")
            print("❌ Gravity Forms n'est pas activé !")
        
        print()
    
    def show_summary(self):
        """Affiche le résumé de l'installation"""
        print()
        print("=" * 80)
        print("📊 RÉSUMÉ")
        print("=" * 80)
        
        if self.errors:
            print()
            print("❌ ERREURS :")
            for error in self.errors:
                print(f"   - {error}")
        
        if self.warnings:
            print()
            print("⚠️ AVERTISSEMENTS :")
            for warning in self.warnings:
                print(f"   - {warning}")
        
        if not self.errors and not self.warnings:
            print()
            print("✅ Installation réussie !")
            print()
            print("🎯 Prochaines étapes :")
            print("   1. Accéder au formulaire sur votre site")
            print("   2. Tester le bouton 'Vérifier le SIRET'")
            print("   3. Vérifier que les champs se remplissent automatiquement")
        
        print()
        print("=" * 80)

def main():
    """Fonction principale"""
    installer = GFSirenInstaller()
    
    try:
        installer.run()
        return 0
    except KeyboardInterrupt:
        print("\n\n⚠️ Installation interrompue par l'utilisateur")
        return 1
    except Exception as e:
        print(f"\n\n❌ Erreur inattendue : {e}")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    sys.exit(main())

