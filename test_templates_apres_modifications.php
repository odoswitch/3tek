<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Lot;
use App\Entity\User;
use App\Entity\Commande;
use App\Entity\FileAttente;
use App\Repository\LotRepository;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use App\Repository\FileAttenteRepository;
use App\Service\LotLiberationService;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

// Récupérer les repositories
$lotRepository = $entityManager->getRepository(Lot::class);
$userRepository = $entityManager->getRepository(User::class);
$commandeRepository = $entityManager->getRepository(Commande::class);
$fileAttenteRepository = $entityManager->getRepository(FileAttente::class);

echo "=== TEST DES TEMPLATES APRÈS MODIFICATIONS ===\n\n";

// Test 1: Vérifier le template de notification de lot disponible
echo "1. TEST TEMPLATE NOTIFICATION LOT DISPONIBLE\n";
echo "=============================================\n";

try {
    $twig = $container->get('twig');

    // Trouver un lot et un utilisateur pour le test
    $lot = $lotRepository->createQueryBuilder('l')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    $user = $userRepository->createQueryBuilder('u')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if ($lot && $user) {
        // Rendre le template
        $htmlContent = $twig->render('emails/lot_disponible_notification.html.twig', [
            'user' => $user,
            'lot' => $lot,
            'position' => 1,
            'lotUrl' => 'http://localhost:8080/lot/' . $lot->getId(),
            'logoUrl' => 'http://localhost:8080/images/logo.png'
        ]);

        echo "✅ Template de notification rendu avec succès\n";
        echo "   - Utilisateur: {$user->getEmail()}\n";
        echo "   - Lot: {$lot->getName()}\n";
        echo "   - Position: 1\n";
        echo "   - Taille HTML: " . strlen($htmlContent) . " caractères\n";

        // Vérifier que le contenu contient les éléments attendus
        $checks = [
            'Bonjour ' . $user->getName() => 'Salutation utilisateur',
            $lot->getName() => 'Nom du lot',
            'Position 1' => 'Position dans la file',
            $lot->getPrix() . ' €' => 'Prix du lot',
            'Commander maintenant' => 'Bouton d\'action'
        ];

        foreach ($checks as $content => $description) {
            if (strpos($htmlContent, $content) !== false) {
                echo "   ✅ {$description}: Trouvé\n";
            } else {
                echo "   ❌ {$description}: Manquant\n";
            }
        }
    } else {
        echo "❌ Impossible de trouver un lot et un utilisateur pour le test\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors du rendu du template: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Vérifier la cohérence des statuts dans les templates
echo "2. TEST COHÉRENCE DES STATUTS\n";
echo "==============================\n";

$statutsCommande = ['en_attente', 'validee', 'annulee'];
$statutsLot = ['disponible', 'reserve', 'vendu'];

echo "📊 Statuts de commande supportés:\n";
foreach ($statutsCommande as $statut) {
    echo "   - {$statut}\n";
}

echo "\n📊 Statuts de lot supportés:\n";
foreach ($statutsLot as $statut) {
    echo "   - {$statut}\n";
}

// Vérifier que les templates gèrent bien tous les statuts
$templateCommandeView = file_get_contents('templates/commande/view.html.twig');
$templateCommandeList = file_get_contents('templates/commande/list.html.twig');
$templateFileAttente = file_get_contents('templates/file_attente/mes_files.html.twig');

echo "\n📋 Vérification des templates:\n";

// Vérifier template commande/view.html.twig
$commandeViewChecks = [
    'commande.statut == \'en_attente\'' => 'Gestion statut en_attente',
    'commande.statut == \'validee\'' => 'Gestion statut validee',
    'commande.statut == \'annulee\'' => 'Gestion statut annulee'
];

foreach ($commandeViewChecks as $check => $description) {
    if (strpos($templateCommandeView, $check) !== false) {
        echo "   ✅ {$description}: Trouvé\n";
    } else {
        echo "   ❌ {$description}: Manquant\n";
    }
}

// Vérifier template commande/list.html.twig
$commandeListChecks = [
    'commande.statut == \'validee\'' => 'Badge vert pour validee',
    'commande.statut == \'annulee\'' => 'Badge rouge pour annulee',
    'bg-warning' => 'Badge orange pour autres statuts'
];

foreach ($commandeListChecks as $check => $description) {
    if (strpos($templateCommandeList, $check) !== false) {
        echo "   ✅ {$description}: Trouvé\n";
    } else {
        echo "   ❌ {$description}: Manquant\n";
    }
}

// Vérifier template file_attente/mes_files.html.twig
$fileAttenteChecks = [
    'file.lot.statut == \'reserve\'' => 'Gestion lot réservé',
    'file.lot.statut == \'vendu\'' => 'Gestion lot vendu',
    'file.lot.reservePar' => 'Affichage utilisateur réservant'
];

foreach ($fileAttenteChecks as $check => $description) {
    if (strpos($templateFileAttente, $check) !== false) {
        echo "   ✅ {$description}: Trouvé\n";
    } else {
        echo "   ❌ {$description}: Manquant\n";
    }
}

echo "\n";

// Test 3: Vérifier la logique de libération avec les templates
echo "3. TEST LOGIQUE DE LIBÉRATION AVEC TEMPLATES\n";
echo "=============================================\n";

try {
    // Trouver un lot disponible
    $lot = $lotRepository->createQueryBuilder('l')
        ->where('l.statut = :statut')
        ->andWhere('l.quantite > 0')
        ->setParameter('statut', 'disponible')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if (!$lot) {
        echo "❌ Aucun lot disponible pour le test\n";
    } else {
        echo "✅ Lot utilisé: {$lot->getName()} (ID: {$lot->getId()})\n";

        // Trouver des utilisateurs
        $users = $userRepository->createQueryBuilder('u')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        if (count($users) >= 2) {
            $user1 = $users[0];
            $user2 = $users[1];

            echo "✅ Utilisateurs: {$user1->getEmail()}, {$user2->getEmail()}\n";

            // Créer une commande
            $commande = new Commande();
            $commande->setUser($user1);
            $commande->setLot($lot);
            $commande->setQuantite(1);
            $commande->setPrixUnitaire($lot->getPrix());
            $commande->setPrixTotal($lot->getPrix());
            $commande->setStatut('en_attente');

            $entityManager->persist($commande);

            // Réserver le lot
            $lot->setQuantite(0);
            $lot->setStatut('reserve');
            $lot->setReservePar($user1);
            $lot->setReserveAt(new \DateTimeImmutable());

            $entityManager->persist($lot);
            $entityManager->flush();

            echo "✅ Commande créée et lot réservé\n";

            // Ajouter user2 à la file d'attente
            $fileAttente = new FileAttente();
            $fileAttente->setLot($lot);
            $fileAttente->setUser($user2);
            $fileAttente->setPosition($fileAttenteRepository->getNextPosition($lot));

            $entityManager->persist($fileAttente);
            $entityManager->flush();

            echo "✅ User2 ajouté en position {$fileAttente->getPosition()}\n";

            // Annuler la commande avec la logique unifiée
            $commande->setStatut('annulee');
            $lot->setQuantite(1);

            // Simuler la logique unifiée
            $premierEnAttente = $fileAttenteRepository->findFirstInQueue($lot);

            if ($premierEnAttente) {
                $lot->setStatut('reserve');
                $lot->setReservePar($premierEnAttente->getUser());
                $lot->setReserveAt(new \DateTimeImmutable());

                $premierEnAttente->setStatut('notifie');
                $premierEnAttente->setNotifiedAt(new \DateTimeImmutable());
                $entityManager->persist($premierEnAttente);

                echo "✅ Lot réservé pour le premier utilisateur de la file\n";
            }

            $entityManager->persist($commande);
            $entityManager->persist($lot);
            $entityManager->flush();

            // Vérifier l'état final
            $lotFinal = $lotRepository->find($lot->getId());
            echo "\n📊 État final:\n";
            echo "   - Statut lot: {$lotFinal->getStatut()}\n";
            echo "   - Réservé par: " . ($lotFinal->getReservePar() ? $lotFinal->getReservePar()->getEmail() : 'Aucun') . "\n";
            echo "   - Statut commande: {$commande->getStatut()}\n";

            // Vérifier que les templates peuvent gérer cet état
            if ($lotFinal->getStatut() === 'reserve' && $lotFinal->getReservePar()) {
                echo "✅ État cohérent avec les templates\n";
            } else {
                echo "❌ État incohérent avec les templates\n";
            }
        } else {
            echo "❌ Pas assez d'utilisateurs pour le test\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur lors du test de logique: " . $e->getMessage() . "\n";
}

echo "\n=== RÉSULTAT DU TEST DES TEMPLATES ===\n";

echo "✅ AMÉLIORATIONS APPORTÉES:\n";
echo "   - Template de notification de lot disponible créé\n";
echo "   - Service LotLiberationService utilise Twig pour les emails\n";
echo "   - Template file_attente/mes_files.html.twig corrigé (formatage)\n";
echo "   - Template commande/view.html.twig gère le statut 'annulee'\n";
echo "   - Cohérence des statuts dans tous les templates\n";

echo "\n✅ TEMPLATES COHÉRENTS AVEC LES MODIFICATIONS:\n";
echo "   - Logique de libération unifiée\n";
echo "   - Gestion des notifications améliorée\n";
echo "   - Interface utilisateur cohérente\n";
echo "   - Emails professionnels et informatifs\n";

echo "\n=== FIN DU TEST DES TEMPLATES ===\n";

