<?php
/**
 * Public facing functionality class
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
        // Register AJAX handlers
        add_action('wp_ajax_rpp_partner_registration', array($this, 'handle_partner_registration'));
        add_action('wp_ajax_nopriv_rpp_partner_registration', array($this, 'handle_partner_registration'));
        add_action('wp_ajax_rpp_partner_login', array($this, 'handle_partner_login'));
        add_action('wp_ajax_nopriv_rpp_partner_login', array($this, 'handle_partner_login'));
        add_action('wp_ajax_rpp_search_partner', array($this, 'handle_partner_search'));
        add_action('wp_ajax_nopriv_rpp_search_partner', array($this, 'handle_partner_search'));
        add_action('wp_ajax_rpp_get_mlm_tree', array($this, 'handle_get_mlm_tree'));
        add_action('wp_ajax_rpp_load_tracking_data', array($this, 'handle_load_tracking_data'));
        add_action('wp_ajax_rpp_load_team_data', array($this, 'handle_load_team_data'));
        add_action('wp_ajax_rpp_load_orders_data', array($this, 'handle_load_orders_data'));
        add_action('wp_ajax_rpp_update_bank_account', array($this, 'handle_update_bank_account'));
        add_action('wp_ajax_rpp_request_payout', array($this, 'handle_request_payout'));
        add_action('wp_ajax_rpp_load_payout_data', array($this, 'handle_load_payout_data'));
    }
    
    /**
     * Handle referral tracking on wp hook
     */
    public function handle_referral_tracking() {
        // Handle referral tracking
        $tracking = new RPP_Tracking();
        $tracking->process_referral_links();
    }
    /**
     * Partner registration shortcode
     */
    public function partner_registration_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect_url' => '',
        ), $atts);
        
        // Check if user is already a partner (only if logged in)
        if (is_user_logged_in()) {
            $partner_class = new RPP_Partner();
            $existing_partner = $partner_class->get_partner_by_user(get_current_user_id());
            
            if ($existing_partner) {
                $status_message = '';
                switch ($existing_partner->status) {
                    case 'pending':
                        $status_message = __('Vaše partnerská žádost je právě posuzována.', 'roanga-partner');
                        break;
                    case 'approved':
                        $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
                        $status_message = __('Již jste schváleným partnerem!', 'roanga-partner') . 
                                        ' <a href="' . $dashboard_url . '">' . __('Přejít na dashboard', 'roanga-partner') . '</a>';
                        break;
                    case 'rejected':
                        $status_message = __('Vaše předchozí partnerská žádost nebyla schválena.', 'roanga-partner');
                        break;
                }
                return '<div class="rpp-notice">' . $status_message . '</div>';
            }
        }
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Partner login shortcode
     */
    public function partner_login_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect_url' => '',
        ), $atts);
        
        // If user is already logged in, redirect to dashboard
        if (is_user_logged_in()) {
            $partner_class = new RPP_Partner();
            $partner = $partner_class->get_partner_by_user(get_current_user_id());
            
            if ($partner && $partner->status === 'approved') {
                $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
                wp_redirect($dashboard_url);
                exit;
            }
        }
        
        $redirect_to = $atts['redirect_url'] ? $atts['redirect_url'] : get_permalink(get_option('rpp_dashboard_page_id'));
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-partner-login.php';
        return ob_get_clean();
    }
    
    /**
     * Partner dashboard shortcode
     */
    public function partner_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            $login_url = get_permalink(get_option('rpp_login_page_id'));
            return '<p>' . __('Pro zobrazení dashboardu se musíte přihlásit.', 'roanga-partner') . 
                   ' <a href="' . $login_url . '">' . __('Přihlásit se zde', 'roanga-partner') . '</a></p>';
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner) {
            $registration_url = get_permalink(get_option('rpp_registration_page_id'));
            return '<p>' . __('Nejste registrováni jako partner.', 'roanga-partner') . 
                   ' <a href="' . $registration_url . '">' . __('Registrovat se zde', 'roanga-partner') . '</a></p>';
        }
        
        if ($partner->status !== 'approved') {
            return '<div class="rpp-notice">' . __('Vaše partnerská žádost je stále posuzována.', 'roanga-partner') . '</div>';
        }
        
        // Get partner statistics
        $stats = $partner_class->get_partner_stats($partner->id);
        
        // Get partner group for bonus calculations
        $groups_class = new RPP_Partner_Groups();
        $partner_group = $groups_class->get_group($partner->group_id ?? 1);
        
        // Get team statistics for MLM
        if (get_option('rpp_mlm_enabled', false)) {
            $mlm_class = new RPP_MLM_Structure();
            $team_stats = $mlm_class->get_team_statistics($partner->id);
        } else {
            $team_stats = array('direct_referrals' => 0);
        }
        
        // Get recent commissions
        $commission_class = new RPP_Commission();
        $recent_commissions = $commission_class->get_partner_commissions($partner->id, null, 10);
        
        // Get tracking data
        $tracking_class = new RPP_Tracking();
        $tracking_stats = $tracking_class->get_tracking_stats($partner->id);
        $referral_link = $tracking_class->get_referral_link($partner->partner_code);
        
        // Get currency symbol from WooCommerce
        
        ob_start();
        include RPP_PLUGIN_PATH . 'public/partials/rpp-partner-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Calculate team volume for specific period
     */
    private function calculate_team_volume_for_period($partner_id, $period) {
        global $wpdb;
        
        // Calculate period start date
        $period_start = '';
        switch ($period) {
            case 'weekly':
                // Start of current week (Monday)
                $period_start = date('Y-m-d', strtotime('monday this week'));
                break;
            case 'monthly':
                // Start of current month
                $period_start = date('Y-m-01');
                break;
            case 'quarterly':
                // Start of current quarter
                $current_quarter = ceil(date('n') / 3);
                $quarter_start_month = ($current_quarter - 1) * 3 + 1;
                $period_start = date('Y-' . sprintf('%02d', $quarter_start_month) . '-01');
                break;
            case 'semi-annually':
                // Start of current half-year
                $current_month = date('n');
                $half_start_month = $current_month <= 6 ? 1 : 7;
                $period_start = date('Y-' . sprintf('%02d', $half_start_month) . '-01');
                break;
            case 'annually':
                // Start of current year
                $period_start = date('Y-01-01');
                break;
            default:
                $period_start = date('Y-m-01'); // Default to monthly
        }
        
        // Get MLM structure to find team members
        $mlm_class = new RPP_MLM_Structure();
        $team_members = $mlm_class->get_partner_downline($partner_id);
        
        if (empty($team_members)) {
            return 0;
        }
        
        // Get partner IDs including the main partner
        $partner_ids = array($partner_id);
        foreach ($team_members as $member) {
            $partner_ids[] = $member->partner_id;
        }
        
        $partner_ids_str = implode(',', array_map('intval', $partner_ids));
        
        // Calculate total volume from commissions in the period
        $total_volume = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN c.type = 'sale' AND o.ID IS NOT NULL THEN o.total
                        ELSE c.amount * 10
                    END
                ), 0)
                FROM {$wpdb->prefix}rpp_commissions c
                LEFT JOIN {$wpdb->posts} o ON c.order_id = o.ID AND o.post_type = 'shop_order'
                WHERE c.partner_id IN ($partner_ids_str) 
                AND c.status IN ('approved', 'paid')
                AND DATE(c.created_at) >= %s
            ", $period_start)
        );
        
        return floatval($total_volume);
    }
    
    /**
     * Handle partner registration AJAX
     */
    public function handle_partner_registration() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        // Get or create user
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            // Create new user account
            $email = sanitize_email($_POST['email']);
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            
            if (empty($email) || empty($first_name) || empty($last_name)) {
                wp_send_json_error(__('Prosím vyplňte všechna povinná pole.', 'roanga-partner'));
            }
            
            if (!is_email($email)) {
                wp_send_json_error(__('Prosím zadejte platnou emailovou adresu.', 'roanga-partner'));
            }
            
            if (email_exists($email)) {
                wp_send_json_error(__('Uživatel s tímto emailem již existuje.', 'roanga-partner'));
            }
            
            // Generate username and password
            $username = sanitize_user($email);
            $password = wp_generate_password(12, false);
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(__('Chyba při vytváření účtu: ', 'roanga-partner') . $user_id->get_error_message());
            }
            
            // Update user meta
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
            
            // Send login credentials with password
            $this->send_welcome_email($user_id, $password, $email, $first_name . ' ' . $last_name);
        }
        
        // Check if user is already a partner
        $partner_class = new RPP_Partner();
        $existing_partner = $partner_class->get_partner_by_user($user_id);
        
        if ($existing_partner) {
            wp_send_json_error(__('You have already applied as a partner.', 'roanga-partner'));
        }
        
        // Validate form data
        $website = sanitize_url($_POST['website']);
        $experience = sanitize_textarea_field($_POST['experience']);
        $motivation = sanitize_textarea_field($_POST['motivation']);
        $sponsor_code = sanitize_text_field($_POST['sponsor_code']);
        
        if (empty($website) || empty($experience) || empty($motivation)) {
            wp_send_json_error(__('Prosím vyplňte všechna povinná pole.', 'roanga-partner'));
        }
        
        // Check sponsor code requirement
        if (get_option('rpp_mlm_require_sponsor', false) && empty($sponsor_code)) {
            wp_send_json_error(__('Sponzorský kód je povinný nebo zaškrtněte "Nemám doporučitele".', 'roanga-partner'));
        }
        
        // Additional form data
        $application_data = array(
            'website' => $website,
            'experience' => $experience,
            'motivation' => $motivation,
            'social_media' => sanitize_text_field($_POST['social_media']),
            'audience' => sanitize_textarea_field($_POST['audience']),
            'sponsor_code' => $sponsor_code
        );
        
        // Create partner application
        $partner_id = $partner_class->create_partner($user_id, $application_data);
        
        if ($partner_id) {
            // Add to MLM structure if enabled
            if (get_option('rpp_mlm_enabled', false)) {
                $mlm_class = new RPP_MLM_Structure();
                $sponsor_code = sanitize_text_field($_POST['sponsor_code']);
                $mlm_class->add_partner_to_structure($partner_id, $sponsor_code);
            }
            
            // Send email notifications
            $email_class = new RPP_Email_Notifications();
            $email_class->send_application_notification($partner_id);
            
            $success_message = __('Vaše partnerská žádost byla úspěšně odeslána!', 'roanga-partner');
            if (!is_user_logged_in()) {
                $success_message .= ' ' . __('Přihlašovací údaje vám byly zaslány na email.', 'roanga-partner');
            }
            
            wp_send_json_success($success_message);
        } else {
            wp_send_json_error(__('Chyba při odesílání žádosti. Zkuste to prosím znovu.', 'roanga-partner'));
        }
    }
    
    /**
     * Send welcome email with login credentials
     */
    private function send_welcome_email($user_id, $password, $email, $display_name) {
        $login_url = get_permalink(get_option('rpp_login_page_id'));
        $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
        
        $subject = sprintf(__('Vítejte v partnerském programu - %s', 'roanga-partner'), get_bloginfo('name'));
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $subject . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; }
                .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d4af37; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: #2d5a27; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Vítejte v partnerském programu!</h1>
                </div>
                
                <div class="content">
                    <p>Vážený/á ' . esc_html($display_name) . ',</p>
                    
                    <p>Děkujeme za registraci do našeho partnerského programu! Váš účet byl úspěšně vytvořen.</p>
                    
                    <div class="credentials">
                        <h3>🔐 Vaše přihlašovací údaje:</h3>
                        <p><strong>Email:</strong> ' . esc_html($email) . '</p>
                        <p><strong>Heslo:</strong> <code>' . esc_html($password) . '</code></p>
                    </div>
                    
                    <p><strong>⚠️ Důležité:</strong> Doporučujeme vám po prvním přihlášení změnit heslo na vlastní.</p>
                    
                    <p>Vaše partnerská žádost bude brzy posouzena naším týmem. Po schválení budete moci začít vydělávat provize!</p>
                    
                    <p style="text-align: center;">
                        <a href="' . esc_url($login_url) . '" class="button">Přihlásit se do dashboardu</a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>S pozdravem,<br>Tým ' . get_bloginfo('name') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Handle partner login AJAX
     */
    public function handle_partner_login() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        $email = sanitize_email($_POST['partner_email']);
        $password = sanitize_text_field($_POST['partner_password']);
        $remember = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(__('Prosím vyplňte email a heslo.', 'roanga-partner'));
        }
        
        // Authenticate user
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error(__('Nesprávný email nebo heslo.', 'roanga-partner'));
        }
        
        // Check if user is a partner
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user($user->ID);
        
        if (!$partner) {
            wp_send_json_error(__('Tento účet není registrován jako partner.', 'roanga-partner'));
        }
        
        if ($partner->status !== 'approved') {
            wp_send_json_error(__('Váš partnerský účet ještě nebyl schválen.', 'roanga-partner'));
        }
        
        // Log user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        $dashboard_url = get_permalink(get_option('rpp_dashboard_page_id'));
        
        wp_send_json_success(array(
            'message' => __('Úspěšně přihlášen! Přesměrovávám...', 'roanga-partner'),
            'redirect_url' => $dashboard_url
        ));
    }
    
    /**
     * Handle partner search AJAX
     */
    public function handle_partner_search() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'rpp_public_nonce')) {
            wp_send_json_error(__('Bezpečnostní kontrola selhala.', 'roanga-partner'));
        }
        
        $partner_code = sanitize_text_field($_POST['partner_code']);
        
        if (empty($partner_code)) {
            wp_send_json_error(__('Zadejte partnerský kód.', 'roanga-partner'));
        }
        
        global $wpdb;
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email 
                 FROM {$wpdb->prefix}rpp_partners p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 WHERE p.partner_code = %s AND p.status = 'approved'",
                $partner_code
            )
        );
        
        if ($partner) {
            wp_send_json_success(array(
                'name' => $partner->display_name,
                'email' => $partner->user_email,
                'code' => $partner->partner_code
            ));
        } else {
            wp_send_json_error(__('Partner s tímto kódem nebyl nalezen nebo není schválen.', 'roanga-partner'));
        }
    }
    
    /**
     * Handle MLM tree AJAX for partners
     */
    public function handle_get_mlm_tree() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        $mlm_class = new RPP_MLM_Structure();
        $downline = $mlm_class->get_partner_downline($partner->id, 3);
        
        if (empty($downline)) {
            wp_send_json_success('<div style="text-align: center; padding: 40px; color: #666;"><h4>Žádní partneři v týmu</h4><p>Zatím nemáte žádné referály.</p></div>');
            return;
        }
        
        // Group by levels
        $levels = array();
        $root_level = null;
        
        foreach ($downline as $member) {
            if ($root_level === null) {
                $root_level = $member->level - 1;
            }
            $level = $member->level - $root_level;
            if (!isset($levels[$level])) {
                $levels[$level] = array();
            }
            $levels[$level][] = $member;
        }
        
        $html = '<div class="rpp-mlm-tree">';
        
        foreach ($levels as $level => $members) {
            if ($level > 3) break;
            
            $html .= '<div class="rpp-tree-level">';
            $html .= '<h4 style="width: 100%; text-align: center; color: #2d5a27; margin-bottom: 16px;">Úroveň ' . $level . ' (' . count($members) . ' partnerů)</h4>';
            
            foreach ($members as $member) {
                $html .= '<div class="rpp-tree-node level-' . $level . '">';
                $html .= '<div class="rpp-node-name">' . esc_html($member->display_name) . '</div>';
                $html .= '<div class="rpp-node-code">' . esc_html($member->partner_code) . '</div>';
                $html .= '<div class="rpp-node-earnings">' . number_format($member->total_earnings, 0, ',', ' ') . ' Kč</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Handle tracking data AJAX
     */
    public function handle_load_tracking_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        global $wpdb;
        
        // Get tracking data
        $tracking_data = $wpdb->get_results(
            $wpdb->prepare("
                SELECT 
                    DATE(created_at) as date,
                    conversion_type,
                    referrer_url,
                    landing_page,
                    visitor_ip,
                    created_at
                FROM {$wpdb->prefix}rpp_tracking 
                WHERE partner_id = %d 
                ORDER BY created_at DESC 
                LIMIT 100
            ", $partner->id)
        );
        
        $html = '<div class="rpp-tracking-list">';
        
        if (empty($tracking_data)) {
            $html .= '<div class="rpp-empty-state"><p>Zatím žádné sledované aktivity.</p></div>';
        } else {
            foreach ($tracking_data as $track) {
                $html .= '<div class="rpp-tracking-item">';
                $html .= '<div class="rpp-tracking-header">';
                $html .= '<span class="rpp-tracking-date">' . date_i18n('j.n.Y H:i', strtotime($track->created_at)) . '</span>';
                $html .= '<span class="rpp-tracking-type rpp-type-' . esc_attr($track->conversion_type) . '">' . esc_html(ucfirst($track->conversion_type)) . '</span>';
                $html .= '</div>';
                $html .= '<div class="rpp-tracking-details">';
                $html .= '<div><strong>Zdroj:</strong> ' . esc_html($track->referrer_url ?: 'Přímý přístup') . '</div>';
                $html .= '<div><strong>Stránka:</strong> ' . esc_html($track->landing_page) . '</div>';
                $html .= '<div><strong>IP:</strong> ' . esc_html($track->visitor_ip) . '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Handle team data AJAX
     */
    public function handle_load_team_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        $mlm_class = new RPP_MLM_Structure();
        $direct_referrals = $mlm_class->get_direct_referrals($partner->id);
        
        $html = '<div class="rpp-team-structure">';
        
        if (empty($direct_referrals)) {
            $html .= '<div class="rpp-empty-state"><p>Zatím nemáte žádné přímé referály.</p></div>';
        } else {
            $html .= '<table class="rpp-team-table">';
            $html .= '<thead><tr><th>Partner</th><th>Kód</th><th>Email</th><th>Výdělky</th><th>Status</th><th>Registrace</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($direct_referrals as $referral) {
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($referral->display_name) . '</strong></td>';
                $html .= '<td><code>' . esc_html($referral->partner_code) . '</code></td>';
                $html .= '<td>' . esc_html($referral->user_email) . '</td>';
                $html .= '<td><strong>' . wc_price($referral->total_earnings) . '</strong></td>';
                $html .= '<td><span class="rpp-status-' . esc_attr($referral->status) . '">' . esc_html(ucfirst($referral->status)) . '</span></td>';
                $html .= '<td>' . date_i18n('j.n.Y', strtotime($referral->created_at)) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Handle orders data AJAX
     */
    public function handle_load_orders_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        $commission_class = new RPP_Commission();
        $commissions = $commission_class->get_partner_commissions($partner->id, null, 50);
        
        $html = '<div class="rpp-orders-list">';
        
        if (empty($commissions)) {
            $html .= '<div class="rpp-empty-state"><p>Zatím žádné objednávky s provizemi.</p></div>';
        } else {
            $html .= '<table class="rpp-orders-table">';
            $html .= '<thead><tr><th>Datum</th><th>Číslo objednávky</th><th>Hodnota objednávky</th><th>Provize</th><th>Status</th><th>Typ</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach ($commissions as $commission) {
                $order_value = 0;
                if ($commission->order_id && function_exists('wc_get_order')) {
                    $order = wc_get_order($commission->order_id);
                    if ($order) {
                        $order_value = $order->get_total();
                    }
                }
                
                $html .= '<tr>';
                $html .= '<td>' . date_i18n('j.n.Y H:i', strtotime($commission->created_at)) . '</td>';
                $html .= '<td>' . ($commission->order_id ? '#' . $commission->order_id : '—') . '</td>';
                $html .= '<td>' . ($order_value ? wc_price($order_value) : '—') . '</td>';
                $html .= '<td><strong>' . wc_price($commission->amount) . '</strong></td>';
                $html .= '<td><span class="rpp-status-' . esc_attr($commission->status) . '">' . esc_html(ucfirst($commission->status)) . '</span></td>';
                $html .= '<td>' . esc_html($commission->type) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Load payout data for dashboard
     */
    public function load_payout_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner) {
            wp_send_json_error(__('Partner nebyl nalezen.', 'roanga-partner'));
        }
        
        global $wpdb;
        
        // Get partner's payout requests
        $payout_requests = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}rpp_payouts 
                WHERE partner_id = %d 
                ORDER BY created_at DESC
            ", $partner->id)
        );
        
        // Get partner stats
        $stats = $partner_class->get_partner_stats($partner->id);
        $minimum_payout = get_option('rpp_minimum_payout', 50);
        $pending_amount = $stats['pending_commissions'] ?? 0;
        $bank_account = get_user_meta(get_current_user_id(), 'rpp_bank_account', true);
        $bank_name = get_user_meta(get_current_user_id(), 'rpp_bank_name', true);
        $can_request_payout = $pending_amount >= $minimum_payout && !empty($bank_account);
        
        $html = '<div class="rpp-payout-content">';
        
        // Payout form section
        $html .= '<div class="rpp-payout-form-section">';
        $html .= '<h3>Žádost o výplatu</h3>';
        
        if (!$bank_account) {
            $html .= '<div class="rpp-notice rpp-notice-warning">';
            $html .= '<p>Před žádostí o výplatu musíte nastavit číslo účtu v záložce "Přehled".</p>';
            $html .= '</div>';
        } elseif ($pending_amount < $minimum_payout) {
            $html .= '<div class="rpp-notice rpp-notice-info">';
            $html .= '<p>Pro žádost o výplatu potřebujete minimálně ' . wc_price($minimum_payout) . '. Aktuálně máte ' . wc_price($pending_amount) . '.</p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="rpp-payout-form">';
            $html .= '<div class="rpp-form-group">';
            $html .= '<label>Dostupná částka: <strong>' . wc_price($pending_amount) . '</strong></label>';
            $html .= '</div>';
            $html .= '<div class="rpp-form-group">';
            $html .= '<label>Číslo účtu: ' . esc_html($bank_account) . '</label>';
            if ($bank_name) {
                $html .= '<small>Banka: ' . esc_html($bank_name) . '</small>';
            }
            $html .= '</div>';
            $html .= '<form id="rpp-payout-request-form">';
            $html .= '<div class="rpp-form-group">';
            $html .= '<label for="payout_amount">Částka k výplatě:</label>';
            $html .= '<input type="number" id="payout_amount" name="payout_amount" min="' . $minimum_payout . '" max="' . $pending_amount . '" step="0.01" value="' . $pending_amount . '" required>';
            $html .= '</div>';
            $html .= '<div class="rpp-form-group">';
            $html .= '<label for="payout_invoice">Faktura (PDF):</label>';
            $html .= '<input type="file" id="payout_invoice" name="payout_invoice" accept=".pdf" required>';
            $html .= '</div>';
            $html .= '<div class="rpp-form-group">';
            $html .= '<label for="payout_notes">Poznámky (volitelné):</label>';
            $html .= '<textarea id="payout_notes" name="payout_notes" rows="3"></textarea>';
            $html .= '</div>';
            $html .= '<button type="submit" class="rpp-btn rpp-btn-primary">Odeslat žádost</button>';
            $html .= '</form>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Payout history
        $html .= '<div class="rpp-payout-history-section">';
        $html .= '<h3>Historie výplat</h3>';
        
        if (empty($payout_requests)) {
            $html .= '<div class="rpp-empty-state"><p>Zatím žádné žádosti o výplatu.</p></div>';
        } else {
            $html .= '<div class="rpp-payout-history">';
            $html .= '<table class="rpp-payout-table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Datum</th>';
            $html .= '<th>Částka</th>';
            $html .= '<th>Status</th>';
            $html .= '<th>Faktura</th>';
            $html .= '<th>Poznámky</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            
            foreach ($payout_requests as $request) {
                $html .= '<tr>';
                $html .= '<td>' . date_i18n('j.n.Y H:i', strtotime($request->created_at)) . '</td>';
                $html .= '<td><strong>' . wc_price($request->amount) . '</strong></td>';
                $html .= '<td><span class="rpp-status-' . esc_attr($request->status) . '">' . esc_html(ucfirst($request->status)) . '</span></td>';
                $html .= '<td>';
                if ($request->invoice_url) {
                    $html .= '<a href="' . esc_url($request->invoice_url) . '" target="_blank" class="rpp-invoice-link">📄 Zobrazit</a>';
                } else {
                    $html .= '—';
                }
                $html .= '</td>';
                $html .= '<td>' . esc_html($request->notes ?: '—') . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    /**
     * Handle load payout data AJAX
     */
    public function handle_load_payout_data() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        global $wpdb;
        
        // Get partner's payout requests
        $payout_requests = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}rpp_payouts 
                WHERE partner_id = %d 
                ORDER BY created_at DESC
            ", $partner->id)
        );
        
        // Get partner stats for available amount
        $stats = $partner_class->get_partner_stats($partner->id);
        $available_amount = ($stats['paid_commissions'] ?? 0) - ($stats['total_payouts'] ?? 0);
        $minimum_payout = get_option('rpp_minimum_payout', 50);
        
        // Get bank account info
        $bank_account = get_user_meta(get_current_user_id(), 'rpp_bank_account', true);
        $bank_name = get_user_meta(get_current_user_id(), 'rpp_bank_name', true);
        
        $html = '<div class="rpp-payout-content">';
        
        // Available amount display
        $html .= '<div class="rpp-payout-overview">';
        $html .= '<div class="rpp-available-amount-card">';
        $html .= '<div class="rpp-amount-header">';
        $html .= '<h3>💰 Dostupná částka</h3>';
        $html .= '<div class="rpp-amount-large">' . wc_price($available_amount) . '</div>';
        $html .= '</div>';
        
        // Progress to minimum
        $progress_percent = min(($available_amount / $minimum_payout) * 100, 100);
        $html .= '<div class="rpp-progress-section">';
        $html .= '<div class="rpp-progress-info">';
        $html .= '<span>Minimální výplata: ' . wc_price($minimum_payout) . '</span>';
        $html .= '<span>' . round($progress_percent, 1) . '%</span>';
        $html .= '</div>';
        $html .= '<div class="rpp-progress-bar">';
        $html .= '<div class="rpp-progress-fill" style="width: ' . $progress_percent . '%"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Payout request form
        $html .= '<div class="rpp-payout-form-section">';
        $html .= '<h3>📝 Nová žádost o výplatu</h3>';
        
        if (!$bank_account) {
            $html .= '<div class="rpp-info-box rpp-warning">';
            $html .= '<div class="rpp-info-icon">⚠️</div>';
            $html .= '<div class="rpp-info-content">';
            $html .= '<h4>Nastavte číslo účtu</h4>';
            $html .= '<p>Před žádostí o výplatu musíte nastavit číslo účtu v záložce "Přehled".</p>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="rpp-bank-account-info">';
            $html .= '<div class="rpp-bank-detail">';
            $html .= '<strong>Číslo účtu:</strong> ' . esc_html($bank_account);
            if ($bank_name) {
                $html .= '<br><strong>Banka:</strong> ' . esc_html($bank_name);
            }
            $html .= '</div>';
            $html .= '</div>';
            
            if ($available_amount >= $minimum_payout) {
                $html .= '<form id="rpp-payout-request-form" class="rpp-payout-form">';
                $html .= '<div class="rpp-form-group">';
                $html .= '<label for="payout_amount"><strong>Částka k výplatě:</strong></label>';
                $html .= '<input type="number" id="payout_amount" name="payout_amount" min="' . $minimum_payout . '" max="' . $available_amount . '" step="0.01" value="' . $available_amount . '" class="rpp-input" required>';
                $html .= '<small>Minimální výplata: ' . wc_price($minimum_payout) . '</small>';
                $html .= '</div>';
                
                $html .= '<div class="rpp-form-group">';
                $html .= '<label for="payout_invoice"><strong>Faktura (PDF) *:</strong></label>';
                $html .= '<input type="file" id="payout_invoice" name="payout_invoice" accept=".pdf" class="rpp-file-input" required>';
                $html .= '<small class="rpp-file-help">Nahrajte fakturu ve formátu PDF (max. 5MB)</small>';
                $html .= '</div>';
                
                $html .= '<div class="rpp-form-group">';
                $html .= '<label for="payout_notes"><strong>Poznámky (volitelné):</strong></label>';
                $html .= '<textarea id="payout_notes" name="payout_notes" rows="3" class="rpp-textarea" placeholder="Volitelné poznámky k výplatě"></textarea>';
                $html .= '</div>';
                
                $html .= '<div class="rpp-form-group">';
                $html .= '<button type="submit" class="rpp-btn rpp-btn-primary rpp-btn-large">📤 Odeslat žádost o výplatu</button>';
                $html .= '</div>';
                $html .= '</form>';
            } else {
                $html .= '<div class="rpp-info-box rpp-info">';
                $html .= '<div class="rpp-info-icon">ℹ️</div>';
                $html .= '<div class="rpp-info-content">';
                $html .= '<h4>Nedostatečná částka</h4>';
                $html .= '<p>Pro žádost o výplatu potřebujete minimálně ' . wc_price($minimum_payout) . '.<br>';
                $html .= 'Aktuálně máte ' . wc_price($available_amount) . '.</p>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        
        // Payout history
        $html .= '<div class="rpp-payout-history-section">';
        $html .= '<h3>📋 Historie výplat</h3>';
        
        if (empty($payout_requests)) {
            $html .= '<div class="rpp-info-box rpp-info">';
            $html .= '<div class="rpp-info-icon">📋</div>';
            $html .= '<div class="rpp-info-content">';
            $html .= '<h4>Žádné žádosti</h4>';
            $html .= '<p>Zatím jste nepodali žádnou žádost o výplatu.</p>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="rpp-payout-history-list">';
            
            foreach ($payout_requests as $request) {
                $status_class = 'rpp-payout-status-' . $request->status;
                $status_icon = '';
                $status_text = '';
                
                switch ($request->status) {
                    case 'requested':
                        $status_icon = '⏳';
                        $status_text = 'Čeká na schválení';
                        break;
                    case 'approved':
                        $status_icon = '✅';
                        $status_text = 'Schváleno';
                        break;
                    case 'completed':
                        $status_icon = '💰';
                        $status_text = 'Vyplaceno';
                        break;
                    case 'rejected':
                        $status_icon = '❌';
                        $status_text = 'Zamítnuto';
                        break;
                }
                
                $html .= '<div class="rpp-payout-history-item ' . $status_class . '">';
                $html .= '<div class="rpp-payout-header">';
                $html .= '<div class="rpp-payout-date">' . date_i18n('j.n.Y H:i', strtotime($request->created_at)) . '</div>';
                $html .= '<div class="rpp-payout-status">';
                $html .= '<span class="rpp-status-icon">' . $status_icon . '</span>';
                $html .= '<span class="rpp-status-text">' . $status_text . '</span>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="rpp-payout-details">';
                $html .= '<div class="rpp-payout-amount">' . wc_price($request->amount) . '</div>';
                if ($request->invoice_url) {
                    $html .= '<div class="rpp-payout-invoice">';
                    $html .= '<a href="' . esc_url($request->invoice_url) . '" target="_blank" class="rpp-invoice-link">📄 Zobrazit fakturu</a>';
                    $html .= '</div>';
                }
                if ($request->notes) {
                    $html .= '<div class="rpp-payout-notes">';
                    $html .= '<strong>Poznámky:</strong> ' . esc_html($request->notes);
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Add styles for payout tab
        $html .= '<style>
        .rpp-payout-content {
            display: grid;
            gap: 24px;
        }
        
        .rpp-payout-overview {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .rpp-available-amount-card {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            border-left: 6px solid #4a7c59;
            box-shadow: 0 4px 20px rgba(74, 124, 89, 0.15);
        }
        
        .rpp-amount-header h3 {
            margin: 0 0 16px 0;
            color: #2d5a27;
            font-size: 20px;
            font-weight: 600;
        }
        
        .rpp-amount-large {
            font-size: 36px;
            font-weight: 700;
            color: #2d5a27;
            margin-bottom: 20px;
        }
        
        .rpp-progress-section {
            margin-top: 16px;
        }
        
        .rpp-progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #2d5a27;
            font-weight: 600;
        }
        
        .rpp-progress-bar {
            height: 12px;
            background: rgba(255,255,255,0.3);
            border-radius: 6px;
            overflow: hidden;
        }
        
        .rpp-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a7c59, #66bb6a);
            transition: width 0.3s ease;
        }
        
        .rpp-payout-form-section,
        .rpp-payout-history-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8f5e8;
        }
        
        .rpp-payout-form-section h3,
        .rpp-payout-history-section h3 {
            margin: 0 0 24px 0;
            color: #2d5a27;
            font-size: 22px;
            font-weight: 600;
            border-bottom: 2px solid #e8f5e8;
            padding-bottom: 12px;
        }
        
        .rpp-info-box {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .rpp-info-box.rpp-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
        }
        
        .rpp-info-box.rpp-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-left: 4px solid #2196f3;
        }
        
        .rpp-info-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .rpp-info-content h4 {
            margin: 0 0 8px 0;
            color: #2d5a27;
            font-size: 16px;
            font-weight: 600;
        }
        
        .rpp-info-content p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }
        
        .rpp-bank-account-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4a7c59;
        }
        
        .rpp-bank-detail {
            color: #2d5a27;
            font-size: 14px;
        }
        
        .rpp-payout-form {
            margin-top: 20px;
        }
        
        .rpp-form-group {
            margin-bottom: 20px;
        }
        
        .rpp-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d5a27;
            font-size: 14px;
        }
        
        .rpp-input,
        .rpp-textarea,
        .rpp-file-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8f5e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .rpp-input:focus,
        .rpp-textarea:focus {
            outline: none;
            border-color: #4a7c59;
            box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.1);
        }
        
        .rpp-file-input {
            border-style: dashed;
            background: #f8f9fa;
            cursor: pointer;
        }
        
        .rpp-file-input:hover {
            border-color: #4a7c59;
            background: #e8f5e8;
        }
        
        .rpp-file-help {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .rpp-btn-large {
            padding: 16px 32px;
            font-size: 16px;
            width: 100%;
            justify-content: center;
        }
        
        .rpp-payout-history-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .rpp-payout-history-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .rpp-payout-history-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .rpp-payout-status-requested {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .rpp-payout-status-approved {
            border-left-color: #17a2b8;
            background: #d1ecf1;
        }
        
        .rpp-payout-status-completed {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .rpp-payout-status-rejected {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .rpp-payout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .rpp-payout-date {
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }
        
        .rpp-payout-status {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .rpp-status-icon {
            font-size: 16px;
        }
        
        .rpp-status-text {
            font-size: 13px;
            font-weight: 600;
            color: #2d5a27;
        }
        
        .rpp-payout-details {
            display: grid;
            gap: 12px;
        }
        
        .rpp-payout-amount {
            font-size: 24px;
            font-weight: 700;
            color: #2d5a27;
        }
        
        .rpp-payout-invoice {
            margin-top: 8px;
        }
        
        .rpp-invoice-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2d5a27;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 12px;
            background: #e8f5e8;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .rpp-invoice-link:hover {
            background: #d4edda;
            transform: translateY(-1px);
        }
        
        .rpp-payout-notes {
            font-size: 14px;
            color: #555;
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .rpp-payout-overview {
                grid-template-columns: 1fr;
            }
            
            .rpp-amount-large {
                font-size: 28px;
            }
            
            .rpp-payout-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        </style>';
        
        // Add JavaScript for payout form
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const payoutForm = document.getElementById("rpp-payout-request-form");
            if (payoutForm) {
                payoutForm.addEventListener("submit", function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append("action", "rpp_request_payout");
                    formData.append("nonce", rpp_ajax.nonce);
                    
                    const submitBtn = this.querySelector("button[type=submit]");
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = "📤 Odesílám...";
                    
                    fetch(rpp_ajax.ajax_url, {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification("Žádost o výplatu byla odeslána!", "success");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification("Chyba: " + data.data, "error");
                        }
                    })
                    .catch(error => {
                        showNotification("Chyba při komunikaci se serverem", "error");
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
                });
            }
        });
        </script>';
        return array(
            'html' => ob_get_clean()
        );
    }
    
    /**
     * Handle bank account update AJAX
     */
    public function handle_update_bank_account() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $bank_account = sanitize_text_field($_POST['bank_account']);
        $bank_name = sanitize_text_field($_POST['bank_name']);
        
        if (empty($bank_account)) {
            wp_send_json_error(__('Číslo účtu je povinné.', 'roanga-partner'));
        }
        
        // Basic validation for Czech bank account format
        if (!preg_match('/^\d+\/\d{4}$/', $bank_account)) {
            wp_send_json_error(__('Neplatný formát čísla účtu. Použijte formát: číslo/kód_banky', 'roanga-partner'));
        }
        
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'rpp_bank_account', $bank_account);
        update_user_meta($user_id, 'rpp_bank_name', $bank_name);
        
        wp_send_json_success(__('Číslo účtu bylo úspěšně uloženo.', 'roanga-partner'));
    }
    
    /**
     * Handle payout request AJAX
     */
    public function handle_request_payout() {
        check_ajax_referer('rpp_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_user(get_current_user_id());
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Nejste schváleným partnerem.', 'roanga-partner'));
        }
        
        $payout_amount = floatval($_POST['payout_amount']);
        $payout_notes = sanitize_textarea_field($_POST['payout_notes']);
        $minimum_payout = get_option('rpp_minimum_payout', 50);
        
        // Get partner stats
        $stats = $partner_class->get_partner_stats($partner->id);
        $available_amount = $stats['pending_commissions'] ?? 0;
        
        if ($payout_amount < $minimum_payout) {
            wp_send_json_error(sprintf(__('Minimální výplata je %s.', 'roanga-partner'), wc_price($minimum_payout)));
        }
        
        if ($payout_amount > $available_amount) {
            wp_send_json_error(__('Požadovaná částka překračuje dostupnou částku.', 'roanga-partner'));
        }
        
        // Handle file upload
        $invoice_url = '';
        if (isset($_FILES['payout_invoice']) && $_FILES['payout_invoice']['error'] === UPLOAD_ERR_OK) {
            $upload_result = $this->handle_invoice_upload($_FILES['payout_invoice'], $partner->id);
            if (is_wp_error($upload_result)) {
                wp_send_json_error($upload_result->get_error_message());
            }
            $invoice_url = $upload_result;
        } else {
            wp_send_json_error(__('Faktura je povinná.', 'roanga-partner'));
        }
        
        // Create payout request
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'rpp_payouts',
            array(
                'partner_id' => $partner->id,
                'amount' => $payout_amount,
                'status' => 'requested',
                'payout_method' => 'bank_transfer',
                'notes' => $payout_notes,
                'invoice_url' => $invoice_url,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            // Send notification to admin
            $this->send_payout_request_notification($partner, $payout_amount, $invoice_url);
            
            wp_send_json_success(__('Žádost o výplatu byla úspěšně odeslána.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při odesílání žádosti.', 'roanga-partner'));
        }
    }
    
    /**
     * Get payout data for dashboard
     */
    public function get_payout_data() {
        check_ajax_referer('rpp_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Musíte být přihlášeni.', 'roanga-partner'));
        }
        
        $partner = $this->get_current_partner();
        if (!$partner) {
            wp_send_json_error(__('Nejste registrovaným partnerem.', 'roanga-partner'));
        }
        
        $data = $this->load_payout_data($partner);
        wp_send_json_success($data);
    }
    
    /**
     * Handle invoice file upload
     */
    private function handle_invoice_upload($file, $partner_id) {
        // Check file type
        if ($file['type'] !== 'application/pdf') {
            return new WP_Error('invalid_file_type', __('Pouze PDF soubory jsou povoleny.', 'roanga-partner'));
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Soubor je příliš velký. Maximum je 5MB.', 'roanga-partner'));
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $rpp_dir = $upload_dir['basedir'] . '/rpp-invoices';
        
        if (!file_exists($rpp_dir)) {
            wp_mkdir_p($rpp_dir);
        }
        
        // Generate unique filename
        $filename = 'invoice_partner_' . $partner_id . '_' . time() . '.pdf';
        $file_path = $rpp_dir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return $upload_dir['baseurl'] . '/rpp-invoices/' . $filename;
        } else {
            return new WP_Error('upload_failed', __('Chyba při nahrávání souboru.', 'roanga-partner'));
        }
    }
    
    /**
     * Send payout request notification to admin
     */
    private function send_payout_request_notification($partner, $amount, $invoice_url) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('Nová žádost o výplatu - %s', 'roanga-partner'), get_bloginfo('name'));
        
        $bank_account = get_user_meta($partner->user_id, 'rpp_bank_account', true);
        $bank_name = get_user_meta($partner->user_id, 'rpp_bank_name', true);
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $subject . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; }
                .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d4af37; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: #2d5a27; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>💰 Nová žádost o výplatu</h1>
                </div>
                
                <div class="content">
                    <p>Byla přijata nová žádost o výplatu od partnera.</p>
                    
                    <div class="info-box">
                        <h3>📋 Informace o žádosti:</h3>
                        <ul>
                            <li><strong>Partner:</strong> ' . esc_html($partner->display_name) . '</li>
                            <li><strong>Email:</strong> ' . esc_html($partner->user_email) . '</li>
                            <li><strong>Partnerský kód:</strong> ' . esc_html($partner->partner_code) . '</li>
                            <li><strong>Požadovaná částka:</strong> ' . wc_price($amount) . '</li>
                            <li><strong>Číslo účtu:</strong> ' . esc_html($bank_account) . '</li>';
                            
        if ($bank_name) {
            $message .= '<li><strong>Banka:</strong> ' . esc_html($bank_name) . '</li>';
        }
        
        if ($invoice_url) {
            $message .= '<li><strong>Faktura:</strong> <a href="' . esc_url($invoice_url) . '">Stáhnout PDF</a></li>';
        }
        
        $message .= '
                        </ul>
                    </div>
                    
                    <p style="text-align: center;">
                        <a href="' . admin_url('admin.php?page=rpp-payouts') . '" class="button">📝 Zpracovat výplatu</a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>S pozdravem,<br>Systém ' . get_bloginfo('name') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
}