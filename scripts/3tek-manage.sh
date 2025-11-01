#!/bin/bash

# Script de gestion de l'application 3tek

ACTION=$1

function start_app() {
    echo "🚀 Démarrage de l'application 3tek..."
    docker compose up -d
    echo "✅ Application 3tek démarrée"
    echo "📱 Accès: http://45.11.51.2:8084"
}

function stop_app() {
    echo "🛑 Arrêt de l'application 3tek..."
    docker compose down
    echo "✅ Application 3tek arrêtée"
}

function restart_app() {
    echo "🔄 Redémarrage de l'application 3tek..."
    docker compose restart
    echo "✅ Application 3tek redémarrée"
}

function status_app() {
    echo "📊 Statut de l'application 3tek..."
    docker compose ps
}

function logs_app() {
    echo "📋 Logs de l'application 3tek..."
    docker compose logs -f
}

function install_app() {
    echo "📦 Installation de l'application 3tek..."
    
    # Démarrer les conteneurs
    docker compose up -d
    
    # Attendre que MySQL soit prêt
    echo "⏳ Attente du démarrage de MySQL..."
    sleep 30
    
    # Exécuter les migrations Symfony
    echo "🗄️ Exécution des migrations..."
    docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
    
    # Installer les dépendances si nécessaire
    echo "📦 Installation des dépendances..."
    docker compose exec php composer install --no-dev --optimize-autoloader
    
    # Vider le cache
    echo "🧹 Vidage du cache..."
    docker compose exec php php bin/console cache:clear --env=prod
    
    echo "✅ Installation terminée"
    echo "📱 Accès: http://45.11.51.2:8084"
}

function build_app() {
    echo "🔨 Construction de l'image Docker..."
    docker compose build --no-cache
    echo "✅ Image construite"
}

case "$ACTION" in
    start)
        start_app
        ;;
    stop)
        stop_app
        ;;
    restart)
        restart_app
        ;;
    status)
        status_app
        ;;
    logs)
        logs_app
        ;;
    install)
        install_app
        ;;
    build)
        build_app
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|logs|install|build}"
        echo ""
        echo "Commandes disponibles:"
        echo "  start   - Démarrer l'application"
        echo "  stop    - Arrêter l'application"
        echo "  restart - Redémarrer l'application"
        echo "  status  - Voir le statut"
        echo "  logs    - Voir les logs"
        echo "  install - Installation complète"
        echo "  build   - Construire l'image Docker"
        exit 1
        ;;
esac