<?php
echo "=== TEST FILE D'ATTENTE COMPLET ===\n\n";

echo "🔧 ÉTAPE 1: Vérification de l'état actuel du lot 'Serveurs'...\n";

// Vérifier l'état actuel du lot
$checkLotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE name = 'Serveurs'\"";
exec($checkLotCommand, $checkLotOutput, $checkLotReturnCode);

if ($checkLotReturnCode === 0 && !empty($checkLotOutput)) {
    echo "État actuel du lot 'Serveurs' :\n";
    foreach ($checkLotOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Création d'un utilisateur pour réserver le lot...\n";

// Créer un utilisateur pour réserver
$createReserverCommand = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('reserver@example.com', 'Reserver', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createReserverCommand, $createReserverOutput, $createReserverReturnCode);

if ($createReserverReturnCode === 0) {
    echo "✅ Utilisateur 'Reserver' créé\n";
} else {
    echo "❌ Erreur lors de la création de l'utilisateur\n";
}

echo "\n🔧 ÉTAPE 3: Récupération de l'ID de l'utilisateur 'Reserver'...\n";

// Récupérer l'ID de l'utilisateur 'Reserver'
$getReserverIdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'reserver@example.com'\"";
exec($getReserverIdCommand, $getReserverIdOutput, $getReserverIdReturnCode);

$reserverId = null;
if ($getReserverIdReturnCode === 0 && !empty($getReserverIdOutput)) {
    foreach ($getReserverIdOutput as $line) {
        if (is_numeric(trim($line))) {
            $reserverId = trim($line);
            break;
        }
    }
}

if ($reserverId) {
    echo "✅ ID de l'utilisateur 'Reserver' récupéré: " . $reserverId . "\n";
} else {
    echo "❌ Impossible de récupérer l'ID de l'utilisateur 'Reserver'\n";
    exit;
}

echo "\n🔧 ÉTAPE 4: Création d'utilisateurs pour la file d'attente...\n";

// Créer des utilisateurs pour la file d'attente
$createWaiter1Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('waiter1@example.com', 'Waiter1', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createWaiter1Command, $createWaiter1Output, $createWaiter1ReturnCode);

$createWaiter2Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('waiter2@example.com', 'Waiter2', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createWaiter2Command, $createWaiter2Output, $createWaiter2ReturnCode);

if ($createWaiter1ReturnCode === 0 && $createWaiter2ReturnCode === 0) {
    echo "✅ Utilisateurs 'Waiter1' et 'Waiter2' créés\n";
} else {
    echo "❌ Erreur lors de la création des utilisateurs\n";
}

echo "\n🔧 ÉTAPE 5: Récupération des IDs des utilisateurs 'Waiter'...\n";

// Récupérer les IDs des utilisateurs 'Waiter'
$getWaiter1IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'waiter1@example.com'\"";
exec($getWaiter1IdCommand, $getWaiter1IdOutput, $getWaiter1IdReturnCode);

$getWaiter2IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'waiter2@example.com'\"";
exec($getWaiter2IdCommand, $getWaiter2IdOutput, $getWaiter2IdReturnCode);

$waiter1Id = null;
$waiter2Id = null;

if ($getWaiter1IdReturnCode === 0 && !empty($getWaiter1IdOutput)) {
    foreach ($getWaiter1IdOutput as $line) {
        if (is_numeric(trim($line))) {
            $waiter1Id = trim($line);
            break;
        }
    }
}

if ($getWaiter2IdReturnCode === 0 && !empty($getWaiter2IdOutput)) {
    foreach ($getWaiter2IdOutput as $line) {
        if (is_numeric(trim($line))) {
            $waiter2Id = trim($line);
            break;
        }
    }
}

if ($waiter1Id && $waiter2Id) {
    echo "✅ IDs des utilisateurs 'Waiter' récupérés: Waiter1=" . $waiter1Id . ", Waiter2=" . $waiter2Id . "\n";
} else {
    echo "❌ Impossible de récupérer les IDs des utilisateurs 'Waiter'\n";
    exit;
}

echo "\n🔧 ÉTAPE 6: Mise à jour du lot 'Serveurs' en statut 'réservé' par l'utilisateur 'Reserver'...\n";

// Mettre le lot en statut réservé par l'utilisateur 'Reserver'
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $reserverId WHERE name = 'Serveurs'\"";
exec($updateLotCommand, $updateLotOutput, $updateLotReturnCode);

if ($updateLotReturnCode === 0) {
    echo "✅ Lot 'Serveurs' mis en statut 'réservé' par l'utilisateur 'Reserver'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du lot\n";
}

echo "\n🔧 ÉTAPE 7: Création de files d'attente...\n";

// Récupérer l'ID du lot 'Serveurs'
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
    $createFile1Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES ($lotId, $waiter1Id, 1, 'en_attente')\"";
    exec($createFile1Command, $createFile1Output, $createFile1ReturnCode);

    $createFile2Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES ($lotId, $waiter2Id, 2, 'en_attente')\"";
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

echo "\n🔧 ÉTAPE 8: Création d'une commande de test...\n";

// Créer une commande de test
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut) VALUES ($reserverId, $lotId, 1, 2.00, 2.00, 'reserve')\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande de test créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🎯 SITUATION DE TEST CRÉÉE !\n";
echo "Maintenant vous pouvez :\n";
echo "1. Vérifier que le lot 'Serveurs' est en statut 'réservé' sur l'interface utilisateur\n";
echo "2. Aller sur http://localhost:8080/admin\n";
echo "3. Supprimer la commande créée (ID du lot: $lotId)\n";
echo "4. Vérifier que le lot passe à 'disponible'\n";
echo "5. Vérifier que Waiter1 (premier en file) est notifié\n";
echo "6. Vérifier les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📊 DONNÉES CRÉÉES:\n";
echo "- Lot 'Serveurs' (ID: $lotId) en statut 'réservé' par l'utilisateur 'Reserver' (ID: $reserverId)\n";
echo "- Waiter1 ID: $waiter1Id (premier en file d'attente)\n";
echo "- Waiter2 ID: $waiter2Id (deuxième en file d'attente)\n";
echo "- Commande créée et lot réservé\n";
echo "- Files d'attente créées\n\n";

echo "🔍 POUR VÉRIFIER AVANT SUPPRESSION:\n";
echo "Vérifiez d'abord que le lot 'Serveurs' est bien en statut 'réservé' sur l'interface utilisateur\n";
echo "Puis supprimez la commande via l'admin et vérifiez qu'il passe à 'disponible'\n";
echo "Et que Waiter1 (premier en file) est notifié\n\n";

echo "=== FIN DE LA CRÉATION DU TEST ===\n";



