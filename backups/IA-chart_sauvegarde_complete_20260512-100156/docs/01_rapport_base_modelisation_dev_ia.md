# Rapport 1 - Base De Donnees, Modelisation Et Architecture

## 1. Objet du rapport

Ce document explique la base de donnees reelle de l application `DEV IA`, ses relations, ses cles etrangeres, la facon de faire :

- le diagramme de classes ;
- le diagramme de cas d utilisation ;
- l explication de l architecture de developpement.

Le but est que tu puisses t en servir dans le memoire et a la defense.

## 2. Architecture de developpement

L application est construite avec :

- `Laravel 10` pour la structure generale ;
- `PHP 8.1+` pour la logique serveur ;
- `MySQL` pour la base de donnees ;
- `Blade` pour les pages visibles ;
- `CSS` dans `resources/css/app.css` pour le style ;
- `JavaScript` dans `resources/js/app.js` pour les interactions dynamiques ;
- `DomPDF` pour les PDF ;
- `Gemini API` pour l intelligence artificielle ;
- `SMTP` pour l envoi des factures par email.

## 3. Structure technique generale

L architecture est de type `MVC`.

- `Models`
  Ils representent les tables de la base.
- `Views`
  Elles affichent l interface utilisateur.
- `Controllers`
  Ils recoivent les actions de l utilisateur et appellent les traitements.
- `Services`
  Ils contiennent la logique metier importante comme la facturation, le stock, les parametres et l IA.

En plus de MVC, le projet utilise :

- `middlewares`
  pour proteger l acces selon le role et l etat du compte ;
- `routes`
  pour definir les URL et les actions ;
- `mail`
  pour envoyer les factures ;
- `DomPDF`
  pour generer les PDF ;
- `Gemini`
  pour analyser les donnees et repondre aux questions IA.

## 4. Tables de la base reelle

La base contient actuellement `19 tables`.

### 4.1 Tables metier et IA

Les tables principales sont :

1. `roles`
2. `utilisateurs`
3. `categories`
4. `clients`
5. `produits`
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

### 4.2 Tables techniques Laravel

Les tables techniques sont :

1. `migrations`
2. `failed_jobs`
3. `password_reset_tokens`
4. `personal_access_tokens`

Pour le memoire, les plus importantes sont les `15 tables metier et IA`.

## 5. Correspondance table -> classe

| Table | Classe |
|---|---|
| `roles` | `Role` |
| `utilisateurs` | `User` |
| `categories` | `Category` |
| `clients` | `Client` |
| `produits` | `Produit` |
| `factures` | `Facture` |
| `details_facture` | `FactureDetail` |
| `mouvements_stock` | `MouvementStock` |
| `activites_utilisateurs` | `ActiviteUtilisateur` |
| `parametres` | `Parametre` |
| `parametres_ia` | `ParametreIa` |
| `journaux_ia` | `LogIa` |
| `scores_performance` | `ScorePerformance` |
| `notifications_ia` | `NotificationIa` |
| `historiques_rapports_ia` | `HistoriqueRapportIa` |

## 6. Description des classes et tables

### 6.1 `Role`

Role de la table :
elle contient les profils d acces.

Cle primaire :
- `id`

Champs importants :
- `nom`
- `libelle`
- `description`

Modelisation UML conseillee :

```text
Role
-------------------------
- id : int
- nom : string
- libelle : string
- description : text
```

Ce que tu peux dire :
la classe `Role` sert a separer les droits administrateur et employe.

### 6.2 `User`

Role de la table :
elle contient les comptes qui se connectent a l application.

Cle primaire :
- `id`

Cle etrangere :
- `role_id -> roles.id`

Champs importants :
- `nom`
- `adresse_email`
- `code_connexion`
- `telephone`
- `chemin_photo_profil`
- `mot_de_passe`
- `est_actif`
- `dernier_login_a`

Modelisation UML conseillee :

```text
User
-------------------------
- id : int
- nom : string
- adresse_email : string
- code_connexion : string
- telephone : string
- chemin_photo_profil : string
- mot_de_passe : string
- est_actif : boolean
- dernier_login_a : datetime
```

Ce que tu peux dire :
la classe `User` est centrale, car elle participe a la connexion, aux factures, au stock, aux activites et a l IA.

### 6.3 `Category`

Role de la table :
elle regroupe les produits par famille.

Cle primaire :
- `id`

Champs importants :
- `nom`
- `description`
- `est_active`

Modelisation UML conseillee :

```text
Category
-------------------------
- id : int
- nom : string
- description : text
- est_active : boolean
```

### 6.4 `Client`

Role de la table :
elle contient les informations des clients factures.

Cle primaire :
- `id`

Champs importants :
- `nom`
- `postnom`
- `email`
- `telephone`
- `adresse`
- `entreprise`

Modelisation UML conseillee :

```text
Client
-------------------------
- id : int
- nom : string
- postnom : string
- email : string
- telephone : string
- adresse : text
- entreprise : string
```

### 6.5 `Produit`

Role de la table :
elle contient les produits vendus et suivis en stock.

Cle primaire :
- `id`

Cle etrangere :
- `categorie_id -> categories.id`

Champs importants :
- `nom`
- `reference`
- `prix_achat`
- `prix_vente`
- `stock`
- `stock_minimum`
- `chemin_photo`
- `description`
- `est_actif`

Modelisation UML conseillee :

```text
Produit
-------------------------
- id : int
- nom : string
- reference : string
- prix_achat : decimal
- prix_vente : decimal
- stock : int
- stock_minimum : int
- chemin_photo : string
- description : text
- est_actif : boolean
```

### 6.6 `Facture`

Role de la table :
elle contient l entete de la facture.

Cle primaire :
- `id`

Cles etrangeres :
- `client_id -> clients.id`
- `utilisateur_id -> utilisateurs.id`

Champs importants :
- `numero_facture`
- `date_facture`
- `date_echeance`
- `total_ht`
- `taux_tva`
- `montant_tva`
- `montant_remise`
- `total_ttc`
- `statut`
- `notes`

Modelisation UML conseillee :

```text
Facture
-------------------------
- id : int
- numero_facture : string
- date_facture : date
- date_echeance : date
- total_ht : decimal
- taux_tva : decimal
- montant_tva : decimal
- montant_remise : decimal
- total_ttc : decimal
- statut : string
- notes : text
```

Pourquoi `Facture` et `FactureDetail` sont separes :

- `Facture` contient les informations generales ;
- `FactureDetail` contient les lignes de vente ;
- une facture peut contenir plusieurs produits.

### 6.7 `FactureDetail`

Role de la table :
elle contient les lignes d une facture.

Cle primaire :
- `id`

Cles etrangeres :
- `facture_id -> factures.id`
- `produit_id -> produits.id`

Champs importants :
- `description`
- `quantite`
- `prix_unitaire_ht`
- `total_ligne_ht`

Modelisation UML conseillee :

```text
FactureDetail
-------------------------
- id : int
- description : string
- quantite : int
- prix_unitaire_ht : decimal
- total_ligne_ht : decimal
```

### 6.8 `MouvementStock`

Role de la table :
elle trace toutes les entrees, sorties et ajustements.

Cle primaire :
- `id`

Cles etrangeres :
- `produit_id -> produits.id`
- `utilisateur_id -> utilisateurs.id`

Champs importants :
- `type_mouvement`
- `quantite`
- `stock_avant`
- `stock_apres`
- `motif`
- `date_mouvement`

Modelisation UML conseillee :

```text
MouvementStock
-------------------------
- id : int
- type_mouvement : string
- quantite : int
- stock_avant : int
- stock_apres : int
- motif : string
- date_mouvement : date
```

### 6.9 `ActiviteUtilisateur`

Role de la table :
elle garde la trace des actions faites dans l application.

Cle primaire :
- `id`

Cle etrangere :
- `utilisateur_id -> utilisateurs.id`

Champs importants :
- `action`
- `type_cible`
- `cible_id`
- `metadonnees`
- `adresse_ip`
- `agent_utilisateur`

### 6.10 `Parametre`

Role de la table :
elle contient la configuration globale.

Cle primaire :
- `id`

Pas de cle etrangere metier.

Champs importants a montrer dans le diagramme :
- `nom_entreprise`
- `email_entreprise`
- `telephone_entreprise`
- `adresse_entreprise`
- `devise`
- `taux_tva_defaut`
- `pourcentage_remise_defaut`
- `format_numero_facture`
- `delai_echeance_facture_jours`
- `seuil_stock_minimum_global`
- `nom_banque`
- `numero_compte_bancaire`
- `conditions_facture`
- `couleur_principale_facture`
- `couleur_secondaire_facture`
- `titre_facture`

Champs de personnalisation facture qui existent aussi en base :
- `chemin_logo`
- `chemin_fond_facture`
- `libelle_emetteur_facture`
- `libelle_facturer_a`
- `libelle_envoyer_a`
- `libelle_paiement_facture`
- `libelle_signature_facture`
- `libelle_banque_pied_facture`
- `libelle_compte_pied_facture`
- `libelle_contact_pied_facture`
- `libelle_description_facture`
- `libelle_quantite_facture`
- `libelle_prix_unitaire_facture`
- `libelle_montant_facture`
- `afficher_signature_facture`
- `note_pied_facture`

### 6.11 `ParametreIa`

Role de la table :
elle contient les regles de l IA.

Champs importants :
- `seuil_marge_minimale`
- `seuil_stock_critique`
- `niveau_sensibilite`
- `activer_analyse_comportementale`
- `activer_audit_quotidien`
- `modele_openrouter`
- `duree_cache_minutes`
- `activer_alertes_automatiques`
- `activer_pdf_automatique`
- `activer_mode_temps_reel`

Remarque importante a dire :
le nom physique de la colonne est encore `modele_openrouter`, mais elle sert actuellement a stocker le modele `Gemini`.

### 6.12 `LogIa`

Role de la table :
elle memorise les analyses et les conversations IA.

Cle primaire :
- `id`

Cle etrangere :
- `utilisateur_id -> utilisateurs.id`

Champs importants :
- `type_journal`
- `statut`
- `signature_entree`
- `charge_entree`
- `charge_sortie`
- `message_erreur`
- `genere_le`

### 6.13 `ScorePerformance`

Role de la table :
elle garde le score global calcule par l IA.

Cle primaire :
- `id`

Cle etrangere :
- `journal_ia_id -> journaux_ia.id`

Champs importants :
- `score_global`
- `detail_score`
- `explication_score`

### 6.14 `NotificationIa`

Role de la table :
elle garde les alertes de l IA.

Cle primaire :
- `id`

Cle etrangere :
- `journal_ia_id -> journaux_ia.id`

Champs importants :
- `titre`
- `message`
- `niveau_gravite`
- `est_lue`
- `notifiee_le`

### 6.15 `HistoriqueRapportIa`

Role de la table :
elle garde la trace des rapports IA generes.

Cle primaire :
- `id`

Cles etrangeres :
- `journal_ia_id -> journaux_ia.id`
- `utilisateur_id -> utilisateurs.id`

Champs importants :
- `titre_rapport`
- `genere_le`

## 7. Liste simple des cles etrangeres

1. `utilisateurs.role_id -> roles.id`
2. `produits.categorie_id -> categories.id`
3. `factures.client_id -> clients.id`
4. `factures.utilisateur_id -> utilisateurs.id`
5. `details_facture.facture_id -> factures.id`
6. `details_facture.produit_id -> produits.id`
7. `mouvements_stock.produit_id -> produits.id`
8. `mouvements_stock.utilisateur_id -> utilisateurs.id`
9. `activites_utilisateurs.utilisateur_id -> utilisateurs.id`
10. `journaux_ia.utilisateur_id -> utilisateurs.id`
11. `scores_performance.journal_ia_id -> journaux_ia.id`
12. `notifications_ia.journal_ia_id -> journaux_ia.id`
13. `historiques_rapports_ia.journal_ia_id -> journaux_ia.id`
14. `historiques_rapports_ia.utilisateur_id -> utilisateurs.id`

## 8. Relations et cardinalites

### 8.1 Noyau metier

- `Role (1) ---- User (0..*)`
- `Category (1) ---- Produit (0..*)`
- `Client (1) ---- Facture (0..*)`
- `User (1) ---- Facture (0..*)`
- `Facture (1) ---- FactureDetail (1..*)`
- `Produit (1) ---- FactureDetail (0..*)`
- `Produit (1) ---- MouvementStock (0..*)`
- `User (1) ---- MouvementStock (0..*)`
- `User (1) ---- ActiviteUtilisateur (0..*)`

### 8.2 Sous-systeme IA

- `User (1) ---- LogIa (0..*)`
- `LogIa (1) ---- ScorePerformance (0..*)`
- `LogIa (1) ---- NotificationIa (0..*)`
- `LogIa (1) ---- HistoriqueRapportIa (0..*)`
- `User (1) ---- HistoriqueRapportIa (0..*)`

## 9. Comment faire le diagramme de classes

Le mieux est de travailler `par package`.

### 9.1 Package Authentification et utilisateurs

- `Role`
- `User`
- `ActiviteUtilisateur`

### 9.2 Package Gestion commerciale

- `Client`
- `Facture`
- `FactureDetail`

### 9.3 Package Gestion de stock

- `Category`
- `Produit`
- `MouvementStock`

### 9.4 Package Parametrage

- `Parametre`
- `ParametreIa`

### 9.5 Package IA

- `LogIa`
- `ScorePerformance`
- `NotificationIa`
- `HistoriqueRapportIa`

## 10. Comment modeliser les classes

Regle simple :

- une table importante devient une classe ;
- les colonnes importantes deviennent des attributs ;
- les cles etrangeres deviennent des associations ;
- les cardinalites montrent combien d objets sont lies.

Tu n es pas oblige de mettre `created_at` et `updated_at` dans ton diagramme UML.

## 11. Diagramme de cas d utilisation

### 11.1 Acteurs

- `Administrateur`
- `Employe`
- `Client` optionnel

### 11.2 Cas d utilisation de l administrateur

- Se connecter
- Gerer son profil
- Gerer les utilisateurs
- Gerer les categories
- Gerer les produits
- Gerer les clients
- Gerer le stock
- Consulter les mouvements de stock
- Creer une facture
- Voir les factures
- Telecharger une facture PDF
- Envoyer une facture par email
- Supprimer une facture
- Gerer les parametres
- Lancer une analyse locale
- Lancer une analyse avec IA
- Consulter les notifications IA
- Poser une question a l IA
- Telecharger le rapport IA

### 11.3 Cas d utilisation de l employe

- Se connecter
- Gerer son profil
- Voir les produits
- Voir le stock disponible
- Creer une facture
- Voir ses factures
- Telecharger une facture PDF
- Envoyer une facture par email

### 11.4 Cas d utilisation du client

- Recevoir une facture par email
- Consulter une facture PDF

## 12. Relations `include`

Les `include` montrent les traitements obligatoires.

- `Creer une facture` `<<include>>` `Selectionner un client`
- `Creer une facture` `<<include>>` `Calculer les montants`
- `Creer une facture` `<<include>>` `Generer la facture PDF`
- `Envoyer une facture par email` `<<include>>` `Generer la facture PDF`
- `Lancer une analyse avec IA` `<<include>>` `Lire les donnees de l application`
- `Lancer une analyse avec IA` `<<include>>` `Construire le resume`
- `Lancer une analyse avec IA` `<<include>>` `Generer les alertes`
- `Telecharger le rapport IA` `<<include>>` `Verifier qu une vraie analyse IA existe`

## 13. Relations `extend`

Les `extend` montrent les actions optionnelles ou complementaires.

- `Ajouter un client` `<<extend>>` `Creer une facture`
- `Telecharger une facture PDF` `<<extend>>` `Voir les factures`
- `Envoyer une facture par email` `<<extend>>` `Voir les factures`
- `Supprimer une facture` `<<extend>>` `Voir les factures`
- `Poser une question a l IA` `<<extend>>` `Lancer une analyse avec IA`
- `Consulter les notifications IA` `<<extend>>` `Lancer une analyse avec IA`

## 14. Architecture de l application en une phrase

Tu peux dire a l oral :

`L application DEV IA est une application web Laravel construite en architecture MVC, avec une base MySQL en francais, une logique metier centralisee dans des services, une generation PDF via DomPDF, et une analyse intelligente reliee a Gemini pour produire des resumés, alertes et rapports.`

## 15. Resume oral simple

Tu peux dire :

`La base de donnees de DEV IA est organisee autour des utilisateurs, des roles, des clients, des produits, des factures et des mouvements de stock. Les factures sont separees de leurs lignes pour respecter une bonne modelisation. En plus de la partie transactionnelle, un sous-systeme IA enregistre les analyses, les scores, les notifications et les rapports generes. Le projet suit une architecture MVC avec des services pour la logique metier et des vues Blade pour l interface.`
