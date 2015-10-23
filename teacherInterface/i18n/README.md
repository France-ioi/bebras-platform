# Bebras platform internationalization

This file briefly describes the translation system used by the platform, as well
as the system used for regions (« académies » in French system) and official
domain names linked to a country.

## Translation

The translation is made through [i18next](http://i18next.com/). Main translations
are present in simple json files, one by language:

- `teacherInterface/i18n/xx/translation.json`
- `contestInterface/i18n/xx/translation.json`

where xx is the *ISO639* code corresponding to the language (such as fr for
French). It is also possible to have variants by having the whole locale (such
as fr_FR for French language specific to France).

The i18next system detects the locale of the browser (like `fr_FR`), tries to
fetch the translations in this locale. If absent, it will look for the language
only (like `fr`) and then will default to `fr`.


## Country-specific settings

France has a system of « académies », which are administrative regions linked to
educations. Each academy has a DNS domain name, and the platform checks for
emails with these domains for administrators.

These regions and domains depend on the country and must be translated. The
system used is the following:

- set `$config->teacherInterface->countryCode` in `config_local.php`, it must be
  the two-letter code of *ISO 3166-1-alpha-2*.
- create `teacherInterface/regions/XX/regions.js` (where XX is the country code)
   containing the regions (take the FR example)
- create `teacherInterface/regions/XX/domains.json` containing the list of
   official domains in json (make it an empty json array if there are no such
   domains)
- create `teacherInterface/i18n/xx/regionsXX.json` containing the translation
   of the regions (take FR as an example) in the different languages (xx)
