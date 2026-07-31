## Learned User Preferences

- Prefer a WordPress plugin with CPT + admin for JCE locales (not JSON/CSV-only or external API as the primary data source).
- Locale detail links should prefer the WordPress single page when present, otherwise fall back to an external URL.
- Group all overseas territories under one Outre-mer region (not separate DROM regions on the map).
- Region labels on the map must be shown in French.
- In the region click panel listing JCE locales, show email instead of GPS coordinates.

## Learned Workspace Facts

- This repo is the WordPress plugin « Carte Interactive JCE France » (`wp-interactive-map-jce`), bootstrap at `wp-interactive-map-jce.php`.
- Front map is Leaflet + GeoJSON (`data/regions.geojson`); shortcode is `[jce_map]`.
- Locales are CPT `jce_locale` with taxonomy `jce_region`; REST and assets live under `includes/` and `assets/`.
- Region set is 13 métropole codes plus Outre-mer (`om`); legacy DROM slugs `01`–`06` are migrated into Outre-mer.
