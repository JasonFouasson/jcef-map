=== Carte Interactive JCE France ===
Contributors: jce
Tags: map, france, jce, leaflet, regions
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Carte interactive de la France (métropole + outre-mer) pour les Jeunes Chambres Économiques Locales.

== Description ==

Ce plugin ajoute :

* Un Custom Post Type **JCE Locales** avec taxonomie **Régions** (13 métropole + Outre-mer).
* Une carte Leaflet via le shortcode `[jce_map]`.
* Un panneau listant les locales d'une région au clic (e-mail, fiche WP, lien externe).
* Des encarts DROM regroupés sous la région unique **Outre-mer**.
* Mises à jour automatiques depuis GitHub (releases).

== Installation ==

1. Copiez le dossier `wp-interactive-map-jce` dans `wp-content/plugins/`.
2. Activez le plugin dans Extensions.
3. Ajoutez des locales (menu JCE Locales) avec région, latitude et longitude.
4. Insérez `[jce_map]` dans une page.

== Mises à jour ==

Les mises à jour sont détectées depuis les **Releases** du dépôt GitHub :
https://github.com/JasonFouasson/jcef-map/releases

Pour publier une nouvelle version :

1. Incrémentez `Version` dans `wp-interactive-map-jce.php` et `Stable tag` dans `readme.txt`.
2. Poussez sur `main`.
3. Créez une Release GitHub avec un tag version (ex. `v1.2.0` ou `1.2.0`).
4. Joignez l’asset ZIP `wp-interactive-map-jce.zip` (ou laissez GitHub Actions le générer).
5. Dans WordPress : Tableau de bord → Mises à jour (ou Extensions).

== Shortcode ==

`[jce_map height="600" region=""]`

* `height` — hauteur (px ou unité CSS, ex. `70vh`).
* `region` — code région optionnel (ex. `84`, `om`).

== Données géographiques ==

Contours issus d'Etalab (Admin Express / data.gouv.fr), fichier `regions-1000m.geojson` vendored dans `data/regions.geojson`.

== Changelog ==

= 1.2.0 =
* Mises à jour automatiques depuis GitHub Releases (Plugin Update Checker).

= 1.1.2 =
* Affiche l'e-mail dans le panneau région (plus les GPS).

= 1.1.1 =
* Libellés des régions en français sur la carte.

= 1.1.0 =
* Regroupe les DROM sous une seule région « Outre-mer » (slug `om`).

= 1.0.0 =
* Version initiale.
