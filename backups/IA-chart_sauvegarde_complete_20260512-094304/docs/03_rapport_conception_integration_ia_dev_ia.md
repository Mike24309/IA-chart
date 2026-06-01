# Rapport 3 - Conception De L Application Et Integration De L IA

## 1. Objet du rapport

Ce rapport explique :

- comment l application a ete concue ;
- comment les utilisateurs se connectent ;
- comment les factures sont creees ;
- comment les PDF sont generes ;
- comment l IA est integree ;
- comment Gemini recupere indirectement les informations.

## 2. Idee generale de l application

L application `DEV IA` a ete concue comme une application de :

- gestion des utilisateurs ;
- gestion des produits ;
- gestion des clients ;
- gestion des factures ;
- gestion du stock ;
- supervision et analyse avec intelligence artificielle.

Le principe etait de construire d abord une base transactionnelle simple et stable :

- utilisateurs ;
- produits ;
- stock ;
- factures ;
- clients.

Ensuite, l IA a ete ajoutee comme un module d analyse et d explication, sans remplacer la logique metier principale.

## 3. Architecture fonctionnelle

Le projet est organise en plusieurs blocs :

### 3.1 Bloc authentification

Il gere :

- la connexion par email ou code de connexion ;
- la creation du premier administrateur ;
- la deconnexion ;
- le controle du role ;
- la verification que le compte est actif.

### 3.2 Bloc commercial

Il gere :

- les clients ;
- les produits ;
- les categories ;
- les factures ;
- les lignes de facture.

### 3.3 Bloc stock

Il gere :

- le stock courant du produit ;
- les entrees ;
- les sorties ;
- les ajustements ;
- l historique des mouvements.

### 3.4 Bloc parametrage

Il gere :

- les informations de l entreprise ;
- la TVA par defaut ;
- la remise par defaut ;
- le format du numero de facture ;
- les couleurs de facture ;
- les mentions de facture ;
- les informations bancaires.

### 3.5 Bloc IA

Il gere :

- les analyses locales ;
- les analyses avec Gemini ;
- les notifications IA ;
- les questions a l assistant ;
- les rapports PDF IA.

## 4. Comment la connexion des utilisateurs fonctionne

La connexion est geree par `AuthController`.

### 4.1 Affichage du formulaire

Quand l utilisateur arrive sur la page :

- Laravel appelle `AuthController@showLogin`
- la vue `resources/views/auth/login.blade.php` est affichee

### 4.2 Saisie de l identifiant

L utilisateur peut se connecter avec :

- `adresse_email`
- ou `code_connexion`

Le systeme detecte automatiquement ce qui a ete saisi.

### 4.3 Verification

Le controleur :

- cherche l utilisateur ;
- verifie le mot de passe chiffre avec `Hash::check` ;
- recharge le role ;
- ouvre la session Laravel ;
- enregistre la derniere connexion ;
- journalise l action dans `activites_utilisateurs`.

### 4.4 Redirection

Apres connexion :

- l utilisateur arrive sur `/dashboard`
- le dashboard affiche une vue adaptee au role.

## 5. Comment les utilisateurs sont differencies

Le systeme utilise la table `roles` et la colonne `utilisateurs.role_id`.

Le modele `User` possede la methode :

- `isAdministrator()`

Cette methode sert a :

- afficher l interface admin ;
- afficher l interface employe ;
- proteger les routes ;
- montrer ou cacher le module IA.

## 6. Comment la facture est concue

La facture est construite en deux niveaux.

### 6.1 Entete de facture

Table :
- `factures`

Elle contient :

- le numero ;
- le client ;
- l utilisateur ;
- la date ;
- la TVA ;
- la remise ;
- le total ;
- le statut.

### 6.2 Lignes de facture

Table :
- `details_facture`

Elle contient :

- le produit ;
- la quantite ;
- le prix unitaire ;
- le total de ligne.

### 6.3 Pourquoi cette separation

Une facture peut contenir plusieurs articles.

Si on mettait tout dans une seule table :

- on repeterait les informations ;
- la base serait moins propre ;
- la modelisation serait mauvaise.

Donc :

- `factures` = entete ;
- `details_facture` = lignes.

## 7. Comment la creation d une facture fonctionne

La logique principale passe par :

- `InvoiceController`
- `InvoiceService`
- `StockService`

### 7.1 Etapes

1. l utilisateur ouvre `Créer une facture`
2. il choisit ou cree un client
3. il ajoute un ou plusieurs produits
4. Laravel valide les donnees
5. `InvoiceService` cree la facture
6. les lignes de facture sont enregistrees
7. `StockService` diminue le stock
8. la facture est redirigee vers l affichage, l envoi ou le telechargement selon le bouton clique

### 7.2 Calculs automatiques

La facture utilise :

- la `TVA` definie dans `parametres`
- la `remise par defaut` definie dans `parametres`

Donc l utilisateur ne definit plus manuellement la TVA et la remise a chaque facture.

## 8. Comment le PDF de facture est genere

Le PDF de facture passe par `DomPDF`.

### 8.1 Circuit

1. `InvoiceController@pdf` ou `InvoiceController@store`
2. appel de `Pdf::loadView(...)`
3. Laravel charge la vue :
   - `resources/views/invoices/pdf.blade.php`
4. DomPDF transforme le HTML en PDF
5. le PDF est telecharge ou envoye par email

### 8.2 Pourquoi Blade + DomPDF

Cette approche permet :

- d utiliser le meme esprit de design que la vue normale ;
- de personnaliser les couleurs ;
- d utiliser le logo de l entreprise ;
- d afficher les conditions, la banque, la signature et les totaux.

## 9. Comment le PDF IA est genere

Le PDF IA utilise aussi `DomPDF`, mais avec un autre circuit.

### 9.1 Circuit

1. l administrateur lance une vraie analyse IA
2. l application recupere la reponse Gemini
3. la reponse est structuree en resume, details, alertes, recommandations et conclusion
4. `AiController@report` appelle `AiAuditService@buildReportContext`
5. Laravel charge :
   - `resources/views/ai/report-pdf.blade.php`
6. DomPDF genere le fichier telechargeable

### 9.2 Condition importante

Le PDF IA est volontairement strict :

- il ne se telecharge que si une vraie analyse IA existe ;
- il ne se telecharge pas apres une simple analyse locale ;
- il ne se telecharge pas si Gemini a renvoye une reponse incomplete.

## 10. Comment l IA est integree

Le module IA est concu comme un service d analyse.

Les fichiers principaux sont :

- `AiController.php`
- `AiAuditService.php`
- `AiSettingsService.php`
- `DashboardController.php`

### 10.1 Rôle du controleur IA

`AiController` gere :

- le lancement de l analyse ;
- les questions du chat ;
- le telechargement du rapport IA ;
- la lecture des notifications.

### 10.2 Rôle du service IA

`AiAuditService` gere :

- la collecte des donnees metier ;
- la construction du prompt ;
- l appel HTTP vers Gemini ;
- le nettoyage de la reponse ;
- le stockage des resultats ;
- la creation des alertes ;
- la creation des rapports.

### 10.3 Rôle des parametres IA

`AiSettingsService` fournit :

- le modele Gemini ;
- les seuils de stock ;
- la sensibilite ;
- la duree de cache ;
- les options du module IA.

## 11. Comment l IA recupere les informations

L IA ne se connecte pas directement a MySQL.

Le fonctionnement est le suivant :

1. l application lit la base de donnees ;
2. elle construit un `snapshot` metier ;
3. ce snapshot est transforme en prompt ;
4. le prompt est envoye a Gemini ;
5. Gemini repond en texte ou en JSON structure ;
6. l application interprete la reponse et l affiche.

Autrement dit :

- `Gemini ne vient pas lire la base tout seul`
- `c est l application qui lui envoie un resume structure des donnees`

## 12. Quelles donnees sont envoyees a l IA

Le service IA prepare notamment :

- le chiffre d affaires ;
- les factures validees ;
- les annulations ;
- les produits critiques ;
- les mouvements de stock ;
- l activite des utilisateurs ;
- les ventes par produit ;
- les ventes par utilisateur ;
- les marges ;
- les tendances mensuelles.

Ces donnees sont assemblees dans `AiAuditService`.

## 13. Comment Gemini est connecte

Le projet utilise maintenant `Gemini API`.

### 13.1 Donnees techniques

- la cle API est stockee dans `.env`
- le modele est stocke dans les parametres IA
- l appel est fait en HTTP par Laravel

### 13.2 URL et appel

Le service envoie une requete vers l endpoint Gemini de type :

- `generateContent`

Le prompt contient :

- le contexte metier ;
- les donnees condensees ;
- le format de sortie attendu.

### 13.3 Reponse attendue

Pour l analyse :

- resume executif
- analyse detaillee
- anomalies
- score global
- recommandations
- alertes
- resume de telechargement

Pour le chat :

- `answer`
- `short_summary`

## 14. Comment l IA interagit avec le dashboard

L interface admin montre deux niveaux :

### 14.1 Analyse locale

Elle :

- remplit les graphiques ;
- ne produit pas un vrai rapport IA ;
- ne donne pas un PDF IA telechargeable.

### 14.2 Analyse avec IA

Elle :

- interroge Gemini ;
- remplit les graphiques ;
- produit un resume ;
- cree des alertes ;
- permet le PDF IA.

## 15. Comment les resultats IA sont stockes

Les resultats sont memorises dans :

- `journaux_ia`
- `scores_performance`
- `notifications_ia`
- `historiques_rapports_ia`

### 15.1 `journaux_ia`

Elle stocke :

- le type ;
- l entree ;
- la sortie ;
- la date ;
- l utilisateur.

### 15.2 `scores_performance`

Elle stocke :

- la note globale ;
- le detail du score ;
- l explication.

### 15.3 `notifications_ia`

Elle stocke :

- les alertes ;
- la gravite ;
- l etat lu ou non lu.

### 15.4 `historiques_rapports_ia`

Elle stocke :

- le titre du rapport ;
- l analyse source ;
- l utilisateur qui l a genere.

## 16. Point important sur les noms anglais et francais

La base est maintenant en francais :

- `utilisateurs`
- `produits`
- `factures`
- `mouvements_stock`

Mais une partie du code garde encore des noms anglais historiques comme :

- `name`
- `email`
- `invoice_number`
- `sale_price`

Le projet gere cela avec un systeme de mappage dans :

- `app/Models/Concerns/MappeAnciensAttributs.php`

Donc :

- le code peut encore utiliser certains anciens noms ;
- mais la base reelle stocke les colonnes physiques en francais.

## 17. Ce que tu peux dire a la defense

Tu peux dire :

`J ai concu l application comme une solution de gestion commerciale et de stock avec une architecture MVC Laravel. La partie transactionnelle gere les utilisateurs, les produits, les clients, les factures et les mouvements de stock. Ensuite, j ai integre un module IA base sur Gemini. L application prepare elle-meme les donnees metier, les envoie a l IA sous forme de prompt structure, puis recupere une analyse exploitable pour le dashboard, les alertes, les questions-reponses et le rapport PDF. L IA n accede donc pas directement a la base : elle travaille sur des donnees que l application lui transmet de facon controlee.`
