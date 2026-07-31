<?php
/**
 * Front-end asset registration.
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JCE_Map_Assets
 */
class JCE_Map_Assets {

	/**
	 * Whether assets should be loaded on this request.
	 *
	 * @var bool
	 */
	private static $enqueue = false;

	/**
	 * Hook registrations.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_single' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_enqueue' ), 5 );
	}

	/**
	 * Flag that the shortcode is present and assets are needed.
	 */
	public static function mark_needed() {
		self::$enqueue = true;
	}

	/**
	 * Register (but do not enqueue yet) Leaflet + map assets.
	 */
	public static function register() {
		wp_register_style(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_register_style(
			'jce-map',
			JCE_MAP_URL . 'assets/css/map.css',
			array( 'leaflet' ),
			JCE_MAP_VERSION
		);

		wp_register_script(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_register_script(
			'jce-map',
			JCE_MAP_URL . 'assets/js/map.js',
			array( 'leaflet' ),
			JCE_MAP_VERSION,
			true
		);

		wp_localize_script(
			'jce-map',
			'jceMapData',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'jce/v1/locales' ) ),
				'geojsonUrl'  => esc_url_raw( JCE_MAP_URL . 'data/regions.geojson' ),
				'metaUrl'     => esc_url_raw( JCE_MAP_URL . 'data/regions-meta.json' ),
				'regionCodes' => array_keys( jce_map_get_regions() ),
				'i18n'        => array(
					'allFrance'    => __( 'Toute la France', 'wp-interactive-map-jce' ),
					'locales'      => __( 'locales', 'wp-interactive-map-jce' ),
					'locale'       => __( 'locale', 'wp-interactive-map-jce' ),
					'noLocales'    => __( 'Aucune JCE locale dans cette région.', 'wp-interactive-map-jce' ),
					'viewPage'     => __( 'Voir la fiche', 'wp-interactive-map-jce' ),
					'externalSite' => __( 'Site de la locale', 'wp-interactive-map-jce' ),
					'email'        => __( 'E-mail', 'wp-interactive-map-jce' ),
					'close'        => __( 'Fermer', 'wp-interactive-map-jce' ),
					'loading'      => __( 'Chargement…', 'wp-interactive-map-jce' ),
					'error'        => __( 'Impossible de charger les données de la carte.', 'wp-interactive-map-jce' ),
					'selectRegion' => __( 'Cliquez sur une région pour afficher les locales.', 'wp-interactive-map-jce' ),
				),
			)
		);
	}

	/**
	 * Enqueue styles on locale single pages.
	 */
	public static function enqueue_single() {
		if ( is_singular( JCE_Map_Post_Type::POST_TYPE ) ) {
			wp_enqueue_style( 'jce-map' );
		}
	}

	/**
	 * Enqueue assets only when the shortcode was rendered.
	 */
	public static function maybe_enqueue() {
		if ( ! self::$enqueue ) {
			return;
		}

		wp_enqueue_style( 'jce-map' );
		wp_enqueue_script( 'jce-map' );
	}
}
