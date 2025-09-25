<?php
/**
 * Database operations class
 */
class RPP_Database {
    
    /**
     * Get all partners with pagination
     */
    public static function get_partners($status = null, $limit = 20, $offset = 0, $search = '', $group_id = null) {
        global $wpdb;
        
        $partners_table = $wpdb->prefix . 'rpp_partners';
        $users_table = $wpdb->users;
        
        $where_conditions = array();
        $where_values = array();
        
        if ($status) {
            $where_conditions[] = "p.status = %s";
            $where_values[] = $status;
        }
        
        if ($group_id) {
            $where_conditions[] = "p.group_id = %d";
            $where_values[] = $group_id;
        }
        
        if ($search) {
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR p.partner_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "
            SELECT p.*, u.display_name, u.user_email
            FROM {$partners_table} p
            LEFT JOIN {$users_table} u ON p.user_id = u.ID
            {$where_clause}
            ORDER BY p.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get partners count
     */
    public static function get_partners_count($status = null, $search = '', $group_id = null) {
        global $wpdb;
        
        $partners_table = $wpdb->prefix . 'rpp_partners';
        $users_table = $wpdb->users;
        
        $where_conditions = array();
        $where_values = array();
        
        if ($status) {
            $where_conditions[] = "p.status = %s";
            $where_values[] = $status;
        }
        
        if ($group_id) {
            $where_conditions[] = "p.group_id = %d";
            $where_values[] = $group_id;
        }
        
        if ($search) {
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR p.partner_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "
            SELECT COUNT(*)
            FROM {$partners_table} p
            LEFT JOIN {$users_table} u ON p.user_id = u.ID
            {$where_clause}
        ";
        
        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($query, $where_values));
        } else {
            return $wpdb->get_var($query);
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public static function get_dashboard_stats() {
        global $wpdb;
        
        $partners_table = $wpdb->prefix . 'rpp_partners';
        $commissions_table = $wpdb->prefix . 'rpp_commissions';
        $tracking_table = $wpdb->prefix . 'rpp_tracking';
        
        $stats = array();
        
        // Partner statistics
        $stats['total_partners'] = $wpdb->get_var("SELECT COUNT(*) FROM {$partners_table}");
        $stats['active_partners'] = $wpdb->get_var("SELECT COUNT(*) FROM {$partners_table} WHERE status = 'approved'");
        $stats['pending_partners'] = $wpdb->get_var("SELECT COUNT(*) FROM {$partners_table} WHERE status = 'pending'");
        
        // Commission statistics
        $stats['total_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$commissions_table}");
        $stats['paid_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$commissions_table} WHERE status = 'paid'");
        $stats['pending_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM {$commissions_table} WHERE status = 'pending'");
        
        // Tracking statistics
        $stats['total_clicks'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tracking_table} WHERE conversion_type = 'click'");
        $stats['total_conversions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tracking_table} WHERE conversion_type != 'click'");
        
        // This month statistics
        $current_month = date('Y-m-01');
        $stats['this_month_commissions'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$commissions_table} WHERE created_at >= %s",
                $current_month
            )
        );
        
        $stats['this_month_clicks'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tracking_table} WHERE created_at >= %s AND conversion_type = 'click'",
                $current_month
            )
        );
        
        return $stats;
    }
    
    /**
     * Get recent activities
     */
    public static function get_recent_activities($limit = 10) {
        global $wpdb;
        
        $partners_table = $wpdb->prefix . 'rpp_partners';
        $commissions_table = $wpdb->prefix . 'rpp_commissions';
        $users_table = $wpdb->users;
        
        $activities = array();
        
        // Recent partner registrations
        $recent_partners = $wpdb->get_results(
            $wpdb->prepare("
                SELECT p.*, u.display_name, u.user_email, 'partner_registered' as activity_type
                FROM {$partners_table} p
                LEFT JOIN {$users_table} u ON p.user_id = u.ID
                ORDER BY p.created_at DESC
                LIMIT %d
            ", $limit)
        );
        
        // Recent commissions
        $recent_commissions = $wpdb->get_results(
            $wpdb->prepare("
                SELECT c.*, p.partner_code, u.display_name, u.user_email, 'commission_created' as activity_type
                FROM {$commissions_table} c
                LEFT JOIN {$partners_table} p ON c.partner_id = p.id
                LEFT JOIN {$users_table} u ON p.user_id = u.ID
                ORDER BY c.created_at DESC
                LIMIT %d
            ", $limit)
        );
        
        // Merge and sort by date
        $all_activities = array_merge($recent_partners, $recent_commissions);
        usort($all_activities, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        return array_slice($all_activities, 0, $limit);
    }
    
    /**
     * Get top performing partners
     */
    public static function get_top_partners($limit = 10) {
        global $wpdb;
        
        $partners_table = $wpdb->prefix . 'rpp_partners';
        $users_table = $wpdb->users;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT p.*, u.display_name, u.user_email
                FROM {$partners_table} p
                LEFT JOIN {$users_table} u ON p.user_id = u.ID
                WHERE p.status = 'approved'
                ORDER BY p.total_earnings DESC, p.total_referrals DESC
                LIMIT %d
            ", $limit)
        );
    }
}