<?php
/**
 * Shortcode: Mappa Interattiva Proposte/Eventi
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 1.4.0
 */

namespace CdV\Shortcodes;

/**
 * Class MappaInterattiva
 */
class MappaInterattiva {
	/**
	 * Render shortcode
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'tipo'      => 'proposte', // proposte, eventi, petizioni, tutti
				'quartiere' => '',
				'tematica'  => '',
				'height'    => '600px',
				'center'    => '42.4175,12.1089', // Viterbo
				'zoom'      => '13',
			),
			$atts,
			'cdv_mappa'
		);

		// Enqueue Leaflet
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

		// Get markers data
		$markers = self::get_markers( $atts );

		$map_id = 'cdv-map-' . uniqid();
		
		// Valida e parse le coordinate
		$center_parts = explode( ',', $atts['center'] );
		if ( count( $center_parts ) !== 2 ) {
			// Fallback a coordinate di Viterbo se il formato non è valido
			$center_parts = array( '42.4175', '12.1089' );
		}
		list( $lat, $lng ) = $center_parts;
		$lat = floatval( trim( $lat ) );
		$lng = floatval( trim( $lng ) );

		ob_start();
		?>
		<div class="cdv-mappa-wrapper">
			<div id="<?php echo esc_attr( $map_id ); ?>" class="cdv-mappa" style="height: <?php echo esc_attr( $atts['height'] ); ?>"></div>
		</div>

		<script>
		(function() {
			if (typeof L === 'undefined') {
				console.error('Leaflet non caricato');
				return;
			}

			var map = L.map('<?php echo esc_js( $map_id ); ?>').setView([<?php echo esc_js( $lat ); ?>, <?php echo esc_js( $lng ); ?>], <?php echo esc_js( $atts['zoom'] ); ?>);

			// Add OpenStreetMap tiles
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '© OpenStreetMap contributors',
				maxZoom: 19
			}).addTo(map);

			// Markers data
			var markers = <?php echo json_encode( $markers ); ?>;

			// Custom icons
			var icons = {
				proposta: L.icon({
					iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzE2NzhmZiI+PHBhdGggZD0iTTEyIDJDOC4xMyAyIDUgNS4xMyA1IDljMCA1LjI1IDcgMTMgNyAxM3M3LTcuNzUgNy0xM2MwLTMuODctMy4xMy03LTctN3ptMCA5LjVjLTEuMzggMC0yLjUtMS4xMi0yLjUtMi41czEuMTItMi41IDIuNS0yLjUgMi41IDEuMTIgMi41IDIuNS0xLjEyIDIuNS0yLjUgMi41eiIvPjwvc3ZnPg==',
					iconSize: [32, 32],
					iconAnchor: [16, 32],
					popupAnchor: [0, -32]
				}),
				evento: L.icon({
					iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2ZmOTgwMCI+PHBhdGggZD0iTTEyIDJDOC4xMyAyIDUgNS4xMyA1IDljMCA1LjI1IDcgMTMgNyAxM3M3LTcuNzUgNy0xM2MwLTMuODctMy4xMy03LTctN3ptMCA5LjVjLTEuMzggMC0yLjUtMS4xMi0yLjUtMi41czEuMTItMi41IDIuNS0yLjUgMi41IDEuMTIgMi41IDIuNS0xLjEyIDIuNS0yLjUgMi41eiIvPjwvc3ZnPg==',
					iconSize: [32, 32],
					iconAnchor: [16, 32],
					popupAnchor: [0, -32]
				}),
				petizione: L.icon({
					iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2Y0NDMzNiI+PHBhdGggZD0iTTEyIDJDOC4xMyAyIDUgNS4xMyA1IDljMCA1LjI1IDcgMTMgNyAxM3M3LTcuNzUgNy0xM2MwLTMuODctMy4xMy03LTctN3ptMCA5LjVjLTEuMzggMC0yLjUtMS4xMi0yLjUtMi41czEuMTItMi41IDIuNS0yLjUgMi41IDEuMTIgMi41IDIuNS0xLjEyIDIuNS0yLjUgMi41eiIvPjwvc3ZnPg==',
					iconSize: [32, 32],
					iconAnchor: [16, 32],
					popupAnchor: [0, -32]
				})
			};

			// Add markers
			markers.forEach(function(markerData) {
				if (markerData.lat && markerData.lng) {
					var icon = icons[markerData.tipo] || icons.proposta;
					var marker = L.marker([markerData.lat, markerData.lng], { icon: icon }).addTo(map);
					
					var popupContent = '<div class="cdv-marker-popup">' +
						'<h4><a href="' + markerData.link + '">' + markerData.title + '</a></h4>' +
						'<p class="cdv-marker-tipo"><strong>' + markerData.tipo_label + '</strong></p>';
					
					if (markerData.excerpt) {
						popupContent += '<p>' + markerData.excerpt + '</p>';
					}
					
					if (markerData.quartiere) {
						popupContent += '<p class="cdv-marker-quartiere">📍 ' + markerData.quartiere + '</p>';
					}
					
					if (markerData.meta) {
						popupContent += '<p class="cdv-marker-meta">' + markerData.meta + '</p>';
					}
					
					popupContent += '<a href="' + markerData.link + '" class="cdv-marker-link">Dettagli →</a>' +
					'</div>';
					
					marker.bindPopup(popupContent);
				}
			});

			// Fit bounds if multiple markers
			if (markers.length > 1) {
				var bounds = markers
					.filter(function(m) { return m.lat && m.lng; })
					.map(function(m) { return [m.lat, m.lng]; });
				
				if (bounds.length > 0) {
					map.fitBounds(bounds, { padding: [50, 50] });
				}
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get markers data
	 *
	 * @param array $atts Attributes.
	 * @return array
	 */
	private static function get_markers( array $atts ): array {
		$markers = array();
		$post_types = array();

		// Determine post types
		switch ( $atts['tipo'] ) {
			case 'proposte':
				$post_types = array( 'cdv_proposta' );
				break;
			case 'eventi':
				$post_types = array( 'cdv_evento' );
				break;
			case 'petizioni':
				$post_types = array( 'cdv_petizione' );
				break;
			case 'tutti':
			default:
				$post_types = array( 'cdv_proposta', 'cdv_evento', 'cdv_petizione' );
				break;
		}

		foreach ( $post_types as $post_type ) {
			$query_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_cdv_latitudine',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_cdv_longitudine',
						'compare' => 'EXISTS',
					),
				),
			);

			// Filter by quartiere
			if ( ! empty( $atts['quartiere'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'cdv_quartiere',
					'field'    => 'slug',
					'terms'    => $atts['quartiere'],
				);
			}

			// Filter by tematica
			if ( ! empty( $atts['tematica'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'cdv_tematica',
					'field'    => 'slug',
					'terms'    => $atts['tematica'],
				);
			}

			$posts = get_posts( $query_args );

			foreach ( $posts as $post ) {
				$lat = floatval( get_post_meta( $post->ID, '_cdv_latitudine', true ) );
				$lng = floatval( get_post_meta( $post->ID, '_cdv_longitudine', true ) );

				if ( ! $lat || ! $lng ) {
					continue;
				}

				$quartieri = wp_get_post_terms( $post->ID, 'cdv_quartiere' );
				$quartiere_name = ! empty( $quartieri ) ? $quartieri[0]->name : '';

				$tipo_labels = array(
					'cdv_proposta'  => __( 'Proposta', 'cronaca-di-viterbo' ),
					'cdv_evento'    => __( 'Evento', 'cronaca-di-viterbo' ),
					'cdv_petizione' => __( 'Petizione', 'cronaca-di-viterbo' ),
				);

				$meta = '';
				if ( $post_type === 'cdv_proposta' ) {
					$voti = intval( get_post_meta( $post->ID, '_cdv_votes', true ) );
					$meta = sprintf( __( '%d voti', 'cronaca-di-viterbo' ), $voti );
				} elseif ( $post_type === 'cdv_evento' ) {
					$data = get_post_meta( $post->ID, '_cdv_data_inizio', true );
					if ( $data ) {
						$meta = date_i18n( get_option( 'date_format' ), strtotime( $data ) );
					}
				} elseif ( $post_type === 'cdv_petizione' ) {
					$firme = intval( get_post_meta( $post->ID, '_cdv_firme_count', true ) );
					$soglia = intval( get_post_meta( $post->ID, '_cdv_soglia_firme', true ) );
					$meta = sprintf( __( '%d/%d firme', 'cronaca-di-viterbo' ), $firme, $soglia );
				}

				$markers[] = array(
					'lat'        => $lat,
					'lng'        => $lng,
					'title'      => get_the_title( $post ),
					'link'       => get_permalink( $post ),
					'excerpt'    => wp_trim_words( get_the_excerpt( $post ), 20 ),
					'tipo'       => str_replace( 'cdv_', '', $post_type ),
					'tipo_label' => $tipo_labels[ $post_type ],
					'quartiere'  => $quartiere_name,
					'meta'       => $meta,
				);
			}
		}

		return $markers;
	}
}
