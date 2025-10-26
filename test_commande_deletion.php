<?php
echo "=== TEST SUPPRESSION COMMANDE ===\n\n";

echo "🔍 ÉTAPE 1: Vérification des commandes existantes...\n";

// Vérifier les commandes existantes
$command = "php bin/console doctrine:query:sql \"SELECT id, statut, user_id, lot_id FROM commande ORDER BY id DESC LIMIT 3\"";
exec($command, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "Commandes trouvées :\n";
    foreach ($output as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune commande trouvée ou erreur de requête\n";
}

echo "\n🔍 ÉTAPE 2: Vérification des lots...\n";

$lotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 3\"";
exec($lotCommand, $lotOutput, $lotReturnCode);

if ($lotReturnCode === 0 && !empty($lotOutput)) {
    echo "Lots trouvés :\n";
    foreach ($lotOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé ou erreur de requête\n";
}

echo "\n🔍 ÉTAPE 3: Vérification des files d'attente...\n";

$fileCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY created_at DESC LIMIT 3\"";
exec($fileCommand, $fileOutput, $fileReturnCode);

if ($fileReturnCode === 0 && !empty($fileOutput)) {
    echo "Files d'attente trouvées :\n";
    foreach ($fileOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune file d'attente trouvée ou erreur de requête\n";
}

echo "\n📊 RÉSUMÉ:\n";
echo "- Commandes: " . (count($output) > 0 ? "✅ PRÉSENTES" : "❌ ABSENTES") . "\n";
echo "- Lots: " . (count($lotOutput) > 0 ? "✅ PRÉSENTS" : "❌ ABSENTS") . "\n";
echo "- Files d'attente: " . (count($fileOutput) > 0 ? "✅ PRÉSENTES" : "❌ ABSENTES") . "\n";

echo "\n🎯 INSTRUCTIONS POUR LE TEST:\n";
echo "1. Allez sur http://localhost:8080/admin\n";
echo "2. Connectez-vous avec un compte admin\n";
echo "3. Allez dans 'Toutes les commandes'\n";
echo "4. Supprimez une commande en statut 'réservé' ou 'validée'\n";
echo "5. Vérifiez les logs avec: docker compose exec php php check_debug_logs.php\n";
echo "6. Vérifiez que le lot passe à 'disponible'\n";
echo "7. Vérifiez que la première personne de la file d'attente est notifiée\n\n";

echo "=== FIN DU TEST ===\n";



