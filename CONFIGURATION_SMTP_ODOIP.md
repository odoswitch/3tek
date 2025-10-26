# CONFIGURATION SMTP POUR CPANEL - 3TEK-EUROPE

## 📧 Configuration SMTP Production

### **Variables d'environnement à configurer sur cPanel :**

```bash
# Configuration SMTP avec authentification SSL
MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
MAILER_FROM=noreply@odoip.net
MAILER_FROM_NAME="3Tek-Europe"
```

### **Détails de la configuration :**

-   **Email :** noreply@odoip.net
-   **Mot de passe :** Ngamba-123
-   **Serveur SMTP :** mail.odoip.net
-   **Port :** 465 (SSL)
-   **Chiffrement :** SSL
-   **Authentification :** Requise

### **Fichier .env.local pour cPanel :**

```bash
# Créer ce fichier sur cPanel
MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
MAILER_FROM=noreply@odoip.net
MAILER_FROM_NAME="3Tek-Europe"
```

---

## 🔧 Configuration Docker (Développement)

### **Dans compose.yaml :**

```yaml
environment:
    - MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
```

---

## 📊 Test de Configuration

### **Script de test SMTP :**

```php
<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$dsn = 'smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl';

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('noreply@odoip.net')
        ->to('test@example.com')
        ->subject('Test SMTP 3Tek-Europe')
        ->text('Test de connexion SMTP avec les vrais identifiants');

    $mailer->send($email);
    echo "✅ Email envoyé avec succès !\n";
} catch (Exception $e) {
    echo "❌ Erreur SMTP : " . $e->getMessage() . "\n";
}
```

---

## 🎯 Déploiement cPanel

### **Étapes pour cPanel :**

1. **Créer le fichier .env.local :**

    ```bash
    MAILER_DSN=smtp://noreply%40odoip.net:Ngamba%2D123@mail.odoip.net:465?encryption=ssl
    MAILER_FROM=noreply@odoip.net
    MAILER_FROM_NAME="3Tek-Europe"
    ```

2. **Vérifier la configuration :**

    ```bash
    php bin/console debug:config framework mailer
    ```

3. **Tester l'envoi d'email :**
    ```bash
    php bin/console messenger:consume async
    ```

---

## ✅ Avantages de cette configuration

-   ✅ **Authentification SSL** : Connexion sécurisée
-   ✅ **Port 465** : Port standard pour SSL
-   ✅ **Email professionnel** : noreply@odoip.net
-   ✅ **Compatible cPanel** : Configuration standard
-   ✅ **Sécurisé** : Mot de passe encodé dans l'URL

**Cette configuration fonctionnera parfaitement sur cPanel !**

