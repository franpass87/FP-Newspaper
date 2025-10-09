<?php
/**
 * Widget: Statistiche Community
 *
 * @package CdV
 * @subpackage Widgets
 * @since 1.6.0
 */

namespace CdV\Widgets;

/**
 * Class StatsWidget
 */
class StatsWidget extends \WP_Widget {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'cdv_stats_widget',
			__( 'CdV - Statistiche Community', 'cronaca-di-viterbo' ),
			array(
				'description' => __( 'Mostra statistiche partecipazione', 'cronaca-di-viterbo' ),
			)
		);
	}

	/**
	 * Widget output
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'La Nostra Community', 'cronaca-di-viterbo' );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// Get stats
		$proposte = wp_count_posts( 'cdv_proposta' )->publish;
		$petizioni = wp_count_posts( 'cdv_petizione' )->publish;
		$eventi = wp_count_posts( 'cdv_evento' )->publish;
		
		global $wpdb;
		$firme_totali = $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_cdv_firme_count'" );
		$voti_totali = $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_cdv_votes'" );

		?>
		<div class="cdv-widget-stats">
			<div class="cdv-widget-stat">
				<span class="cdv-widget-stat-value"><?php echo esc_html( number_format_i18n( $proposte ) ); ?></span>
				<span class="cdv-widget-stat-label"><?php esc_html_e( 'Proposte', 'cronaca-di-viterbo' ); ?></span>
			</div>
			<div class="cdv-widget-stat">
				<span class="cdv-widget-stat-value"><?php echo esc_html( number_format_i18n( intval( $firme_totali ) ) ); ?></span>
				<span class="cdv-widget-stat-label"><?php esc_html_e( 'Firme', 'cronaca-di-viterbo' ); ?></span>
			</div>
			<div class="cdv-widget-stat">
				<span class="cdv-widget-stat-value"><?php echo esc_html( number_format_i18n( intval( $voti_totali ) ) ); ?></span>
				<span class="cdv-widget-stat-label"><?php esc_html_e( 'Voti', 'cronaca-di-viterbo' ); ?></span>
			</div>
			<div class="cdv-widget-stat">
				<span class="cdv-widget-stat-value"><?php echo esc_html( number_format_i18n( $eventi ) ); ?></span>
				<span class="cdv-widget-stat-label"><?php esc_html_e( 'Eventi', 'cronaca-di-viterbo' ); ?></span>
			</div>
		</div>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Widget form
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Titolo:', 'cronaca-di-viterbo' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/**
	 * Update widget
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		return $instance;
	}
}
