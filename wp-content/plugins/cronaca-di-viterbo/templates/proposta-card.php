<?php
/**
 * Template: Card Proposta
 * 
 * Disponibile via Utils\View::render('proposta-card', ['proposta' => $post])
 *
 * @package CdV
 * @var WP_Post $proposta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$votes = get_post_meta( $proposta->ID, '_cdv_votes', true ) ?: 0;
$quartiere_terms = get_the_terms( $proposta->ID, 'cdv_quartiere' );
$tematica_terms = get_the_terms( $proposta->ID, 'cdv_tematica' );
?>

<div class="cdv-proposta-card" data-id="<?php echo esc_attr( $proposta->ID ); ?>">
	<h4><?php echo esc_html( $proposta->post_title ); ?></h4>
	
	<div class="cdv-proposta-meta">
		<?php if ( $quartiere_terms ) : ?>
			<span class="cdv-quartiere">📍 <?php echo esc_html( $quartiere_terms[0]->name ); ?></span>
		<?php endif; ?>
		<?php if ( $tematica_terms ) : ?>
			<span class="cdv-tematica">🏷️ <?php echo esc_html( $tematica_terms[0]->name ); ?></span>
		<?php endif; ?>
	</div>

	<div class="cdv-proposta-excerpt">
		<?php echo wp_kses_post( $proposta->post_excerpt ); ?>
	</div>

	<div class="cdv-proposta-actions">
		<button class="cdv-vote-btn" data-id="<?php echo esc_attr( $proposta->ID ); ?>">
			👍 <span class="cdv-vote-count"><?php echo esc_html( $votes ); ?></span>
		</button>
		<a href="<?php echo esc_url( get_permalink( $proposta->ID ) ); ?>" class="cdv-read-more">
			<?php esc_html_e( 'Leggi tutto', 'cronaca-di-viterbo' ); ?>
		</a>
	</div>
</div>
