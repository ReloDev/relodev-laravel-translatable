# Changelog

## v2.0.0

### Breaking changes
- La clé `fallback_locale` ne définit plus la langue principale. Utiliser `primary_locale`.
- La clé `supported_locales` est remplacée par `primary_locale` + `secondary_locales`.

### Nouveautés
- `primary_locale` : la langue principale est désormais configurable (`fr`, `en`, `es`, …)
- `secondary_locales` : tableau des langues secondaires — chacune génère ses colonnes suffixées
- Commande `translatable:add-locale` : ajoute une langue en cours de développement
- Commande `translatable:locales` : affiche la configuration des langues en vigueur
- Option `--only={locale}` sur `translatable:migration`
- Méthodes `setTranslation()`, `setTranslations()`, `getAllTranslations()`, `getRaw()` sur le trait

## v1.0.3
- Ajout : middleware `SetLocale` auto-enregistré
- Ajout : création automatique des dossiers `lang/{locale}/`

## v1.0.2
- Fix : suppression de la colonne `_fr` redondante dans les migrations
- Fix : `down()` supprime correctement les colonnes

## v1.0.1
- Fix : support Laravel 9

## v1.0.0
- Release initiale
