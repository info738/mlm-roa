<?php
/**
 * MLM Structure management class
 */
class RPP_MLM_Structure {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rpp_mlm_structure';
    }
    
    /**
     * Add partner to MLM structure
     */
    public function add_partner_to_structure($partner_id, $sponsor_code = null) {
        global $wpdb;
        
        $sponsor_id = null;
        $level = 1;
        $path = '';
        
        if ($sponsor_code) {
            $sponsor = $this->get_partner_by_code($sponsor_code);
            if ($sponsor) {
                $sponsor_id = $sponsor->id;
                $sponsor_structure = $this->get_partner_structure($sponsor_id);
                if ($sponsor_structure) {
                    $level = $sponsor_structure->level + 1;
                    $path = $sponsor_structure->path . $sponsor_id . '/';
                }
            }
        }
        
        $data = array(
            'partner_id' => $partner_id,
            'sponsor_id' => $sponsor_id,
            'level' => $level,
            'path' => $path,
            'created_at' => current_time('mysql')
        );
        
        return $wpdb->insert($this->table_name, $data);
    }
    
    /**
     * Get partner structure
     */
    public function get_partner_structure($partner_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE partner_id = %d",
                $partner_id
            )
        );
    }
    
    /**
     * Get partner downline
     */
    public function get_partner_downline($partner_id, $levels = null) {
        global $wpdb;
        
        $structure = $this->get_partner_structure($partner_id);
        if (!$structure) return array();
        
        $path_condition = $wpdb->prepare("path LIKE %s", $structure->path . $partner_id . '/%');
        
        if ($levels) {
            $max_level = $structure->level + $levels;
            $level_condition = $wpdb->prepare(" AND level <= %d", $max_level);
        } else {
            $level_condition = '';
        }
        
        $query = "
            SELECT s.*, p.partner_code, u.display_name, u.user_email, p.total_earnings, p.status
            FROM {$this->table_name} s
            LEFT JOIN {$wpdb->prefix}rpp_partners p ON s.partner_id = p.id
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE {$path_condition} {$level_condition}
            ORDER BY s.level, s.created_at
        ";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get direct referrals
     */
    public function get_direct_referrals($partner_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT s.*, p.partner_code, u.display_name, u.user_email, p.total_earnings, p.status
                FROM {$this->table_name} s
                LEFT JOIN {$wpdb->prefix}rpp_partners p ON s.partner_id = p.id
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                WHERE s.sponsor_id = %d
                ORDER BY s.created_at DESC
            ", $partner_id)
        );
    }
    
    /**
     * Calculate MLM commissions
     */
    public function calculate_mlm_commissions($partner_id, $sale_amount) {
        $commissions = array();
        $structure = $this->get_partner_structure($partner_id);
        
        if (!$structure || !$structure->sponsor_id) {
            return $commissions;
        }
        
        // Get MLM settings
        $mlm_levels = get_option('rpp_mlm_levels', array());
        
        $current_sponsor_id = $structure->sponsor_id;
        $level = 1;
        
        while ($current_sponsor_id && $level <= count($mlm_levels)) {
            $sponsor_structure = $this->get_partner_structure($current_sponsor_id);
            if (!$sponsor_structure) break;
            
            $commission_rate = isset($mlm_levels[$level - 1]) ? $mlm_levels[$level - 1] : 0;
            if ($commission_rate > 0) {
                $commission_amount = ($sale_amount * $commission_rate) / 100;
                
                $commissions[] = array(
                    'partner_id' => $current_sponsor_id,
                    'level' => $level,
                    'rate' => $commission_rate,
                    'amount' => $commission_amount,
                    'from_partner_id' => $partner_id
                );
            }
            
            $current_sponsor_id = $sponsor_structure->sponsor_id;
            $level++;
        }
        
        return $commissions;
    }
    
    /**
     * Get partner by code
     */
    private function get_partner_by_code($partner_code) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rpp_partners WHERE partner_code = %s",
                $partner_code
            )
        );
    }
    
    /**
     * Get team statistics
     */
    public function get_team_statistics($partner_id) {
        global $wpdb;
        
        $downline = $this->get_partner_downline($partner_id);
        
        $stats = array(
            'total_team_members' => count($downline),
            'direct_referrals' => 0,
            'team_volume' => 0,
            'levels' => array()
        );
        
        foreach ($downline as $member) {
            $member_level = $member->level - $this->get_partner_structure($partner_id)->level;
            
            if ($member_level == 1) {
                $stats['direct_referrals']++;
            }
            
            if (!isset($stats['levels'][$member_level])) {
                $stats['levels'][$member_level] = 0;
            }
            $stats['levels'][$member_level]++;
            
            $stats['team_volume'] += floatval($member->total_earnings);
        }
        
        return $stats;
    }
    
    /**
     * Get full MLM tree HTML
     */
    public function get_full_tree_html() {
        global $wpdb;
        
        // Get all partners with their structure info
        $partners = $wpdb->get_results("
            SELECT p.*, u.display_name, u.user_email, s.sponsor_id, s.level, s.path
            FROM {$wpdb->prefix}rpp_partners p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            LEFT JOIN {$this->table_name} s ON p.id = s.partner_id
            WHERE p.status = 'approved'
            ORDER BY s.level ASC, p.created_at ASC
        ");
        
        if (empty($partners)) {
            return '<div style="text-align: center; padding: 40px; color: #666;"><h4>Žádní partneři v MLM struktuře</h4><p>Zatím nejsou registrováni žádní schválení partneři.</p></div>';
        }
        
        // Build tree structure
        $tree = array();
        $partner_map = array();
        
        foreach ($partners as $partner) {
            $partner_map[$partner->id] = $partner;
            if (!$partner->sponsor_id) {
                $tree[] = $partner;
            }
        }
        
        // Add children to each partner
        foreach ($partners as $partner) {
            if ($partner->sponsor_id && isset($partner_map[$partner->sponsor_id])) {
                if (!isset($partner_map[$partner->sponsor_id]->children)) {
                    $partner_map[$partner->sponsor_id]->children = array();
                }
                $partner_map[$partner->sponsor_id]->children[] = $partner;
            }
        }
        
        return $this->render_tree_html($tree, 0);
    }
    
    /**
     * Render tree HTML recursively
     */
    private function render_tree_html($partners, $level) {
        $html = '';
        
        foreach ($partners as $partner) {
            $children_count = isset($partner->children) ? count($partner->children) : 0;
            
            $html .= '<div class="rpp-partner-node level-' . $level . '">';
            $html .= '<div class="rpp-partner-header">';
            $html .= '<div class="rpp-partner-info">';
            $html .= '<div class="rpp-partner-name">' . esc_html($partner->display_name) . '</div>';
            $html .= '<span class="rpp-partner-code">' . esc_html($partner->partner_code) . '</span>';
            $html .= '</div>';
            $html .= '<div class="rpp-partner-actions">';
            
            if ($children_count > 0) {
                $html .= '<button class="rpp-toggle-children">[-]</button>';
            }
            
            $html .= '<button class="rpp-move-partner" data-partner-id="' . $partner->id . '" data-partner-name="' . esc_attr($partner->display_name) . '">Přesunout</button>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Stats
            $html .= '<div class="rpp-partner-stats">';
            $html .= '<div class="rpp-stat-item">';
            $html .= '<div class="rpp-stat-value">' . number_format($partner->total_earnings, 0, ',', ' ') . ' Kč</div>';
            $html .= '<div class="rpp-stat-label">Výdělky</div>';
            $html .= '</div>';
            $html .= '<div class="rpp-stat-item">';
            $html .= '<div class="rpp-stat-value">' . $children_count . '</div>';
            $html .= '<div class="rpp-stat-label">Přímí</div>';
            $html .= '</div>';
            $html .= '<div class="rpp-stat-item">';
            $html .= '<div class="rpp-stat-value">' . ($partner->level ?: 1) . '</div>';
            $html .= '<div class="rpp-stat-label">Úroveň</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Children
            if ($children_count > 0) {
                $html .= '<div class="rpp-children">';
                $html .= $this->render_tree_html($partner->children, $level + 1);
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Move partner to new sponsor
     */
    public function move_partner($partner_id, $new_sponsor_code = '') {
        global $wpdb;
        
        $new_sponsor_id = null;
        $new_level = 1;
        $new_path = '';
        
        if (!empty($new_sponsor_code)) {
            $new_sponsor = $this->get_partner_by_code($new_sponsor_code);
            if (!$new_sponsor) {
                return false;
            }
            
            $new_sponsor_id = $new_sponsor->id;
            $sponsor_structure = $this->get_partner_structure($new_sponsor_id);
            if ($sponsor_structure) {
                $new_level = $sponsor_structure->level + 1;
                $new_path = $sponsor_structure->path . $new_sponsor_id . '/';
            }
        }
        
        // Update partner's structure
        $result = $wpdb->update(
            $this->table_name,
            array(
                'sponsor_id' => $new_sponsor_id,
                'level' => $new_level,
                'path' => $new_path
            ),
            array('partner_id' => $partner_id),
            array('%d', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Update all children's paths and levels
            $this->update_children_structure($partner_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update children structure after parent move
     */
    private function update_children_structure($parent_id) {
        global $wpdb;
        
        $parent_structure = $this->get_partner_structure($parent_id);
        if (!$parent_structure) return;
        
        $children = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE sponsor_id = %d",
                $parent_id
            )
        );
        
        foreach ($children as $child) {
            $new_level = $parent_structure->level + 1;
            $new_path = $parent_structure->path . $parent_id . '/';
            
            $wpdb->update(
                $this->table_name,
                array(
                    'level' => $new_level,
                    'path' => $new_path
                ),
                array('partner_id' => $child->partner_id),
                array('%d', '%s'),
                array('%d')
            );
            
            // Recursively update grandchildren
            $this->update_children_structure($child->partner_id);
        }
    }
}