<?php
/**
 * Admin functionality class
 */
class RPP_Admin {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, RPP_PLUGIN_URL . 'admin/css/rpp-admin.css', array(), $this->version, 'all');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, RPP_PLUGIN_URL . 'admin/js/rpp-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'rpp_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rpp_admin_nonce')
        ));
    }
    
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('Partner Program', 'roanga-partner'),
            __('Partners', 'roanga-partner'),
            'manage_options',
            'rpp-partners',
            array($this, 'display_partners_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'rpp-partners',
            __('All Partners', 'roanga-partner'),
            __('All Partners', 'roanga-partner'),
            'manage_options',
            'rpp-partners',
            array($this, 'display_partners_page')
        );
        
        // Add partner detail page (hidden from menu)
        add_submenu_page(
            null, // Hidden from menu
            __('Partner Details', 'roanga-partner'),
            __('Partner Details', 'roanga-partner'),
            'manage_options',
            'rpp-partner-detail',
            array($this, 'display_partner_detail_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('Partner Groups', 'roanga-partner'),
            __('Partner Groups', 'roanga-partner'),
            'manage_options',
            'rpp-groups',
            array($this, 'display_groups_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('MLM Struktura', 'roanga-partner'),
            __('MLM Struktura', 'roanga-partner'),
            'manage_options',
            'rpp-mlm-structure',
            array($this, 'display_mlm_structure_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('Výplaty', 'roanga-partner'),
            __('Výplaty', 'roanga-partner'),
            'manage_options',
            'rpp-payouts',
            array($this, 'display_payouts_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('Commissions', 'roanga-partner'),
            __('Commissions', 'roanga-partner'),
            'manage_options',
            'rpp-commissions',
            array($this, 'display_commissions_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('Tracking', 'roanga-partner'),
            __('Tracking', 'roanga-partner'),
            'manage_options',
            'rpp-tracking',
            array($this, 'display_tracking_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('MLM Nastavení', 'roanga-partner'),
            __('MLM Nastavení', 'roanga-partner'),
            'manage_options',
            'rpp-mlm-settings',
            array($this, 'display_mlm_settings_page')
        );
        
        add_submenu_page(
            'rpp-partners',
            __('Settings', 'roanga-partner'),
            __('Settings', 'roanga-partner'),
            'manage_options',
            'rpp-settings',
            array($this, 'display_settings_page')
        );
    }
    
    public function display_partners_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $group_filter = isset($_GET['group']) ? intval($_GET['group']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $partners = RPP_Database::get_partners($status_filter, $per_page, $offset, $search, $group_filter);
        $total_partners = RPP_Database::get_partners_count($status_filter, $search, $group_filter);
        $total_pages = ceil($total_partners / $per_page);
        
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-partners.php';
    }
    
    public function display_groups_page() {
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-groups.php';
    }
    
    public function display_commissions_page() {
        $commission_class = new RPP_Commission();
        $commissions = $commission_class->get_commission_summary();
        
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-commissions.php';
    }
    
    public function display_tracking_page() {
        $stats = RPP_Database::get_dashboard_stats();
        
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-tracking.php';
    }
    
    public function display_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-settings.php';
    }
    
    public function display_partner_detail_page() {
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-partner-detail.php';
    }
    
    public function display_mlm_settings_page() {
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-mlm-settings.php';
    }
    
    public function display_mlm_structure_page() {
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-mlm-structure.php';
    }
    
    public function display_payouts_page() {
        include_once RPP_PLUGIN_PATH . 'admin/partials/rpp-admin-payouts.php';
    }
    
    public function approve_partner() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $partner_class = new RPP_Partner();
        
        if ($partner_class->update_partner_status($partner_id, 'approved')) {
            // Send email notification
            $email_class = new RPP_Email_Notifications();
            $email_class->send_status_update($partner_id, 'approved');
            
            wp_send_json_success(__('Partner approved successfully.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to approve partner.', 'roanga-partner'));
        }
    }
    
    public function reject_partner() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $partner_class = new RPP_Partner();
        
        if ($partner_class->update_partner_status($partner_id, 'rejected')) {
            // Send email notification
            $email_class = new RPP_Email_Notifications();
            $email_class->send_status_update($partner_id, 'rejected');
            
            wp_send_json_success(__('Partner rejected.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to reject partner.', 'roanga-partner'));
        }
    }
    
    public function update_partner_group() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $group_id = intval($_POST['group_id']);
        
        $groups_class = new RPP_Partner_Groups();
        
        if ($groups_class->assign_partner_to_group($partner_id, $group_id)) {
            wp_send_json_success(__('Partner group updated successfully.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to update partner group.', 'roanga-partner'));
        }
    }
    
    public function update_commission_rate() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $commission_rate = floatval($_POST['commission_rate']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'rpp_partners',
            array('commission_rate' => $commission_rate),
            array('id' => $partner_id),
            array('%f'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Commission rate updated successfully.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to update commission rate.', 'roanga-partner'));
        }
    }
    
    public function send_test_email() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $email_class = new RPP_Email_Notifications();
        
        if ($email_class->send_monthly_report($partner_id)) {
            wp_send_json_success(__('Test email sent successfully.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Failed to send test email.', 'roanga-partner'));
        }
    }
    
    // Group management AJAX handlers
    public function create_group() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $commission_rate = floatval($_POST['commission_rate']);
        $volume_based = isset($_POST['volume_based']) ? 1 : 0;
        $volume_percentage = floatval($_POST['volume_percentage']);
        $disable_mlm = isset($_POST['disable_mlm']) ? 1 : 0;
        $restart_period = sanitize_text_field($_POST['restart_period']);
        
        // Process benefits
        $benefits = array();
        if (isset($_POST['benefits']) && is_array($_POST['benefits'])) {
            foreach ($_POST['benefits'] as $benefit) {
                $benefit = trim(sanitize_text_field($benefit));
                if (!empty($benefit)) {
                    $benefits[] = $benefit;
                }
            }
        }
        
        // Process bonus thresholds
        $bonus_thresholds = array();
        if (isset($_POST['bonus_turnover']) && is_array($_POST['bonus_turnover'])) {
            for ($i = 0; $i < count($_POST['bonus_turnover']); $i++) {
                $turnover = floatval($_POST['bonus_turnover'][$i]);
                $amount = floatval($_POST['bonus_amount'][$i]);
                $restart = sanitize_text_field($_POST['bonus_restart'][$i]);
                
                if ($turnover > 0 && $amount > 0) {
                    $bonus_thresholds[] = array(
                        'turnover' => $turnover,
                        'amount' => $amount,
                        'restart' => $restart
                    );
                }
            }
        }
        
        $groups_class = new RPP_Partner_Groups();
        $group_id = $groups_class->create_group(
            $name, 
            $description, 
            $commission_rate, 
            $benefits, 
            $volume_based, 
            $volume_percentage, 
            $disable_mlm, 
            $bonus_thresholds, 
            $restart_period
        );
        
        if ($group_id) {
            wp_send_json_success(__('Skupina byla úspěšně vytvořena.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při vytváření skupiny.', 'roanga-partner'));
        }
    }
    
    public function update_group() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $group_id = intval($_POST['group_id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $commission_rate = floatval($_POST['commission_rate']);
        $volume_based = isset($_POST['volume_based']) ? 1 : 0;
        $volume_percentage = floatval($_POST['volume_percentage']);
        $disable_mlm = isset($_POST['disable_mlm']) ? 1 : 0;
        $restart_period = sanitize_text_field($_POST['restart_period']);
        
        // Process benefits
        $benefits = array();
        if (isset($_POST['benefits']) && is_array($_POST['benefits'])) {
            foreach ($_POST['benefits'] as $benefit) {
                $benefit = trim(sanitize_text_field($benefit));
                if (!empty($benefit)) {
                    $benefits[] = $benefit;
                }
            }
        }
        
        // Process bonus thresholds
        $bonus_thresholds = array();
        if (isset($_POST['bonus_turnover']) && is_array($_POST['bonus_turnover'])) {
            for ($i = 0; $i < count($_POST['bonus_turnover']); $i++) {
                $turnover = floatval($_POST['bonus_turnover'][$i]);
                $amount = floatval($_POST['bonus_amount'][$i]);
                $restart = sanitize_text_field($_POST['bonus_restart'][$i]);
                
                if ($turnover > 0 && $amount > 0) {
                    $bonus_thresholds[] = array(
                        'turnover' => $turnover,
                        'amount' => $amount,
                        'restart' => $restart
                    );
                }
            }
        }
        
        $data = array(
            'name' => $name,
            'description' => $description,
            'commission_rate' => $commission_rate,
            'volume_based' => $volume_based,
            'volume_percentage' => $volume_percentage,
            'disable_mlm' => $disable_mlm,
            'restart_period' => $restart_period,
            'benefits' => $benefits,
            'bonus_thresholds' => $bonus_thresholds
        );
        
        $groups_class = new RPP_Partner_Groups();
        
        if ($groups_class->update_group($group_id, $data)) {
            wp_send_json_success(__('Skupina byla úspěšně aktualizována.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při aktualizaci skupiny.', 'roanga-partner'));
        }
    }
    
    public function delete_group() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $group_id = intval($_POST['group_id']);
        
        if ($group_id == 1) {
            wp_send_json_error(__('Výchozí skupinu nelze smazat.', 'roanga-partner'));
        }
        
        $groups_class = new RPP_Partner_Groups();
        
        if ($groups_class->delete_group($group_id)) {
            wp_send_json_success(__('Skupina byla úspěšně smazána.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při mazání skupiny.', 'roanga-partner'));
        }
    }
    
    public function get_group() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $group_id = intval($_POST['group_id']);
        $groups_class = new RPP_Partner_Groups();
        $group = $groups_class->get_group($group_id);
        
        if ($group) {
            wp_send_json_success($group);
        } else {
            wp_send_json_error(__('Skupina nebyla nalezena.', 'roanga-partner'));
        }
    }
    
    public function get_mlm_tree() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $mlm_class = new RPP_MLM_Structure();
        $downline = $mlm_class->get_partner_downline($partner_id, 3);
        
        if (empty($downline)) {
            wp_send_json_success('<div style="text-align: center; padding: 40px; color: #666;"><h4>Žádní partneři v týmu</h4><p>Tento partner zatím nemá žádné referály.</p></div>');
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
                $html .= '<div class="rpp-node-earnings">' . wc_price($member->total_earnings) . '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    public function get_full_mlm_tree() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $mlm_class = new RPP_MLM_Structure();
        $tree_html = $mlm_class->get_full_tree_html();
        
        wp_send_json_success($tree_html);
    }
    
    public function move_partner() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $partner_id = intval($_POST['partner_id']);
        $new_sponsor_code = sanitize_text_field($_POST['new_sponsor_code']);
        
        $mlm_class = new RPP_MLM_Structure();
        
        if ($mlm_class->move_partner($partner_id, $new_sponsor_code)) {
            wp_send_json_success(__('Partner byl úspěšně přesunut.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při přesunu partnera.', 'roanga-partner'));
        }
    }
    
    public function get_mlm_table() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        global $wpdb;
        
        // Get all partners with MLM structure info
        $partners = $wpdb->get_results("
            SELECT p.*, u.display_name, u.user_email, s.sponsor_id, s.level, s.path,
                   g.name as group_name,
                   sponsor_p.partner_code as sponsor_code, sponsor_u.display_name as sponsor_name
            FROM {$wpdb->prefix}rpp_partners p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}rpp_mlm_structure s ON p.id = s.partner_id
            LEFT JOIN {$wpdb->prefix}rpp_partner_groups g ON p.group_id = g.id
            LEFT JOIN {$wpdb->prefix}rpp_partners sponsor_p ON s.sponsor_id = sponsor_p.id
            LEFT JOIN {$wpdb->users} sponsor_u ON sponsor_p.user_id = sponsor_u.ID
            WHERE p.status = 'approved'
            ORDER BY s.level ASC, p.created_at ASC
        ");
        
        if (empty($partners)) {
            wp_send_json_success('<tr><td colspan="10" style="text-align: center; padding: 40px; color: #666;">Žádní schválení partneři v MLM struktuře.</td></tr>');
            return;
        }
        
        $html = '';
        
        foreach ($partners as $partner) {
            // Get direct referrals count
            $direct_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_mlm_structure WHERE sponsor_id = %d",
                    $partner->id
                )
            );
            
            // Get team size
            $team_size = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_mlm_structure WHERE path LIKE %s",
                    '%/' . $partner->id . '/%'
                )
            );
            
            $level = $partner->level ?: 1;
            
            $html .= '<tr>';
            $html .= '<td><span class="rpp-level-indicator rpp-level-' . min($level - 1, 4) . '">' . $level . '</span></td>';
            $html .= '<td><div class="rpp-partner-name">' . esc_html($partner->display_name) . '</div></td>';
            $html .= '<td><span class="rpp-partner-code">' . esc_html($partner->partner_code) . '</span></td>';
            $html .= '<td>' . esc_html($partner->user_email) . '</td>';
            $html .= '<td><span class="rpp-group-badge">' . esc_html($partner->group_name ?: 'Standardní') . '</span></td>';
            $html .= '<td>' . ($partner->sponsor_name ? esc_html($partner->sponsor_name) . ' (' . esc_html($partner->sponsor_code) . ')' : '—') . '</td>';
            $html .= '<td><strong>' . $direct_count . '</strong></td>';
            $html .= '<td><strong>' . $team_size . '</strong></td>';
            $html .= '<td><strong>' . wc_price($partner->total_earnings) . '</strong></td>';
            $html .= '<td>';
            $html .= '<button class="rpp-btn rpp-btn-small rpp-btn-secondary rpp-move-partner" data-partner-id="' . $partner->id . '" data-partner-name="' . esc_attr($partner->display_name) . '">Přesunout</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        wp_send_json_success($html);
    }
    
    // Payout management AJAX handlers
    public function approve_payout_request() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $request_id = intval($_POST['request_id']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'rpp_payouts',
            array(
                'status' => 'approved',
                'paid_at' => current_time('mysql')
            ),
            array('id' => $request_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Žádost o výplatu byla schválena.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při schvalování žádosti.', 'roanga-partner'));
        }
    }
    
    public function reject_payout_request() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $request_id = intval($_POST['request_id']);
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'rpp_payouts',
            array('status' => 'rejected'),
            array('id' => $request_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Žádost o výplatu byla zamítnuta.', 'roanga-partner'));
        } else {
            wp_send_json_error(__('Chyba při zamítání žádosti.', 'roanga-partner'));
        }
    }
    
    public function assign_partner_to_order() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $order_id = intval($_POST['order_id']);
        $partner_code = sanitize_text_field($_POST['partner_code']);
        
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_by_code($partner_code);
        
        if (!$partner || $partner->status !== 'approved') {
            wp_send_json_error(__('Partner nebyl nalezen nebo není schválen.', 'roanga-partner'));
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Objednávka nebyla nalezena.', 'roanga-partner'));
        }
        
        $order->add_meta_data('_rpp_partner_code', $partner->partner_code);
        $order->add_meta_data('_rpp_partner_id', $partner->id);
        $order->add_meta_data('_rpp_partner_name', $partner->display_name);
        $order->save();
        
        wp_send_json_success(__('Partner byl úspěšně přiřazen k objednávce.', 'roanga-partner'));
    }
    
    public function remove_partner_from_order() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $order_id = intval($_POST['order_id']);
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Objednávka nebyla nalezena.', 'roanga-partner'));
        }
        
        $order->delete_meta_data('_rpp_partner_code');
        $order->delete_meta_data('_rpp_partner_id');
        $order->delete_meta_data('_rpp_partner_name');
        $order->save();
        
        wp_send_json_success(__('Partner byl odebrán z objednávky.', 'roanga-partner'));
    }
    
    public function process_commission_now() {
        check_ajax_referer('rpp_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Nemáte oprávnění k této akci.', 'roanga-partner'));
        }
        
        $order_id = intval($_POST['order_id']);
        
        // Process commission manually
        $woo_integration = new RPP_WooCommerce_Integration();
        $woo_integration->process_order_commission($order_id);
        
        wp_send_json_success(__('Provize byly zpracovány.', 'roanga-partner'));
    }
    
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('rpp_settings_nonce');
        
        $settings = array(
            'rpp_default_commission_rate' => floatval($_POST['default_commission_rate']),
            'rpp_cookie_duration' => intval($_POST['cookie_duration']),
            'rpp_minimum_payout' => floatval($_POST['minimum_payout']),
            'rpp_auto_approve' => isset($_POST['auto_approve']),
            'rpp_email_notifications' => isset($_POST['email_notifications'])
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        add_settings_error('rpp_settings', 'settings_updated', __('Settings saved successfully.', 'roanga-partner'), 'updated');
    }
}