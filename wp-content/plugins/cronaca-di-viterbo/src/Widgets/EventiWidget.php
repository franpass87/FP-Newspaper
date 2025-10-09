<?php
/**
 * Widget: Eventi in Arrivo
 *
 * @package CdV
 * @subpackage Widgets
 * @since 1.6.0
 */

namespace CdV\Widgets;

/**
 * Class EventiWidget
 */
class EventiWidget extends \WP_Widget {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'cdv_eventi_widget',
			__( 'CdV - Eventi in Arrivo', 'cronaca-di-viterbo' ),
			array(
				'description' => __( 'Mostra i prossimi eventi', 'cronaca-di-viterbo' ),
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
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Eventi in Arrivo', 'cronaca-di-viterbo' );
		$limit = ! empty( $instance['limit'] ) ? intval( $instance['limit'] ) : 5;

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		$query_args = array(
			'post_type'      => 'cdv_evento',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => '_cdv_data_inizio',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_cdv_data_inizio',
					'value'   => current_time( 'mysql' ),
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			),
		);

		$eventi = new \WP_Query( $query_args );

		if ( $eventi->have_posts() ) {
			echo '<ul class="cdv-widget-eventi">';
			while ( $eventi->have_posts() ) {
				$eventi->the_post();
				$data = get_post_meta( get_the_ID(), '_cdv_data_inizio', true );
				$luogo = get_post_meta( get_the_ID(), '_cdv_luogo', true );
				?>
				<li class="cdv-widget-evento-item">
					<a href="<?php the_permalink(); ?>" class="cdv-widget-evento-title">
						<?php the_title(); ?>
					</a>
					<?php if ( $data ) : ?>
						<span class="cdv-widget-evento-data">
							📅 <?php echo esc_html( date_i18n( 'j M', strtotime( $data ) ) ); ?>
						</span>
					<?php endif; ?>
					<?php if ( $luogo ) : ?>
						<span class="cdv-widget-evento-luogo">
							📍 <?php echo esc_html( $luogo ); ?>
						</span>
					<?php endif; ?>
				</li>
				<?php
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'Nessun evento in programma', 'cronaca-di-viterbo' ) . '</p>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Widget form
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$limit = ! empty( $instance['limit'] ) ? $instance['limit'] : 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Titolo:', 'cronaca-di-viterbo' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
				<?php esc_html_e( 'Numero eventi:', 'cronaca-di-viterbo' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>" min="1" max="20">
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
		$instance['limit'] = ! empty( $new_instance['limit'] ) ? intval( $new_instance['limit'] ) : 5;
		return $instance;
	}
}
