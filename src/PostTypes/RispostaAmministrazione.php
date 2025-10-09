<?php
/**
 * Custom Post Type: Risposta Amministrazione
 *
 * Gestisce le risposte ufficiali dell'amministrazione comunale alle proposte
 *
 * @package CdV
 * @subpackage PostTypes
 * @since 1.2.0
 */

namespace CdV\PostTypes;

/**
 * Class RispostaAmministrazione
 */
class RispostaAmministrazione {
	/**
	 * Post type slug
	 */
	const POST_TYPE = 'cdv_risposta_amm';

	/**
	 * Stati possibili della risposta
	 */
	const STATUS_IN_VALUTAZIONE = 'in_valutazione';
	const STATUS_ACCETTATA = 'accettata';
	const STATUS_RESPINTA = 'respinta';
	const STATUS_IN_CORSO = 'in_corso';
	const STATUS_COMPLETATA = 'completata';

	/**
	 * Register post type
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Risposte Amministrazione', 'cronaca-di-viterbo' ),
					'singular_name'      => __( 'Risposta Amministrazione', 'cronaca-di-viterbo' ),
					'add_new'            => __( 'Aggiungi Risposta', 'cronaca-di-viterbo' ),
					'add_new_item'       => __( 'Aggiungi Nuova Risposta', 'cronaca-di-viterbo' ),
					'edit_item'          => __( 'Modifica Risposta', 'cronaca-di-viterbo' ),
					'new_item'           => __( 'Nuova Risposta', 'cronaca-di-viterbo' ),
					'view_item'          => __( 'Visualizza Risposta', 'cronaca-di-viterbo' ),
					'search_items'       => __( 'Cerca Risposte', 'cronaca-di-viterbo' ),
					'not_found'          => __( 'Nessuna risposta trovata', 'cronaca-di-viterbo' ),
					'not_found_in_trash' => __( 'Nessuna risposta nel cestino', 'cronaca-di-viterbo' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=cdv_proposta',
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'risposte-amministrazione' ),
				'capability_type'     => array( 'cdv_risposta_amm', 'cdv_risposte_amm' ),
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'author', 'revisions' ),
				'menu_icon'           => 'dashicons-megaphone',
			)
		);
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'cdv_risposta_amm_details',
			__( 'Dettagli Risposta', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_details' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box dettagli
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_details( $post ): void {
		wp_nonce_field( 'cdv_risposta_amm_meta', 'cdv_risposta_amm_nonce' );

		$proposta_id = get_post_meta( $post->ID, '_cdv_proposta_id', true );
		$status = get_post_meta( $post->ID, '_cdv_status', true ) ?: self::STATUS_IN_VALUTAZIONE;
		$budget = get_post_meta( $post->ID, '_cdv_budget', true );
		$timeline = get_post_meta( $post->ID, '_cdv_timeline', true );
		$delibera = get_post_meta( $post->ID, '_cdv_delibera', true );
		$ufficio = get_post_meta( $post->ID, '_cdv_ufficio', true );
		$referente = get_post_meta( $post->ID, '_cdv_referente', true );
		$data_risposta = get_post_meta( $post->ID, '_cdv_data_risposta', true );

		?>
		<table class="form-table">
			<tr>
				<th><label for="cdv_proposta_id"><?php esc_html_e( 'Proposta Collegata', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_posts(
						array(
							'post_type'        => 'cdv_proposta',
							'selected'         => $proposta_id,
							'name'             => 'cdv_proposta_id',
							'id'               => 'cdv_proposta_id',
							'show_option_none' => __( 'Seleziona proposta...', 'cronaca-di-viterbo' ),
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Seleziona la proposta a cui si riferisce questa risposta', 'cronaca-di-viterbo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cdv_status"><?php esc_html_e( 'Stato', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<select name="cdv_status" id="cdv_status" class="regular-text">
						<option value="<?php echo esc_attr( self::STATUS_IN_VALUTAZIONE ); ?>" <?php selected( $status, self::STATUS_IN_VALUTAZIONE ); ?>>
							<?php esc_html_e( 'In Valutazione', 'cronaca-di-viterbo' ); ?>
						</option>
						<option value="<?php echo esc_attr( self::STATUS_ACCETTATA ); ?>" <?php selected( $status, self::STATUS_ACCETTATA ); ?>>
							<?php esc_html_e( 'Accettata', 'cronaca-di-viterbo' ); ?>
						</option>
						<option value="<?php echo esc_attr( self::STATUS_RESPINTA ); ?>" <?php selected( $status, self::STATUS_RESPINTA ); ?>>
							<?php esc_html_e( 'Respinta', 'cronaca-di-viterbo' ); ?>
						</option>
						<option value="<?php echo esc_attr( self::STATUS_IN_CORSO ); ?>" <?php selected( $status, self::STATUS_IN_CORSO ); ?>>
							<?php esc_html_e( 'In Corso', 'cronaca-di-viterbo' ); ?>
						</option>
						<option value="<?php echo esc_attr( self::STATUS_COMPLETATA ); ?>" <?php selected( $status, self::STATUS_COMPLETATA ); ?>>
							<?php esc_html_e( 'Completata', 'cronaca-di-viterbo' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cdv_data_risposta"><?php esc_html_e( 'Data Risposta', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<input type="date" name="cdv_data_risposta" id="cdv_data_risposta" value="<?php echo esc_attr( $data_risposta ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="cdv_budget"><?php esc_html_e( 'Budget Allocato (€)', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<input type="number" name="cdv_budget" id="cdv_budget" value="<?php echo esc_attr( $budget ); ?>" class="regular-text" step="0.01" min="0">
					<p class="description"><?php esc_html_e( 'Budget stanziato per questa proposta', 'cronaca-di-viterbo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cdv_timeline"><?php esc_html_e( 'Timeline Implementazione', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<textarea name="cdv_timeline" id="cdv_timeline" rows="3" class="large-text"><?php echo esc_textarea( $timeline ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Es: Q1 2026 - Progettazione, Q2 2026 - Realizzazione', 'cronaca-di-viterbo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cdv_delibera"><?php esc_html_e( 'Delibera/Atto', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<input type="text" name="cdv_delibera" id="cdv_delibera" value="<?php echo esc_attr( $delibera ); ?>" class="large-text">
					<p class="description"><?php esc_html_e( 'Es: Delibera n. 123/2025 o link documento', 'cronaca-di-viterbo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cdv_ufficio"><?php esc_html_e( 'Ufficio Competente', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<input type="text" name="cdv_ufficio" id="cdv_ufficio" value="<?php echo esc_attr( $ufficio ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="cdv_referente"><?php esc_html_e( 'Referente', 'cronaca-di-viterbo' ); ?></label></th>
				<td>
					<input type="text" name="cdv_referente" id="cdv_referente" value="<?php echo esc_attr( $referente ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Nome e contatto del referente comunale', 'cronaca-di-viterbo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['cdv_risposta_amm_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_risposta_amm_nonce'], 'cdv_risposta_amm_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$fields = array(
			'cdv_proposta_id'   => 'intval',
			'cdv_status'        => 'sanitize_text_field',
			'cdv_budget'        => 'floatval',
			'cdv_timeline'      => 'sanitize_textarea_field',
			'cdv_delibera'      => 'sanitize_text_field',
			'cdv_ufficio'       => 'sanitize_text_field',
			'cdv_referente'     => 'sanitize_text_field',
			'cdv_data_risposta' => 'sanitize_text_field',
		);

		foreach ( $fields as $field => $sanitize ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}

		// Trigger action per notifiche
		do_action( 'cdv_risposta_pubblicata', $post_id, get_post_meta( $post_id, '_cdv_proposta_id', true ) );
	}

	/**
	 * Get status label
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function get_status_label( string $status ): string {
		$labels = array(
			self::STATUS_IN_VALUTAZIONE => __( 'In Valutazione', 'cronaca-di-viterbo' ),
			self::STATUS_ACCETTATA      => __( 'Accettata', 'cronaca-di-viterbo' ),
			self::STATUS_RESPINTA       => __( 'Respinta', 'cronaca-di-viterbo' ),
			self::STATUS_IN_CORSO       => __( 'In Corso', 'cronaca-di-viterbo' ),
			self::STATUS_COMPLETATA     => __( 'Completata', 'cronaca-di-viterbo' ),
		);

		return $labels[ $status ] ?? $status;
	}

	/**
	 * Get status color class
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function get_status_color( string $status ): string {
		$colors = array(
			self::STATUS_IN_VALUTAZIONE => 'status-pending',
			self::STATUS_ACCETTATA      => 'status-success',
			self::STATUS_RESPINTA       => 'status-error',
			self::STATUS_IN_CORSO       => 'status-info',
			self::STATUS_COMPLETATA     => 'status-complete',
		);

		return $colors[ $status ] ?? 'status-default';
	}
}

/**
 * Helper function per wp_dropdown_posts
 */
if ( ! function_exists( 'wp_dropdown_posts' ) ) {
	function wp_dropdown_posts( $args = array() ) {
		$defaults = array(
			'post_type'        => 'post',
			'selected'         => 0,
			'name'             => 'post_id',
			'id'               => '',
			'show_option_none' => '',
			'posts_per_page'   => -1,
		);

		$args = wp_parse_args( $args, $defaults );

		$posts = get_posts(
			array(
				'post_type'      => $args['post_type'],
				'posts_per_page' => $args['posts_per_page'],
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);

		$output = '<select name="' . esc_attr( $args['name'] ) . '"';
		if ( $args['id'] ) {
			$output .= ' id="' . esc_attr( $args['id'] ) . '"';
		}
		$output .= '>';

		if ( $args['show_option_none'] ) {
			$output .= '<option value="">' . esc_html( $args['show_option_none'] ) . '</option>';
		}

		foreach ( $posts as $post ) {
			$output .= '<option value="' . esc_attr( $post->ID ) . '"' . selected( $args['selected'], $post->ID, false ) . '>';
			$output .= esc_html( $post->post_title ) . ' (#' . $post->ID . ')';
			$output .= '</option>';
		}

		$output .= '</select>';

		echo $output;
	}
}
