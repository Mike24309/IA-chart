# Rapport 06 - Styles, Modeles Et Zones A Modifier

## 1. Objectif

Ce document sert de carte rapide pour modifier l apparence, les textes et certains comportements de l application.

## 2. Fichiers de style principaux

- `resources/css/app.css`
- `public/app.css`

Ces fichiers contiennent :

- les couleurs ;
- les espacements ;
- les boutons ;
- les cartes ;
- la sidebar ;
- la topbar ;
- les tableaux ;
- les formulaires.

## 3. Fichiers de structure visuelle

- `resources/views/layouts/app.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/invoices/create.blade.php`
- `resources/views/invoices/show.blade.php`
- `resources/views/invoices/pdf.blade.php`

## 4. Modeles utiles

Les modeles definissent la relation entre le code et la base :

- `app/Models/User.php`
- `app/Models/Produit.php`
- `app/Models/Facture.php`
- `app/Models/FactureDetail.php`
- `app/Models/Client.php`

Le trait de compatibilite :

- `app/Models/Concerns/MappeAnciensAttributs.php`

sert a faire correspondre les anciens noms de colonnes avec les nouveaux noms utilises dans le code.

## 5. Exemple de modification visuelle

### Changer une couleur

Si tu veux changer l orange principal, tu modifies `resources/css/app.css`.

Exemple :

```css
:root {
    --brand-primary: #0f172a;
    --brand-accent: #f97316;
    --surface: #ffffff;
}
```

### Changer la taille d un bouton

```css
.btn-primary {
    padding: 14px 20px;
    border-radius: 14px;
    font-weight: 700;
}
```

### Changer un titre

Le texte du titre se change dans la vue correspondante :

- dashboard ;
- login ;
- facture ;
- sidebar IA.

## 6. Exemple de modification de contenu

Si tu veux changer le texte d accueil du dashboard :

```blade
<h1>Dashboard</h1>
<p>Pilotage commercial, stock et activite operationnelle</p>
```

## 7. Exemple de logique dans la facture

Dans `resources/views/invoices/create.blade.php` :

- la ligne de produit est construite ;
- le montant unitaire est editable ;
- le total se met a jour dans le navigateur ;
- le serveur recalcule au moment de la soumission.

## 8. Exemple de zone a modifier pour l IA

Si tu veux modifier le resume Gemini :

- `app/Services/AiAuditService.php`

Si tu veux modifier la sidebar IA :

- `resources/views/layouts/app.blade.php`

Si tu veux modifier le comportement du clic :

- `resources/js/app.js`

## 9. Point a retenir

Le plus important est de distinguer :

- **style** -> CSS ;
- **texte** -> Blade ;
- **comportement** -> JavaScript ;
- **logique metier** -> Controller / Service / Model.

