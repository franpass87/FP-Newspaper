<?php
/**
 * Admin: Import/Export Tools
 *
 * @package CdV
 * @subpackage Admin
 * @since 1.6.0
 */

namespace CdV\Admin;

/**
 * Class ImportExport
 */
class ImportExport {
	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
		add_action( 'admin_post_cdv_export_proposte', array( self::class, 'export_proposte' ) );
		add_action( 'admin_post_cdv_export_petizioni', array( self::class, 'export_petizioni' ) );
		add_action( 'admin_post_cdv_export_firme', array( self::class, 'export_firme' ) );
		add_action( 'admin_post_cdv_import_csv', array( self::class, 'import_csv' ) );
	}

	/**
	 * Add menu page
	 */
	public static function add_menu_page(): void {
		add_submenu_page(
			'edit.php?post_type=cdv_proposta',
			__( 'Import/Export', 'cronaca-di-viterbo' ),
			__( 'Import/Export', 'cronaca-di-viterbo' ),
			'manage_options',
			'cdv-import-export',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Render page
	 */
	public static function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import/Export Dati', 'cronaca-di-viterbo' ); ?></h1>

			<div class="cdv-import-export-container">
				<!-- Export Section -->
				<div class="cdv-export-section">
					<h2><?php esc_html_e( 'Export Dati', 'cronaca-di-viterbo' ); ?></h2>
					
					<div class="cdv-export-buttons">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 12px;">
							<?php wp_nonce_field( 'cdv_export_proposte', 'cdv_export_nonce' ); ?>
							<input type="hidden" name="action" value="cdv_export_proposte">
							<button type="submit" class="button button-primary">
								📥 <?php esc_html_e( 'Export Proposte (CSV)', 'cronaca-di-viterbo' ); ?>
							</button>
						</form>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 12px;">
							<?php wp_nonce_field( 'cdv_export_petizioni', 'cdv_export_nonce' ); ?>
							<input type="hidden" name="action" value="cdv_export_petizioni">
							<button type="submit" class="button button-primary">
								📥 <?php esc_html_e( 'Export Petizioni (CSV)', 'cronaca-di-viterbo' ); ?>
							</button>
						</form>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;">
							<?php wp_nonce_field( 'cdv_export_firme', 'cdv_export_nonce' ); ?>
							<input type="hidden" name="action" value="cdv_export_firme">
							<select name="petizione_id" required>
								<option value=""><?php esc_html_e( 'Seleziona petizione...', 'cronaca-di-viterbo' ); ?></option>
								<?php
								$petizioni = get_posts( array( 'post_type' => 'cdv_petizione', 'posts_per_page' => -1 ) );
								foreach ( $petizioni as $p ) {
									echo '<option value="' . esc_attr( $p->ID ) . '">' . esc_html( $p->post_title ) . '</option>';
								}
								?>
							</select>
							<button type="submit" class="button button-primary">
								📥 <?php esc_html_e( 'Export Firme (CSV)', 'cronaca-di-viterbo' ); ?>
							</button>
						</form>
					</div>

					<p class="description">
						<?php esc_html_e( 'I file CSV includono tutti i dati con intestazioni in italiano.', 'cronaca-di-viterbo' ); ?>
					</p>
				</div>

				<hr style="margin: 40px 0;">

				<!-- Import Section -->
				<div class="cdv-import-section">
					<h2><?php esc_html_e( 'Import Dati', 'cronaca-di-viterbo' ); ?></h2>
					
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'cdv_import_csv', 'cdv_import_nonce' ); ?>
						<input type="hidden" name="action" value="cdv_import_csv">

						<table class="form-table">
							<tr>
								<th><label for="cdv_import_type"><?php esc_html_e( 'Tipo', 'cronaca-di-viterbo' ); ?></label></th>
								<td>
									<select name="import_type" id="cdv_import_type" required>
										<option value=""><?php esc_html_e( 'Seleziona...', 'cronaca-di-viterbo' ); ?></option>
										<option value="proposte"><?php esc_html_e( 'Proposte', 'cronaca-di-viterbo' ); ?></option>
										<option value="eventi"><?php esc_html_e( 'Eventi', 'cronaca-di-viterbo' ); ?></option>
										<option value="persone"><?php esc_html_e( 'Persone', 'cronaca-di-viterbo' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="cdv_import_file"><?php esc_html_e( 'File CSV', 'cronaca-di-viterbo' ); ?></label></th>
								<td>
									<input type="file" name="import_file" id="cdv_import_file" accept=".csv" required>
									<p class="description"><?php esc_html_e( 'Formato: CSV con intestazioni', 'cronaca-di-viterbo' ); ?></p>
								</td>
							</tr>
						</table>

						<p class="submit">
							<button type="submit" class="button button-primary">
								📤 <?php esc_html_e( 'Import CSV', 'cronaca-di-viterbo' ); ?>
							</button>
						</p>
					</form>

					<div class="cdv-import-info">
						<h3><?php esc_html_e( 'Formato CSV Atteso', 'cronaca-di-viterbo' ); ?></h3>
						<pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
<strong>Proposte:</strong>
titolo,contenuto,quartiere_slug,tematica_slug,autore_email

<strong>Eventi:</strong>
titolo,contenuto,data_inizio,luogo,quartiere_slug,latitudine,longitudine

<strong>Persone:</strong>
nome,bio,ruolo,email,telefono
						</pre>
					</div>
				</div>
			</div>
		</div>

		<style>
		.cdv-import-export-container {
			max-width: 1200px;
			background: #fff;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.05);
		}
		.cdv-export-buttons {
			margin: 20px 0;
		}
		.cdv-export-buttons form {
			vertical-align: middle;
		}
		.cdv-import-info {
			margin-top: 30px;
			padding: 20px;
			background: #f8f9fa;
			border-radius: 8px;
		}
		</style>
		<?php
	}

	/**
	 * Export proposte
	 */
	public static function export_proposte(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Non autorizzato' );
		}

		check_admin_referer( 'cdv_export_proposte', 'cdv_export_nonce' );

		$proposte = get_posts( array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		) );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=proposte-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		
		// BOM per UTF-8
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header
		fputcsv( $output, array( 'ID', 'Titolo', 'Contenuto', 'Quartiere', 'Tematica', 'Voti', 'Autore', 'Data', 'Status' ) );

		foreach ( $proposte as $proposta ) {
			$voti = get_post_meta( $proposta->ID, '_cdv_votes', true );
			$quartieri = wp_get_post_terms( $proposta->ID, 'cdv_quartiere', array( 'fields' => 'names' ) );
			$tematiche = wp_get_post_terms( $proposta->ID, 'cdv_tematica', array( 'fields' => 'names' ) );

			fputcsv( $output, array(
				$proposta->ID,
				$proposta->post_title,
				wp_strip_all_tags( $proposta->post_content ),
				! empty( $quartieri ) ? implode( ', ', $quartieri ) : '',
				! empty( $tematiche ) ? implode( ', ', $tematiche ) : '',
				$voti,
				get_the_author_meta( 'display_name', $proposta->post_author ),
				get_the_date( 'Y-m-d H:i:s', $proposta ),
				$proposta->post_status,
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export petizioni
	 */
	public static function export_petizioni(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Non autorizzato' );
		}

		check_admin_referer( 'cdv_export_petizioni', 'cdv_export_nonce' );

		$petizioni = get_posts( array(
			'post_type'      => 'cdv_petizione',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=petizioni-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv( $output, array( 'ID', 'Titolo', 'Firme', 'Soglia', '% Completamento', 'Aperta', 'Scadenza', 'Autore', 'Data' ) );

		foreach ( $petizioni as $petizione ) {
			$firme = get_post_meta( $petizione->ID, '_cdv_firme_count', true );
			$soglia = get_post_meta( $petizione->ID, '_cdv_soglia_firme', true );
			$aperta = get_post_meta( $petizione->ID, '_cdv_aperta', true );
			$deadline = get_post_meta( $petizione->ID, '_cdv_deadline', true );

			fputcsv( $output, array(
				$petizione->ID,
				$petizione->post_title,
				$firme,
				$soglia,
				$soglia > 0 ? round( ( $firme / $soglia ) * 100, 1 ) : 0,
				$aperta === '0' ? 'No' : 'Sì',
				$deadline,
				get_the_author_meta( 'display_name', $petizione->post_author ),
				get_the_date( 'Y-m-d H:i:s', $petizione ),
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export firme petizione
	 */
	public static function export_firme(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Non autorizzato' );
		}

		check_admin_referer( 'cdv_export_firme', 'cdv_export_nonce' );

		$petizione_id = isset( $_POST['petizione_id'] ) ? intval( $_POST['petizione_id'] ) : 0;

		if ( ! $petizione_id ) {
			wp_die( 'Petizione non valida' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cdv_petizioni_firme';

		$firme = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table WHERE petizione_id = %d ORDER BY created_at DESC",
			$petizione_id
		), ARRAY_A );

		$petizione = get_post( $petizione_id );
		$filename = 'firme-' . sanitize_title( $petizione->post_title ) . '-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv( $output, array( 'ID', 'Nome', 'Cognome', 'Email', 'Comune', 'Motivazione', 'Verificato', 'Data' ) );

		foreach ( $firme as $firma ) {
			fputcsv( $output, array(
				$firma['id'],
				$firma['nome'],
				$firma['cognome'],
				$firma['email'],
				$firma['comune'],
				$firma['motivazione'],
				$firma['verified'] ? 'Sì' : 'No',
				$firma['created_at'],
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Import CSV
	 */
	public static function import_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Non autorizzato' );
		}

		check_admin_referer( 'cdv_import_csv', 'cdv_import_nonce' );

		$import_type = isset( $_POST['import_type'] ) ? sanitize_text_field( $_POST['import_type'] ) : '';

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_redirect( add_query_arg( array( 'page' => 'cdv-import-export', 'error' => 'no_file' ), admin_url( 'edit.php?post_type=cdv_proposta' ) ) );
			exit;
		}

		$file = $_FILES['import_file']['tmp_name'];
		$handle = fopen( $file, 'r' );

		if ( ! $handle ) {
			wp_redirect( add_query_arg( array( 'page' => 'cdv-import-export', 'error' => 'file_error' ), admin_url( 'edit.php?post_type=cdv_proposta' ) ) );
			exit;
		}

		// Skip header
		fgetcsv( $handle );

		$imported = 0;
		$errors = 0;

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			$result = self::import_row( $import_type, $data );
			if ( $result ) {
				$imported++;
			} else {
				$errors++;
			}
		}

		fclose( $handle );

		wp_redirect( add_query_arg( array(
			'page'     => 'cdv-import-export',
			'imported' => $imported,
			'errors'   => $errors,
		), admin_url( 'edit.php?post_type=cdv_proposta' ) ) );
		exit;
	}

	/**
	 * Import single row
	 *
	 * @param string $type Import type.
	 * @param array  $data Row data.
	 * @return bool
	 */
	private static function import_row( string $type, array $data ): bool {
		switch ( $type ) {
			case 'proposte':
				return self::import_proposta( $data );
			case 'eventi':
				return self::import_evento( $data );
			case 'persone':
				return self::import_persona( $data );
			default:
				return false;
		}
	}

	/**
	 * Import proposta
	 *
	 * @param array $data Row data.
	 * @return bool
	 */
	private static function import_proposta( array $data ): bool {
		if ( count( $data ) < 3 ) {
			return false;
		}

		$post_data = array(
			'post_type'    => 'cdv_proposta',
			'post_title'   => sanitize_text_field( $data[0] ),
			'post_content' => wp_kses_post( $data[1] ),
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Set quartiere
		if ( ! empty( $data[2] ) ) {
			$quartiere = get_term_by( 'slug', sanitize_title( $data[2] ), 'cdv_quartiere' );
			if ( $quartiere ) {
				wp_set_post_terms( $post_id, array( $quartiere->term_id ), 'cdv_quartiere' );
			}
		}

		// Set tematica
		if ( ! empty( $data[3] ) ) {
			$tematica = get_term_by( 'slug', sanitize_title( $data[3] ), 'cdv_tematica' );
			if ( $tematica ) {
				wp_set_post_terms( $post_id, array( $tematica->term_id ), 'cdv_tematica' );
			}
		}

		return true;
	}

	/**
	 * Import evento
	 *
	 * @param array $data Row data.
	 * @return bool
	 */
	private static function import_evento( array $data ): bool {
		if ( count( $data ) < 4 ) {
			return false;
		}

		$post_data = array(
			'post_type'    => 'cdv_evento',
			'post_title'   => sanitize_text_field( $data[0] ),
			'post_content' => wp_kses_post( $data[1] ),
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Set data inizio
		if ( ! empty( $data[2] ) ) {
			update_post_meta( $post_id, '_cdv_data_inizio', sanitize_text_field( $data[2] ) );
		}

		// Set luogo
		if ( ! empty( $data[3] ) ) {
			update_post_meta( $post_id, '_cdv_luogo', sanitize_text_field( $data[3] ) );
		}

		// Set coordinate se disponibili
		if ( ! empty( $data[5] ) && ! empty( $data[6] ) ) {
			update_post_meta( $post_id, '_cdv_latitudine', floatval( $data[5] ) );
			update_post_meta( $post_id, '_cdv_longitudine', floatval( $data[6] ) );
		}

		return true;
	}

	/**
	 * Import persona
	 *
	 * @param array $data Row data.
	 * @return bool
	 */
	private static function import_persona( array $data ): bool {
		if ( count( $data ) < 2 ) {
			return false;
		}

		$post_data = array(
			'post_type'    => 'cdv_persona',
			'post_title'   => sanitize_text_field( $data[0] ),
			'post_content' => wp_kses_post( $data[1] ),
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Set meta
		if ( ! empty( $data[2] ) ) {
			update_post_meta( $post_id, '_cdv_ruolo', sanitize_text_field( $data[2] ) );
		}

		if ( ! empty( $data[3] ) ) {
			update_post_meta( $post_id, '_cdv_email', sanitize_email( $data[3] ) );
		}

		return true;
	}
}
