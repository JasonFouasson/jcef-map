<?php
/**
 * CPT jce_locale, taxonomy jce_region, metabox and admin columns.
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JCE_Map_Post_Type
 */
class JCE_Map_Post_Type {

	const POST_TYPE = 'jce_locale';
	const TAXONOMY  = 'jce_region';

	/**
	 * Meta keys stored on each locale.
	 *
	 * @var string[]
	 */
	const META_KEYS = array(
		'lat',
		'lng',
		'address',
		'city',
		'postal_code',
		'email',
		'phone',
		'website',
		'external_url',
	);

	/**
	 * Hook registrations.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'single_template', array( __CLASS__, 'single_template' ) );
	}

	/**
	 * Register CPT and taxonomy.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'JCE Locales', 'wp-interactive-map-jce' ),
					'singular_name'      => __( 'JCE Locale', 'wp-interactive-map-jce' ),
					'add_new'            => __( 'Ajouter', 'wp-interactive-map-jce' ),
					'add_new_item'       => __( 'Ajouter une JCE Locale', 'wp-interactive-map-jce' ),
					'edit_item'          => __( 'Modifier la JCE Locale', 'wp-interactive-map-jce' ),
					'new_item'           => __( 'Nouvelle JCE Locale', 'wp-interactive-map-jce' ),
					'view_item'          => __( 'Voir la JCE Locale', 'wp-interactive-map-jce' ),
					'search_items'       => __( 'Rechercher des locales', 'wp-interactive-map-jce' ),
					'not_found'          => __( 'Aucune locale trouvée', 'wp-interactive-map-jce' ),
					'not_found_in_trash' => __( 'Aucune locale dans la corbeille', 'wp-interactive-map-jce' ),
					'menu_name'          => __( 'JCE Locales', 'wp-interactive-map-jce' ),
					'all_items'          => __( 'Toutes les locales', 'wp-interactive-map-jce' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-location-alt',
				'menu_position'       => 25,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'             => array(
					'slug'       => 'jce-locale',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Régions', 'wp-interactive-map-jce' ),
					'singular_name' => __( 'Région', 'wp-interactive-map-jce' ),
					'search_items'  => __( 'Rechercher des régions', 'wp-interactive-map-jce' ),
					'all_items'     => __( 'Toutes les régions', 'wp-interactive-map-jce' ),
					'edit_item'     => __( 'Modifier la région', 'wp-interactive-map-jce' ),
					'update_item'   => __( 'Mettre à jour la région', 'wp-interactive-map-jce' ),
					'add_new_item'  => __( 'Ajouter une région', 'wp-interactive-map-jce' ),
					'new_item_name' => __( 'Nouvelle région', 'wp-interactive-map-jce' ),
					'menu_name'     => __( 'Régions', 'wp-interactive-map-jce' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug' => 'jce-region',
				),
			)
		);
	}

	/**
	 * Register the coordinates & contact metabox.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'jce_locale_details',
			__( 'Coordonnées & contact', 'wp-interactive-map-jce' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox fields.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'jce_locale_save_meta', 'jce_locale_meta_nonce' );

		$values = array();
		foreach ( self::META_KEYS as $key ) {
			$values[ $key ] = get_post_meta( $post->ID, '_jce_' . $key, true );
		}
		?>
		<style>
			.jce-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
			.jce-meta-grid label { display: block; font-weight: 600; margin-bottom: 4px; }
			.jce-meta-grid input { width: 100%; }
			.jce-meta-grid .full { grid-column: 1 / -1; }
		</style>
		<div class="jce-meta-grid">
			<p>
				<label for="jce_lat"><?php esc_html_e( 'Latitude *', 'wp-interactive-map-jce' ); ?></label>
				<input type="number" step="any" id="jce_lat" name="jce_lat" value="<?php echo esc_attr( $values['lat'] ); ?>" required />
			</p>
			<p>
				<label for="jce_lng"><?php esc_html_e( 'Longitude *', 'wp-interactive-map-jce' ); ?></label>
				<input type="number" step="any" id="jce_lng" name="jce_lng" value="<?php echo esc_attr( $values['lng'] ); ?>" required />
			</p>
			<p class="full">
				<label for="jce_address"><?php esc_html_e( 'Adresse', 'wp-interactive-map-jce' ); ?></label>
				<input type="text" id="jce_address" name="jce_address" value="<?php echo esc_attr( $values['address'] ); ?>" />
			</p>
			<p>
				<label for="jce_city"><?php esc_html_e( 'Ville', 'wp-interactive-map-jce' ); ?></label>
				<input type="text" id="jce_city" name="jce_city" value="<?php echo esc_attr( $values['city'] ); ?>" />
			</p>
			<p>
				<label for="jce_postal_code"><?php esc_html_e( 'Code postal', 'wp-interactive-map-jce' ); ?></label>
				<input type="text" id="jce_postal_code" name="jce_postal_code" value="<?php echo esc_attr( $values['postal_code'] ); ?>" />
			</p>
			<p>
				<label for="jce_email"><?php esc_html_e( 'E-mail', 'wp-interactive-map-jce' ); ?></label>
				<input type="email" id="jce_email" name="jce_email" value="<?php echo esc_attr( $values['email'] ); ?>" />
			</p>
			<p>
				<label for="jce_phone"><?php esc_html_e( 'Téléphone', 'wp-interactive-map-jce' ); ?></label>
				<input type="text" id="jce_phone" name="jce_phone" value="<?php echo esc_attr( $values['phone'] ); ?>" />
			</p>
			<p>
				<label for="jce_website"><?php esc_html_e( 'Site web', 'wp-interactive-map-jce' ); ?></label>
				<input type="url" id="jce_website" name="jce_website" value="<?php echo esc_attr( $values['website'] ); ?>" placeholder="https://" />
			</p>
			<p>
				<label for="jce_external_url"><?php esc_html_e( 'URL externe (lien secondaire)', 'wp-interactive-map-jce' ); ?></label>
				<input type="url" id="jce_external_url" name="jce_external_url" value="<?php echo esc_attr( $values['external_url'] ); ?>" placeholder="https://" />
			</p>
		</div>
		<p class="description"><?php esc_html_e( 'Les champs latitude et longitude sont requis pour afficher la locale sur la carte.', 'wp-interactive-map-jce' ); ?></p>
		<?php
	}

	/**
	 * Persist metabox fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['jce_locale_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jce_locale_meta_nonce'] ) ), 'jce_locale_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$float_keys = array( 'lat', 'lng' );
		$url_keys   = array( 'website', 'external_url' );
		$email_keys = array( 'email' );

		foreach ( self::META_KEYS as $key ) {
			$field = 'jce_' . $key;
			$raw   = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';

			if ( in_array( $key, $float_keys, true ) ) {
				$value = ( '' === $raw || null === $raw ) ? '' : (string) (float) $raw;
			} elseif ( in_array( $key, $url_keys, true ) ) {
				$value = esc_url_raw( $raw );
			} elseif ( in_array( $key, $email_keys, true ) ) {
				$value = sanitize_email( $raw );
			} else {
				$value = sanitize_text_field( $raw );
			}

			update_post_meta( $post_id, '_jce_' . $key, $value );
		}
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['jce_city']   = __( 'Ville', 'wp-interactive-map-jce' );
				$new['jce_coords'] = __( 'Coordonnées', 'wp-interactive-map-jce' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'jce_city' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_jce_city', true ) );
			return;
		}

		if ( 'jce_coords' === $column ) {
			$lat = get_post_meta( $post_id, '_jce_lat', true );
			$lng = get_post_meta( $post_id, '_jce_lng', true );
			if ( '' !== $lat && '' !== $lng ) {
				echo esc_html( $lat . ', ' . $lng );
			} else {
				echo '—';
			}
		}
	}

	/**
	 * Use plugin single template when theme has none.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public static function single_template( $template ) {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return $template;
		}

		$theme_template = locate_template( array( 'single-jce_locale.php' ) );
		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = JCE_MAP_PATH . 'templates/single-jce_locale.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Read all meta for a locale as a keyed array.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string>
	 */
	public static function get_locale_meta( $post_id ) {
		$data = array();
		foreach ( self::META_KEYS as $key ) {
			$data[ $key ] = (string) get_post_meta( $post_id, '_jce_' . $key, true );
		}
		return $data;
	}
}
