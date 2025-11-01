#!/bin/bash

# Script de monitoring pour l'application 3tek
# Usage: ./monitor-3tek.sh

set -e

# Configuration
APP_DIR="/opt/docker/3tek"
DB_NAME="3tek"
DB_USER="root"
DB_PASSWORD="ngamba123"
APP_URL="http://45.11.51.2:8084"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Fonction de test HTTP
test_http() {
    local url="$1"
    local expected_status="$2"
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
    if [ "$response" = "$expected_status" ]; then
        print_status "HTTP $response - $url"
        return 0
    else
        print_error "HTTP $response (attendu: $expected_status) - $url"
        return 1
    fi
}

# Test de l'application web
test_web_application() {
    print_header "TEST APPLICATION WEB"
    
    # Test page d'accueil
    test_http "$APP_URL/" "200"
    
    # Test page d'inscription
    test_http "$APP_URL/register" "200"
    
    # Test page des lots
    test_http "$APP_URL/lots" "200"
    
    # Test page admin (doit rediriger)
    test_http "$APP_URL/admin" "302"
    
    # Test page de profil (doit rediriger)
    test_http "$APP_URL/profile" "302"
}

# Test de la base de données
test_database() {
    print_header "TEST BASE DE DONNÉES"
    
    # Test de connexion via Docker
    if docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1; then
        print_status "Connexion à la base de données OK"
    else
        print_error "Erreur de connexion à la base de données"
        return 1
    fi
    
    # Test de la base 3tek
    if docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT 1;" > /dev/null 2>&1; then
        print_status "Base de données '$DB_NAME' accessible"
    else
        print_error "Base de données '$DB_NAME' non accessible"
        return 1
    fi
    
    # Vérification des tables
    table_count=$(docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" | wc -l)
    print_status "Nombre de tables: $((table_count - 1))"
    
    # Vérification des données
    if [ "$table_count" -gt 1 ]; then
        # Compter les utilisateurs
        user_count=$(docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM user;" 2>/dev/null | tail -1)
        print_status "Nombre d'utilisateurs: $user_count"
        
        # Compter les lots
        lot_count=$(docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM lot;" 2>/dev/null | tail -1)
        print_status "Nombre de lots: $lot_count"
        
        # Compter les commandes
        commande_count=$(docker exec 3tek-database-1 mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM commande;" 2>/dev/null | tail -1)
        print_status "Nombre de commandes: $commande_count"
    fi
}

# Test des conteneurs Docker
test_docker_containers() {
    print_header "TEST CONTENEURS DOCKER"
    
    # Vérifier que Docker est en cours d'exécution
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker n'est pas en cours d'exécution"
        return 1
    fi
    
    # Vérifier les conteneurs 3tek
    containers=("3tek_nginx" "3tek_php" "3tek-database-1" "3tek_phpmyadmin" "3tek-mailer-1")
    
    for container in "${containers[@]}"; do
        if docker ps --format "table {{.Names}}" | grep -q "^$container$"; then
            status=$(docker ps --format "table {{.Status}}" --filter "name=$container")
            print_status "Conteneur $container: $status"
        else
            print_error "Conteneur $container non trouvé ou arrêté"
        fi
    done
}

# Test des ressources système
test_system_resources() {
    print_header "TEST RESSOURCES SYSTÈME"
    
    # Mémoire
    memory_usage=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
    print_status "Utilisation mémoire: ${memory_usage}%"
    
    # Disque
    disk_usage=$(df -h / | awk 'NR==2{print $5}')
    print_status "Utilisation disque: $disk_usage"
    
    # CPU
    cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    print_status "Utilisation CPU: ${cpu_usage}%"
    
    # Espace disque pour l'application
    app_size=$(du -sh "$APP_DIR" 2>/dev/null | cut -f1)
    print_status "Taille application: $app_size"
}

# Test des logs
test_logs() {
    print_header "TEST LOGS"
    
    # Vérifier les logs de l'application
    if [ -f "$APP_DIR/var/log/prod.log" ]; then
        log_size=$(du -sh "$APP_DIR/var/log/prod.log" | cut -f1)
        print_status "Log de production: $log_size"
        
        # Dernières erreurs
        error_count=$(grep -c "ERROR" "$APP_DIR/var/log/prod.log" 2>/dev/null || echo "0")
        if [ "$error_count" -gt 0 ]; then
            print_warning "Erreurs trouvées dans les logs: $error_count"
            echo "Dernières erreurs:"
            grep "ERROR" "$APP_DIR/var/log/prod.log" | tail -3
        else
            print_status "Aucune erreur dans les logs"
        fi
    else
        print_warning "Fichier de log de production non trouvé"
    fi
    
    # Vérifier les logs Docker
    print_status "Logs des conteneurs Docker:"
    for container in "3tek_nginx" "3tek_php" "3tek-database-1"; do
        if docker ps --format "table {{.Names}}" | grep -q "^$container$"; then
            log_lines=$(docker logs "$container" --tail 5 2>/dev/null | wc -l)
            print_status "  $container: $log_lines lignes de log"
        fi
    done
}

# Test des performances
test_performance() {
    print_header "TEST PERFORMANCES"
    
    # Temps de réponse de l'application
    response_time=$(curl -s -o /dev/null -w "%{time_total}" "$APP_URL/" 2>/dev/null)
    print_status "Temps de réponse: ${response_time}s"
    
    # Test de charge simple
    print_status "Test de charge (5 requêtes simultanées)..."
    for i in {1..5}; do
        (
            start_time=$(date +%s.%N)
            curl -s "$APP_URL/" > /dev/null
            end_time=$(date +%s.%N)
            duration=$(echo "$end_time - $start_time" | bc)
            echo "Requête $i: ${duration}s"
        ) &
    done
    wait
}

# Fonction principale
main() {
    print_header "MONITORING APPLICATION 3TEK"
    echo "Date: $(date)"
    echo "Serveur: $(hostname)"
    echo "Application: $APP_URL"
    echo ""
    
    # Tests
    test_web_application
    echo ""
    
    test_database
    echo ""
    
    test_docker_containers
    echo ""
    
    test_system_resources
    echo ""
    
    test_logs
    echo ""
    
    test_performance
    echo ""
    
    print_header "MONITORING TERMINÉ"
    print_status "Tous les tests ont été exécutés"
    
    # Recommandations
    echo ""
    print_header "RECOMMANDATIONS"
    
    # Vérifier l'espace disque
    disk_usage_num=$(df / | awk 'NR==2{print $5}' | sed 's/%//')
    if [ "$disk_usage_num" -gt 80 ]; then
        print_warning "Espace disque faible: ${disk_usage_num}% utilisé"
        print_warning "Considérez nettoyer les logs et sauvegardes anciennes"
    fi
    
    # Vérifier la mémoire
    memory_usage_num=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    if [ "$memory_usage_num" -gt 80 ]; then
        print_warning "Utilisation mémoire élevée: ${memory_usage_num}%"
        print_warning "Considérez redémarrer les conteneurs si nécessaire"
    fi
    
    # Vérifier les erreurs
    if [ -f "$APP_DIR/var/log/prod.log" ]; then
        recent_errors=$(grep -c "ERROR" "$APP_DIR/var/log/prod.log" 2>/dev/null || echo "0")
        if [ "$recent_errors" -gt 10 ]; then
            print_warning "Nombre d'erreurs élevé: $recent_errors"
            print_warning "Consultez les logs pour identifier les problèmes"
        fi
    fi
}

# Exécution
main "$@"
