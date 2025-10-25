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

echo "=== TEST EMAIL SPECIFIQUE POUR CONGOCREI2000@GMAIL.COM ===\n\n";
echo "Configuration SMTP:\n";
echo "DSN: " . $dsn . "\n";
echo "FROM: " . $from . "\n\n";

try {
    // Créer le transport
    $transport = Transport::fromDsn($dsn);
    
    // Créer le mailer
    $mailer = new Mailer($transport);
    
    $recipient = 'congocrei2000@gmail.com';
    
    echo "Envoi d'un email de test à $recipient...\n\n";
    
    // Créer l'email avec un contenu plus riche
    $email = (new Email())
        ->from($from)
        ->to($recipient)
        ->replyTo($from)
        ->subject('Notification 3Tek-Europe - Test ' . date('H:i:s'))
        ->text("Bonjour,\n\nCeci est un email de test depuis la plateforme 3Tek-Europe.\n\nSi vous recevez cet email, cela signifie que notre système de notification fonctionne correctement.\n\nCordialement,\nL'équipe 3Tek-Europe\n\n---\nDate: " . date('Y-m-d H:i:s'))
        ->html('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #0066cc, #0052a3); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    .button { display: inline-block; padding: 12px 30px; background: #0066cc; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>3Tek-Europe</h1>
                        <p>Plateforme de gestion</p>
                    </div>
                    <div class="content">
                        <h2>Email de test</h2>
                        <p>Bonjour,</p>
                        <p>Ceci est un <strong>email de test</strong> depuis la plateforme 3Tek-Europe.</p>
                        <p>Si vous recevez cet email, cela signifie que notre système de notification fonctionne correctement.</p>
                        <p>Date et heure d\'envoi : <strong>' . date('Y-m-d H:i:s') . '</strong></p>
                        <p>Destinataire : <strong>congocrei2000@gmail.com</strong></p>
                    </div>
                    <div class="footer">
                        <p>© 2025 3Tek-Europe - Tous droits réservés</p>
                        <p>Cet email a été envoyé depuis noreply@3tek-europe.com</p>
                    </div>
                </div>
            </body>
            </html>
        ');
    
    // Ajouter des en-têtes pour améliorer la délivrabilité
    $headers = $email->getHeaders();
    $headers->addTextHeader('X-Mailer', '3Tek-Europe Notification System');
    $headers->addTextHeader('X-Priority', '3');
    $headers->addTextHeader('Importance', 'Normal');
    $headers->addTextHeader('X-Application', '3Tek-Europe');
    
    // Envoyer l'email
    $mailer->send($email);
    
    echo "✅ Email envoyé avec succès à $recipient !\n\n";
    echo "Vérifiez maintenant :\n";
    echo "1. La boîte de réception\n";
    echo "2. Le dossier Spam\n";
    echo "3. Le dossier Promotions (si activé)\n";
    echo "4. Tous les messages\n\n";
    echo "Si l'email n'apparaît pas dans les 2-3 minutes, il se peut que Gmail l'ait bloqué.\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR lors de l'envoi: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
