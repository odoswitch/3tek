<?php
// Test simple du problème
echo "=== DIAGNOSTIC DU PROBLÈME ===\n\n";

echo "Problème identifié:\n";
echo "1. Le lot 'David' a un prix de 0 €\n";
echo "2. Une commande de 2 unités a été créée avec un prix total de 0 €\n";
echo "3. Le stock du lot n'a pas été mis à jour\n\n";

echo "Causes possibles:\n";
echo "1. ❌ Prix de 0 € - Problème d'affichage mais pas de logique\n";
echo "2. ❌ Code de mise à jour du stock non exécuté\n";
echo "3. ❌ Erreur silencieuse dans le contrôleur\n";
echo "4. ❌ Problème de transaction de base de données\n\n";

echo "Solutions à tester:\n";
echo "1. Corriger le prix du lot via phpMyAdmin\n";
echo "2. Tester une nouvelle commande\n";
echo "3. Vérifier les logs d'erreur\n";
echo "4. Tester la logique de mise à jour du stock\n\n";

echo "=== FIN DU DIAGNOSTIC ===\n";


