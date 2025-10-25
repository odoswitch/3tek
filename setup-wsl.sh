#!/bin/bash

echo "========================================="
echo "  Configuration WSL pour 3TEK"
echo "========================================="
echo ""

# Vérifier que Docker est accessible
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas accessible depuis WSL"
    echo "Activez l'intégration WSL dans Docker Desktop:"
    echo "  Settings > Resources > WSL Integration"
    exit 1
fi

echo "✅ Docker est accessible"
echo ""

# Vérifier Docker Compose
if ! command -v docker compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas accessible"
    exit 1
fi

echo "✅ Docker Compose est accessible"
echo ""

# Installer Make si nécessaire
if ! command -v make &> /dev/null; then
    echo "📦 Installation de Make..."
    sudo apt-get update
    sudo apt-get install -y make
fi

echo "✅ Make est installé"
echo ""

# Créer un alias pour faciliter l'accès
echo "📝 Configuration des alias..."

# Ajouter les alias au .bashrc si pas déjà présents
if ! grep -q "alias 3tek" ~/.bashrc; then
    cat >> ~/.bashrc << 'EOF'

# Alias 3TEK
alias 3tek='cd /mnt/e/DEV_IA/3tek'
alias 3tek-start='cd /mnt/e/DEV_IA/3tek && docker compose -f compose.yaml -f compose.override.yaml up -d'
alias 3tek-stop='cd /mnt/e/DEV_IA/3tek && docker compose down'
alias 3tek-logs='cd /mnt/e/DEV_IA/3tek && docker compose logs -f'
alias 3tek-shell='cd /mnt/e/DEV_IA/3tek && docker compose exec php bash'
EOF
    echo "✅ Alias ajoutés à ~/.bashrc"
else
    echo "✅ Alias déjà configurés"
fi

echo ""
echo "========================================="
echo "  Configuration terminée !"
echo "========================================="
echo ""
echo "Commandes disponibles:"
echo "  3tek          - Aller dans le projet"
echo "  3tek-start    - Démarrer l'application"
echo "  3tek-stop     - Arrêter l'application"
echo "  3tek-logs     - Voir les logs"
echo "  3tek-shell    - Accéder au shell PHP"
echo ""
echo "Pour activer les alias maintenant:"
echo "  source ~/.bashrc"
echo ""
echo "Ou redémarrez votre terminal WSL"
echo ""
