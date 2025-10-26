<?php

/**
 * SCRIPT DE SUPPRESSION DE FILE D'ATTENTE ET LIBÉRATION DU LOT
 */

echo "=== SUPPRESSION DE FILE D'ATTENTE ET LIBÉRATION DU LOT ===\n\n";

$lot_id = 5; // HP Serveur
$user3_id = 3; // congocrei2000@gmail.com

echo "📋 CONFIGURATION :\n";
echo "   - Lot : HP Serveur (ID $lot_id)\n";
echo "   - Utilisateur à supprimer : congocrei2000@gmail.com (ID $user3_id)\n\n";

// Étape 1 : Vérifier l'état actuel
echo "🔍 ÉTAPE 1 : VÉRIFICATION DE L'ÉTAT ACTUEL\n";
echo "------------------------------------------\n";

$etat_lot = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, name, statut, reserve_par_id FROM lot WHERE id = $lot_id\"");
echo "État du lot :\n$etat_lot\n";

$files_attente = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, user_id, position, statut FROM file_attente WHERE lot_id = $lot_id\"");
echo "Files d'attente :\n$files_attente\n";

echo "\n";

// Étape 2 : Supprimer la file d'attente
echo "🗑️ ÉTAPE 2 : SUPPRESSION DE LA FILE D'ATTENTE\n";
echo "----------------------------------------------\n";

$supprimer_file = "docker exec 3tek_php php bin/console doctrine:query:sql \"DELETE FROM file_attente WHERE lot_id = $lot_id AND user_id = $user3_id\"";
shell_exec($supprimer_file);
echo "File d'attente supprimée\n";

// Vérifier qu'elle est bien supprimée
$verif_suppression = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT COUNT(*) as count FROM file_attente WHERE lot_id = $lot_id\"");
echo "Vérification : $verif_suppression\n";

echo "\n";

// Étape 3 : Libérer le lot pour tous
echo "🔓 ÉTAPE 3 : LIBÉRATION DU LOT POUR TOUS\n";
echo "----------------------------------------\n";

$liberer_lot = "docker exec 3tek_php php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE id = $lot_id\"";
shell_exec($liberer_lot);
echo "Lot libéré pour tous\n";

echo "\n";

// Étape 4 : Vérifier l'état final
echo "🔍 ÉTAPE 4 : VÉRIFICATION DE L'ÉTAT FINAL\n";
echo "----------------------------------------\n";

$etat_final = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT id, name, statut, reserve_par_id FROM lot WHERE id = $lot_id\"");
echo "État final du lot :\n$etat_final\n";

$files_finales = shell_exec("docker exec 3tek_php php bin/console doctrine:query:sql \"SELECT COUNT(*) as count FROM file_attente WHERE lot_id = $lot_id\"");
echo "Files d'attente restantes : $files_finales\n";

echo "\n";

// Étape 5 : Vider le cache
echo "🧹 ÉTAPE 5 : VIDAGE DU CACHE\n";
echo "----------------------------\n";

shell_exec("docker exec 3tek_php php bin/console cache:clear");
echo "Cache vidé\n";

echo "\n";

// Résumé
echo "📊 RÉSUMÉ\n";
echo "---------\n";
echo "✅ File d'attente supprimée\n";
echo "✅ Lot libéré pour tous\n";
echo "✅ Cache vidé\n\n";
echo "🚀 Le lot HP Serveur est maintenant disponible pour tout le monde !\n";
echo "Rafraîchissez votre page web pour voir les changements.\n\n";

echo "=== FIN DU SCRIPT ===\n";

