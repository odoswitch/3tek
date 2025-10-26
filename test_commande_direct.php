<?php
// Test direct de la logique de commande
echo "=== TEST DIRECT DE LA COMMANDE ===\n\n";

// Simuler les données
$lotQuantite = 2;  // Quantité actuelle du lot
$commandeQuantite = 2;  // Quantité commandée
$lotPrix = 0.00;  // Prix actuel (problématique)

echo "Données de simulation:\n";
echo "- Quantité du lot: $lotQuantite\n";
echo "- Quantité commandée: $commandeQuantite\n";
echo "- Prix du lot: $lotPrix €\n\n";

// Calculer la nouvelle quantité
$nouvelleQuantite = $lotQuantite - $commandeQuantite;
echo "Calcul:\n";
echo "- Nouvelle quantité: $nouvelleQuantite\n";
echo "- Stock atteint 0? " . ($nouvelleQuantite <= 0 ? "OUI" : "NON") . "\n\n";

// Simuler la logique du contrôleur
if ($nouvelleQuantite <= 0) {
    echo "✅ LOGIQUE: Le lot devrait être marqué comme 'reserve'\n";
    echo "✅ SQL: UPDATE lot SET quantite = 0, statut = 'reserve', reserve_par_id = [user_id]\n";
    echo "✅ Prix total de la commande: " . ($lotPrix * $commandeQuantite) . " €\n";
} else {
    echo "❌ LOGIQUE: Le lot devrait juste décrémenter la quantité\n";
    echo "❌ SQL: UPDATE lot SET quantite = $nouvelleQuantite\n";
    echo "❌ Prix total de la commande: " . ($lotPrix * $commandeQuantite) . " €\n";
}

echo "\n=== PROBLÈME IDENTIFIÉ ===\n";
if ($lotPrix == 0) {
    echo "❌ PROBLÈME PRINCIPAL: Le lot a un prix de 0 €\n";
    echo "   - La commande est créée avec un prix total de 0 €\n";
    echo "   - Le stock est mis à jour correctement\n";
    echo "   - Mais le prix de 0 € peut causer des problèmes d'affichage\n";
} else {
    echo "✅ Le prix est correct: $lotPrix €\n";
}

echo "\n=== SOLUTION ===\n";
echo "1. Corriger le prix du lot dans la base de données\n";
echo "2. Tester une nouvelle commande\n";
echo "3. Vérifier que le stock est mis à jour\n";

echo "\n=== FIN DU TEST ===\n";
