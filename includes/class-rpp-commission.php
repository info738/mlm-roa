<?php
/**
 * Commission management class
 */
class RPP_Commission {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rpp_commissions';
    }
    
    /**
     * Create a new commission
     */
    public function create_commission($partner_id, $amount, $order_id = null, $type = 'sale') {
        global $wpdb;
        
        $data = array(
            'partner_id' => $partner_id,
            'order_id' => $order_id,
            'amount' => $amount,
            'status' => 'pending',
            'type' => $type,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            // Update partner's total earnings
            $this->update_partner_earnings($partner_id);
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get commissions by partner
     */
    public function get_partner_commissions($partner_id, $status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = $wpdb->prepare("WHERE partner_id = %d", $partner_id);
        
        if ($status) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $limit, $offset)
        );
    }
    
    /**
     * Update commission status
     */
    public function update_commission_status($commission_id, $status) {
        global $wpdb;
        
        $data = array('status' => $status);
        
        if ($status === 'paid') {
            $data['paid_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $commission_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Update partner's total earnings
            $commission = $this->get_commission($commission_id);
            if ($commission) {
                $this->update_partner_earnings($commission->partner_id);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get commission by ID
     */
    public function get_commission($commission_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $commission_id
            )
        );
    }
    
    /**
     * Calculate commission amount
     */
    public function calculate_commission($order_total, $commission_rate) {
        return round(($order_total * $commission_rate) / 100, 2);
    }
    
    /**
     * Update partner's total earnings
     */
    private function update_partner_earnings($partner_id) {
        global $wpdb;
        
        $total_earnings = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$this->table_name} WHERE partner_id = %d AND status IN ('approved', 'paid')",
                $partner_id
            )
        );
        
        $wpdb->update(
            $wpdb->prefix . 'rpp_partners',
            array('total_earnings' => $total_earnings ?: 0),
            array('id' => $partner_id),
            array('%f'),
            array('%d')
        );
    }
    
    /**
     * Get commission summary
     */
    public function get_commission_summary($partner_id = null) {
        global $wpdb;
        
        $where = $partner_id ? $wpdb->prepare("WHERE partner_id = %d", $partner_id) : "";
        
        $query = "
            SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total
            FROM {$this->table_name} 
            {$where}
            GROUP BY status
        ";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Process bulk commission payments
     */
    public function process_bulk_payments($commission_ids) {
        global $wpdb;
        
        $ids = implode(',', array_map('intval', $commission_ids));
        
        $result = $wpdb->query(
            "UPDATE {$this->table_name} 
             SET status = 'paid', paid_at = '" . current_time('mysql') . "' 
             WHERE id IN ($ids) AND status = 'approved'"
        );
        
        if ($result) {
            // Update partner earnings for affected partners
            $partners = $wpdb->get_col(
                "SELECT DISTINCT partner_id FROM {$this->table_name} WHERE id IN ($ids)"
            );
            
            foreach ($partners as $partner_id) {
                $this->update_partner_earnings($partner_id);
            }
        }
        
        return $result;
    }
}