<?php
/**
 * Shortcode [jce_map].
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JCE_Map_Shortcode
 */
class JCE_Map_Shortcode {

	/**
	 * Hook registrations.
	 */
	public static function init() {
		add_shortcode( 'jce_map', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the interactive map container.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'height' => '600',
				'region' => '',
			),
			$atts,
			'jce_map'
		);

		JCE_Map_Assets::mark_needed();

		wp_enqueue_style( 'jce-map' );
		wp_enqueue_script( 'jce-map' );

		$height = trim( (string) $atts['height'] );
		if ( is_numeric( $height ) ) {
			$height .= 'px';
		}
		$height = preg_match( '/^\d+(\.\d+)?(px|vh|rem|em|%)$/', $height ) ? $height : '600px';

		$region = sanitize_text_field( (string) $atts['region'] );
		$id     = 'jce-map-' . ( function_exists( 'wp_unique_id' ) ? wp_unique_id() : uniqid( '', false ) );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $id ); ?>"
			class="jce-map-app"
			data-region="<?php echo esc_attr( $region ); ?>"
			style="--jce-map-height: <?php echo esc_attr( $height ); ?>"
		>
			<div class="jce-map-layout">
				<div class="jce-map-main">
					<div class="jce-map-canvas" role="application" aria-label="<?php esc_attr_e( 'Carte des Jeunes Chambres Économiques de France', 'wp-interactive-map-jce' ); ?>"></div>
					<div class="jce-map-insets" aria-hidden="true"></div>
				</div>
				<aside class="jce-map-panel" aria-live="polite">
					<div class="jce-map-panel__header">
						<button type="button" class="jce-map-panel__reset" hidden><?php esc_html_e( 'Toute la France', 'wp-interactive-map-jce' ); ?></button>
						<button type="button" class="jce-map-panel__close" aria-label="<?php esc_attr_e( 'Fermer', 'wp-interactive-map-jce' ); ?>">&times;</button>
					</div>
					<div class="jce-map-panel__body">
						<p class="jce-map-panel__placeholder"><?php esc_html_e( 'Cliquez sur une région pour afficher les locales.', 'wp-interactive-map-jce' ); ?></p>
					</div>
				</aside>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
