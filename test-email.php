<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

// Configuration email depuis .env
$mailerDsn = 'smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl';
$fromEmail = 'noreply@3tek-europe.com';

try {
    // Créer le transport
    $transport = Transport::fromDsn($mailerDsn);
    $mailer = new Mailer($transport);

    // Email 1: info@odoip.fr
    $email1 = (new Email())
        ->from($fromEmail)
        ->to('info@odoip.fr')
        ->subject('Test Email 3tek - Application Fonctionnelle')
        ->html('
            <h2>🎉 Test Email depuis l\'Application 3tek</h2>
            <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Serveur:</strong> 45.11.51.2:8084</p>
            <p><strong>Statut:</strong> Application 3tek opérationnelle</p>
            <hr>
            <p>Ceci est un test d\'envoi d\'email depuis l\'application 3tek déployée sur Docker.</p>
            <p><strong>Accès:</strong> <a href="http://45.11.51.2:8084">http://45.11.51.2:8084</a></p>
            <p><strong>Admin:</strong> <a href="http://45.11.51.2:8084/admin">http://45.11.51.2:8084/admin</a></p>
        ');

    // Email 2: congocrei2000@gmail.com
    $email2 = (new Email())
        ->from($fromEmail)
        ->to('congocrei2000@gmail.com')
        ->subject('Test Email 3tek - Application Fonctionnelle')
        ->html('
            <h2>🎉 Test Email depuis l\'Application 3tek</h2>
            <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Serveur:</strong> 45.11.51.2:8084</p>
            <p><strong>Statut:</strong> Application 3tek opérationnelle</p>
            <hr>
            <p>Ceci est un test d\'envoi d\'email depuis l\'application 3tek déployée sur Docker.</p>
            <p><strong>Accès:</strong> <a href="http://45.11.51.2:8084">http://45.11.51.2:8084</a></p>
            <p><strong>Admin:</strong> <a href="http://45.11.51.2:8084/admin">http://45.11.51.2:8084/admin</a></p>
        ');

    // Envoyer les emails
    echo "Envoi de l'email vers info@odoip.fr...\n";
    $mailer->send($email1);
    echo "✅ Email envoyé vers info@odoip.fr\n\n";

    echo "Envoi de l'email vers congocrei2000@gmail.com...\n";
    $mailer->send($email2);
    echo "✅ Email envoyé vers congocrei2000@gmail.com\n\n";

    echo "🎉 Test d'envoi d'emails réussi !\n";
    echo "Vérifiez vos boîtes de réception.\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de l'envoi des emails:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    exit(1);
}
