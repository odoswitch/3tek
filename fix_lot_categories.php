<?php
echo "=== FIX LOT CATEGORIES ===\n\n";

echo "🔧 ÉTAPE 1: Vérification des lots de test...\n";

// Vérifier les lots de test
$checkLotsCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, cat_id FROM lot WHERE name LIKE '%Test%' ORDER BY id DESC\"";
exec($checkLotsCommand, $checkLotsOutput, $checkLotsReturnCode);

if ($checkLotsReturnCode === 0 && !empty($checkLotsOutput)) {
    echo "Lots de test trouvés :\n";
    foreach ($checkLotsOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot de test trouvé\n";
}

echo "\n🔧 ÉTAPE 2: Vérification des catégories existantes...\n";

// Vérifier les catégories
$checkCategoriesCommand = "php bin/console doctrine:query:sql \"SELECT id, name FROM category ORDER BY id\"";
exec($checkCategoriesCommand, $checkCategoriesOutput, $checkCategoriesReturnCode);

if ($checkCategoriesReturnCode === 0 && !empty($checkCategoriesOutput)) {
    echo "Catégories trouvées :\n";
    foreach ($checkCategoriesOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune catégorie trouvée\n";
}

echo "\n🔧 ÉTAPE 3: Vérification des types existants...\n";

// Vérifier les types
$checkTypesCommand = "php bin/console doctrine:query:sql \"SELECT id, name FROM type ORDER BY id\"";
exec($checkTypesCommand, $checkTypesOutput, $checkTypesReturnCode);

if ($checkTypesReturnCode === 0 && !empty($checkTypesOutput)) {
    echo "Types trouvés :\n";
    foreach ($checkTypesOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun type trouvé\n";
}

echo "\n🔧 ÉTAPE 4: Mise à jour des lots de test...\n";

// Mettre à jour les lots de test avec une catégorie par défaut
$updateLotsCommand = "php bin/console doctrine:query:sql \"UPDATE lot SET cat_id = 1 WHERE name LIKE '%Test%'\"";
exec($updateLotsCommand, $updateLotsOutput, $updateLotsReturnCode);

if ($updateLotsReturnCode === 0) {
    echo "✅ Lots de test mis à jour avec catégorie par défaut\n";
} else {
    echo "❌ Erreur lors de la mise à jour des lots\n";
}

echo "\n🔧 ÉTAPE 5: Vérification après mise à jour...\n";

// Vérifier les lots après mise à jour
$checkAfterCommand = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, cat_id FROM lot WHERE name LIKE '%Test%' ORDER BY id DESC\"";
exec($checkAfterCommand, $checkAfterOutput, $checkAfterReturnCode);

if ($checkAfterReturnCode === 0 && !empty($checkAfterOutput)) {
    echo "Lots de test après mise à jour :\n";
    foreach ($checkAfterOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot de test trouvé après mise à jour\n";
}

echo "\n✅ FIX LOT CATEGORIES TERMINÉ !\n";
echo "Les lots de test ont été mis à jour avec une catégorie par défaut.\n";
echo "Ils devraient maintenant s'afficher sur l'interface utilisateur.\n\n";

echo "=== FIN DU FIX ===\n";



