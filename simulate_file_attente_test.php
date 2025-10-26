<?php
echo "=== SIMULATION FILE D'ATTENTE TEST ===\n\n";

echo "🔧 ÉTAPE 1: Création d'un lot de test...\n";

// Créer un lot de test
$createLotCommand = "php bin/console doctrine:query:sql \"INSERT INTO lot (name, description, prix, quantite, statut, created_at, updated_at) VALUES ('Lot Test Notification', 'Description du lot test', 100.00, 1, 'disponible', NOW(), NOW())\"";
exec($createLotCommand, $createLotOutput, $createLotReturnCode);

if ($createLotReturnCode === 0) {
    echo "✅ Lot de test créé\n";
} else {
    echo "❌ Erreur lors de la création du lot\n";
}

echo "\n🔧 ÉTAPE 2: Récupération de l'ID du lot créé...\n";

// Récupérer l'ID du lot créé
$getLotIdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM lot WHERE name = 'Lot Test Notification' ORDER BY id DESC LIMIT 1\"";
exec($getLotIdCommand, $getLotIdOutput, $getLotIdReturnCode);

$lotId = null;
if ($getLotIdReturnCode === 0 && !empty($getLotIdOutput)) {
    foreach ($getLotIdOutput as $line) {
        if (is_numeric(trim($line))) {
            $lotId = trim($line);
            break;
        }
    }
}

if ($lotId) {
    echo "✅ ID du lot récupéré: " . $lotId . "\n";
} else {
    echo "❌ Impossible de récupérer l'ID du lot\n";
    exit;
}

echo "\n🔧 ÉTAPE 3: Création d'utilisateurs de test...\n";

// Créer des utilisateurs de test
$createUser1Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, created_at, updated_at) VALUES ('test1@example.com', 'Test', 'User1', '\$2y\$13\$test', '[\"ROLE_USER\"]', NOW(), NOW())\"";
exec($createUser1Command, $createUser1Output, $createUser1ReturnCode);

$createUser2Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, created_at, updated_at) VALUES ('test2@example.com', 'Test', 'User2', '\$2y\$13\$test', '[\"ROLE_USER\"]', NOW(), NOW())\"";
exec($createUser2Command, $createUser2Output, $createUser2ReturnCode);

if ($createUser1ReturnCode === 0 && $createUser2ReturnCode === 0) {
    echo "✅ Utilisateurs de test créés\n";
} else {
    echo "❌ Erreur lors de la création des utilisateurs\n";
}

echo "\n🔧 ÉTAPE 4: Récupération des IDs des utilisateurs...\n";

// Récupérer les IDs des utilisateurs
$getUser1IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'test1@example.com'\"";
exec($getUser1IdCommand, $getUser1IdOutput, $getUser1IdReturnCode);

$getUser2IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'test2@example.com'\"";
exec($getUser2IdCommand, $getUser2IdOutput, $getUser2IdReturnCode);

$user1Id = null;
$user2Id = null;

if ($getUser1IdReturnCode === 0 && !empty($getUser1IdOutput)) {
    foreach ($getUser1IdOutput as $line) {
        if (is_numeric(trim($line))) {
            $user1Id = trim($line);
            break;
        }
    }
}

if ($getUser2IdReturnCode === 0 && !empty($getUser2IdOutput)) {
    foreach ($getUser2IdOutput as $line) {
        if (is_numeric(trim($line))) {
            $user2Id = trim($line);
            break;
        }
    }
}

if ($user1Id && $user2Id) {
    echo "✅ IDs des utilisateurs récupérés: User1=" . $user1Id . ", User2=" . $user2Id . "\n";
} else {
    echo "❌ Impossible de récupérer les IDs des utilisateurs\n";
    exit;
}

echo "\n🔧 ÉTAPE 5: Création de files d'attente...\n";

// Créer des files d'attente
$createFile1Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut, created_at, updated_at) VALUES ($lotId, $user1Id, 1, 'en_attente', NOW(), NOW())\"";
exec($createFile1Command, $createFile1Output, $createFile1ReturnCode);

$createFile2Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut, created_at, updated_at) VALUES ($lotId, $user2Id, 2, 'en_attente', NOW(), NOW())\"";
exec($createFile2Command, $createFile2Output, $createFile2ReturnCode);

if ($createFile1ReturnCode === 0 && $createFile2ReturnCode === 0) {
    echo "✅ Files d'attente créées\n";
} else {
    echo "❌ Erreur lors de la création des files d'attente\n";
}

echo "\n🔧 ÉTAPE 6: Création d'une commande de test...\n";

// Créer une commande de test
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut, created_at, updated_at) VALUES ($user1Id, $lotId, 1, 100.00, 100.00, 'reserve', NOW(), NOW())\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande de test créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🔧 ÉTAPE 7: Mise à jour du lot en statut 'réservé'...\n";

// Mettre le lot en statut réservé
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $user1Id, reserve_at = NOW() WHERE id = $lotId\"";
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
echo "4. Vérifier que User1 (premier en file) est notifié\n";
echo "5. Vérifier les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📊 DONNÉES CRÉÉES:\n";
echo "- Lot ID: $lotId\n";
echo "- User1 ID: $user1Id (premier en file)\n";
echo "- User2 ID: $user2Id (deuxième en file)\n";
echo "- Commande créée et lot réservé\n\n";

echo "=== FIN DE LA SIMULATION ===\n";


