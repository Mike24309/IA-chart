# Rapport 01 - Base De Donnees Reelle De DEV IA

## 1. Objectif

Ce document decrit la base de donnees actuelle du projet `DEV IA` :

- tables metier ;
- relations ;
- cardinalites ;
- ordre logique de creation des donnees ;
- role de chaque table dans l application.

L objectif est de pouvoir expliquer la base a un jury de facon claire et defendable.

## 2. Vue d ensemble

La base contient 19 tables au total :

### 2.1 Tables metier et IA

- `roles`
- `utilisateurs`
- `categories`
- `clients`
- `produits`
- `factures`
- `details_facture`
- `mouvements_stock`
- `activites_utilisateurs`
- `parametres`
- `parametres_ia`
- `journaux_ia`
- `scores_performance`
- `notifications_ia`
- `historiques_rapports_ia`

### 2.2 Tables techniques Laravel

- `migrations`
- `password_reset_tokens`
- `failed_jobs`
- `personal_access_tokens`

## 3. Ordre logique des tables

Pour comprendre le projet, il faut lire les tables dans cet ordre :

1. `roles`
2. `utilisateurs`
3. `categories`
4. `produits`
5. `clients`
6. `factures`
7. `details_facture`
8. `mouvements_stock`
9. `activites_utilisateurs`
10. `parametres`
11. `parametres_ia`
12. `journaux_ia`
13. `scores_performance`
14. `notifications_ia`
15. `historiques_rapports_ia`

Cette logique est simple :

- on cree les profils ;
- ensuite les comptes ;
- ensuite le catalogue ;
- ensuite les clients ;
- ensuite les ventes ;
- ensuite les traces de stock et d audit ;
- enfin les parametres et les resultats IA.

## 4. Relations et cardinalites

### 4.1 `roles` -> `utilisateurs`

- Relation : `Role` 1 -> N `User`
- Cle : `utilisateurs.role_id`

Un role peut etre attribue a plusieurs utilisateurs.

### 4.2 `categories` -> `produits`

- Relation : `Category` 1 -> N `Produit`
- Cle : `produits.categorie_id`

Une categorie regroupe plusieurs produits.

### 4.3 `clients` -> `factures`

- Relation : `Client` 1 -> N `Facture`
- Cle : `factures.client_id`

Un client peut avoir plusieurs factures.

### 4.4 `utilisateurs` -> `factures`

- Relation : `User` 1 -> N `Facture`
- Cle : `factures.utilisateur_id`

Un utilisateur peut creer plusieurs factures.

### 4.5 `factures` -> `details_facture`

- Relation : `Facture` 1 -> N `FactureDetail`
- Cle : `details_facture.facture_id`

Une facture contient plusieurs lignes.

### 4.6 `produits` -> `details_facture`

- Relation : `Produit` 1 -> N `FactureDetail`
- Cle : `details_facture.produit_id`

Un produit peut apparaitre dans plusieurs factures.

### 4.7 `produits` -> `mouvements_stock`

- Relation : `Produit` 1 -> N `MouvementStock`
- Cle : `mouvements_stock.produit_id`

Chaque produit conserve son historique de stock.

### 4.8 `utilisateurs` -> `mouvements_stock`

- Relation : `User` 1 -> N `MouvementStock`
- Cle : `mouvements_stock.utilisateur_id`

Chaque mouvement de stock est attribue a un utilisateur.

### 4.9 `utilisateurs` -> `activites_utilisateurs`

- Relation : `User` 1 -> N `ActiviteUtilisateur`

Cette table journalise les actions de la plateforme.

### 4.10 Tables IA

Le bloc IA fonctionne autour de :

- `journaux_ia` : un journal par analyse ou question ;
- `scores_performance` : les scores calcules ;
- `notifications_ia` : les alertes visibles par l administrateur ;
- `historiques_rapports_ia` : l historique des rapports exportes.

Dans la pratique :

- un journal peut produire plusieurs notifications ;
- un journal peut alimenter un score ;
- un journal peut donner lieu a un rapport PDF.

## 5. Table par table

### `roles`

Contient les profils de droits : administrateur, employe.

### `utilisateurs`

Contient les comptes connectables. La connexion se fait par email ou code de connexion.

### `categories`

Organise les produits par famille.

### `clients`

Contient les acheteurs factures.

### `produits`

Contient le catalogue, le stock, les prix et l activite du produit.

### `factures`

Contient l entete de la vente : numero, client, date, devise, total, statut.

### `details_facture`

Contient les lignes de la facture : produit, quantite, prix unitaire, total de ligne.

### `mouvements_stock`

Contient l historique des entrees, sorties et ajustements.

### `activites_utilisateurs`

Conserve l historique des actions importantes.

### `parametres`

Contient les reglages generaux de l entreprise et des factures.

### `parametres_ia`

Contient les reglages du module IA.

### `journaux_ia`

Enregistre chaque analyse ou question lancee par l administrateur.

### `scores_performance`

Conserve le score global et les details de performance.

### `notifications_ia`

Stocke les alertes a afficher dans l interface IA.

### `historiques_rapports_ia`

Archive les rapports exportes par l IA.

## 6. Point a retenir pour la soutenance

La base est normalisee par blocs :

- authentification ;
- catalogue ;
- ventes ;
- stock ;
- parametres ;
- audit ;
- IA.

Le principe general est :

> les donnees operationnelles sont d abord calculees par l application, puis l IA les interprete.

Cela permet d expliquer au jury que l IA n est pas le moteur de verite, mais une couche d interpretation au-dessus d une base transactionnelle stable.
