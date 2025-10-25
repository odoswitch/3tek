# 🚀 Tests en mode Production - 3Tek-Europe

## 📋 Objectif

Ce guide vous permet de tester l'application en mode production **localement** avant le déploiement sur cPanel.

## ⚙️ Configuration actuelle

Le fichier `.env` a été modifié pour activer le mode production :

```env
APP_ENV=prod
APP_DEBUG=0
```

## 🧪 Tests à effectuer

### 1. Vider et régénérer le cache

```bash
docker compose exec php php bin/console cache:clear --env=prod
docker compose exec php php bin/console cache:warmup --env=prod
```

### 2. Tester les pages principales

- ✅ Page d'accueil : http://localhost:8080
- ✅ Page de connexion : http://localhost:8080/login
- ✅ Dashboard : http://localhost:8080/dash
- ✅ Administration : http://localhost:8080/admin

### 3. Tester les pages d'erreur

#### Page 404 (Page non trouvée)
```
http://localhost:8080/page-qui-nexiste-pas
```
Devrait afficher une belle page d'erreur 404 personnalisée.

#### Page 500 (Erreur serveur)
Pour tester, créer temporairement une erreur dans un contrôleur.

### 4. Tester les fonctionnalités

- [ ] Inscription utilisateur
- [ ] Connexion/Déconnexion
- [ ] Affichage des lots
- [ ] Ajout au panier
- [ ] Passage de commande
- [ ] Envoi d'emails
- [ ] Upload d'images (admin)
- [ ] Gestion des commandes (admin)

### 5. Vérifier les logs

```bash
# Voir les logs en temps réel
docker compose exec php tail -f var/log/prod.log

# Voir les dernières erreurs
docker compose exec php tail -n 50 var/log/prod.log
```

### 6. Tester les performances

En mode production, l'application devrait être **plus rapide** car :
- Le cache est optimisé
- Le debug est désactivé
- Les assets sont compilés

## 🔍 Différences Production vs Développement

| Fonctionnalité | Développement | Production |
|---|---|---|
| Debug toolbar | ✅ Visible | ❌ Cachée |
| Messages d'erreur | Détaillés | Génériques |
| Cache | Régénéré à chaque requête | Persistant |
| Logs | Verbeux | Erreurs uniquement |
| Performance | Lente | Rapide |

## 🐛 Résolution de problèmes

### Erreur "Class not found"
```bash
docker compose exec php composer dump-autoload --optimize
docker compose exec php php bin/console cache:clear --env=prod
```

### Erreur de permissions
```bash
docker compose exec php chmod -R 777 var/
docker compose exec php chmod -R 777 public/uploads/
```

### Cache corrompu
```bash
docker compose exec php rm -rf var/cache/prod
docker compose exec php php bin/console cache:warmup --env=prod
```

## 🔄 Revenir en mode développement

Quand vous avez fini les tests, revenez en mode développement :

```bash
# 1. Modifier .env
APP_ENV=dev
APP_DEBUG=1

# 2. Vider le cache
docker compose exec php php bin/console cache:clear
```

## 📝 Checklist avant déploiement

- [ ] Tous les tests passent en mode production
- [ ] Pages d'erreur personnalisées fonctionnent
- [ ] Emails s'envoient correctement
- [ ] Uploads d'images fonctionnent
- [ ] Base de données accessible
- [ ] Pas d'erreurs dans les logs
- [ ] Performance acceptable
- [ ] `.env.example` créé avec les bonnes variables
- [ ] `.gitignore` à jour
- [ ] Documentation de déploiement prête

## 📚 Fichiers créés pour le déploiement

1. **`.env.example`** : Template de configuration pour cPanel
2. **`DEPLOIEMENT_CPANEL.md`** : Guide complet de déploiement
3. **`test_production.sh`** : Script de tests automatisés
4. **`README_PRODUCTION.md`** : Ce fichier

## 🚀 Prochaines étapes

1. ✅ Tester en mode production localement
2. ✅ Vérifier toutes les fonctionnalités
3. ✅ Corriger les bugs éventuels
4. ✅ Revenir en mode dev
5. ✅ Commit et push sur Git
6. ✅ Déployer sur cPanel (suivre `DEPLOIEMENT_CPANEL.md`)

## 💡 Conseils

- **Ne jamais** commiter le fichier `.env` avec les vraies credentials
- **Toujours** tester en production localement avant de déployer
- **Faire** un backup de la base avant chaque déploiement
- **Vérifier** les logs après chaque déploiement

## 📞 Support

En cas de problème :
- Consulter les logs : `var/log/prod.log`
- Vérifier la configuration : `php bin/console about`
- Contacter : contact@3tek-europe.com

---

**Bonne chance pour le déploiement ! 🎉**
