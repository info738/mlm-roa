<?php
/**
 * Plugin activation class
 */
class RPP_Activator {
    
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Partners table
        $table_partners = $wpdb->prefix . 'rpp_partners';
        $sql_partners = "CREATE TABLE $table_partners (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            partner_code varchar(50) NOT NULL,
            group_id mediumint(9) DEFAULT 1,
            status varchar(20) DEFAULT 'pending',
            commission_rate decimal(5,2) DEFAULT 10.00,
            total_earnings decimal(10,2) DEFAULT 0.00,
            total_referrals int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY partner_code (partner_code),
            KEY user_id (user_id),
            KEY group_id (group_id)
        ) $charset_collate;";
        
        // Partner Groups table
        $table_groups = $wpdb->prefix . 'rpp_partner_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            commission_rate decimal(5,2) NOT NULL,
            volume_based tinyint(1) DEFAULT 0,
            volume_percentage decimal(5,2) DEFAULT 0,
            disable_mlm tinyint(1) DEFAULT 0,
            bonus_thresholds text,
            restart_period varchar(20) DEFAULT 'monthly',
            benefits text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Commissions table
        $table_commissions = $wpdb->prefix . 'rpp_commissions';
        $sql_commissions = "CREATE TABLE $table_commissions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            partner_id mediumint(9) NOT NULL,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            type varchar(50) DEFAULT 'sale',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // Tracking table
        $table_tracking = $wpdb->prefix . 'rpp_tracking';
        $sql_tracking = "CREATE TABLE $table_tracking (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            partner_id mediumint(9) NOT NULL,
            visitor_ip varchar(45) NOT NULL,
            user_agent text,
            referrer_url text,
            landing_page text,
            conversion_type varchar(50) DEFAULT 'click',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // MLM Structure table
        $table_mlm = $wpdb->prefix . 'rpp_mlm_structure';
        $sql_mlm = "CREATE TABLE $table_mlm (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            partner_id mediumint(9) NOT NULL,
            sponsor_id mediumint(9) DEFAULT NULL,
            level int(11) DEFAULT 1,
            path text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY sponsor_id (sponsor_id),
            KEY level (level)
        ) $charset_collate;";
        
        // Payouts table
        $table_payouts = $wpdb->prefix . 'rpp_payouts';
        $sql_payouts = "CREATE TABLE $table_payouts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            partner_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'requested',
            payout_method varchar(50) DEFAULT 'manual',
            transaction_id varchar(100) DEFAULT NULL,
            invoice_url text DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Volume bonuses table
        $table_volume_bonuses = $wpdb->prefix . 'rpp_volume_bonuses';
        $sql_volume_bonuses = "CREATE TABLE $table_volume_bonuses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            partner_id mediumint(9) NOT NULL,
            group_id mediumint(9) NOT NULL,
            bonus_threshold_id mediumint(9) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            total_volume decimal(10,2) NOT NULL,
            bonus_amount decimal(10,2) NOT NULL,
            restart_period varchar(20) DEFAULT 'monthly',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY partner_id (partner_id),
            KEY group_id (group_id),
            KEY bonus_threshold_id (bonus_threshold_id),
            KEY period_start (period_start)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_groups);
        dbDelta($sql_partners);
        dbDelta($sql_commissions);
        dbDelta($sql_tracking);
        dbDelta($sql_mlm);
        dbDelta($sql_payouts);
        dbDelta($sql_volume_bonuses);
        
        // Create default partner group
        // Create default partner group only if it doesn't exist
        $existing_group = $wpdb->get_var("SELECT COUNT(*) FROM $table_groups WHERE id = 1");
        if ($existing_group == 0) {
            $wpdb->insert(
                $table_groups,
                array(
                    'name' => 'Standardní partneři',
                    'description' => 'Výchozí skupina partnerů se standardními provizními sazbami',
                    'commission_rate' => 10.00,
                    'benefits' => serialize(array('Standardní provizní sazba', 'Měsíční reporty', 'Email podpora'))
                )
            );
        }
    }
    
    private static function create_pages() {
        // Pouze vytvoř stránky pokud ještě neexistují
        
        // Partner Registration Page
        if (!get_option('rpp_registration_page_id') || !get_post(get_option('rpp_registration_page_id'))) {
            $registration_page = array(
                'post_title' => 'Registrace partnera',
                'post_content' => '[rpp_partner_registration]',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'page',
                'post_name' => 'registrace-partnera'
            );
            
            $registration_id = wp_insert_post($registration_page);
            update_option('rpp_registration_page_id', $registration_id);
        }
        
        // Partner Dashboard Page
        if (!get_option('rpp_dashboard_page_id') || !get_post(get_option('rpp_dashboard_page_id'))) {
            $dashboard_page = array(
                'post_title' => 'Partnerský dashboard',
                'post_content' => '[rpp_partner_dashboard]',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'page',
                'post_name' => 'partnersky-dashboard'
            );
            
            $dashboard_id = wp_insert_post($dashboard_page);
            update_option('rpp_dashboard_page_id', $dashboard_id);
        }
        
        // Partner Login Page
        if (!get_option('rpp_login_page_id') || !get_post(get_option('rpp_login_page_id'))) {
            $login_page = array(
                'post_title' => 'Přihlášení partnera',
                'post_content' => '[rpp_partner_login]',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'page',
                'post_name' => 'prihlaseni-partnera'
            );
            
            $login_id = wp_insert_post($login_page);
            update_option('rpp_login_page_id', $login_id);
        }
    }
    
    private static function set_default_options() {
        add_option('rpp_default_commission_rate', 10);
        add_option('rpp_cookie_duration', 30);
        add_option('rpp_minimum_payout', 50);
        add_option('rpp_auto_approve', false);
        add_option('rpp_email_notifications', true);
        add_option('rpp_mlm_enabled', false);
        add_option('rpp_mlm_levels', array(1 => 10, 2 => 5, 3 => 3, 4 => 2, 5 => 1));
        add_option('rpp_mlm_max_levels', 5);
        add_option('rpp_mlm_require_sponsor', false);
    }
}