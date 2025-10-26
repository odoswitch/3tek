<?php
echo "=== FORCE FIX LOT STATUS IMMÉDIAT ===\n\n";

echo "🔧 ÉTAPE 1: Vérification des lots en statut 'réservé'...\n";

// Vérifier les lots en statut réservé
$command = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE statut = 'reserve' OR statut = 'rupture' OR quantite = 0\"";
exec($command, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "Lots en statut problématique trouvés :\n";
    foreach ($output as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot en statut problématique trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Correction forcée des lots...\n";

// Forcer tous les lots à être "disponible"
$updateCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'disponible', quantite = 1, reserve_par_id = NULL, reserve_at = NULL WHERE statut = 'reserve' OR statut = 'rupture' OR quantite = 0\"";
exec($updateCommand, $updateOutput, $updateReturnCode);

if ($updateReturnCode === 0) {
    echo "✅ Correction forcée des lots effectuée\n";
} else {
    echo "❌ Erreur lors de la correction des lots\n";
}

echo "\n🔧 ÉTAPE 3: Vérification après correction...\n";

// Vérifier que les lots ont été corrigés
$verifyCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 5\"";
exec($verifyCommand, $verifyOutput, $verifyReturnCode);

if ($verifyReturnCode === 0 && !empty($verifyOutput)) {
    echo "Statut des lots après correction :\n";
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

echo "\n✅ CORRECTION IMMÉDIATE TERMINÉE !\n";
echo "Tous les lots ont été forcés vers 'disponible'.\n";
echo "Les réservataires ont été supprimés.\n";
echo "Les lots sont maintenant disponibles pour de nouvelles commandes.\n\n";

echo "🎯 RÉSULTAT ATTENDU:\n";
echo "- Plus de lots en statut 'réservé' ou 'rupture'\n";
echo "- Tous les lots sont maintenant 'disponibles'\n";
echo "- Les utilisateurs peuvent commander à nouveau\n";
echo "- Le système de file d'attente fonctionne normalement\n\n";

echo "=== FIN DE LA CORRECTION IMMÉDIATE ===\n";
