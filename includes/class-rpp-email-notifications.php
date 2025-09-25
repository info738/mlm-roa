<?php
/**
 * Email notifications management class
 */
class RPP_Email_Notifications {
    
    private $templates_path;
    
    public function __construct() {
        $this->templates_path = RPP_PLUGIN_PATH . 'templates/emails/';
    }
    
    /**
     * Send partner application notification
     */
    public function send_application_notification($partner_id) {
        global $wpdb;
        
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email 
                 FROM {$wpdb->prefix}rpp_partners p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 WHERE p.id = %d",
                $partner_id
            )
        );
        
        if (!$partner) return false;
        
        // Send to admin
        $this->send_admin_application_notification($partner);
        
        return true;
    }
    
    /**
     * Send status update notification
     */
    public function send_status_update($partner_id, $new_status) {
        global $wpdb;
        
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email, g.name as group_name
                 FROM {$wpdb->prefix}rpp_partners p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 LEFT JOIN {$wpdb->prefix}rpp_partner_groups g ON p.group_id = g.id
                 WHERE p.id = %d",
                $partner_id
            )
        );
        
        if (!$partner) return false;
        
        // Send only approval notification
        if ($new_status === 'approved') {
            $this->send_approval_notification($partner);
        }
        
        return true;
    }
    
    /**
     * Send commission notification
     */
    public function send_commission_notification($commission_id) {
        global $wpdb;
        
        $commission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT c.*, p.partner_code, u.display_name, u.user_email
                 FROM {$wpdb->prefix}rpp_commissions c
                 LEFT JOIN {$wpdb->prefix}rpp_partners p ON c.partner_id = p.id
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                 WHERE c.id = %d",
                $commission_id
            )
        );
        
        if (!$commission) return false;
        
        $subject = __('New Commission Earned!', 'roanga-partner');
        $template_vars = array(
            'partner_name' => $commission->display_name,
            'commission_amount' => wc_price($commission->amount),
            'commission_type' => $commission->type,
            'order_id' => $commission->order_id,
            'dashboard_url' => get_permalink(get_option('rpp_dashboard_page_id'))
        );
        
        $message = $this->load_template('commission-earned', $template_vars);
        
        return $this->send_email($commission->user_email, $subject, $message);
    }
    
    /**
     * Send monthly performance report
     */
    public function send_monthly_report($partner_id) {
        global $wpdb;
        
        $partner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, u.display_name, u.user_email
                 FROM {$wpdb->prefix}rpp_partners p 
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
                 WHERE p.id = %d",
                $partner_id
            )
        );
        
        if (!$partner) return false;
        
        // Get monthly stats
        $current_month = date('Y-m-01');
        $next_month = date('Y-m-01', strtotime('+1 month'));
        
        $monthly_stats = array(
            'clicks' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking 
                     WHERE partner_id = %d AND conversion_type = 'click' 
                     AND created_at >= %s AND created_at < %s",
                    $partner_id, $current_month, $next_month
                )
            ),
            'conversions' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}rpp_tracking 
                     WHERE partner_id = %d AND conversion_type != 'click' 
                     AND created_at >= %s AND created_at < %s",
                    $partner_id, $current_month, $next_month
                )
            ),
            'earnings' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(amount) FROM {$wpdb->prefix}rpp_commissions 
                     WHERE partner_id = %d AND status IN ('approved', 'paid')
                     AND created_at >= %s AND created_at < %s",
                    $partner_id, $current_month, $next_month
                )
            ) ?: 0
        );
        
        $subject = sprintf(__('Your %s Performance Report', 'roanga-partner'), date('F Y'));
        $template_vars = array(
            'partner_name' => $partner->display_name,
            'month_year' => date('F Y'),
            'clicks' => $monthly_stats['clicks'],
            'conversions' => $monthly_stats['conversions'],
            'earnings' => wc_price($monthly_stats['earnings']),
            'conversion_rate' => $monthly_stats['clicks'] > 0 
                ? round(($monthly_stats['conversions'] / $monthly_stats['clicks']) * 100, 2) 
                : 0,
            'dashboard_url' => get_permalink(get_option('rpp_dashboard_page_id'))
        );
        
        $message = $this->load_template('monthly-report', $template_vars);
        
        return $this->send_email($partner->user_email, $subject, $message);
    }
    
    /**
     * Send admin application notification
     */
    private function send_admin_application_notification($partner) {
        $admin_email = get_option('admin_email');
        $subject = __('Nová partnerská registrace', 'roanga-partner');
        
        $template_vars = array(
            'partner_name' => $partner->display_name,
            'partner_email' => $partner->user_email,
            'partner_code' => $partner->partner_code,
            'application_date' => date_i18n(get_option('date_format'), strtotime($partner->created_at)),
            'admin_url' => admin_url('admin.php?page=rpp-partner-detail&id=' . $partner->id),
            'website' => get_user_meta($partner->user_id, 'rpp_website', true),
            'experience' => get_user_meta($partner->user_id, 'rpp_experience', true),
            'site_name' => get_bloginfo('name')
        );
        
        $message = $this->get_admin_notification_template($template_vars);
        
        return $this->send_email($admin_email, $subject, $message);
    }
    
    /**
     * Send applicant confirmation
     */
    private function send_applicant_confirmation($partner) {
        $subject = __('Partner Application Received', 'roanga-partner');
        
        $template_vars = array(
            'partner_name' => $partner->display_name,
            'partner_code' => $partner->partner_code,
            'site_name' => get_bloginfo('name'),
            'contact_email' => get_option('admin_email')
        );
        
        $message = $this->load_template('application-confirmation', $template_vars);
        
        return $this->send_email($partner->user_email, $subject, $message);
    }
    
    /**
     * Send approval notification
     */
    private function send_approval_notification($partner) {
        $subject = sprintf(__('Vítejte v partnerském programu - %s', 'roanga-partner'), get_bloginfo('name'));
        
        $template_vars = array(
            'partner_name' => $partner->display_name,
            'partner_code' => $partner->partner_code,
            'commission_rate' => $partner->commission_rate,
            'group_name' => $partner->group_name ?: __('Standard', 'roanga-partner'),
            'dashboard_url' => get_permalink(get_option('rpp_dashboard_page_id')),
            'site_name' => get_bloginfo('name')
        );
        
        $message = $this->get_approval_template($template_vars);
        
        return $this->send_email($partner->user_email, $subject, $message);
    }
    
    /**
     * Send rejection notification
     */
    private function send_rejection_notification($partner) {
        $subject = __('Partner Application Update', 'roanga-partner');
        
        $template_vars = array(
            'partner_name' => $partner->display_name,
            'site_name' => get_bloginfo('name'),
            'contact_email' => get_option('admin_email'),
            'reapply_url' => get_permalink(get_option('rpp_registration_page_id'))
        );
        
        $message = $this->load_template('application-rejected', $template_vars);
        
        return $this->send_email($partner->user_email, $subject, $message);
    }
    
    /**
     * Load email template
     */
    private function load_template($template_name, $vars = array()) {
        $template_file = $this->templates_path . $template_name . '.php';
        
        if (file_exists($template_file)) {
            extract($vars);
            ob_start();
            include $template_file;
            return ob_get_clean();
        }
        
        // Fallback to simple text template
        return $this->get_fallback_template($template_name, $vars);
    }
    
    /**
     * Get fallback template
     */
    private function get_admin_notification_template($vars) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Nová partnerská registrace</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #d4af37; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: #2d5a27; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎯 Nová partnerská registrace</h1>
                </div>
                
                <div class="content">
                    <p>Byla přijata nová partnerská registrace, která vyžaduje vaše posouzení.</p>
                    
                    <div class="info-box">
                        <h3>📋 Informace o žadateli:</h3>
                        <ul>
                            <li><strong>Jméno:</strong> ' . esc_html($vars['partner_name']) . '</li>
                            <li><strong>Email:</strong> ' . esc_html($vars['partner_email']) . '</li>
                            <li><strong>Partnerský kód:</strong> ' . esc_html($vars['partner_code']) . '</li>
                            <li><strong>Datum registrace:</strong> ' . esc_html($vars['application_date']) . '</li>';
                            
        if (!empty($vars['website'])) {
            $message .= '<li><strong>Website:</strong> <a href="' . esc_url($vars['website']) . '">' . esc_html($vars['website']) . '</a></li>';
        }
        
        $message .= '
                        </ul>
                    </div>';
                    
        if (!empty($vars['experience'])) {
            $message .= '
                    <div class="info-box">
                        <h4>💼 Marketingové zkušenosti:</h4>
                        <p>' . nl2br(esc_html($vars['experience'])) . '</p>
                    </div>';
        }
        
        $message .= '
                    <p style="text-align: center;">
                        <a href="' . esc_url($vars['admin_url']) . '" class="button">📝 Posoudit žádost</a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>S pozdravem,<br>Systém ' . esc_html($vars['site_name']) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $message;
    }
    
    private function get_approval_template($vars) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Vítejte v partnerském programu</title>
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
                    <h1>🎉 Gratulujeme!</h1>
                </div>
                
                <div class="content">
                    <p>Vážený/á ' . esc_html($vars['partner_name']) . ',</p>
                    
                    <p>Skvělá zpráva! Vaše partnerská žádost byla schválena a nyní můžete začít vydělávat provize.</p>
                    
                    <div class="credentials">
                        <h3>🎯 Vaše partnerské údaje:</h3>
                        <ul>
                            <li><strong>Partnerský kód:</strong> ' . esc_html($vars['partner_code']) . '</li>
                            <li><strong>Provizní sazba:</strong> ' . esc_html($vars['commission_rate']) . '%</li>
                            <li><strong>Skupina:</strong> ' . esc_html($vars['group_name']) . '</li>
                        </ul>
                    </div>
                    
                    <p>Nyní máte přístup ke svému partnerskému dashboardu, kde můžete získat referenční odkazy a sledovat svou výkonnost.</p>
                    
                    <p style="text-align: center;">
                        <a href="' . esc_url($vars['dashboard_url']) . '" class="button">🚀 Přejít na dashboard</a>
                    </p>
                    
                    <p>Děkujeme, že jste se připojili k našemu partnerskému programu!</p>
                </div>
                
                <div class="footer">
                    <p>S pozdravem,<br>Tým ' . esc_html($vars['site_name']) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $message;
    }
    
    private function get_commission_notification_template($vars) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Nová provize!</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; }
                .commission-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d4af37; text-align: center; }
                .commission-amount { font-size: 32px; font-weight: 700; color: #d4af37; margin: 10px 0; }
                .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); color: #2d5a27; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Nová provize!</h1>
                </div>
                
                <div class="content">
                    <p>Vážený/á ' . esc_html($vars['partner_name']) . ',</p>
                    
                    <p>Gratulujeme! Právě jste získali novou provizi!</p>
                    
                    <div class="commission-box">
                        <h3>💰 Vaše provize</h3>
                        <div class="commission-amount">' . wc_price($vars['commission_amount']) . '</div>';
                        
        if ($vars['order_id']) {
            $message .= '<p><strong>Objednávka:</strong> #' . esc_html($vars['order_id']) . '</p>';
        }
        
        $message .= '<p><strong>Typ:</strong> ' . esc_html(ucfirst($vars['commission_type'])) . '</p>';
        $message .= '
                    </div>
                    
                    <p>Provize bude zpracována a přidána k vašemu zůstatku. Můžete si ji prohlédnout ve svém dashboardu.</p>
                    
                    <p style="text-align: center;">
                        <a href="' . esc_url($vars['dashboard_url']) . '" class="button">📊 Zobrazit dashboard</a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>S pozdravem,<br>Tým ' . get_bloginfo('name') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $message;
    }
    
    /**
     * Send email
     */
    private function send_email($to, $subject, $message) {
        if (!get_option('rpp_email_notifications', true)) {
            return false;
        }
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
}