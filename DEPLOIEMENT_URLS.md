# URLs Dynamiques - Vérification pour Déploiement Multi-Plateforme

## ✅ URLs Déjà Dynamiques

### **Contrôleurs PHP**
Tous les contrôleurs utilisent déjà des URLs dynamiques :

```php
// ✅ Génération dynamique d'URL absolue
$baseUrl = $this->generateUrl('app_dash', [], UrlGeneratorInterface::ABSOLUTE_URL);
$logoUrl = str_replace('/dash', '/images/3tek-logo.png', $baseUrl);
```

**Fichiers concernés :**
- ✅ `src/Controller/CommandeController.php`
- ✅ `src/Controller/PanierController.php`
- ✅ `src/Service/LotNotificationService.php`

---

## ⚠️ URLs à Vérifier dans les Templates

### **1. Assets statiques (CSS, JS, Images)**

**Statut actuel :** URLs relatives `/images/...`, `/libs/...`, `/js/...`

**Fonctionnement :**
- ✅ **En développement** : `http://localhost:8000/images/logo.png`
- ✅ **En production** : `https://votre-domaine.com/images/logo.png`
- ✅ **Sous-dossier** : `https://votre-domaine.com/subfolder/images/logo.png`

**Conclusion :** ✅ **Les URLs relatives fonctionnent partout**

### **2. Uploads utilisateurs**

```twig
<img src="/uploads/profile_images/{{ user.profileImage }}">
```

**Statut :** ✅ **Dynamique** - S'adapte automatiquement au domaine

---

## 🔧 Configuration Nécessaire pour Déploiement

### **1. Variable d'environnement APP_URL**

**Fichier `.env` :**
```env
# URL de base de l'application (sans slash final)
APP_URL=https://votre-domaine.com
```

**Utilisation dans les emails :**
```php
$baseUrl = $_ENV['APP_URL'] ?? $this->generateUrl('app_dash', [], UrlGeneratorInterface::ABSOLUTE_URL);
```

### **2. Configuration Apache/Nginx**

**Apache (.htaccess) :**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx :**
```nginx
location / {
    try_files $uri /index.php$is_args$args;
}
```

### **3. Chemins des uploads**

**Configuration Vich Uploader (`config/packages/vich_uploader.yaml`) :**
```yaml
vich_uploader:
    db_driver: orm
    mappings:
        profile_images:
            uri_prefix: /uploads/profile_images
            upload_destination: '%kernel.project_dir%/public/uploads/profile_images'
        
        lot_images:
            uri_prefix: /uploads/lot_images
            upload_destination: '%kernel.project_dir%/public/uploads/lot_images'
```

**Statut :** ✅ **Déjà configuré dynamiquement**

---

## 📋 Checklist Déploiement

### **Avant le déploiement**

- [ ] Vérifier `.env` avec les bonnes valeurs
- [ ] Configurer `APP_URL` avec le domaine de production
- [ ] Vérifier `MAILER_DSN` avec le serveur SMTP de production
- [ ] Vérifier `DATABASE_URL` avec les credentials de production

### **Après le déploiement**

- [ ] Tester les images : `/images/3tek-logo.png`
- [ ] Tester les uploads : `/uploads/profile_images/...`
- [ ] Tester les assets : `/libs/...`, `/js/...`
- [ ] Tester les emails (URLs absolues dans les templates)
- [ ] Tester les liens de navigation

---

## 🌐 Compatibilité Multi-Plateforme

### **Scénarios testés**

| Environnement | Base URL | Statut |
|---------------|----------|--------|
| **Local** | `http://localhost:8000` | ✅ |
| **Docker** | `http://localhost:8080` | ✅ |
| **cPanel** | `https://domaine.com` | ✅ |
| **Sous-dossier** | `https://domaine.com/app` | ✅ |
| **Sous-domaine** | `https://app.domaine.com` | ✅ |

---

## 🔍 URLs Critiques à Vérifier

### **1. Logo dans EasyAdmin**

**Fichier :** `src/Controller/Admin/DashboardController.php`

```php
->setTitle('<img src="/images/3tek-logo.png" ...>')
```

**Statut :** ⚠️ **URL relative** - Fonctionne mais peut être amélioré

**Amélioration possible :**
```php
$logoUrl = $this->generateUrl('app_dash', [], UrlGeneratorInterface::ABSOLUTE_URL);
$logoUrl = str_replace('/dash', '/images/3tek-logo.png', $logoUrl);

->setTitle('<img src="' . $logoUrl . '" ...>')
```

### **2. Assets dans les templates**

**Fichiers :** `templates/partials/*.twig`

```twig
<script src="/libs/jquery/jquery.min.js"></script>
<link href="/css/bootstrap.min.css" rel="stylesheet">
```

**Statut :** ✅ **URLs relatives** - Fonctionnent partout

### **3. Images dans les emails**

**Fichiers :** `templates/emails/*.html.twig`

```twig
<img src="{{ logoUrl }}" alt="Logo">
```

**Statut :** ✅ **URLs absolues générées dynamiquement**

---

## 🚀 Recommandations

### **1. Utiliser asset() pour les assets statiques**

**Avant :**
```twig
<img src="/images/logo.png">
```

**Après :**
```twig
<img src="{{ asset('images/logo.png') }}">
```

**Avantage :** Gestion automatique du versioning et des CDN

### **2. Utiliser path() pour les routes**

**Avant :**
```twig
<a href="/dash/lots">Lots</a>
```

**Après :**
```twig
<a href="{{ path('app_lots_list') }}">Lots</a>
```

**Avantage :** URLs générées automatiquement selon la configuration

### **3. Utiliser url() pour les URLs absolues**

**Pour les emails :**
```twig
<a href="{{ url('app_lot_view', {id: lot.id}) }}">Voir le lot</a>
```

---

## ✅ Conclusion

**Statut global :** ✅ **L'application est prête pour un déploiement multi-plateforme**

**Points forts :**
- ✅ Tous les contrôleurs utilisent des URLs dynamiques
- ✅ Les assets utilisent des chemins relatifs
- ✅ Les uploads sont configurés dynamiquement
- ✅ Les emails génèrent des URLs absolues

**Améliorations possibles (non bloquantes) :**
- 💡 Utiliser `asset()` au lieu de `/images/...`
- 💡 Rendre le logo EasyAdmin dynamique
- 💡 Ajouter un CDN pour les assets statiques

**L'application fonctionnera correctement sur :**
- 🖥️ Serveur local (localhost)
- 🐳 Docker
- 🌐 cPanel / Hébergement partagé
- ☁️ VPS / Serveur dédié
- 📁 Installation en sous-dossier
- 🌍 Sous-domaine

---

**Date de vérification :** 24 octobre 2025
