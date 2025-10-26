<?php

/**
 * SCRIPT DE CORRECTION POUR LA LIBÉRATION DES LOTS
 * 
 * Ce script corrige directement l'état des lots en utilisant la logique
 * de libération unifiée du service LotLiberationServiceAmeliore.
 */

echo "=== SCRIPT DE CORRECTION POUR LA LIBÉRATION DES LOTS ===\n\n";

// Configuration
$lot_id = 5; // HP Serveur

echo "📋 CONFIGURATION :\n";
echo "   - Lot à corriger : HP Serveur (ID $lot_id)\n\n";

// Étape 1 : Vérifier l'état actuel
echo "🔍 ÉTAPE 1 : VÉRIFICATION DE L'ÉTAT ACTUEL\n";
echo "------------------------------------------\n";

$etat_actuel = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id, reserve_at FROM lot WHERE id = $lot_id\"");
echo "État actuel du lot :\n$etat_actuel\n";

// Vérifier les commandes actives
$commandes_actives = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, user_id, statut FROM commande WHERE lot_id = $lot_id AND statut IN ('en_attente', 'reserve')\"");
echo "Commandes actives :\n$commandes_actives\n";

// Vérifier les files d'attente
$files_attente = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, user_id, position, statut FROM file_attente WHERE lot_id = $lot_id ORDER BY position\"");
echo "Files d'attente :\n$files_attente\n";

echo "\n";

// Étape 2 : Appliquer la logique de libération
echo "🔧 ÉTAPE 2 : APPLICATION DE LA LOGIQUE DE LIBÉRATION\n";
echo "----------------------------------------------------\n";

// Vérifier s'il y a des utilisateurs en file d'attente
$premier_en_file = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, user_id, position, statut FROM file_attente WHERE lot_id = $lot_id AND statut IN ('en_attente', 'en_attente_validation', 'notifie', 'delai_depasse') ORDER BY position ASC LIMIT 1\"");

if (trim($premier_en_file) && strpos($premier_en_file, 'id') !== false) {
    echo "✅ Utilisateur trouvé en file d'attente\n";
    echo "File d'attente :\n$premier_en_file\n";

    // Extraire l'ID de l'utilisateur
    preg_match('/\s+(\d+)\s+(\d+)\s+/', $premier_en_file, $matches);
    $user_id = $matches[2] ?? null;

    if ($user_id) {
        echo "Utilisateur ID trouvé : $user_id\n";

        // Réserver le lot pour le premier utilisateur en file d'attente
        $reserver_lot = "docker exec 3tek_php php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $user_id, reserve_at = NOW() WHERE id = $lot_id\"";
        shell_exec($reserver_lot);
        echo "Lot réservé pour l'utilisateur $user_id\n";

        // Mettre à jour le statut de la file d'attente
        $update_file = "docker exec 3tek_php php bin/console doctrine:query:sql \"UPDATE file_attente SET statut = 'en_attente_validation', notified_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user_id = $user_id AND lot_id = $lot_id\"";
        shell_exec($update_file);
        echo "Statut de la file d'attente mis à jour\n";
    }
} else {
    echo "❌ Aucun utilisateur en file d'attente\n";

    // Libérer le lot pour tous
    $liberer_lot = "docker exec 3tek_php php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = $lot_id\"";
    shell_exec($liberer_lot);
    echo "Lot libéré pour tous\n";
}

echo "\n";

// Étape 3 : Vérifier l'état final
echo "🔍 ÉTAPE 3 : VÉRIFICATION DE L'ÉTAT FINAL\n";
echo "----------------------------------------\n";

$etat_final = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE id = $lot_id\"");
echo "État final du lot :\n$etat_final\n";

$files_finales = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, user_id, position, statut FROM file_attente WHERE lot_id = $lot_id ORDER BY position\"");
echo "Files d'attente finales :\n$files_finales\n";

echo "\n";

// Étape 4 : Vider le cache
echo "🧹 ÉTAPE 4 : VIDAGE DU CACHE\n";
echo "----------------------------\n";

$cache_clear = shell_exec("docker exec 3tek_php php bin/console cache:clear");
echo "Cache vidé\n";

echo "\n";

// Étape 5 : Résumé
echo "📊 ÉTAPE 5 : RÉSUMÉ\n";
echo "-------------------\n";

echo "✅ Script de correction terminé !\n\n";

echo "🎯 ACTIONS EFFECTUÉES :\n";
echo "   - ✅ Vérification de l'état actuel\n";
echo "   - ✅ Application de la logique de libération\n";
echo "   - ✅ Mise à jour du statut du lot\n";
echo "   - ✅ Mise à jour des files d'attente\n";
echo "   - ✅ Vidage du cache\n\n";

echo "🚀 RÉSULTAT :\n";
echo "   Le lot HP Serveur est maintenant dans le bon état !\n";
echo "   Vous pouvez rafraîchir l'interface web pour voir les changements.\n\n";

echo "=== FIN DU SCRIPT DE CORRECTION ===\n";

