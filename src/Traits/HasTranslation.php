<?php

namespace Relodev\Translatable\Traits;

trait HasTranslation
{
    /**
     * Retourne toutes les locales supportées (primaire + secondaires).
     */
    protected function getAllLocales(): array
    {
        $primary = config('translatable.primary_locale', 'fr');
        $secondary = config('translatable.secondary_locales', []);

        return array_unique(array_merge([$primary], $secondary));
    }

    /**
     * Retourne la locale primaire du projet.
     */
    protected function getPrimaryLocale(): string
    {
        return config('translatable.primary_locale', 'fr');
    }

    /**
     * Retourne la locale de fallback (primaire par défaut).
     */
    protected function getFallbackLocale(): string
    {
        return config('translatable.fallback_locale')
            ?? $this->getPrimaryLocale();
    }

    /**
     * Retourne le nom réel de la colonne en base de données pour un champ
     * et une locale donnée.
     *
     * - Langue primaire  → le champ "nu" (ex: `name`)
     * - Langue secondaire → le champ suffixé (ex: `name_en`)
     */
    protected function getColumnForLocale(string $field, string $locale): string
    {
        if ($locale === $this->getPrimaryLocale()) {
            return $field;
        }

        return $field . '_' . $locale;
    }

    /**
     * Résout la valeur traduite d'un champ selon la locale active.
     *
     * Ordre de résolution :
     *  1. Colonne de la locale active (ex: name_en ou name si primaire)
     *  2. Colonne de la locale de fallback
     *  3. Colonne primaire brute
     */
    public function translateField(string $field): mixed
    {
        $locale   = app()->getLocale();
        $fallback = $this->getFallbackLocale();
        $primary  = $this->getPrimaryLocale();
        $all      = $this->getAllLocales();

        // 1. Locale active
        if (in_array($locale, $all)) {
            $column = $this->getColumnForLocale($field, $locale);
            if (!empty($this->attributes[$column] ?? null)) {
                return $this->attributes[$column];
            }
        }

        // 2. Fallback
        if ($fallback !== $locale) {
            $fallbackColumn = $this->getColumnForLocale($field, $fallback);
            if (!empty($this->attributes[$fallbackColumn] ?? null)) {
                return $this->attributes[$fallbackColumn];
            }
        }

        // 3. Champ primaire brut (sûreté)
        return $this->attributes[$field] ?? null;
    }

    /**
     * Intercepte les accès Eloquent aux attributs traduisibles.
     */
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
     * Retourne la valeur brute d'un champ pour une locale précise,
     * utile dans les formulaires d'édition.
     *
     * Exemple : $model->getRaw('name', 'en') → valeur de name_en
     *           $model->getRaw('name')        → valeur de name_{locale_active}
     */
    public function getRaw(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $column = $this->getColumnForLocale($field, $locale);

        return $this->attributes[$column] ?? null;
    }

    /**
     * Retourne toutes les valeurs traduites d'un champ, indexées par locale.
     *
     * Exemple : $model->getAllTranslations('name')
     * → ['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']
     */
    public function getAllTranslations(string $field): array
    {
        $result = [];
        foreach ($this->getAllLocales() as $locale) {
            $column = $this->getColumnForLocale($field, $locale);
            $result[$locale] = $this->attributes[$column] ?? null;
        }
        return $result;
    }

    /**
     * Vérifie si une traduction existe pour une locale donnée.
     */
    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $locale ??= app()->getLocale();
        $column = $this->getColumnForLocale($field, $locale);
        return !empty($this->attributes[$column] ?? null);
    }

    /**
     * Définit la valeur traduite d'un champ pour une locale précise.
     *
     * Exemple : $model->setTranslation('name', 'en', 'Hello')
     */
    public function setTranslation(string $field, string $locale, mixed $value): static
    {
        $column = $this->getColumnForLocale($field, $locale);
        $this->attributes[$column] = $value;
        return $this;
    }

    /**
     * Remplit plusieurs traductions d'un champ en une seule fois.
     *
     * Exemple :
     * $model->setTranslations('name', ['fr' => 'Bonjour', 'en' => 'Hello'])
     */
    public function setTranslations(string $field, array $translations): static
    {
        foreach ($translations as $locale => $value) {
            $this->setTranslation($field, $locale, $value);
        }
        return $this;
    }

    /**
     * Compatibilité avec l'ancien nom getRawAttribute().
     *
     * @deprecated Utilisez getRaw() à la place.
     */
    public function getRawAttribute(string $field, ?string $locale = null): mixed
    {
        return $this->getRaw($field, $locale);
    }
}
