/**
 * FP Newspaper - Admin Dashboard JavaScript
 * Dashboard interattivo con charts, filtri e AJAX refresh
 * 
 * @package FPNewspaper
 * @version 1.7.0
 */

(function($) {
    'use strict';
    
    /**
     * Dashboard Manager
     */
    const FPDashboard = {
        
        /**
         * Chart instances
         */
        charts: {},
        
        /**
         * Refresh interval ID
         */
        refreshInterval: null,
        
        /**
         * Initialize
         */
        init() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }
            
            if (typeof fpDashboardData === 'undefined') {
                console.warn('Dashboard data not available');
                return;
            }
            
            console.log('FP Newspaper Dashboard JS initialized');
            
            this.initCharts();
            this.initFilters();
            this.initRefresh();
            this.initCollapsibles();
            this.initTooltips();
            this.initSortableTables();
        },
        
        /**
         * Initialize Charts
         */
        initCharts() {
            this.initPublicationsChart();
            this.initProductivityChart();
            this.initAuthorsChart();
            this.initViewsChart();
        },
        
        /**
         * Publications Trend Chart (Line)
         */
        initPublicationsChart() {
            const canvas = document.getElementById('fp-publications-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const data = fpDashboardData.chartData || {};
            
            if (!data.labels || !data.published) {
                console.warn('Chart data incomplete');
                return;
            }
            
            this.charts.publications = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Pubblicati',
                        data: data.published,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }, {
                        label: 'Bozze',
                        data: data.drafts || [],
                        borderColor: '#f0b849',
                        backgroundColor: 'rgba(240, 184, 73, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            displayColors: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Productivity Donut Chart
         */
        initProductivityChart() {
            const canvas = document.getElementById('fp-productivity-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const data = fpDashboardData.productivity || {
                published: 0,
                review: 0,
                drafts: 0
            };
            
            this.charts.productivity = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pubblicati', 'In Revisione', 'Bozze'],
                    datasets: [{
                        data: [data.published, data.review, data.drafts],
                        backgroundColor: [
                            '#10b981', // green
                            '#f59e0b', // orange
                            '#6b7280'  // gray
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return {
                                                text: `${label}: ${value} (${percentage}%)`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Top Authors Bar Chart
         */
        initAuthorsChart() {
            const canvas = document.getElementById('fp-authors-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const data = fpDashboardData.authors || {
                labels: [],
                counts: []
            };
            
            this.charts.authors = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Articoli',
                        data: data.counts,
                        backgroundColor: '#2271b1',
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y', // horizontal bars
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.x} articoli`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Views Trend Chart
         */
        initViewsChart() {
            const canvas = document.getElementById('fp-views-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const data = fpDashboardData.views || {
                labels: [],
                views: []
            };
            
            this.charts.views = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Visualizzazioni',
                        data: data.views,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },
        
        /**
         * Initialize Filters
         */
        initFilters() {
            const $dateFilter = $('#fp-date-range');
            const $authorFilter = $('#fp-author-filter');
            const $applyFilters = $('#fp-apply-filters');
            
            if ($dateFilter.length) {
                $dateFilter.on('change', () => {
                    if (!$applyFilters.length) {
                        this.refreshDashboard();
                    }
                });
            }
            
            if ($authorFilter.length) {
                $authorFilter.on('change', () => {
                    if (!$applyFilters.length) {
                        this.refreshDashboard();
                    }
                });
            }
            
            if ($applyFilters.length) {
                $applyFilters.on('click', (e) => {
                    e.preventDefault();
                    this.refreshDashboard();
                });
            }
        },
        
        /**
         * Initialize Auto-refresh
         */
        initRefresh() {
            const $refreshBtn = $('#fp-refresh-dashboard');
            
            if ($refreshBtn.length) {
                $refreshBtn.on('click', (e) => {
                    e.preventDefault();
                    this.refreshDashboard();
                });
            }
            
            // Auto-refresh ogni 5 minuti (opzionale)
            const autoRefresh = fpDashboardData.autoRefresh || false;
            if (autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    this.refreshDashboard(true); // silent refresh
                }, 5 * 60 * 1000);
            }
        },
        
        /**
         * Refresh Dashboard Data via AJAX
         */
        refreshDashboard(silent = false) {
            if (!silent) {
                this.showLoading();
            }
            
            const data = {
                action: 'fp_refresh_dashboard',
                nonce: fpDashboardData.nonce,
                date_range: $('#fp-date-range').val() || '30',
                author: $('#fp-author-filter').val() || 'all'
            };
            
            $.post(fpDashboardData.ajaxurl, data)
                .done((response) => {
                    if (response.success && response.data) {
                        this.updateDashboard(response.data);
                        if (!silent) {
                            this.showNotice('Dashboard aggiornato con successo', 'success');
                        }
                    } else {
                        this.showNotice(response.data?.message || 'Errore aggiornamento', 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Dashboard refresh failed:', error);
                    this.showNotice('Errore di rete. Riprova.', 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        },
        
        /**
         * Update Dashboard with new data
         */
        updateDashboard(data) {
            // Update stats cards
            if (data.stats) {
                Object.keys(data.stats).forEach(key => {
                    $(`.fp-stat-${key} .fp-stat-number`).text(data.stats[key]);
                });
            }
            
            // Update charts
            if (data.chartData) {
                if (this.charts.publications && data.chartData.labels && data.chartData.published) {
                    this.charts.publications.data.labels = data.chartData.labels;
                    this.charts.publications.data.datasets[0].data = data.chartData.published;
                    if (data.chartData.drafts) {
                        this.charts.publications.data.datasets[1].data = data.chartData.drafts;
                    }
                    this.charts.publications.update('active');
                }
            }
            
            if (data.productivity && this.charts.productivity) {
                this.charts.productivity.data.datasets[0].data = [
                    data.productivity.published,
                    data.productivity.review,
                    data.productivity.drafts
                ];
                this.charts.productivity.update('active');
            }
            
            if (data.authors && this.charts.authors) {
                this.charts.authors.data.labels = data.authors.labels;
                this.charts.authors.data.datasets[0].data = data.authors.counts;
                this.charts.authors.update('active');
            }
            
            // Update activity feed
            if (data.activity && data.activity.length > 0) {
                this.updateActivityFeed(data.activity);
            }
        },
        
        /**
         * Update Activity Feed
         */
        updateActivityFeed(activities) {
            const $feed = $('.fp-activity-feed');
            if (!$feed.length) return;
            
            $feed.empty();
            
            activities.forEach(activity => {
                const $item = $(`
                    <li class="fp-activity-item">
                        <div class="fp-activity-icon">
                            <span class="dashicons ${activity.icon || 'dashicons-admin-post'}"></span>
                        </div>
                        <div class="fp-activity-content">
                            <div class="fp-activity-title">${activity.title}</div>
                            <div class="fp-activity-meta">${activity.time}</div>
                        </div>
                    </li>
                `);
                $feed.append($item);
            });
        },
        
        /**
         * Collapsible Widgets
         */
        initCollapsibles() {
            $('.fp-card-toggle').on('click', function() {
                const $card = $(this).closest('.fp-card');
                const $body = $card.find('.fp-card-body');
                
                $body.slideToggle(300, function() {
                    $card.toggleClass('fp-card-collapsed');
                });
                
                $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
            });
        },
        
        /**
         * Tooltips (using native WordPress tooltips)
         */
        initTooltips() {
            $('[data-tooltip]').each(function() {
                const $el = $(this);
                const text = $el.data('tooltip');
                $el.attr('title', text);
            });
        },
        
        /**
         * Sortable Tables
         */
        initSortableTables() {
            $('.fp-sortable-table th[data-sort]').on('click', function() {
                const $th = $(this);
                const $table = $th.closest('table');
                const column = $th.data('sort');
                const order = $th.hasClass('asc') ? 'desc' : 'asc';
                
                // Remove sort classes from all headers
                $table.find('th').removeClass('asc desc');
                
                // Add sort class to clicked header
                $th.addClass(order);
                
                // Sort rows
                const $tbody = $table.find('tbody');
                const $rows = $tbody.find('tr').toArray();
                
                $rows.sort((a, b) => {
                    const aVal = $(a).find(`td[data-column="${column}"]`).text();
                    const bVal = $(b).find(`td[data-column="${column}"]`).text();
                    
                    // Try numeric comparison first
                    const aNum = parseFloat(aVal);
                    const bNum = parseFloat(bVal);
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return order === 'asc' ? aNum - bNum : bNum - aNum;
                    }
                    
                    // String comparison
                    return order === 'asc' 
                        ? aVal.localeCompare(bVal) 
                        : bVal.localeCompare(aVal);
                });
                
                $tbody.empty().append($rows);
            });
        },
        
        /**
         * Show Loading Overlay
         */
        showLoading() {
            let $loading = $('#fp-dashboard-loading');
            if (!$loading.length) {
                $loading = $(`
                    <div id="fp-dashboard-loading" class="fp-dashboard-loading">
                        <span class="fp-loading-spinner"></span>
                        <span>Aggiornamento...</span>
                    </div>
                `);
                $('body').append($loading);
            }
            $loading.addClass('visible').fadeIn(200);
        },
        
        /**
         * Hide Loading Overlay
         */
        hideLoading() {
            $('#fp-dashboard-loading').removeClass('visible').fadeOut(200);
        },
        
        /**
         * Show Notice
         */
        showNotice(message, type = 'success') {
            const types = {
                success: 'notice-success',
                error: 'notice-error',
                warning: 'notice-warning',
                info: 'notice-info'
            };
            
            const $notice = $(`
                <div class="notice ${types[type] || types.success} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss</span>
                    </button>
                </div>
            `);
            
            $('.fp-editorial-dashboard > h1').after($notice);
            
            // Dismiss functionality
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-dismiss dopo 5 secondi
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Destroy (cleanup)
         */
        destroy() {
            // Destroy charts
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            
            // Clear refresh interval
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Remove event listeners
            $(document).off('.fpdashboard');
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        FPDashboard.init();
    });
    
    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        FPDashboard.destroy();
    });
    
    // Expose to global scope (for debugging/extensions)
    window.FPDashboard = FPDashboard;
    
})(jQuery);


