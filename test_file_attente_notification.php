<?php
echo "=== TEST FILE D'ATTENTE NOTIFICATION ===\n\n";

echo "🔍 ÉTAPE 1: Vérification des files d'attente...\n";

// Vérifier les files d'attente
$fileCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut, created_at FROM file_attente ORDER BY created_at DESC LIMIT 5\"";
exec($fileCommand, $fileOutput, $fileReturnCode);

if ($fileReturnCode === 0 && !empty($fileOutput)) {
    echo "Files d'attente trouvées :\n";
    foreach ($fileOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune file d'attente trouvée\n";
}

echo "\n🔍 ÉTAPE 2: Vérification des lots...\n";

// Vérifier les lots
$lotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 5\"";
exec($lotCommand, $lotOutput, $lotReturnCode);

if ($lotReturnCode === 0 && !empty($lotOutput)) {
    echo "Lots trouvés :\n";
    foreach ($lotOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé\n";
}

echo "\n🔍 ÉTAPE 3: Vérification des utilisateurs...\n";

// Vérifier les utilisateurs
$userCommand = "php bin/console doctrine:query:sql \"SELECT id, name, lastname, email FROM user ORDER BY id DESC LIMIT 3\"";
exec($userCommand, $userOutput, $userReturnCode);

if ($userReturnCode === 0 && !empty($userOutput)) {
    echo "Utilisateurs trouvés :\n";
    foreach ($userOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun utilisateur trouvé\n";
}

echo "\n🎯 INSTRUCTIONS POUR TESTER LA LOGIQUE AUTOMATIQUE:\n";
echo "1. Créez une nouvelle commande pour un lot\n";
echo "2. Supprimez cette commande via l'admin\n";
echo "3. Vérifiez que le lot passe à 'disponible'\n";
echo "4. Vérifiez que la première personne de la file d'attente est notifiée\n";
echo "5. Vérifiez les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📧 POUR TESTER LA NOTIFICATION MANUELLE:\n";
echo "Si vous voulez tester la notification manuellement, utilisez le script de test suivant...\n\n";

echo "=== FIN DU TEST ===\n";


