<?php
// Diagnostic final du problème
echo "=== DIAGNOSTIC FINAL ===\n\n";

// Connexion directe à la base de données
try {
    $pdo = new PDO('mysql:host=database;dbname=db_3tek', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion à la base de données réussie\n\n";

    // Vérifier le lot David
    $stmt = $pdo->prepare("SELECT id, nom, prix, quantite, statut, reserve_par_id, reserve_at FROM lot WHERE nom LIKE '%David%'");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lot) {
        echo "LOT ACTUEL:\n";
        echo "- ID: " . $lot['id'] . "\n";
        echo "- Nom: " . $lot['nom'] . "\n";
        echo "- Prix: " . $lot['prix'] . " €\n";
        echo "- Quantité: " . $lot['quantite'] . "\n";
        echo "- Statut: " . $lot['statut'] . "\n";
        echo "- Réservé par: " . ($lot['reserve_par_id'] ?: 'Aucun') . "\n";
        echo "- Réservé le: " . ($lot['reserve_at'] ?: 'Jamais') . "\n\n";

        // Vérifier les commandes récentes
        $stmt = $pdo->prepare("SELECT c.id, c.quantite, c.statut, c.prix_total, c.created_at, u.email 
                               FROM commande c 
                               JOIN user u ON c.user_id = u.id 
                               WHERE c.lot_id = ? 
                               ORDER BY c.created_at DESC 
                               LIMIT 3");
        $stmt->execute([$lot['id']]);
        $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "COMMANDES RÉCENTES:\n";
        foreach ($commandes as $commande) {
            echo "- ID: " . $commande['id'] . "\n";
            echo "- Client: " . $commande['email'] . "\n";
            echo "- Quantité: " . $commande['quantite'] . "\n";
            echo "- Statut: " . $commande['statut'] . "\n";
            echo "- Prix total: " . $commande['prix_total'] . " €\n";
            echo "- Créé le: " . $commande['created_at'] . "\n";
            echo "---\n";
        }

        // Analyser le problème
        echo "\n=== ANALYSE DU PROBLÈME ===\n";

        if ($lot['quantite'] == 2) {
            echo "❌ PROBLÈME CONFIRMÉ: Le stock n'a pas été mis à jour\n";
            echo "   - Quantité actuelle: " . $lot['quantite'] . "\n";
            echo "   - Statut: " . $lot['statut'] . "\n";
            echo "   - Le code de mise à jour ne s'exécute pas\n\n";

            echo "CAUSES POSSIBLES:\n";
            echo "1. ❌ Erreur silencieuse dans le contrôleur\n";
            echo "2. ❌ Problème de transaction de base de données\n";
            echo "3. ❌ Le code n'est pas exécuté\n";
            echo "4. ❌ Problème avec la connexion à la base de données\n\n";

            echo "SOLUTIONS À TESTER:\n";
            echo "1. Ajouter des logs de débogage dans le contrôleur\n";
            echo "2. Vérifier les logs d'erreur de l'application\n";
            echo "3. Tester la logique de mise à jour manuellement\n";
            echo "4. Vérifier que le code est bien exécuté\n";
        } else {
            echo "✅ Le stock semble correctement mis à jour\n";
        }
    } else {
        echo "❌ Lot non trouvé!\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";


