# Rapport Base De Donnees - DEV IA

## 1. Objet du document

Ce document presente la base de donnees reelle de l'application DEV IA.

Il sert a :

- comprendre le role de chaque table ;
- identifier les classes metier a placer dans le diagramme de classes ;
- retrouver les cles primaires et les cles etrangeres ;
- preciser les cardinalites ;
- aider a construire un diagramme UML ou un schema relationnel clair.

Le document est ecrit dans un langage simple pour pouvoir etre reutilise dans un memoire ou pendant la soutenance.

Important :
les informations de ce rapport ont ete alignees sur la structure reelle observee dans la base actuelle du projet, et pas seulement sur les intentions du code.

## 2. Nombre de tables

La base contient actuellement 19 tables.

### 2.1 Tables metier et IA

Les 15 tables principales du projet sont :

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

### 2.2 Tables techniques Laravel

Les tables techniques sont :

1. `migrations`
2. `password_reset_tokens`
3. `failed_jobs`
4. `personal_access_tokens`

Pour le memoire, le diagramme de classes et la defense, il faut surtout travailler sur les 15 tables metier et IA.

## 3. Correspondance table -> classe

Chaque table principale correspond a une classe Eloquent dans `app/Models`.

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

## 4. Description detaillee des tables

### 4.1 `roles`

Role de la table :
Cette table contient les profils d acces de l application.

Cle primaire :
- `id`

Champs importants :
- `nom` : nom technique du role, par exemple `admin` ou `employee` ;
- `libelle` : nom lisible du role ;
- `description` : explication du role.

Utilite metier :
Elle sert a separer les droits administrateur et employe.

Classe UML a retenir :
`Role`

### 4.2 `utilisateurs`

Role de la table :
Cette table contient tous les comptes capables de se connecter au systeme.

Cle primaire :
- `id`

Cle etrangere :
- `role_id` -> `roles.id`

Champs importants :
- `nom` : nom complet de l utilisateur ;
- `adresse_email` : email de connexion ;
- `code_connexion` : code de connexion attribue par l administrateur ;
- `telephone` : numero de telephone ;
- `chemin_photo_profil` : photo de profil ;
- `mot_de_passe` : mot de passe chiffre ;
- `est_actif` : etat du compte ;
- `dernier_login_a` : derniere connexion.

Utilite metier :
Cette table gere l authentification, les roles, le suivi des utilisateurs et la liaison avec les factures, les mouvements de stock et l audit.

Classe UML a retenir :
`User`

### 4.3 `categories`

Role de la table :
Cette table contient les familles ou groupes de produits.

Cle primaire :
- `id`

Champs importants :
- `nom`
- `description`
- `est_active`

Utilite metier :
Elle permet de regrouper les produits par categorie pour la lisibilite, les filtres et les statistiques.

Classe UML a retenir :
`Category`

### 4.4 `clients`

Role de la table :
Cette table enregistre les clients factures par l entreprise.

Cle primaire :
- `id`

Champs importants :
- `nom`
- `postnom`
- `email`
- `telephone`
- `adresse`
- `entreprise`

Utilite metier :
Elle permet d associer chaque facture a un client bien identifie.

Classe UML a retenir :
`Client`

### 4.5 `produits`

Role de la table :
Cette table contient tous les produits vendus et suivis en stock.

Cle primaire :
- `id`

Cle etrangere :
- `categorie_id` -> `categories.id`

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

Utilite metier :
Cette table est centrale dans l application car elle relie la vente, le stock, les mouvements et les statistiques.

Classe UML a retenir :
`Produit`

### 4.6 `factures`

Role de la table :
Cette table contient l entete de chaque facture.

Cle primaire :
- `id`

Cles etrangeres :
- `client_id` -> `clients.id`
- `utilisateur_id` -> `utilisateurs.id`

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

Utilite metier :
Cette table porte l information generale de la facture : qui a facture, pour quel client, a quelle date et pour quel montant.

Classe UML a retenir :
`Facture`

### 4.7 `details_facture`

Role de la table :
Cette table contient le detail des lignes d une facture.

Cle primaire :
- `id`

Cles etrangeres :
- `facture_id` -> `factures.id`
- `produit_id` -> `produits.id`

Champs importants :
- `description`
- `quantite`
- `prix_unitaire_ht`
- `total_ligne_ht`

Utilite metier :
Elle permet de decomposer une facture en plusieurs produits vendus.

Classe UML a retenir :
`FactureDetail`

### 4.8 `mouvements_stock`

Role de la table :
Cette table garde l historique complet de toutes les entrees, sorties et corrections de stock.

Cle primaire :
- `id`

Cles etrangeres :
- `produit_id` -> `produits.id`
- `utilisateur_id` -> `utilisateurs.id`

Champs importants :
- `type_mouvement`
- `quantite`
- `stock_avant`
- `stock_apres`
- `motif`
- `date_mouvement`

Utilite metier :
Elle sert de journal de stock pour assurer la tracabilite.

Classe UML a retenir :
`MouvementStock`

### 4.9 `activites_utilisateurs`

Role de la table :
Cette table conserve les actions realisees par les utilisateurs.

Cle primaire :
- `id`

Cle etrangere :
- `utilisateur_id` -> `utilisateurs.id`

Champs importants :
- `action`
- `type_cible`
- `cible_id`
- `metadonnees`
- `adresse_ip`
- `agent_utilisateur`

Utilite metier :
Elle sert a l audit interne et au suivi des actions sensibles.

Classe UML a retenir :
`ActiviteUtilisateur`

### 4.10 `parametres`

Role de la table :
Cette table stocke la configuration generale de l application.

Cle primaire :
- `id`

Particularite :
Cette table n a pas de cle etrangere metier, car elle joue le role de table de configuration globale.

Champs importants :
- `nom_entreprise`
- `email_entreprise`
- `telephone_entreprise`
- `adresse_entreprise`
- `chemin_logo`
- `chemin_fond_facture`
- `devise`
- `taux_tva_defaut`
- `pourcentage_remise_defaut`
- `format_numero_facture`
- `delai_echeance_facture_jours`
- `seuil_stock_minimum_global`
- `nom_banque`
- `nom_compte_bancaire`
- `numero_compte_bancaire`
- `code_swift`
- `conditions_facture`
- `couleur_principale_facture`
- `couleur_secondaire_facture`
- `titre_facture`
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

Utilite metier :
Elle centralise les regles globales utilisees par toute l application, notamment la TVA, la remise par defaut et la personnalisation des factures.

Classe UML a retenir :
`Parametre`

### 4.11 `parametres_ia`

Role de la table :
Cette table contient les regles de fonctionnement de l analyse IA.

Cle primaire :
- `id`

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

Utilite metier :
Elle permet de regler le comportement du moteur IA sans toucher au code.

Classe UML a retenir :
`ParametreIa`

### 4.12 `journaux_ia`

Role de la table :
Cette table memorise toutes les analyses et traitements realises par le module IA.

Cle primaire :
- `id`

Cle etrangere :
- `utilisateur_id` -> `utilisateurs.id`

Champs importants :
- `type_journal`
- `statut`
- `signature_entree`
- `charge_entree`
- `charge_sortie`
- `message_erreur`
- `genere_le`

Utilite metier :
Elle garde la trace de ce que l IA a recu, produit et retourne.

Classe UML a retenir :
`LogIa`

### 4.13 `scores_performance`

Role de la table :
Cette table conserve le score global de performance calcule par l IA.

Cle primaire :
- `id`

Cle etrangere :
- `journal_ia_id` -> `journaux_ia.id`

Champs importants :
- `score_global`
- `detail_score`
- `explication_score`

Utilite metier :
Elle sert a conserver la note finale et l explication du score.

Classe UML a retenir :
`ScorePerformance`

### 4.14 `notifications_ia`

Role de la table :
Cette table contient les alertes generees par l IA.

Cle primaire :
- `id`

Cle etrangere :
- `journal_ia_id` -> `journaux_ia.id`

Champs importants :
- `titre`
- `message`
- `niveau_gravite`
- `est_lue`
- `notifiee_le`

Utilite metier :
Elle permet d archiver les alertes et de les afficher sur le dashboard.

Classe UML a retenir :
`NotificationIa`

### 4.15 `historiques_rapports_ia`

Role de la table :
Cette table garde la trace des rapports IA generes.

Cle primaire :
- `id`

Cles etrangeres :
- `journal_ia_id` -> `journaux_ia.id`
- `utilisateur_id` -> `utilisateurs.id`

Champs importants :
- `titre_rapport`
- `genere_le`

Utilite metier :
Elle permet de savoir quel rapport a ete genere, a partir de quelle analyse et par quel utilisateur.

Classe UML a retenir :
`HistoriqueRapportIa`

## 5. Relations detaillees et cardinalites

Cette section est la plus importante pour faire le diagramme de classes.

### 5.1 Relations du noyau metier

#### `Role` -> `User`

- Relation : un role peut etre attribue a plusieurs utilisateurs ;
- Cardinalite : `Role (1)` ---- `User (0..*)`
- Sens inverse : un utilisateur appartient a un seul role ;
- Cardinalite inverse : `User (1)` ---- `Role (1)`
- Cle etrangere : `utilisateurs.role_id`

#### `Category` -> `Produit`

- Relation : une categorie peut contenir plusieurs produits ;
- Cardinalite : `Category (1)` ---- `Produit (0..*)`
- Sens inverse : un produit appartient a zero ou une categorie selon la configuration ;
- Cardinalite inverse : `Produit (0..1)` ---- `Category (1)`
- Cle etrangere : `produits.categorie_id`
- Remarque : la relation est nullable, donc un produit peut exister sans categorie.

#### `Client` -> `Facture`

- Relation : un client peut avoir plusieurs factures ;
- Cardinalite : `Client (1)` ---- `Facture (0..*)`
- Sens inverse : une facture appartient a un seul client ;
- Cardinalite inverse : `Facture (1)` ---- `Client (1)`
- Cle etrangere : `factures.client_id`

#### `User` -> `Facture`

- Relation : un utilisateur peut creer plusieurs factures ;
- Cardinalite : `User (1)` ---- `Facture (0..*)`
- Sens inverse : une facture est creee par un seul utilisateur ;
- Cardinalite inverse : `Facture (1)` ---- `User (1)`
- Cle etrangere : `factures.utilisateur_id`

#### `Facture` -> `FactureDetail`

- Relation : une facture contient plusieurs lignes ;
- Cardinalite : `Facture (1)` ---- `FactureDetail (1..*)`
- Sens inverse : une ligne appartient a une seule facture ;
- Cardinalite inverse : `FactureDetail (1)` ---- `Facture (1)`
- Cle etrangere : `details_facture.facture_id`
- Remarque : au niveau metier, une facture sans ligne n a pas de sens. Donc en UML, tu peux mettre `1..*`.

#### `Produit` -> `FactureDetail`

- Relation : un produit peut apparaitre dans plusieurs lignes de facture ;
- Cardinalite : `Produit (1)` ---- `FactureDetail (0..*)`
- Sens inverse : une ligne de facture concerne un seul produit ;
- Cardinalite inverse : `FactureDetail (1)` ---- `Produit (1)`
- Cle etrangere : `details_facture.produit_id`

#### `Produit` -> `MouvementStock`

- Relation : un produit peut avoir plusieurs mouvements de stock ;
- Cardinalite : `Produit (1)` ---- `MouvementStock (0..*)`
- Sens inverse : un mouvement de stock concerne un seul produit ;
- Cardinalite inverse : `MouvementStock (1)` ---- `Produit (1)`
- Cle etrangere : `mouvements_stock.produit_id`

#### `User` -> `MouvementStock`

- Relation : un utilisateur peut enregistrer plusieurs mouvements de stock ;
- Cardinalite : `User (1)` ---- `MouvementStock (0..*)`
- Sens inverse : un mouvement est saisi par un seul utilisateur ;
- Cardinalite inverse : `MouvementStock (1)` ---- `User (1)`
- Cle etrangere : `mouvements_stock.utilisateur_id`

#### `User` -> `ActiviteUtilisateur`

- Relation : un utilisateur peut avoir plusieurs activites journalisees ;
- Cardinalite : `User (1)` ---- `ActiviteUtilisateur (0..*)`
- Sens inverse : une activite appartient a un seul utilisateur ;
- Cardinalite inverse : `ActiviteUtilisateur (0..1)` ---- `User (1)`
- Cle etrangere : `activites_utilisateurs.utilisateur_id`
- Remarque : la colonne est nullable au niveau base, donc pour un schema tres exact on peut noter `0..1` du cote activite vers utilisateur.

### 5.2 Relations du sous-systeme IA

#### `User` -> `LogIa`

- Relation : un utilisateur peut lancer plusieurs analyses IA ;
- Cardinalite : `User (1)` ---- `LogIa (0..*)`
- Sens inverse : un journal IA est rattache a un utilisateur ;
- Cardinalite inverse : `LogIa (0..1)` ---- `User (1)`
- Cle etrangere : `journaux_ia.utilisateur_id`
- Remarque : la colonne a ete creee nullable dans les anciennes migrations, donc `0..1` est la lecture la plus prudente.

#### `LogIa` -> `ScorePerformance`

- Relation : une analyse IA peut produire un ou plusieurs scores enregistres ;
- Cardinalite : `LogIa (1)` ---- `ScorePerformance (0..*)`
- Sens inverse : un score appartient a un seul journal IA ;
- Cardinalite inverse : `ScorePerformance (0..1)` ---- `LogIa (1)`
- Cle etrangere : `scores_performance.journal_ia_id`

#### `LogIa` -> `NotificationIa`

- Relation : une analyse IA peut produire plusieurs notifications ;
- Cardinalite : `LogIa (1)` ---- `NotificationIa (0..*)`
- Sens inverse : une notification appartient a un seul journal IA ;
- Cardinalite inverse : `NotificationIa (0..1)` ---- `LogIa (1)`
- Cle etrangere : `notifications_ia.journal_ia_id`

#### `LogIa` -> `HistoriqueRapportIa`

- Relation : une analyse IA peut produire plusieurs rapports exportes ;
- Cardinalite : `LogIa (1)` ---- `HistoriqueRapportIa (0..*)`
- Sens inverse : un rapport historique se rattache a un seul journal IA ;
- Cardinalite inverse : `HistoriqueRapportIa (0..1)` ---- `LogIa (1)`
- Cle etrangere : `historiques_rapports_ia.journal_ia_id`

#### `User` -> `HistoriqueRapportIa`

- Relation : un utilisateur peut generer plusieurs rapports IA ;
- Cardinalite : `User (1)` ---- `HistoriqueRapportIa (0..*)`
- Sens inverse : un rapport historique peut etre associe a un utilisateur ;
- Cardinalite inverse : `HistoriqueRapportIa (0..1)` ---- `User (1)`
- Cle etrangere : `historiques_rapports_ia.utilisateur_id`

## 6. Liste claire des cles etrangeres

Voici la liste des cles etrangeres a montrer dans ton memoire ou a utiliser pour la modelisation.

### 6.1 Cles etrangeres du noyau metier

1. `utilisateurs.role_id` -> `roles.id`
2. `produits.categorie_id` -> `categories.id`
3. `factures.client_id` -> `clients.id`
4. `factures.utilisateur_id` -> `utilisateurs.id`
5. `details_facture.facture_id` -> `factures.id`
6. `details_facture.produit_id` -> `produits.id`
7. `mouvements_stock.produit_id` -> `produits.id`
8. `mouvements_stock.utilisateur_id` -> `utilisateurs.id`
9. `activites_utilisateurs.utilisateur_id` -> `utilisateurs.id`

### 6.2 Cles etrangeres du module IA

10. `journaux_ia.utilisateur_id` -> `utilisateurs.id`
11. `scores_performance.journal_ia_id` -> `journaux_ia.id`
12. `notifications_ia.journal_ia_id` -> `journaux_ia.id`
13. `historiques_rapports_ia.journal_ia_id` -> `journaux_ia.id`
14. `historiques_rapports_ia.utilisateur_id` -> `utilisateurs.id`

## 7. Lecture prete pour le diagramme de classes UML

Si tu veux dessiner le diagramme, tu peux partir exactement de cette lecture.

### 7.1 Package Authentification et utilisateurs

- `Role` 1 ---- 0..* `User`
- `User` 1 ---- 0..* `ActiviteUtilisateur`

### 7.2 Package Gestion commerciale

- `Client` 1 ---- 0..* `Facture`
- `User` 1 ---- 0..* `Facture`
- `Facture` 1 ---- 1..* `FactureDetail`
- `Produit` 1 ---- 0..* `FactureDetail`

### 7.3 Package Gestion de stock

- `Category` 1 ---- 0..* `Produit`
- `Produit` 1 ---- 0..* `MouvementStock`
- `User` 1 ---- 0..* `MouvementStock`

### 7.4 Package Parametrage

- `Parametre`
- `ParametreIa`

Ces deux classes sont plutot des tables de configuration. Elles ne portent pas de relation forte obligatoire avec les autres tables dans le diagramme metier principal.

### 7.5 Package Analyse IA

- `User` 1 ---- 0..* `LogIa`
- `LogIa` 1 ---- 0..* `ScorePerformance`
- `LogIa` 1 ---- 0..* `NotificationIa`
- `LogIa` 1 ---- 0..* `HistoriqueRapportIa`
- `User` 1 ---- 0..* `HistoriqueRapportIa`

## 8. Conseils de modelisation

### 8.1 Faut-il tout mettre sur un seul diagramme ?

Pour le memoire, le mieux est :

- un petit diagramme global de synthese ;
- puis un diagramme detaille par package.

### 8.2 Quelles classes mettre absolument ?

Pour la partie transactionnelle, il faut absolument montrer :

- `Role`
- `User`
- `Client`
- `Category`
- `Produit`
- `Facture`
- `FactureDetail`
- `MouvementStock`
- `ActiviteUtilisateur`
- `Parametre`

Pour la partie IA, tu ajoutes ensuite :

- `ParametreIa`
- `LogIa`
- `ScorePerformance`
- `NotificationIa`
- `HistoriqueRapportIa`

### 8.3 Quel type de cardinalite dire a l oral ?

Exemple simple :

- "Un client peut avoir plusieurs factures, mais une facture appartient a un seul client."
- "Une facture contient plusieurs lignes, mais chaque ligne appartient a une seule facture."
- "Un produit appartient a une categorie, et une categorie peut contenir plusieurs produits."

## 9. Tables les plus importantes pour la soutenance

### 9.1 Noyau metier

- `utilisateurs`
- `roles`
- `clients`
- `categories`
- `produits`
- `factures`
- `details_facture`
- `mouvements_stock`
- `parametres`
- `activites_utilisateurs`

### 9.2 Extension IA

- `parametres_ia`
- `journaux_ia`
- `scores_performance`
- `notifications_ia`
- `historiques_rapports_ia`

## 10. Resume oral simple

Tu peux presenter la base comme ceci :

"La base de donnees de DEV IA est construite autour des utilisateurs, des roles, des clients, des produits, des factures et des mouvements de stock. La table `factures` contient l entete de la facture, tandis que `details_facture` contient les lignes vendues. Les produits sont classes dans `categories`, et chaque mouvement de stock est historise dans `mouvements_stock`. En plus de la partie transactionnelle, j ai ajoute un sous-systeme IA avec `journaux_ia`, `scores_performance`, `notifications_ia` et `historiques_rapports_ia` afin de conserver les analyses, les alertes et les rapports generes."

## 11. Resume tres court pour dessiner

Si tu veux aller vite pour dessiner ton diagramme de classes, pars de ceci :

- `Role` 1 -> * `User`
- `Category` 1 -> * `Produit`
- `Client` 1 -> * `Facture`
- `User` 1 -> * `Facture`
- `Facture` 1 -> * `FactureDetail`
- `Produit` 1 -> * `FactureDetail`
- `Produit` 1 -> * `MouvementStock`
- `User` 1 -> * `MouvementStock`
- `User` 1 -> * `ActiviteUtilisateur`
- `User` 1 -> * `LogIa`
- `LogIa` 1 -> * `ScorePerformance`
- `LogIa` 1 -> * `NotificationIa`
- `LogIa` 1 -> * `HistoriqueRapportIa`
- `User` 1 -> * `HistoriqueRapportIa`

## 12. Comment modeliser les classes une par une

Cette section t explique comment dessiner les classes dans ton diagramme UML.

Le principe est simple :

- une table importante devient une classe ;
- les colonnes importantes deviennent des attributs ;
- les cles etrangeres deviennent des a ssociations ;
- les cardinalites montrent combien d objets peuvent etre lies.

Tu n es pas oblige de mettre toutes les colonnes techniques comme `created_at` et `updated_at` dans le diagramme de classes du memoire.
Tu peux garder seulement les attributs utiles pour comprendre le metier.

### 12.1 Comment modeliser la classe `Role`

Tu vas dessiner une classe `Role`.

Dans cette classe, tu peux mettre :

- `id : int`
- `nom : string`
- `libelle : string`
- `description : string`

Pourquoi cette classe existe :
Parce qu elle represente les profils d acces du systeme.

Comment la relier :

- un `Role` est relie a `User`
- cardinalite : `Role 1 ---- 0..* User`

Ce que tu peux dire :
"J ai modelise la classe Role pour representer les profils d acces. Un role peut etre attribue a plusieurs utilisateurs."

### 12.2 Comment modeliser la classe `User`

Tu vas dessiner une classe `User`.

Attributs conseilles :

- `id : int`
- `nom : string`
- `adresse_email : string`
- `code_connexion : string`
- `telephone : string`
- `mot_de_passe : string`
- `est_actif : boolean`
- `dernier_login_a : datetime`

Tu peux aussi ajouter :

- `chemin_photo_profil : string`

Relations a dessiner :

- `User` appartient a `Role`
- `User` cree des `Facture`
- `User` enregistre des `MouvementStock`
- `User` genere des `ActiviteUtilisateur`
- `User` peut lancer des `LogIa`
- `User` peut etre lie a des `HistoriqueRapportIa`

Cardinalites utiles :

- `Role 1 ---- 0..* User`
- `User 1 ---- 0..* Facture`
- `User 1 ---- 0..* MouvementStock`
- `User 1 ---- 0..* ActiviteUtilisateur`
- `User 1 ---- 0..* LogIa`
- `User 1 ---- 0..* HistoriqueRapportIa`

Ce que tu peux dire :
"J ai modelise User comme acteur principal de l application. Cette classe est reliee a plusieurs modules : facturation, stock, audit et analyse IA."

### 12.3 Comment modeliser la classe `Category`

Tu vas dessiner une classe `Category`.

Attributs conseilles :

- `id : int`
- `nom : string`
- `description : string`
- `est_active : boolean`

Relation :

- `Category 1 ---- 0..* Produit`

Ce que tu peux dire :
"La classe Category permet d organiser les produits en familles. Une categorie peut contenir plusieurs produits."

### 12.4 Comment modeliser la classe `Client`

Tu vas dessiner une classe `Client`.

Attributs conseilles :

- `id : int`
- `nom : string`
- `postnom : string`
- `email : string`
- `telephone : string`
- `adresse : string`
- `entreprise : string`

Relation principale :

- `Client 1 ---- 0..* Facture`

Ce que tu peux dire :
"J ai modelise la classe Client pour representer les personnes ou entreprises facturees. Un client peut recevoir plusieurs factures."

### 12.5 Comment modeliser la classe `Produit`

Tu vas dessiner une classe `Produit`.

Attributs conseilles :

- `id : int`
- `nom : string`
- `reference : string`
- `prix_achat : decimal`
- `prix_vente : decimal`
- `stock : int`
- `stock_minimum : int`
- `description : string`
- `est_actif : boolean`

Tu peux ajouter si tu veux :

- `chemin_photo : string`

Relations a dessiner :

- `Produit` appartient a `Category`
- `Produit` apparait dans `FactureDetail`
- `Produit` possede plusieurs `MouvementStock`

Cardinalites :

- `Category 1 ---- 0..* Produit`
- `Produit 1 ---- 0..* FactureDetail`
- `Produit 1 ---- 0..* MouvementStock`

Ce que tu peux dire :
"La classe Produit est centrale dans l application, car elle intervient dans la vente, la facturation et la gestion de stock."

### 12.6 Comment modeliser la classe `Facture`

Tu vas dessiner une classe `Facture`.

Attributs conseilles :

- `id : int`
- `numero_facture : string`
- `date_facture : date`
- `date_echeance : date`
- `total_ht : decimal`
- `taux_tva : decimal`
- `montant_tva : decimal`
- `montant_remise : decimal`
- `total_ttc : decimal`
- `statut : string`
- `notes : string`

Relations a dessiner :

- `Facture` appartient a `Client`
- `Facture` est creee par `User`
- `Facture` contient plusieurs `FactureDetail`

Cardinalites :

- `Client 1 ---- 0..* Facture`
- `User 1 ---- 0..* Facture`
- `Facture 1 ---- 1..* FactureDetail`

Ce que tu peux dire :
"La classe Facture represente l entete de la vente. Elle contient les informations generales et se relie aux lignes detaillees de la table details_facture."

### 12.7 Comment modeliser la classe `FactureDetail`

Tu vas dessiner une classe `FactureDetail`.

Attributs conseilles :

- `id : int`
- `description : string`
- `quantite : int`
- `prix_unitaire_ht : decimal`
- `total_ligne_ht : decimal`

Relations :

- `FactureDetail` appartient a `Facture`
- `FactureDetail` appartient a `Produit`

Cardinalites :

- `Facture 1 ---- 1..* FactureDetail`
- `Produit 1 ---- 0..* FactureDetail`

Ce que tu peux dire :
"J ai modele FactureDetail comme une classe separee parce qu une facture contient plusieurs lignes, et chaque ligne correspond a un produit."

### 12.8 Comment modeliser la classe `MouvementStock`

Tu vas dessiner une classe `MouvementStock`.

Attributs conseilles :

- `id : int`
- `type_mouvement : string`
- `quantite : int`
- `stock_avant : int`
- `stock_apres : int`
- `motif : string`
- `date_mouvement : date`

Relations :

- `MouvementStock` appartient a `Produit`
- `MouvementStock` appartient a `User`

Cardinalites :

- `Produit 1 ---- 0..* MouvementStock`
- `User 1 ---- 0..* MouvementStock`

Ce que tu peux dire :
"Cette classe sert a tracer toutes les operations de stock. Elle permet de savoir qui a fait le mouvement, sur quel produit et avec quel impact."

### 12.9 Comment modeliser la classe `ActiviteUtilisateur`

Tu vas dessiner une classe `ActiviteUtilisateur`.

Attributs conseilles :

- `id : int`
- `action : string`
- `type_cible : string`
- `cible_id : int`
- `metadonnees : text`
- `adresse_ip : string`
- `agent_utilisateur : string`

Relation :

- `ActiviteUtilisateur` appartient a `User`

Cardinalite :

- `User 1 ---- 0..* ActiviteUtilisateur`

Ce que tu peux dire :
"Cette classe permet l audit fonctionnel. Elle garde la trace des actions realisees dans l application."

### 12.10 Comment modeliser la classe `Parametre`

Tu vas dessiner une classe `Parametre`.

Tu ne mets pas forcement toutes les colonnes, sinon la classe devient trop chargee.

Tu peux garder les attributs les plus utiles :

- `id : int`
- `nom_entreprise : string`
- `email_entreprise : string`
- `telephone_entreprise : string`
- `adresse_entreprise : string`
- `devise : string`
- `taux_tva_defaut : decimal`
- `pourcentage_remise_defaut : decimal`
- `format_numero_facture : string`
- `delai_echeance_facture_jours : int`
- `seuil_stock_minimum_global : int`
- `nom_banque : string`
- `numero_compte_bancaire : string`
- `conditions_facture : text`
- `couleur_principale_facture : string`
- `couleur_secondaire_facture : string`
- `titre_facture : string`

Tu peux ajouter dans une version plus detaillee :

- `libelle_emetteur_facture : string`
- `libelle_facturer_a : string`
- `libelle_envoyer_a : string`
- `libelle_paiement_facture : string`
- `libelle_signature_facture : string`
- `libelle_banque_pied_facture : string`
- `libelle_compte_pied_facture : string`
- `libelle_contact_pied_facture : string`
- `libelle_description_facture : string`
- `libelle_quantite_facture : string`
- `libelle_prix_unitaire_facture : string`
- `libelle_montant_facture : string`
- `afficher_signature_facture : boolean`
- `note_pied_facture : string`

Pourquoi :
Cette classe represente la configuration generale de l application.

Ce que tu peux dire :
"Je l ai modelisee comme une classe de parametrage global, car elle centralise les regles appliquees aux factures et a l application."

### 12.11 Comment modeliser les classes IA

Pour la partie IA, tu peux faire un package separe.

#### `ParametreIa`

Attributs utiles :

- `seuil_marge_minimale`
- `seuil_stock_critique`
- `niveau_sensibilite`
- `modele_openrouter`
- `duree_cache_minutes`

Ce que tu peux dire :
"Cette classe configure le comportement du module d analyse IA."

#### `LogIa`

Attributs utiles :

- `id`
- `type_journal`
- `statut`
- `charge_entree`
- `charge_sortie`
- `message_erreur`
- `genere_le`

Relations :

- `User 1 ---- 0..* LogIa`
- `LogIa 1 ---- 0..* ScorePerformance`
- `LogIa 1 ---- 0..* NotificationIa`
- `LogIa 1 ---- 0..* HistoriqueRapportIa`

#### `ScorePerformance`

Attributs utiles :

- `id`
- `score_global`
- `detail_score`
- `explication_score`

Relation :

- `ScorePerformance` appartient a `LogIa`

#### `NotificationIa`

Attributs utiles :

- `id`
- `titre`
- `message`
- `niveau_gravite`
- `est_lue`

Relation :

- `NotificationIa` appartient a `LogIa`

#### `HistoriqueRapportIa`

Attributs utiles :

- `id`
- `titre_rapport`
- `genere_le`

Relations :

- `HistoriqueRapportIa` appartient a `LogIa`
- `HistoriqueRapportIa` peut etre associe a `User`

## 13. Exemple de phrase de defense

Si on te demande comment tu as modelise, tu peux repondre comme ceci :

"Pour construire le diagramme de classes, je suis parti des tables principales de la base de donnees. Chaque table metier importante est devenue une classe UML. Ensuite, j ai transforme les cles etrangeres en associations entre classes. Par exemple, la cle `factures.client_id` m a permis de relier la classe Facture a la classe Client avec une cardinalite un-a-plusieurs. J ai ensuite organise le diagramme par packages afin de separer l authentification, la gestion commerciale, le stock, le parametrage et le module IA."

## 14. Conseils finaux pour ton dessin

Pour que ton diagramme soit propre :

- ne mets pas toutes les colonnes techniques ;
- garde seulement les attributs metier ;
- separe les classes en packages ;
- montre bien les cardinalites ;
- fais ressortir les classes centrales :
  - `User`
  - `Produit`
  - `Facture`
  - `FactureDetail`
  - `Client`

Si tu veux un diagramme plus simple, commence par le noyau metier :

- `Role`
- `User`
- `Client`
- `Category`
- `Produit`
- `Facture`
- `FactureDetail`
- `MouvementStock`

Puis ajoute l IA a part.
# Diagramme de cas d utilisation

Cette application peut etre representee dans un diagramme de cas d utilisation avec trois acteurs principaux :

- `Administrateur`
- `Employe`
- `Client` (optionnel, seulement si l on veut montrer la reception de facture)

## Acteur : Administrateur

L administrateur est l acteur principal du systeme. Il dispose d un acces complet aux modules de gestion et au module IA.

Cas d utilisation principaux :

- `Se connecter`
- `Gerer son profil`
- `Gerer les utilisateurs`
- `Attribuer un role`
- `Attribuer un code de connexion`
- `Gerer les categories`
- `Gerer les produits`
- `Gerer les clients`
- `Gerer le stock`
- `Consulter les mouvements de stock`
- `Creer une facture`
- `Voir les factures`
- `Telecharger une facture PDF`
- `Envoyer une facture par email`
- `Supprimer une facture`
- `Gerer les parametres`
- `Lancer une analyse locale`
- `Lancer une analyse avec IA`
- `Consulter les notifications IA`
- `Poser une question a l IA`
- `Telecharger le rapport IA`

## Acteur : Employe

L employe a un acces limite aux operations de vente, de facturation et de consultation.

Cas d utilisation principaux :

- `Se connecter`
- `Gerer son profil`
- `Voir les produits`
- `Voir le stock disponible`
- `Creer une facture`
- `Voir ses factures`
- `Telecharger une facture PDF`
- `Envoyer une facture par email`

## Acteur : Client

Le client peut etre ajoute comme acteur secondaire si l on veut montrer la reception du document facture.

Cas d utilisation possibles :

- `Recevoir la facture par email`
- `Consulter la facture PDF`

## Relations entre cas d utilisation

Voici les relations principales a montrer dans le diagramme :

- `Administrateur` est relie a tous les cas d utilisation de gestion et d analyse.
- `Employe` est relie seulement aux cas d utilisation operationnels.
- `Client` est relie a la reception ou a la consultation de la facture.

## Relations `include`

Les relations `include` servent a montrer qu un cas d utilisation appelle obligatoirement un autre.

- `Creer une facture` `<<include>>` `Selectionner un client`
- `Creer une facture` `<<include>>` `Calculer les montants`
- `Creer une facture` `<<include>>` `Generer la facture PDF`
- `Envoyer une facture par email` `<<include>>` `Generer la facture PDF`
- `Lancer une analyse avec IA` `<<include>>` `Generer les graphiques`
- `Lancer une analyse avec IA` `<<include>>` `Produire un resume`
- `Lancer une analyse avec IA` `<<include>>` `Generer des alertes`
- `Telecharger le rapport IA` `<<include>>` `Produire un resume`

## Relations `extend`

Les relations `extend` servent a montrer qu une fonctionnalite est optionnelle ou qu elle vient enrichir une autre.

- `Ajouter un client` `<<extend>>` `Creer une facture`
- `Telecharger une facture PDF` `<<extend>>` `Voir les factures`
- `Envoyer une facture par email` `<<extend>>` `Voir les factures`
- `Supprimer une facture` `<<extend>>` `Voir les factures`
- `Poser une question a l IA` `<<extend>>` `Lancer une analyse avec IA`
- `Consulter les notifications IA` `<<extend>>` `Lancer une analyse avec IA`

## Explication simple pour la soutenance

Pour presenter ce diagramme, on peut dire :

`Le diagramme de cas d utilisation montre les interactions entre les acteurs du systeme et les grandes fonctionnalites de l application. L administrateur dispose d un acces complet a la gestion et au module IA. L employe a un acces limite a la vente, a la facturation, au stock disponible et a son profil. Le client peut etre represente comme acteur secondaire pour la reception de la facture. Les relations include montrent les traitements obligatoires, tandis que les relations extend montrent les actions optionnelles ou complementaires.`

# Rapport de base de donnees
