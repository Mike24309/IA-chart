# Déploiement de l'application IA-chart sur GitHub et Render

## Objectif

Ce document explique, de manière détaillée, comment l'application **IA-chart** a été préparée, versionnée sur GitHub, puis déployée sur **Render** avec une base **PostgreSQL**. Il sert de guide de référence pour refaire le déploiement sur une autre machine ou pour comprendre l'architecture de publication.

## 1. Préparation du projet local

Avant le déploiement, le projet Laravel a été vérifié et stabilisé en local.

### Étapes réalisées

1. Vérification du fonctionnement du projet en local.
2. Contrôle des vues, des routes et du comportement des fonctionnalités principales.
3. Nettoyage des erreurs liées au module IA et aux formulaires.
4. Ajout des fichiers nécessaires au déploiement Docker.
5. Préparation du code pour une exécution compatible avec Render.

### Fichiers importants préparés

- `Dockerfile`
- `.dockerignore`
- `composer.json`
- `composer.lock`
- `routes/web.php`
- `resources/views/*`
- `app/Http/Controllers/*`
- `database/migrations/*`

## 2. Création du dépôt GitHub

Le projet a été envoyé sur GitHub afin que Render puisse le récupérer automatiquement.

### Commandes utilisées en local

```bash
git remote add origin https://github.com/Mike24309/IA-chart.git
git branch -M main
git add .
git commit -m "Initial commit"
git push -u origin main
```

### Rôle de chaque commande

- `git remote add origin ...` : lie le dépôt local au dépôt GitHub.
- `git branch -M main` : renomme la branche principale en `main`.
- `git add .` : ajoute tous les fichiers au suivi Git.
- `git commit -m ...` : crée un point de sauvegarde du projet.
- `git push -u origin main` : envoie le code sur GitHub.

## 3. Choix de Render

Render a été choisi pour héberger l'application web.

### Pourquoi Render

- déploiement simple avec GitHub
- support Docker
- gestion facile des variables d'environnement
- base PostgreSQL intégrée
- redéploiement automatique à chaque push sur GitHub

## 4. Création de la base de données Render

Une base **PostgreSQL** a été créée sur Render.

### Champs configurés

- `Name` : nom du service de base de données
- `Region` : même région que le service web
- type : `Postgres`

### Informations récupérées

Depuis l'onglet de connexion de la base PostgreSQL, les valeurs suivantes ont été récupérées :

- `Hostname`
- `Port`
- `Database`
- `Username`
- `Password`

Ces informations ont ensuite été placées dans les variables d'environnement du service web.

## 5. Création du service Web Render

Le service web a été créé à partir du dépôt GitHub.

### Étapes effectuées

1. Ouverture du menu `New`.
2. Choix de `Web Service`.
3. Connexion du dépôt GitHub `Mike24309/IA-chart`.
4. Sélection de la branche `main`.
5. Choix du type `Docker`.

### Pourquoi Docker

Le projet Laravel a été déployé avec Docker afin de garantir un environnement reproductible et compatible avec Render.

## 6. Dockerfile de production

Un `Dockerfile` a été ajouté pour permettre à Render de construire et lancer l'application.

### Rôle du Dockerfile

- installer PHP et les extensions nécessaires
- installer Composer
- préparer le dossier Laravel
- lancer l'application sur le port fourni par Render

### Points importants

- le service doit écouter sur `0.0.0.0`
- Render fournit le port via la variable `PORT`
- le container doit être compatible avec PostgreSQL

## 7. Variables d'environnement

Les variables d'environnement ont été configurées dans Render.

### Variables principales

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=la_cle_laravel
APP_URL=https://ia-chart.onrender.com
ASSET_URL=https://ia-chart.onrender.com
```

### Variables base de données

```env
DB_CONNECTION=pgsql
DB_HOST=hostname_render
DB_PORT=5432
DB_DATABASE=nom_base
DB_USERNAME=utilisateur_base
DB_PASSWORD=mot_de_passe_base
```

### Rôle de ces variables

- `APP_ENV` : mode production
- `APP_DEBUG` : désactive le debug en ligne
- `APP_KEY` : clé de chiffrement Laravel
- `APP_URL` : URL publique du service
- `ASSET_URL` : aide à charger correctement les assets
- `DB_CONNECTION` : moteur PostgreSQL
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` : connexion à la base

## 8. Déploiement initial

Une fois le service créé et les variables configurées, le déploiement a été lancé.

### Processus

1. Cliquer sur `Save, rebuild, and deploy`.
2. Attendre la construction de l'image Docker.
3. Vérifier les logs de déploiement.
4. Ouvrir l'URL fournie par Render.

## 9. Problèmes rencontrés et corrections

Plusieurs problèmes ont été corrigés pendant le déploiement.

### 9.1. Erreur `SET FOREIGN_KEY_CHECKS`

#### Problème

Une erreur PostgreSQL est apparue à cause d'une instruction MySQL :

```sql
SET FOREIGN_KEY_CHECKS = 0
```

#### Correction

Les migrations ont été adaptées pour ne plus exécuter cette instruction sur PostgreSQL.

### 9.2. `500 Server Error`

#### Problème

Le service affichait un `500` côté navigateur.

#### Causes possibles corrigées

- configuration `.env` incomplète
- `APP_URL` absent
- migrations non appliquées
- problèmes de permissions sur `storage` et `bootstrap/cache`

### 9.3. CSS non chargé

#### Problème

Le site apparaissait sans style sur Render.

#### Correction

`APP_URL` et `ASSET_URL` ont été ajoutés pour que les ressources CSS/JS soient chargées correctement.

### 9.4. `Failed to fetch` sur le module IA

#### Problème

L'interface IA renvoyait parfois `Failed to fetch`.

#### Correction

Les routes IA ont été injectées en relatif pour éviter les erreurs d'URL absolues mal résolues.

## 10. Vérifications réalisées après déploiement

Après déploiement, plusieurs points ont été vérifiés :

- ouverture de l'application depuis l'URL Render
- affichage du login
- chargement du CSS et du JS
- accès à la base PostgreSQL
- fonctionnement du module IA
- affichage des rapports PDF

## 11. Résultat obtenu

Le projet est maintenant :

- versionné sur GitHub
- déployable sur Render
- connecté à PostgreSQL
- compatible avec Docker
- prêt à être relancé sur une autre machine

## 12. Recommandation pour une nouvelle machine

Pour relancer le projet ailleurs :

1. Cloner le dépôt GitHub.
2. Installer PHP, Composer et PostgreSQL.
3. Copier le fichier `.env`.
4. Renseigner les variables de base de données.
5. Lancer :

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan serve
```

## 13. Conclusion

Le déploiement de **IA-chart** a été réalisé en deux parties :

- publication du code sur GitHub
- hébergement du service web sur Render avec Docker et PostgreSQL

L'application peut maintenant être maintenue, redéployée et reprise plus facilement sur un autre environnement.

