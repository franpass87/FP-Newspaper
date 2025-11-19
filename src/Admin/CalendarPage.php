<?php
/**
 * Pagina Admin Calendario Editoriale
 *
 * @package FPNewspaper\Admin
 */

namespace FPNewspaper\Admin;

use FPNewspaper\Editorial\Calendar;

defined('ABSPATH') || exit;

/**
 * Dashboard calendario editoriale con vista mensile/settimanale
 */
class CalendarPage {
    
    /**
     * @var Calendar
     */
    private $calendar;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->calendar = new Calendar();
        
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Aggiunge pagina menu
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php',
            __('Calendario Editoriale', 'fp-newspaper'),
            __('📅 Calendario', 'fp-newspaper'),
            'edit_posts',
            'fp-newspaper-calendar',
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ('posts_page_fp-newspaper-calendar' !== $hook) {
            return;
        }
        
        // FullCalendar CDN
        wp_enqueue_style(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
            [],
            '6.1.10'
        );
        
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            [],
            '6.1.10',
            true
        );
        
        // FullCalendar Locale IT
        wp_enqueue_script(
            'fullcalendar-it',
            'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/it.global.min.js',
            ['fullcalendar'],
            '6.1.10',
            true
        );
        
        wp_localize_script('fullcalendar', 'fpCalendarData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fp_calendar_nonce'),
            'i18n' => [
                'scheduleTitle' => __('Programma Articolo', 'fp-newspaper'),
                'selectDate' => __('Seleziona data:', 'fp-newspaper'),
                'selectSlot' => __('Seleziona fascia oraria:', 'fp-newspaper'),
                'morning' => __('Mattina (08:00)', 'fp-newspaper'),
                'afternoon' => __('Pomeriggio (14:00)', 'fp-newspaper'),
                'evening' => __('Sera (20:00)', 'fp-newspaper'),
                'cancel' => __('Annulla', 'fp-newspaper'),
                'save' => __('Programma', 'fp-newspaper'),
            ],
        ]);
    }
    
    /**
     * Renderizza pagina
     */
    public function render_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $current_month = date('Y-m');
        $month_stats = $this->calendar->get_month_stats($current_month);
        
        ?>
        <div class="wrap fp-calendar-page">
            <h1><?php _e('📅 Calendario Editoriale', 'fp-newspaper'); ?></h1>
            
            <!-- Month Stats -->
            <div class="fp-month-stats">
                <div class="fp-stat-item">
                    <span class="fp-stat-value"><?php echo esc_html($month_stats['total']); ?></span>
                    <span class="fp-stat-label"><?php _e('Articoli Programmati', 'fp-newspaper'); ?></span>
                </div>
                <?php foreach ($month_stats['by_status'] as $status => $count): ?>
                    <div class="fp-stat-item">
                        <span class="fp-stat-value"><?php echo esc_html($count); ?></span>
                        <span class="fp-stat-label"><?php echo esc_html($this->get_status_label($status)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Actions -->
            <div class="fp-calendar-actions">
                <button type="button" class="button" id="fp-export-ical">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Esporta iCal', 'fp-newspaper'); ?>
                </button>
                <button type="button" class="button" id="fp-print-calendar">
                    <span class="dashicons dashicons-printer"></span>
                    <?php _e('Stampa Calendario', 'fp-newspaper'); ?>
                </button>
            </div>
            
            <!-- Calendar -->
            <div id="fp-editorial-calendar" style="margin-top: 20px;"></div>
            
            <!-- Legend -->
            <div class="fp-calendar-legend">
                <h3><?php _e('Legenda:', 'fp-newspaper'); ?></h3>
                <div class="fp-legend-items">
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#95a5a6;"></span> Bozza</span>
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#f39c12;"></span> In Revisione</span>
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#e74c3c;"></span> Richiede Modifiche</span>
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#27ae60;"></span> Approvato</span>
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#3498db;"></span> Programmato</span>
                    <span class="fp-legend-item"><span class="fp-legend-color" style="background:#2ecc71;"></span> Pubblicato</span>
                </div>
            </div>
        </div>
        
        <style>
            .fp-calendar-page {
                max-width: 1400px;
            }
            .fp-month-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
                flex-wrap: wrap;
            }
            .fp-stat-item {
                background: white;
                padding: 15px 25px;
                border-radius: 6px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
            }
            .fp-stat-value {
                display: block;
                font-size: 28px;
                font-weight: bold;
                color: #2271b1;
            }
            .fp-stat-label {
                display: block;
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .fp-calendar-actions {
                margin: 15px 0;
            }
            .fp-calendar-actions .button {
                margin-right: 10px;
            }
            #fp-editorial-calendar {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fp-calendar-legend {
                margin-top: 20px;
                padding: 15px;
                background: white;
                border-radius: 6px;
            }
            .fp-legend-items {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
            }
            .fp-legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
            }
            .fp-legend-color {
                display: inline-block;
                width: 16px;
                height: 16px;
                border-radius: 3px;
            }
            .fp-row-overdue {
                background: #fff5f5 !important;
            }
            .fp-overdue-text {
                color: #d63638;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Inizializza FullCalendar
            var calendarEl = document.getElementById('fp-editorial-calendar');
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'it',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                buttonText: {
                    today: 'Oggi',
                    month: 'Mese',
                    week: 'Settimana',
                    list: 'Lista'
                },
                height: 'auto',
                editable: true,
                droppable: false,
                eventDurationEditable: false,
                events: function(info, successCallback, failureCallback) {
                    $.ajax({
                        url: fpCalendarData.ajaxurl,
                        type: 'GET',
                        data: {
                            action: 'fp_get_calendar_events',
                            start: info.startStr.split('T')[0],
                            end: info.endStr.split('T')[0],
                            nonce: fpCalendarData.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                successCallback(response.data);
                            } else {
                                failureCallback();
                            }
                        },
                        error: failureCallback
                    });
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                },
                eventDrop: function(info) {
                    // Handle drag & drop
                    $.post(fpCalendarData.ajaxurl, {
                        action: 'fp_update_schedule',
                        post_id: info.event.id,
                        new_date: info.event.startStr,
                        nonce: fpCalendarData.nonce
                    }, function(response) {
                        if (!response.success) {
                            alert(response.data || 'Errore aggiornamento');
                            info.revert();
                        }
                    });
                },
                eventContent: function(arg) {
                    var status = arg.event.extendedProps.status_label;
                    var author = arg.event.extendedProps.author;
                    
                    return {
                        html: '<div class="fc-event-main-frame">' +
                              '<div class="fc-event-time">' + arg.timeText + '</div>' +
                              '<div class="fc-event-title-container">' +
                              '<div class="fc-event-title">' + arg.event.title + '</div>' +
                              '<div class="fc-event-meta">' + status + ' • ' + author + '</div>' +
                              '</div>' +
                              '</div>'
                    };
                }
            });
            
            calendar.render();
            
            // Export iCal
            $('#fp-export-ical').on('click', function() {
                var view = calendar.view;
                window.location = fpCalendarData.ajaxurl + 
                    '?action=fp_export_calendar' +
                    '&start=' + view.activeStart.toISOString().split('T')[0] +
                    '&end=' + view.activeEnd.toISOString().split('T')[0] +
                    '&nonce=' + fpCalendarData.nonce;
            });
            
            // Print calendar
            $('#fp-print-calendar').on('click', function() {
                window.print();
            });
        });
        </script>
        
        <style>
            .fc-event-meta {
                font-size: 11px;
                opacity: 0.9;
                margin-top: 2px;
            }
            .fc-event-title {
                font-weight: 600;
            }
            
            @media print {
                .fp-calendar-actions,
                .fp-month-stats,
                #wpadminbar,
                #adminmenumain,
                .wrap > h1,
                .notice {
                    display: none !important;
                }
                #fp-editorial-calendar {
                    box-shadow: none;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Ottiene label per stato
     */
    private function get_status_label($status) {
        $labels = [
            'draft' => __('Bozza', 'fp-newspaper'),
            'fp_in_review' => __('In Revisione', 'fp-newspaper'),
            'fp_needs_changes' => __('Modifiche', 'fp-newspaper'),
            'fp_approved' => __('Approvato', 'fp-newspaper'),
            'fp_scheduled' => __('Programmato', 'fp-newspaper'),
            'publish' => __('Pubblicato', 'fp-newspaper'),
        ];
        
        return $labels[$status] ?? $status;
    }
}


