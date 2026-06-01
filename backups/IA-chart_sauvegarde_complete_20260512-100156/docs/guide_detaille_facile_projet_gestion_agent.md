# Guide detaille et facile a comprendre

## Projet : Gestion Agent

Ce document est fait pour t aider a :

- comprendre facilement ton projet ;
- retrouver rapidement les fichiers ;
- savoir a quoi sert chaque dossier ;
- expliquer ton application avec des mots simples ;
- repondre plus facilement aux questions pendant le memoire.

Ce n est pas un document technique complique.  
C est un document de travail et de revision.

---

## 1. Comment comprendre le projet dans son ensemble

Ton application est une application web de gestion.

En pratique, elle permet de faire 5 grandes choses :

1. gerer les utilisateurs ;
2. gerer les produits et le stock ;
3. gerer les clients ;
4. gerer les factures ;
5. analyser les donnees avec l IA.

Quand un utilisateur clique sur une page, Laravel suit en general ce chemin :

1. la route recoit la demande ;
2. le controleur traite la demande ;
3. le service fait la logique importante ;
4. le modele parle avec la base de donnees ;
5. la vue affiche le resultat.

Donc si tu veux chercher un code, tu peux te poser cette question :

Est-ce que je cherche :

- une page ?
- un traitement ?
- une regle de calcul ?
- une table ?
- un affichage ?

Selon la reponse, tu iras dans un dossier different.

---

## 2. Le dossier `app` : le coeur du projet

Le dossier `app` est le dossier le plus important.

On peut dire simplement :

`app` = le cerveau de l application

Dedans, il y a plusieurs sous-dossiers utiles.

---

## 3. Le dossier `app/Http`

Ce dossier gere tout ce qui se passe quand quelqu un utilise l application dans le navigateur.

Il contient surtout :

- les `Controllers`
- les `Middleware`

### Pourquoi ce dossier est important ?

Parce que c est souvent la premiere porte d entree de la logique.

Quand tu cliques sur :

- connexion ;
- dashboard ;
- facture ;
- produits ;
- profil ;
- IA ;

en general tu passes par un fichier qui se trouve dans `app/Http/Controllers`.

---


## 4. Le dossier `app/Http/Controllers`

### Idee simple

Un controleur est un fichier qui recoit la demande de l utilisateur et decide quoi faire.

On peut dire :

le controleur sert a organiser les actions de l application.

Par exemple :

- ouvrir une page ;
- enregistrer un formulaire ;
- afficher une liste ;
- lancer une analyse ;
- telecharger un PDF.

Je vais maintenant t expliquer chaque controleur avec des mots tres simples.

---

### `AuthController.php`

Ce fichier gere la connexion.

En clair, ce fichier sert a :

- afficher la page de connexion ;
- verifier les identifiants ;
- connecter l utilisateur ;
- creer le tout premier administrateur ;
- deconnecter l utilisateur.

Quand tu veux comprendre :

- pourquoi un utilisateur arrive ou non a se connecter ;
- comment le premier compte admin est cree ;
- comment fonctionne le code de connexion ;

tu ouvres ce fichier.

### Ce qu il faut retenir pour l oral

`AuthController` gere la securite d entree dans l application.

---

### `DashboardController.php`

Ce fichier gere le tableau de bord.

Il sert a calculer ce qu on doit afficher sur la page principale.

Exemples :

- chiffre d affaires ;
- nombre de factures ;
- produits en stock critique ;
- utilisateurs actifs ;
- graphiques ;
- liste des dernieres factures.

Il ne stocke pas les donnees lui-meme.  
Il va chercher les informations dans les modeles, les calcule, puis les envoie a la vue.

### Ce qu il faut retenir pour l oral

`DashboardController` sert a preparer la vue de pilotage de l application.

---

### `InvoiceController.php`

C est un des fichiers les plus importants.

Il gere tout ce qui concerne les factures.

Il sert a :

- afficher la liste des factures ;
- filtrer les factures ;
- ouvrir la page de creation ;
- enregistrer une nouvelle facture ;
- afficher le detail d une facture ;
- generer le PDF ;
- envoyer la facture par email ;
- supprimer une facture si on est admin.

### Ce qu il faut retenir pour l oral

`InvoiceController` sert a piloter le cycle de vie d une facture.

### Si tu veux modifier quoi ?

- la liste des factures : ouvre ce fichier et `resources/views/invoices/index.blade.php`
- l affichage detaille : ouvre ce fichier et `resources/views/invoices/show.blade.php`
- le PDF : ouvre ce fichier et `resources/views/invoices/pdf.blade.php`

---

### `ProductController.php`

Ce fichier gere les produits.

Il sert a :

- afficher les produits ;
- ajouter un produit ;
- modifier un produit ;
- supprimer un produit ;
- afficher le stock disponible.

### Ce qu il faut retenir

`ProductController` gere les articles vendus dans l application.

---

### `CategoryController.php`

Ce fichier gere les categories.

Par exemple :

- informatique ;
- bureautique ;
- accessoires.

Il sert a :

- creer une categorie ;
- modifier une categorie ;
- supprimer une categorie ;
- afficher la liste.

### Ce qu il faut retenir

Ce fichier sert a classer les produits.

---

### `ClientController.php`

Ce fichier gere les clients.

Il sert a :

- ajouter un client ;
- modifier un client ;
- supprimer un client ;
- voir les clients existants.

### Ce qu il faut retenir

Ce fichier sert a garder les informations des personnes ou entreprises a qui on vend.

---

### `StockMovementController.php`

Ce fichier gere les mouvements de stock manuels.

Il sert a :

- enregistrer une entree ;
- enregistrer une sortie ;
- voir l historique des mouvements.

### Ce qu il faut retenir

Ce controleur n est pas le seul a toucher au stock.  
Le stock bouge aussi quand une facture est creee.

---

### `SettingsController.php`

Ce fichier gere les parametres generaux.

Il sert a modifier :

- le nom de l entreprise ;
- l email ;
- le telephone ;
- l adresse ;
- le logo ;
- les couleurs de facture ;
- le fond de facture ;
- les informations bancaires ;
- les textes affiches sur la facture.

### Ce qu il faut retenir

Ce fichier sert a personnaliser l application sans modifier le code.

---

### `ProfileController.php`

Ce fichier gere le compte personnel de l utilisateur.

Il sert a :

- modifier le nom ;
- modifier l email ;
- modifier le mot de passe ;
- changer la photo de profil.

### Ce qu il faut retenir

Ce fichier sert a la gestion du profil personnel.

---

### `UserController.php`

Ce fichier sert surtout a l administrateur.

Il permet de :

- creer des utilisateurs ;
- attribuer un role ;
- donner un code de connexion ;
- activer ou desactiver le compte ;
- modifier les informations d un utilisateur ;
- supprimer un utilisateur.

### Ce qu il faut retenir

Ce fichier sert a l administration interne des comptes.

---

### `ActivityController.php`

Ce fichier sert a afficher le journal des activites.

Il permet de voir :

- qui a fait quoi ;
- a quel moment ;
- sur quelle partie du systeme.

### Ce qu il faut retenir

C est un fichier utile pour le suivi et l audit humain.

---

### `AiController.php`

Ce fichier pilote la partie IA.

Il sert a :

- ouvrir le module IA ;
- lancer une analyse ;
- poser une question a l assistant ;
- telecharger le rapport IA ;
- marquer les notifications comme lues.

### Ce qu il faut retenir

Ce fichier ne fait pas toute l intelligence lui-meme.  
Il appelle surtout `AiAuditService`.

---

### `AiSettingsController.php`

Ce fichier sert a regler l IA.

Il permet de modifier :

- le modele OpenRouter ;
- la sensibilite ;
- les seuils ;
- le cache ;
- certaines options d audit.

### Ce qu il faut retenir

Ce fichier sert a configurer le comportement du module IA.

---

## 5. Le dossier `app/Http/Middleware`

### Idee simple

Un middleware est un filtre.

Il verifie une condition avant de laisser passer l utilisateur.

Exemple tres simple :

- est-ce que la personne est connectee ?
- est-ce qu elle est admin ?
- est-ce que son compte est actif ?

### Fichiers importants

#### `RoleMiddleware.php`

Ce fichier verifie le role.

Il sert a dire :

- cette route est seulement pour admin ;
- cette route est pour admin et employe.

#### `EnsureUserIsActive.php`

Ce fichier verifie si le compte est actif.

S il est desactive, l utilisateur ne doit pas continuer.

#### `Authenticate.php`

Ce fichier verifie si l utilisateur est connecte.

#### `VerifyCsrfToken.php`

Ce fichier aide a proteger les formulaires contre les attaques CSRF.

### Ce qu il faut retenir pour l oral

Les middlewares servent a proteger les routes avant meme d executer la logique principale.

---

## 6. Le dossier `app/Models`

### Idee simple

Les modeles representent les tables de la base de donnees.

On peut dire :

un modele = une table

Ils servent a :

- lire les donnees ;
- enregistrer les donnees ;
- definir les relations entre les tables.

---

### `User.php`

Ce modele represente les utilisateurs.

Il contient des informations comme :

- le nom ;
- l email ;
- le mot de passe ;
- le role ;
- le code de connexion ;
- l etat actif ou non.

Il contient aussi les relations vers :

- les factures ;
- les activites ;
- les mouvements de stock.

### Ce qu il faut retenir

`User` represente une personne qui utilise l application.

---

### `Role.php`

Ce modele represente les roles.

Exemples :

- admin ;
- employee.

### Ce qu il faut retenir

`Role` permet de savoir quels droits un utilisateur possede.

---

### `Client.php`

Ce modele represente les clients.

Il contient :

- le nom ;
- l email ;
- le telephone ;
- l adresse ;
- l entreprise.

Il est lie aux factures.

### Ce qu il faut retenir

`Client` represente la personne ou l entreprise a qui on vend.

---

### `Produit.php`

Ce modele represente les produits.

Il contient :

- le nom ;
- la reference ;
- le prix d achat ;
- le prix de vente ;
- le stock theorique ;
- le stock physique ;
- le stock minimum ;
- la categorie.

### Ce qu il faut retenir

`Produit` represente ce qui est vendu et suivi dans le stock.

---

### `Category.php`

Ce modele represente une categorie de produits.

### Ce qu il faut retenir

Il permet de ranger les produits par famille.

---

### `Facture.php`

Ce modele represente l entete d une facture.

Il contient :

- le numero ;
- le client ;
- l utilisateur createur ;
- la date ;
- l echeance ;
- les montants ;
- le statut.

### Ce qu il faut retenir

`Facture` represente le document principal de vente.

---

### `FactureDetail.php`

Ce modele represente les lignes de facture.

Chaque ligne contient :

- le produit ;
- la quantite ;
- le prix unitaire ;
- le total de la ligne.

### Ce qu il faut retenir

Une facture a plusieurs lignes, donc il fallait une table separee.

---

### `MouvementStock.php`

Ce modele represente un mouvement de stock.

Exemples :

- entree ;
- sortie ;
- ajustement.

### Ce qu il faut retenir

Chaque changement de stock laisse une trace dans cette table.

---

### `ActiviteUtilisateur.php`

Ce modele represente une action faite dans l application.

Exemples :

- connexion ;
- creation facture ;
- suppression utilisateur ;
- modification produit.

### Ce qu il faut retenir

Il sert de journal d activite.

---

### `Parametre.php`

Ce modele represente les parametres generaux.

Il contient :

- le nom de l entreprise ;
- les infos de contact ;
- les couleurs ;
- les textes de facture ;
- les infos bancaires.

### Ce qu il faut retenir

Ce modele sert a personnaliser l application.

---

### Les modeles IA

#### `ParametreIa.php`

Ce modele garde les regles et options du module IA.

#### `LogIa.php`

Ce modele garde les analyses et conversations IA.

#### `ScorePerformance.php`

Ce modele garde la note globale calculee par l IA.

#### `NotificationIa.php`

Ce modele garde les alertes de l IA.

#### `HistoriqueRapportIa.php`

Ce modele garde la trace des rapports IA generes.

### Ce qu il faut retenir

Ces modeles montrent que l IA a son propre espace de travail dans la base, sans modifier les tables metier principales.

---

## 7. Le dossier `app/Services`

### Idee simple

Les services contiennent la vraie logique metier.

Pourquoi c est important ?

Parce que si on mettait tout dans les controleurs, le projet deviendrait vite desordonne.

Donc on peut dire :

- le controleur organise ;
- le service travaille.

---

### `InvoiceService.php`

Ce service sert a creer correctement une facture.

Il gere :

- la creation du numero ;
- le calcul du total HT ;
- la remise ;
- la TVA ;
- le total TTC ;
- la creation des lignes ;
- l appel au service de stock.

### Ce qu il faut retenir

`InvoiceService` est le coeur metier de la facturation.

---

### `StockService.php`

Ce service sert a centraliser tous les changements de stock.

Il fait :

- la mise a jour du stock theorique ;
- la mise a jour du stock physique ;
- la verification que le stock ne devient pas negatif ;
- la creation d un mouvement dans l historique.

### Ce qu il faut retenir

Grace a ce service, le stock reste coherent dans toute l application.

---

### `ParameterService.php`

Ce service sert a charger les parametres generaux.

Il sert aussi a preparer la configuration mail.

### Ce qu il faut retenir

Il evite de dupliquer partout le code qui charge les informations de l entreprise.

---

### `ActivityService.php`

Ce service sert a enregistrer les actions importantes.

### Ce qu il faut retenir

Il simplifie la creation du journal d activite.

---

### `AiSettingsService.php`

Ce service sert a charger ou initialiser les parametres de l IA.

### Ce qu il faut retenir

Il centralise la configuration du module IA.

---

### `AiAuditService.php`

C est le fichier le plus important pour l IA.

Il fait beaucoup de choses :

1. il lit les donnees dans la base ;
2. il calcule les indicateurs utiles ;
3. il construit un prompt ;
4. il envoie ce prompt a OpenRouter ;
5. il lit la reponse ;
6. il enregistre le resultat ;
7. il cree des alertes si besoin ;
8. il permet aussi de discuter avec l assistant.

### Ce qu il faut retenir

`AiAuditService` est le cerveau du module IA.

### Phrase simple pour l oral

Le service IA ne modifie pas le systeme. Il lit les donnees, les analyse et retourne un rapport.

---

## 8. Le dossier `resources/views`

### Idee simple

Ce dossier contient l affichage.

On peut dire :

`resources/views` = ce que l utilisateur voit

Tu vas ici si tu veux modifier :

- le design ;
- les formulaires ;
- la disposition des cartes ;
- la facture visible ;
- la page de connexion ;
- le dashboard.

---

### `resources/views/layouts`

Ce dossier contient la structure generale.

Le fichier principal est :

- `app.blade.php`

Il contient :

- la sidebar ;
- la topbar ;
- le bouton IA ;
- la structure globale des pages.

### Ce qu il faut retenir

Si tu veux modifier le cadre general de l application, tu ouvres ce fichier.

---

### `resources/views/auth`

Ce dossier contient la connexion.

Si tu veux modifier :

- le design login ;
- le formulaire de connexion ;
- le formulaire d inscription initiale ;

tu ouvres ce dossier.

---

### `resources/views/dashboard`

Ce dossier contient le dashboard.

Tu l ouvres si tu veux modifier :

- les cartes statistiques ;
- les graphiques ;
- la partie IA visible dans le dashboard ;
- la vue employe ;
- la vue administrateur.

---

### `resources/views/invoices`

C est un dossier tres important.

#### `index.blade.php`

Ce fichier affiche :

- la liste des factures ;
- les filtres ;
- les boutons voir, PDF, supprimer.

#### `create.blade.php`

Ce fichier affiche :

- le formulaire de creation ;
- l apercu de facture en direct.

#### `show.blade.php`

Ce fichier affiche :

- la facture visible a l ecran ;
- le rendu detaille avant telechargement.

#### `pdf.blade.php`

Ce fichier contient :

- le vrai modele PDF.

### Ce qu il faut retenir

Si tu veux modifier l apparence ou l affichage d une facture, tu dois presque toujours chercher dans ce dossier.

---

### `resources/views/products`

Ce dossier contient :

- la liste des produits ;
- le formulaire produit.

### `resources/views/clients`

Ce dossier contient :

- la liste des clients ;
- le formulaire client.

### `resources/views/categories`

Ce dossier contient :

- la liste des categories ;
- le formulaire categorie.

### `resources/views/users`

Ce dossier contient :

- la liste des utilisateurs ;
- le formulaire utilisateur.

### `resources/views/profile`

Ce dossier contient :

- la page profil.

### `resources/views/settings`

Ce dossier contient :

- la page des parametres.

### `resources/views/stock-movements`

Ce dossier contient :

- la page des mouvements de stock.

### `resources/views/activities`

Ce dossier contient :

- la page du journal d activites.

### `resources/views/ai`

Ce dossier contient :

- la page complete du module IA ;
- le rapport PDF IA ;
- les vues liees a l audit.

### `resources/views/emails`

Ce dossier contient :

- les emails envoyes automatiquement.

Exemple :

- email de facture.

---

## 9. Le dossier `resources/js`

Ce dossier contient le JavaScript.

Tu cherches ici quand tu modifies :

- un comportement dynamique ;
- un bouton interactif ;
- un formulaire qui change sans recharger ;
- le chat IA ;
- l ajout de lignes de facture.

### Ce qu il faut retenir

PHP gere la logique serveur, mais JavaScript gere surtout l interaction directe dans le navigateur.

---

## 10. Le dossier `resources/css`

Ce dossier contient le style.

Tu cherches ici quand tu modifies :

- les couleurs ;
- les espacements ;
- les tableaux ;
- les cartes ;
- la sidebar ;
- le mode sombre ;
- les boutons ;
- le style du dashboard ;
- le style de la facture ecran.

---

## 11. Le dossier `routes`

Ce dossier contient les routes.

### `routes/web.php`

C est le fichier le plus important.

Il dit quelle URL appelle quel controleur.

Exemple simple :

- telle URL ouvre la connexion ;
- telle URL ouvre le dashboard ;
- telle URL cree une facture ;
- telle URL lance l IA.

### Ce qu il faut retenir

Si tu ne sais pas par ou une page passe, commence souvent par `routes/web.php`.

---

## 12. Le dossier `database`

Ce dossier contient la base de donnees sous forme de code Laravel.

### `database/migrations`

Ce dossier contient la structure des tables.

Tu dois l ouvrir si tu veux comprendre :

- quelles tables existent ;
- quelles colonnes existent ;
- quelles relations existent ;
- comment la base a ete construite.

### `database/seeders`

Ce dossier contient les donnees de base ou de demonstration.

Tu l ouvres si tu veux voir :

- les comptes de demo ;
- les roles par defaut ;
- les produits de demo ;
- les parametres initiaux.

### Ce qu il faut retenir

`migrations` = structure  
`seeders` = donnees de depart

---

## 13. Le dossier `public`

Ce dossier contient ce qui est visible par le navigateur.

Exemple :

- `index.php`
- `app.css`
- `app.js`

### Ce qu il faut retenir

Si une ressource est chargee publiquement, elle passe souvent par ce dossier.

---

## 14. Le dossier `storage`

Ce dossier contient :

- les logs ;
- les caches ;
- les vues compilees ;
- certains fichiers temporaires ;
- les fichiers stockes publiquement.

### Sous-dossiers utiles

#### `storage/logs`

Si tu as une erreur Laravel, tu peux souvent chercher ici.

#### `storage/framework`

Ce dossier contient :

- des caches ;
- les vues compilees.

#### `storage/app/public`

Ce dossier contient souvent :

- les logos ;
- les photos de profil ;
- les images envoyees par le systeme.

---

## 15. Le dossier `docs`

Ce dossier contient tes documents de travail.

Il est tres utile pour :

- le memoire ;
- les rapports ;
- les guides internes.

### Ce qu il faut retenir

Tu peux utiliser ce dossier comme espace de documentation du projet.

---

## 16. Le dossier `config`

Ce dossier contient la configuration Laravel.

Tu cherches ici quand tu veux comprendre :

- la base de donnees ;
- le mail ;
- les sessions ;
- les services ;
- le cache.

Mais en general, pour ton memoire, tu y touches moins que :

- `app`
- `resources`
- `routes`
- `database`

---

## 17. Comment retrouver un code rapidement

Voici une methode tres simple.

### Si tu veux modifier une page

Cherche d abord dans :

- `resources/views`

### Si tu veux modifier un traitement

Cherche d abord dans :

- `app/Http/Controllers`

### Si tu veux modifier une regle metier

Cherche d abord dans :

- `app/Services`

### Si tu veux comprendre une table

Cherche d abord dans :

- `app/Models`
- `database/migrations`

### Si tu veux comprendre une autorisation

Cherche dans :

- `routes/web.php`
- `app/Http/Middleware`

### Si tu veux comprendre l IA

Cherche dans :

- `AiController.php`
- `AiAuditService.php`
- `AiSettingsService.php`
- `resources/views/ai`

---

## 18. Raccourci mental tres simple

Tu peux retenir cette phrase :

- `routes` dit ou aller ;
- `controller` recoit la demande ;
- `service` fait le travail ;
- `model` parle a la base ;
- `view` affiche ;
- `migration` cree les tables.

Si tu retiens deja cela, tu comprends une grande partie de l architecture.

---

## 19. Exemple concret facile

### Cas : je veux modifier la creation de facture

Tu peux suivre ce chemin :

1. `routes/web.php`
   pour voir quelle route appelle la creation

2. `app/Http/Controllers/InvoiceController.php`
   pour voir la methode `create()` et la methode `store()`

3. `app/Services/InvoiceService.php`
   pour voir comment les montants sont calcules

4. `app/Services/StockService.php`
   pour voir comment le stock diminue

5. `resources/views/invoices/create.blade.php`
   pour voir le formulaire et l apercu

6. `resources/views/invoices/pdf.blade.php`
   pour voir le modele final PDF

Cet exemple montre tres bien comment se deplace la logique dans le projet.

---

## 20. Conclusion tres simple

Si tu veux travailler facilement dans ce projet, il ne faut pas regarder tous les dossiers en meme temps.

Fais toujours comme ceci :

1. identifie la fonctionnalite ;
2. ouvre le controleur principal ;
3. ouvre le service si le traitement est important ;
4. ouvre la vue si l affichage est concerne ;
5. ouvre le modele ou la migration si la base est concernee.

Avec cette methode, tu vas retrouver ton code beaucoup plus vite et tu seras plus a l aise quand quelqu un te demandera :

- ou se trouve la logique ;
- ou se trouve l affichage ;
- ou se trouve la base ;
- ou se trouve l IA.
