<?php
/**
 * Schermate Admin personalizzate.
 *
 * @package CdV\Admin
 */

namespace CdV\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per le schermate admin.
 */
class Screens {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
	}

	/**
	 * Aggiunge le pagine menu.
	 */
	public function add_menu_pages() {
		// Schermata Coda Moderazione
		add_menu_page(
			__( 'Coda Moderazione', 'cronaca-di-viterbo' ),
			__( 'Moderazione', 'cronaca-di-viterbo' ),
			'moderate_cdv_propostas',
			'cdv-moderation',
			[ $this, 'render_moderation_page' ],
			'dashicons-thumbs-up',
			25
		);
	}

	/**
	 * Render della pagina moderazione.
	 */
	public function render_moderation_page() {
		// Ottieni proposte in pending
		$proposte = new \WP_Query(
			[
				'post_type'      => 'cdv_proposta',
				'post_status'    => 'pending',
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Coda Moderazione - Proposte in Pending', 'cronaca-di-viterbo' ); ?></h1>

			<?php if ( $proposte->have_posts() ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Titolo', 'cronaca-di-viterbo' ); ?></th>
							<th><?php esc_html_e( 'Quartiere', 'cronaca-di-viterbo' ); ?></th>
							<th><?php esc_html_e( 'Tematica', 'cronaca-di-viterbo' ); ?></th>
							<th><?php esc_html_e( 'Voti', 'cronaca-di-viterbo' ); ?></th>
							<th><?php esc_html_e( 'Data', 'cronaca-di-viterbo' ); ?></th>
							<th><?php esc_html_e( 'Azioni', 'cronaca-di-viterbo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ( $proposte->have_posts() ) : $proposte->the_post(); ?>
							<?php
							$votes = get_post_meta( get_the_ID(), '_cdv_votes', true ) ?: 0;
							$quartiere_terms = get_the_terms( get_the_ID(), 'cdv_quartiere' );
							$tematica_terms = get_the_terms( get_the_ID(), 'cdv_tematica' );
							?>
							<tr>
								<td>
									<strong><?php the_title(); ?></strong><br>
									<small><?php the_excerpt(); ?></small>
								</td>
								<td>
									<?php echo $quartiere_terms ? esc_html( $quartiere_terms[0]->name ) : '-'; ?>
								</td>
								<td>
									<?php echo $tematica_terms ? esc_html( $tematica_terms[0]->name ) : '-'; ?>
								</td>
								<td><?php echo absint( $votes ); ?></td>
								<td><?php echo get_the_date(); ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="button button-small">
										<?php esc_html_e( 'Modifica', 'cronaca-di-viterbo' ); ?>
									</a>
									<a href="<?php echo esc_url( get_permalink() ); ?>" class="button button-small" target="_blank">
										<?php esc_html_e( 'Anteprima', 'cronaca-di-viterbo' ); ?>
									</a>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Nessuna proposta in moderazione.', 'cronaca-di-viterbo' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
