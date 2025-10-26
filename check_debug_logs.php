<?php
echo "=== VÉRIFICATION DES LOGS DE DÉBOGAGE ===\n\n";

echo "🔍 ÉTAPE 1: Vérification des logs de suppression...\n";

if (file_exists('var/log/dev.log')) {
    $logContent = file_get_contents('var/log/dev.log');
    $lines = explode("\n", $logContent);

    // Chercher les logs de suppression
    $deleteLogs = [];
    $libererLogs = [];

    foreach ($lines as $line) {
        if (strpos($line, 'DEBUG DELETE:') !== false) {
            $deleteLogs[] = $line;
        }
        if (strpos($line, 'DEBUG LIBERER:') !== false) {
            $libererLogs[] = $line;
        }
    }

    echo "Logs de suppression trouvés: " . count($deleteLogs) . "\n";
    foreach ($deleteLogs as $log) {
        echo "- " . $log . "\n";
    }

    echo "\nLogs de libération trouvés: " . count($libererLogs) . "\n";
    foreach ($libererLogs as $log) {
        echo "- " . $log . "\n";
    }

    if (count($deleteLogs) == 0) {
        echo "⚠️ Aucun log de suppression trouvé - la méthode deleteEntity n'est peut-être pas appelée\n";
    }

    if (count($libererLogs) == 0) {
        echo "⚠️ Aucun log de libération trouvé - la méthode libererLot n'est peut-être pas appelée\n";
    }
} else {
    echo "❌ Fichier de log non trouvé\n";
}

echo "\n🔍 ÉTAPE 2: Vérification du statut des lots...\n";

$command = "php bin/console doctrine:query:sql \"SELECT id, name, statut, quantite, reserve_par_id FROM lot ORDER BY id DESC LIMIT 3\"";
exec($command, $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "Statut des lots :\n";
    foreach ($output as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucun lot trouvé ou erreur de requête\n";
}

echo "\n🔍 ÉTAPE 3: Vérification des files d'attente...\n";

$fileCommand = "php bin/console doctrine:query:sql \"SELECT id, lot_id, user_id, position, statut FROM file_attente ORDER BY created_at DESC LIMIT 3\"";
exec($fileCommand, $fileOutput, $fileReturnCode);

if ($fileReturnCode === 0 && !empty($fileOutput)) {
    echo "Files d'attente :\n";
    foreach ($fileOutput as $line) {
        if (strpos($line, '|') !== false) {
            echo "- " . $line . "\n";
        }
    }
} else {
    echo "Aucune file d'attente trouvée ou erreur de requête\n";
}

echo "\n📊 RÉSUMÉ:\n";
echo "- Logs de suppression: " . (count($deleteLogs) > 0 ? "✅ PRÉSENTS" : "❌ ABSENTS") . "\n";
echo "- Logs de libération: " . (count($libererLogs) > 0 ? "✅ PRÉSENTS" : "❌ ABSENTS") . "\n";

if (count($deleteLogs) == 0) {
    echo "\n⚠️ PROBLÈME DÉTECTÉ !\n";
    echo "La méthode deleteEntity n'est pas appelée par EasyAdmin.\n";
    echo "Il faut vérifier la configuration EasyAdmin.\n";
} else {
    echo "\n✅ LOGS PRÉSENTS !\n";
    echo "La logique de suppression fonctionne.\n";
}

echo "\n=== FIN DE LA VÉRIFICATION ===\n";
