# Configuration du Fuseau Horaire (France - Paris)

## ✅ Modifications Apportées

### **1. PHP - Fichier `php-custom.ini`**

```ini
; Fuseau horaire pour la France (Paris)
date.timezone = Europe/Paris
```

**Impact :** Toutes les fonctions PHP de date/heure utiliseront le fuseau horaire de Paris.

---

### **2. Docker Compose - Fichier `compose.yaml`**

**Service PHP :**
```yaml
php:
  environment:
    - TZ=Europe/Paris
```

**Service MySQL :**
```yaml
database:
  environment:
    TZ: Europe/Paris
  command: --default-time-zone='+01:00'
```

**Impact :** Les conteneurs Docker et MySQL utiliseront le fuseau horaire de Paris.

---

## 🔄 Redémarrage Nécessaire

Pour appliquer les changements, redémarrez les conteneurs :

```bash
# Arrêter les conteneurs
docker compose down

# Redémarrer les conteneurs
docker compose up -d

# Vérifier que tout fonctionne
docker compose ps
```

---

## 🧪 Vérification

### **1. Vérifier le timezone PHP**

```bash
docker compose exec php php -r "echo date_default_timezone_get();"
```

**Résultat attendu :** `Europe/Paris`

### **2. Vérifier l'heure PHP**

```bash
docker compose exec php php -r "echo date('Y-m-d H:i:s');"
```

**Résultat attendu :** Heure actuelle de Paris

### **3. Vérifier le timezone MySQL**

```bash
docker compose exec database mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT @@global.time_zone, @@session.time_zone;"
```

**Résultat attendu :** `+01:00` ou `+02:00` (selon heure d'été/hiver)

### **4. Vérifier dans l'application**

1. Allez sur `/admin` → **Logs Emails**
2. Vérifiez la colonne **Date/Heure**
3. L'heure doit correspondre à l'heure de Paris

---

## 📅 Gestion Heure d'Été/Hiver

**Europe/Paris gère automatiquement :**
- ✅ **Heure d'hiver** : UTC+1 (CET)
- ✅ **Heure d'été** : UTC+2 (CEST)
- ✅ **Changement automatique** : Dernier dimanche de mars et octobre

**Pas besoin de configuration manuelle !**

---

## 🌍 Autres Fuseaux Horaires

Si vous déployez dans un autre pays, modifiez :

**Belgique :**
```ini
date.timezone = Europe/Brussels
```

**Suisse :**
```ini
date.timezone = Europe/Zurich
```

**Canada (Montréal) :**
```ini
date.timezone = America/Montreal
```

**Liste complète :** https://www.php.net/manual/fr/timezones.php

---

## 🔧 Configuration pour Production (cPanel)

### **Fichier `.htaccess`**

Ajoutez dans le fichier `.htaccess` :

```apache
# Fuseau horaire PHP
php_value date.timezone "Europe/Paris"
```

### **Fichier `php.ini` (si accessible)**

```ini
date.timezone = Europe/Paris
```

### **Dans le code PHP (solution de secours)**

Si vous ne pouvez pas modifier la configuration serveur :

```php
// config/bootstrap.php ou public/index.php
date_default_timezone_set('Europe/Paris');
```

---

## 📊 Impact sur les Dates

**Avant (UTC) :**
```
Création lot : 2025-10-24 10:35:00 (UTC)
Affichage : 10:35 (incorrect pour la France)
```

**Après (Europe/Paris) :**
```
Création lot : 2025-10-24 12:35:00 (Paris)
Affichage : 12:35 (correct !)
```

---

## ⚠️ Points d'Attention

### **1. Dates déjà enregistrées**

Les dates déjà en base de données ne seront **pas converties automatiquement**.

**Solution :** Elles s'afficheront correctement car PHP interprétera les dates avec le bon timezone.

### **2. Comparaisons de dates**

Utilisez toujours `DateTimeImmutable` ou `DateTime` avec timezone :

```php
// ✅ Bon
$now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

// ✅ Bon (utilise le timezone par défaut)
$now = new \DateTimeImmutable();

// ❌ Éviter
$now = time(); // Retourne toujours UTC
```

### **3. API externes**

Les API externes utilisent souvent UTC. Convertissez si nécessaire :

```php
// Recevoir une date UTC et la convertir en Paris
$utcDate = new \DateTime('2025-10-24 10:00:00', new \DateTimeZone('UTC'));
$parisDate = $utcDate->setTimezone(new \DateTimeZone('Europe/Paris'));
```

---

## 📝 Résumé

**Configuration appliquée :**
- ✅ PHP : `Europe/Paris`
- ✅ Docker PHP : `TZ=Europe/Paris`
- ✅ MySQL : `+01:00` (ajusté automatiquement)

**Actions à faire :**
1. Redémarrer les conteneurs Docker
2. Vérifier l'heure dans l'application
3. Tester la création d'un lot
4. Vérifier l'heure dans les logs emails

**Toutes les dates/heures seront maintenant à l'heure de Paris ! 🇫🇷**

---

**Date de configuration :** 24 octobre 2025
