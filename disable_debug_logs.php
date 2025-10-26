<?php
// Script pour désactiver les logs de débogage en production
echo "=== DÉSACTIVATION DES LOGS DE DÉBOGAGE ===\n\n";

echo "⚠️ ATTENTION: Les logs de débogage sont actuellement activés dans:\n";
echo "1. PanierController::valider()\n";
echo "2. CommandeController::create()\n\n";

echo "📋 POUR DÉSACTIVER LES LOGS EN PRODUCTION:\n";
echo "1. Commenter ou supprimer les lignes error_log()\n";
echo "2. Ou utiliser une variable d'environnement\n";
echo "3. Ou utiliser le système de logs de Symfony\n\n";

echo "🔧 SOLUTION RECOMMANDÉE:\n";
echo "Remplacer les error_log() par:\n";
echo "if (\$_ENV['APP_ENV'] === 'dev') {\n";
echo "    error_log('DEBUG: ...');\n";
echo "}\n\n";

echo "✅ AVANTAGES:\n";
echo "- Logs uniquement en développement\n";
echo "- Performance optimisée en production\n";
echo "- Sécurité améliorée\n\n";

echo "=== FIN DU SCRIPT ===\n";


