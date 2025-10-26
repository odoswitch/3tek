<?php
// Script pour corriger le prix via la console Symfony
echo "=== CORRECTION DU PRIX VIA CONSOLE ===\n\n";

// Commande pour vérifier l'état actuel
echo "Vérification de l'état actuel...\n";
$checkCmd = 'docker compose exec php bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'"';
echo "Commande: $checkCmd\n";
$output = shell_exec($checkCmd . ' 2>&1');
echo "Résultat:\n$output\n\n";

// Commande pour corriger le prix
echo "Correction du prix...\n";
$fixCmd = 'docker compose exec php bin/console doctrine:query:sql "UPDATE lot SET prix = 100.00 WHERE nom LIKE \'%David%\'"';
echo "Commande: $fixCmd\n";
$fixOutput = shell_exec($fixCmd . ' 2>&1');
echo "Résultat:\n$fixOutput\n\n";

// Vérification après correction
echo "Vérification après correction...\n";
$verifyCmd = 'docker compose exec php bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'"';
echo "Commande: $verifyCmd\n";
$verifyOutput = shell_exec($verifyCmd . ' 2>&1');
echo "Résultat:\n$verifyOutput\n\n";

echo "=== FIN DE LA CORRECTION ===\n";
