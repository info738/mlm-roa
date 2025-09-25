<?php
/**
 * Plugin deactivation class
 */
class RPP_Deactivator {
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('rpp_daily_cleanup');
        wp_clear_scheduled_hook('rpp_commission_calculation');
    }
}