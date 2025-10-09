<?php
/**
 * Service per JSON-LD e Schema.org.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per generare markup Schema.org.
 */
class Schema {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'output_schema' ] );
	}

	/**
	 * Output del JSON-LD nel footer.
	 */
	public function output_schema() {
		if ( is_singular( 'cdv_dossier' ) ) {
			$this->output_news_article();
		} elseif ( is_singular( 'cdv_evento' ) ) {
			$this->output_event();
		} elseif ( is_singular( 'cdv_persona' ) ) {
			$this->output_person();
		}
	}

	/**
	 * Schema NewsArticle per dossier.
	 */
	private function output_news_article() {
		global $post;

		$schema = [
			'@context'      => 'https://schema.org',
			'@type'         => 'NewsArticle',
			'headline'      => get_the_title(),
			'description'   => get_the_excerpt(),
			'datePublished' => get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'author'        => [
				'@type' => 'Person',
				'name'  => get_the_author(),
			],
		];

		if ( has_post_thumbnail() ) {
			$schema['image'] = get_the_post_thumbnail_url( $post->ID, 'large' );
		}

		$this->render_schema( $schema );
	}

	/**
	 * Schema Event per eventi.
	 */
	private function output_event() {
		global $post;

		$data = get_post_meta( $post->ID, '_cdv_evento_data', true );
		$ora = get_post_meta( $post->ID, '_cdv_evento_ora', true );
		$luogo = get_post_meta( $post->ID, '_cdv_evento_luogo', true );
		$indirizzo = get_post_meta( $post->ID, '_cdv_evento_indirizzo', true );

		if ( ! $data ) {
			return;
		}

		$start_date = $data . ( $ora ? 'T' . $ora : 'T12:00:00' );

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Event',
			'name'        => get_the_title(),
			'description' => get_the_excerpt(),
			'startDate'   => $start_date,
		];

		if ( $luogo || $indirizzo ) {
			$schema['location'] = [
				'@type'   => 'Place',
				'name'    => $luogo ?: '',
				'address' => $indirizzo ?: '',
			];
		}

		if ( has_post_thumbnail() ) {
			$schema['image'] = get_the_post_thumbnail_url( $post->ID, 'large' );
		}

		$this->render_schema( $schema );
	}

	/**
	 * Schema Person per persone.
	 */
	private function output_person() {
		global $post;

		$ruolo = get_post_meta( $post->ID, '_cdv_persona_ruolo', true );
		$email = get_post_meta( $post->ID, '_cdv_persona_email', true );

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Person',
			'name'        => get_the_title(),
			'description' => get_the_excerpt(),
		];

		if ( $ruolo ) {
			$schema['jobTitle'] = $ruolo;
		}

		if ( $email ) {
			$schema['email'] = $email;
		}

		if ( has_post_thumbnail() ) {
			$schema['image'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
		}

		$this->render_schema( $schema );
	}

	/**
	 * Render del JSON-LD.
	 *
	 * @param array $schema Array schema.
	 */
	private function render_schema( $schema ) {
		echo '<script type="application/ld+json">';
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo '</script>';
	}
}
