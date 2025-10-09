<?php
/**
 * WPBakery Integration - Mapping shortcodes.
 *
 * @package CdV\WPBakery
 */

namespace CdV\WPBakery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per l'integrazione con WPBakery Page Builder.
 */
class Map {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'vc_before_init', [ $this, 'map_shortcodes' ] );
	}

	/**
	 * Mappa tutti gli shortcodes come elementi WPBakery.
	 */
	public function map_shortcodes() {
		$this->map_proposta_form();
		$this->map_proposte_list();
		$this->map_dossier_hero();
		$this->map_eventi_list();
		$this->map_persona_card();
	}

	/**
	 * Mappa [cdv_proposta_form].
	 */
	private function map_proposta_form() {
		vc_map(
			[
				'name'     => __( 'Form Proposta', 'cronaca-di-viterbo' ),
				'base'     => 'cdv_proposta_form',
				'category' => __( 'Cronaca di Viterbo', 'cronaca-di-viterbo' ),
				'icon'     => 'icon-wpb-cdv-proposta-form',
				'params'   => [
					[
						'type'        => 'textfield',
						'heading'     => __( 'Titolo', 'cronaca-di-viterbo' ),
						'param_name'  => 'title',
						'value'       => __( 'Invia una Proposta', 'cronaca-di-viterbo' ),
						'description' => __( 'Titolo del form', 'cronaca-di-viterbo' ),
					],
				],
			]
		);
	}

	/**
	 * Mappa [cdv_proposte].
	 */
	private function map_proposte_list() {
		vc_map(
			[
				'name'     => __( 'Lista Proposte', 'cronaca-di-viterbo' ),
				'base'     => 'cdv_proposte',
				'category' => __( 'Cronaca di Viterbo', 'cronaca-di-viterbo' ),
				'icon'     => 'icon-wpb-cdv-proposte',
				'params'   => [
					[
						'type'        => 'textfield',
						'heading'     => __( 'Numero di proposte', 'cronaca-di-viterbo' ),
						'param_name'  => 'limit',
						'value'       => '10',
						'description' => __( 'Numero massimo di proposte da visualizzare', 'cronaca-di-viterbo' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => __( 'Quartiere (slug)', 'cronaca-di-viterbo' ),
						'param_name'  => 'quartiere',
						'description' => __( 'Filtra per quartiere (inserire slug)', 'cronaca-di-viterbo' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => __( 'Tematica (slug)', 'cronaca-di-viterbo' ),
						'param_name'  => 'tematica',
						'description' => __( 'Filtra per tematica (inserire slug)', 'cronaca-di-viterbo' ),
					],
					[
						'type'       => 'dropdown',
						'heading'    => __( 'Ordina per', 'cronaca-di-viterbo' ),
						'param_name' => 'orderby',
						'value'      => [
							__( 'Data', 'cronaca-di-viterbo' )  => 'date',
							__( 'Titolo', 'cronaca-di-viterbo' ) => 'title',
						],
					],
					[
						'type'       => 'dropdown',
						'heading'    => __( 'Ordine', 'cronaca-di-viterbo' ),
						'param_name' => 'order',
						'value'      => [
							__( 'Decrescente', 'cronaca-di-viterbo' ) => 'DESC',
							__( 'Crescente', 'cronaca-di-viterbo' )   => 'ASC',
						],
					],
				],
			]
		);
	}

	/**
	 * Mappa [cdv_dossier_hero].
	 */
	private function map_dossier_hero() {
		vc_map(
			[
				'name'     => __( 'Hero Dossier', 'cronaca-di-viterbo' ),
				'base'     => 'cdv_dossier_hero',
				'category' => __( 'Cronaca di Viterbo', 'cronaca-di-viterbo' ),
				'icon'     => 'icon-wpb-cdv-dossier-hero',
				'params'   => [
					[
						'type'        => 'textfield',
						'heading'     => __( 'ID Dossier', 'cronaca-di-viterbo' ),
						'param_name'  => 'id',
						'description' => __( 'ID del dossier (lasciare vuoto per dossier corrente)', 'cronaca-di-viterbo' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => __( 'Testo CTA', 'cronaca-di-viterbo' ),
						'param_name'  => 'cta',
						'value'       => __( 'Approfondisci', 'cronaca-di-viterbo' ),
						'description' => __( 'Testo del pulsante call-to-action', 'cronaca-di-viterbo' ),
					],
				],
			]
		);
	}

	/**
	 * Mappa [cdv_eventi].
	 */
	private function map_eventi_list() {
		vc_map(
			[
				'name'     => __( 'Lista Eventi', 'cronaca-di-viterbo' ),
				'base'     => 'cdv_eventi',
				'category' => __( 'Cronaca di Viterbo', 'cronaca-di-viterbo' ),
				'icon'     => 'icon-wpb-cdv-eventi',
				'params'   => [
					[
						'type'        => 'textfield',
						'heading'     => __( 'Numero di eventi', 'cronaca-di-viterbo' ),
						'param_name'  => 'limit',
						'value'       => '6',
						'description' => __( 'Numero massimo di eventi da visualizzare', 'cronaca-di-viterbo' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => __( 'Quartiere (slug)', 'cronaca-di-viterbo' ),
						'param_name'  => 'quartiere',
						'description' => __( 'Filtra per quartiere (inserire slug)', 'cronaca-di-viterbo' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => __( 'Tematica (slug)', 'cronaca-di-viterbo' ),
						'param_name'  => 'tematica',
						'description' => __( 'Filtra per tematica (inserire slug)', 'cronaca-di-viterbo' ),
					],
					[
						'type'       => 'dropdown',
						'heading'    => __( 'Solo eventi futuri', 'cronaca-di-viterbo' ),
						'param_name' => 'upcoming',
						'value'      => [
							__( 'Sì', 'cronaca-di-viterbo' )  => 'yes',
							__( 'No', 'cronaca-di-viterbo' )  => 'no',
						],
					],
				],
			]
		);
	}

	/**
	 * Mappa [cdv_persona_card].
	 */
	private function map_persona_card() {
		vc_map(
			[
				'name'     => __( 'Card Persona', 'cronaca-di-viterbo' ),
				'base'     => 'cdv_persona_card',
				'category' => __( 'Cronaca di Viterbo', 'cronaca-di-viterbo' ),
				'icon'     => 'icon-wpb-cdv-persona',
				'params'   => [
					[
						'type'        => 'textfield',
						'heading'     => __( 'ID Persona', 'cronaca-di-viterbo' ),
						'param_name'  => 'id',
						'description' => __( 'ID della persona da visualizzare', 'cronaca-di-viterbo' ),
					],
				],
			]
		);
	}
}
