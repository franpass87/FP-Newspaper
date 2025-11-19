<?php
/**
 * Calendario Editoriale
 *
 * @package FPNewspaper\Editorial
 */

namespace FPNewspaper\Editorial;

use FPNewspaper\Logger;
use FPNewspaper\Workflow\WorkflowManager;

defined('ABSPATH') || exit;

/**
 * Gestisce il calendario editoriale con pianificazione pubblicazioni
 */
class Calendar {
    
    /**
     * Meta keys
     */
    const META_SCHEDULED_DATE = '_fp_scheduled_date';
    const META_SCHEDULED_SLOT = '_fp_scheduled_slot';  // morning, afternoon, evening
    const META_ASSIGNED_AUTHOR = '_fp_assigned_author';
    
    /**
     * Costruttore
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_fp_get_calendar_events', [$this, 'ajax_get_calendar_events']);
        add_action('wp_ajax_fp_schedule_article', [$this, 'ajax_schedule_article']);
        add_action('wp_ajax_fp_unschedule_article', [$this, 'ajax_unschedule_article']);
        add_action('wp_ajax_fp_update_schedule', [$this, 'ajax_update_schedule']);
    }
    
    /**
     * Ottiene eventi calendario per un range di date
     *
     * @param string $start_date Data inizio (Y-m-d)
     * @param string $end_date Data fine (Y-m-d)
     * @return array Eventi formato FullCalendar
     */
    public function get_calendar_events($start_date, $end_date) {
        global $wpdb;
        
        // Query articoli schedulati nel range
        $args = [
            'post_type' => 'post',
            'post_status' => ['future', 'fp_scheduled', 'fp_approved', 'fp_in_review'],
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true,
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        $events = [];
        
        foreach ($query->posts as $post) {
            $scheduled_date = get_post_meta($post->ID, self::META_SCHEDULED_DATE, true);
            $slot = get_post_meta($post->ID, self::META_SCHEDULED_SLOT, true);
            $assigned_author = get_post_meta($post->ID, self::META_ASSIGNED_AUTHOR, true);
            
            // Use post_date se scheduled_date non impostato
            $event_date = $scheduled_date ?: $post->post_date;
            
            // Colore base su stato
            $color = $this->get_status_color($post->post_status);
            
            $events[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'start' => $event_date,
                'url' => get_edit_post_link($post->ID, 'raw'),
                'color' => $color,
                'extendedProps' => [
                    'status' => $post->post_status,
                    'status_label' => $this->get_status_label($post->post_status),
                    'author' => get_the_author_meta('display_name', $post->post_author),
                    'assigned_author' => $assigned_author ? get_the_author_meta('display_name', $assigned_author) : null,
                    'slot' => $slot,
                    'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
                ],
            ];
        }
        
        Logger::debug('Calendar events retrieved', [
            'start' => $start_date,
            'end' => $end_date,
            'count' => count($events),
        ]);
        
        return $events;
    }
    
    /**
     * Programma un articolo per una data specifica
     *
     * @param int $post_id
     * @param string $scheduled_date Data programmata (Y-m-d H:i:s)
     * @param string $slot Slot giornaliero (morning, afternoon, evening)
     * @param int $assigned_author Author ID (opzionale)
     * @return bool|WP_Error
     */
    public function schedule_article($post_id, $scheduled_date, $slot = 'morning', $assigned_author = null) {
        $post = get_post($post_id);
        
        if (!$post || 'post' !== $post->post_type) {
            return new \WP_Error('invalid_post', __('Post non valido', 'fp-newspaper'));
        }
        
        // Verifica permessi
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('permission_denied', __('Permessi insufficienti', 'fp-newspaper'));
        }
        
        // Verifica conflitti
        $conflicts = $this->check_schedule_conflicts($scheduled_date, $slot, $post_id);
        if (!empty($conflicts)) {
            return new \WP_Error('schedule_conflict', __('Conflitto: Slot già occupato', 'fp-newspaper'), $conflicts);
        }
        
        // Salva scheduling
        update_post_meta($post_id, self::META_SCHEDULED_DATE, sanitize_text_field($scheduled_date));
        update_post_meta($post_id, self::META_SCHEDULED_SLOT, sanitize_text_field($slot));
        
        if ($assigned_author) {
            update_post_meta($post_id, self::META_ASSIGNED_AUTHOR, absint($assigned_author));
        }
        
        // Aggiorna post_date se necessario
        $update_data = [
            'ID' => $post_id,
            'post_date' => $scheduled_date,
            'post_date_gmt' => get_gmt_from_date($scheduled_date),
        ];
        
        // Se data futura e approved, imposta come future
        if (strtotime($scheduled_date) > current_time('timestamp') && $post->post_status === 'fp_approved') {
            $update_data['post_status'] = 'future';
        }
        
        wp_update_post($update_data);
        
        Logger::info('Article scheduled', [
            'post_id' => $post_id,
            'date' => $scheduled_date,
            'slot' => $slot,
        ]);
        
        do_action('fp_newspaper_article_scheduled', $post_id, $scheduled_date);
        
        return true;
    }
    
    /**
     * Rimuove scheduling da articolo
     *
     * @param int $post_id
     * @return bool
     */
    public function unschedule_article($post_id) {
        delete_post_meta($post_id, self::META_SCHEDULED_DATE);
        delete_post_meta($post_id, self::META_SCHEDULED_SLOT);
        delete_post_meta($post_id, self::META_ASSIGNED_AUTHOR);
        
        Logger::info('Article unscheduled', ['post_id' => $post_id]);
        
        do_action('fp_newspaper_article_unscheduled', $post_id);
        
        return true;
    }
    
    /**
     * Verifica conflitti di scheduling
     *
     * @param string $date
     * @param string $slot
     * @param int $exclude_post_id
     * @return array Post in conflitto
     */
    public function check_schedule_conflicts($date, $slot, $exclude_post_id = null) {
        $date_only = date('Y-m-d', strtotime($date));
        
        $args = [
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => self::META_SCHEDULED_DATE,
                    'value' => [$date_only . ' 00:00:00', $date_only . ' 23:59:59'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME',
                ],
                [
                    'key' => self::META_SCHEDULED_SLOT,
                    'value' => $slot,
                ],
            ],
        ];
        
        if ($exclude_post_id) {
            $args['post__not_in'] = [$exclude_post_id];
        }
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Ottiene slot disponibili per una data
     *
     * @param string $date
     * @return array
     */
    public function get_available_slots($date) {
        $all_slots = ['morning', 'afternoon', 'evening'];
        $available = [];
        
        foreach ($all_slots as $slot) {
            $conflicts = $this->check_schedule_conflicts($date, $slot);
            $available[$slot] = [
                'available' => empty($conflicts),
                'count' => count($conflicts),
            ];
        }
        
        return $available;
    }
    
    /**
     * Esporta calendario in formato iCal
     *
     * @param string $start_date
     * @param string $end_date
     * @return string iCal format
     */
    public function export_to_ical($start_date, $end_date) {
        $events = $this->get_calendar_events($start_date, $end_date);
        
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//FP Newspaper//Editorial Calendar//IT\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        
        foreach ($events as $event) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $event['id'] . "@" . get_bloginfo('url') . "\r\n";
            $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ical .= "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($event['start'])) . "\r\n";
            $ical .= "SUMMARY:" . $this->ical_escape($event['title']) . "\r\n";
            $ical .= "DESCRIPTION:Stato: " . $this->ical_escape($event['extendedProps']['status_label']) . "\r\n";
            $ical .= "URL:" . $event['url'] . "\r\n";
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "END:VEVENT\r\n";
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        return $ical;
    }
    
    /**
     * Escape testo per iCal
     */
    private function ical_escape($text) {
        return str_replace(["\r\n", "\n", "\r", ",", ";"], ["\\n", "\\n", "\\n", "\\,", "\\;"], $text);
    }
    
    /**
     * Ottiene colore per stato
     *
     * @param string $status
     * @return string
     */
    private function get_status_color($status) {
        $colors = [
            'draft' => '#95a5a6',
            'fp_in_review' => '#f39c12',
            'fp_needs_changes' => '#e74c3c',
            'fp_approved' => '#27ae60',
            'fp_scheduled' => '#3498db',
            'future' => '#9b59b6',
            'publish' => '#2ecc71',
        ];
        
        return $colors[$status] ?? '#7f8c8d';
    }
    
    /**
     * Ottiene label per stato
     */
    private function get_status_label($status) {
        $labels = [
            'draft' => __('Bozza', 'fp-newspaper'),
            'fp_in_review' => __('In Revisione', 'fp-newspaper'),
            'fp_needs_changes' => __('Richiede Modifiche', 'fp-newspaper'),
            'fp_approved' => __('Approvato', 'fp-newspaper'),
            'fp_scheduled' => __('Programmato', 'fp-newspaper'),
            'future' => __('Pubblicazione Futura', 'fp-newspaper'),
            'publish' => __('Pubblicato', 'fp-newspaper'),
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * AJAX: Get calendar events
     */
    public function ajax_get_calendar_events() {
        check_ajax_referer('fp_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $start = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-01');
        $end = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-t');
        
        $events = $this->get_calendar_events($start, $end);
        
        wp_send_json_success($events);
    }
    
    /**
     * AJAX: Schedule article
     */
    public function ajax_schedule_article() {
        check_ajax_referer('fp_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $slot = isset($_POST['slot']) ? sanitize_text_field($_POST['slot']) : 'morning';
        
        $result = $this->schedule_article($post_id, $date, $slot);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Articolo programmato', 'fp-newspaper'));
    }
    
    /**
     * AJAX: Unschedule article
     */
    public function ajax_unschedule_article() {
        check_ajax_referer('fp_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        $this->unschedule_article($post_id);
        
        wp_send_json_success(__('Scheduling rimosso', 'fp-newspaper'));
    }
    
    /**
     * AJAX: Update schedule (drag & drop)
     */
    public function ajax_update_schedule() {
        check_ajax_referer('fp_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        
        if (!$post_id || !$new_date) {
            wp_send_json_error(__('Dati mancanti', 'fp-newspaper'));
        }
        
        // Ottieni slot esistente o usa morning
        $slot = get_post_meta($post_id, self::META_SCHEDULED_SLOT, true) ?: 'morning';
        
        $result = $this->schedule_article($post_id, $new_date, $slot);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Data aggiornata', 'fp-newspaper'));
    }
    
    /**
     * Ottiene statistiche calendario
     *
     * @param string $month Mese (Y-m)
     * @return array
     */
    public function get_month_stats($month = null) {
        $month = $month ?: date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        
        $events = $this->get_calendar_events($start, $end);
        
        $stats = [
            'total' => count($events),
            'by_status' => [],
            'by_day' => [],
        ];
        
        foreach ($events as $event) {
            $status = $event['extendedProps']['status'];
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;
            
            $day = date('Y-m-d', strtotime($event['start']));
            if (!isset($stats['by_day'][$day])) {
                $stats['by_day'][$day] = 0;
            }
            $stats['by_day'][$day]++;
        }
        
        return $stats;
    }
}


