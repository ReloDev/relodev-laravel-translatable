# Laravel Relodev Translatable

Package Laravel ultra-léger pour gérer la traduction bilingue (FR/EN) de vos modèles Eloquent et de vos vues, sans dépendance lourde.

## Fonctionnement

Le package repose sur deux piliers :

- **Vues & messages** : traduction classique via les fichiers `lang/fr/` et `lang/en/` de Laravel
- **Données dynamiques** : colonnes supplémentaires en base de données (`champ_en`) et un trait Eloquent qui retourne automatiquement la bonne valeur selon la locale active

## Prérequis

- PHP 8.1+
- Laravel 10, 11 ou 12

## Installation

```bash
composer require relodev/laravel-relodevtranslatable
```

Le ServiceProvider est auto-découvert par Laravel, aucune configuration manuelle nécessaire.

## Configuration

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag=translatable-config
```

Cela crée `config/translatable.php` :

```php
return [
    'supported_locales' => ['fr', 'en'],
    'fallback_locale'   => 'fr',
];
```

| Clé | Description |
|-----|-------------|
| `supported_locales` | Langues supportées par le package |
| `fallback_locale` | Langue utilisée si la traduction active est vide |

## Utilisation

### 1. Générer la migration

La commande artisan génère automatiquement la migration qui ajoute les colonnes traduites.
Le champ de base (ex: `note`) sert de version par défaut (français), seule la colonne `_en` est créée.

```bash
php artisan translatable:migration {table} {colonnes...}
```

Exemple :

```bash
php artisan translatable:migration commentaires contenu note
```

Migration générée :

```php
$table->text('contenu_en')->nullable();
$table->text('note_en')->nullable();
```

Puis :

```bash
php artisan migrate
```

### 2. Configurer le modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TonVendor\Translatable\Traits\HasTranslation;

class Commentaire extends Model
{
    use HasTranslation;

    protected $fillable = [
        'contenu',
        'contenu_en',
        'note',
        'note_en',
    ];

    // Champs à traduire automatiquement
    protected $translatable = [
        'contenu',
        'note',
    ];
}
```

### 3. Affichage dans les vues

```blade
{{-- Retourne automatiquement la valeur selon la locale active --}}
{{ $commentaire->contenu }}
{{ $commentaire->note }}

{{-- Valeur brute pour les formulaires d'édition --}}
{{ $commentaire->getRawAttribute('contenu', 'fr') }}
{{ $commentaire->getRawAttribute('contenu', 'en') }}

{{-- Vérifier si une traduction existe --}}
@if($commentaire->hasTranslation('contenu', 'en'))
    <span class="badge">EN</span>
@endif
```

### 4. Traductions statiques (vues, messages)

Fonctionne exactement comme Laravel nativement, aucun changement :

```
lang/
├── fr/
│   └── accueil/
│       └── faq.php
└── en/
    └── accueil/
        └── faq.php
```

```blade
{{ __('accueil/faq.no_faqs') }}
```

### 5. Changer la langue

Via un middleware :

```php
public function handle($request, $next)
{
    $locale = $request->segment(1); // /fr/... ou /en/...

    if (in_array($locale, ['fr', 'en'])) {
        app()->setLocale($locale);
    }

    return $next($request);
}
```

Via une route de switch :

```php
Route::get('/langue/{locale}', function ($locale) {
    session(['locale' => $locale]);
    app()->setLocale($locale);
    return back();
});
```

## Méthodes disponibles sur les modèles

| Méthode | Description |
|---------|-------------|
| `$model->champ` | Retourne la valeur dans la locale active |
| `$model->getRawAttribute('champ', 'en')` | Retourne la valeur brute d'une locale précise |
| `$model->hasTranslation('champ', 'en')` | Vérifie si une traduction existe pour ce champ |

## Logique de résolution

Quand vous accédez à `$model->note`, le trait suit cet ordre :

```
1. note_{locale_active}   → si non vide, retourner cette valeur
2. note_{fallback_locale} → si non vide, retourner cette valeur
3. note                   → valeur de base en dernier recours
```

## Changelog

### v1.0.2
- Fix : la migration ne génère plus la colonne `_fr` (redondante avec le champ de base)
- Fix : le `down()` de la migration supprime désormais les colonnes correctement

### v1.0.1
- Fix : support élargi aux projets Laravel 9

### v1.0.0
- Release initiale

## Licence

MIT