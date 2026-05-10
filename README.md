# Laravel Relodev Translatable

Package Laravel ultra-léger pour gérer la traduction multilingue de vos modèles Eloquent, **sans dépendance lourde** et avec une **langue principale entièrement configurable**.

> **v2.0** — La langue principale n'est plus figée en `fr`. Vous définissez vous-même la langue de base de votre projet et toutes les langues secondaires, à tout moment du développement.

---

## Sommaire

- [Comment ça marche](#comment-ça-marche)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Gestion de la langue active](#gestion-de-la-langue-active)
- [Utilisation](#utilisation)
- [Ajouter une langue en cours de développement](#ajouter-une-langue-en-cours-de-développement)
- [Méthodes disponibles sur les modèles](#méthodes-disponibles-sur-les-modèles)
- [Commandes Artisan](#commandes-artisan)
- [Structure du package](#structure-du-package)
- [Changelog](#changelog)

---

## Comment ça marche

Le package repose sur deux piliers :

- **Données dynamiques** — Des colonnes suffixées en base de données (`name_en`, `name_es`, …) et un trait Eloquent qui retourne automatiquement la bonne valeur selon la locale active. La langue principale utilise **le champ sans suffixe** (`name`), ce qui garantit une compatibilité totale avec votre code existant.
- **Vues & messages** — Traduction classique via les fichiers `lang/{locale}/` de Laravel, inchangés.

Le package inclut un **middleware automatique** qui détecte et applique la langue active sans configuration supplémentaire.

### Convention des colonnes

| Locale | Type | Colonne en BDD |
|--------|------|----------------|
| `fr` (primaire) | langue de base | `name` (sans suffixe) |
| `en` (secondaire) | traduction | `name_en` |
| `es` (secondaire) | traduction | `name_es` |
| `de` (secondaire) | traduction | `name_de` |

---

## Prérequis

- PHP 8.1+
- Laravel 10, 11 ou 12

---

## Installation

```bash
composer require relodev/laravel-translatable
```

Le `ServiceProvider` et le middleware sont auto-enregistrés par Laravel. Aucune configuration manuelle n'est nécessaire.

---

## Configuration

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag=translatable-config
```

Cela crée `config/translatable.php` :

```php
return [
    /*
     | Langue principale du projet.
     | Le champ sans suffixe (ex: `name`) contiendra toujours cette langue.
     */
    'primary_locale' => env('TRANSLATABLE_PRIMARY', 'fr'),

    /*
     | Langues secondaires.
     | Pour chaque langue ici, une colonne suffixée sera générée (ex: name_en).
     | Vous pouvez en ajouter à tout moment en cours de développement.
     */
    'secondary_locales' => ['en'],

    /*
     | Langue de repli si la traduction active est vide.
     | Par défaut, la langue primaire est utilisée comme fallback.
     */
    'fallback_locale' => null,
];
```

| Clé | Description |
|-----|-------------|
| `primary_locale` | Langue "native" de votre projet. La colonne de base (sans suffixe) la contiendra. |
| `secondary_locales` | Tableau de toutes les autres langues. Chacune génère des colonnes suffixées. |
| `fallback_locale` | Langue de repli si la locale active n'a pas de valeur. `null` → utilise `primary_locale`. |

### Exemples de configuration

**Projet anglophone avec traductions FR et ES :**
```php
'primary_locale'    => 'en',
'secondary_locales' => ['fr', 'es'],
```

**Projet trilingue FR / EN / AR :**
```php
'primary_locale'    => 'fr',
'secondary_locales' => ['en', 'ar'],
```

**Via `.env` :**
```env
TRANSLATABLE_PRIMARY=en
TRANSLATABLE_FALLBACK=en
```

---

## Gestion de la langue active

Le middleware inclus détecte automatiquement la langue à appliquer selon cet ordre :

```
1. Segment de route  → /fr/... ou /en/...
2. Session           → définie via le bouton de switch
3. Langue primaire   → valeur de primary_locale
```

### Bouton de switch de langue

Ajoutez cette route dans `routes/web.php` :

```php
Route::get('/langue/{locale}', function ($locale) {
    $all = array_merge(
        [config('translatable.primary_locale')],
        config('translatable.secondary_locales', [])
    );
    if (in_array($locale, $all)) {
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
<a href="{{ route('lang.switch', 'es') }}">ES</a>
```

---

## Utilisation

### 1. Générer la migration initiale

```bash
php artisan translatable:migration {table} {colonnes...}
```

**Exemple** — projet avec `primary_locale = 'fr'` et `secondary_locales = ['en', 'es']` :

```bash
php artisan translatable:migration categories name description
```

Migration générée :

```php
// Colonne `name`        → déjà existante, contient le français (primaire)
$table->text('name_en')->nullable();   // anglais
$table->text('name_es')->nullable();   // espagnol
$table->text('description_en')->nullable();
$table->text('description_es')->nullable();
```

Puis :

```bash
php artisan migrate
```

> **Note :** Les colonnes de la langue primaire (ex: `name`, `description`) sont vos colonnes existantes. Le package ne les touche pas.

### 2. Configurer le modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Relodev\Translatable\Traits\HasTranslation;

class Category extends Model
{
    use HasTranslation;

    protected $fillable = [
        'name',        // langue primaire (fr dans cet exemple)
        'name_en',
        'name_es',
        'description',
        'description_en',
        'description_es',
    ];

    // Champs à résoudre automatiquement selon la locale
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

Le trait résout la valeur dans cet ordre :

```
1. Colonne de la locale active   → name_en  (si locale = 'en')
2. Colonne de la locale fallback → name     (si 'en' est vide)
3. Colonne primaire brute        → name     (en dernier recours)
```

### 4. Traductions statiques (vues, messages)

Fonctionne comme Laravel nativement :

```
lang/
├── fr/
│   └── accueil/
│       └── faq.php
├── en/
│   └── accueil/
│       └── faq.php
└── es/
    └── accueil/
        └── faq.php
```

```blade
{{ __('accueil/faq.no_faqs') }}
```

---

## Ajouter une langue en cours de développement

Vous pouvez ajouter une langue **à tout moment** sans toucher aux données existantes.

### Étape 1 — Ajouter la langue dans la config

```php
// config/translatable.php
'secondary_locales' => ['en', 'es', 'de'], // ← ajout de 'de'
```

### Étape 2 — Générer la migration pour cette nouvelle langue

```bash
php artisan translatable:add-locale de categories name description
```

Cela génère uniquement les colonnes pour `de` :

```php
$table->text('name_de')->nullable();
$table->text('description_de')->nullable();
```

### Étape 3 — Migrer

```bash
php artisan migrate
```

### Étape 4 — Mettre à jour le modèle

```php
protected $fillable = [
    'name', 'name_en', 'name_es', 'name_de', // ← ajout name_de
    'description', 'description_en', 'description_es', 'description_de',
];
```

C'est tout. L'affichage dans les vues (`$category->name`) fonctionne sans modification.

---

## Méthodes disponibles sur les modèles

### `hasTranslation(string $field, ?string $locale = null): bool`

Vérifie si une traduction existe pour une locale donnée.

```php
$model->hasTranslation('name', 'en'); // → true/false
$model->hasTranslation('name');       // → locale active
```

### `setTranslation(string $field, string $locale, mixed $value): static`

Définit la valeur d'un champ pour une locale précise.

```php
$model->setTranslation('name', 'en', 'Hello')->save();
```

### `setTranslations(string $field, array $translations): static`

Remplit plusieurs traductions en une fois.

```php
$model->setTranslations('name', [
    'fr' => 'Bonjour',
    'en' => 'Hello',
    'es' => 'Hola',
])->save();
```

---

## Commandes Artisan

| Commande | Description |
|----------|-------------|
| `translatable:migration {table} {colonnes...}` | Génère la migration pour toutes les langues secondaires |
| `translatable:add-locale {locale} {table} {colonnes...}` | Ajoute une nouvelle langue et génère la migration ciblée |
| `translatable:locales` | Affiche la configuration des langues en vigueur |

### Options de `translatable:migration`

```bash
# Toutes les langues secondaires
php artisan translatable:migration products name description

# Une seule langue secondaire (utile si vous gérez les migrations par langue)
php artisan translatable:migration products name description --only=en
```

### `translatable:locales`

```bash
php artisan translatable:locales
```

Affiche un résumé lisible :

```
  Laravel Relodev Translatable — Configuration des langues

  Langue primaire    : fr
  Langues secondaires: en, es, de
  Fallback           : fr (défaut → primaire)

  Convention des colonnes en BDD :
    - Langue primaire (fr) → colonne sans suffixe   ex: name
    - Langue en            → colonne suffixée _en   ex: name_en
    - Langue es            → colonne suffixée _es   ex: name_es
    - Langue de            → colonne suffixée _de   ex: name_de
```

---

## ⚠️ Logiques métier (create & update)

Le package gère l'**affichage** automatiquement. La cohérence des données lors des écritures reste sous votre responsabilité.

```php
// Création
Category::create([
    'name'       => 'Bonjour',   // langue primaire (fr)
    'name_en'    => 'Hello',
    'name_es'    => 'Hola',
]);

// Mise à jour
$category->update([
    'name_en' => 'Hello World',
]);
```

---

## Structure du package

```
src/
├── Traits/
│   └── HasTranslation.php
├── Commands/
│   ├── MakeTranslatableCommand.php   (translatable:migration)
│   ├── AddLocaleCommand.php          (translatable:add-locale)
│   └── ListLocalesCommand.php        (translatable:locales)
├── Middleware/
│   └── SetLocale.php
└── TranslatableServiceProvider.php
config/
└── translatable.php
lang/
└── (dossiers créés dynamiquement selon la config)
```

---

## Migration depuis la v1

Si vous utilisiez la v1 avec `fr` comme langue principale et `en` comme secondaire, **aucun changement en base de données n'est nécessaire**. Il suffit de :

1. Mettre à jour le package : `composer update relodev/laravel-translatable`
2. Re-publier la config : `php artisan vendor:publish --tag=translatable-config --force`
3. Vérifier que `primary_locale = 'fr'` et `secondary_locales = ['en']`

La clé `fallback_locale` (ancienne) est toujours lue pour compatibilité.

---

## Changelog

### v2.0.0
- **Breaking change (config)** : `fallback_locale` ne définit plus la langue primaire. Utiliser `primary_locale` à la place.
- **Nouveau** : `primary_locale` — la langue principale est désormais entièrement configurable (`fr`, `en`, `es`, ou toute autre langue)
- **Nouveau** : `secondary_locales` — tableau de toutes les langues secondaires ; chacune génère ses colonnes suffixées
- **Nouveau** : commande `translatable:add-locale` — ajoute une langue en cours de développement et génère la migration ciblée
- **Nouveau** : commande `translatable:locales` — affiche la configuration des langues en vigueur
- **Nouveau** : option `--only={locale}` sur `translatable:migration` pour cibler une seule langue
- **Amélioré** : la convention de colonnes est universelle — la langue primaire = colonne sans suffixe, quelle que soit la langue choisie

### v1.0.3
- Ajout : middleware `SetLocale` auto-enregistré, gestion de la locale via route, session et fallback config
- Ajout : création automatique des dossiers `lang/fr/` et `lang/en/` à l'installation

### v1.0.2
- Fix : la migration ne génère plus la colonne `_fr` (redondante avec le champ de base)
- Fix : le `down()` de la migration supprime désormais les colonnes correctement

### v1.0.1
- Fix : support élargi aux projets Laravel 9

### v1.0.0
- Release initiale

---

## Licence

MIT
