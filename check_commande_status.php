<?php
echo "=== VÉRIFICATION STATUT COMMANDES ===\n\n";

// Connexion à la base de données
$host = 'database';
$dbname = 'app';
$username = 'root';
$password = '!ChangeMe!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 ÉTAPE 1: Vérification des commandes...\n";

    // Récupérer toutes les commandes avec leur statut
    $stmt = $pdo->query("SELECT id, statut, created_at FROM commande ORDER BY created_at DESC LIMIT 10");
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Dernières commandes :\n";
    foreach ($commandes as $commande) {
        echo "- Commande ID: {$commande['id']}, Statut: {$commande['statut']}, Date: {$commande['created_at']}\n";
    }

    echo "\n🔍 ÉTAPE 2: Vérification des lots...\n";

    // Récupérer tous les lots avec leur statut
    $stmt = $pdo->query("SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 10");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Derniers lots :\n";
    foreach ($lots as $lot) {
        echo "- Lot ID: {$lot['id']}, Nom: {$lot['name']}, Statut: {$lot['statut']}, Quantité: {$lot['quantite']}, Réservé par: {$lot['reserve_par_id']}\n";
    }

    echo "\n🔍 ÉTAPE 3: Vérification des files d'attente...\n";

    // Récupérer les files d'attente
    $stmt = $pdo->query("SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY created_at DESC LIMIT 5");
    $filesAttente = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Files d'attente :\n";
    foreach ($filesAttente as $file) {
        echo "- File ID: {$file['id']}, Lot ID: {$file['lot_id']}, User ID: {$file['user_id']}, Position: {$file['position']}, Statut: {$file['statut']}\n";
    }

    echo "\n📊 RÉSUMÉ:\n";
    echo "- Commandes trouvées: " . count($commandes) . "\n";
    echo "- Lots trouvés: " . count($lots) . "\n";
    echo "- Files d'attente: " . count($filesAttente) . "\n";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";
