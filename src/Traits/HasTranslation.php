<?php

namespace Relodev\Translatable\Traits;

trait HasTranslation
{
    public function translateField(string $field): mixed
    {
        $locale = app()->getLocale();
        $fallback = config('translatable.fallback_locale', config('app.fallback_locale', 'fr'));
        $supported = config('translatable.supported_locales', ['fr', 'en']);

        // Locale active
        if (in_array($locale, $supported)) {
            $localeField = $field . '_' . $locale;
            if (!empty($this->attributes[$localeField] ?? null)) {
                return $this->attributes[$localeField];
            }
        }

        // Fallback
        $fallbackField = $field . '_' . $fallback;
        if (!empty($this->attributes[$fallbackField] ?? null)) {
            return $this->attributes[$fallbackField];
        }

        // Champ de base
        return $this->attributes[$field] ?? null;
    }

    public function getAttribute($key): mixed
    {
        if (
            property_exists($this, 'translatable') &&
            in_array($key, $this->translatable)
        ) {
            return $this->translateField($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Obtenir la valeur brute sans traduction (utile dans les formulaires d'édition)
     */
    public function getRawAttribute(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $localeField = $field . '_' . $locale;

        return $this->attributes[$localeField]
            ?? $this->attributes[$field]
            ?? null;
    }

    /**
     * Savoir si une traduction existe pour une locale donnée
     */
    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $locale ??= app()->getLocale();
        return !empty($this->attributes[$field . '_' . $locale] ?? null);
    }
}