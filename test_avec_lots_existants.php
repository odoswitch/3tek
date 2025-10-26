<?php
echo "=== TEST AVEC LOTS EXISTANTS ===\n\n";

echo "🔧 ÉTAPE 1: Vérification des lots existants...\n";

// Vérifier les lots existants
$checkLotsCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE name IN ('Serveurs', 'Lot David', 'HP Serveur') ORDER BY id DESC\"";
exec($checkLotsCommand, $checkLotsOutput, $checkLotsReturnCode);

if ($checkLotsReturnCode === 0 && !empty($checkLotsOutput)) {
    echo "Lots existants trouvés :\n";
    foreach ($checkLotsOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot existant trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Création d'utilisateurs de test...\n";

// Créer des utilisateurs de test
$createUser1Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('testexist1@example.com', 'TestExist', 'User1', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createUser1Command, $createUser1Output, $createUser1ReturnCode);

$createUser2Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('testexist2@example.com', 'TestExist', 'User2', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createUser2Command, $createUser2Output, $createUser2ReturnCode);

if ($createUser1ReturnCode === 0 && $createUser2ReturnCode === 0) {
    echo "✅ Utilisateurs de test créés\n";
} else {
    echo "❌ Erreur lors de la création des utilisateurs\n";
}

echo "\n🔧 ÉTAPE 3: Récupération des IDs des utilisateurs...\n";

// Récupérer les IDs des utilisateurs
$getUser1IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'testexist1@example.com'\"";
exec($getUser1IdCommand, $getUser1IdOutput, $getUser1IdReturnCode);

$getUser2IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'testexist2@example.com'\"";
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

echo "\n🔧 ÉTAPE 4: Mise à jour du lot 'Serveurs' en statut 'réservé'...\n";

// Mettre le lot 'Serveurs' en statut réservé
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $user1Id WHERE name = 'Serveurs'\"";
exec($updateLotCommand, $updateLotOutput, $updateLotReturnCode);

if ($updateLotReturnCode === 0) {
    echo "✅ Lot 'Serveurs' mis en statut 'réservé'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du lot\n";
}

echo "\n🔧 ÉTAPE 5: Création de files d'attente...\n";

// Créer des files d'attente pour le lot 'Serveurs'
$getLotIdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM lot WHERE name = 'Serveurs'\"";
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
    echo "✅ ID du lot 'Serveurs' récupéré: " . $lotId . "\n";

    // Créer des files d'attente
    $createFile1Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES ($lotId, $user1Id, 1, 'en_attente')\"";
    exec($createFile1Command, $createFile1Output, $createFile1ReturnCode);

    $createFile2Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES ($lotId, $user2Id, 2, 'en_attente')\"";
    exec($createFile2Command, $createFile2Output, $createFile2ReturnCode);

    if ($createFile1ReturnCode === 0 && $createFile2ReturnCode === 0) {
        echo "✅ Files d'attente créées\n";
    } else {
        echo "❌ Erreur lors de la création des files d'attente\n";
    }
} else {
    echo "❌ Impossible de récupérer l'ID du lot 'Serveurs'\n";
    exit;
}

echo "\n🔧 ÉTAPE 6: Création d'une commande de test...\n";

// Créer une commande de test
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut) VALUES ($user1Id, $lotId, 1, 2.00, 2.00, 'reserve')\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande de test créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🎯 SITUATION DE TEST CRÉÉE AVEC LOTS EXISTANTS !\n";
echo "Maintenant vous pouvez :\n";
echo "1. Vérifier que le lot 'Serveurs' est en statut 'réservé' sur l'interface utilisateur\n";
echo "2. Aller sur http://localhost:8080/admin\n";
echo "3. Supprimer la commande créée (ID du lot: $lotId)\n";
echo "4. Vérifier que le lot passe à 'disponible'\n";
echo "5. Vérifier que User1 (premier en file) est notifié\n";
echo "6. Vérifier les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📊 DONNÉES CRÉÉES:\n";
echo "- Lot 'Serveurs' (ID: $lotId) en statut 'réservé'\n";
echo "- User1 ID: $user1Id (premier en file d'attente)\n";
echo "- User2 ID: $user2Id (deuxième en file d'attente)\n";
echo "- Commande créée et lot réservé\n";
echo "- Files d'attente créées\n\n";

echo "🔍 POUR VÉRIFIER AVANT SUPPRESSION:\n";
echo "Vérifiez d'abord que le lot 'Serveurs' est bien en statut 'réservé' sur l'interface utilisateur\n";
echo "Puis supprimez la commande via l'admin et vérifiez qu'il passe à 'disponible'\n\n";

echo "=== FIN DE LA CRÉATION DU TEST ===\n";
