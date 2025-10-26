<?php
// Script pour corriger le prix du lot et tester la logique
echo "=== CORRECTION ET TEST DU LOT ===\n\n";

// Utiliser la console Symfony
$output = shell_exec('docker compose exec php bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'" 2>&1');
echo "État actuel du lot:\n";
echo $output . "\n";

// Corriger le prix si nécessaire
echo "Correction du prix...\n";
$fixOutput = shell_exec('docker compose exec php bin/console doctrine:query:sql "UPDATE lot SET prix = 100.00 WHERE nom LIKE \'%David%\'" 2>&1');
echo $fixOutput . "\n";

// Vérifier après correction
echo "Vérification après correction:\n";
$checkOutput = shell_exec('docker compose exec php bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'" 2>&1');
echo $checkOutput . "\n";

echo "=== FIN DE LA CORRECTION ===\n";

