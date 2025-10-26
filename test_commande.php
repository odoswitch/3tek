<?php
// Script de test pour simuler une commande
echo "=== TEST DE COMMANDE ===\n\n";

// Simuler les données de la commande
$lotId = 1; // ID du lot David
$quantite = 2;
$userId = 1; // ID de l'utilisateur

echo "Données de test:\n";
echo "- Lot ID: $lotId\n";
echo "- Quantité: $quantite\n";
echo "- User ID: $userId\n\n";

// Vérifier l'état avant
echo "=== ÉTAT AVANT COMMANDE ===\n";
echo "Lot ID: $lotId\n";
echo "Quantité demandée: $quantite\n";
echo "Nouvelle quantité calculée: " . (2 - $quantite) . "\n";
echo "Stock atteint 0? " . ((2 - $quantite) <= 0 ? "OUI" : "NON") . "\n\n";

// Simuler la logique
$nouvelleQuantite = 2 - $quantite; // 2 - 2 = 0

if ($nouvelleQuantite <= 0) {
    echo "✅ LOGIQUE: Le lot devrait être marqué comme 'reserve'\n";
    echo "✅ SQL: UPDATE lot SET quantite = 0, statut = 'reserve', reserve_par_id = $userId\n";
} else {
    echo "❌ LOGIQUE: Le lot devrait juste décrémenter la quantité\n";
    echo "❌ SQL: UPDATE lot SET quantite = $nouvelleQuantite\n";
}

echo "\n=== RÉSULTAT ATTENDU ===\n";
echo "Le lot devrait avoir:\n";
echo "- Quantité: 0\n";
echo "- Statut: 'reserve'\n";
echo "- Réservé par: User ID $userId\n";
echo "- Réservé le: " . date('Y-m-d H:i:s') . "\n";

echo "\n=== FIN DU TEST ===\n";

