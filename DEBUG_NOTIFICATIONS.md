# Debug des Notifications Email

## 🔍 Comment vérifier si les notifications sont envoyées

### **1. Consulter les logs PHP**

```bash
# Voir les logs en temps réel
docker compose logs php --tail=50 --follow

# Ou voir les derniers logs
docker compose logs php --tail=100
```

**Ce que vous devriez voir :**
```
Lot "Nom du lot" (ID: 123) - Types: VIP, Catégorie: Électronique
Notification nouveau lot "Nom du lot" - 2 utilisateur(s) trouvé(s) avec les bons critères
Envoi email à : user@example.com (Jean Dupont) - Type: VIP, Catégories: Électronique
```

---

### **2. Consulter les logs emails dans EasyAdmin**

1. Allez sur `/admin`
2. Cliquez sur **Système** → **Logs Emails**
3. Filtrez par :
   - **Type** : Notification nouveau lot
   - **Statut** : Succès ou Erreur
   - **Date** : Aujourd'hui

**Vous devriez voir :**
- ✅ Une ligne par utilisateur notifié
- ✅ Le statut (succès/erreur)
- ✅ Le message d'erreur si échec

---

## 🐛 Problèmes Courants

### **Problème 1 : Aucun utilisateur trouvé**

**Log :**
```
Aucun utilisateur trouvé pour cette catégorie et ce(s) type(s) !
```

**Causes possibles :**
1. ❌ L'utilisateur n'a pas la bonne **catégorie**
2. ❌ L'utilisateur n'a pas le bon **type** (VIP, Basique, etc.)
3. ❌ L'utilisateur n'est pas **vérifié** (`isVerified = 0`)

**Solution :**
1. Vérifiez dans `/admin` → **Users**
2. Éditez l'utilisateur
3. Vérifiez :
   - ✅ **Catégories** : Doit contenir la catégorie du lot
   - ✅ **Type** : Doit correspondre au type du lot (VIP, Basique, etc.)
   - ✅ **Vérifié** : Doit être coché

---

### **Problème 2 : Le lot n'a pas de type**

**Log :**
```
Le lot "Nom du lot" (ID: 123) n'a aucun type associé !
```

**Solution :**
1. Allez dans `/admin` → **Lots**
2. Éditez le lot
3. Sélectionnez au moins un **Type** (VIP, Basique, etc.)
4. Sauvegardez

---

### **Problème 3 : Erreur SMTP**

**Log dans EasyAdmin :**
```
Statut: Erreur
Message: Failed to authenticate on SMTP server
```

**Solution :**
1. Vérifiez le fichier `.env`
2. Corrigez `MAILER_DSN` avec les bons credentials
3. Testez la connexion SMTP

---

## ✅ Checklist de Vérification

### **Pour qu'un utilisateur reçoive une notification :**

- [ ] **Utilisateur vérifié** (`isVerified = 1`)
- [ ] **Utilisateur a la catégorie du lot** (ex: Électronique)
- [ ] **Utilisateur a le type du lot** (ex: VIP)
- [ ] **Lot a au moins un type** (VIP, Basique, etc.)
- [ ] **SMTP configuré** (si vous voulez vraiment envoyer l'email)

---

## 🧪 Test Manuel

### **Créer un lot de test :**

1. Allez dans `/admin` → **Lots** → **Créer**
2. Remplissez :
   - **Nom** : Test VIP
   - **Catégorie** : Électronique
   - **Types** : ✅ VIP
   - **Prix** : 100
   - **Quantité** : 10
3. Sauvegardez

### **Vérifier les logs :**

```bash
docker compose logs php --tail=50
```

**Vous devriez voir :**
```
Lot "Test VIP" (ID: 124) - Types: VIP, Catégorie: Électronique
Notification nouveau lot "Test VIP" - 1 utilisateur(s) trouvé(s) avec les bons critères
Envoi email à : vip@example.com (User VIP) - Type: VIP, Catégories: Électronique
```

### **Vérifier dans EasyAdmin :**

1. `/admin` → **Logs Emails**
2. Vous devriez voir une nouvelle entrée
3. Statut : **Erreur** (si SMTP non configuré) ou **Succès**

---

## 📊 Requête SQL de Debug

**Pour voir quels utilisateurs devraient recevoir la notification :**

```sql
SELECT 
    u.id,
    u.email,
    u.name,
    u.lastname,
    u.is_verified,
    t.name as type_name,
    GROUP_CONCAT(c.name) as categories
FROM user u
LEFT JOIN type t ON u.type_id = t.id
LEFT JOIN user_category uc ON u.id = uc.user_id
LEFT JOIN category c ON uc.category_id = c.id
WHERE u.is_verified = 1
GROUP BY u.id;
```

**Exécuter dans phpMyAdmin ou adminer**

---

## 🔧 Correction Apportée

**Avant :**
```php
// ❌ Ne vérifiait que la catégorie
$users = $this->userRepository->createQueryBuilder('u')
    ->where(':category MEMBER OF u.categorie')
    ->andWhere('u.isVerified = 1')
    ->setParameter('category', $lot->getCat())
    ->getQuery()
    ->getResult();
```

**Après :**
```php
// ✅ Vérifie la catégorie ET le type
$qb = $this->userRepository->createQueryBuilder('u')
    ->where(':category MEMBER OF u.categorie')
    ->andWhere('u.isVerified = 1')
    ->setParameter('category', $lot->getCat());

// Ajouter la condition pour les types
foreach ($lotTypes as $index => $type) {
    $typeConditions[] = ':type' . $index . ' = u.type';
    $qb->setParameter('type' . $index, $type);
}

$qb->andWhere('(' . implode(' OR ', $typeConditions) . ')');
```

---

## 📝 Logs Détaillés

**Maintenant, les logs affichent :**

1. ✅ Les types du lot
2. ✅ La catégorie du lot
3. ✅ Le nombre d'utilisateurs trouvés
4. ✅ Pour chaque utilisateur :
   - Email
   - Nom complet
   - Type
   - Catégories

**Exemple de log complet :**
```
Lot "Smartphone VIP" (ID: 125) - Types: VIP, Catégorie: Électronique
Notification nouveau lot "Smartphone VIP" - 2 utilisateur(s) trouvé(s) avec les bons critères
Envoi email à : alice@example.com (Alice Martin) - Type: VIP, Catégories: Électronique, Informatique
Envoi email à : bob@example.com (Bob Dupont) - Type: VIP, Catégories: Électronique
```

---

**Date de création :** 24 octobre 2025
