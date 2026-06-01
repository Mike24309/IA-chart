# Rapport 07 - Questions Probables Du Jury Et Reponses Courtes

## 1. Pourquoi le projet est-il construit en Laravel ?

Parce que Laravel donne une structure MVC claire, une securite de base solide, un ORM pratique et une bonne lisibilite pour une application de gestion.

## 2. Pourquoi la base utilise-t-elle plusieurs tables au lieu d une seule ?

Parce que chaque information a son role :

- comptes ;
- roles ;
- produits ;
- clients ;
- factures ;
- lignes ;
- stock ;
- audit ;
- IA.

Cela rend la base plus propre et plus facile a maintenir.

## 3. Pourquoi separer facture et lignes de facture ?

Parce qu une facture peut contenir plusieurs produits. La table d entete contient les donnees globales, la table de lignes contient le detail.

## 4. Pourquoi le stock diminue automatiquement ?

Parce que chaque vente correspond a une sortie reelle. Si on ne diminue pas le stock, l application donne un faux etat du magasin.

## 5. Pourquoi l IA ne recalcule-t-elle pas tout ?

Parce que les calculs doivent rester deterministes et stables. L IA lit les chiffres deja produits par l application et les interprete.

## 6. Pourquoi Gemini peut echouer sans casser l application ?

Parce que le resume IA est une couche superieure. Le calcul local du dashboard reste disponible meme si Gemini a un probleme reseau.

## 7. Pourquoi avoir ajoute un montant unitaire editable dans la facture ?

Pour gerer les cas reels :

- remise commerciale ;
- prix negocie ;
- prix special ;
- correction manuelle.

## 8. Comment expliquer la devise USD / FC ?

L utilisateur choisit la devise lors de la facture. Il n y a pas de conversion automatique. Le but est de garder une saisie simple et lisible.

## 9. Comment expliquer la connexion ?

L application utilise une session Laravel. Le mot de passe est chiffre, le role est verifie, et le compte peut etre bloque s il n est pas actif.

## 10. Que dire si le jury demande pourquoi il y a des rapports IA ?

Pour aider la direction a comprendre rapidement :

- les ventes ;
- le stock ;
- les alertes ;
- les incoherences ;
- les actions prioritaires.

## 11. Quelle est la phrase de synthese la plus utile ?

> DEV IA est une application de gestion transactionnelle a laquelle on a ajoute une couche d interpretation IA pour transformer les chiffres en lecture de direction.
