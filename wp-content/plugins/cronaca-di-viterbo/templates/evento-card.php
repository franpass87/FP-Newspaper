<?php
/**
 * Template: Card Evento
 * 
 * Disponibile via Utils\View::render('evento-card', ['evento' => $post])
 *
 * @package CdV
 * @var WP_Post $evento
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = get_post_meta( $evento->ID, '_cdv_evento_data', true );
$ora = get_post_meta( $evento->ID, '_cdv_evento_ora', true );
$luogo = get_post_meta( $evento->ID, '_cdv_evento_luogo', true );
$quartiere_terms = get_the_terms( $evento->ID, 'cdv_quartiere' );
?>

<div class="cdv-evento-card">
	<?php if ( has_post_thumbnail( $evento->ID ) ) : ?>
		<div class="cdv-evento-thumbnail">
			<?php echo get_the_post_thumbnail( $evento->ID, 'medium' ); ?>
		</div>
	<?php endif; ?>

	<div class="cdv-evento-content">
		<h4><?php echo esc_html( $evento->post_title ); ?></h4>
		
		<div class="cdv-evento-meta">
			<?php if ( $data ) : ?>
				<span class="cdv-data">📅 <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data ) ) ); ?></span>
			<?php endif; ?>
			<?php if ( $ora ) : ?>
				<span class="cdv-ora">🕐 <?php echo esc_html( $ora ); ?></span>
			<?php endif; ?>
			<?php if ( $luogo ) : ?>
				<span class="cdv-luogo">📍 <?php echo esc_html( $luogo ); ?></span>
			<?php endif; ?>
		</div>

		<div class="cdv-evento-excerpt">
			<?php echo wp_kses_post( $evento->post_excerpt ); ?>
		</div>

		<a href="<?php echo esc_url( get_permalink( $evento->ID ) ); ?>" class="cdv-btn cdv-btn-secondary">
			<?php esc_html_e( 'Scopri di più', 'cronaca-di-viterbo' ); ?>
		</a>
	</div>
</div>
