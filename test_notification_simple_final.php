<?php
echo "=== TEST NOTIFICATION SIMPLE FINAL ===\n\n";

echo "🔍 ÉTAPE 1: Vérification des données existantes...\n";

// Vérifier les lots
$lotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite FROM lot ORDER BY id DESC LIMIT 3\"";
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

echo "\n🔍 ÉTAPE 2: Vérification des utilisateurs existants...\n";

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

echo "\n🔍 ÉTAPE 3: Vérification des commandes existantes...\n";

// Vérifier les commandes
$commandeCommand = "php bin/console doctrine:query:sql \"SELECT id, user_id, lot_id, statut FROM commande ORDER BY id DESC LIMIT 3\"";
exec($commandeCommand, $commandeOutput, $commandeReturnCode);

if ($commandeReturnCode === 0 && !empty($commandeOutput)) {
    echo "Commandes trouvées :\n";
    foreach ($commandeOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune commande trouvée\n";
}

echo "\n🔍 ÉTAPE 4: Vérification des files d'attente existantes...\n";

// Vérifier les files d'attente
$fileCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY id DESC LIMIT 3\"";
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

echo "\n🎯 INSTRUCTIONS POUR TESTER LA LOGIQUE AUTOMATIQUE:\n";
echo "1. Créez une commande via l'interface utilisateur\n";
echo "2. Supprimez cette commande via l'admin\n";
echo "3. Vérifiez que le lot passe à 'disponible'\n";
echo "4. Vérifiez que la première personne de la file d'attente est notifiée\n\n";

echo "📧 POUR TESTER LA NOTIFICATION MANUELLE:\n";
echo "Si vous voulez forcer la notification, utilisez le script suivant...\n\n";

echo "=== FIN DU TEST ===\n";


