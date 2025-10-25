# Dépannage SMTP - Congo Electronicenter

## Erreur actuelle

```
Failed to authenticate on SMTP server with username "info@congoelectronicenter.com" 
using the following authenticators: "LOGIN", "PLAIN". 
Authenticator "LOGIN" returned "Expected response code "235" but got code "535", 
with message "535 Incorrect authentication data"
```

## Configurations à tester

### ⚠️ ERREUR ACTUELLE
```
Authentication: unauthorized
Sender verify failed
Authenticator "LOGIN" returned "Expected response code "235" but got code "535"
```

**Cause probable** : Username ou mot de passe incorrect, ou format non accepté par le serveur.

---

### ✅ Option 1 : Username simple + Port 465 SSL (ACTUELLE)

```env
MAILER_DSN=smtp://info:Ngamba%2D123@mail.congoelectronicenter.com:465?encryption=ssl
```

### Option 2 : Username complet + Port 465 SSL

```env
MAILER_DSN=smtp://info%40congoelectronicenter.com:Ngamba%2D123@mail.congoelectronicenter.com:465?encryption=ssl
```

### Option 3 : Username simple + Port 587 TLS

```env
MAILER_DSN=smtp://info:Ngamba%2D123@mail.congoelectronicenter.com:587?encryption=tls
```

### Option 4 : Username complet + Port 587 TLS

```env
MAILER_DSN=smtp://info%40congoelectronicenter.com:Ngamba%2D123@mail.congoelectronicenter.com:587?encryption=tls
```

### Option 5 : Mot de passe non encodé

```env
# Si l'encodage du - pose problème
MAILER_DSN=smtp://info:Ngamba-123@mail.congoelectronicenter.com:465?encryption=ssl
```

### Option 6 : Sans encryption (TEST uniquement)

```env
MAILER_DSN=smtp://info:Ngamba-123@mail.congoelectronicenter.com:25
```

## Solution temporaire appliquée

Le code a été modifié pour que **la création de lots ne soit pas bloquée** si l'envoi d'email échoue.

**Fichier modifié** : `src/EventListener/LotCreatedListener.php`

```php
public function postPersist(Lot $lot, LifecycleEventArgs $event): void
{
    try {
        $this->notificationService->notifyUsersAboutNewLot($lot);
    } catch (\Exception $e) {
        error_log('Erreur email : ' . $e->getMessage());
        // Le lot est créé même si l'email échoue
    }
}
```

## Vérifications à faire

### 1. ⚠️ VÉRIFIER LES CREDENTIALS (PRIORITAIRE)

**Action immédiate** : Connectez-vous au webmail de Congo Electronicenter

1. Allez sur : https://congoelectronicenter.com/webmail (ou le lien webmail fourni)
2. Essayez de vous connecter avec :
   - **Email** : info@congoelectronicenter.com
   - **Mot de passe** : Ngamba-123

**Si la connexion échoue** :
- ❌ Le mot de passe est incorrect
- ❌ Le compte n'existe pas ou est désactivé
- ❌ Il faut contacter l'hébergeur pour obtenir les bons credentials

**Si la connexion réussit** :
- ✅ Les credentials sont corrects
- ➡️ Le problème vient du format de la configuration SMTP
- ➡️ Testez les différentes options ci-dessus

### 2. Vérifier dans cPanel

1. Connectez-vous à cPanel
2. Allez dans **Comptes de messagerie**
3. Vérifiez que le compte `info@congoelectronicenter.com` existe
4. Notez les paramètres SMTP exacts affichés
5. Si nécessaire, changez le mot de passe et utilisez le nouveau

### 2. Vérifier les ports ouverts

```bash
telnet mail.congoelectronicenter.com 587
telnet mail.congoelectronicenter.com 465
```

### 3. Vérifier avec un client email

Configurez Thunderbird ou Outlook avec ces paramètres pour tester.

### 4. Contacter l'hébergeur

Demandez à Congo Electronicenter :
- Les paramètres SMTP exacts
- Si l'authentification SMTP est activée
- Si une IP doit être autorisée
- Le format du username (avec ou sans @domaine.com)

## Alternative : Utiliser le serveur SMTP local

En développement, vous pouvez utiliser Mailhog (déjà dans Docker) :

```env
MAILER_DSN=smtp://mailer:1025
```

Les emails seront capturés et visibles sur : http://localhost:8025

## Alternative : Utiliser Gmail

Si Congo Electronicenter ne fonctionne pas, utilisez Gmail temporairement :

1. Créer un mot de passe d'application Gmail
2. Configurer :

```env
MAILER_DSN=gmail+smtp://votre-email@gmail.com:mot-de-passe-app@default
MAILER_FROM=votre-email@gmail.com
```

## Alternative : Utiliser SendGrid (Gratuit jusqu'à 100 emails/jour)

1. Créer un compte sur sendgrid.com
2. Obtenir une API Key
3. Configurer :

```env
MAILER_DSN=sendgrid://API_KEY@default
MAILER_FROM=info@congoelectronicenter.com
```

## Logs à vérifier

```bash
# Logs PHP
docker compose logs php --tail=100

# Logs Symfony
docker compose exec php tail -f var/log/dev.log
```

## Test manuel

Créez un script de test :

```php
// src/Command/TestEmailCommand.php
$email = (new Email())
    ->from('info@congoelectronicenter.com')
    ->to('votre-email@test.com')
    ->subject('Test SMTP')
    ->text('Test de configuration SMTP');

try {
    $this->mailer->send($email);
    echo "Email envoyé avec succès\n";
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
```

---

**Statut actuel** : La création de lots fonctionne même si l'email échoue. Les notifications seront envoyées une fois le SMTP correctement configuré.
