<?php
/**
 * Public-facing functionality
 */
class RPP_Public {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, RPP_PLUGIN_URL . 'public/css/rpp-public.css', array(), $this->version, 'all');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, RPP_PLUGIN_URL . 'public/js/rpp-public.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'rpp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rpp_public_nonce')
        ));
    }
    
    public function init() {
        // Initialize tracking
        $tracking_class = new RPP_Tracking();
        add_action('wp', array($tracking_class, 'process_referral_links'));
    }
    
    public function handle_referral_tracking() {
        if (isset($_GET['ref'])) {
            $tracking_class = new RPP_Tracking();
            $tracking_class->process_referral_links();
        }
    }
    
    /**
     * Partner registration shortcode
     */
    public function partner_registration_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => ''
        ), $atts);
        
        // Check if user is already a partner
        if (is_user_logged_in()) {
            $partner_class = new RPP_Partner();
            $partner = $partner_class->get_partner_by_user(get_current_user_id());
            
            if ($partner) {
                $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
                return '<div class="rpp-notice">' . 
                       sprintf(__('You are already registered as a partner. <a href="%s">Go to your dashboard</a>', 'roanga-partner'), $dashboard_url) . 
                       '</div>';
            }
        }
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Partner dashboard shortcode
     */
    public function partner_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(get_permalink());
            return '<div class="rpp-notice">' . 
                   sprintf(__('Please <a href="%s">log in</a> to access your partner dashboard.', 'roanga-partner'), $login_url) . 
                   '</div>';
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner) {
            $registration_url = get_permalink(get_option('rpp_registration_page_id'));
            return '<div class="rpp-notice">' . 
                   sprintf(__('You are not registered as a partner yet. <a href="%s">Apply here</a>', 'roanga-partner'), $registration_url) . 
                   '</div>';
        }
        
        if ($partner->status !== 'approved') {
            return '<div class="rpp-notice">' . 
                   __('Your partner application is still being reviewed. We will notify you once it has been processed.', 'roanga-partner') . 
                   '</div>';
        }
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-partner-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Partner login shortcode
     */
    public function partner_login_shortcode($atts) {
        if (is_user_logged_in()) {
            $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
            return '<div class="rpp-notice">' . 
                   sprintf(__('You are already logged in. <a href="%s">Go to your dashboard</a>', 'roanga-partner'), $dashboard_url) . 
                   '</div>';
        }
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-partner-login.php';
        return ob_get_clean();
    }
    
    /**
     * Handle partner registration AJAX
     */
    public function handle_partner_registration() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        $website = sanitize_url($_POST['website']);
        $social_media = sanitize_textarea_field($_POST['social_media']);
        $experience = sanitize_textarea_field($_POST['experience']);
        $audience = sanitize_textarea_field($_POST['audience']);
        $motivation = sanitize_textarea_field($_POST['motivation']);
        $sponsor_code = sanitize_text_field($_POST['sponsor_code']);
        
        // Validation
        if (empty($website) || empty($experience) || empty($motivation)) {
            wp_send_json_error(__('Please fill in all required fields.', 'roanga-partner'));
        }
        
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Please enter a valid website URL.', 'roanga-partner'));
        }
        
        $user_id = get_current_user_id();
        
        // Handle user registration if not logged in
        if (!$user_id) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            
            if (empty($first_name) || empty($last_name) || empty($email)) {
                wp_send_json_error(__('Please fill in all personal information fields.', 'roanga-partner'));
            }
            
            if (!is_email($email)) {
                wp_send_json_error(__('Please enter a valid email address.', 'roanga-partner'));
            }
            
            if (email_exists($email)) {
                wp_send_json_error(__('An account with this email already exists.', 'roanga-partner'));
            }
            
            // Create user account
            $username = sanitize_user($email);
            $password = wp_generate_password();
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(__('Failed to create user account.', 'roanga-partner'));
            }
            
            // Update user meta
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
            
            // Send password reset email
            wp_new_user_notification($user_id, null, 'user');
        }
        
        // Check if user is already a partner
        $partner_class = new RPP_Partner();
        $existing_partner = $partner_class->get_partner_by_user($user_id);
        
        if ($existing_partner) {
            wp_send_json_error(__('You are already registered as a partner.', 'roanga-partner'));
        }
        
        // Validate sponsor code if provided
        if (!empty($sponsor_code)) {
            $sponsor = $partner_class->get_partner_by_code($sponsor_code);
            if (!$sponsor || $sponsor->status !== 'approved') {
                wp_send_json_error(__('Invalid sponsor code.', 'roanga-partner'));
            }
        }
        
        // Create partner application
        $application_data = array(
            'website' => $website,
            'social_media' => $social_media,
            'experience' => $experience,
            'audience' => $audience,
            'motivation' => $motivation
        );
        
        $partner_id = $partner_class->create_partner($user_id, $application_data);
        
        if ($partner_id) {
            // Add to MLM structure if sponsor provided
            if (!empty($sponsor_code)) {
                $mlm_class = new RPP_MLM_Structure();
                $mlm_class->add_partner_to_structure($partner_id, $sponsor_code);
            }
            
            // Send notification emails
            $email_class = new RPP_Email_Notifications();
            $email_class->send_application_notification($partner_id);
            
            wp_send_json_success(__('Your partner application has been submitted successfully! We will review it and notify you via email.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to submit application. Please try again.', 'roanga-partner'));
        }
    }
    
    /**
     * Handle payout request AJAX
     */
    public function handle_payout_request() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to request a payout.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('You are not an approved partner.', 'roanga-partner'));
        }
        
        $amount = floatval($_POST['amount']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Get partner stats to check available balance
        $stats = $partner_class->get_partner_stats($partner->id);
        $available_balance = $stats['available_balance'];
        
        if ($amount <= 0) {
            wp_send_json_error(__('Please enter a valid amount.', 'roanga-partner'));
        }
        
        if ($amount > $available_balance) {
            wp_send_json_error(__('Requested amount exceeds your available balance.', 'roanga-partner'));
        }
        
        $minimum_payout = get_option('rpp_minimum_payout', 50);
        if ($amount < $minimum_payout) {
            wp_send_json_error(sprintf(__('Minimum payout amount is %s.', 'roanga-partner'), wc_price($minimum_payout)));
        }
        
        // Handle file upload (invoice)
        $invoice_url = '';
        if (!empty($_FILES['invoice']) && $_FILES['invoice']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['invoice'], array('test_form' => false));
            if (!isset($upload['error'])) {
                $invoice_url = $upload['url'];
            }
        }
        
        // Create payout request
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'rpp_payouts',
            array(
                'partner_id' => $partner->id,
                'amount' => $amount,
                'status' => 'requested',
                'invoice_url' => $invoice_url,
                'notes' => $notes,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(__('Payout request submitted successfully!', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to submit payout request. Please try again.', 'roanga-partner'));
        }
    }
    
    /**
     * Get payout data AJAX
     */
    public function get_payout_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('You are not an approved partner.', 'roanga-partner'));
        }
        
        // Get partner stats
        $stats = $partner_class->get_partner_stats($partner->id);
        
        // Get payout history
        global $wpdb;
        $payouts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rpp_payouts 
                 WHERE partner_id = %d 
                 ORDER BY created_at DESC",
                $partner->id
            )
        );
        
        // Get bank info
        $bank_account = get_user_meta(get_current_user_id(), 'rpp_bank_account', true);
        $bank_name = get_user_meta(get_current_user_id(), 'rpp_bank_name', true);
        
        wp_send_json_success(array(
            'stats' => $stats,
            'payouts' => $payouts,
            'bank_account' => $bank_account,
            'bank_name' => $bank_name,
            'minimum_payout' => get_option('rpp_minimum_payout', 50)
        ));
    }
    
    /**
     * Search partner by code AJAX
     */
    public function search_partner() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        $partner_code = sanitize_text_field($_POST['partner_code']);
        
        if (empty($partner_code)) {
            wp_send_json_error(__('Please enter a partner code.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_code($partner_code);
        
        if ($partner && $partner->status === 'approved') {
            wp_send_json_success(array(
                'name' => $partner->display_name,
                'code' => $partner->partner_code
            ));
        } else {
            wp_send_json_error(__('Partner not found or not approved.', 'roanga-partner'));
        }
    }
}