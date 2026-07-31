<?php
/**
 * Plugin Name:       Carte Interactive JCE France
 * Plugin URI:        https://github.com/jce/wp-interactive-map-jce
 * Description:       Carte interactive de la France (métropole + outre-mer) pour afficher les Jeunes Chambres Économiques Locales par région.
 * Version:           1.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            JCE
 * License:           GPL-2.0-or-later
 * Text Domain:       wp-interactive-map-jce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JCE_MAP_VERSION', '1.1.2' );
define( 'JCE_MAP_DB_VERSION', '1.1.0' );
define( 'JCE_MAP_FILE', __FILE__ );
define( 'JCE_MAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'JCE_MAP_URL', plugin_dir_url( __FILE__ ) );

require_once JCE_MAP_PATH . 'includes/class-post-type.php';
require_once JCE_MAP_PATH . 'includes/class-rest-api.php';
require_once JCE_MAP_PATH . 'includes/class-assets.php';
require_once JCE_MAP_PATH . 'includes/class-shortcode.php';

/**
 * Bootstrap the plugin.
 */
function jce_map_init() {
	JCE_Map_Post_Type::init();
	JCE_Map_REST_API::init();
	JCE_Map_Assets::init();
	JCE_Map_Shortcode::init();
}
add_action( 'plugins_loaded', 'jce_map_init' );

/**
 * Region terms: 13 métropole + 1 Outre-mer (DROM regroupés).
 *
 * @return array<string, string> code => label
 */
function jce_map_get_regions() {
	return array(
		'11' => 'Île-de-France',
		'24' => 'Centre-Val de Loire',
		'27' => 'Bourgogne-Franche-Comté',
		'28' => 'Normandie',
		'32' => 'Hauts-de-France',
		'44' => 'Grand Est',
		'52' => 'Pays de la Loire',
		'53' => 'Bretagne',
		'75' => 'Nouvelle-Aquitaine',
		'76' => 'Occitanie',
		'84' => 'Auvergne-Rhône-Alpes',
		'93' => "Provence-Alpes-Côte d'Azur",
		'94' => 'Corse',
		'om' => 'Outre-mer',
	);
}

/**
 * Former DROM term slugs merged into Outre-mer.
 *
 * @return string[]
 */
function jce_map_get_legacy_drom_slugs() {
	return array( '01', '02', '03', '04', '06' );
}

/**
 * Activation: flush rewrites and seed region terms.
 */
function jce_map_activate() {
	JCE_Map_Post_Type::register();
	jce_map_seed_regions();
	jce_map_migrate_outre_mer();
	update_option( 'jce_map_db_version', JCE_MAP_DB_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'jce_map_activate' );

/**
 * Deactivation: flush rewrites.
 */
function jce_map_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'jce_map_deactivate' );

/**
 * Run migrations for already-active installs.
 */
function jce_map_maybe_upgrade() {
	$current = get_option( 'jce_map_db_version', '1.0.0' );
	if ( version_compare( (string) $current, JCE_MAP_DB_VERSION, '>=' ) ) {
		return;
	}
	jce_map_seed_regions();
	jce_map_migrate_outre_mer();
	update_option( 'jce_map_db_version', JCE_MAP_DB_VERSION );
}
add_action( 'admin_init', 'jce_map_maybe_upgrade' );

/**
 * Create jce_region taxonomy terms if missing.
 */
function jce_map_seed_regions() {
	foreach ( jce_map_get_regions() as $code => $label ) {
		if ( ! term_exists( $code, 'jce_region' ) ) {
			wp_insert_term(
				$label,
				'jce_region',
				array(
					'slug' => $code,
				)
			);
		}
	}
}

/**
 * Merge legacy DROM terms into Outre-mer (om).
 */
function jce_map_migrate_outre_mer() {
	$om = term_exists( 'om', 'jce_region' );
	if ( ! $om ) {
		$inserted = wp_insert_term(
			'Outre-mer',
			'jce_region',
			array(
				'slug' => 'om',
			)
		);
		if ( is_wp_error( $inserted ) ) {
			return;
		}
		$om_term_id = (int) $inserted['term_id'];
	} else {
		$om_term_id = (int) ( is_array( $om ) ? $om['term_id'] : $om );
	}

	foreach ( jce_map_get_legacy_drom_slugs() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'jce_region' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$object_ids = get_objects_in_term( (int) $term->term_id, 'jce_region' );
		if ( ! is_wp_error( $object_ids ) && $object_ids ) {
			foreach ( $object_ids as $object_id ) {
				wp_set_object_terms( (int) $object_id, array( $om_term_id ), 'jce_region', false );
			}
		}

		wp_delete_term( (int) $term->term_id, 'jce_region' );
	}
}
