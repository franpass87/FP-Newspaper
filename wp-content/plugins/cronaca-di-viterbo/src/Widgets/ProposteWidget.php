<?php
/**
 * Widget: Proposte Popolari
 *
 * @package CdV
 * @subpackage Widgets
 * @since 1.6.0
 */

namespace CdV\Widgets;

/**
 * Class ProposteWidget
 */
class ProposteWidget extends \WP_Widget {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'cdv_proposte_widget',
			__( 'CdV - Proposte Popolari', 'cronaca-di-viterbo' ),
			array(
				'description' => __( 'Mostra le proposte più votate', 'cronaca-di-viterbo' ),
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
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Proposte Popolari', 'cronaca-di-viterbo' );
		$limit = ! empty( $instance['limit'] ) ? intval( $instance['limit'] ) : 5;
		$quartiere = ! empty( $instance['quartiere'] ) ? $instance['quartiere'] : '';

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		$query_args = array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => '_cdv_votes',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		);

		if ( ! empty( $quartiere ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'cdv_quartiere',
					'field'    => 'slug',
					'terms'    => $quartiere,
				),
			);
		}

		$proposte = new \WP_Query( $query_args );

		if ( $proposte->have_posts() ) {
			echo '<ul class="cdv-widget-proposte">';
			while ( $proposte->have_posts() ) {
				$proposte->the_post();
				$voti = intval( get_post_meta( get_the_ID(), '_cdv_votes', true ) );
				?>
				<li class="cdv-widget-proposta-item">
					<a href="<?php the_permalink(); ?>" class="cdv-widget-proposta-title">
						<?php the_title(); ?>
					</a>
					<span class="cdv-widget-proposta-votes">
						👍 <?php echo esc_html( number_format_i18n( $voti ) ); ?>
					</span>
				</li>
				<?php
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'Nessuna proposta ancora', 'cronaca-di-viterbo' ) . '</p>';
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
		$quartiere = ! empty( $instance['quartiere'] ) ? $instance['quartiere'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Titolo:', 'cronaca-di-viterbo' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
				<?php esc_html_e( 'Numero proposte:', 'cronaca-di-viterbo' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>" min="1" max="20">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'quartiere' ) ); ?>">
				<?php esc_html_e( 'Filtra per quartiere:', 'cronaca-di-viterbo' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quartiere' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quartiere' ) ); ?>">
				<option value=""><?php esc_html_e( 'Tutti i quartieri', 'cronaca-di-viterbo' ); ?></option>
				<?php
				$quartieri = get_terms( array( 'taxonomy' => 'cdv_quartiere', 'hide_empty' => false ) );
				if ( ! is_wp_error( $quartieri ) && ! empty( $quartieri ) ) {
					foreach ( $quartieri as $q ) {
						echo '<option value="' . esc_attr( $q->slug ) . '"' . selected( $quartiere, $q->slug, false ) . '>' . esc_html( $q->name ) . '</option>';
					}
				}
				?>
			</select>
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
		$instance['quartiere'] = ! empty( $new_instance['quartiere'] ) ? sanitize_text_field( $new_instance['quartiere'] ) : '';
		return $instance;
	}
}
