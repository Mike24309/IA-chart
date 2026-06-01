# Rapport 03 - Facture, Apercu Direct, PDF Et Email

## 1. Objectif

Ce document explique :

- comment la facture est creee ;
- comment l apercu se met a jour en direct ;
- comment le PDF est genere ;
- comment l envoi email fonctionne ;
- comment le montant d un article peut etre saisi manuellement.

## 2. Fichiers principaux

- `app/Http/Controllers/InvoiceController.php`
- `app/Services/InvoiceService.php`
- `app/Services/StockService.php`
- `app/Models/Facture.php`
- `app/Models/FactureDetail.php`
- `resources/views/invoices/create.blade.php`
- `resources/views/invoices/show.blade.php`
- `resources/views/invoices/pdf.blade.php`
- `app/Mail/InvoiceMail.php`
- `app/Jobs/SendInvoiceEmailJob.php`

## 3. Saisie de facture

La vue `resources/views/invoices/create.blade.php` contient :

- le client ;
- la date ;
- la devise ;
- les notes ;
- les lignes de produits.

Chaque ligne de facture contient maintenant :

- `Produit`
- `Quantite`
- `Montant unitaire HT`
- `Action`

Le montant unitaire est modifiable par l employe au moment de la vente.

## 4. Apercu en direct

Le JavaScript de la page recalcule :

- le prix unitaire ;
- le total de ligne ;
- le total HT ;
- la TVA ;
- le total TTC ;
- la devise affichee ;
- la note ;
- le client ;
- la date d echeance.

Extrait simplifie :

```javascript
const unitPrice = enteredPrice > 0 ? enteredPrice : defaultPrice;
const quantity = Math.max(1, Number(quantityInput.value || 1));
const lineTotal = unitPrice * quantity;
```

### Explication

- si l employe entre un montant unitaire, il est utilise ;
- sinon, le prix de vente du produit est pris par defaut ;
- le total de ligne est calcule automatiquement.

## 5. Logique serveur de la facture

Le controleur valide :

- le client ;
- la date ;
- la devise ;
- les lignes ;
- la quantite ;
- le montant unitaire optionnel.

Puis il delegue au service :

- `InvoiceService@create`

Le service :

- verrouille le produit ;
- verifie le stock ;
- cree la facture ;
- cree les lignes ;
- calcule les totaux ;
- diminue le stock ;
- journalise l action.

Extrait simplifie :

```php
$customUnitPrice = isset($item['unit_price_ht'])
    ? $this->money((float) $item['unit_price_ht'])
    : null;

$unitPrice = $customUnitPrice ?? $this->money((float) $produit->sale_price);
$lineTotal = $this->money($unitPrice * $quantity);
```

## 6. Pourquoi le stock diminue

Quand une facture est validee :

- chaque ligne vendue devient une sortie de stock ;
- `StockService` applique la sortie ;
- le stock du produit baisse ;
- le mouvement est conserve dans `mouvements_stock`.

Cela permet de garder un stock coherent avec les ventes.

## 7. PDF de facture

Le PDF est genere avec DomPDF.

Processus :

1. `InvoiceController@pdf` ou `buildPdfResponse`
2. chargement de `resources/views/invoices/pdf.blade.php`
3. rendu HTML -> PDF
4. telechargement ou envoi email

Le PDF reprend :

- les informations de l emetteur ;
- les informations du client ;
- les lignes ;
- les totaux ;
- la devise choisie ;
- la note ;
- la signature ;
- les informations de pied de page.

## 8. Envoi email

Quand on clique sur `Creer et envoyer` :

- la facture est d abord enregistree ;
- ensuite un job en arriere-plan envoie l email ;
- le client recoit le PDF en piece jointe.

Extrait logique :

```php
Mail::to($invoice->client->email)->send(new InvoiceMail($invoice, $pdfBinary));
```

## 9. Points importants pour la soutenance

### 9.1 Pourquoi le montant unitaire est editable ?

Parce que certains cas de vente demandent un ajustement manuel :

- remise commerciale ;
- prix negocie ;
- correction ponctuelle ;
- prix special client.

### 9.2 Pourquoi calculer en serveur ?

Parce que le calcul serveur :

- est plus sur ;
- evite la falsification ;
- garantit la coherence du stock ;
- conserve une trace dans la base.

### 9.3 Pourquoi l apercu direct ?

Pour que l utilisateur voie avant validation :

- le rendu final ;
- les chiffres ;
- les erreurs eventuelles ;
- la devise finale.

