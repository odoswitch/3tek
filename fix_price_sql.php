<?php
// Script pour corriger le prix via SQL direct
echo "=== CORRECTION DU PRIX VIA SQL ===\n\n";

// Connexion directe à MySQL
$host = 'database';
$dbname = 'db_3tek';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion à la base de données réussie\n\n";

    // Vérifier l'état actuel
    $stmt = $pdo->prepare("SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE '%David%'");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lot) {
        echo "État actuel du lot:\n";
        echo "- ID: " . $lot['id'] . "\n";
        echo "- Nom: " . $lot['nom'] . "\n";
        echo "- Prix: " . $lot['prix'] . " €\n";
        echo "- Quantité: " . $lot['quantite'] . "\n";
        echo "- Statut: " . $lot['statut'] . "\n\n";

        // Corriger le prix
        if ($lot['prix'] == 0) {
            echo "Correction du prix...\n";
            $stmt = $pdo->prepare("UPDATE lot SET prix = 100.00 WHERE id = ?");
            $stmt->execute([$lot['id']]);
            echo "✅ Prix corrigé à 100,00 €\n\n";

            // Vérifier après correction
            $stmt = $pdo->prepare("SELECT id, nom, prix, quantite, statut FROM lot WHERE id = ?");
            $stmt->execute([$lot['id']]);
            $lotCorrige = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "État après correction:\n";
            echo "- Prix: " . $lotCorrige['prix'] . " €\n";
            echo "- Quantité: " . $lotCorrige['quantite'] . "\n";
            echo "- Statut: " . $lotCorrige['statut'] . "\n";
        } else {
            echo "✅ Le prix est déjà correct: " . $lot['prix'] . " €\n";
        }
    } else {
        echo "❌ Lot non trouvé!\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA CORRECTION ===\n";
