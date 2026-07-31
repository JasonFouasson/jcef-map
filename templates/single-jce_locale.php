<?php
/**
 * Single template for jce_locale.
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$meta     = JCE_Map_Post_Type::get_locale_meta( get_the_ID() );
	$terms    = get_the_terms( get_the_ID(), JCE_Map_Post_Type::TAXONOMY );
	$region   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	$external = $meta['external_url'] ? $meta['external_url'] : $meta['website'];
	?>
	<main class="jce-single" id="jce-locale-<?php the_ID(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="jce-single__thumb">
				<?php the_post_thumbnail( 'medium' ); ?>
			</div>
		<?php endif; ?>

		<h1 class="jce-single__title"><?php the_title(); ?></h1>

		<?php if ( $region ) : ?>
			<p class="jce-single__meta"><?php echo esc_html( $region ); ?></p>
		<?php endif; ?>

		<div class="jce-single__content">
			<?php the_content(); ?>
		</div>

		<section class="jce-single__card" aria-labelledby="jce-contact-heading">
			<h2 id="jce-contact-heading"><?php esc_html_e( 'Coordonnées & contact', 'wp-interactive-map-jce' ); ?></h2>
			<dl class="jce-single__dl">
				<?php if ( $meta['address'] || $meta['city'] || $meta['postal_code'] ) : ?>
					<dt><?php esc_html_e( 'Adresse', 'wp-interactive-map-jce' ); ?></dt>
					<dd>
						<?php
						$lines = array_filter(
							array(
								$meta['address'],
								trim( $meta['postal_code'] . ' ' . $meta['city'] ),
							)
						);
						echo esc_html( implode( ', ', $lines ) );
						?>
					</dd>
				<?php endif; ?>

				<?php if ( '' !== $meta['lat'] && '' !== $meta['lng'] ) : ?>
					<dt><?php esc_html_e( 'GPS', 'wp-interactive-map-jce' ); ?></dt>
					<dd><?php echo esc_html( $meta['lat'] . ', ' . $meta['lng'] ); ?></dd>
				<?php endif; ?>

				<?php if ( $meta['email'] ) : ?>
					<dt><?php esc_html_e( 'E-mail', 'wp-interactive-map-jce' ); ?></dt>
					<dd><a href="mailto:<?php echo esc_attr( $meta['email'] ); ?>"><?php echo esc_html( $meta['email'] ); ?></a></dd>
				<?php endif; ?>

				<?php if ( $meta['phone'] ) : ?>
					<dt><?php esc_html_e( 'Téléphone', 'wp-interactive-map-jce' ); ?></dt>
					<dd><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $meta['phone'] ) ); ?>"><?php echo esc_html( $meta['phone'] ); ?></a></dd>
				<?php endif; ?>
			</dl>

			<?php if ( $external ) : ?>
				<div class="jce-single__actions">
					<a class="jce-btn jce-btn--primary" href="<?php echo esc_url( $external ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Site de la locale', 'wp-interactive-map-jce' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</section>
	</main>
	<?php
endwhile;

get_footer();
