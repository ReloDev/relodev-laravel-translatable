# Laravel Relodev Translatable

A lightweight Laravel package to handle multilingual Eloquent models, **with no heavy dependencies** and a **fully configurable primary language**.

> **v2.0** — The primary language is no longer hardcoded to `fr`. You define your own base language and all secondary languages, at any point during development.

---

## Table of Contents

- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Active locale detection](#active-locale-detection)
- [Usage](#usage)
- [Adding a language during development](#adding-a-language-during-development)
- [Available model methods](#available-model-methods)
- [Artisan commands](#artisan-commands)
- [Package structure](#package-structure)
- [Changelog](#changelog)

---

## How it works

The package is built on two pillars:

- **Dynamic data** — Suffixed database columns (`name_en`, `name_es`, …) and an Eloquent trait that automatically returns the right value based on the active locale. The primary language uses the **unsuffixed column** (`name`), ensuring full compatibility with your existing code.
- **Views & messages** — Standard Laravel translation files under `lang/{locale}/`, unchanged.

An **automatic middleware** is included to detect and apply the active locale with no extra setup.

### Column naming convention

| Locale | Type | Database column |
|--------|------|-----------------|
| `fr` (primary) | base language | `name` (no suffix) |
| `en` (secondary) | translation | `name_en` |
| `es` (secondary) | translation | `name_es` |
| `de` (secondary) | translation | `name_de` |

---

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12 or 13

---

## Installation

```bash
composer require relodev/laravel-translatable
```

The `ServiceProvider` and middleware are auto-registered by Laravel. No manual setup required.

---

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=translatable-config
```

This creates `config/translatable.php`:

```php
return [
    /*
     | Primary language of your project.
     | The unsuffixed column (e.g. `name`) will always hold this language.
     */
    'primary_locale' => env('TRANSLATABLE_PRIMARY', 'fr'),

    /*
     | Secondary languages.
     | Each language listed here will generate a suffixed column (e.g. name_en).
     | You can add more at any point during development.
     */
    'secondary_locales' => ['en'],

    /*
     | Fallback locale used when the active locale has no value.
     | Defaults to primary_locale when set to null.
     */
    'fallback_locale' => null,
];
```

| Key | Description |
|-----|-------------|
| `primary_locale` | The native language of your project. The base column (no suffix) holds this language. |
| `secondary_locales` | All other supported languages. Each one generates suffixed columns. |
| `fallback_locale` | Fallback when the active locale has no value. `null` → falls back to `primary_locale`. |

### Configuration examples

**English-first project with FR and ES translations:**
```php
'primary_locale'    => 'en',
'secondary_locales' => ['fr', 'es'],
```

**Trilingual project FR / EN / AR:**
```php
'primary_locale'    => 'fr',
'secondary_locales' => ['en', 'ar'],
```

**Via `.env`:**
```env
TRANSLATABLE_PRIMARY=en
TRANSLATABLE_FALLBACK=en
```

---

## Active locale detection

The included middleware automatically resolves the locale in this order:

```
1. Route segment   → /fr/... or /en/...
2. Session         → set via language switch button
3. Primary locale  → value of primary_locale
```

### Language switch button

Add this route to `routes/web.php`:

```php
Route::get('/language/{locale}', function ($locale) {
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

In your views:

```blade
<a href="{{ route('lang.switch', 'fr') }}">FR</a>
<a href="{{ route('lang.switch', 'en') }}">EN</a>
<a href="{{ route('lang.switch', 'es') }}">ES</a>
```

---

## Usage

### 1. Generate the initial migration

```bash
php artisan translatable:migration {table} {columns...}
```

**Example** — project with `primary_locale = 'fr'` and `secondary_locales = ['en', 'es']`:

```bash
php artisan translatable:migration categories name description
```

Generated migration:

```php
// `name` column → already exists, holds French (primary)
$table->text('name_en')->nullable();          // English
$table->text('name_es')->nullable();          // Spanish
$table->text('description_en')->nullable();
$table->text('description_es')->nullable();
```

Then:

```bash
php artisan migrate
```

> **Note:** Primary language columns (e.g. `name`, `description`) are your existing columns. The package does not touch them.

### 2. Set up the model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Relodev\Translatable\Traits\HasTranslation;

class Category extends Model
{
    use HasTranslation;

    protected $fillable = [
        'name',        // primary language (fr in this example)
        'name_en',
        'name_es',
        'description',
        'description_en',
        'description_es',
    ];

    // Fields resolved automatically based on the active locale
    protected $translatable = [
        'name',
        'description',
    ];
}
```

### 3. Display in views

```blade
{{-- Automatically returns the value for the active locale --}}
{{ $category->name }}
{{ $category->description }}
```

The trait resolves the value in this order:

```
1. Active locale column    → name_en  (if locale = 'en')
2. Fallback locale column  → name     (if 'en' is empty)
3. Raw primary column      → name     (last resort)
```

### 4. Static translations (views, messages)

Works exactly like native Laravel:

```
lang/
├── fr/
│   └── home/
│       └── faq.php
├── en/
│   └── home/
│       └── faq.php
└── es/
    └── home/
        └── faq.php
```

```blade
{{ __('home/faq.no_faqs') }}
```

---

## Adding a language during development

You can add a language **at any time** without touching existing data.

### Step 1 — Add the language to the config

```php
// config/translatable.php
'secondary_locales' => ['en', 'es', 'de'], // ← adding 'de'
```

### Step 2 — Generate the migration for the new language

```bash
php artisan translatable:add-locale de categories name description
```

This generates only the columns for `de`:

```php
$table->text('name_de')->nullable();
$table->text('description_de')->nullable();
```

### Step 3 — Run the migration

```bash
php artisan migrate
```

### Step 4 — Update the model

```php
protected $fillable = [
    'name', 'name_en', 'name_es', 'name_de', // ← add name_de
    'description', 'description_en', 'description_es', 'description_de',
];
```

That's it. Views (`$category->name`) work without any changes.

---

## Available model methods

### `hasTranslation(string $field, ?string $locale = null): bool`

Checks whether a translation exists for a given locale.

```php
$model->hasTranslation('name', 'en'); // → true/false
$model->hasTranslation('name');       // → checks active locale
```

---

## Artisan commands

| Command | Description |
|---------|-------------|
| `translatable:migration {table} {columns...}` | Generates the migration for all secondary languages |
| `translatable:add-locale {locale} {table} {columns...}` | Adds a new language and generates the targeted migration |
| `translatable:locales` | Displays the current locale configuration |

### `translatable:migration` options

```bash
# All secondary languages
php artisan translatable:migration products name description

# A single secondary language
php artisan translatable:migration products name description --only=en
```

### `translatable:locales`

```bash
php artisan translatable:locales
```

Outputs a readable summary:

```
  Laravel Relodev Translatable — Locale configuration

  Primary locale     : fr
  Secondary locales  : en, es, de
  Fallback           : fr (default → primary)

  Database column convention:
    - Primary locale (fr) → unsuffixed column    e.g. name
    - Locale en           → suffixed column _en   e.g. name_en
    - Locale es           → suffixed column _es   e.g. name_es
    - Locale de           → suffixed column _de   e.g. name_de
```

---

## ⚠️ Business logic (create & update)

The package handles **display** automatically. Data consistency on writes remains your responsibility.

```php
// Create
Category::create([
    'name'    => 'Bonjour',  // primary language (fr)
    'name_en' => 'Hello',
    'name_es' => 'Hola',
]);

// Update
$category->update([
    'name_en' => 'Hello World',
]);
```

---

## Package structure

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
└── (directories created dynamically based on config)
```

---

## Upgrading from v1

If you were using v1 with `fr` as the primary language and `en` as secondary, **no database changes are needed**. Simply:

1. Update the package: `composer update relodev/laravel-translatable`
2. Re-publish the config: `php artisan vendor:publish --tag=translatable-config --force`
3. Confirm `primary_locale = 'fr'` and `secondary_locales = ['en']`

---

## Changelog

### v2.0.0
- **Breaking change (config):** `fallback_locale` no longer defines the primary language — use `primary_locale` instead
- **New:** `primary_locale` — the primary language is now fully configurable (`fr`, `en`, `es`, or any other)
- **New:** `secondary_locales` — array of all secondary languages; each generates its own suffixed columns
- **New:** `translatable:add-locale` command — adds a language mid-development and generates the targeted migration
- **New:** `translatable:locales` command — displays the current locale configuration
- **New:** `--only={locale}` option on `translatable:migration`
- **Improved:** column convention is now universal — primary language always maps to the unsuffixed column, regardless of which language is primary

### v1.0.3
- Added: auto-registered `SetLocale` middleware with route segment, session and config fallback detection
- Added: automatic creation of `lang/{locale}/` directories on install

### v1.0.2
- Fix: migration no longer generates the redundant `_fr` column
- Fix: `down()` correctly drops the generated columns

### v1.0.1
- Fix: extended support to Laravel 9

### v1.0.0
- Initial release

---

## License

MIT
