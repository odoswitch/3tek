<?php
echo "=== TEST LOGIQUE CORRIGÉE ===\n\n";

echo "🔧 ÉTAPE 1: Nettoyage des données de test précédentes...\n";

// Nettoyer les files d'attente existantes pour le lot 14
$cleanFilesCommand = "php bin/console doctrine:query:sql \"DELETE FROM file_attente WHERE lot_id = 14\"";
exec($cleanFilesCommand, $cleanFilesOutput, $cleanFilesReturnCode);

if ($cleanFilesReturnCode === 0) {
    echo "✅ Files d'attente nettoyées\n";
} else {
    echo "❌ Erreur lors du nettoyage des files d'attente\n";
}

echo "\n🔧 ÉTAPE 2: Création d'utilisateurs pour le test...\n";

// Créer des utilisateurs pour le test
$createUser1Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('testlogique1@example.com', 'TestLogique1', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createUser1Command, $createUser1Output, $createUser1ReturnCode);

$createUser2Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('testlogique2@example.com', 'TestLogique2', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createUser2Command, $createUser2Output, $createUser2ReturnCode);

$createUser3Command = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles, office, phone, address, code, ville, pays, is_verified) VALUES ('testlogique3@example.com', 'TestLogique3', 'User', '\$2y\$13\$test', '[]', 'Test Office', '0123456789', 'Test Address', '12345', 'Test City', 'Test Country', 1)\"";
exec($createUser3Command, $createUser3Output, $createUser3ReturnCode);

if ($createUser1ReturnCode === 0 && $createUser2ReturnCode === 0 && $createUser3ReturnCode === 0) {
    echo "✅ Utilisateurs de test créés\n";
} else {
    echo "❌ Erreur lors de la création des utilisateurs\n";
}

echo "\n🔧 ÉTAPE 3: Récupération des IDs des utilisateurs...\n";

// Récupérer les IDs des utilisateurs
$getUser1IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'testlogique1@example.com'\"";
exec($getUser1IdCommand, $getUser1IdOutput, $getUser1IdReturnCode);

$getUser2IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'testlogique2@example.com'\"";
exec($getUser2IdCommand, $getUser2IdOutput, $getUser2IdReturnCode);

$getUser3IdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM user WHERE email = 'testlogique3@example.com'\"";
exec($getUser3IdCommand, $getUser3IdOutput, $getUser3IdReturnCode);

$user1Id = null;
$user2Id = null;
$user3Id = null;

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

if ($getUser3IdReturnCode === 0 && !empty($getUser3IdOutput)) {
    foreach ($getUser3IdOutput as $line) {
        if (is_numeric(trim($line))) {
            $user3Id = trim($line);
            break;
        }
    }
}

if ($user1Id && $user2Id && $user3Id) {
    echo "✅ IDs des utilisateurs récupérés: User1=" . $user1Id . ", User2=" . $user2Id . ", User3=" . $user3Id . "\n";
} else {
    echo "❌ Impossible de récupérer les IDs des utilisateurs\n";
    exit;
}

echo "\n🔧 ÉTAPE 4: Création d'une commande depuis l'interface utilisateur...\n";

// Créer une commande comme si elle venait de l'interface utilisateur
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut) VALUES ($user1Id, 14, 1, 2.00, 2.00, 'reserve')\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande créée depuis l'interface utilisateur\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🔧 ÉTAPE 5: Mise à jour du lot en statut 'réservé'...\n";

// Mettre le lot en statut réservé
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $user1Id WHERE id = 14\"";
exec($updateLotCommand, $updateLotOutput, $updateLotReturnCode);

if ($updateLotReturnCode === 0) {
    echo "✅ Lot mis en statut 'réservé'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du lot\n";
}

echo "\n🔧 ÉTAPE 6: Création de files d'attente...\n";

// Créer des files d'attente
$createFile1Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES (14, $user2Id, 1, 'en_attente')\"";
exec($createFile1Command, $createFile1Output, $createFile1ReturnCode);

$createFile2Command = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES (14, $user3Id, 2, 'en_attente')\"";
exec($createFile2Command, $createFile2Output, $createFile2ReturnCode);

if ($createFile1ReturnCode === 0 && $createFile2ReturnCode === 0) {
    echo "✅ Files d'attente créées\n";
} else {
    echo "❌ Erreur lors de la création des files d'attente\n";
}

echo "\n🔧 ÉTAPE 7: Vérification de l'état final...\n";

// Vérifier l'état du lot
$checkLotCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot WHERE id = 14\"";
exec($checkLotCommand, $checkLotOutput, $checkLotReturnCode);

if ($checkLotReturnCode === 0 && !empty($checkLotOutput)) {
    echo "État du lot 'Serveurs' :\n";
    foreach ($checkLotOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé\n";
}

// Vérifier les files d'attente
$checkFilesCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut FROM file_attente WHERE lot_id = 14 ORDER BY position ASC\"";
exec($checkFilesCommand, $checkFilesOutput, $checkFilesReturnCode);

if ($checkFilesReturnCode === 0 && !empty($checkFilesOutput)) {
    echo "\nFiles d'attente :\n";
    foreach ($checkFilesOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune file d'attente trouvée\n";
}

echo "\n🎯 LOGIQUE CORRIGÉE CRÉÉE !\n";
echo "Maintenant vous pouvez :\n";
echo "1. Vérifier que le lot 'Serveurs' est en statut 'réservé' sur l'interface utilisateur\n";
echo "2. Vérifier que User2 (premier en file) voit le lot comme 'disponible' pour lui\n";
echo "3. Vérifier que User3 (deuxième en file) voit le lot comme 'réservé'\n";
echo "4. Aller sur http://localhost:8080/admin\n";
echo "5. Supprimer la commande créée (ID du lot: 14)\n";
echo "6. Vérifier que le lot reste 'réservé' mais User2 peut le commander\n";
echo "7. Vérifier que User3 voit toujours le lot comme 'réservé'\n";
echo "8. Vérifier les logs avec: docker compose exec php php check_debug_logs.php\n\n";

echo "📊 DONNÉES CRÉÉES:\n";
echo "- Lot 'Serveurs' (ID: 14) en statut 'réservé' par User1 (ID: $user1Id)\n";
echo "- User2 ID: $user2Id (premier en file d'attente) - devrait voir le lot comme disponible\n";
echo "- User3 ID: $user3Id (deuxième en file d'attente) - devrait voir le lot comme réservé\n";
echo "- Commande créée depuis l'interface utilisateur\n";
echo "- Files d'attente créées\n\n";

echo "🔍 POUR VÉRIFIER LA LOGIQUE CORRIGÉE:\n";
echo "1. Vérifiez que le lot 'Serveurs' est bien en statut 'réservé' sur l'interface utilisateur\n";
echo "2. Connectez-vous avec User2 - il devrait voir le lot comme 'disponible' et pouvoir le commander\n";
echo "3. Connectez-vous avec User3 - il devrait voir le lot comme 'réservé' et voir le bouton 'Rejoindre la file d'attente'\n";
echo "4. Puis supprimez la commande via l'admin\n";
echo "5. User2 devrait pouvoir commander le lot immédiatement\n";
echo "6. User3 devrait toujours voir le lot comme 'réservé' (par User2)\n\n";

echo "=== FIN DE LA CRÉATION DE LA LOGIQUE CORRIGÉE ===\n";
