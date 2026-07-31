=== Carte Interactive JCE France ===
Contributors: jce
Tags: map, france, jce, leaflet, regions
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Carte interactive de la France (métropole + outre-mer) pour les Jeunes Chambres Économiques Locales.

== Description ==

Ce plugin ajoute :

* Un Custom Post Type **JCE Locales** avec taxonomie **Régions** (13 métropole + Outre-mer).
* Une carte Leaflet via le shortcode `[jce_map]`.
* Un panneau listant les locales d'une région au clic (coordonnées, fiche WP, lien externe).
* Des encarts DROM regroupés sous la région unique **Outre-mer**.

== Installation ==

1. Copiez le dossier `wp-interactive-map-jce` dans `wp-content/plugins/`.
2. Activez le plugin dans Extensions.
3. Ajoutez des locales (menu JCE Locales) avec région, latitude et longitude.
4. Insérez `[jce_map]` dans une page.

== Shortcode ==

`[jce_map height="600" region=""]`

* `height` — hauteur (px ou unité CSS, ex. `70vh`).
* `region` — code INSEE optionnel pour pré-sélectionner une région (ex. `84`).

== Données géographiques ==

Contours issus d'Etalab (Admin Express / data.gouv.fr), fichier `regions-1000m.geojson` vendored dans `data/regions.geojson`.

== Changelog ==

= 1.1.0 =
* Regroupe les DROM sous une seule région « Outre-mer » (slug `om`).

= 1.0.0 =
* Version initiale.
