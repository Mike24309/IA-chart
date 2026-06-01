# Rapport 2 - Reperage Des Fichiers Et Personnalisation De L Interface

## 1. Objet du rapport

Ce rapport te montre :

- quel fichier ouvre quelle partie de l application ;
- ou changer une couleur ;
- ou changer la taille d un bouton ;
- ou modifier le dashboard ;
- ou changer le PDF ;
- ou modifier l IA ;
- ou chercher un bloc quand les noms anglais te compliquent.

## 2. Regle simple a retenir

Quand tu veux modifier quelque chose, pense a ces 4 niveaux :

### 2.1 Ce qu on voit

Dossier :
- `resources/views`

Ici tu changes :
- les boutons visibles ;
- les textes ;
- les titres ;
- les tableaux ;
- les formulaires ;
- les blocs des pages.

### 2.2 Le style

Fichiers :
- `resources/css/app.css`
- `public/app.css`

Ici tu changes :
- les couleurs ;
- la hauteur ;
- la largeur ;
- les marges ;
- les arrondis ;
- les ombres ;
- l apparence generale.

### 2.3 Le comportement dynamique

Fichiers :
- `resources/js/app.js`
- `public/app.js`

Ici tu changes :
- les graphiques ;
- le panneau IA ;
- les actions sans recharger ;
- les mises a jour du dashboard ;
- l apercu de facture.

### 2.4 Le traitement serveur

Dossier :
- `app/Http/Controllers`

Ici tu changes :
- ce qui se passe apres un clic ;
- les validations ;
- les redirections ;
- la creation ou suppression des donnees.

## 3. Les fichiers les plus importants

### 3.1 Layout general

Fichier :
- `resources/views/layouts/app.blade.php`

Ce fichier gere :
- la sidebar ;
- la topbar ;
- le bouton IA ;
- le panneau IA ;
- la boite de dialogue IA ;
- la structure commune apres connexion.

Tu viens ici si tu veux :
- retirer un lien du menu ;
- changer un texte visible partout ;
- retirer un bouton du panneau IA ;
- changer le bouton flottant IA.

Exemple a chercher :

```blade
<a href="{{ route('ai.report') }}" class="btn btn-secondary is-disabled" id="ai-sidepanel-download" aria-disabled="true">Telecharger le resume</a>
```

Si tu veux changer ce bouton :
- tu modifies ce bloc dans `layouts/app.blade.php`.

### 3.2 Style general

Fichier principal :
- `resources/css/app.css`

Fichier lu directement par le projet actuel :
- `public/app.css`

Exemple de blocs utiles :

```css
.btn-primary { background: var(--navy); color: #fff; }
.btn-secondary { background: #e2e8f0; color: var(--navy); }
```

Ici tu changes :
- la couleur du bouton principal ;
- la couleur du bouton secondaire.

## 4. Page de connexion

### 4.1 Structure

Fichier :
- `resources/views/auth/login.blade.php`

Tu viens ici si tu veux :
- changer le titre `DEV IA` ;
- supprimer un champ ;
- changer le texte `Connexion Administrative` ;
- retirer le bouton de creation du premier admin ;
- changer le bouton `Login`.

Exemples a chercher :

```blade
<title>DEV IA</title>
```

```blade
<p>Connexion Administrative</p>
<h2>DEV IA</h2>
```

```blade
<button class="btn auth-submit-btn" type="submit">Login</button>
```

### 4.2 Couleurs et tailles

Fichier :
- `resources/css/app.css`

Blocs a chercher :

```css
.auth-page
.auth-brand-panel
.auth-login-panel
.auth-brand-logo
.auth-submit-btn
.auth-secondary-btn
```

Exemples :

```css
.auth-page {
    min-height: 100vh;
    background: radial-gradient(...);
}
```

```css
.auth-submit-btn {
    width: 100%;
    border-radius: 4px;
    background: linear-gradient(180deg, #fb923c 0%, #dc2626 100%);
}
```

Ce que tu changes ici :
- le fond de la page ;
- le fond du panneau gauche ;
- le fond du panneau droit ;
- la couleur du bouton Login ;
- la taille du bouton Login.

## 5. Sidebar et navigation

### 5.1 Structure

Fichier :
- `resources/views/layouts/app.blade.php`

Exemples a chercher :

```blade
<nav class="nav-links">
```

```blade
<a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
```

Si tu veux :
- retirer un menu ;
- changer le texte d un lien ;
- changer l ordre du menu ;
alors tu modifies ici.

### 5.2 Couleur et largeur

Fichier :
- `resources/css/app.css`

Exemples a chercher :

```css
.app-shell { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
```

```css
.sidebar {
    background: linear-gradient(180deg, var(--navy) 0%, #111827 100%);
}
```

Ce que tu changes ici :
- la largeur de la sidebar ;
- sa couleur ;
- son fond ;
- son comportement fixe ou non.

## 6. Dashboard

### 6.1 Structure du dashboard

Fichier :
- `resources/views/dashboard/index.blade.php`

Tu viens ici si tu veux :
- changer les cartes statistiques ;
- changer les boutons `Analyse locale`, `Analyse avec IA`, `Rafraichir` ;
- changer les blocs graphiques ;
- retirer ou ajouter une section ;
- changer les titres du dashboard.

Exemples a chercher :

```blade
<article class="card stat-card">
```

```blade
<button type="button" class="btn btn-secondary" id="ai-run-local-analysis">Analyse locale</button>
<button type="button" class="btn btn-primary" id="ai-run-analysis">Analyse avec IA</button>
```

```blade
<canvas id="dashboard-ai-revenue-chart"></canvas>
```

### 6.2 Style du dashboard

Fichier :
- `resources/css/app.css`

Blocs a chercher :

```css
.stat-card
.stat-card-value
.stat-card-currency
```

Exemples :

```css
.stat-card {
    width: 100%;
    height: 118px;
}
```

```css
.stat-card strong {
    display: flex;
    flex-direction: column;
    align-items: center;
}
```

Ce que tu changes ici :
- la taille des cartes ;
- l alignement des chiffres ;
- l espace entre les blocs ;
- le look du dashboard.

### 6.3 Graphiques du dashboard

Fichier structure :
- `resources/views/dashboard/index.blade.php`

Fichier comportement :
- `resources/js/app.js`

Exemples a chercher dans la vue :

```blade
<canvas id="dashboard-ai-revenue-chart"></canvas>
<canvas id="dashboard-ai-category-chart"></canvas>
<canvas id="dashboard-ai-product-share-chart"></canvas>
```

Exemples a chercher dans le JS :

```js
upsertChart('dashboard-ai-revenue-chart', {
```

```js
upsertChart('dashboard-ai-category-chart', {
```

```js
upsertChart('dashboard-ai-product-share-chart', {
```

Si tu veux changer :
- le type du graphique ;
- les couleurs ;
- les labels ;
- les donnees affichees ;
tu modifies surtout `resources/js/app.js`.

## 7. IA

### 7.1 Interface visible de l IA

Fichier :
- `resources/views/layouts/app.blade.php`

Tu viens ici si tu veux :
- retirer un bouton IA ;
- changer le texte du panneau ;
- changer la boite de dialogue IA ;
- changer le bouton `Telecharger le resume`.

Exemples :

```blade
<button type="button" class="btn btn-secondary" id="ai-sidepanel-ask-open">Poser une question</button>
```

```blade
<div class="ai-dialog" id="ai-question-dialog" hidden>
```

### 7.2 Style du panneau IA

Fichier :
- `resources/css/app.css`

Blocs a chercher :

```css
.ai-fab
.ai-sidepanel
.ai-dialog
```

Exemple :

```css
.ai-fab {
    position: fixed;
    right: 24px;
    bottom: 24px;
}
```

Ce que tu changes ici :
- la position du bouton IA ;
- la taille ;
- la couleur ;
- la largeur du panneau ;
- le style de la fenetre de dialogue.

### 7.3 Logique de l IA

Fichiers :
- `app/Http/Controllers/AiController.php`
- `app/Services/AiAuditService.php`
- `app/Services/AiSettingsService.php`

Tu viens ici si tu veux :
- changer la facon dont l analyse se lance ;
- changer la facon dont Gemini est appele ;
- changer les conditions du telechargement PDF IA ;
- changer la logique des notifications IA.

## 8. Produits

### 8.1 Tableau des produits

Fichier :
- `resources/views/products/index.blade.php`

Tu viens ici si tu veux :
- changer les colonnes ;
- retirer un bouton ;
- changer le texte `Stock` ;
- modifier la vue admin ou employe.

### 8.2 Formulaire produit

Fichier :
- `resources/views/products/form.blade.php`

Tu viens ici si tu veux :
- rendre un champ obligatoire visuellement ;
- changer l ordre des champs ;
- changer le bouton d enregistrement.

## 9. Clients

### 9.1 Tableau client

Fichier :
- `resources/views/clients/index.blade.php`

### 9.2 Formulaire client

Fichier :
- `resources/views/clients/form.blade.php`

Tu viens ici si tu veux :
- changer les champs `nom`, `postnom`, `email`, `telephone` ;
- changer un bouton ;
- changer les libelles.

## 10. Factures

### 10.1 Creation de facture

Fichier :
- `resources/views/invoices/create.blade.php`

Ce fichier gere :
- le formulaire ;
- l apercu de facture ;
- les boutons de creation ;
- les lignes produit ;
- une partie du style local de l apercu.

Exemples a chercher :

```blade
<div class="button-row">
```

```blade
<button class="btn btn-primary" type="submit" name="invoice_action" value="create">Creer la facture</button>
```

```css
.invoice-preview-logo {
    width: 140px;
    height: 140px;
}
```

Si tu veux :
- agrandir le logo ;
- changer les boutons ;
- changer les couleurs du modele visible ;
- retirer un bloc dans l apercu ;
tu modifies ici.

### 10.2 Liste des factures

Fichier :
- `resources/views/invoices/index.blade.php`

Tu viens ici si tu veux :
- changer les filtres ;
- retirer le bouton PDF ;
- retirer le bouton Supprimer ;
- changer les colonnes.

### 10.3 Vue detail d une facture

Fichier :
- `resources/views/invoices/show.blade.php`

Tu viens ici si tu veux :
- changer l affichage avant telechargement ;
- retirer des infos en double ;
- rapprocher la vue du PDF.

### 10.4 PDF de facture

Fichier :
- `resources/views/invoices/pdf.blade.php`

Tu viens ici si tu veux :
- changer le look du PDF de facture ;
- changer les titres ;
- changer les couleurs ;
- retirer ou ajouter un bloc.

### 10.5 Email de facture

Fichier :
- `resources/views/emails/invoice.blade.php`

Tu viens ici si tu veux :
- changer le texte du mail ;
- changer le contenu accompagne de la facture.

### 10.6 Logique facture

Fichiers :
- `app/Http/Controllers/InvoiceController.php`
- `app/Services/InvoiceService.php`

Exemples a chercher :

```php
if ($action === 'create_send') {
```

```php
if ($action === 'create_download') {
```

```php
return Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))
```

Ici tu changes :
- le comportement des boutons ;
- le calcul serveur ;
- la creation ;
- l envoi ;
- le telechargement.

## 11. Parametres

### 11.1 Page parametres

Fichier :
- `resources/views/settings/index.blade.php`

Tu viens ici si tu veux :
- changer les sections ;
- changer un champ visible ;
- retirer un reglage.

### 11.2 Sauvegarde des parametres

Fichier :
- `app/Http/Controllers/SettingsController.php`

Ici tu changes :
- la validation ;
- les champs acceptes ;
- la sauvegarde des images.

## 12. Profil

Fichier vue :
- `resources/views/profile/edit.blade.php`

Fichier logique :
- `app/Http/Controllers/ProfileController.php`

## 13. Utilisateurs

Fichiers :
- `resources/views/users/index.blade.php`
- `resources/views/users/form.blade.php`
- `app/Http/Controllers/UserController.php`

Tu viens ici si tu veux :
- changer le formulaire utilisateur ;
- changer le role par defaut ;
- changer les validations de mot de passe.

## 14. Mouvements de stock

Fichiers :
- `resources/views/stock-movements/index.blade.php`
- `resources/views/stock-movements/create.blade.php`
- `app/Http/Controllers/StockMovementController.php`

Tu viens ici si tu veux :
- changer le tableau ;
- changer le formulaire ;
- changer le traitement des entrees, sorties et ajustements.

## 15. Resume ultra simple

Si tu veux modifier :

- un texte visible : va dans `resources/views`
- une couleur : va dans `resources/css/app.css`
- une taille : va dans `resources/css/app.css`
- un graphique : va dans `resources/js/app.js`
- un bouton IA : va dans `layouts/app.blade.php` et `app.js`
- le PDF facture : va dans `resources/views/invoices/pdf.blade.php`
- le PDF IA : va dans `resources/views/ai/report-pdf.blade.php`
- la logique serveur : va dans `app/Http/Controllers` ou `app/Services`

## 16. Ce que tu peux dire au jury

`Pour retrouver rapidement une modification, je separe toujours la structure visible, le style, le comportement dynamique et la logique serveur. Les vues Blade servent a l affichage, le CSS sert aux couleurs et dimensions, le JavaScript gere les interactions du dashboard et de l IA, et les controleurs ou services gerent les traitements serveur.`
