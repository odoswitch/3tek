<?php

echo "=== TEST CORRECTION NOTFOUNDHTTPEXCEPTION ===\n\n";

// Test de l'application en mode production
$url = 'http://localhost:8080';

echo "🌐 Test de l'application sur: $url\n";

// Test avec un ID de commande inexistant
$testUrl = $url . '/commande/999999';
echo "🔍 Test avec ID inexistant: $testUrl\n";

// Utilisation de curl pour tester
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: $error\n";
} else {
    echo "📊 Code HTTP: $httpCode\n";
    
    if ($httpCode === 404) {
        echo "✅ SUCCÈS: Erreur 404 gérée correctement (commande non trouvée)\n";
    } elseif ($httpCode === 500) {
        echo "❌ ÉCHEC: Erreur 500 (NotFoundHttpException non gérée)\n";
    } else {
        echo "⚠️ Code inattendu: $httpCode\n";
    }
}

echo "\n=== FIN DU TEST ===\n";

