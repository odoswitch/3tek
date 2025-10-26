# CONFIGURATION SMTP POUR CPANEL

## 📋 CONFIGURATION MAILER_DSN POUR PRODUCTION

### **Variables d'environnement à configurer sur cPanel :**

```bash
# Configuration SMTP pour cPanel
MAILER_DSN=smtp://username:password@smtp.votre-hebergeur.com:587

# Exemple avec des identifiants réels :
MAILER_DSN=smtp://contact@3tek-europe.com:VotreMotDePasse@smtp.cpanel.net:587
```

### **Configuration alternative avec authentification :**

```bash
# Avec authentification SSL/TLS
MAILER_DSN=smtps://username:password@smtp.votre-hebergeur.com:465

# Exemple :
MAILER_DSN=smtps://contact@3tek-europe.com:VotreMotDePasse@smtp.cpanel.net:465
```

### **Configuration dans le fichier .env.local (pour cPanel) :**

```bash
# .env.local (à créer sur cPanel)
MAILER_DSN=smtp://contact@3tek-europe.com:VotreMotDePasse@smtp.cpanel.net:587
MAILER_FROM=contact@3tek-europe.com
MAILER_FROM_NAME="3Tek-Europe"
```

---

## 🔧 CORRECTION TEMPORAIRE POUR DOCKER LOCAL

Pour que la validation de commande fonctionne en local, nous devons corriger le `MAILER_DSN` pour pointer vers le bon conteneur :

```yaml
# Dans compose.yaml
MAILER_DSN=smtp://3tek-mailer-1:1025
```

---

## 📊 TEST DE CONNEXION SMTP

### **Script de test pour vérifier la connexion SMTP :**

```php
<?php
// test-smtp.php
require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$dsn = $_ENV['MAILER_DSN'] ?? 'smtp://localhost:1025';

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('test@3tek-europe.com')
        ->to('test@example.com')
        ->subject('Test SMTP')
        ->text('Test de connexion SMTP');
    
    $mailer->send($email);
    echo "✅ Connexion SMTP réussie !\n";
} catch (Exception $e) {
    echo "❌ Erreur SMTP : " . $e->getMessage() . "\n";
}
```

---

## 🎯 RECOMMANDATIONS POUR CPANEL

### **1. Configuration recommandée :**
- **Port :** 587 (STARTTLS) ou 465 (SSL)
- **Authentification :** Requise
- **Sécurité :** TLS/SSL recommandé

### **2. Variables d'environnement cPanel :**
```bash
MAILER_DSN=smtp://votre-email@votre-domaine.com:motdepasse@smtp.votre-hebergeur.com:587
MAILER_FROM=votre-email@votre-domaine.com
MAILER_FROM_NAME="3Tek-Europe"
```

### **3. Test en production :**
- Créer un script de test SMTP
- Vérifier les logs d'erreur
- Tester l'envoi d'emails

---

## 🚀 DÉPLOIEMENT CPANEL

### **Étapes pour cPanel :**

1. **Configurer les variables d'environnement :**
   ```bash
   MAILER_DSN=smtp://contact@3tek-europe.com:MotDePasse@smtp.cpanel.net:587
   ```

2. **Créer le fichier .env.local :**
   ```bash
   MAILER_DSN=smtp://contact@3tek-europe.com:MotDePasse@smtp.cpanel.net:587
   MAILER_FROM=contact@3tek-europe.com
   MAILER_FROM_NAME="3Tek-Europe"
   ```

3. **Tester la connexion SMTP :**
   ```bash
   php bin/console debug:config framework mailer
   ```

4. **Vérifier les logs :**
   ```bash
   tail -f var/log/prod.log
   ```

---

## ✅ CONCLUSION

- **Développement local :** Utilise le service mailer Docker (`3tek-mailer-1:1025`)
- **Production cPanel :** Utilise les vrais identifiants SMTP de votre hébergeur
- **Configuration :** Variables d'environnement dans `.env.local`
- **Test :** Script de test SMTP disponible

**Le système fonctionnera parfaitement sur cPanel avec vos vrais identifiants SMTP !**

