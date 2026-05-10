<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Langue principale
    |--------------------------------------------------------------------------
    | C'est la langue "native" de votre projet. Elle sert de valeur de base :
    | $entity->field retourne directement la valeur de cette langue.
    |
    | En base de données, la colonne principale (ex: `name`) contiendra
    | toujours la valeur dans cette langue.
    |
    | Exemples : 'fr', 'en', 'es', 'de', 'ar', ...
    */
    'primary_locale' => env('TRANSLATABLE_PRIMARY', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Langues secondaires
    |--------------------------------------------------------------------------
    | Liste de toutes les autres langues supportées par votre projet.
    | Pour chaque langue secondaire, une colonne suffixée sera créée en BDD.
    |
    | Exemple : si primary_locale = 'fr' et secondary_locales = ['en', 'es'],
    | les colonnes générées seront : name (fr), name_en, name_es
    |
    | Vous pouvez en ajouter à tout moment en cours de développement :
    | ajoutez la langue ici, puis relancez : php artisan translatable:migration
    |
    | Exemples : ['en', 'es', 'de', 'ar', 'pt', 'it', 'uk', ...]
    */
    'secondary_locales' => ['en'],

    /*
    |--------------------------------------------------------------------------
    | Locale de repli (fallback)
    |--------------------------------------------------------------------------
    | Si la traduction dans la locale active est vide, le package cherche
    | une valeur dans cette locale de repli avant de tomber sur le champ brut.
    |
    | Par défaut, on utilise la langue principale comme fallback.
    */
    'fallback_locale' => env('TRANSLATABLE_FALLBACK', null),

];
