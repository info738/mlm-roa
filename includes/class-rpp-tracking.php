<?php
/**
 * Tracking and analytics class
 */
class RPP_Tracking {
    
    private $table_name;
    private $cookie_name = 'rpp_partner_ref';
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rpp_tracking';
    }
    
    /**
     * Track partner referral
     */
    public function track_referral($partner_code) {
        error_log('RPP Tracking: Attempting to track referral for code: ' . $partner_code);
        
        // Verify partner exists and is active
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_code($partner_code);
        
        if (!$partner || $partner->status !== 'approved') {
            error_log('RPP Tracking: Partner not found or not approved: ' . $partner_code);
            return false;
        }
        
        error_log('RPP Tracking: Partner found: ' . $partner->display_name . ' (ID: ' . $partner->id . ')');
        
        // Set tracking cookie
        $cookie_duration = get_option('rpp_cookie_duration', 30) * DAY_IN_SECONDS;
        $cookie_domain = COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
        $cookie_path = COOKIEPATH ? COOKIEPATH : '/';
        $cookie_set = setcookie($this->cookie_name, $partner_code, time() + $cookie_duration, $cookie_path, $cookie_domain, is_ssl(), false);
        
        error_log('RPP Tracking: Cookie set result: ' . ($cookie_set ? 'SUCCESS' : 'FAILED'));
        error_log('RPP Tracking: Cookie name: ' . $this->cookie_name . ', Value: ' . $partner_code . ', Duration: ' . $cookie_duration . ' seconds');
        error_log('RPP Tracking: Cookie path: ' . $cookie_path . ', Domain: ' . $cookie_domain . ', SSL: ' . (is_ssl() ? 'YES' : 'NO'));
        
        // Record click tracking
        $tracking_id = $this->record_tracking($partner->id, 'click');
        error_log('RPP Tracking: Recorded tracking ID: ' . $tracking_id);
        
        // Also set a backup session variable
        if (!session_id()) {
            session_start();
        }
        $_SESSION['rpp_partner_code'] = $partner_code;
        $_SESSION['rpp_partner_id'] = $partner->id;
        error_log('RPP Tracking: Session backup set - Code: ' . $partner_code . ', ID: ' . $partner->id);
        
        return true;
    }
    
    /**
     * Get current partner from tracking
     */
    public function get_current_partner() {
        error_log('RPP Tracking: Checking for current partner...');
        error_log('RPP Tracking: Available cookies: ' . print_r($_COOKIE, true));
        
        $partner = null;
        $partner_code = null;
        
        // Try cookie first
        if (isset($_COOKIE[$this->cookie_name])) {
            $partner_code = sanitize_text_field($_COOKIE[$this->cookie_name]);
            error_log('RPP Tracking: Found partner cookie: ' . $partner_code);
        }
        
        // Try session as backup
        if (!$partner_code) {
            if (!session_id()) {
                session_start();
            }
            if (isset($_SESSION['rpp_partner_code'])) {
                $partner_code = sanitize_text_field($_SESSION['rpp_partner_code']);
                error_log('RPP Tracking: Found partner in session: ' . $partner_code);
            }
        }
        
        if ($partner_code) {
            
            $partner_class = new RPP_Partner();
            $partner = $partner_class->get_partner_by_code($partner_code);
            
            if ($partner) {
                error_log('RPP Tracking: Current partner: ' . $partner->partner_code . ' (ID: ' . $partner->id . ')');
            } else {
                error_log('RPP Tracking: Partner not found for code: ' . $partner_code);
            }
            
            return $partner;
        }
        
        error_log('RPP Tracking: No partner cookie found');
        return null;
    }
    
    /**
     * Record tracking data
     */
    public function record_tracking($partner_id, $conversion_type = 'click') {
        global $wpdb;
        
        error_log('RPP Tracking: Recording tracking - Partner ID: ' . $partner_id . ', Type: ' . $conversion_type);
        
        $data = array(
            'partner_id' => $partner_id,
            'visitor_ip' => $this->get_visitor_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? '',
            'landing_page' => $_SERVER['REQUEST_URI'] ?? '',
            'conversion_type' => $conversion_type,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            $tracking_id = $wpdb->insert_id;
            error_log('RPP Tracking: Successfully recorded tracking ID: ' . $tracking_id);
            return $tracking_id;
        } else {
            error_log('RPP Tracking: Failed to record tracking. MySQL error: ' . $wpdb->last_error);
            return false;
        }
    }
    
    /**
     * Track conversion (sale, signup, etc.)
     */
    public function track_conversion($order_id = null, $conversion_type = 'sale', $amount = 0) {
        $partner = $this->get_current_partner();
        
        if (!$partner) {
            return false;
        }
        
        // Record conversion tracking
        $tracking_id = $this->record_tracking($partner->id, $conversion_type);
        
        if ($tracking_id && $conversion_type === 'sale' && $amount > 0) {
            // Create commission
            $commission_class = new RPP_Commission();
            $commission_amount = $commission_class->calculate_commission($amount, $partner->commission_rate);
            
            if ($commission_amount > 0) {
                $commission_class->create_commission($partner->id, $commission_amount, $order_id, 'sale');
            }
        }
        
        return $tracking_id;
    }
    
    /**
     * Get tracking statistics
     */
    public function get_tracking_stats($partner_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $where = $wpdb->prepare("WHERE partner_id = %d", $partner_id);
        
        if ($date_from && $date_to) {
            $where .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $date_from, $date_to);
        }
        
        $stats = array();
        
        // Total clicks
        $stats['clicks'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} {$where} AND conversion_type = 'click'"
        );
        
        // Total conversions
        $stats['conversions'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} {$where} AND conversion_type != 'click'"
        );
        
        // Conversion rate
        $stats['conversion_rate'] = $stats['clicks'] > 0 ? round(($stats['conversions'] / $stats['clicks']) * 100, 2) : 0;
        
        // Top referrers
        $stats['top_referrers'] = $wpdb->get_results(
            "SELECT referrer_url, COUNT(*) as count 
             FROM {$this->table_name} {$where} 
             AND referrer_url != '' 
             GROUP BY referrer_url 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return $stats;
    }
    
    /**
     * Get visitor IP address
     */
    private function get_visitor_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '';
    }
    
    /**
     * Clean old tracking data
     */
    public function cleanup_old_data($days_old = 365) {
        global $wpdb;
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $date_threshold
            )
        );
    }
    
    /**
     * Get partner referral link
     */
    public function get_referral_link($partner_code, $page_url = null) {
        if (!$page_url) {
            $page_url = home_url();
        }
        
        return add_query_arg('ref', $partner_code, $page_url);
    }
    
    /**
     * Handle referral link processing
     */
    public function process_referral_links() {
        error_log('RPP Tracking: process_referral_links called');
        error_log('RPP Tracking: GET parameters: ' . print_r($_GET, true));
        error_log('RPP Tracking: Current URL: ' . $_SERVER['REQUEST_URI']);
        error_log('RPP Tracking: Is admin: ' . (is_admin() ? 'YES' : 'NO'));
        
        // Don't process in admin
        if (is_admin()) {
            return;
        }
        
        if (isset($_GET['ref'])) {
            $partner_code = sanitize_text_field($_GET['ref']);
            error_log('RPP Tracking: Processing referral link with code: ' . $partner_code);
            
            // Track the referral and set cookie
            if ($this->track_referral($partner_code)) {
                // Log successful tracking
                error_log('RPP Tracking: Successfully tracked referral for partner: ' . $partner_code);
            } else {
                error_log('RPP Tracking: Failed to track referral for partner: ' . $partner_code);
            }
            
            // Redirect to clean URL
            $clean_url = remove_query_arg('ref');
            error_log('RPP Tracking: Redirecting to clean URL: ' . $clean_url);
            wp_redirect($clean_url, 302);
            exit;
        }
        
        // Debug: Log current cookie state
        if (isset($_COOKIE[$this->cookie_name])) {
            error_log('RPP Tracking: Current partner cookie: ' . $_COOKIE[$this->cookie_name]);
        } else {
            error_log('RPP Tracking: No partner cookie found in process_referral_links');
        }
    }
}