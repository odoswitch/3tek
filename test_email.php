<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Récupérer la configuration
$dsn = $_ENV['MAILER_DSN'];
$from = $_ENV['MAILER_FROM'];

echo "Configuration SMTP:\n";
echo "DSN: " . $dsn . "\n";
echo "FROM: " . $from . "\n\n";

try {
    // Créer le transport
    $transport = Transport::fromDsn($dsn);
    
    // Créer le mailer
    $mailer = new Mailer($transport);
    
    // Créer l'email
    $email = (new Email())
        ->from($from)
        ->to('noreply@odoip.net')
        ->subject('Test Email 3TEK - ' . date('Y-m-d H:i:s'))
        ->text('Ceci est un email de test depuis l\'application 3TEK.')
        ->html('<p>Ceci est un <strong>email de test</strong> depuis l\'application 3TEK.</p>');
    
    echo "Envoi de l'email...\n";
    
    // Envoyer l'email
    $mailer->send($email);
    
    echo "✅ Email envoyé avec succès!\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR lors de l'envoi: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
