# Rapport 04 - Authentification Et Securite

## 1. Objectif

Ce document explique comment les utilisateurs se connectent et comment la securite est organisee dans DEV IA.

## 2. Mode d authentification

L application utilise principalement :

- l authentification Laravel par **session** ;
- pas un systeme token pour l interface web classique ;
- un controle de role et de statut de compte.

### Pourquoi la session ?

Parce que l application est une application web de gestion :

- connexion rapide ;
- navigation entre pages ;
- protection des routes ;
- interface admin et employe.

## 3. Fichiers principaux

- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/RedirectIfAuthenticated.php`
- `app/Models/User.php`
- `routes/web.php`
- `resources/views/auth/login.blade.php`
- `app/Http/Middleware/EnsureUserIsActive.php` si present dans le projet

## 4. Comment la connexion fonctionne

### Etape 1 : affichage du formulaire

L utilisateur ouvre la page de connexion.

### Etape 2 : saisie de l identifiant

Il peut entrer :

- son adresse email ;
- ou son code de connexion.

### Etape 3 : verification

Laravel :

- cherche l utilisateur ;
- verifie le mot de passe chiffre ;
- controle le statut actif ;
- controle le role ;
- ouvre la session.

### Etape 4 : redirection

L utilisateur est redirige vers le dashboard adapte a son role.

## 5. Role et droits

La table `roles` separre les profils :

- administrateur ;
- employe.

Le modele `User` contient une methode de test de role.

Exemple logique :

```php
public function isAdministrator(): bool
{
    return $this->role?->nom === 'admin';
}
```

### Effet concret

- l administrateur voit les pages de supervision ;
- l employe voit les modules de saisie et d execution ;
- le module IA peut rester visible uniquement a l admin.

## 6. Securite par session

La session permet de :

- garder l utilisateur connecte ;
- proteger les routes avec `auth` ;
- empecher l acces anonyme ;
- afficher des alertes de connexion ou d erreur.

## 7. Protection supplementaire

Le projet ajoute aussi :

- verification du statut actif ;
- controle de role ;
- journalisation des actions ;
- messages d erreur propres ;
- protection contre la lecture de factures d un autre utilisateur.

## 8. Pourquoi ce n est pas un token ?

Le token est utile pour :

- API ;
- mobile ;
- SPA consommatrice.

Ici, le besoin principal est une application web classique, donc la session est plus simple et plus naturelle.

## 9. Point a dire au jury

> La securite principale du projet repose sur la session Laravel, le mot de passe chiffre, le controle de role et la verification du statut actif. Le token n est pas le mecanisme principal de navigation de l interface web.

