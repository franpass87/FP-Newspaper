<?php
/**
 * Admin: Bulk Actions
 *
 * @package CdV
 * @subpackage Admin
 * @since 1.6.0
 */

namespace CdV\Admin;

/**
 * Class BulkActions
 */
class BulkActions {
	/**
	 * Initialize
	 */
	public static function init(): void {
		// Proposte bulk actions
		add_filter( 'bulk_actions-edit-cdv_proposta', array( self::class, 'add_proposta_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-cdv_proposta', array( self::class, 'handle_proposta_bulk_actions' ), 10, 3 );

		// Petizioni bulk actions
		add_filter( 'bulk_actions-edit-cdv_petizione', array( self::class, 'add_petizione_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-cdv_petizione', array( self::class, 'handle_petizione_bulk_actions' ), 10, 3 );

		// Admin notices
		add_action( 'admin_notices', array( self::class, 'bulk_action_notices' ) );
	}

	/**
	 * Add proposta bulk actions
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public static function add_proposta_bulk_actions( array $actions ): array {
		$actions['cdv_award_points'] = __( 'Assegna +10 Punti Autori', 'cronaca-di-viterbo' );
		$actions['cdv_notify_authors'] = __( 'Notifica Autori', 'cronaca-di-viterbo' );
		return $actions;
	}

	/**
	 * Handle proposta bulk actions
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action name.
	 * @param array  $post_ids    Post IDs.
	 * @return string
	 */
	public static function handle_proposta_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( $action === 'cdv_award_points' ) {
			$count = 0;
			foreach ( $post_ids as $post_id ) {
				$author_id = get_post_field( 'post_author', $post_id );
				if ( $author_id ) {
					\CdV\Services\Reputazione::add_points( $author_id, 10, 'Bonus bulk action admin' );
					$count++;
				}
			}
			$redirect_to = add_query_arg( 'cdv_bulk_points', $count, $redirect_to );
		}

		if ( $action === 'cdv_notify_authors' ) {
			$count = 0;
			foreach ( $post_ids as $post_id ) {
				$author_id = get_post_field( 'post_author', $post_id );
				$author = get_userdata( $author_id );
				
				if ( $author && $author->user_email ) {
					wp_mail(
						$author->user_email,
						__( 'Aggiornamento sulla tua proposta', 'cronaca-di-viterbo' ),
						sprintf( __( 'La tua proposta "%s" è stata selezionata dallo staff.', 'cronaca-di-viterbo' ), get_the_title( $post_id ) )
					);
					$count++;
				}
			}
			$redirect_to = add_query_arg( 'cdv_bulk_notify', $count, $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Add petizione bulk actions
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public static function add_petizione_bulk_actions( array $actions ): array {
		$actions['cdv_close_petizioni'] = __( 'Chiudi Petizioni', 'cronaca-di-viterbo' );
		$actions['cdv_open_petizioni'] = __( 'Apri Petizioni', 'cronaca-di-viterbo' );
		return $actions;
	}

	/**
	 * Handle petizione bulk actions
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action name.
	 * @param array  $post_ids    Post IDs.
	 * @return string
	 */
	public static function handle_petizione_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		if ( $action === 'cdv_close_petizioni' ) {
			foreach ( $post_ids as $post_id ) {
				update_post_meta( $post_id, '_cdv_aperta', '0' );
			}
			$redirect_to = add_query_arg( 'cdv_bulk_closed', count( $post_ids ), $redirect_to );
		}

		if ( $action === 'cdv_open_petizioni' ) {
			foreach ( $post_ids as $post_id ) {
				update_post_meta( $post_id, '_cdv_aperta', '1' );
			}
			$redirect_to = add_query_arg( 'cdv_bulk_opened', count( $post_ids ), $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Show bulk action notices
	 */
	public static function bulk_action_notices(): void {
		if ( ! empty( $_REQUEST['cdv_bulk_points'] ) ) {
			$count = intval( $_REQUEST['cdv_bulk_points'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf( __( 'Assegnati +10 punti a %d autori.', 'cronaca-di-viterbo' ), $count )
			);
		}

		if ( ! empty( $_REQUEST['cdv_bulk_notify'] ) ) {
			$count = intval( $_REQUEST['cdv_bulk_notify'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf( __( 'Email inviate a %d autori.', 'cronaca-di-viterbo' ), $count )
			);
		}

		if ( ! empty( $_REQUEST['cdv_bulk_closed'] ) ) {
			$count = intval( $_REQUEST['cdv_bulk_closed'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf( __( '%d petizioni chiuse.', 'cronaca-di-viterbo' ), $count )
			);
		}

		if ( ! empty( $_REQUEST['cdv_bulk_opened'] ) ) {
			$count = intval( $_REQUEST['cdv_bulk_opened'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf( __( '%d petizioni aperte.', 'cronaca-di-viterbo' ), $count )
			);
		}
	}
}
