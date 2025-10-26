<?php
echo "=== FORCE NOTIFICATION TEST ===\n\n";

echo "🔧 ÉTAPE 1: Création d'un lot simple...\n";

// Créer un lot simple
$createLotCommand = "php bin/console doctrine:query:sql \"INSERT INTO lot (name, description, prix, quantite, statut) VALUES ('Lot Test', 'Description test', 100.00, 1, 'disponible')\"";
exec($createLotCommand, $createLotOutput, $createLotReturnCode);

if ($createLotReturnCode === 0) {
    echo "✅ Lot créé\n";
} else {
    echo "❌ Erreur lors de la création du lot\n";
}

echo "\n🔧 ÉTAPE 2: Récupération de l'ID du lot...\n";

// Récupérer l'ID du lot
$getLotIdCommand = "php bin/console doctrine:query:sql \"SELECT id FROM lot WHERE name = 'Lot Test' ORDER BY id DESC LIMIT 1\"";
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

echo "\n🔧 ÉTAPE 3: Création d'un utilisateur simple...\n";

// Créer un utilisateur simple
$createUserCommand = "php bin/console doctrine:query:sql \"INSERT INTO user (email, name, lastname, password, roles) VALUES ('test@example.com', 'Test', 'User', '\$2y\$13\$test', '[\"ROLE_USER\"]')\"";
exec($createUserCommand, $createUserOutput, $createUserReturnCode);

if ($createUserReturnCode === 0) {
    echo "✅ Utilisateur créé\n";
} else {
    echo "❌ Erreur lors de la création de l'utilisateur\n";
}

echo "\n🔧 ÉTAPE 4: Récupération de l'ID de l'utilisateur...\n";

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

echo "\n🔧 ÉTAPE 5: Création d'une file d'attente...\n";

// Créer une file d'attente
$createFileCommand = "php bin/console doctrine:query:sql \"INSERT INTO file_attente (lot_id, user_id, position, statut) VALUES ($lotId, $userId, 1, 'en_attente')\"";
exec($createFileCommand, $createFileOutput, $createFileReturnCode);

if ($createFileReturnCode === 0) {
    echo "✅ File d'attente créée\n";
} else {
    echo "❌ Erreur lors de la création de la file d'attente\n";
}

echo "\n🔧 ÉTAPE 6: Création d'une commande...\n";

// Créer une commande
$createCommandeCommand = "php bin/console doctrine:query:sql \"INSERT INTO commande (user_id, lot_id, quantite, prix_unitaire, prix_total, statut) VALUES ($userId, $lotId, 1, 100.00, 100.00, 'reserve')\"";
exec($createCommandeCommand, $createCommandeOutput, $createCommandeReturnCode);

if ($createCommandeReturnCode === 0) {
    echo "✅ Commande créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

echo "\n🔧 ÉTAPE 7: Mise à jour du lot en statut 'réservé'...\n";

// Mettre le lot en statut réservé
$updateLotCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET statut = 'reserve', reserve_par_id = $userId WHERE id = $lotId\"";
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
echo "- Lot ID: $lotId\n";
echo "- User ID: $userId\n";
echo "- Commande créée et lot réservé\n";
echo "- File d'attente créée\n\n";

echo "=== FIN DE LA SIMULATION ===\n";
