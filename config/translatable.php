<?php

return [
    /*
     | Locales supportées par le package
     */
    'supported_locales' => ['fr', 'en'],

    /*
     | Locale de repli si la traduction active est vide
     */
    'fallback_locale' => env('TRANSLATABLE_FALLBACK', 'fr'),

    /*
     | Suffixe utilisé pour les colonnes traduites en BDD
     | Ex: name → name_fr, name_en
     */
    'column_suffix' => true,
];