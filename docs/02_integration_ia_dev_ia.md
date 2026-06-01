# Rapport 02 - Integration De L IA Dans DEV IA

## 1. Objectif

Ce document explique comment l IA a ete integree dans l application :

- ou elle se trouve ;
- comment elle recoit les donnees ;
- comment elle interprete les resultats ;
- comment les notifications et les resumes sont produits ;
- quels fichiers modifier quand on change la logique IA.

## 2. Principe general

L application fonctionne en deux temps :

1. l application calcule localement les chiffres, scores, series et alertes ;
2. Gemini lit ces resultats et redige un resume, une interpretation ou une reponse a une question.

Le modele ne doit pas recalculer le metier. Il doit seulement expliquer ce que l application a deja produit.

## 3. Fichiers principaux

### Controleur

- `app/Http/Controllers/AiController.php`

Il expose les actions :

- `analyze`
- `interpret`
- `interpretPage`
- `ask`
- `report`
- `notificationsRead`

### Service metier IA

- `app/Services/AiAuditService.php`

Il contient :

- la preparation des metriques ;
- la construction des prompts ;
- l appel HTTP vers Gemini ;
- la normalisation des reponses ;
- la sauvegarde des journaux et notifications.

### Vue et interface

- `resources/views/layouts/app.blade.php`
- `resources/views/ai/index.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/js/app.js`

## 4. Flux technique

### 4.1 Analyse locale

Quand on clique sur `Lancer l analyse` :

- le navigateur envoie la requete vers `AiController@analyze` ;
- `AiAuditService@analyzeLocal` calcule les indicateurs ;
- le resultat retourne au dashboard ;
- les graphiques sont remplis localement.

### 4.2 Resume Gemini

Quand on clique sur `Generer le resume Gemini` :

- le formulaire envoie la requete vers `AiController@interpretPage` ;
- le controleur appelle `AiAuditService@analyze` ;
- le service construit un prompt tres court ;
- Gemini lit seulement les donnees deja calculees ;
- le resume est renvoye dans la sidebar IA.

### 4.3 Questions libres

Quand l administrateur pose une question :

- la question part vers `AiController@ask` ;
- `AiAuditService@answerQuestion` prepare le contexte ;
- Gemini repond en JSON ;
- la reponse s affiche dans la boite de dialogue.

## 5. Exemple de logique

Extrait simplifie de `AiController` :

```php
public function interpretPage(Request $request): RedirectResponse
{
    $analysis = $this->aiAuditService->analyze(
        $request->user(),
        $request->boolean('force'),
        $request->string('analysis_period')->toString()
    );

    return back()
        ->with('ai_summary_payload', $analysis)
        ->with('ai_sidepanel_open', true);
}
```

Explication :

- `analyze()` produit le contenu interprete ;
- `with('ai_summary_payload', ...)` envoie la reponse a la vue ;
- `ai_sidepanel_open` reouvre automatiquement la sidebar.

## 6. Exemple de prompt

Dans `AiAuditService`, le prompt de resume dit a Gemini :

- ne recalcule rien ;
- utilise seulement les donnees fournies ;
- n invente pas de chiffre ;
- redige un resume professionnel ;
- cite la devise correcte ;
- signale toute incoherence avec une suggestion.

Cela evite que l IA parte dans une interpretation vague ou inventee.

## 7. Notifications IA

Les alertes IA sont produites a partir de la reponse structurée puis sauvegardees dans :

- `notifications_ia`

Elles servent a :

- remonter un point critique ;
- alerter l administrateur ;
- garder un historique de ce que l IA a juge important.

Le contenu attendu d une alerte est :

- un constat ;
- un impact ;
- une action conseillee.

Exemple de logique :

```php
// Ce bloc transforme une alerte IA en notification lisible.
$message = sprintf(
    '%s. Impact: %s. Suggestion: %s.',
    $alert['title'],
    $alert['message'],
    $alert['suggestion'] ?? 'Verifier le point signalé.'
);
```

## 8. Boite de dialogue IA

La boite de dialogue dans la sidebar sert a poser une question libre.

Fichier principal :

- `resources/views/layouts/app.blade.php`

Le JavaScript :

- ouvre la zone de chat ;
- envoie la question ;
- lit la reponse JSON ;
- affiche la reponse dans la conversation.

La touche `Entrée` envoie la question, tandis que `Shift + Entrée` permet d aller a la ligne.

## 9. Quels fichiers modifier si on change l IA ?

### Si on change la logique metier

- `app/Services/AiAuditService.php`

### Si on change le bouton ou la sidebar

- `resources/views/layouts/app.blade.php`
- `resources/js/app.js`

### Si on change la page IA

- `resources/views/ai/index.blade.php`

### Si on change les textes visibles

- `resources/views/dashboard/index.blade.php`
- `resources/views/layouts/app.blade.php`

## 10. Point de defense

La bonne phrase a dire au jury est :

> L IA ne remplace pas la logique de gestion. Elle lit une analyse deja calculee, la resume, la commente et la transforme en aide a la decision.

