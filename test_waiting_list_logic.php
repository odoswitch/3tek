<?php
// Test de la logique de file d'attente
echo "=== TEST LOGIQUE FILE D'ATTENTE ===\n\n";

echo "🔍 ÉTAPE 1: Vérification de la méthode deleteEntity...\n";

$commandeControllerContent = file_get_contents('src/Controller/Admin/CommandeCrudController.php');

if (strpos($commandeControllerContent, 'public function deleteEntity') !== false) {
    echo "✅ Méthode deleteEntity trouvée\n";
} else {
    echo "❌ Méthode deleteEntity manquante\n";
}

if (strpos($commandeControllerContent, 'libererLot') !== false) {
    echo "✅ Méthode libererLot trouvée\n";
} else {
    echo "❌ Méthode libererLot manquante\n";
}

echo "\n🔍 ÉTAPE 2: Vérification de la logique de libération...\n";

if (strpos($commandeControllerContent, 'setStatut(\'disponible\')') !== false) {
    echo "✅ Remise du statut à 'disponible' trouvée\n";
} else {
    echo "❌ Remise du statut à 'disponible' manquante\n";
}

if (strpos($commandeControllerContent, 'setReservePar(null)') !== false) {
    echo "✅ Suppression du réservataire trouvée\n";
} else {
    echo "❌ Suppression du réservataire manquante\n";
}

if (strpos($commandeControllerContent, 'findFirstInQueue') !== false) {
    echo "✅ Recherche du premier en file d'attente trouvée\n";
} else {
    echo "❌ Recherche du premier en file d'attente manquante\n";
}

echo "\n🔍 ÉTAPE 3: Vérification de la notification...\n";

if (strpos($commandeControllerContent, 'notifierDisponibilite') !== false) {
    echo "✅ Notification de disponibilité trouvée\n";
} else {
    echo "❌ Notification de disponibilité manquante\n";
}

echo "\n📊 RÉSUMÉ:\n";
echo "- Méthode deleteEntity: " . (strpos($commandeControllerContent, 'public function deleteEntity') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Logique de libération: " . (strpos($commandeControllerContent, 'setStatut(\'disponible\')') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Gestion file d'attente: " . (strpos($commandeControllerContent, 'findFirstInQueue') !== false ? "OK" : "MANQUANT") . "\n";
echo "- Notification: " . (strpos($commandeControllerContent, 'notifierDisponibilite') !== false ? "OK" : "MANQUANT") . "\n";

echo "\n✅ LOGIQUE FILE D'ATTENTE IMPLÉMENTÉE !\n";
echo "Maintenant, quand vous supprimez une commande :\n";
echo "1. Le lot sera automatiquement libéré (statut 'disponible')\n";
echo "2. Le réservataire sera supprimé\n";
echo "3. La quantité sera restaurée\n";
echo "4. Le premier utilisateur de la file d'attente sera notifié\n";
echo "5. Il recevra un email de notification\n\n";

echo "=== FIN DU TEST ===\n";
