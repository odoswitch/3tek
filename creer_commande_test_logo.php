<?php
/**
 * Création d'une commande de test pour vérifier le logo PDF
 */

echo "=== CRÉATION COMMANDE DE TEST POUR LOGO PDF ===\n\n";

// Charger l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Test 1: Vérifier les utilisateurs existants
echo "1. Vérification des utilisateurs...\n";
$users = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "SELECT id, name, lastname, email FROM user LIMIT 3" 2>/dev/null');
if ($users) {
    echo "✅ Utilisateurs disponibles:\n";
    echo $users;
} else {
    echo "❌ Aucun utilisateur trouvé\n";
}

// Test 2: Vérifier les lots existants
echo "\n2. Vérification des lots...\n";
$lots = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "SELECT id, name, prix, quantite FROM lot LIMIT 3" 2>/dev/null');
if ($lots) {
    echo "✅ Lots disponibles:\n";
    echo $lots;
} else {
    echo "❌ Aucun lot trouvé\n";
}

// Test 3: Créer une commande de test
echo "\n3. Création d'une commande de test...\n";
$createCommandeSQL = "
INSERT INTO commande (numero_commande, statut, prix_total, created_at, user_id) 
VALUES ('CMD-TEST-LOGO-' . UNIX_TIMESTAMP(), 'en_attente', 100.00, NOW(), 2)
";

$result = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "' . $createCommandeSQL . '" 2>/dev/null');
if ($result) {
    echo "✅ Commande de test créée\n";
} else {
    echo "❌ Erreur lors de la création de la commande\n";
}

// Test 4: Récupérer l'ID de la commande créée
echo "\n4. Récupération de la commande créée...\n";
$commandeTest = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "SELECT id, numero_commande, statut FROM commande WHERE numero_commande LIKE \'CMD-TEST-LOGO-%\' ORDER BY id DESC LIMIT 1" 2>/dev/null');
if ($commandeTest) {
    echo "✅ Commande de test trouvée:\n";
    echo $commandeTest;
} else {
    echo "❌ Commande de test non trouvée\n";
}

// Test 5: Créer une ligne de commande
echo "\n5. Création d'une ligne de commande...\n";
$createLigneSQL = "
INSERT INTO commande_ligne (commande_id, lot_id, quantite, prix_unitaire, prix_total) 
SELECT c.id, l.id, 1, l.prix, l.prix 
FROM commande c, lot l 
WHERE c.numero_commande LIKE 'CMD-TEST-LOGO-%' 
AND l.id = (SELECT id FROM lot LIMIT 1)
ORDER BY c.id DESC 
LIMIT 1
";

$result = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "' . $createLigneSQL . '" 2>/dev/null');
if ($result) {
    echo "✅ Ligne de commande créée\n";
} else {
    echo "❌ Erreur lors de la création de la ligne\n";
}

// Test 6: Vérifier la commande complète
echo "\n6. Vérification de la commande complète...\n";
$commandeComplete = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "
SELECT 
    c.id, 
    c.numero_commande, 
    c.statut, 
    c.prix_total,
    u.name, 
    u.lastname, 
    u.email,
    l.name as lot_name,
    cl.quantite,
    cl.prix_unitaire
FROM commande c 
LEFT JOIN user u ON c.user_id = u.id 
LEFT JOIN commande_ligne cl ON c.id = cl.commande_id 
LEFT JOIN lot l ON cl.lot_id = l.id 
WHERE c.numero_commande LIKE \'CMD-TEST-LOGO-%\' 
ORDER BY c.id DESC 
LIMIT 1
" 2>/dev/null');

if ($commandeComplete) {
    echo "✅ Commande complète trouvée:\n";
    echo $commandeComplete;
} else {
    echo "❌ Commande complète non trouvée\n";
}

echo "\n=== INSTRUCTIONS POUR TESTER LE LOGO ===\n";
echo "\n📋 TEST PDF ADMIN:\n";
echo "1. Allez sur: http://localhost:8080/admin/commande\n";
echo "2. Trouvez la commande de test (CMD-TEST-LOGO-...)\n";
echo "3. Cliquez sur 'Générer PDF'\n";
echo "4. Vérifiez que le logo s'affiche correctement\n";
echo "5. Vérifiez que l'email est: contact@3tek-europe.com\n";

echo "\n📋 TEST PDF CLIENT:\n";
echo "1. Connectez-vous avec l'utilisateur ID 2\n";
echo "2. Allez dans 'Mes commandes'\n";
echo "3. Trouvez la commande de test\n";
echo "4. Cliquez sur 'PDF'\n";
echo "5. Vérifiez que le logo s'affiche correctement\n";

echo "\n📋 VÉRIFICATIONS À FAIRE:\n";
echo "✅ Logo visible dans le header (pas de texte 'Logo 3Tek-Europe')\n";
echo "✅ Logo visible dans le footer\n";
echo "✅ Email correct: contact@3tek-europe.com\n";
echo "✅ Design professionnel avec layout flexbox\n";
echo "✅ Descriptions propres sans HTML\n";

echo "\n🎉 COMMANDE DE TEST CRÉÉE !\n";
echo "Vous pouvez maintenant tester la génération PDF avec le logo !\n";
?>

