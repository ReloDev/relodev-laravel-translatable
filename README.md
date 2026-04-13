# Laravel Relodev Translatable

Package Laravel ultra-léger pour gérer la traduction bilingue (FR/EN) de vos modèles Eloquent et de vos vues, sans dépendance lourde.

## Fonctionnement

Le package repose sur deux piliers :

- **Vues & messages** : traduction classique via les fichiers `lang/fr/` et `lang/en/` de Laravel
- **Données dynamiques** : colonnes supplémentaires en base de données (`champ_en`) et un trait Eloquent qui retourne automatiquement la bonne valeur selon la locale active

Le package inclut un **middleware automatique** qui détecte et applique la langue active sans aucune configuration supplémentaire.

## Prérequis

- PHP 8.1+
- Laravel 10, 11 ou 12

## Installation

```bash
composer require relodev/laravel-translatable
```

Le ServiceProvider et le middleware sont auto-enregistrés par Laravel, aucune configuration manuelle nécessaire.

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
| `fallback_locale` | Langue utilisée si aucune locale active n'est détectée |

## Gestion de la langue active

Le middleware inclus dans le package détecte automatiquement la langue à appliquer selon cet ordre de priorité :

```
1. Segment de route   → /fr/... ou /en/...
2. Session            → définie via le bouton de switch
3. Fallback config    → valeur de fallback_locale
```

Aucune modification du `.env` n'est nécessaire.

### Bouton de switch de langue

Ajoutez cette route dans `routes/web.php` :

```php
Route::get('/langue/{locale}', function ($locale) {
    $locales = config('translatable.supported_locales', ['fr', 'en']);
    if (in_array($locale, $locales)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return back();
})->name('lang.switch');
```

Dans vos vues :

```blade
<a href="{{ route('lang.switch', 'fr') }}">FR</a>
<a href="{{ route('lang.switch', 'en') }}">EN</a>
```

## Utilisation

### 1. Générer la migration

La commande artisan génère automatiquement la migration qui ajoute les colonnes traduites.
Le champ de base (ex: `note`) sert de version par défaut (français), seule la colonne `_en` est créée avec le même type et les mêmes attributs que la colonne source.

```bash
php artisan translatable:migration {table} {colonnes...}
```

Exemple :

```bash
php artisan translatable:migration categories contenu note
```

Migration générée :

```php
$table->text('name_en')->nullable();
$table->text('description_en')->nullable;
```

```
NB : Les champs des migrations générés auront toujours pour type text et seront toujours nullable
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
use Relodev\Translatable\Traits\HasTranslation;

class category extends Model
{
    use HasTranslation;

    protected $fillable = [
        'name',
        'name_en',
        'description',
        'description_en',
    ];

    // Champs à traduire automatiquement
    protected $translatable = [
        'name',
        'description',
    ];
}
```

### 3. Affichage dans les vues
```blade
{{-- Retourne automatiquement la valeur selon la locale active --}}
{{ $category->name }}
{{ $category->description }}

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

## Méthodes disponibles sur les modèles

| Méthode | Description |
|---------|-------------|
| `$model->champ` | Retourne la valeur dans la locale active |

## Logique de résolution

Quand vous accédez à `$model->note`, le trait suit cet ordre :

```
1. note_{locale_active}   → si non vide, retourner cette valeur
2. note_{fallback_locale} → si non vide, retourner cette valeur
3. note                   → valeur de base en dernier recours
```

## Structure du package

```
src/
├── Traits/
│   └── HasTranslation.php
├── Commands/
│   └── MakeTranslatableCommand.php
├── Middleware/
│   └── SetLocale.php
└── TranslatableServiceProvider.php
config/
└── translatable.php
lang/
├── fr/
└── en/
```

## Changelog

### v1.0.3
- Ajout : middleware `SetLocale` auto-enregistré, gestion de la locale via route, session et fallback config
- Ajout : création automatique des dossiers `lang/fr/` et `lang/en/` à l'installation

### v1.0.2
- Fix : la migration ne génère plus la colonne `_fr` (redondante avec le champ de base)
- Fix : le `down()` de la migration supprime désormais les colonnes correctement
- Fix : la migration détecte et respecte le type et le nullable de la colonne source

### v1.0.1
- Fix : support élargi aux projets Laravel 9

### v1.0.0
- Release initiale

## Licence

MIT