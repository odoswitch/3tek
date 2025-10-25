# Migration - Système de Réservation de Lots

## Résumé des modifications

Ce système permet de gérer les réservations de lots avec une file d'attente :

### Fonctionnalités implémentées :

1. **Réservation automatique** - Quand un utilisateur commande, le lot est marqué comme "réservé"
2. **File d'attente** - Les autres utilisateurs peuvent se mettre en liste d'attente
3. **Validation admin** - L'admin peut valider la commande → le lot devient "vendu"
4. **Annulation admin** - L'admin peut annuler → le lot redevient disponible et le 1er en file d'attente est notifié

### Entités modifiées/créées :

- **Lot** : Ajout des champs `statut`, `reservePar`, `reserveAt`
- **Commande** : Ajout du statut `reserve`
- **FileAttente** : Nouvelle entité pour gérer la liste d'attente

## Commandes à exécuter

### 1. Créer la migration

```bash
php bin/console make:migration
```

Cette commande va générer automatiquement un fichier de migration basé sur les modifications des entités.

### 2. Vérifier la migration générée

Ouvrez le fichier généré dans `migrations/` et vérifiez qu'il contient :

- Ajout de la colonne `statut` à la table `lot`
- Ajout de la colonne `reserve_par_id` à la table `lot`
- Ajout de la colonne `reserve_at` à la table `lot`
- Création de la table `file_attente`

### 3. Exécuter la migration

```bash
php bin/console doctrine:migrations:migrate
```

### 4. Mettre à jour les lots existants

Après la migration, tous les lots existants auront `statut = NULL`. Il faut les mettre à jour :

```bash
php bin/console doctrine:query:sql "UPDATE lot SET statut = 'disponible' WHERE statut IS NULL"
```

## Statuts possibles

### Pour les Lots :
- `disponible` : Le lot peut être commandé
- `reserve` : Le lot est réservé par un utilisateur
- `vendu` : Le lot a été validé et vendu

### Pour les Commandes :
- `en_attente` : Commande créée mais pas encore traitée
- `reserve` : Lot réservé pour cet utilisateur
- `validee` : Commande validée par l'admin
- `annulee` : Commande annulée par l'admin

### Pour la File d'Attente :
- `en_attente` : Utilisateur en attente
- `notifie` : Utilisateur notifié que le lot est disponible
- `expire` : Notification expirée (optionnel, pour usage futur)

## Workflow complet

1. **Utilisateur A commande un lot disponible**
   - Lot passe en statut `reserve`
   - Commande créée avec statut `reserve`
   - Email de confirmation envoyé

2. **Utilisateur B essaie de commander le même lot**
   - Système détecte que le lot est réservé
   - Utilisateur B est ajouté à la file d'attente (position 1)
   - Message affiché : "Vous êtes en position 1 dans la file d'attente"

3. **Admin valide la commande de A**
   - Commande passe en statut `validee`
   - Lot passe en statut `vendu`
   - Lot n'apparaît plus dans les listings

4. **Admin annule la commande de A**
   - Commande passe en statut `annulee`
   - Lot redevient `disponible`
   - Utilisateur B (1er en file) reçoit un email de notification
   - B peut maintenant commander le lot

## Tests à effectuer

### Test 1 : Réservation simple
1. Connectez-vous en tant qu'utilisateur
2. Commandez un lot disponible
3. Vérifiez que le lot est marqué "Réservé" pour les autres utilisateurs

### Test 2 : File d'attente
1. Avec un 2ème utilisateur, essayez de commander le même lot
2. Vérifiez que vous êtes ajouté à la file d'attente
3. Vérifiez votre position dans la file

### Test 3 : Validation admin
1. Connectez-vous en tant qu'admin
2. Validez une commande
3. Vérifiez que le lot disparaît des listings (statut = vendu)

### Test 4 : Annulation et notification
1. Créez une réservation avec utilisateur A
2. Ajoutez utilisateur B en file d'attente
3. Annulez la réservation de A en tant qu'admin
4. Vérifiez que B reçoit un email de notification
5. Vérifiez que le lot est à nouveau disponible

## Déploiement en production

1. **Sauvegardez la base de données**
   ```bash
   mysqldump -u root -p db_3tek > backup_avant_migration.sql
   ```

2. **Uploadez les fichiers modifiés** sur le serveur

3. **Exécutez les migrations**
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Mettez à jour les lots existants**
   ```bash
   php bin/console doctrine:query:sql "UPDATE lot SET statut = 'disponible' WHERE statut IS NULL"
   ```

5. **Videz le cache**
   ```bash
   php bin/console cache:clear --env=prod
   ```

## Rollback (en cas de problème)

Si vous devez annuler la migration :

```bash
# Restaurer la base de données
mysql -u root -p db_3tek < backup_avant_migration.sql

# Ou revenir à la migration précédente
php bin/console doctrine:migrations:migrate prev
```

## Fichiers modifiés

- `src/Entity/Lot.php`
- `src/Entity/Commande.php`
- `src/Entity/FileAttente.php` (nouveau)
- `src/Repository/FileAttenteRepository.php` (nouveau)
- `src/Controller/CommandeController.php`
- `src/Controller/Admin/CommandeCrudController.php`

## Notes importantes

- Les emails de notification utilisent la configuration SMTP existante
- La file d'attente est gérée par ordre d'arrivée (FIFO)
- Un utilisateur ne peut être qu'une seule fois dans la file d'attente pour un lot donné
- Les notifications sont envoyées automatiquement lors de l'annulation d'une réservation
