<?php
/**
 * Custom Post Type: Sondaggio
 *
 * Gestisce i sondaggi e consultazioni pubbliche
 *
 * @package CdV
 * @subpackage PostTypes
 * @since 1.4.0
 */

namespace CdV\PostTypes;

/**
 * Class Sondaggio
 */
class Sondaggio {
	/**
	 * Post type slug
	 */
	const POST_TYPE = 'cdv_sondaggio';

	/**
	 * Register post type
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Sondaggi', 'cronaca-di-viterbo' ),
					'singular_name'      => __( 'Sondaggio', 'cronaca-di-viterbo' ),
					'add_new'            => __( 'Aggiungi Sondaggio', 'cronaca-di-viterbo' ),
					'add_new_item'       => __( 'Aggiungi Nuovo Sondaggio', 'cronaca-di-viterbo' ),
					'edit_item'          => __( 'Modifica Sondaggio', 'cronaca-di-viterbo' ),
					'new_item'           => __( 'Nuovo Sondaggio', 'cronaca-di-viterbo' ),
					'view_item'          => __( 'Visualizza Sondaggio', 'cronaca-di-viterbo' ),
					'search_items'       => __( 'Cerca Sondaggi', 'cronaca-di-viterbo' ),
					'not_found'          => __( 'Nessun sondaggio trovato', 'cronaca-di-viterbo' ),
					'not_found_in_trash' => __( 'Nessun sondaggio nel cestino', 'cronaca-di-viterbo' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'sondaggi' ),
				'capability_type'     => array( 'cdv_sondaggio', 'cdv_sondaggi' ),
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail' ),
				'taxonomies'          => array( 'cdv_quartiere', 'cdv_tematica' ),
				'menu_icon'           => 'dashicons-chart-bar',
			)
		);
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'cdv_sondaggio_options',
			__( 'Opzioni Sondaggio', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_options' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'cdv_sondaggio_results',
			__( 'Risultati', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_results' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render meta box opzioni
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_options( $post ): void {
		wp_nonce_field( 'cdv_sondaggio_meta', 'cdv_sondaggio_nonce' );

		$options = get_post_meta( $post->ID, '_cdv_options', true ) ?: array();
		$scadenza = get_post_meta( $post->ID, '_cdv_scadenza', true );
		$multiplo = get_post_meta( $post->ID, '_cdv_multiplo', true );
		$mostra_risultati = get_post_meta( $post->ID, '_cdv_mostra_risultati', true ) !== '0';
		$aperto = get_post_meta( $post->ID, '_cdv_aperto', true ) !== '0';

		?>
		<p>
			<label>
				<input type="checkbox" name="cdv_aperto" value="1" <?php checked( $aperto ); ?>>
				<strong><?php esc_html_e( 'Sondaggio aperto', 'cronaca-di-viterbo' ); ?></strong>
			</label>
		</p>

		<p>
			<label for="cdv_scadenza"><strong><?php esc_html_e( 'Scadenza', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="datetime-local" name="cdv_scadenza" id="cdv_scadenza" value="<?php echo esc_attr( $scadenza ); ?>" class="widefat">
		</p>

		<p>
			<label>
				<input type="checkbox" name="cdv_multiplo" value="1" <?php checked( $multiplo ); ?>>
				<?php esc_html_e( 'Consenti selezione multipla', 'cronaca-di-viterbo' ); ?>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="cdv_mostra_risultati" value="1" <?php checked( $mostra_risultati ); ?>>
				<?php esc_html_e( 'Mostra risultati in tempo reale', 'cronaca-di-viterbo' ); ?>
			</label>
		</p>

		<div class="cdv-sondaggio-options">
			<h4><?php esc_html_e( 'Opzioni di Risposta', 'cronaca-di-viterbo' ); ?></h4>
			<div id="cdv-options-container">
				<?php if ( ! empty( $options ) ) : ?>
					<?php foreach ( $options as $index => $option ) : ?>
						<p class="cdv-option-row">
							<input type="text" name="cdv_options[]" value="<?php echo esc_attr( $option ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Opzione...', 'cronaca-di-viterbo' ); ?>">
							<button type="button" class="button cdv-remove-option"><?php esc_html_e( 'Rimuovi', 'cronaca-di-viterbo' ); ?></button>
						</p>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="cdv-option-row">
						<input type="text" name="cdv_options[]" value="" class="widefat" placeholder="<?php esc_attr_e( 'Opzione 1...', 'cronaca-di-viterbo' ); ?>">
						<button type="button" class="button cdv-remove-option"><?php esc_html_e( 'Rimuovi', 'cronaca-di-viterbo' ); ?></button>
					</p>
					<p class="cdv-option-row">
						<input type="text" name="cdv_options[]" value="" class="widefat" placeholder="<?php esc_attr_e( 'Opzione 2...', 'cronaca-di-viterbo' ); ?>">
						<button type="button" class="button cdv-remove-option"><?php esc_html_e( 'Rimuovi', 'cronaca-di-viterbo' ); ?></button>
					</p>
				<?php endif; ?>
			</div>
			<button type="button" id="cdv-add-option" class="button"><?php esc_html_e( 'Aggiungi Opzione', 'cronaca-di-viterbo' ); ?></button>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#cdv-add-option').on('click', function() {
				var count = $('#cdv-options-container .cdv-option-row').length + 1;
				var html = '<p class="cdv-option-row">' +
					'<input type="text" name="cdv_options[]" value="" class="widefat" placeholder="Opzione ' + count + '...">' +
					'<button type="button" class="button cdv-remove-option">Rimuovi</button>' +
					'</p>';
				$('#cdv-options-container').append(html);
			});

			$(document).on('click', '.cdv-remove-option', function() {
				if ($('#cdv-options-container .cdv-option-row').length > 2) {
					$(this).closest('.cdv-option-row').remove();
				} else {
					alert('<?php esc_html_e( 'Devi avere almeno 2 opzioni', 'cronaca-di-viterbo' ); ?>');
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render meta box risultati
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_results( $post ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_sondaggi_voti';

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_identifier) FROM `{$table}` WHERE sondaggio_id = %d",
			$post->ID
		) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT option_index, COUNT(*) as votes FROM `{$table}` WHERE sondaggio_id = %d GROUP BY option_index ORDER BY option_index",
			$post->ID
		) );

		$options = get_post_meta( $post->ID, '_cdv_options', true ) ?: array();

		?>
		<p><strong><?php esc_html_e( 'Partecipanti totali', 'cronaca-di-viterbo' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $total ) ); ?></p>

		<?php if ( ! empty( $results ) && ! empty( $options ) ) : ?>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Opzione', 'cronaca-di-viterbo' ); ?></th>
						<th><?php esc_html_e( 'Voti', 'cronaca-di-viterbo' ); ?></th>
						<th>%</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $result ) : ?>
						<?php if ( isset( $options[ $result->option_index ] ) ) : ?>
							<tr>
								<td><?php echo esc_html( $options[ $result->option_index ] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $result->votes ) ); ?></td>
								<td><?php echo esc_html( $total > 0 ? round( ( $result->votes / $total ) * 100, 1 ) : 0 ); ?>%</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><em><?php esc_html_e( 'Nessun voto ancora', 'cronaca-di-viterbo' ); ?></em></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['cdv_sondaggio_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_sondaggio_nonce'], 'cdv_sondaggio_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Save options
		if ( isset( $_POST['cdv_options'] ) && is_array( $_POST['cdv_options'] ) ) {
			$options = array_filter( array_map( 'sanitize_text_field', $_POST['cdv_options'] ) );
			update_post_meta( $post_id, '_cdv_options', $options );
		}

		// Save settings
		if ( isset( $_POST['cdv_scadenza'] ) ) {
			update_post_meta( $post_id, '_cdv_scadenza', sanitize_text_field( $_POST['cdv_scadenza'] ) );
		}

		update_post_meta( $post_id, '_cdv_multiplo', isset( $_POST['cdv_multiplo'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_cdv_mostra_risultati', isset( $_POST['cdv_mostra_risultati'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_cdv_aperto', isset( $_POST['cdv_aperto'] ) ? '1' : '0' );
	}

	/**
	 * Create votes table
	 */
	public static function create_votes_table(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cdv_sondaggi_voti';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			sondaggio_id bigint(20) UNSIGNED NOT NULL,
			option_index int(11) NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT 0,
			user_identifier varchar(200) NOT NULL,
			ip_address varchar(100) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sondaggio_id (sondaggio_id),
			KEY user_identifier (user_identifier)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
