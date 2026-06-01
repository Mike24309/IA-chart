# Rapport 4 - Questions Et Reponses Possibles Du Jury

## 1. Pourquoi avoir choisi Laravel ?

Reponse :

J ai choisi Laravel parce que c est un framework PHP tres structure, qui facilite l architecture MVC, la gestion des routes, l authentification, les vues Blade, les middlewares, l acces a la base avec Eloquent et l organisation du code. Pour une application de gestion, cela permet d avoir un projet lisible et evolutif.

## 2. Pourquoi avoir choisi une architecture MVC ?

Reponse :

J ai choisi MVC pour separer clairement :

- la logique metier ;
- l affichage ;
- le traitement des requetes.

Cela rend l application plus facile a maintenir, a expliquer et a faire evoluer.

## 3. Quelle est la difference entre un controller et un service dans ton projet ?

Reponse :

Le controller recoit la requete HTTP, valide les donnees et choisit la reponse. Le service contient la logique metier reutilisable. Par exemple, `InvoiceController` orchestre l action, tandis que `InvoiceService` calcule la facture et cree les lignes.

## 4. Pourquoi avoir separe `factures` et `details_facture` ?

Reponse :

Parce qu une facture peut contenir plusieurs lignes de produits. La table `factures` contient l entete, alors que `details_facture` contient les lignes. Cette separation evite la repetition et respecte une bonne modelisation relationnelle.

## 5. Pourquoi avoir cree une table `mouvements_stock` alors qu il y a deja `produits.stock` ?

Reponse :

`produits.stock` donne l etat actuel du stock. `mouvements_stock` garde l historique complet des entrees, sorties et ajustements. Cela permet la tracabilite et l audit.

## 6. Pourquoi avoir une table `roles` separee des utilisateurs ?

Reponse :

Parce que le role est une information reutilisable. Plusieurs utilisateurs peuvent partager le meme role. Cette separation permet une meilleure normalisation et facilite la gestion des droits.

## 7. Quelles sont les principales cles etrangeres ?

Reponse :

Les principales sont :

- `utilisateurs.role_id -> roles.id`
- `produits.categorie_id -> categories.id`
- `factures.client_id -> clients.id`
- `factures.utilisateur_id -> utilisateurs.id`
- `details_facture.facture_id -> factures.id`
- `details_facture.produit_id -> produits.id`
- `mouvements_stock.produit_id -> produits.id`
- `mouvements_stock.utilisateur_id -> utilisateurs.id`

## 8. Comment as-tu gere la connexion des utilisateurs ?

Reponse :

La connexion passe par `AuthController`. L utilisateur peut se connecter soit avec son email, soit avec son code de connexion. Ensuite, le systeme verifie le mot de passe chiffre, ouvre la session Laravel, met a jour la derniere connexion et journalise l activite.

## 9. Comment differencies-tu l administrateur et l employe ?

Reponse :

Je me base sur le role de l utilisateur. Le modele `User` possede une methode `isAdministrator()` qui permet de savoir si l utilisateur est admin. Ensuite, les routes, la sidebar et le dashboard changent selon ce role.

## 10. Comment as-tu protege les routes sensibles ?

Reponse :

J utilise des middlewares. Le middleware `auth` exige une connexion. Le middleware `active` verifie que le compte est actif. Le middleware `role:admin` reserve certaines routes a l administrateur, par exemple les utilisateurs, les parametres, le module IA et la suppression de facture.

## 11. Comment fonctionne la creation d une facture ?

Reponse :

Le formulaire envoie les donnees a `InvoiceController`. Le controleur valide la requete, cree le client si besoin, puis appelle `InvoiceService`. Le service cree la facture, les lignes de facture, calcule les montants et demande a `StockService` de diminuer le stock.

## 12. Comment sont calcules la TVA et la remise ?

Reponse :

La TVA et la remise par defaut viennent de la table `parametres`. Elles ne sont pas definies a chaque facture par l utilisateur. Le serveur lit les parametres, calcule le HT, la remise, le montant taxable, la TVA et enfin le TTC.

## 13. Pourquoi avoir mis la TVA et la remise dans les parametres ?

Reponse :

Parce que ce sont des regles globales de gestion. Cela evite les erreurs de saisie et garantit des calculs coherents dans toute l application.

## 14. Comment generes-tu les PDF ?

Reponse :

J utilise `barryvdh/laravel-dompdf`. Laravel charge une vue Blade, puis DomPDF la transforme en fichier PDF. Cette technique est utilisee pour la facture et pour le rapport IA.

## 15. Pourquoi avoir choisi DomPDF ?

Reponse :

Parce qu il s integre facilement dans Laravel, permet d utiliser Blade, et convient tres bien aux documents administratifs comme les factures et les rapports.

## 16. Comment envoies-tu une facture par email ?

Reponse :

Le controleur genere d abord le PDF en memoire, puis il utilise `Mail` avec la classe `InvoiceMail` pour l envoyer au client. La configuration SMTP est chargee depuis les parametres d environnement.

## 17. Comment l IA est-elle integree dans ton projet ?

Reponse :

L IA est integree comme un module d analyse separe. Le controleur `AiController` recoit les actions, et `AiAuditService` prepare les donnees, construit le prompt, appelle Gemini, nettoie la reponse, stocke les resultats, puis alimente le dashboard et les rapports.

## 18. Pourquoi dis-tu que l IA n accede pas directement a la base ?

Reponse :

Parce que Gemini ne se connecte pas a MySQL. C est l application qui lit les donnees de la base, construit un resume structure, puis l envoie a Gemini. L IA travaille donc sur des donnees controlees par l application.

## 19. Quelles donnees envoies-tu a l IA ?

Reponse :

J envoie principalement :

- les ventes ;
- les factures ;
- les produits critiques ;
- les mouvements de stock ;
- l activite utilisateur ;
- les marges ;
- les indicateurs mensuels.

## 20. Comment Gemini est-il appele techniquement ?

Reponse :

Laravel utilise un appel HTTP depuis `AiAuditService`. La cle API est dans `.env`, le modele est gere dans `parametres_ia`, et la requete part vers l endpoint `generateContent` de Gemini.

## 21. Pourquoi avoir garde une analyse locale et une analyse avec IA ?

Reponse :

L analyse locale permet de remplir rapidement les graphiques meme sans demander une vraie explication complete. L analyse avec IA produit le resume, les alertes, les recommandations et le rapport PDF.

## 22. Pourquoi le rapport PDF IA est-il reserve a une vraie analyse IA ?

Reponse :

Parce que je voulais eviter un faux rapport. Le telechargement est autorise seulement si Gemini a produit une vraie analyse complete, avec un resume, des details et une conclusion exploitables.

## 23. Comment stockes-tu les resultats de l IA ?

Reponse :

Les analyses sont memorisees dans `journaux_ia`. Les notes sont stockees dans `scores_performance`. Les alertes sont stockees dans `notifications_ia`. Les exports PDF sont traces dans `historiques_rapports_ia`.

## 24. Pourquoi as-tu ajoute un systeme de notifications IA ?

Reponse :

Pour que l administrateur voie rapidement les alertes importantes sans devoir lire tout le rapport. Cela rend l IA plus utile dans un contexte de supervision.

## 25. Pourquoi certains noms du code sont encore en anglais alors que la base est en francais ?

Reponse :

Le projet a evolue. Pour conserver la compatibilite, j ai garde une partie des anciens noms logiques dans le code, mais la base physique a ete renommee en francais. Le trait `MappeAnciensAttributs` sert justement a faire le lien entre les anciens noms et les colonnes reelles.

## 26. Comment peux-tu justifier l utilisation de services ?

Reponse :

Les services permettent d isoler les traitements importants. Par exemple :

- `InvoiceService` pour la facture ;
- `StockService` pour le stock ;
- `AiAuditService` pour l IA ;
- `ParameterService` pour les parametres.

Cela rend le code plus propre, plus reutilisable et plus facile a tester ou corriger.

## 27. Comment le dashboard est-il alimente ?

Reponse :

Le `DashboardController` calcule les indicateurs principaux : chiffre d affaires, nombre de factures, produits critiques, activite, meilleures categories et donnees des graphiques. Ensuite la vue Blade les affiche, et le JavaScript s occupe de certains graphiques dynamiques.

## 28. Pourquoi avoir utilise Blade au lieu d un framework front complet ?

Reponse :

Pour ce type d application de gestion, Blade est suffisant, rapide a integrer avec Laravel, et plus simple a maintenir dans un contexte de memoire et de soutenance.

## 29. Comment garantis-tu la coherence du stock ?

Reponse :

Chaque facture validee entraine une sortie de stock via `StockService`. En cas de suppression de facture par l administrateur, le stock est remis a jour. De plus, chaque mouvement est journalise dans `mouvements_stock`.

## 30. Comment ameliorerais-tu l application dans une version future ?

Reponse :

Je pourrais :

- ajouter des tests automatiques ;
- renforcer les statistiques ;
- ajouter plus de filtres de recherche ;
- ajouter des exports Excel ;
- enrichir les analyses IA ;
- ajouter des permissions plus fines que seulement admin et employe.

## 31. Si le jury te demande ta phrase de synthese

Tu peux dire :

`DEV IA est une application Laravel de gestion commerciale et de stock, enrichie par un module IA base sur Gemini. L architecture suit le modele MVC, la base de donnees est structuree en tables metier et tables IA, la facturation est reliee au stock, les PDF sont generes avec DomPDF, et l IA fournit des analyses, alertes, reponses et rapports a partir des donnees que l application lui transmet de facon controlee.`
