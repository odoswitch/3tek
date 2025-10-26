<?php
// Script pour corriger le prix directement
echo "=== CORRECTION DU PRIX DIRECTE ===\n\n";

// Utiliser la console Symfony directement
echo "Tentative de correction via la console Symfony...\n";

// Commande pour vérifier l'état
$checkCmd = 'bin/console doctrine:query:sql "SELECT id, nom, prix, quantite, statut FROM lot WHERE nom LIKE \'%David%\'"';
echo "Commande: $checkCmd\n";
$output = shell_exec($checkCmd . ' 2>&1');
echo "Résultat:\n$output\n\n";

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


