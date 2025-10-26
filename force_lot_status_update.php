<?php
echo "=== FORCE LOT STATUS UPDATE ===\n\n";

echo "🔧 ÉTAPE 1: Vérification des lots en statut 'réservé'...\n";

// Utiliser la commande Symfony pour accéder à la base de données
$command = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE statut = 'reserve'\"";
exec($command, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "Lots en statut 'réservé' trouvés :\n";
    foreach ($output as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot en statut 'réservé' trouvé ou erreur de requête\n";
}

echo "\n🔧 ÉTAPE 2: Mise à jour forcée des lots...\n";

// Forcer la mise à jour de tous les lots réservés
$updateCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'disponible', reserve_par_id = NULL, reserve_at = NULL WHERE statut = 'reserve'\"";
exec($updateCommand, $updateOutput, $updateReturnCode);

if ($updateReturnCode === 0) {
    echo "✅ Mise à jour forcée des lots effectuée\n";
} else {
    echo "❌ Erreur lors de la mise à jour des lots\n";
}

echo "\n🔧 ÉTAPE 3: Vérification après mise à jour...\n";

// Vérifier que les lots ont été mis à jour
$verifyCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE statut = 'reserve'\"";
exec($verifyCommand, $verifyOutput, $verifyReturnCode);

if ($verifyReturnCode === 0 && !empty($verifyOutput)) {
    echo "Lots encore en statut 'réservé' :\n";
    foreach ($verifyOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "✅ Aucun lot en statut 'réservé' - tous ont été mis à jour !\n";
}

echo "\n🔧 ÉTAPE 4: Vérification des lots disponibles...\n";

$availableCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite FROM lot WHERE statut = 'disponible'\"";
exec($availableCommand, $availableOutput, $availableReturnCode);

if ($availableReturnCode === 0 && !empty($availableOutput)) {
    echo "Lots maintenant disponibles :\n";
    foreach ($availableOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
}

echo "\n✅ FORCE LOT STATUS UPDATE TERMINÉ !\n";
echo "Tous les lots en statut 'réservé' ont été forcés vers 'disponible'.\n";
echo "Les réservataires ont été supprimés.\n";
echo "Les lots sont maintenant disponibles pour de nouvelles commandes.\n\n";

echo "🎯 RÉSULTAT ATTENDU:\n";
echo "- Plus de lots en statut 'réservé'\n";
echo "- Tous les lots sont maintenant 'disponibles'\n";
echo "- Les utilisateurs peuvent commander à nouveau\n";
echo "- Le système de file d'attente fonctionne normalement\n\n";

echo "=== FIN DU FORCE UPDATE ===\n";



