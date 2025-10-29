<?php
/**
 * Widget: Ultimi Articoli
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget per mostrare ultimi articoli in sidebar
 */
class LatestArticles extends \WP_Widget {
    
    /**
     * Costruttore
     */
    public function __construct() {
        parent::__construct(
            'fp_latest_articles',
            __('FP Newspaper - Ultimi Articoli', 'fp-newspaper'),
            [
                'description' => __('Mostra gli ultimi articoli pubblicati', 'fp-newspaper'),
                'classname'   => 'fp-widget-latest-articles',
            ]
        );
    }
    
    /**
     * Output widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) 
                 ? $instance['title'] 
                 : __('Ultimi Articoli', 'fp-newspaper');
        
        $count = !empty($instance['count']) ? absint($instance['count']) : 5;
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $show_date = !empty($instance['show_date']);
        
        $query_args = [
            'post_type'      => 'fp_article',
            'posts_per_page' => min($count, 20),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ];
        
        $query = new \WP_Query($query_args);
        
        if ($query->have_posts()) {
            echo $args['before_widget'];
            
            if ($title) {
                echo $args['before_title'] . esc_html($title) . $args['after_title'];
            }
            
            echo '<ul class="fp-widget-articles-list">';
            
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li class="fp-widget-article-item">';
                
                if ($show_thumbnail && has_post_thumbnail()) {
                    echo '<div class="fp-widget-thumb">';
                    echo '<a href="' . esc_url(get_permalink()) . '">';
                    the_post_thumbnail('thumbnail');
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '<div class="fp-widget-content">';
                echo '<a href="' . esc_url(get_permalink()) . '" class="fp-widget-title">';
                echo esc_html(get_the_title());
                echo '</a>';
                
                if ($show_date) {
                    echo '<span class="fp-widget-date">' . esc_html(get_the_date()) . '</span>';
                }
                echo '</div>';
                
                echo '</li>';
            }
            
            echo '</ul>';
            
            wp_reset_postdata();
            
            echo $args['after_widget'];
        }
    }
    
    /**
     * Form impostazioni widget
     *
     * @param array $instance
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $count = !empty($instance['count']) ? absint($instance['count']) : 5;
        $show_thumbnail = !empty($instance['show_thumbnail']);
        $show_date = !empty($instance['show_date']);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Titolo:', 'fp-newspaper'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>">
                <?php esc_html_e('Numero di articoli:', 'fp-newspaper'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('count')); ?>" 
                   type="number" 
                   min="1" 
                   max="20" 
                   value="<?php echo esc_attr($count); ?>">
        </p>
        
        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_thumbnail')); ?>" 
                   <?php checked($show_thumbnail); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_thumbnail')); ?>">
                <?php esc_html_e('Mostra immagine in evidenza', 'fp-newspaper'); ?>
            </label>
        </p>
        
        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>" 
                   <?php checked($show_date); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>">
                <?php esc_html_e('Mostra data', 'fp-newspaper'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Aggiorna impostazioni widget
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        
        $instance['title'] = !empty($new_instance['title']) 
                             ? sanitize_text_field($new_instance['title']) 
                             : '';
        
        $instance['count'] = !empty($new_instance['count']) 
                             ? min(absint($new_instance['count']), 20) 
                             : 5;
        
        $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']);
        $instance['show_date'] = !empty($new_instance['show_date']);
        
        return $instance;
    }
}

