<?php
/**
 * WooCommerce integration class
 */
class RPP_WooCommerce_Integration {
    
    private $tracking_class;
    private $commission_class;
    private $mlm_class;
    
    public function __construct() {
        // Initialize classes immediately - they are loaded by this point
        $this->tracking_class = new RPP_Tracking();
        $this->commission_class = new RPP_Commission();
        $this->mlm_class = new RPP_MLM_Structure();
    }
    
    /**
     * Initialize all WooCommerce hooks
     */
    public function init_woocommerce_hooks() {
        error_log('RPP WooCommerce: Initializing WooCommerce hooks');
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_partner_meta_box'));
        
        // Order processing hooks
        add_action('woocommerce_checkout_create_order', array($this, 'add_partner_info_to_order'), 10, 2);
        add_action('woocommerce_order_status_completed', array($this, 'process_order_commission'));
        add_action('woocommerce_payment_complete', array($this, 'process_order_commission'));
        add_action('woocommerce_thankyou', array($this, 'process_order_commission'));
        
        // REST API hooks
        
        // Frontend tracking
        add_action('wp_footer', array($this, 'add_checkout_tracking_script'));
        add_action('wp_footer', array($this, 'add_debug_tracking_script'));
        
        // Partner column in orders list
        add_filter('manage_edit-shop_order_columns', array($this, 'add_partner_column_to_orders'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_partner_column_content'), 10, 2);
        
        error_log('RPP WooCommerce: All hooks initialized');
    }
    
    /**
     * Add meta box to order edit screen
     */
    public function add_partner_meta_box() {
        add_meta_box(
            'rpp-partner-info',
            __('🎯 Partner & Affiliate Info', 'roanga-partner'),
            array($this, 'display_partner_meta_box'),
            'shop_order',
            'side',
            'high'
        );
        
        // Also add for HPOS (High Performance Order Storage)
        add_meta_box(
            'rpp-partner-info',
            __('🎯 Partner & Affiliate Info', 'roanga-partner'),
            array($this, 'display_partner_meta_box'),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }
    
    /**
     * Display partner meta box content
     */
    public function display_partner_meta_box($post_or_order_object) {
        // Get order object
        if (is_numeric($post_or_order_object)) {
            $order = wc_get_order($post_or_order_object);
        } elseif (isset($post_or_order_object->ID)) {
            $order = wc_get_order($post_or_order_object->ID);
        } else {
            $order = $post_or_order_object;
        }
        
        if (!$order) {
            echo '<p>Chyba při načítání objednávky.</p>';
            return;
        }
        
        $order_id = $order->get_id();
        $partner_code = $order->get_meta('_rpp_partner_code');
        $partner_name = $order->get_meta('_rpp_partner_name');
        $direct_commission = $order->get_meta('_rpp_direct_commission_amount');
        $mlm_commissions = $order->get_meta('_rpp_mlm_commissions');
        $commission_processed = $order->get_meta('_rpp_commission_processed');
        
        echo '<div class="rpp-partner-meta-box">';
        
        if ($partner_code) {
            // Partner is assigned
            echo '<div class="rpp-partner-assigned">';
            echo '<div class="rpp-partner-header">';
            echo '<h4>✅ Partner přiřazen</h4>';
            echo '</div>';
            
            echo '<div class="rpp-partner-details">';
            echo '<p><strong>Partner:</strong><br>' . esc_html($partner_name ?: 'Neznámý') . '</p>';
            echo '<p><strong>Kód:</strong><br><code>' . esc_html($partner_code) . '</code></p>';
            
            if ($direct_commission) {
                echo '<p><strong>Přímá provize:</strong><br><span class="rpp-commission-amount">' . wc_price($direct_commission) . '</span></p>';
            }
            
            if ($mlm_commissions && is_array($mlm_commissions)) {
                echo '<p><strong>MLM provize:</strong></p>';
                echo '<ul class="rpp-mlm-list">';
                foreach ($mlm_commissions as $mlm_commission) {
                    echo '<li>Úroveň ' . $mlm_commission['level'] . ': ' . wc_price($mlm_commission['amount']) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '<div class="rpp-status">';
            if ($commission_processed) {
                echo '<span class="rpp-status-processed">✅ Provize zpracovány</span>';
            } else {
                echo '<span class="rpp-status-pending">⏳ Čeká na zpracování</span>';
                echo '<button type="button" class="button button-primary button-small" onclick="processCommissionNow(' . $order_id . ')">Zpracovat nyní</button>';
            }
            echo '</div>';
            
            echo '<div class="rpp-actions">';
            echo '<button type="button" class="button button-secondary button-small" onclick="removePartnerFromOrder(' . $order_id . ')">Odebrat partnera</button>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
        } else {
            // No partner assigned
            echo '<div class="rpp-no-partner">';
            echo '<div class="rpp-no-partner-header">';
            echo '<h4>⚠️ Žádný partner</h4>';
            echo '</div>';
            
            echo '<p>Tato objednávka nemá přiřazeného partnera.</p>';
            
            echo '<div class="rpp-manual-assignment">';
            echo '<label for="manual-partner-code-' . $order_id . '"><strong>Přiřadit partnera:</strong></label>';
            echo '<input type="text" id="manual-partner-code-' . $order_id . '" placeholder="PARTNER12345" class="widefat">';
            echo '<button type="button" class="button button-primary" onclick="assignPartnerToOrder(' . $order_id . ')" style="margin-top: 8px; width: 100%;">Přiřadit partnera</button>';
            echo '<div id="partner-search-result-' . $order_id . '" style="margin-top: 10px; display: none;"></div>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add CSS and JavaScript
        $this->add_meta_box_styles_and_scripts($order_id);
    }
    
    /**
     * Add styles and scripts for meta box
     */
    private function add_meta_box_styles_and_scripts($order_id) {
        ?>
        <style>
        .rpp-partner-meta-box {
            font-size: 13px;
        }
        
        .rpp-partner-assigned {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 12px;
        }
        
        .rpp-no-partner {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px;
        }
        
        .rpp-partner-header h4,
        .rpp-no-partner-header h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        
        .rpp-partner-details p {
            margin: 8px 0;
        }
        
        .rpp-commission-amount {
            color: #d4af37;
            font-weight: bold;
            font-size: 14px;
        }
        
        .rpp-mlm-list {
            margin: 4px 0 8px 16px;
            font-size: 12px;
        }
        
        .rpp-status-processed {
            color: #155724;
            font-weight: bold;
        }
        
        .rpp-status-pending {
            color: #856404;
            font-weight: bold;
        }
        
        .rpp-actions {
            margin-top: 12px;
        }
        
        .rpp-manual-assignment {
            margin-top: 12px;
        }
        
        .rpp-manual-assignment label {
            display: block;
            margin-bottom: 4px;
            font-weight: bold;
        }
        
        .rpp-manual-assignment input {
            margin-bottom: 8px;
        }
        
        #partner-search-result-<?php echo $order_id; ?> {
            padding: 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .rpp-result-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .rpp-result-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .rpp-result-loading {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        </style>
        
        <script>
        function assignPartnerToOrder(orderId) {
            var partnerCode = document.getElementById('manual-partner-code-' + orderId).value.trim();
            var resultDiv = document.getElementById('partner-search-result-' + orderId);
            
            if (!partnerCode) {
                alert('Prosím zadejte partnerský kód');
                return;
            }
            
            // Show loading
            resultDiv.className = 'rpp-result-loading';
            resultDiv.innerHTML = '🔍 Vyhledávám partnera...';
            resultDiv.style.display = 'block';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_assign_partner_to_order',
                    order_id: orderId,
                    partner_code: partnerCode,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.className = 'rpp-result-success';
                        resultDiv.innerHTML = '✅ ' + response.data;
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        resultDiv.className = 'rpp-result-error';
                        resultDiv.innerHTML = '❌ ' + response.data;
                    }
                },
                error: function() {
                    resultDiv.className = 'rpp-result-error';
                    resultDiv.innerHTML = '❌ Chyba při komunikaci se serverem';
                }
            });
        }
        
        function removePartnerFromOrder(orderId) {
            if (!confirm('Opravdu chcete odebrat partnera z této objednávky?')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_remove_partner_from_order',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Partner byl odebrán z objednávky');
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data);
                    }
                },
                error: function() {
                    alert('Chyba při komunikaci se serverem');
                }
            });
        }
        
        function processCommissionNow(orderId) {
            if (!confirm('Zpracovat provize pro tuto objednávku nyní?')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_process_commission_now',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Provize byly zpracovány');
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data);
                    }
                },
                error: function() {
                    alert('Chyba při komunikaci se serverem');
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Register REST API hooks for WooCommerce
     */
    public function register_rest_hooks() {
        error_log('RPP WooCommerce: Registering REST API hooks');
        
        // Hook into WooCommerce Store API checkout
        add_filter('woocommerce_store_api_checkout_update_order_from_request', array($this, 'add_partner_to_rest_order'), 10, 2);
        add_filter('woocommerce_rest_checkout_process_payment', array($this, 'capture_partner_before_payment'), 10, 2);
    }
    
    /**
     * Add partner info to order via REST API
     */
    public function add_partner_to_rest_order($order, $request) {
        error_log('RPP WooCommerce REST: add_partner_to_rest_order called for order ID: ' . $order->get_id());
        
        // Get partner from tracking
        $partner = $this->tracking_class->get_current_partner();
        
        if ($partner) {
            error_log('RPP WooCommerce REST: Partner found via tracking: ' . $partner->partner_code . ' (ID: ' . $partner->id . ')');
            
            $order->add_meta_data('_rpp_partner_code', $partner->partner_code);
            $order->add_meta_data('_rpp_partner_id', $partner->id);
            $order->add_meta_data('_rpp_partner_name', $partner->display_name);
            $order->save_meta_data();
            
            error_log('RPP WooCommerce REST: Successfully added partner meta to order');
        } else {
            error_log('RPP WooCommerce REST: No partner found for order');
            error_log('RPP WooCommerce REST: Available cookies: ' . print_r($_COOKIE, true));
        }
        
        return $order;
    }
    
    /**
     * Capture partner before payment processing
     */
    public function capture_partner_before_payment($result, $server) {
        error_log('RPP WooCommerce REST: capture_partner_before_payment called');
        
        $partner = $this->tracking_class->get_current_partner();
        if ($partner) {
            error_log('RPP WooCommerce REST: Partner found before payment: ' . $partner->partner_code);
        }
        
        return $result;
    }
    
    /**
     * Add JavaScript tracking for checkout
     */
    public function add_checkout_tracking_script() {
        if (!is_checkout() && !is_cart()) {
            return;
        }
        
        $partner = $this->tracking_class->get_current_partner();
        if (!$partner) {
            return;
        }
        
        ?>
        <script>
        // Add partner info to checkout data
        document.addEventListener('DOMContentLoaded', function() {
            console.log('RPP: Adding partner tracking to checkout');
            console.log('RPP: Partner code: <?php echo esc_js($partner->partner_code); ?>');
            
            // Store partner info in localStorage as backup
            localStorage.setItem('rpp_partner_code', '<?php echo esc_js($partner->partner_code); ?>');
            localStorage.setItem('rpp_partner_id', '<?php echo esc_js($partner->id); ?>');
            localStorage.setItem('rpp_partner_name', '<?php echo esc_js($partner->display_name); ?>');
            
            // Add hidden fields to checkout form if it exists
            var checkoutForm = document.querySelector('form.checkout, form.woocommerce-checkout');
            if (checkoutForm) {
                var partnerField = document.createElement('input');
                partnerField.type = 'hidden';
                partnerField.name = 'rpp_partner_code';
                partnerField.value = '<?php echo esc_js($partner->partner_code); ?>';
                checkoutForm.appendChild(partnerField);
                
                var partnerIdField = document.createElement('input');
                partnerIdField.type = 'hidden';
                partnerIdField.name = 'rpp_partner_id';
                partnerIdField.value = '<?php echo esc_js($partner->id); ?>';
                checkoutForm.appendChild(partnerIdField);
                
                console.log('RPP: Added hidden fields to checkout form');
            }
            
            // Hook into WooCommerce Store API if available
            if (typeof wc !== 'undefined' && wc.wcBlocksRegistry) {
                console.log('RPP: Hooking into WooCommerce Blocks');
                
                // Add partner data to checkout data
                jQuery(document).on('checkout_place_order', function() {
                    console.log('RPP: Checkout place order triggered');
                    return true;
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add debug tracking script to all pages
     */
    public function add_debug_tracking_script() {
        $partner = $this->tracking_class->get_current_partner();
        ?>
        <script>
        // Debug tracking info
        console.log('RPP Debug: Current page tracking info');
        console.log('RPP Debug: Partner from PHP: <?php echo $partner ? esc_js($partner->partner_code) : 'NONE'; ?>');
        console.log('RPP Debug: Cookies:', document.cookie);
        console.log('RPP Debug: localStorage partner:', localStorage.getItem('rpp_partner_code'));
        
        // Show tracking info in console
        if (document.cookie.includes('rpp_partner_ref=')) {
            var partnerCode = document.cookie.split('rpp_partner_ref=')[1].split(';')[0];
            console.log('RPP Debug: Partner cookie found:', partnerCode);
        } else {
            console.log('RPP Debug: No partner cookie found');
        }
        </script>
        <?php
    }
    
    /**
     * Process commission when order is completed
     */
    public function process_order_commission($order_id) {
        error_log('RPP WooCommerce: Processing commission for order: ' . $order_id);
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('RPP WooCommerce: Order not found: ' . $order_id);
            return;
        }
        
        // Check if commission already processed
        if ($order->get_meta('_rpp_commission_processed')) {
            error_log('RPP WooCommerce: Commission already processed for order: ' . $order_id);
            return;
        }
        
        // Get partner from order meta (set during checkout)
        $partner_code = $order->get_meta('_rpp_partner_code');
        $partner_id = $order->get_meta('_rpp_partner_id');
        $partner_name = $order->get_meta('_rpp_partner_name');
        
        error_log('RPP WooCommerce: Order meta - Partner code: ' . $partner_code . ', Partner ID: ' . $partner_id . ', Name: ' . $partner_name);
        
        if (!$partner_code || !$partner_id) {
            error_log('RPP WooCommerce: No partner info found in order meta for order: ' . $order_id);
            error_log('RPP WooCommerce: Skipping commission processing - no partner assigned');
            return;
        }
        
        // Get partner details
        $partner_class = new RPP_Partner();
        $partner = $partner_class->get_partner_detail($partner_id);
        
        if (!$partner || $partner->status !== 'approved') {
            error_log('RPP WooCommerce: Partner not found or not approved. Partner ID: ' . $partner_id);
            return;
        }
        
        error_log('RPP WooCommerce: Processing commission for partner: ' . $partner->partner_code . ' (' . $partner->display_name . ')');
        
        $order_total = $order->get_total();
        error_log('RPP WooCommerce: Order total: ' . $order_total);
        
        // Create direct commission for the partner
        $commission_amount = $this->commission_class->calculate_commission($order_total, $partner->commission_rate);
        error_log('RPP WooCommerce: Calculated commission: ' . $commission_amount . ' (rate: ' . $partner->commission_rate . '%)');
        
        if ($commission_amount > 0) {
            $commission_id = $this->commission_class->create_commission(
                $partner->id, 
                $commission_amount, 
                $order_id, 
                'sale'
            );
            
            error_log('RPP WooCommerce: Created commission ID: ' . $commission_id);
            
            // Add commission info to order
            $order->add_meta_data('_rpp_direct_commission_id', $commission_id);
            $order->add_meta_data('_rpp_direct_commission_amount', $commission_amount);
            
            // Send commission notification
            $email_class = new RPP_Email_Notifications();
            $email_class->send_commission_notification($commission_id);
        }
        
        // Process MLM commissions if enabled
        if (get_option('rpp_mlm_enabled', false)) {
            error_log('RPP WooCommerce: Processing MLM commissions...');
            $mlm_commissions = $this->mlm_class->calculate_mlm_commissions($partner->id, $order_total);
            $mlm_commission_ids = array();
            
            foreach ($mlm_commissions as $mlm_commission) {
                error_log('RPP WooCommerce: Creating MLM commission - Level: ' . $mlm_commission['level'] . ', Amount: ' . $mlm_commission['amount']);
                $mlm_commission_id = $this->commission_class->create_commission(
                    $mlm_commission['partner_id'],
                    $mlm_commission['amount'],
                    $order_id,
                    'mlm_level_' . $mlm_commission['level']
                );
                
                if ($mlm_commission_id) {
                    $mlm_commission_ids[] = array(
                        'id' => $mlm_commission_id,
                        'level' => $mlm_commission['level'],
                        'amount' => $mlm_commission['amount'],
                        'partner_id' => $mlm_commission['partner_id']
                    );
                    
                    // Send MLM commission notification
                    $email_class->send_commission_notification($mlm_commission_id);
                }
            }
            
            // Add MLM commission info to order
            $order->add_meta_data('_rpp_mlm_commissions', $mlm_commission_ids);
            error_log('RPP WooCommerce: Added ' . count($mlm_commission_ids) . ' MLM commissions to order');
        }
        
        // Mark as processed
        $order->add_meta_data('_rpp_commission_processed', true);
        $order->save();
        
        error_log('RPP WooCommerce: Commission processing completed for order: ' . $order_id);
        
        // Track conversion
        $this->tracking_class->track_conversion($order_id, 'sale', $order_total);
    }
    
    /**
     * Capture partner info during checkout process
     */
    public function capture_partner_during_checkout() {
        error_log('RPP WooCommerce: capture_partner_during_checkout called');
        error_log('RPP WooCommerce: Available cookies: ' . print_r($_COOKIE, true));
        
        $partner = $this->tracking_class->get_current_partner();
        
        if ($partner) {
            error_log('RPP WooCommerce: Partner found during checkout: ' . $partner->partner_code . ' (ID: ' . $partner->id . ')');
            
            // Store in session for checkout
            if (!session_id()) {
                session_start();
            }
            $_SESSION['checkout_partner_id'] = $partner->id;
            $_SESSION['checkout_partner_code'] = $partner->partner_code;
            $_SESSION['checkout_partner_name'] = $partner->display_name;
            
            error_log('RPP WooCommerce: Stored partner in checkout session');
            error_log('RPP WooCommerce: Session data stored: ID=' . $partner->id . ', Code=' . $partner->partner_code . ', Name=' . $partner->display_name);
        } else {
            error_log('RPP WooCommerce: No partner found during checkout');
            error_log('RPP WooCommerce: Available cookies: ' . print_r($_COOKIE, true));
            
            // Try to get partner directly from cookie as fallback
            if (isset($_COOKIE['rpp_partner_ref'])) {
                $partner_code = sanitize_text_field($_COOKIE['rpp_partner_ref']);
                error_log('RPP WooCommerce: Found partner cookie directly: ' . $partner_code);
                
                $partner_class = new RPP_Partner();
                $partner = $partner_class->get_partner_by_code($partner_code);
                
                if ($partner && $partner->status === 'approved') {
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['checkout_partner_id'] = $partner->id;
                    $_SESSION['checkout_partner_code'] = $partner->partner_code;
                    $_SESSION['checkout_partner_name'] = $partner->display_name;
                    
                    error_log('RPP WooCommerce: Fallback partner stored in session: ' . $partner->partner_code);
                }
            }
        }
    }
    
    /**
     * Add partner info to order during checkout
     */
    public function add_partner_info_to_order($order, $data = null) {
        error_log('RPP WooCommerce: add_partner_info_to_order called for order ID: ' . $order->get_id());
        
        // Try to get partner from POST data first (from hidden fields)
        $partner_code = null;
        $partner_id = null;
        $partner_name = null;
        
        if (isset($_POST['rpp_partner_code']) && isset($_POST['rpp_partner_id'])) {
            $partner_code = sanitize_text_field($_POST['rpp_partner_code']);
            $partner_id = intval($_POST['rpp_partner_id']);
            error_log('RPP WooCommerce: Partner found in POST data - Code: ' . $partner_code . ', ID: ' . $partner_id);
        }
        
        
        // Try to get partner from checkout session first
        if (!$partner_code && !session_id()) {
            session_start();
        }
        
        if (!$partner_code) {
            $partner_id = isset($_SESSION['checkout_partner_id']) ? $_SESSION['checkout_partner_id'] : null;
            $partner_code = isset($_SESSION['checkout_partner_code']) ? $_SESSION['checkout_partner_code'] : null;
            $partner_name = isset($_SESSION['checkout_partner_name']) ? $_SESSION['checkout_partner_name'] : null;
        }
        
        error_log('RPP WooCommerce: Session partner data - ID: ' . $partner_id . ', Code: ' . $partner_code . ', Name: ' . $partner_name);
        
        // Fallback to tracking class
        if (!$partner_id) {
            error_log('RPP WooCommerce: No partner in session, trying tracking class...');
            $partner = $this->tracking_class->get_current_partner();
            if ($partner) {
                $partner_id = $partner->id;
                $partner_code = $partner->partner_code;
                $partner_name = $partner->display_name;
                error_log('RPP WooCommerce: Fallback partner from tracking: ' . $partner_code);
            } else {
                error_log('RPP WooCommerce: No partner found in tracking class either');
                error_log('RPP WooCommerce: Available cookies in add_partner_info_to_order: ' . print_r($_COOKIE, true));
            }
        }
        
        error_log('RPP WooCommerce: Final partner data - ID: ' . $partner_id . ', Code: ' . $partner_code . ', Name: ' . $partner_name);
        
        if ($partner_id && $partner_code) {
            error_log('RPP WooCommerce: SUCCESS - Adding partner meta to order - Code: ' . $partner_code . ', ID: ' . $partner_id . ', Name: ' . $partner_name);
            
            $order->add_meta_data('_rpp_partner_code', $partner_code);
            $order->add_meta_data('_rpp_partner_id', $partner_id);
            if ($partner_name) {
                $order->add_meta_data('_rpp_partner_name', $partner_name);
            }
            $order->save_meta_data();
            
            error_log('RPP WooCommerce: Meta data saved successfully');
            
            // Verify meta was saved
            $saved_code = $order->get_meta('_rpp_partner_code');
            error_log('RPP WooCommerce: Verification - saved partner code: ' . $saved_code);
        } else {
            error_log('RPP WooCommerce: No partner found for order - checking cookies and session');
            error_log('RPP WooCommerce: Available cookies: ' . print_r($_COOKIE, true));
            if (session_id()) {
                error_log('RPP WooCommerce: Session data: ' . print_r($_SESSION, true));
            }
        }
    }
    
    /**
     * Display partner info in admin order details
     */
    public function display_partner_info_in_admin($order) {
        error_log('RPP WooCommerce: Displaying partner info for order ID: ' . $order->get_id());
        
        $partner_code = $order->get_meta('_rpp_partner_code');
        $partner_name = $order->get_meta('_rpp_partner_name');
        $direct_commission = $order->get_meta('_rpp_direct_commission_amount');
        $mlm_commissions = $order->get_meta('_rpp_mlm_commissions');
        $commission_processed = $order->get_meta('_rpp_commission_processed');
        
        error_log('RPP WooCommerce: Order meta - Partner code: ' . $partner_code . ', Name: ' . $partner_name . ', Commission: ' . $direct_commission);
        
        echo '<div class="rpp-order-partner-section" style="margin-top: 20px;">';
        
        if ($partner_code) {
            echo '<div class="rpp-order-partner-info" style="margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%); border-radius: 12px; border-left: 4px solid #d4af37;">';
            echo '<h3 style="color: #2d5a27; margin-top: 0; display: flex; align-items: center; gap: 8px;">📊 Partnerské provize</h3>';
            
            echo '<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
            echo '<p style="margin: 0;"><strong>Partner:</strong> ' . esc_html($partner_name ?: $partner_code) . ' <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' . esc_html($partner_code) . '</code></p>';
            echo '<button type="button" class="button button-small" onclick="removePartnerFromOrder(' . $order->get_id() . ')" style="margin-left: 10px; background: #dc3545; color: white;">Odebrat partnera</button>';
            echo '</div>';
            
            if ($direct_commission) {
                echo '<div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
                echo '<p style="margin: 0;"><strong>💰 Přímá provize:</strong> <span style="color: #d4af37; font-weight: 700; font-size: 16px;">' . wc_price($direct_commission) . '</span></p>';
                echo '</div>';
            }
            
            if ($mlm_commissions && is_array($mlm_commissions)) {
                echo '<div style="background: white; padding: 15px; border-radius: 8px;">';
                echo '<h4 style="margin: 0 0 10px 0; color: #2d5a27;">🌳 MLM Provize:</h4>';
                echo '<ul style="margin: 0; padding-left: 20px;">';
                foreach ($mlm_commissions as $mlm_commission) {
                    echo '<li style="margin-bottom: 5px;">Úroveň ' . $mlm_commission['level'] . ': <strong style="color: #4a7c59;">' . wc_price($mlm_commission['amount']) . '</strong></li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            // Show processing status
            if ($commission_processed) {
                echo '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-top: 10px; text-align: center;">';
                echo '<strong>✅ Provize zpracovány</strong>';
                echo '</div>';
            } else {
                echo '<div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 6px; margin-top: 10px; text-align: center;">';
                echo '<strong>⏳ Provize čekají na zpracování</strong>';
                echo '<button type="button" class="button button-primary" onclick="processCommissionNow(' . $order->get_id() . ')" style="margin-left: 10px;">Zpracovat nyní</button>';
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            // Manual partner assignment form
            echo '<div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 12px; border-left: 4px solid #ffc107;">';
            echo '<h3 style="color: #856404; margin-top: 0; display: flex; align-items: center; gap: 8px;">⚠️ Žádný partner přiřazen</h3>';
            echo '<p style="margin: 0 0 15px 0; color: #856404;">Tato objednávka nemá přiřazeného partnera. Můžete partnera přiřadit ručně:</p>';
            
            echo '<div style="background: white; padding: 15px; border-radius: 8px;">';
            echo '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
            echo '<label for="manual-partner-code-' . $order->get_id() . '" style="font-weight: 600; color: #333;">Partnerský kód:</label>';
            echo '<input type="text" id="manual-partner-code-' . $order->get_id() . '" placeholder="PARTNER12345" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 150px;">';
            echo '<button type="button" class="button button-primary" onclick="assignPartnerToOrder(' . $order->get_id() . ')">Přiřadit partnera</button>';
            echo '</div>';
            echo '<div id="partner-search-result-' . $order->get_id() . '" style="margin-top: 10px; display: none;"></div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add JavaScript for manual assignment
        ?>
        <script>
        function assignPartnerToOrder(orderId) {
            var partnerCode = document.getElementById('manual-partner-code-' + orderId).value.trim();
            var resultDiv = document.getElementById('partner-search-result-' + orderId);
            
            if (!partnerCode) {
                alert('Prosím zadejte partnerský kód');
                return;
            }
            
            // Show loading
            resultDiv.innerHTML = '<div style="padding: 10px; background: #e3f2fd; border-radius: 4px; color: #1976d2;">🔍 Vyhledávám partnera...</div>';
            resultDiv.style.display = 'block';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_assign_partner_to_order',
                    order_id: orderId,
                    partner_code: partnerCode,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.innerHTML = '<div style="padding: 10px; background: #d4edda; border-radius: 4px; color: #155724;">✅ ' + response.data + '</div>';
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        resultDiv.innerHTML = '<div style="padding: 10px; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ ' + response.data + '</div>';
                    }
                },
                error: function() {
                    resultDiv.innerHTML = '<div style="padding: 10px; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Chyba při komunikaci se serverem</div>';
                }
            });
        }
        
        function removePartnerFromOrder(orderId) {
            if (!confirm('Opravdu chcete odebrat partnera z této objednávky?')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_remove_partner_from_order',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Partner byl odebrán z objednávky');
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data);
                    }
                },
                error: function() {
                    alert('Chyba při komunikaci se serverem');
                }
            });
        }
        
        function processCommissionNow(orderId) {
            if (!confirm('Zpracovat provize pro tuto objednávku nyní?')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rpp_process_commission_now',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Provize byly zpracovány');
                        location.reload();
                    } else {
                        alert('Chyba: ' + response.data);
                    }
                },
                error: function() {
                    alert('Chyba při komunikaci se serverem');
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Add partner column to orders list
     */
    public function add_partner_column_to_orders($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_status') {
                $new_columns['rpp_partner'] = __('Partner', 'roanga-partner');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display partner column content
     */
    public function display_partner_column_content($column, $post_id) {
        if ($column === 'rpp_partner') {
            $order = wc_get_order($post_id);
            $partner_code = $order->get_meta('_rpp_partner_code');
            $direct_commission = $order->get_meta('_rpp_direct_commission_amount');
            
            if ($partner_code) {
                echo '<strong>' . esc_html($partner_code) . '</strong>';
                if ($direct_commission) {
                    echo '<br><small style="color: #d4af37;">' . wc_price($direct_commission) . '</small>';
                }
            } else {
                echo '<span style="color: #999;">—</span>';
            }
        }
    }
}