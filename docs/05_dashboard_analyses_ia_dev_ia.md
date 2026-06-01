# Rapport 05 - Dashboard Admin, Analyse Locale Et Envoi A L IA

## 1. Objectif

Ce document explique :

- comment le dashboard admin est construit ;
- comment les analyses sont calculees ;
- comment les graphiques sont alimentes ;
- comment les donnees sont preparees pour Gemini ;
- comment la periode d analyse change les resultats.

## 2. Fichiers principaux

- `app/Http/Controllers/DashboardController.php`
- `app/Services/AiAuditService.php`
- `resources/views/dashboard/index.blade.php`
- `resources/js/app.js`
- `resources/css/app.css`

## 3. Principe general

Le dashboard suit la logique suivante :

1. la base est lue ;
2. les donnees sont filtrees par periode ;
3. l application calcule les indicateurs ;
4. les graphiques sont remplis ;
5. Gemini ne fait ensuite que commenter et resumer.

## 4. Periodes d analyse

Le projet prend en charge :

- `Journalier`
- `Hebdomadaire`
- `Mensuel`
- `Semestriel`
- `Annuel`

### Effet de chaque periode

- `Journalier` : lecture par heures ;
- `Hebdomadaire` : lecture par jours ;
- `Mensuel` : lecture par jours du mois ;
- `Semestriel` : lecture plus large sur six mois ;
- `Annuel` : vue globale sur douze mois.

## 5. Chiffre d affaires

Le chiffre d affaires global et periodique se base sur :

- les factures validees ;
- les montants de facture ;
- la devise systeme ;
- la repartition des devises si plusieurs existent.

Exemple de logique :

```php
$revenue = Facture::query()
    ->where('statut', 'validated')
    ->sum('total_ttc');
```

## 6. Graphiques

Les graphiques visibles sur le dashboard sont alimentes localement par le JavaScript.

Exemples :

- evolution du chiffre d affaires ;
- produits qui rapportent ;
- repartition du stock ;
- activite utilisateur ;
- indicateur de priorite.

Le fichier `resources/js/app.js` recupere la reponse locale et met a jour les cartes et les graphiques sans rechargement complet.

## 7. Analyse locale puis interpretation Gemini

### Etape locale

L application calcule :

- score global ;
- stock ;
- alertes ;
- anomalies ;
- tendances ;
- top produits ;
- activite utilisateurs.

### Etape Gemini

Gemini recoit uniquement les resultats calcules.

Il doit :

- rediger un resume ;
- interpreter ;
- commenter ;
- proposer des recommandations ;
- signaler les incoherences.

Il ne doit pas recalculer les chiffres.

## 8. Ce que le service IA envoie

Le service construit un contexte avec :

- finance ;
- stock ;
- utilisateurs ;
- anomalies ;
- recommandations ;
- alertes ;
- graphiques ;
- devise ;
- periode.

## 9. Ce qu il faut dire au jury

> Le dashboard calcule d abord localement les indicateurs. Gemini n est pas le moteur de calcul. Il intervient apres coup pour rediger un commentaire de direction, un resume et des alertes exploitables.

