<?php
/**
 * Gestione Ruoli e Capabilities personalizzati.
 *
 * @package CdV\Roles
 */

namespace CdV\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire ruoli e capabilities.
 */
class Capabilities {

	/**
	 * Aggiunge i ruoli personalizzati.
	 */
	public static function add_roles() {
		// CdV Editor: può gestire tutto
		add_role(
			'cdv_editor',
			__( 'CdV Editor', 'cronaca-di-viterbo' ),
			[
				'read'                      => true,
				'edit_posts'                => true,
				'delete_posts'              => true,
				'publish_posts'             => true,
				'upload_files'              => true,
				// Dossier
				'edit_cdv_dossier'          => true,
				'edit_cdv_dossiers'         => true,
				'edit_others_cdv_dossiers'  => true,
				'publish_cdv_dossiers'      => true,
				'read_private_cdv_dossiers' => true,
				'delete_cdv_dossiers'       => true,
				// Proposte
				'edit_cdv_proposta'         => true,
				'edit_cdv_propostas'        => true,
				'edit_others_cdv_propostas' => true,
				'publish_cdv_propostas'     => true,
				'delete_cdv_propostas'      => true,
				'moderate_cdv_propostas'    => true,
				// Eventi
				'edit_cdv_evento'           => true,
				'edit_cdv_eventos'          => true,
				'edit_others_cdv_eventos'   => true,
				'publish_cdv_eventos'       => true,
				'delete_cdv_eventos'        => true,
				// Persone
				'edit_cdv_persona'          => true,
				'edit_cdv_personas'         => true,
				'edit_others_cdv_personas'  => true,
				'publish_cdv_personas'      => true,
				'delete_cdv_personas'       => true,
			]
		);

		// CdV Moderatore: solo moderazione proposte
		add_role(
			'cdv_moderatore',
			__( 'CdV Moderatore', 'cronaca-di-viterbo' ),
			[
				'read'                      => true,
				'moderate_cdv_propostas'    => true,
				'edit_cdv_propostas'        => true,
				'publish_cdv_propostas'     => true,
				'moderate_comments'         => true,
			]
		);

		// CdV Reporter: può creare bozze
		add_role(
			'cdv_reporter',
			__( 'CdV Reporter', 'cronaca-di-viterbo' ),
			[
				'read'              => true,
				'edit_posts'        => true,
				'upload_files'      => true,
				'edit_cdv_dossier'  => true,
				'edit_cdv_dossiers' => true,
				'edit_cdv_evento'   => true,
				'edit_cdv_eventos'  => true,
			]
		);

		// Aggiungi capabilities all'amministratore
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$caps = [
				'edit_cdv_dossier',
				'edit_cdv_dossiers',
				'edit_others_cdv_dossiers',
				'publish_cdv_dossiers',
				'read_private_cdv_dossiers',
				'delete_cdv_dossiers',
				'edit_cdv_proposta',
				'edit_cdv_propostas',
				'edit_others_cdv_propostas',
				'publish_cdv_propostas',
				'delete_cdv_propostas',
				'moderate_cdv_propostas',
				'edit_cdv_evento',
				'edit_cdv_eventos',
				'edit_others_cdv_eventos',
				'publish_cdv_eventos',
				'delete_cdv_eventos',
				'edit_cdv_persona',
				'edit_cdv_personas',
				'edit_others_cdv_personas',
				'publish_cdv_personas',
				'delete_cdv_personas',
			];

			foreach ( $caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Rimuove i ruoli personalizzati.
	 */
	public static function remove_roles() {
		remove_role( 'cdv_editor' );
		remove_role( 'cdv_moderatore' );
		remove_role( 'cdv_reporter' );

		// Rimuovi capabilities dall'amministratore
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$caps = [
				'edit_cdv_dossier',
				'edit_cdv_dossiers',
				'edit_others_cdv_dossiers',
				'publish_cdv_dossiers',
				'read_private_cdv_dossiers',
				'delete_cdv_dossiers',
				'edit_cdv_proposta',
				'edit_cdv_propostas',
				'edit_others_cdv_propostas',
				'publish_cdv_propostas',
				'delete_cdv_propostas',
				'moderate_cdv_propostas',
				'edit_cdv_evento',
				'edit_cdv_eventos',
				'edit_others_cdv_eventos',
				'publish_cdv_eventos',
				'delete_cdv_eventos',
				'edit_cdv_persona',
				'edit_cdv_personas',
				'edit_others_cdv_personas',
				'publish_cdv_personas',
				'delete_cdv_personas',
			];

			foreach ( $caps as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}
}
