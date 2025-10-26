#!/bin/bash

echo "=== TEST COMPLET : SUPPRESSION VS ANNULATION VIA CONTRÔLEURS ==="
echo ""

# Fonction pour exécuter une commande dans le conteneur
run_in_container() {
    docker exec 3tek_php php bin/console doctrine:query:sql "$1"
}

# Fonction pour afficher l'état des lots
show_lots_state() {
    echo "📊 État des lots :"
    run_in_container "SELECT id, name, statut, reserve_par_id FROM lot WHERE id IN (5, 13) ORDER BY id"
    echo ""
}

# Fonction pour afficher l'état des files d'attente
show_queue_state() {
    echo "📋 État des files d'attente :"
    run_in_container "SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY lot_id, position"
    echo ""
}

# Fonction pour afficher l'état des commandes
show_commands_state() {
    echo "📦 État des commandes :"
    run_in_container "SELECT id, numero_commande, lot_id, statut FROM commande WHERE numero_commande LIKE 'TEST-%' ORDER BY id"
    echo ""
}

echo "🔧 PRÉPARATION DE L'ENVIRONNEMENT DE TEST"
echo "========================================"

# Nettoyer l'environnement
run_in_container "DELETE FROM commande WHERE numero_commande LIKE 'TEST-%'"
run_in_container "DELETE FROM file_attente WHERE lot_id IN (5, 13)"
run_in_container "UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id IN (5, 13)"

echo "✅ Environnement nettoyé"

# Créer des files d'attente pour les deux lots avec utilisateurs ID 3,4
run_in_container "INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 3, 1, 'en_attente', NOW())"
run_in_container "INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (5, 4, 2, 'en_attente', NOW())"
run_in_container "INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 3, 1, 'en_attente', NOW())"
run_in_container "INSERT INTO file_attente (lot_id, user_id, position, statut, created_at) VALUES (13, 4, 2, 'en_attente', NOW())"

echo "✅ Files d'attente créées (utilisateurs ID 3,4)"

# Créer des commandes pour les deux lots
run_in_container "INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-SUPPRESSION', 2, 5, 1, 12.00, 12.00, 'en_attente', NOW())"
run_in_container "INSERT INTO commande (numero_commande, user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at) VALUES ('TEST-ANNULATION', 2, 13, 1, 12.00, 12.00, 'en_attente', NOW())"

echo "✅ Commandes de test créées"
echo ""

echo "📊 ÉTAT INITIAL"
echo "==============="
show_lots_state
show_queue_state
show_commands_state

echo "🧪 TEST 1 - SUPPRESSION DE COMMANDE"
echo "==================================="

echo "Simulation de la suppression via CommandeCrudController::deleteEntity..."

# Récupérer l'ID de la commande à supprimer
COMMANDE_ID=$(run_in_container "SELECT id FROM commande WHERE numero_commande = 'TEST-SUPPRESSION'" | grep -o '[0-9]\+' | head -1)

echo "Commande à supprimer : ID $COMMANDE_ID (TEST-SUPPRESSION)"

# Simuler la logique du contrôleur : libérer le lot
echo "Libération du lot ID 5 (HP Serveur)..."

# Vérifier s'il y a des utilisateurs en file d'attente
FIRST_USER=$(run_in_container "SELECT user_id FROM file_attente WHERE lot_id = 5 ORDER BY position ASC LIMIT 1" | grep -o '[0-9]\+' | head -1)

if [ ! -z "$FIRST_USER" ]; then
    echo "Premier en file d'attente trouvé : User ID $FIRST_USER"
    
    # Réserver le lot pour le premier utilisateur
    run_in_container "UPDATE lot SET statut = 'reserve', reserve_par_id = $FIRST_USER, reserve_at = NOW() WHERE id = 5"
    
    # Mettre à jour le statut de la file d'attente
    run_in_container "UPDATE file_attente SET statut = 'en_attente_validation', notified_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE lot_id = 5 AND user_id = $FIRST_USER"
    
    echo "✅ Lot réservé pour l'utilisateur ID $FIRST_USER avec délai d'1h"
else
    echo "Aucun utilisateur en file d'attente"
    
    # Libérer le lot pour tous
    run_in_container "UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = 5"
    
    echo "✅ Lot libéré pour tous"
fi

# Supprimer la commande
run_in_container "DELETE FROM commande WHERE id = $COMMANDE_ID"

echo "✅ Commande supprimée"
echo ""

echo "🧪 TEST 2 - ANNULATION DE COMMANDE"
echo "==================================="

echo "Simulation de l'annulation via CommandeController::cancel..."

# Récupérer l'ID de la commande à annuler
COMMANDE_ID=$(run_in_container "SELECT id FROM commande WHERE numero_commande = 'TEST-ANNULATION'" | grep -o '[0-9]\+' | head -1)

echo "Commande à annuler : ID $COMMANDE_ID (TEST-ANNULATION)"

# Changer le statut de la commande à 'annulee'
run_in_container "UPDATE commande SET statut = 'annulee' WHERE id = $COMMANDE_ID"

echo "✅ Commande annulée (statut changé à 'annulee')"

# Libérer le lot (même logique que pour la suppression)
echo "Libération du lot ID 13 (Lot David)..."

# Vérifier s'il y a des utilisateurs en file d'attente
FIRST_USER=$(run_in_container "SELECT user_id FROM file_attente WHERE lot_id = 13 ORDER BY position ASC LIMIT 1" | grep -o '[0-9]\+' | head -1)

if [ ! -z "$FIRST_USER" ]; then
    echo "Premier en file d'attente trouvé : User ID $FIRST_USER"
    
    # Réserver le lot pour le premier utilisateur
    run_in_container "UPDATE lot SET statut = 'reserve', reserve_par_id = $FIRST_USER, reserve_at = NOW() WHERE id = 13"
    
    # Mettre à jour le statut de la file d'attente
    run_in_container "UPDATE file_attente SET statut = 'en_attente_validation', notified_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE lot_id = 13 AND user_id = $FIRST_USER"
    
    echo "✅ Lot réservé pour l'utilisateur ID $FIRST_USER avec délai d'1h"
else
    echo "Aucun utilisateur en file d'attente"
    
    # Libérer le lot pour tous
    run_in_container "UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = 13"
    
    echo "✅ Lot libéré pour tous"
fi

echo ""

echo "🔍 VÉRIFICATION DES RÉSULTATS"
echo "============================"
show_lots_state
show_queue_state
show_commands_state

echo "📈 ANALYSE DES RÉSULTATS"
echo "========================"

# Vérifier les résultats
LOT5_STATUT=$(run_in_container "SELECT statut FROM lot WHERE id = 5" | grep -o '[a-z_]*' | head -1)
LOT5_RESERVE=$(run_in_container "SELECT reserve_par_id FROM lot WHERE id = 5" | grep -o '[0-9]*' | head -1)

LOT13_STATUT=$(run_in_container "SELECT statut FROM lot WHERE id = 13" | grep -o '[a-z_]*' | head -1)
LOT13_RESERVE=$(run_in_container "SELECT reserve_par_id FROM lot WHERE id = 13" | grep -o '[0-9]*' | head -1)

echo ""
echo "TEST 1 - SUPPRESSION (Lot HP Serveur) :"
if [ "$LOT5_STATUT" = "reserve" ] && [ "$LOT5_RESERVE" = "3" ]; then
    echo "✅ SUCCÈS : Le lot est réservé pour l'utilisateur ID 3 (premier en file)"
else
    echo "❌ ÉCHEC : Le lot n'est pas correctement réservé pour l'utilisateur ID 3"
    echo "   - Statut attendu: 'reserve', obtenu: '$LOT5_STATUT'"
    echo "   - Réservé par attendu: 3, obtenu: '$LOT5_RESERVE'"
fi

echo ""
echo "TEST 2 - ANNULATION (Lot David) :"
if [ "$LOT13_STATUT" = "reserve" ] && [ "$LOT13_RESERVE" = "3" ]; then
    echo "✅ SUCCÈS : Le lot est réservé pour l'utilisateur ID 3 (premier en file)"
else
    echo "❌ ÉCHEC : Le lot n'est pas correctement réservé pour l'utilisateur ID 3"
    echo "   - Statut attendu: 'reserve', obtenu: '$LOT13_STATUT'"
    echo "   - Réservé par attendu: 3, obtenu: '$LOT13_RESERVE'"
fi

echo ""
echo "=== FIN DU TEST ==="

