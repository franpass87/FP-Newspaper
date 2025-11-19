<?php
/**
 * Editorial Dashboard - Metriche e Analytics Redazionali
 *
 * @package FPNewspaper\Editorial
 */

namespace FPNewspaper\Editorial;

use FPNewspaper\Logger;
use FPNewspaper\Workflow\WorkflowManager;
use FPNewspaper\Cache\Manager as CacheManager;

defined('ABSPATH') || exit;

/**
 * Dashboard redazionale con metriche e statistiche team
 */
class Dashboard {
    
    /**
     * Cache TTL
     */
    const CACHE_TTL = 300; // 5 minuti
    
    /**
     * Ottiene tutte le metriche dashboard
     *
     * @return array
     */
    public function get_dashboard_data() {
        return CacheManager::get('editorial_dashboard_data', function() {
            return [
                'overview' => $this->get_overview_stats(),
                'team_performance' => $this->get_team_performance(),
                'pipeline' => $this->get_pipeline_stats(),
                'recent_activity' => $this->get_recent_activity(10),
                'trending' => $this->get_trending_articles(5),
                'deadlines' => $this->get_upcoming_deadlines(7),
            ];
        }, self::CACHE_TTL);
    }
    
    /**
     * Statistiche panoramica generale
     *
     * @return array
     */
    public function get_overview_stats() {
        global $wpdb;
        
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $month_ago = date('Y-m-d', strtotime('-30 days'));
        
        // Articoli pubblicati oggi
        $published_today = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND DATE(post_date) = %s
        ", $today));
        
        // Articoli pubblicati questa settimana
        $published_week = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND DATE(post_date) >= %s
        ", $week_ago));
        
        // Articoli pubblicati questo mese
        $published_month = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND DATE(post_date) >= %s
        ", $month_ago));
        
        // Media giornaliera
        $avg_per_day = $published_month / 30;
        
        // Bozze totali
        $drafts = wp_count_posts('post')->draft ?? 0;
        
        return [
            'published_today' => $published_today,
            'published_week' => $published_week,
            'published_month' => $published_month,
            'avg_per_day' => round($avg_per_day, 1),
            'drafts' => $drafts,
        ];
    }
    
    /**
     * Performance del team
     *
     * @return array
     */
    public function get_team_performance() {
        global $wpdb;
        
        $month_ago = date('Y-m-d', strtotime('-30 days'));
        
        // Top autori per numero articoli (ultimi 30 giorni)
        $top_authors = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.post_author,
                u.display_name,
                COUNT(*) as article_count,
                SUM(CASE WHEN p.post_status = 'publish' THEN 1 ELSE 0 END) as published_count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = 'post'
            AND DATE(p.post_date) >= %s
            GROUP BY p.post_author
            ORDER BY published_count DESC
            LIMIT 10
        ", $month_ago));
        
        // Tempo medio da bozza a pubblicazione
        $avg_time_to_publish = $this->calculate_avg_time_to_publish();
        
        // Articoli per stato
        $by_status = wp_count_posts('post');
        
        return [
            'top_authors' => $top_authors,
            'avg_time_to_publish' => $avg_time_to_publish,
            'by_status' => $by_status,
        ];
    }
    
    /**
     * Statistiche pipeline editoriale
     *
     * @return array
     */
    public function get_pipeline_stats() {
        $workflow = new WorkflowManager();
        
        return [
            'in_review' => $this->count_by_status('fp_in_review'),
            'needs_changes' => $this->count_by_status('fp_needs_changes'),
            'approved' => $this->count_by_status('fp_approved'),
            'scheduled' => $this->count_by_status('fp_scheduled') + $this->count_by_status('future'),
            'drafts' => $this->count_by_status('draft'),
        ];
    }
    
    /**
     * Activity feed recente
     *
     * @param int $limit
     * @return array
     */
    public function get_recent_activity($limit = 10) {
        global $wpdb;
        
        // Ultime modifiche articoli
        $recent = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_status,
                p.post_author,
                p.post_modified,
                u.display_name as author_name
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
            WHERE p.post_type = 'post'
            AND p.post_status IN ('draft', 'fp_in_review', 'fp_needs_changes', 'fp_approved', 'publish')
            ORDER BY p.post_modified DESC
            LIMIT %d
        ", $limit));
        
        $activities = [];
        
        foreach ($recent as $post) {
            $time_ago = human_time_diff(strtotime($post->post_modified), current_time('timestamp'));
            
            $activities[] = [
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'author' => $post->author_name,
                'status' => $post->post_status,
                'status_label' => $this->get_status_label($post->post_status),
                'time_ago' => $time_ago,
                'action' => $this->get_activity_action($post->post_status),
                'edit_link' => get_edit_post_link($post->ID, 'raw'),
            ];
        }
        
        return $activities;
    }
    
    /**
     * Articoli trending (crescita veloce views)
     *
     * @param int $limit
     * @return array
     */
    public function get_trending_articles($limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Articoli pubblicati nelle ultime 48h con molte views
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                s.views,
                s.shares,
                (s.views / (TIMESTAMPDIFF(HOUR, p.post_date, NOW()) + 1)) as velocity
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_date > DATE_SUB(NOW(), INTERVAL 48 HOUR)
            AND s.views > 10
            ORDER BY velocity DESC
            LIMIT %d
        ", $limit));
        
        return $results ?: [];
    }
    
    /**
     * Deadline imminenti
     *
     * @param int $days
     * @return array
     */
    public function get_upcoming_deadlines($days = 7) {
        $workflow = new WorkflowManager();
        return $workflow->get_upcoming_deadlines($days);
    }
    
    /**
     * Metriche per grafici
     *
     * @param int $days Giorni da analizzare
     * @return array
     */
    public function get_chart_data($days = 30) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        // Articoli pubblicati per giorno
        $by_day = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(post_date) as date,
                COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND DATE(post_date) >= %s
            GROUP BY DATE(post_date)
            ORDER BY DATE(post_date) ASC
        ", $start_date));
        
        // Converti in formato Chart.js
        $labels = [];
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date_i18n('d/m', strtotime($date));
            
            // Trova count per questa data
            $count = 0;
            foreach ($by_day as $row) {
                if ($row->date === $date) {
                    $count = (int) $row->count;
                    break;
                }
            }
            
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Articoli Pubblicati', 'fp-newspaper'),
                    'data' => $data,
                    'borderColor' => '#2271b1',
                    'backgroundColor' => 'rgba(34, 113, 177, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
            ],
        ];
    }
    
    /**
     * Statistiche autori
     *
     * @param int $limit
     * @return array
     */
    public function get_author_stats($limit = 10) {
        global $wpdb;
        
        $month_ago = date('Y-m-d', strtotime('-30 days'));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                COUNT(p.ID) as total_articles,
                SUM(CASE WHEN p.post_status = 'publish' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN p.post_status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN p.post_status = 'fp_in_review' THEN 1 ELSE 0 END) as in_review
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                AND p.post_type = 'post'
                AND DATE(p.post_date) >= %s
            GROUP BY u.ID
            HAVING total_articles > 0
            ORDER BY published DESC
            LIMIT %d
        ", $month_ago, $limit));
        
        return $results ?: [];
    }
    
    /**
     * Calcola tempo medio da bozza a pubblicazione
     *
     * @return float Ore
     */
    private function calculate_avg_time_to_publish() {
        global $wpdb;
        
        $month_ago = date('Y-m-d', strtotime('-30 days'));
        
        $avg_hours = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, post_date, post_modified))
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND DATE(post_date) >= %s
        ", $month_ago));
        
        return $avg_hours ? round((float) $avg_hours, 1) : 0;
    }
    
    /**
     * Conta articoli per stato
     */
    private function count_by_status($status) {
        $counts = wp_count_posts('post');
        return isset($counts->$status) ? (int) $counts->$status : 0;
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
     * Ottiene azione per activity feed
     */
    private function get_activity_action($status) {
        $actions = [
            'draft' => __('ha creato una bozza', 'fp-newspaper'),
            'fp_in_review' => __('ha inviato in revisione', 'fp-newspaper'),
            'fp_needs_changes' => __('richiede modifiche', 'fp-newspaper'),
            'fp_approved' => __('è stato approvato', 'fp-newspaper'),
            'publish' => __('è stato pubblicato', 'fp-newspaper'),
        ];
        
        return $actions[$status] ?? __('ha modificato', 'fp-newspaper');
    }
    
    /**
     * Assegnazioni per utente
     *
     * @param int $user_id
     * @return array
     */
    public function get_my_assignments($user_id) {
        $workflow = new WorkflowManager();
        return $workflow->get_my_assignments($user_id);
    }
    
    /**
     * Quick stats per widget
     *
     * @return array
     */
    public function get_quick_stats() {
        $pipeline = $this->get_pipeline_stats();
        $overview = $this->get_overview_stats();
        
        return [
            'pipeline_total' => $pipeline['in_review'] + $pipeline['needs_changes'] + $pipeline['approved'],
            'published_today' => $overview['published_today'],
            'published_week' => $overview['published_week'],
            'avg_per_day' => $overview['avg_per_day'],
            'drafts' => $pipeline['drafts'],
        ];
    }
    
    /**
     * Metriche produttività team
     *
     * @param string $period 'week' o 'month'
     * @return array
     */
    public function get_productivity_metrics($period = 'month') {
        $days = $period === 'week' ? 7 : 30;
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        global $wpdb;
        
        $metrics = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_articles,
                SUM(CASE WHEN post_status = 'publish' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN post_status = 'fp_in_review' THEN 1 ELSE 0 END) as in_review,
                SUM(CASE WHEN post_status = 'fp_approved' THEN 1 ELSE 0 END) as approved,
                AVG(TIMESTAMPDIFF(HOUR, post_date, post_modified)) as avg_time_hours
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND DATE(post_date) >= %s
        ", $start_date), ARRAY_A);
        
        return $metrics ?: [
            'total_articles' => 0,
            'published' => 0,
            'in_review' => 0,
            'approved' => 0,
            'avg_time_hours' => 0,
        ];
    }
    
    /**
     * Backlog articoli (pipeline)
     *
     * @return int
     */
    public function get_backlog_count() {
        $counts = wp_count_posts('post');
        
        return ($counts->draft ?? 0) + 
               ($counts->fp_in_review ?? 0) + 
               ($counts->fp_needs_changes ?? 0);
    }
    
    /**
     * Articoli programmati prossimi 7 giorni
     *
     * @return array
     */
    public function get_upcoming_publications() {
        global $wpdb;
        
        $today = date('Y-m-d');
        $week_later = date('Y-m-d', strtotime('+7 days'));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ID,
                post_title,
                post_date,
                post_author
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status IN ('future', 'fp_scheduled')
            AND DATE(post_date) BETWEEN %s AND %s
            ORDER BY post_date ASC
        ", $today, $week_later));
        
        return $results ?: [];
    }
    
    /**
     * Alert e warning per dashboard
     *
     * @return array
     */
    public function get_alerts() {
        $alerts = [];
        
        // Deadline scadute
        $overdue = $this->get_overdue_deadlines();
        if (count($overdue) > 0) {
            $alerts[] = [
                'type' => 'error',
                'icon' => 'warning',
                'message' => sprintf(
                    _n('%d articolo in ritardo!', '%d articoli in ritardo!', count($overdue), 'fp-newspaper'),
                    count($overdue)
                ),
                'action_text' => __('Visualizza', 'fp-newspaper'),
                'action_link' => admin_url('edit.php?page=fp-newspaper-workflow'),
            ];
        }
        
        // Molti articoli in attesa
        $in_review = $this->count_by_status('fp_in_review');
        if ($in_review > 10) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'message' => sprintf(__('%d articoli in attesa di revisione', 'fp-newspaper'), $in_review),
                'action_text' => __('Revisiona', 'fp-newspaper'),
                'action_link' => admin_url('edit.php?page=fp-newspaper-workflow'),
            ];
        }
        
        // Backlog alto
        $backlog = $this->get_backlog_count();
        if ($backlog > 50) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'info',
                'message' => sprintf(__('Backlog alto: %d articoli in lavorazione', 'fp-newspaper'), $backlog),
                'action_text' => __('Gestisci', 'fp-newspaper'),
                'action_link' => admin_url('edit.php'),
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Deadline scadute
     */
    private function get_overdue_deadlines() {
        global $wpdb;
        
        $now = current_time('mysql');
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'post'
            AND p.post_status IN ('fp_in_review', 'fp_approved')
            AND pm.meta_key = '_fp_review_deadline'
            AND pm.meta_value < %s
        ", $now));
        
        return $results ?: [];
    }
}


