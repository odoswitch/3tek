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
    
    // Adresses de test
    $testEmails = [
        'toufic.khreish@gmail.com',
        'congocrei2000@gmail.com',
        'toufic.khreish@3tek-europe.com'
    ];
    
    echo "Envoi vers : " . implode(', ', $testEmails) . "\n\n";
    
    foreach ($testEmails as $recipient) {
        echo "Envoi vers $recipient...\n";
        
        // Créer l'email avec améliorations
        $email = (new Email())
            ->from($from)
            ->to($recipient)
            ->replyTo($from)
            ->subject('Test Email 3TEK - ' . date('Y-m-d H:i:s'))
            ->text('Ceci est un email de test depuis l\'application 3TEK.' . "\n\nEnvoyé à : " . $recipient)
            ->html('<p>Ceci est un <strong>email de test</strong> depuis l\'application 3TEK.</p><p>Envoyé à : ' . $recipient . '</p>');
        
        // Ajouter des en-têtes pour améliorer la délivrabilité
        $headers = $email->getHeaders();
        $headers->addTextHeader('X-Mailer', '3Tek-Europe Notification System');
        $headers->addTextHeader('X-Priority', '3');
        $headers->addTextHeader('Importance', 'Normal');
        
        try {
            // Envoyer l'email
            $mailer->send($email);
            echo "  ✅ Envoyé avec succès à $recipient\n";
        } catch (\Exception $e) {
            echo "  ❌ ERREUR pour $recipient: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERREUR GLOBALE: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
