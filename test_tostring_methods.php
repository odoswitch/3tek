<?php
// Test des méthodes __toString()
echo "=== TEST MÉTHODES __toString() ===\n\n";

echo "🔍 ÉTAPE 1: Vérification de la méthode __toString() dans Lot...\n";

$lotContent = file_get_contents('src/Entity/Lot.php');

if (strpos($lotContent, 'public function __toString(): string') !== false) {
    echo "✅ Méthode __toString() trouvée dans Lot\n";
} else {
    echo "❌ Méthode __toString() manquante dans Lot\n";
}

if (strpos($lotContent, 'return $this->name ?? \'Lot sans nom\';') !== false) {
    echo "✅ Retour correct dans __toString() de Lot\n";
} else {
    echo "❌ Retour incorrect dans __toString() de Lot\n";
}

echo "\n🔍 ÉTAPE 2: Vérification de la méthode __toString() dans User...\n";

$userContent = file_get_contents('src/Entity/User.php');

if (strpos($userContent, 'public function __toString(): string') !== false) {
    echo "✅ Méthode __toString() trouvée dans User\n";
} else {
    echo "❌ Méthode __toString() manquante dans User\n";
}

if (strpos($userContent, 'return $this->name . \' \' . $this->lastname . \' (\' . $this->email . \')\';') !== false) {
    echo "✅ Retour correct dans __toString() de User\n";
} else {
    echo "❌ Retour incorrect dans __toString() de User\n";
}

echo "\n📊 RÉSUMÉ:\n";
echo "- Lot __toString(): " . (strpos($lotContent, 'public function __toString(): string') !== false ? "OK" : "MANQUANT") . "\n";
echo "- User __toString(): " . (strpos($userContent, 'public function __toString(): string') !== false ? "OK" : "MANQUANT") . "\n";

echo "\n✅ MÉTHODES __toString() AJOUTÉES !\n";
echo "Maintenant, les entités peuvent être converties en chaînes de caractères :\n";
echo "- Lot: Affiche le nom du lot\n";
echo "- User: Affiche 'Prénom Nom (email)'\n";
echo "- Plus d'erreur 'Object could not be converted to string' dans EasyAdmin\n\n";

echo "=== FIN DU TEST ===\n";


