<?php
/**
 * Rate Limiter con protezione DDoS
 *
 * @package FPNewspaper\Security
 */

namespace FPNewspaper\Security;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Rate limiting intelligente con protezione DDoS
 */
class RateLimiter {
    
    /**
     * Time windows (secondi)
     */
    const WINDOW_NORMAL = 30;       // 30 secondi per utenti normali
    const WINDOW_SUSPICIOUS = 300;  // 5 minuti se attività sospetta
    const WINDOW_BANNED = 3600;     // 1 ora per IP bannati
    
    /**
     * Soglie
     */
    const MAX_ATTEMPTS_PER_MINUTE = 10;  // 10 richieste/minuto = sospetto
    const MAX_ATTEMPTS_PER_HOUR = 100;   // 100 richieste/ora = ban
    const BAN_THRESHOLD = 5;             // 5 violazioni = ban automatico
    
    /**
     * Verifica se l'IP può eseguire l'azione
     *
     * @param string $action Tipo azione (es: 'view', 'api_call')
     * @param int    $post_id Opzionale: ID risorsa specifica
     * @param string $ip IP address (auto-detect se null)
     * @return bool
     */
    public static function is_allowed($action, $post_id = null, $ip = null) {
        $ip = $ip ?? self::get_client_ip();
        
        // Whitelisted IP bypass tutto
        if (self::is_whitelisted($ip)) {
            Logger::debug("Rate limit bypassed (whitelisted)", ['ip' => $ip, 'action' => $action]);
            return true;
        }
        
        // Check se IP è bannato
        if (self::is_banned($ip)) {
            Logger::warning("Request blocked: IP is banned", ['ip' => $ip, 'action' => $action]);
            do_action('fp_newspaper_ip_banned_attempt', $ip, $action);
            return false;
        }
        
        // Genera cache key
        $key = self::get_rate_key($action, $post_id, $ip);
        
        // Check se già bloccato
        if (get_transient($key)) {
            // Incrementa contatore violazioni
            self::increment_violations($ip);
            
            Logger::debug("Rate limit exceeded", [
                'ip' => $ip,
                'action' => $action,
                'post_id' => $post_id,
            ]);
            
            do_action('fp_newspaper_rate_limit_exceeded', $ip, $action, $post_id);
            return false;
        }
        
        // Check attività sospetta
        if (self::is_suspicious($ip, $action)) {
            Logger::warning("Suspicious activity detected", [
                'ip' => $ip,
                'action' => $action,
                'attempts_minute' => self::get_attempts_count($ip, 60),
                'attempts_hour' => self::get_attempts_count($ip, 3600),
            ]);
            
            do_action('fp_newspaper_suspicious_activity', $ip, $action);
            
            // Ban automatico se troppi tentativi
            if (self::should_auto_ban($ip)) {
                self::ban_ip($ip, 'Automatic ban: too many suspicious activities');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Marca l'azione come usata
     *
     * @param string $action
     * @param int    $post_id
     * @param string $ip
     */
    public static function mark_used($action, $post_id = null, $ip = null) {
        $ip = $ip ?? self::get_client_ip();
        
        // Skip per whitelisted
        if (self::is_whitelisted($ip)) {
            return;
        }
        
        $key = self::get_rate_key($action, $post_id, $ip);
        $window = self::get_window_duration($ip);
        
        set_transient($key, true, $window);
        
        // Track tentativo
        self::track_attempt($ip, $action);
        
        Logger::debug("Rate limit marked", [
            'ip' => $ip,
            'action' => $action,
            'window' => $window,
        ]);
    }
    
    /**
     * Verifica se IP è in whitelist
     *
     * @param string $ip
     * @return bool
     */
    public static function is_whitelisted($ip) {
        // Localhost sempre whitelisted
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }
        
        $whitelist = get_option('fp_newspaper_ip_whitelist', []);
        
        // Filtrabile
        $whitelist = apply_filters('fp_newspaper_ip_whitelist', $whitelist);
        
        return in_array($ip, $whitelist, true);
    }
    
    /**
     * Verifica se IP è bannato
     *
     * @param string $ip
     * @return bool
     */
    public static function is_banned($ip) {
        $ban_key = "fp_rate_banned_{$ip}";
        return (bool) get_transient($ban_key);
    }
    
    /**
     * Banna un IP
     *
     * @param string $ip
     * @param string $reason
     * @param int    $duration Durata in secondi (default 1 ora)
     */
    public static function ban_ip($ip, $reason = 'Manual ban', $duration = self::WINDOW_BANNED) {
        $ban_key = "fp_rate_banned_{$ip}";
        set_transient($ban_key, [
            'reason' => $reason,
            'banned_at' => current_time('timestamp'),
        ], $duration);
        
        Logger::error("IP BANNED: {$reason}", [
            'ip' => $ip,
            'duration' => $duration,
        ]);
        
        do_action('fp_newspaper_ip_banned', $ip, $reason, $duration);
    }
    
    /**
     * Rimuove ban da IP
     *
     * @param string $ip
     */
    public static function unban_ip($ip) {
        $ban_key = "fp_rate_banned_{$ip}";
        delete_transient($ban_key);
        
        Logger::info("IP unbanned", ['ip' => $ip]);
        do_action('fp_newspaper_ip_unbanned', $ip);
    }
    
    /**
     * Verifica se attività è sospetta
     *
     * @param string $ip
     * @param string $action
     * @return bool
     */
    private static function is_suspicious($ip, $action) {
        // Tentativi nell'ultimo minuto
        $attempts_minute = self::get_attempts_count($ip, 60);
        if ($attempts_minute > self::MAX_ATTEMPTS_PER_MINUTE) {
            return true;
        }
        
        // Tentativi nell'ultima ora
        $attempts_hour = self::get_attempts_count($ip, 3600);
        if ($attempts_hour > self::MAX_ATTEMPTS_PER_HOUR) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se IP dovrebbe essere auto-bannato
     *
     * @param string $ip
     * @return bool
     */
    private static function should_auto_ban($ip) {
        $violations_key = "fp_rate_violations_{$ip}";
        $violations = (int) get_transient($violations_key);
        
        return $violations >= self::BAN_THRESHOLD;
    }
    
    /**
     * Incrementa contatore violazioni
     *
     * @param string $ip
     */
    private static function increment_violations($ip) {
        $violations_key = "fp_rate_violations_{$ip}";
        $violations = (int) get_transient($violations_key) + 1;
        
        set_transient($violations_key, $violations, HOUR_IN_SECONDS);
    }
    
    /**
     * Track tentativo
     *
     * @param string $ip
     * @param string $action
     */
    private static function track_attempt($ip, $action) {
        $attempts_key = "fp_rate_attempts_{$ip}";
        $attempts = get_transient($attempts_key) ?: [];
        
        $attempts[] = [
            'timestamp' => current_time('timestamp'),
            'action' => $action,
        ];
        
        // Limita a 200 tentativi tracciati
        if (count($attempts) > 200) {
            array_shift($attempts);
        }
        
        set_transient($attempts_key, $attempts, HOUR_IN_SECONDS);
    }
    
    /**
     * Conta tentativi in una finestra temporale
     *
     * @param string $ip
     * @param int    $window Finestra in secondi
     * @return int
     */
    private static function get_attempts_count($ip, $window) {
        $attempts_key = "fp_rate_attempts_{$ip}";
        $attempts = get_transient($attempts_key) ?: [];
        
        $cutoff = current_time('timestamp') - $window;
        
        $recent_attempts = array_filter($attempts, function($attempt) use ($cutoff) {
            return $attempt['timestamp'] > $cutoff;
        });
        
        return count($recent_attempts);
    }
    
    /**
     * Ottiene durata finestra in base allo stato IP
     *
     * @param string $ip
     * @return int
     */
    private static function get_window_duration($ip) {
        if (self::is_suspicious($ip, '')) {
            return self::WINDOW_SUSPICIOUS;
        }
        
        return self::WINDOW_NORMAL;
    }
    
    /**
     * Genera cache key per rate limiting
     *
     * @param string $action
     * @param int    $post_id
     * @param string $ip
     * @return string
     */
    private static function get_rate_key($action, $post_id, $ip) {
        $parts = ['fp_rate', $action, $ip];
        
        if ($post_id) {
            $parts[] = $post_id;
        }
        
        return md5(implode('_', $parts));
    }
    
    /**
     * Ottiene IP del client
     *
     * @return string
     */
    private static function get_client_ip() {
        // Check vari header per proxy/load balancer
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se X-Forwarded-For contiene multipli IP, prendi il primo
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Valida IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Ottiene statistiche rate limiting
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        
        // Conta IP bannati
        $banned_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_rate_banned_%'"
        );
        
        // Conta IP con violazioni
        $violations_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_rate_violations_%'"
        );
        
        return [
            'banned_ips' => $banned_count,
            'ips_with_violations' => $violations_count,
            'window_normal' => self::WINDOW_NORMAL,
            'window_suspicious' => self::WINDOW_SUSPICIOUS,
        ];
    }
    
    /**
     * Pulisce dati rate limiting vecchi
     */
    public static function cleanup() {
        global $wpdb;
        
        // Rimuovi transient scaduti
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_fp_rate_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        Logger::info('Rate limiter cleanup completed');
    }
}


