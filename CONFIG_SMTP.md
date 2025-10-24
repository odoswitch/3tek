# Configuration SMTP - 3Tek-Europe

## Serveur de messagerie Congo Electronicenter

### Paramètres SMTP

| Paramètre | Valeur |
|-----------|--------|
| **Serveur SMTP** | `mail.congoelectronicenter.com` |
| **Port** | `465` (SSL/TLS) ou `995` (POP3) |
| **Encryption** | SSL/TLS |
| **Nom d'utilisateur** | `info@congoelectronicenter.com` |
| **Mot de passe** | `Ngamba-123` |
| **Serveur IMAP** | `mail.congoelectronicenter.com` (Port 993) |
| **Serveur POP3** | `mail.congoelectronicenter.com` (Port 995) |

### Configuration dans .env

**IMPORTANT** : Les caractères spéciaux doivent être encodés selon RFC 3986 !

```env
###> symfony/mailer ###
# Configuration SMTP Congo Electronicenter
# @ = %40, - = %2D
# Port 465 avec SSL
MAILER_DSN=smtp://info%40congoelectronicenter.com:Ngamba%2D123@mail.congoelectronicenter.com:465?encryption=ssl
MAILER_FROM=info@congoelectronicenter.com
###< symfony/mailer ###
```

**Encodage des caractères spéciaux :**
- `@` → `%40`
- `-` → `%2D`
- `:` → `%3A`
- `/` → `%2F`
- `?` → `%3F`
- `#` → `%23`
- `[` → `%5B`
- `]` → `%5D`
- `!` → `%21`
- `$` → `%24`
- `&` → `%26`
- `'` → `%27`
- `(` → `%28`
- `)` → `%29`
- `*` → `%2A`
- `+` → `%2B`
- `,` → `%2C`
- `;` → `%3B`
- `=` → `%3D`

### Emails envoyés par l'application

L'application envoie des emails dans les cas suivants :

1. **Inscription d'un nouvel utilisateur**
   - Email de confirmation d'inscription
   - Expéditeur : info@congoelectronicenter.com
   - Template : `templates/registration/confirmation_email.html.twig`

2. **Réinitialisation de mot de passe**
   - Email avec lien de réinitialisation
   - Expéditeur : info@congoelectronicenter.com
   - Template : `templates/reset_password/email.html.twig`

3. **Notification de nouveau lot**
   - Email aux utilisateurs concernés par la catégorie
   - Expéditeur : info@congoelectronicenter.com
   - Template : `templates/emails/new_lot_notification.html.twig`

4. **Confirmation de commande (client)**
   - Email de confirmation au client
   - Expéditeur : info@congoelectronicenter.com
   - Template : `templates/emails/commande_confirmation.html.twig`

5. **Notification de commande (admin)**
   - Email aux administrateurs
   - Expéditeur : info@congoelectronicenter.com
   - Template : `templates/emails/admin_nouvelle_commande.html.twig`

### Test de la configuration

Pour tester l'envoi d'emails, utilisez la route de test :

```
GET /test
```

Cette route envoie un email de test à `info@odoip.fr` et `congocrei2000@gmail.com`.

### Dépannage

**Problème : Les emails ne sont pas envoyés**

1. Vérifiez que le serveur SMTP est accessible :
   ```bash
   telnet mail.congoelectronicenter.com 465
   ```

2. Vérifiez les logs Symfony :
   ```bash
   docker compose logs php
   ```

3. Vérifiez la configuration dans `.env` :
   - Le mot de passe ne doit pas contenir de caractères spéciaux non échappés
   - Le port doit être 465 avec encryption=ssl

**Problème : Erreur d'authentification**

- Vérifiez que le nom d'utilisateur et le mot de passe sont corrects
- Assurez-vous que le compte email existe sur le serveur

**Problème : Timeout de connexion**

- Vérifiez que le port 465 n'est pas bloqué par un pare-feu
- Essayez le port 587 avec encryption=tls si 465 ne fonctionne pas

### Sécurité

⚠️ **Important** :
- Ne jamais commiter le fichier `.env` avec les vraies credentials
- Utiliser `.env.local` pour les configurations locales
- Sur le serveur de production, définir les variables d'environnement directement dans cPanel

### Configuration cPanel

Lors du déploiement sur cPanel :

1. Créer un compte email : `info@congoelectronicenter.com`
2. Configurer les variables d'environnement dans cPanel
3. Ou modifier le fichier `.env` sur le serveur avec les bonnes valeurs

### Alternatives

Si le serveur Congo Electronicenter ne fonctionne pas, vous pouvez utiliser :

1. **Gmail SMTP** :
   ```env
   MAILER_DSN=gmail+smtp://votre-email@gmail.com:mot-de-passe-app@default
   ```

2. **SendGrid** :
   ```env
   MAILER_DSN=sendgrid://API_KEY@default
   ```

3. **Mailgun** :
   ```env
   MAILER_DSN=mailgun://API_KEY:DOMAIN@default
   ```

---

**Dernière mise à jour** : 24 octobre 2025
