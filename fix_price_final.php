<?php
// Script final pour corriger le prix
echo "=== CORRECTION DU PRIX FINALE ===\n\n";

// Utiliser la console Symfony directement
echo "Correction du prix du lot David...\n";

// Commande pour corriger le prix
$fixCmd = 'bin/console doctrine:query:sql "UPDATE lot SET prix = 100.00 WHERE nom LIKE \'%David%\'"';
echo "Commande: $fixCmd\n";
$fixOutput = shell_exec($fixCmd . ' 2>&1');
echo "Résultat:\n$fixOutput\n\n";

// Vérification après correction
$verifyCmd = 'bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'"';
echo "Commande: $verifyCmd\n";
$verifyOutput = shell_exec($verifyCmd . ' 2>&1');
echo "Résultat:\n$verifyOutput\n\n";

echo "=== FIN DE LA CORRECTION ===\n";



