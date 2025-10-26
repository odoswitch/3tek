<?php
echo "=== FORCE NOTIFICATION MANUELLE ===\n\n";

echo "🔧 ÉTAPE 1: Vérification du lot créé...\n";

// Vérifier le lot créé
$lotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite FROM lot WHERE name = 'Lot Test'\"";
exec($lotCommand, $lotOutput, $lotReturnCode);

if ($lotReturnCode === 0 && !empty($lotOutput)) {
    echo "Lot trouvé :\n";
    foreach ($lotOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Création d'un utilisateur simple...\n";

// Créer un utilisateur simple avec un format JSON correct
$createUserCommand = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles) VALUES ('test@example.com', 'Test', 'User', '\$2y\$13\$test', '[]')\"";
exec($createUserCommand, $createUserOutput, $createUserReturnCode);

if ($createUserReturnCode === 0) {
    echo "✅ Utilisateur créé\n";
} else {
    echo "❌ Erreur lors de la création de l'utilisateur\n";
}

echo "\n🔧 ÉTAPE 3: Récupération de l'ID de l'utilisateur...\n";

// Récupérer l'ID de l'utilisateur
$getUserIdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'test@example.com'\"";
exec($getUserIdCommand, $getUserIdOutput, $getUserIdReturnCode);

$userId = null;
if ($getUserIdReturnCode === 0 && !empty($getUserIdOutput)) {
    foreach ($getUserIdOutput as $line) {
        if (is_numeric(trim($line))) {
            $userId = trim($line);
            break;
        }
    }
}

if ($userId) {
    echo "✅ ID de l'utilisateur récupéré: " . $userId . "\n";
} else {
    echo "❌ Impossible de récupérer l'ID de l'utilisateur\n";
    exit;
}

echo "\n🔧 ÉTAPE 4: Création d'une file d'attente...\n";

// Créer une file d'attente
$createFileCommand = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES (15, $userId, 1, 'en_attente')\"";
exec($createFileCommand, $createFileOutput, $createFileReturnCode);

if ($createFileReturnCode === 0) {
    echo "✅ File d'attente créée\n";
} else {
    echo "❌ Erreur lors de la création de la file d'attente\n";
}

echo "\n🔧 ÉTAPE 5: Création d'une commande...\n";

// Créer une commande
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut) VALUES ($userId, 15, 1, 100.00, 100.00, 'reserve')\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🔧 ÉTAPE 6: Mise à jour du lot en statut 'réservé'...\n";

// Mettre le lot en statut réservé
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $userId WHERE id = 15\"";
exec($updateLotCommand, $updateLotOutput, $updateLotReturnCode);

if ($updateLotReturnCode === 0) {
    echo "✅ Lot mis en statut 'réservé'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du lot\n";
}

echo "\n🎯 SITUATION DE TEST CRÉÉE !\n";
echo "Maintenant vous pouvez :\n";
echo "1. Aller sur http://localhost:8080/admin\n";
echo "2. Supprimer la commande créée\n";
echo "3. Vérifier que le lot passe à 'disponible'\n";
echo "4. Vérifier que l'utilisateur est notifié\n";
echo "5. Vérifier les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📊 DONNÉES CRÉÉES:\n";
echo "- Lot ID: 15\n";
echo "- User ID: $userId\n";
echo "- Commande créée et lot réservé\n";
echo "- File d'attente créée\n\n";

echo "=== FIN DE LA SIMULATION ===\n";
