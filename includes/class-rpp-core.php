<?php
/**
 * Core plugin class
 */
class RPP_Core {
    
    protected $loader;
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->version = RPP_VERSION;
        $this->plugin_name = 'roanga-partner-program';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-loader.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-i18n.php';
        require_once RPP_PLUGIN_PATH . 'admin/class-rpp-admin.php';
        require_once RPP_PLUGIN_PATH . 'public/class-rpp-public.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-database.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-partner.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-partner-groups.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-email-notifications.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-commission.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-tracking.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-mlm-structure.php';
        require_once RPP_PLUGIN_PATH . 'includes/class-rpp-woocommerce-integration.php';
        
        $this->loader = new RPP_Loader();
    }
    
    private function set_locale() {
        $plugin_i18n = new RPP_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new RPP_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // AJAX handlers for admin
        $this->loader->add_action('wp_ajax_rpp_approve_partner', $plugin_admin, 'approve_partner');
        $this->loader->add_action('wp_ajax_rpp_reject_partner', $plugin_admin, 'reject_partner');
        $this->loader->add_action('wp_ajax_rpp_update_partner_group', $plugin_admin, 'update_partner_group');
        $this->loader->add_action('wp_ajax_rpp_update_commission_rate', $plugin_admin, 'update_commission_rate');
        $this->loader->add_action('wp_ajax_rpp_send_test_email', $plugin_admin, 'send_test_email');
        
        // Group management AJAX handlers
        $this->loader->add_action('wp_ajax_rpp_create_group', $plugin_admin, 'create_group');
        $this->loader->add_action('wp_ajax_rpp_update_group', $plugin_admin, 'update_group');
        $this->loader->add_action('wp_ajax_rpp_delete_group', $plugin_admin, 'delete_group');
        $this->loader->add_action('wp_ajax_rpp_get_group', $plugin_admin, 'get_group');
        $this->loader->add_action('wp_ajax_rpp_get_mlm_tree', $plugin_admin, 'get_mlm_tree');
        $this->loader->add_action('wp_ajax_rpp_get_full_mlm_tree', $plugin_admin, 'get_full_mlm_tree');
        $this->loader->add_action('wp_ajax_rpp_move_partner', $plugin_admin, 'move_partner');
        $this->loader->add_action('wp_ajax_rpp_get_mlm_table', $plugin_admin, 'get_mlm_table');
        
        // Payout management AJAX handlers
        $this->loader->add_action('wp_ajax_rpp_approve_payout_request', $plugin_admin, 'approve_payout_request');
        $this->loader->add_action('wp_ajax_rpp_reject_payout_request', $plugin_admin, 'reject_payout_request');
        
        // Public payout AJAX handlers
        $this->loader->add_action('wp_ajax_rpp_get_payout_data', $plugin_public, 'get_payout_data');
        $this->loader->add_action('wp_ajax_rpp_payout_request', $plugin_public, 'handle_payout_request');
        
        // WooCommerce order management AJAX handlers
        $this->loader->add_action('wp_ajax_rpp_assign_partner_to_order', $plugin_admin, 'assign_partner_to_order');
        $this->loader->add_action('wp_ajax_rpp_remove_partner_from_order', $plugin_admin, 'remove_partner_from_order');
        $this->loader->add_action('wp_ajax_rpp_process_commission_now', $plugin_admin, 'process_commission_now');
    }
    
    private function define_public_hooks() {
        $plugin_public = new RPP_Public($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'init');
        $this->loader->add_action('wp', $plugin_public, 'handle_referral_tracking');
        $this->loader->add_shortcode('rpp_partner_registration', $plugin_public, 'partner_registration_shortcode');
        $this->loader->add_shortcode('rpp_partner_dashboard', $plugin_public, 'partner_dashboard_shortcode');
        $this->loader->add_shortcode('rpp_partner_login', $plugin_public, 'partner_login_shortcode');
        
        // AJAX handlers for public
        $this->loader->add_action('wp_ajax_rpp_partner_registration', $plugin_public, 'handle_partner_registration');
        $this->loader->add_action('wp_ajax_nopriv_rpp_partner_registration', $plugin_public, 'handle_partner_registration');
        $this->loader->add_action('wp_ajax_rpp_payout_request', $plugin_public, 'handle_payout_request');
        $this->loader->add_action('wp_ajax_rpp_get_payout_data', $plugin_public, 'get_payout_data');
        $this->loader->add_action('wp_ajax_rpp_search_partner', $plugin_public, 'search_partner');
        $this->loader->add_action('wp_ajax_nopriv_rpp_search_partner', $plugin_public, 'search_partner');
        
        // Initialize WooCommerce integration if WooCommerce is active
        $this->loader->add_action('plugins_loaded', $this, 'init_woocommerce_integration');
    }
    
    /**
     * Initialize WooCommerce integration after all plugins are loaded
     */
    public function init_woocommerce_integration() {
        if (class_exists('WooCommerce')) {
            error_log('RPP Core: WooCommerce detected, initializing integration');
            $woo_integration = new RPP_WooCommerce_Integration();
            $woo_integration->init_woocommerce_hooks();
        } else {
            error_log('RPP Core: WooCommerce not detected');
        }
    }
    
    public function run() {
        $this->loader->run();
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_loader() {
        return $this->loader;
    }
    
    public function get_version() {
        return $this->version;
    }
}