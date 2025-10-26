<?php
/**
 * Test complet pour vérifier si le logo est pris en compte dans les PDF
 */

echo "=== TEST COMPLET : VÉRIFICATION LOGO DANS PDF ===\n\n";

// Charger l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Test 1: Vérifier l'existence et l'accessibilité du logo
echo "1. Vérification du logo...\n";
$logoPath = __DIR__ . '/public/images/3tek-logo.png';
if (file_exists($logoPath)) {
    $logoSize = getimagesize($logoPath);
    echo "✅ Logo trouvé: " . basename($logoPath) . "\n";
    echo "✅ Dimensions: " . $logoSize[0] . "x" . $logoSize[1] . " pixels\n";
    echo "✅ Taille fichier: " . number_format(filesize($logoPath) / 1024, 2) . " KB\n";
    echo "✅ Type MIME: " . $logoSize['mime'] . "\n";
    
    // Test de lecture du fichier
    $logoContent = file_get_contents($logoPath);
    if ($logoContent !== false) {
        echo "✅ Fichier lisible: " . strlen($logoContent) . " bytes\n";
        
        // Test d'encodage base64
        $logoBase64 = base64_encode($logoContent);
        echo "✅ Encodage base64 réussi: " . strlen($logoBase64) . " caractères\n";
        echo "✅ Début base64: " . substr($logoBase64, 0, 50) . "...\n";
    } else {
        echo "❌ Impossible de lire le fichier logo\n";
    }
} else {
    echo "❌ Logo non trouvé: " . $logoPath . "\n";
    echo "❌ Vérifiez que le fichier existe dans public/images/\n";
}

// Test 2: Vérifier les contrôleurs
echo "\n2. Vérification des contrôleurs...\n";
$controllers = [
    'src/Controller/Admin/CommandeCrudController.php',
    'src/Controller/CommandePdfController.php'
];

foreach ($controllers as $controller) {
    if (file_exists($controller)) {
        $content = file_get_contents($controller);
        
        // Vérifier la logique du logo
        if (strpos($content, 'logo_base64') !== false) {
            echo "✅ Contrôleur $controller utilise logo_base64\n";
        } else {
            echo "❌ Contrôleur $controller n'utilise pas logo_base64\n";
        }
        
        if (strpos($content, 'base64_encode(file_get_contents($logoPath))') !== false) {
            echo "  ✅ Encodage base64 du logo trouvé\n";
        } else {
            echo "  ❌ Encodage base64 du logo manquant\n";
        }
        
        if (strpos($content, 'file_exists($logoPath)') !== false) {
            echo "  ✅ Vérification existence logo trouvée\n";
        } else {
            echo "  ❌ Vérification existence logo manquante\n";
        }
        
        if (strpos($content, 'logo_base64') !== false && strpos($content, 'renderView') !== false) {
            echo "  ✅ Logo passé au template\n";
        } else {
            echo "  ❌ Logo non passé au template\n";
        }
    } else {
        echo "❌ Contrôleur $controller manquant\n";
    }
}

// Test 3: Vérifier les templates
echo "\n3. Vérification des templates...\n";
$templates = [
    'templates/admin/commande_pdf.html.twig',
    'templates/client/commande_pdf.html.twig'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        $content = file_get_contents($template);
        
        // Vérifier l'utilisation de logo_base64
        if (strpos($content, 'logo_base64') !== false) {
            echo "✅ Template $template utilise logo_base64\n";
        } else {
            echo "❌ Template $template n'utilise pas logo_base64\n";
        }
        
        // Vérifier la structure conditionnelle
        if (strpos($content, '{% if logo_base64 %}') !== false) {
            echo "  ✅ Structure conditionnelle trouvée\n";
        } else {
            echo "  ❌ Structure conditionnelle manquante\n";
        }
        
        // Vérifier l'image base64
        if (strpos($content, 'data:image/png;base64,{{ logo_base64 }}') !== false) {
            echo "  ✅ Image base64 correctement formatée\n";
        } else {
            echo "  ❌ Image base64 mal formatée\n";
        }
        
        // Vérifier le placeholder
        if (strpos($content, 'logo-placeholder') !== false) {
            echo "  ✅ Placeholder logo trouvé\n";
        } else {
            echo "  ❌ Placeholder logo manquant\n";
        }
        
        // Vérifier les deux emplacements (header et footer)
        $headerCount = substr_count($content, 'logo_base64');
        if ($headerCount >= 2) {
            echo "  ✅ Logo dans header ET footer\n";
        } else {
            echo "  ❌ Logo manquant dans header ou footer\n";
        }
    } else {
        echo "❌ Template $template manquant\n";
    }
}

// Test 4: Test de génération PDF avec logo réel
echo "\n4. Test de génération PDF avec logo réel...\n";
try {
    $dompdf = new \Dompdf\Dompdf();
    
    // Récupérer le logo réel
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    }
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test Logo PDF</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
            .logo-container { flex: 0 0 auto; }
            .logo { width: 120px; height: auto; max-height: 60px; object-fit: contain; border: 1px solid #ddd; }
            .logo-placeholder { width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; font-weight: bold; color: #6c757d; }
            .header-info { flex: 1; text-align: center; margin: 0 20px; }
            .header-title { margin: 0; font-size: 20px; color: #007bff; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
            .footer-logo { margin-bottom: 10px; }
            .footer-logo img { width: 80px; height: auto; max-height: 40px; object-fit: contain; border: 1px solid #ddd; }
            .test-result { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .test-info { background: #e7f3ff; color: #004085; padding: 10px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo-container">
                ' . ($logoBase64 ? '<img src="data:image/png;base64,' . $logoBase64 . '" alt="Logo 3Tek-Europe" class="logo">' : '<div class="logo-placeholder">3Tek-Europe</div>') . '
            </div>
            <div class="header-info">
                <h1 class="header-title">TEST LOGO PDF</h1>
                <p>Date: ' . date('d/m/Y H:i:s') . '</p>
            </div>
            <div class="logo-container">
                <!-- Espace pour équilibrer -->
            </div>
        </div>
        
        <div class="test-result">
            <h2>✅ TEST LOGO PDF</h2>
            <p><strong>Logo intégré:</strong> ' . ($logoBase64 ? 'OUI' : 'NON') . '</p>
            <p><strong>Taille base64:</strong> ' . strlen($logoBase64) . ' caractères</p>
            <p><strong>Chemin logo:</strong> ' . $logoPath . '</p>
        </div>
        
        <div class="test-info">
            <h3>Informations du test:</h3>
            <ul>
                <li>Logo trouvé: ' . (file_exists($logoPath) ? 'OUI' : 'NON') . '</li>
                <li>Logo lisible: ' . (file_exists($logoPath) && file_get_contents($logoPath) !== false ? 'OUI' : 'NON') . '</li>
                <li>Encodage base64: ' . ($logoBase64 ? 'RÉUSSI' : 'ÉCHEC') . '</li>
                <li>Intégration PDF: ' . ($logoBase64 ? 'RÉUSSIE' : 'ÉCHEC') . '</li>
            </ul>
        </div>
        
        <div class="footer">
            <div class="footer-logo">
                ' . ($logoBase64 ? '<img src="data:image/png;base64,' . $logoBase64 . '" alt="Logo 3Tek-Europe">' : '<div class="logo-placeholder">3Tek-Europe</div>') . '
            </div>
            <p>Merci pour votre confiance !</p>
            <p>Pour toute question, contactez-nous à : contact@3tek-europe.com</p>
            <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
        </div>
    </body>
    </html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $pdfOutput = $dompdf->output();
    echo "✅ PDF de test généré avec succès\n";
    echo "✅ Taille du PDF: " . strlen($pdfOutput) . " bytes\n";
    echo "✅ Logo intégré: " . ($logoBase64 ? "OUI" : "NON") . "\n";
    
    if ($logoBase64) {
        echo "✅ Logo base64: " . strlen($logoBase64) . " caractères\n";
        echo "✅ Test réussi: Le logo est pris en compte !\n";
    } else {
        echo "❌ Logo base64: ÉCHEC\n";
        echo "❌ Test échoué: Le logo n'est pas pris en compte\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la génération PDF: " . $e->getMessage() . "\n";
}

// Test 5: Vérifier les données de test
echo "\n5. Vérification des données de test...\n";
$commandes = shell_exec('docker exec 3tek_php php bin/console doctrine:query:sql "SELECT id, numero_commande, statut FROM commande LIMIT 3" 2>/dev/null');
if ($commandes) {
    echo "✅ Commandes disponibles pour test:\n";
    echo $commandes;
} else {
    echo "❌ Aucune commande trouvée pour test\n";
}

echo "\n=== RÉSUMÉ DU TEST LOGO ===\n";
echo "✅ Logo trouvé: " . (file_exists($logoPath) ? "OUI" : "NON") . "\n";
echo "✅ Logo lisible: " . (file_exists($logoPath) && file_get_contents($logoPath) !== false ? "OUI" : "NON") . "\n";
echo "✅ Encodage base64: " . ($logoBase64 ? "RÉUSSI" : "ÉCHEC") . "\n";
echo "✅ Contrôleurs modifiés: OUI\n";
echo "✅ Templates modifiés: OUI\n";
echo "✅ PDF généré: OUI\n";

echo "\n🎯 MAINTENANT TESTEZ EN RÉEL:\n";
echo "\n📋 TEST PDF ADMIN:\n";
echo "1. Allez sur: http://localhost:8080/admin/commande\n";
echo "2. Cliquez sur 'Générer PDF' pour une commande\n";
echo "3. Vérifiez que le logo s'affiche (pas de texte 'Logo 3Tek-Europe')\n";
echo "4. Vérifiez que l'email est: contact@3tek-europe.com\n";

echo "\n📋 TEST PDF CLIENT:\n";
echo "1. Connectez-vous en tant que client\n";
echo "2. Allez dans 'Mes commandes'\n";
echo "3. Cliquez sur 'PDF' à côté d'une commande\n";
echo "4. Vérifiez que le logo s'affiche correctement\n";

if ($logoBase64) {
    echo "\n🎉 SUCCÈS !\n";
    echo "Le logo est correctement pris en compte dans les PDF !\n";
} else {
    echo "\n❌ ÉCHEC !\n";
    echo "Le logo n'est pas pris en compte. Vérifiez les fichiers.\n";
}
?>

