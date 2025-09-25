<?php
/**
 * Partner management class
 */
class RPP_Partner {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rpp_partners';
    }
    
    /**
     * Create a new partner
     */
    public function create_partner($user_id, $application_data = array()) {
        global $wpdb;
        
        $partner_code = $this->generate_partner_code();
        
        $data = array(
            'user_id' => $user_id,
            'partner_code' => $partner_code,
            'status' => get_option('rpp_auto_approve', false) ? 'approved' : 'pending',
            'commission_rate' => get_option('rpp_default_commission_rate', 10),
            'created_at' => current_time('mysql'),
            'approved_at' => get_option('rpp_auto_approve', false) ? current_time('mysql') : null
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result) {
            $partner_id = $wpdb->insert_id;
            
            // Store additional application data as meta
            if (!empty($application_data)) {
                foreach ($application_data as $key => $value) {
                    update_user_meta($user_id, 'rpp_' . $key, sanitize_text_field($value));
                }
            }
            
            // Send notification emails
            $this->send_application_emails($partner_id, $user_id);
            
            return $partner_id;
        }
        
        return false;
    }
    
    /**
     * Get partner by user ID
     */
    public function get_partner_by_user($user_id) {
        global $wpdb;
        
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, g.name as group_name 
                 FROM {$this->table_name} p 
                 LEFT JOIN {$wpdb->prefix}rpp_partner_groups g ON p.group_id = g.id 
                 WHERE p.user_id = %d",
                $user_id
            )
        );
        
        return $partner;
    }
    
    /**
     * Get partner detail with user info
     */
    public function get_partner_detail($partner_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email, g.name as group_name
                 FROM {$this->table_name} p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 LEFT JOIN {$wpdb->prefix}rpp_partner_groups g ON p.group_id = g.id
                 WHERE p.id = %d",
                $partner_id
            )
        );
    }
    
    /**
     * Get partner by partner code
     */
    public function get_partner_by_code($partner_code) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email 
                 FROM {$this->table_name} p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 WHERE p.partner_code = %s",
                $partner_code
            )
        );
    }
    
    /**
     * Update partner status
     */
    public function update_partner_status($partner_id, $status) {
        global $wpdb;
        
        $data = array('status' => $status);
        
        if ($status === 'approved') {
            $data['approved_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $partner_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Send status update email
            $this->send_status_update_email($partner_id, $status);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get partner statistics
     */
    public function get_partner_stats($partner_id) {
        global $wpdb;
        
        error_log('RPP Partner Stats: Getting stats for partner ID: ' . $partner_id);
        
        $stats = array();
        
        // Get total clicks
        $stats['total_clicks'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking WHERE partner_id = %d",
                $partner_id
            )
        ) ?: 0;
        
        // Get total earnings from rpp_commissions table (ALL statuses including pending)
        $stats['total_earnings'] = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}rpp_commissions WHERE partner_id = %d",
                $partner_id
            )
        ) ?: 0;
        
        // Get total payouts from rpp_payouts table (completed only)
        $stats['total_payouts'] = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}rpp_payouts WHERE partner_id = %d AND status = 'completed'",
                $partner_id
            )
        ) ?: 0;
        
        // Calculate available balance (total earnings - total payouts)
        $stats['available_balance'] = $stats['total_earnings'] - $stats['total_payouts'];
        
        // Get approved but not paid commissions
        $stats['approved_commissions'] = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}rpp_commissions WHERE partner_id = %d AND status = 'approved'",
                $partner_id
            )
        ) ?: 0;
        
        // Get pending commissions (not yet approved)
        $stats['pending_commissions'] = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}rpp_commissions WHERE partner_id = %d AND status = 'pending'",
                $partner_id
            )
        ) ?: 0;
        
        // Get conversions count
        $stats['total_conversions'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking WHERE partner_id = %d AND conversion_type != 'click'",
                $partner_id
            )
        ) ?: 0;
        
        // Calculate conversion rate
        $stats['conversion_rate'] = $stats['total_clicks'] > 0 
            ? round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2) 
            : 0;
        
        // For backward compatibility
        $stats['total_commissions'] = $stats['total_earnings'];
        $stats['paid_commissions'] = $stats['total_payouts'];
        
        error_log('RPP Partner Stats Results:');
        error_log('- Total earnings: ' . $stats['total_earnings']);
        error_log('- Total payouts: ' . $stats['total_payouts']);
        error_log('- Available balance: ' . $stats['available_balance']);
        error_log('- Approved commissions: ' . $stats['approved_commissions']);
        error_log('- Pending commissions: ' . $stats['pending_commissions']);
        error_log('- Total clicks: ' . $stats['total_clicks']);
        error_log('- Total conversions: ' . $stats['total_conversions']);
        error_log('- Conversion rate: ' . $stats['conversion_rate'] . '%');
        
        return $stats;
    }
    
    /**
     * Generate unique partner code
     */
    private function generate_partner_code() {
        global $wpdb;
        
        do {
            $code = 'PARTNER' . wp_rand(10000, 99999);
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE partner_code = %s",
                    $code
                )
            );
        } while ($exists > 0);
        
        return $code;
    }
    
    /**
     * Send application notification emails
     */
    private function send_application_emails($partner_id, $user_id) {
        if (!get_option('rpp_email_notifications', true)) {
            return;
        }
        
        $user = get_user_by('ID', $user_id);
        $admin_email = get_option('admin_email');
        
        // Email to admin
        $admin_subject = __('New Partner Application', 'roanga-partner');
        $admin_message = sprintf(
            __('A new partner application has been submitted by %s (%s). Please review it in the admin panel.', 'roanga-partner'),
            $user->display_name,
            $user->user_email
        );
        wp_mail($admin_email, $admin_subject, $admin_message);
        
        // Email to applicant
        $user_subject = __('Partner Application Received', 'roanga-partner');
        $user_message = sprintf(
            __('Thank you for applying to become a partner, %s! We have received your application and will review it shortly.', 'roanga-partner'),
            $user->display_name
        );
        wp_mail($user->user_email, $user_subject, $user_message);
    }
    
    /**
     * Send status update email
     */
    private function send_status_update_email($partner_id, $status) {
        if (!get_option('rpp_email_notifications', true)) {
            return;
        }
        
        global $wpdb;
        
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $partner_id
            )
        );
        
        if (!$partner) return;
        
        $user = get_user_by('ID', $partner->user_id);
        
        if ($status === 'approved') {
            $subject = __('Partner Application Approved!', 'roanga-partner');
            $message = sprintf(
                __('Congratulations %s! Your partner application has been approved. Your partner code is: %s', 'roanga-partner'),
                $user->display_name,
                $partner->partner_code
            );
        } elseif ($status === 'rejected') {
            $subject = __('Partner Application Status', 'roanga-partner');
            $message = sprintf(
                __('Hello %s, unfortunately we cannot approve your partner application at this time.', 'roanga-partner'),
                $user->display_name
            );
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
}