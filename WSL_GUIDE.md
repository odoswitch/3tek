# 🐧 Guide WSL pour 3TEK

## 🎯 Pourquoi WSL ?

- ✅ **Performance** : Docker tourne nativement sur Linux
- ✅ **Compatibilité** : Toutes les commandes Linux fonctionnent
- ✅ **Pas de bugs** : Fini les problèmes de chemins Windows
- ✅ **Outils Linux** : Make, bash, grep, etc. disponibles

## 📋 Installation Complète

### 1. Installer WSL 2

Ouvrez **PowerShell en administrateur** :

```powershell
# Installer WSL avec Ubuntu
wsl --install

# Redémarrer votre PC si demandé

# Après redémarrage, définir WSL 2 par défaut
wsl --set-default-version 2

# Vérifier l'installation
wsl --list --verbose
```

Vous devriez voir :
```
  NAME      STATE           VERSION
* Ubuntu    Running         2
```

### 2. Configurer Docker Desktop

1. Ouvrez **Docker Desktop**
2. **Settings** (⚙️) → **General**
   - ✅ Cochez **"Use the WSL 2 based engine"**
3. **Resources** → **WSL Integration**
   - ✅ Activez **"Enable integration with my default WSL distro"**
   - ✅ Activez **Ubuntu** dans la liste
4. Cliquez **"Apply & Restart"**

### 3. Premier Démarrage WSL

Ouvrez un terminal **Ubuntu** (depuis le menu Démarrer) :

```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer les outils essentiels
sudo apt install -y make git curl wget

# Vérifier que Docker fonctionne
docker --version
docker compose version
```

### 4. Accéder au Projet

Dans le terminal Ubuntu :

```bash
# Aller dans votre projet (sur le disque E:)
cd /mnt/e/DEV_IA/3tek

# Lancer le script de configuration
chmod +x setup-wsl.sh
./setup-wsl.sh

# Recharger la configuration
source ~/.bashrc
```

### 5. Démarrer l'Application

```bash
# Méthode 1 : Avec l'alias
3tek-start

# Méthode 2 : Avec Make
cd /mnt/e/DEV_IA/3tek
make dev

# Méthode 3 : Avec Docker Compose
cd /mnt/e/DEV_IA/3tek
docker compose -f compose.yaml -f compose.override.yaml up -d
```

## 🚀 Commandes Rapides (après setup-wsl.sh)

```bash
3tek          # Aller dans le projet
3tek-start    # Démarrer l'application
3tek-stop     # Arrêter l'application
3tek-logs     # Voir les logs
3tek-shell    # Accéder au conteneur PHP
```

## 💻 Configurer VSCode pour WSL

### Installer l'Extension WSL

1. Ouvrez VSCode
2. Extensions (Ctrl+Shift+X)
3. Cherchez **"WSL"**
4. Installez **"WSL"** par Microsoft

### Ouvrir le Projet dans WSL

**Méthode 1 : Depuis VSCode**
1. **F1** ou **Ctrl+Shift+P**
2. Tapez : **"WSL: Open Folder in WSL"**
3. Naviguez vers `/mnt/e/DEV_IA/3tek`

**Méthode 2 : Depuis le Terminal WSL**
```bash
cd /mnt/e/DEV_IA/3tek
code .
```

VSCode va se reconnecter en mode WSL (vous verrez "WSL: Ubuntu" en bas à gauche).

## 🔧 Configuration du Terminal dans VSCode

1. **Ctrl+`** pour ouvrir le terminal
2. Cliquez sur le **+** à côté du terminal
3. Sélectionnez **"Ubuntu (WSL)"**
4. Définissez-le comme terminal par défaut

Ou dans les settings VSCode :
```json
{
  "terminal.integrated.defaultProfile.windows": "Ubuntu (WSL)"
}
```

## 📂 Structure des Fichiers

### Depuis Windows
```
E:\DEV_IA\3tek\
```

### Depuis WSL
```
/mnt/e/DEV_IA/3tek/
```

Les deux pointent vers le même endroit !

## 🎯 Workflow Complet

### Développement Quotidien

```bash
# 1. Ouvrir le terminal WSL (Ubuntu)
# 2. Aller dans le projet
3tek

# 3. Démarrer l'application
3tek-start

# 4. Voir les logs
3tek-logs

# 5. Ouvrir VSCode
code .

# 6. Développer...

# 7. Arrêter l'application
3tek-stop
```

### Commandes Utiles

```bash
# Voir l'état des conteneurs
docker compose ps

# Accéder au shell PHP
3tek-shell
# ou
docker compose exec php bash

# Exécuter une commande Symfony
docker compose exec php php bin/console cache:clear

# Voir les logs d'un service spécifique
docker compose logs -f php
docker compose logs -f nginx

# Redémarrer les conteneurs
docker compose restart

# Reconstruire les images
docker compose build --no-cache
docker compose up -d
```

## 🔍 Dépannage

### Docker n'est pas accessible dans WSL

**Solution** :
1. Ouvrez Docker Desktop
2. Settings → Resources → WSL Integration
3. Activez Ubuntu
4. Apply & Restart

### Le projet est lent

**Solution** : Déplacez le projet dans le système de fichiers WSL :

```bash
# Copier le projet dans WSL
cp -r /mnt/e/DEV_IA/3tek ~/3tek

# Travailler depuis là
cd ~/3tek
```

Les fichiers dans `~/` (système WSL) sont **beaucoup plus rapides** que `/mnt/e/` (disque Windows).

### Permission denied

```bash
# Corriger les permissions
sudo chown -R $USER:$USER /mnt/e/DEV_IA/3tek
```

### Make ne fonctionne pas

```bash
# Installer Make
sudo apt install -y make

# Vérifier
make --version
```

## 📊 Comparaison Performance

| Action | Windows | WSL 2 |
|--------|---------|-------|
| Build Docker | ~2 min | ~30 sec |
| Composer install | ~1 min | ~15 sec |
| Cache clear | ~10 sec | ~2 sec |
| Hot reload | Lent | Instantané |

## 🎓 Astuces WSL

### Accéder aux fichiers Windows depuis WSL
```bash
cd /mnt/c/Users/VotreNom/Documents
cd /mnt/e/DEV_IA
```

### Accéder aux fichiers WSL depuis Windows
```
\\wsl$\Ubuntu\home\username\
```

Ou dans l'explorateur : `\\wsl$`

### Copier/Coller dans le Terminal WSL
- **Copier** : Ctrl+Shift+C
- **Coller** : Ctrl+Shift+V
- Ou clic droit

### Ouvrir l'Explorateur Windows depuis WSL
```bash
# Ouvrir le dossier courant dans l'explorateur
explorer.exe .
```

## 🚀 Commandes Make Disponibles

Une fois dans WSL, vous pouvez utiliser toutes les commandes du Makefile :

```bash
make help              # Liste toutes les commandes
make dev               # Démarrer en développement
make dev-build         # Reconstruire et démarrer
make logs              # Voir les logs
make shell             # Shell PHP
make migrate           # Migrations
make cache-clear       # Vider le cache
make install           # Installation complète
make clean             # Nettoyer
```

## ✅ Checklist Post-Installation

- [ ] WSL 2 installé
- [ ] Ubuntu installé
- [ ] Docker Desktop configuré pour WSL
- [ ] Docker accessible depuis WSL (`docker --version`)
- [ ] setup-wsl.sh exécuté
- [ ] Alias fonctionnels (`3tek-start`)
- [ ] VSCode avec extension WSL
- [ ] Projet ouvert dans WSL
- [ ] Application démarre avec `3tek-start`
- [ ] http://localhost:8080 accessible

## 🎉 Résultat Final

Après configuration, vous aurez :

✅ Un environnement Linux complet sur Windows  
✅ Docker qui tourne nativement  
✅ Toutes les commandes Linux disponibles  
✅ Performance optimale  
✅ VSCode intégré avec WSL  
✅ Commandes simplifiées avec les alias  

**Prochaine étape** : Ouvrez un terminal Ubuntu et tapez `3tek-start` ! 🚀
