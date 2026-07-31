<?php
/**
 * REST API for JCE locales.
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JCE_Map_REST_API
 */
class JCE_Map_REST_API {

	const NAMESPACE = 'jce/v1';

	/**
	 * Hook registrations.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/locales',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_locales' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'region' => array(
						'description'       => 'INSEE region code filter',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Return published locales for the map.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_locales( $request ) {
		$region = $request->get_param( 'region' );

		$query_args = array(
			'post_type'      => JCE_Map_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		if ( $region ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => JCE_Map_Post_Type::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $region,
				),
			);
		}

		$query   = new WP_Query( $query_args );
		$locales = array();

		foreach ( $query->posts as $post ) {
			$meta = JCE_Map_Post_Type::get_locale_meta( $post->ID );
			if ( '' === $meta['lat'] || '' === $meta['lng'] ) {
				continue;
			}

			$terms       = get_the_terms( $post->ID, JCE_Map_Post_Type::TAXONOMY );
			$region_code = '';
			$region_name = '';
			if ( $terms && ! is_wp_error( $terms ) ) {
				$region_code = $terms[0]->slug;
				$region_name = $terms[0]->name;
			}

			$external = $meta['external_url'] ? $meta['external_url'] : $meta['website'];

			$locales[] = array(
				'id'           => $post->ID,
				'title'        => get_the_title( $post ),
				'permalink'    => get_permalink( $post ),
				'excerpt'      => wp_trim_words( $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content ), 24 ),
				'lat'          => (float) $meta['lat'],
				'lng'          => (float) $meta['lng'],
				'address'      => $meta['address'],
				'city'         => $meta['city'],
				'postal_code'  => $meta['postal_code'],
				'email'        => $meta['email'],
				'phone'        => $meta['phone'],
				'website'      => $meta['website'],
				'external_url' => $external,
				'region_code'  => $region_code,
				'region_name'  => $region_name,
				'thumbnail'    => get_the_post_thumbnail_url( $post, 'thumbnail' ) ? get_the_post_thumbnail_url( $post, 'thumbnail' ) : '',
			);
		}

		return rest_ensure_response( $locales );
	}
}
