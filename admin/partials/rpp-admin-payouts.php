<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle payout actions
if (isset($_POST['action']) && $_POST['action'] === 'process_payout') {
    check_admin_referer('rpp_payout_nonce');
    
    $partner_id = intval($_POST['partner_id']);
    $amount = floatval($_POST['amount']);
    $method = sanitize_text_field($_POST['method']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'rpp_payouts',
        array(
            'partner_id' => $partner_id,
            'amount' => $amount,
            'status' => 'completed',
            'payout_method' => $method,
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'paid_at' => current_time('mysql')
        )
    );
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Výplata byla úspěšně zpracována!</p></div>';
    }
}

// Get payouts
global $wpdb;

// Get payout requests (status = 'requested')
$payout_requests = $wpdb->get_results("
    SELECT po.*, p.partner_code, u.display_name, u.user_email,
           um1.meta_value as bank_account, um2.meta_value as bank_name
    FROM {$wpdb->prefix}rpp_payouts po
    LEFT JOIN {$wpdb->prefix}rpp_partners p ON po.partner_id = p.id
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'rpp_bank_account'
    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'rpp_bank_name'
    WHERE po.status = 'requested'
    ORDER BY po.created_at DESC
");

$payouts = $wpdb->get_results("
    SELECT po.*, p.partner_code, u.display_name, u.user_email
    FROM {$wpdb->prefix}rpp_payouts po
    LEFT JOIN {$wpdb->prefix}rpp_partners p ON po.partner_id = p.id
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    WHERE po.status IN ('approved', 'completed', 'rejected')
    ORDER BY po.created_at DESC
    LIMIT 50
");

// Get partners with pending commissions
$pending_payouts = $wpdb->get_results("
    SELECT p.id, p.partner_code, u.display_name, u.user_email,
           SUM(c.amount) as pending_amount
    FROM {$wpdb->prefix}rpp_partners p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->prefix}rpp_commissions c ON p.id = c.partner_id
    WHERE c.status = 'approved' AND p.status = 'approved'
    GROUP BY p.id
    HAVING pending_amount >= " . get_option('rpp_minimum_payout', 50) . "
    ORDER BY pending_amount DESC
");
?>

<div class="wrap rpp-admin-wrap">
    <div class="rpp-header">
        <h1 class="rpp-title">
            <span class="rpp-icon">💰</span>
            <?php _e('Výplaty partnerům', 'roanga-partner'); ?>
        </h1>
    </div>
    
    <div class="rpp-payouts-container">
        <!-- Payout Requests -->
        <div class="rpp-section">
            <h2>📋 Žádosti o výplatu</h2>
            
            <?php if (empty($payout_requests)): ?>
                <div class="rpp-empty-state">
                    <p>Žádné nové žádosti o výplatu.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Datum žádosti</th>
                            <th>Partner</th>
                            <th>Částka</th>
                            <th>Číslo účtu</th>
                            <th>Faktura</th>
                            <th>Poznámky</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payout_requests as $request): ?>
                            <tr>
                                <td><?php echo date_i18n('j.n.Y H:i', strtotime($request->created_at)); ?></td>
                                <td>
                                    <strong><?php echo esc_html($request->display_name); ?></strong><br>
                                    <small><?php echo esc_html($request->partner_code); ?></small><br>
                                    <small><?php echo esc_html($request->user_email); ?></small>
                                </td>
                                <td><strong><?php echo wc_price($request->amount); ?></strong></td>
                                <td>
                                    <?php if ($request->bank_account): ?>
                                        <code><?php echo esc_html($request->bank_account); ?></code>
                                        <?php if ($request->bank_name): ?>
                                            <br><small><?php echo esc_html($request->bank_name); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em>Nenastaveno</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request->invoice_url): ?>
                                        <a href="<?php echo esc_url($request->invoice_url); ?>" target="_blank" class="button button-small">
                                            📄 Stáhnout PDF
                                        </a>
                                    <?php else: ?>
                                        <em>Bez faktury</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($request->notes); ?></td>
                                <td>
                                    <button class="button button-primary button-small approve-payout-request" 
                                            data-request-id="<?php echo $request->id; ?>"
                                            data-partner-name="<?php echo esc_attr($request->display_name); ?>"
                                            data-amount="<?php echo esc_attr($request->amount); ?>">
                                        ✅ Schválit
                                    </button>
                                    <button class="button button-secondary button-small reject-payout-request" 
                                            data-request-id="<?php echo $request->id; ?>">
                                        ❌ Zamítnout
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Pending Payouts -->
        <div class="rpp-section">
            <h2>💸 Čekající výplaty</h2>
            
            <?php if (empty($pending_payouts)): ?>
                <div class="rpp-empty-state">
                    <p>Žádné čekající výplaty.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>Email</th>
                            <th>Kód</th>
                            <th>Částka k výplatě</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_payouts as $payout): ?>
                            <tr>
                                <td><strong><?php echo esc_html($payout->display_name); ?></strong></td>
                                <td><?php echo esc_html($payout->user_email); ?></td>
                                <td><code><?php echo esc_html($payout->partner_code); ?></code></td>
                                <td><strong><?php echo number_format($payout->pending_amount, 0, ',', ' '); ?> Kč</strong></td>
                                <td>
                                    <button class="button button-primary process-payout" 
                                            data-partner-id="<?php echo $payout->id; ?>"
                                            data-partner-name="<?php echo esc_attr($payout->display_name); ?>"
                                            data-amount="<?php echo $payout->pending_amount; ?>">
                                        Vyplatit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Payout History -->
        <div class="rpp-section">
            <h2>📋 Historie výplat</h2>
            
            <?php if (empty($payouts)): ?>
                <div class="rpp-empty-state">
                    <p>Žádné výplaty zatím nebyly provedeny.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Partner</th>
                            <th>Částka</th>
                            <th>Metoda</th>
                            <th>Status</th>
                            <th>Poznámky</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payouts as $payout): ?>
                            <tr>
                                <td><?php echo date_i18n('j.n.Y H:i', strtotime($payout->created_at)); ?></td>
                                <td>
                                    <strong><?php echo esc_html($payout->display_name); ?></strong><br>
                                    <small><?php echo esc_html($payout->partner_code); ?></small>
                                </td>
                                <td><strong><?php echo number_format($payout->amount, 0, ',', ' '); ?> Kč</strong></td>
                                <td><?php echo esc_html($payout->payout_method); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($payout->status); ?>">
                                        <?php echo esc_html(ucfirst($payout->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($payout->notes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payout Modal -->
<div id="payout-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content">
        <div class="rpp-modal-header">
            <h3>💰 Zpracovat výplatu</h3>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <form method="post" action="" class="rpp-form">
            <?php wp_nonce_field('rpp_payout_nonce'); ?>
            <input type="hidden" name="action" value="process_payout">
            <input type="hidden" id="payout-partner-id" name="partner_id" value="">
            
            <div class="rpp-form-group">
                <label>Partner:</label>
                <div id="payout-partner-name" class="rpp-partner-display"></div>
            </div>
            
            <div class="rpp-form-group">
                <label for="payout-amount">Částka k výplatě:</label>
                <input type="number" id="payout-amount" name="amount" step="0.01" min="0" required class="rpp-input">
                <span class="rpp-currency">Kč</span>
            </div>
            
            <div class="rpp-form-group">
                <label for="payout-method">Metoda výplaty:</label>
                <select id="payout-method" name="method" required class="rpp-input">
                    <option value="bank_transfer">Bankovní převod</option>
                    <option value="paypal">PayPal</option>
                    <option value="cash">Hotovost</option>
                    <option value="other">Jiné</option>
                </select>
            </div>
            
            <div class="rpp-form-group">
                <label for="payout-notes">Poznámky:</label>
                <textarea id="payout-notes" name="notes" rows="3" class="rpp-textarea" placeholder="Volitelné poznámky k výplatě"></textarea>
            </div>
            
            <div class="rpp-modal-footer">
                <button type="button" class="rpp-btn rpp-btn-secondary" onclick="closePayoutModal()">Zrušit</button>
                <button type="submit" class="rpp-btn rpp-btn-primary">Zpracovat výplatu</button>
            </div>
        </form>
    </div>
</div>

<style>
.rpp-payouts-container {
    display: grid;
    gap: 24px;
}

.rpp-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8f5e8;
}

.rpp-section h2 {
    margin: 0 0 20px 0;
    color: #2d5a27;
    font-size: 24px;
    font-weight: 600;
    border-bottom: 2px solid #e8f5e8;
    padding-bottom: 12px;
}

.rpp-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.rpp-partner-display {
    background: #f8f9fa;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #e8f5e8;
    font-weight: 600;
    color: #2d5a27;
}

.rpp-currency {
    margin-left: 8px;
    color: #666;
    font-weight: 600;
}

.process-payout {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.process-payout:hover {
    background: #218838;
    border-color: #218838;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Approve payout request
    $('.approve-payout-request').on('click', function() {
        var requestId = $(this).data('request-id');
        var partnerName = $(this).data('partner-name');
        var amount = $(this).data('amount');
        
        if (!confirm('Schválit žádost o výplatu pro ' + partnerName + ' ve výši ' + amount + '?')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('Schvaluji...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_approve_payout_request',
                request_id: requestId,
                nonce: rpp_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Žádost byla schválena!');
                    location.reload();
                } else {
                    alert('Chyba: ' + response.data);
                    button.prop('disabled', false).text('✅ Schválit');
                }
            },
            error: function() {
                alert('Chyba při komunikaci se serverem');
                button.prop('disabled', false).text('✅ Schválit');
            }
        });
    });
    
    // Reject payout request
    $('.reject-payout-request').on('click', function() {
        var requestId = $(this).data('request-id');
        
        if (!confirm('Zamítnout tuto žádost o výplatu?')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('Zamítám...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_reject_payout_request',
                request_id: requestId,
                nonce: rpp_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Žádost byla zamítnuta!');
                    location.reload();
                } else {
                    alert('Chyba: ' + response.data);
                    button.prop('disabled', false).text('❌ Zamítnout');
                }
            },
            error: function() {
                alert('Chyba při komunikaci se serverem');
                button.prop('disabled', false).text('❌ Zamítnout');
            }
        });
    });
    
    // Process payout
    $('.process-payout').on('click', function() {
        var partnerId = $(this).data('partner-id');
        var partnerName = $(this).data('partner-name');
        var amount = $(this).data('amount');
        
        $('#payout-partner-id').val(partnerId);
        $('#payout-partner-name').text(partnerName);
        $('#payout-amount').val(amount);
        $('#payout-modal').show();
    });
    
    // Close modal
    $('.rpp-modal-close, .rpp-modal-overlay').on('click', function() {
        $('#payout-modal').hide();
    });
});

function closePayoutModal() {
    jQuery('#payout-modal').hide();
}
</script>