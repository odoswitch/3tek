#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

echo "=== TEST VALIDATION COMMANDE ===\n\n";

// Configuration de la base de données
$databaseUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:ngamba123@3tek-database-1:3306/db_3tek?serverVersion=8.0&charset=utf8mb4';

echo "🔍 Test de connexion à la base de données...\n";
try {
    $pdo = new PDO($databaseUrl);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à la base de données réussie\n";
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🔍 Test de connexion SMTP...\n";
try {
    $dsn = $_ENV['MAILER_DSN'] ?? 'smtp://3tek-mailer-1:1025';
    echo "📧 MAILER_DSN : $dsn\n";
    
    $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
    echo "✅ Transport SMTP créé avec succès\n";
    
    // Test d'envoi d'email
    $mailer = new \Symfony\Component\Mailer\Mailer($transport);
    $email = (new \Symfony\Component\Mime\Email())
        ->from('test@3tek-europe.com')
        ->to('test@example.com')
        ->subject('Test SMTP')
        ->text('Test de connexion SMTP');
    
    $mailer->send($email);
    echo "✅ Email de test envoyé avec succès\n";
    
} catch (Exception $e) {
    echo "❌ Erreur SMTP : " . $e->getMessage() . "\n";
}

echo "\n🔍 Test de création d'une commande...\n";
try {
    // Initialiser le kernel Symfony
    $kernel = new App\Kernel('prod', false);
    $kernel->boot();
    $container = $kernel->getContainer();
    
    $entityManager = $container->get('doctrine.orm.entity_manager');
    $userRepo = $entityManager->getRepository(\App\Entity\User::class);
    $lotRepo = $entityManager->getRepository(\App\Entity\Lot::class);
    
    $user = $userRepo->findOneBy([]);
    $lot = $lotRepo->findOneBy([]);
    
    if ($user && $lot) {
        echo "✅ Utilisateur et lot trouvés pour le test\n";
        echo "   - Utilisateur : " . $user->getEmail() . "\n";
        echo "   - Lot : " . $lot->getName() . "\n";
        
        // Créer une commande de test
        $commande = new \App\Entity\Commande();
        $commande->setUser($user);
        $commande->setLot($lot);
        $commande->setQuantite(1);
        $commande->setPrixUnitaire($lot->getPrix());
        $commande->setPrixTotal($lot->getPrix());
        $commande->setStatut('en_attente');
        $commande->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($commande);
        $entityManager->flush();
        
        echo "✅ Commande créée avec succès (ID: " . $commande->getId() . ")\n";
        
        // Nettoyer
        $entityManager->remove($commande);
        $entityManager->flush();
        echo "✅ Commande de test supprimée\n";
        
    } else {
        echo "❌ Pas d'utilisateur ou de lot trouvé pour le test\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test de création : " . $e->getMessage() . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";

