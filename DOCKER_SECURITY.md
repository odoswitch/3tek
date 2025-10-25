# 🔒 Sécurité Docker - 3TEK

## ⚠️ Avertissements de Vulnérabilité

Vous pouvez voir des avertissements de vulnérabilité dans le Dockerfile. Voici ce qu'il faut savoir :

### 📋 Origine des Avertissements

Les avertissements proviennent de l'image de base officielle PHP :
```dockerfile
FROM php:8.2-fpm
```

### ✅ C'est Normal

Ces avertissements sont **normaux** car :

1. **Image officielle** : Nous utilisons l'image PHP officielle maintenue par Docker et l'équipe PHP
2. **Mises à jour régulières** : L'équipe PHP corrige les vulnérabilités rapidement
3. **Vulnérabilités mineures** : La plupart sont de faible impact ou déjà corrigées
4. **Scan automatique** : Docker scanne toutes les images et signale même les vulnérabilités mineures

### 🛡️ Comment Maintenir la Sécurité

#### 1. Mettre à jour régulièrement l'image PHP

```bash
# Télécharger la dernière version de l'image PHP
docker pull php:8.2-fpm

# Reconstruire votre image
make dev-build
# ou
docker compose build --no-cache
```

#### 2. Vérifier les mises à jour de sécurité

```bash
# Scanner l'image pour les vulnérabilités
docker scout cves php:8.2-fpm

# Ou utiliser Trivy
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy image php:8.2-fpm
```

#### 3. Utiliser une version spécifique (optionnel)

Au lieu de `php:8.2-fpm`, vous pouvez utiliser une version spécifique :

```dockerfile
# Version spécifique avec date
FROM php:8.2.15-fpm

# Ou avec digest SHA256 pour une reproductibilité totale
FROM php:8.2-fpm@sha256:abc123...
```

### 🔐 Bonnes Pratiques de Sécurité Implémentées

Notre configuration Docker inclut déjà plusieurs bonnes pratiques :

#### ✅ Multi-stage Build
```dockerfile
# Stage 1: Build
FROM php:8.2-fpm AS builder
# ... installation des dépendances ...

# Stage 2: Production (plus légère)
FROM php:8.2-fpm
# ... seulement le nécessaire ...
```
**Avantage** : Image finale plus petite = moins de surface d'attaque

#### ✅ Utilisateur non-root
```dockerfile
RUN chown -R www-data:www-data /var/www/html/var
```
**Avantage** : L'application ne tourne pas en root

#### ✅ Nettoyage des caches
```dockerfile
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
```
**Avantage** : Moins de fichiers inutiles dans l'image

#### ✅ Dépendances minimales en production
```dockerfile
# Seulement les bibliothèques runtime nécessaires
RUN apt-get install -y libpng16-16 libonig5 libxml2 libzip4 libicu72
```
**Avantage** : Moins de packages = moins de vulnérabilités potentielles

#### ✅ .dockerignore optimisé
```
.git
.env.local
var/cache/*
vendor/
```
**Avantage** : Pas de fichiers sensibles dans l'image

### 🚨 Sécurité en Production

#### Checklist de Sécurité Production

- [ ] **Secrets** : Changez tous les mots de passe par défaut
  ```bash
  # Générer un secret fort
  openssl rand -base64 32
  ```

- [ ] **APP_DEBUG** : Désactivez le mode debug
  ```env
  APP_DEBUG=0
  ```

- [ ] **HTTPS** : Utilisez SSL/TLS
  ```yaml
  nginx:
    ports:
      - "443:443"
    volumes:
      - ./ssl:/etc/nginx/ssl:ro
  ```

- [ ] **Firewall** : Limitez l'accès aux ports
  ```bash
  # Exemple avec ufw (Ubuntu)
  ufw allow 80/tcp
  ufw allow 443/tcp
  ufw deny 3306/tcp  # Ne pas exposer MySQL
  ```

- [ ] **Mises à jour** : Planifiez des mises à jour régulières
  ```bash
  # Cron job hebdomadaire
  0 2 * * 0 cd /path/to/project && docker compose pull && docker compose up -d
  ```

- [ ] **Sauvegardes** : Configurez des sauvegardes automatiques
  ```bash
  # Sauvegarde quotidienne
  0 3 * * * docker compose exec -T database mysqldump -u root -p${MYSQL_ROOT_PASSWORD} 3tek > backup_$(date +\%Y\%m\%d).sql
  ```

- [ ] **Logs** : Surveillez les logs
  ```bash
  # Configurer un système de monitoring
  docker compose logs -f | grep -i error
  ```

- [ ] **Volumes** : Protégez les données persistantes
  ```bash
  # Permissions strictes
  chmod 700 /var/lib/docker/volumes/
  ```

### 🔍 Scanner les Vulnérabilités

#### Avec Docker Scout (intégré)

```bash
# Activer Docker Scout
docker scout quickview

# Scanner une image
docker scout cves 3tek_php

# Recommandations
docker scout recommendations 3tek_php
```

#### Avec Trivy (outil tiers)

```bash
# Installer Trivy
# Windows (avec Chocolatey)
choco install trivy

# Linux
wget -qO - https://aquasecurity.github.io/trivy-repo/deb/public.key | sudo apt-key add -
echo "deb https://aquasecurity.github.io/trivy-repo/deb $(lsb_release -sc) main" | sudo tee -a /etc/apt/sources.list.d/trivy.list
sudo apt-get update && sudo apt-get install trivy

# Scanner l'image
trivy image 3tek_php
```

#### Avec Snyk

```bash
# Installer Snyk
npm install -g snyk

# Scanner
snyk container test 3tek_php
```

### 📊 Niveaux de Vulnérabilité

| Niveau | Priorité | Action |
|--------|----------|--------|
| **CRITICAL** | 🔴 Immédiate | Corriger immédiatement |
| **HIGH** | 🟠 Haute | Corriger rapidement |
| **MEDIUM** | 🟡 Moyenne | Planifier une correction |
| **LOW** | 🟢 Basse | Surveiller |

### 🛠️ Corriger les Vulnérabilités

#### Option 1 : Mettre à jour l'image de base

```bash
# 1. Vérifier la dernière version
docker pull php:8.2-fpm

# 2. Reconstruire
docker compose build --no-cache

# 3. Redémarrer
docker compose up -d
```

#### Option 2 : Utiliser une image Alpine (plus légère)

```dockerfile
# Remplacer
FROM php:8.2-fpm

# Par
FROM php:8.2-fpm-alpine

# Note: Alpine nécessite des ajustements dans les commandes d'installation
```

#### Option 3 : Construire une image personnalisée

```dockerfile
FROM php:8.2-fpm

# Mettre à jour tous les packages
RUN apt-get update && apt-get upgrade -y

# Installer les correctifs de sécurité
RUN apt-get install -y --only-upgrade \
    libssl3 \
    openssl \
    && apt-get clean
```

### 📅 Calendrier de Maintenance

#### Quotidien
- Surveiller les logs d'erreur
- Vérifier l'état des conteneurs

#### Hebdomadaire
- Vérifier les mises à jour disponibles
- Tester les sauvegardes

#### Mensuel
- Mettre à jour les images Docker
- Scanner les vulnérabilités
- Réviser les logs de sécurité

#### Trimestriel
- Audit de sécurité complet
- Mise à jour de Symfony et dépendances
- Test de restauration des sauvegardes

### 🔗 Ressources

- [Docker Security Best Practices](https://docs.docker.com/develop/security-best-practices/)
- [PHP Docker Official Images](https://hub.docker.com/_/php)
- [OWASP Docker Security](https://cheatsheetseries.owasp.org/cheatsheets/Docker_Security_Cheat_Sheet.html)
- [Symfony Security](https://symfony.com/doc/current/security.html)

### 📝 Conclusion

Les avertissements de vulnérabilité dans le Dockerfile sont **normaux et gérables** :

1. ✅ Ils proviennent de l'image PHP officielle
2. ✅ L'équipe PHP les corrige régulièrement
3. ✅ Mettez à jour l'image régulièrement
4. ✅ Suivez les bonnes pratiques de sécurité
5. ✅ Surveillez et auditez régulièrement

**L'important** : Maintenir l'image à jour et suivre les bonnes pratiques de sécurité en production.

---

**Pour mettre à jour maintenant** :
```bash
docker pull php:8.2-fpm
make dev-build
```
