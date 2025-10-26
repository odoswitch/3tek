<?php
// Test du contrôleur de commande
echo "=== TEST DU CONTRÔLEUR DE COMMANDE ===\n\n";

echo "Problème identifié:\n";
echo "1. Le lot a un prix de 12 € (corrigé)\n";
echo "2. Le stock reste à 2 (non mis à jour)\n";
echo "3. Le contrôleur n'est pas appelé ou il y a une erreur\n\n";

echo "Causes possibles:\n";
echo "1. ❌ Le contrôleur n'est pas appelé (problème de route)\n";
echo "2. ❌ Erreur dans le contrôleur avant la mise à jour du stock\n";
echo "3. ❌ Problème de transaction de base de données\n";
echo "4. ❌ Le code de mise à jour n'est pas exécuté\n\n";

echo "Solutions à tester:\n";
echo "1. Vérifier que la route /commande/create/{lotId} existe\n";
echo "2. Tester directement la création d'une commande\n";
echo "3. Vérifier les logs d'erreur\n";
echo "4. Tester la logique de mise à jour manuellement\n\n";

echo "=== FIN DU TEST ===\n";
