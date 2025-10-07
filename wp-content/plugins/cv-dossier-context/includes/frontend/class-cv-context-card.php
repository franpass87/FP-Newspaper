<?php
/**
 * Context Card Renderer
 *
 * Gestisce il rendering delle schede riassuntive dei dossier.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per rendering schede contesto.
 */
class CV_Context_Card {
    
    /**
     * Renderizza la scheda riassuntiva di un dossier.
     *
     * @param int  $dossier_id ID del dossier.
     * @param bool $compact    Se true, modalità compatta.
     * @return string HTML della scheda o stringa vuota.
     */
    public function render( $dossier_id, $compact = false ) {
        $post = get_post( $dossier_id );
        if ( ! $post || $post->post_type !== 'cv_dossier' ) {
            return '';
        }
        
        $status = get_post_meta( $dossier_id, '_cv_status', true ) ?: 'open';
        $score  = intval( get_post_meta( $dossier_id, '_cv_score', true ) );
        $facts  = get_post_meta( $dossier_id, '_cv_facts', true );
        $actors = get_post_meta( $dossier_id, '_cv_actors', true );
        
        $events = get_posts([
            'post_type'   => 'cv_dossier_event',
            'numberposts' => 3,
            'post_parent' => $dossier_id,
            'meta_key'    => '_cv_date',
            'orderby'     => 'meta_value',
            'order'       => 'DESC',
        ]);
        $last = $events ? get_post_meta( $events[0]->ID, '_cv_date', true ) : '';
        
        ob_start(); ?>
        <aside class="cv-card" data-ga4="dossier_context" data-dossier="<?php echo esc_attr($dossier_id); ?>">
            <div class="cv-card__head">
                <span class="cv-badge <?php echo $status==='open'?'open':'closed'; ?>">
                    <?php echo $status === 'open' ? esc_html__( 'Dossier aperto', 'cv-dossier' ) : esc_html__( 'Dossier chiuso', 'cv-dossier' ); ?>
                </span>
                <h3 class="cv-card__title">
                    <a href="<?php echo esc_url( get_permalink($dossier_id) ); ?>">
                        <?php echo esc_html( get_the_title($dossier_id) ); ?>
                    </a>
                </h3>
                <div class="cv-score" title="<?php echo esc_attr__( 'Promesse mantenute', 'cv-dossier' ); ?>"><?php echo intval($score); ?>%</div>
            </div>

            <div class="cv-card__body">
                <?php if ( $facts ) : ?>
                    <ul class="cv-facts">
                        <?php foreach ( preg_split("/\r\n|\n|\r/", $facts ) as $li ) {
                            $li = trim($li); if (!$li) continue;
                            echo '<li>'. esc_html($li) .'</li>';
                        } ?>
                    </ul>
                <?php endif; ?>

                <?php if ( $actors ) : ?>
                    <div class="cv-actors"><strong><?php esc_html_e( 'Attori/Enti:', 'cv-dossier' ); ?></strong> <?php echo esc_html($actors); ?></div>
                <?php endif; ?>

                <?php if ( $last ) : ?>
                    <div class="cv-last"><strong><?php esc_html_e( 'Ultimo evento:', 'cv-dossier' ); ?></strong> <?php echo esc_html( $last ); ?></div>
                <?php endif; ?>
            </div>

            <div class="cv-card__cta">
                <a class="cv-btn" href="<?php echo esc_url( get_permalink($dossier_id) ); ?>" data-ga4="open_dossier"><?php esc_html_e( 'Tutto il dossier', 'cv-dossier' ); ?></a>
                <form class="cv-follow" method="post" data-ga4="follow_dossier">
                    <input type="email" name="email" placeholder="<?php echo esc_attr__( 'La tua email per gli aggiornamenti', 'cv-dossier' ); ?>" required />
                    <input type="hidden" name="dossier_id" value="<?php echo esc_attr($dossier_id); ?>"/>
                    <button type="submit" class="cv-btn"><?php esc_html_e( 'Segui', 'cv-dossier' ); ?></button>
                </form>
            </div>
        </aside>
        <?php
        return ob_get_clean();
    }
}