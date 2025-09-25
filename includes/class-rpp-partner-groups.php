<?php
/**
 * Partner Groups management class
 */
class RPP_Partner_Groups {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rpp_partner_groups';
    }
    
    /**
     * Create a new partner group
     */
    public function create_group($name, $description, $commission_rate, $benefits = array(), $volume_based = false, $volume_percentage = 0, $disable_mlm = false, $bonus_thresholds = array(), $restart_period = 'monthly') {
        global $wpdb;
        
        $data = array(
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description),
            'commission_rate' => floatval($commission_rate),
            'volume_based' => $volume_based ? 1 : 0,
            'volume_percentage' => floatval($volume_percentage),
            'disable_mlm' => $disable_mlm ? 1 : 0,
            'bonus_thresholds' => maybe_serialize($bonus_thresholds),
            'restart_period' => sanitize_text_field($restart_period),
            'benefits' => maybe_serialize($benefits),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get all partner groups
     */
    public function get_all_groups() {
        global $wpdb;
        
        $groups = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY commission_rate DESC");
        
        foreach ($groups as $group) {
            $group->benefits = unserialize($group->benefits) ?: array();
            $group->bonus_thresholds = unserialize($group->bonus_thresholds) ?: array();
            $group->partner_count = $this->get_group_partner_count($group->id);
        }
        
        return $groups;
    }
    
    /**
     * Get group by ID
     */
    public function get_group($group_id) {
        global $wpdb;
        
        $group = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $group_id)
        );
        
        if ($group) {
            $group->benefits = maybe_unserialize($group->benefits) ?: array();
            $group->bonus_thresholds = maybe_unserialize($group->bonus_thresholds) ?: array();
        }
        
        return $group;
    }
    
    /**
     * Update partner group
     */
    public function update_group($group_id, $data) {
        global $wpdb;
        
        // Handle serialization properly
        if (isset($data['benefits'])) {
            $data['benefits'] = serialize($data['benefits']);
        }
        
        if (isset($data['bonus_thresholds'])) {
            $data['bonus_thresholds'] = serialize($data['bonus_thresholds']);
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $group_id),
            null,
            array('%d')
        );
        
        // Log the result for debugging
        error_log('RPP: Group update result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
        if ($result === false) {
            error_log('RPP: MySQL error: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete partner group
     */
    public function delete_group($group_id) {
        global $wpdb;
        
        // First, move all partners from this group to default group
        $wpdb->update(
            $wpdb->prefix . 'rpp_partners',
            array('group_id' => 1), // Default group
            array('group_id' => $group_id),
            array('%d'),
            array('%d')
        );
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $group_id),
            array('%d')
        );
    }
    
    /**
     * Get partner count for group
     */
    public function get_group_partner_count($group_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_partners WHERE group_id = %d",
                $group_id
            )
        );
    }
    
    /**
     * Assign partner to group
     */
    public function assign_partner_to_group($partner_id, $group_id) {
        global $wpdb;
        
        $group = $this->get_group($group_id);
        if (!$group) {
            return false;
        }
        
        return $wpdb->update(
            $wpdb->prefix . 'rpp_partners',
            array(
                'group_id' => $group_id,
                'commission_rate' => $group->commission_rate
            ),
            array('id' => $partner_id),
            array('%d', '%f'),
            array('%d')
        );
    }
    
    /**
     * Get group performance statistics
     */
    public function get_group_performance($group_id) {
        global $wpdb;
        
        $stats = array();
        
        // Get partners in group
        $partner_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rpp_partners WHERE group_id = %d",
                $group_id
            )
        );
        
        if (empty($partner_ids)) {
            return array(
                'total_earnings' => 0,
                'total_clicks' => 0,
                'total_conversions' => 0,
                'avg_conversion_rate' => 0
            );
        }
        
        $partner_ids_str = implode(',', array_map('intval', $partner_ids));
        
        // Total earnings
        $stats['total_earnings'] = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$wpdb->prefix}rpp_commissions 
             WHERE partner_id IN ($partner_ids_str) AND status IN ('approved', 'paid')"
        ) ?: 0;
        
        // Total clicks
        $stats['total_clicks'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking 
             WHERE partner_id IN ($partner_ids_str) AND conversion_type = 'click'"
        ) ?: 0;
        
        // Total conversions
        $stats['total_conversions'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking 
             WHERE partner_id IN ($partner_ids_str) AND conversion_type != 'click'"
        ) ?: 0;
        
        // Average conversion rate
        $stats['avg_conversion_rate'] = $stats['total_clicks'] > 0 
            ? round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2) 
            : 0;
        
        return $stats;
    }
}