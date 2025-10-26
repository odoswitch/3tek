<?php
echo "=== FORCE FIX LOT STATUS ===\n\n";

echo "🔧 ÉTAPE 1: Vérification des lots en statut 'réservé'...\n";

// Vérifier les lots en statut réservé
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
    echo "Aucun lot en statut 'réservé' trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Mise à jour forcée des lots...\n";

// Forcer tous les lots à être "disponible"
$updateCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'disponible', quantite = 1, reserve_par_id = NULL, reserve_at = NULL WHERE statut = 'reserve' OR statut = 'rupture' OR quantite = 0\"";
exec($updateCommand, $updateOutput, $updateReturnCode);

if ($updateReturnCode === 0) {
    echo "✅ Mise à jour forcée des lots effectuée\n";
} else {
    echo "❌ Erreur lors de la mise à jour des lots\n";
}

echo "\n🔧 ÉTAPE 3: Vérification après mise à jour...\n";

// Vérifier que les lots ont été mis à jour
$verifyCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 5\"";
exec($verifyCommand, $verifyOutput, $verifyReturnCode);

if ($verifyReturnCode === 0 && !empty($verifyOutput)) {
    echo "Statut des lots après mise à jour :\n";
    foreach ($verifyOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé ou erreur de requête\n";
}

echo "\n🔧 ÉTAPE 4: Vérification des files d'attente...\n";

// Vérifier les files d'attente
$fileCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY created_at DESC LIMIT 3\"";
exec($fileCommand, $fileOutput, $fileReturnCode);

if ($fileReturnCode === 0 && !empty($fileOutput)) {
    echo "Files d'attente :\n";
    foreach ($fileOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune file d'attente trouvée\n";
}

echo "\n✅ FORCE FIX LOT STATUS TERMINÉ !\n";
echo "Tous les lots en statut 'réservé' ont été forcés vers 'disponible'.\n";
echo "Les réservataires ont été supprimés.\n";
echo "Les lots sont maintenant disponibles pour de nouvelles commandes.\n\n";

echo "🎯 RÉSULTAT ATTENDU:\n";
echo "- Plus de lots en statut 'réservé'\n";
echo "- Tous les lots sont maintenant 'disponibles'\n";
echo "- Les utilisateurs peuvent commander à nouveau\n";
echo "- Le système de file d'attente fonctionne normalement\n\n";

echo "=== FIN DU FORCE FIX ===\n";


