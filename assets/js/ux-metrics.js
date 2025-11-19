/**
 * FP Newspaper - UX Metrics Tracking (Web Vitals)
 * Tracks Core Web Vitals and sends to Google Analytics / WordPress
 * 
 * @package FPNewspaper
 * @version 1.7.0
 */

(function() {
    'use strict';
    
    /**
     * UX Metrics Tracker
     */
    const UXMetrics = {
        
        /**
         * Metrics storage
         */
        metrics: {},
        
        /**
         * Initialize tracking
         */
        init() {
            if (typeof performance === 'undefined') {
                console.warn('Performance API not supported');
                return;
            }
            
            this.trackCoreWebVitals();
            this.trackCustomMetrics();
            this.setupBeaconOnUnload();
        },
        
        /**
         * Track Core Web Vitals (LCP, FID, CLS)
         */
        trackCoreWebVitals() {
            // Largest Contentful Paint (LCP)
            this.trackLCP();
            
            // First Input Delay (FID)
            this.trackFID();
            
            // Cumulative Layout Shift (CLS)
            this.trackCLS();
            
            // First Contentful Paint (FCP)
            this.trackFCP();
            
            // Time to First Byte (TTFB)
            this.trackTTFB();
        },
        
        /**
         * Track Largest Contentful Paint
         */
        trackLCP() {
            if (!('PerformanceObserver' in window)) return;
            
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    
                    const lcp = lastEntry.renderTime || lastEntry.loadTime;
                    
                    this.metrics.lcp = Math.round(lcp);
                    this.sendMetric('LCP', this.metrics.lcp, this.getRating(this.metrics.lcp, [2500, 4000]));
                    
                    observer.disconnect();
                });
                
                observer.observe({ 
                    type: 'largest-contentful-paint', 
                    buffered: true 
                });
            } catch (e) {
                console.warn('LCP tracking failed:', e);
            }
        },
        
        /**
         * Track First Input Delay
         */
        trackFID() {
            if (!('PerformanceObserver' in window)) return;
            
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    
                    entries.forEach((entry) => {
                        const fid = entry.processingStart - entry.startTime;
                        
                        this.metrics.fid = Math.round(fid);
                        this.sendMetric('FID', this.metrics.fid, this.getRating(this.metrics.fid, [100, 300]));
                    });
                    
                    observer.disconnect();
                });
                
                observer.observe({ 
                    type: 'first-input', 
                    buffered: true 
                });
            } catch (e) {
                console.warn('FID tracking failed:', e);
            }
        },
        
        /**
         * Track Cumulative Layout Shift
         */
        trackCLS() {
            if (!('PerformanceObserver' in window)) return;
            
            try {
                let clsValue = 0;
                
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        // Only count layout shifts without recent user input
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    }
                    
                    this.metrics.cls = Math.round(clsValue * 1000) / 1000;
                });
                
                observer.observe({ 
                    type: 'layout-shift', 
                    buffered: true 
                });
                
                // Send CLS on page hide
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'hidden') {
                        this.sendMetric('CLS', this.metrics.cls, this.getRating(this.metrics.cls, [0.1, 0.25]));
                        observer.disconnect();
                    }
                });
            } catch (e) {
                console.warn('CLS tracking failed:', e);
            }
        },
        
        /**
         * Track First Contentful Paint
         */
        trackFCP() {
            if (!('PerformanceObserver' in window)) return;
            
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    
                    entries.forEach((entry) => {
                        if (entry.name === 'first-contentful-paint') {
                            this.metrics.fcp = Math.round(entry.startTime);
                            this.sendMetric('FCP', this.metrics.fcp, this.getRating(this.metrics.fcp, [1800, 3000]));
                        }
                    });
                    
                    observer.disconnect();
                });
                
                observer.observe({ 
                    type: 'paint', 
                    buffered: true 
                });
            } catch (e) {
                console.warn('FCP tracking failed:', e);
            }
        },
        
        /**
         * Track Time to First Byte
         */
        trackTTFB() {
            try {
                const navTiming = performance.getEntriesByType('navigation')[0];
                
                if (navTiming) {
                    const ttfb = Math.round(navTiming.responseStart - navTiming.requestStart);
                    
                    this.metrics.ttfb = ttfb;
                    this.sendMetric('TTFB', ttfb, this.getRating(ttfb, [800, 1800]));
                }
            } catch (e) {
                console.warn('TTFB tracking failed:', e);
            }
        },
        
        /**
         * Track custom metrics
         */
        trackCustomMetrics() {
            // DOM Content Loaded
            window.addEventListener('DOMContentLoaded', () => {
                const dcl = Math.round(performance.now());
                this.metrics.dcl = dcl;
                this.sendMetric('DCL', dcl);
            });
            
            // Window Load
            window.addEventListener('load', () => {
                const load = Math.round(performance.now());
                this.metrics.load = load;
                this.sendMetric('WindowLoad', load);
                
                // Resource timing
                this.trackResourceTiming();
            });
            
            // JavaScript errors
            window.addEventListener('error', (e) => {
                this.sendMetric('JSError', 1, 'error', {
                    message: e.message,
                    filename: e.filename,
                    lineno: e.lineno
                });
            });
            
            // User interactions
            this.trackUserInteractions();
        },
        
        /**
         * Track resource loading timing
         */
        trackResourceTiming() {
            try {
                const resources = performance.getEntriesByType('resource');
                
                let cssTotal = 0, jsTotal = 0, imgTotal = 0;
                let cssCount = 0, jsCount = 0, imgCount = 0;
                
                resources.forEach((resource) => {
                    const duration = resource.duration;
                    
                    if (resource.name.endsWith('.css')) {
                        cssTotal += duration;
                        cssCount++;
                    } else if (resource.name.endsWith('.js')) {
                        jsTotal += duration;
                        jsCount++;
                    } else if (resource.name.match(/\.(jpg|jpeg|png|gif|webp|svg)/i)) {
                        imgTotal += duration;
                        imgCount++;
                    }
                });
                
                if (cssCount > 0) {
                    this.sendMetric('CSS_Load_Avg', Math.round(cssTotal / cssCount));
                }
                if (jsCount > 0) {
                    this.sendMetric('JS_Load_Avg', Math.round(jsTotal / jsCount));
                }
                if (imgCount > 0) {
                    this.sendMetric('IMG_Load_Avg', Math.round(imgTotal / imgCount));
                }
            } catch (e) {
                console.warn('Resource timing failed:', e);
            }
        },
        
        /**
         * Track user interactions
         */
        trackUserInteractions() {
            // Time to first interaction
            let firstInteraction = null;
            
            const events = ['click', 'keydown', 'scroll', 'touchstart'];
            
            const handler = () => {
                if (!firstInteraction) {
                    firstInteraction = Math.round(performance.now());
                    this.sendMetric('TimeToInteraction', firstInteraction);
                    
                    events.forEach(event => {
                        document.removeEventListener(event, handler);
                    });
                }
            };
            
            events.forEach(event => {
                document.addEventListener(event, handler, { once: true, passive: true });
            });
        },
        
        /**
         * Get rating (good/needs-improvement/poor)
         */
        getRating(value, thresholds) {
            if (value <= thresholds[0]) {
                return 'good';
            } else if (value <= thresholds[1]) {
                return 'needs-improvement';
            } else {
                return 'poor';
            }
        },
        
        /**
         * Send metric to analytics
         */
        sendMetric(name, value, rating, extra) {
            rating = rating || 'neutral';
            
            console.log(`[UX Metric] ${name}: ${value}ms (${rating})`);
            
            // Google Analytics 4
            if (typeof gtag !== 'undefined') {
                gtag('event', name, {
                    event_category: 'Web Vitals',
                    value: Math.round(value),
                    metric_rating: rating,
                    ...extra
                });
            }
            
            // Google Analytics Universal
            if (typeof ga !== 'undefined') {
                ga('send', 'event', {
                    eventCategory: 'Web Vitals',
                    eventAction: name,
                    eventValue: Math.round(value),
                    eventLabel: rating,
                    nonInteraction: true
                });
            }
            
            // WordPress AJAX (optional - store in DB)
            this.sendToWordPress(name, value, rating, extra);
        },
        
        /**
         * Send to WordPress via AJAX
         */
        sendToWordPress(name, value, rating, extra) {
            // Only send if fpNewsConfig exists
            if (typeof fpNewsConfig === 'undefined' || !fpNewsConfig.trackMetrics) {
                return;
            }
            
            // Beacon API for reliability (doesn't block page unload)
            const data = new FormData();
            data.append('action', 'fp_track_ux_metric');
            data.append('nonce', fpNewsConfig.nonce || '');
            data.append('metric_name', name);
            data.append('metric_value', value);
            data.append('metric_rating', rating);
            data.append('page_url', window.location.href);
            data.append('user_agent', navigator.userAgent);
            
            if (extra) {
                data.append('extra_data', JSON.stringify(extra));
            }
            
            if (navigator.sendBeacon) {
                navigator.sendBeacon(fpNewsConfig.ajaxUrl || '/wp-admin/admin-ajax.php', data);
            } else {
                // Fallback to fetch (not guaranteed on unload)
                fetch(fpNewsConfig.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: data,
                    keepalive: true
                }).catch(() => {
                    // Silent fail
                });
            }
        },
        
        /**
         * Setup beacon on page unload
         */
        setupBeaconOnUnload() {
            window.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.sendSessionSummary();
                }
            });
            
            // Fallback for older browsers
            window.addEventListener('pagehide', () => {
                this.sendSessionSummary();
            });
        },
        
        /**
         * Send session summary
         */
        sendSessionSummary() {
            const summary = {
                lcp: this.metrics.lcp,
                fid: this.metrics.fid,
                cls: this.metrics.cls,
                fcp: this.metrics.fcp,
                ttfb: this.metrics.ttfb,
                dcl: this.metrics.dcl,
                load: this.metrics.load,
                timestamp: Date.now(),
                url: window.location.href
            };
            
            console.log('[UX Metrics] Session Summary:', summary);
            
            // Send to Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'session_metrics', {
                    event_category: 'UX Metrics',
                    ...summary
                });
            }
        },
        
        /**
         * Get metrics summary
         */
        getSummary() {
            return {
                ...this.metrics,
                timestamp: Date.now(),
                url: window.location.href,
                userAgent: navigator.userAgent,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                connection: this.getConnectionInfo()
            };
        },
        
        /**
         * Get connection info
         */
        getConnectionInfo() {
            if ('connection' in navigator || 'mozConnection' in navigator || 'webkitConnection' in navigator) {
                const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                return {
                    effectiveType: conn.effectiveType,
                    downlink: conn.downlink,
                    rtt: conn.rtt,
                    saveData: conn.saveData
                };
            }
            return null;
        }
    };
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            UXMetrics.init();
        });
    } else {
        UXMetrics.init();
    }
    
    // Expose to global scope
    window.FPUXMetrics = UXMetrics;
    
})();

