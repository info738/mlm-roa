<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$partner_class = new RPP_Partner();
$partner = $partner_class->get_partner_by_user($current_user->ID);

if (!$partner) {
    return;
}

$stats = $partner_class->get_partner_stats($partner->id);
$tracking_class = new RPP_Tracking();
$tracking_stats = $tracking_class->get_tracking_stats($partner->id);

// Get recent commissions
$commission_class = new RPP_Commission();
$recent_commissions = $commission_class->get_partner_commissions($partner->id, null, 10);

// Get referral link
$referral_link = $tracking_class->get_referral_link($partner->partner_code);
?>

<div class="rpp-partner-dashboard">
    <div class="rpp-welcome-message">
        <h3><?php printf(__('Vítejte, %s!', 'roanga-partner'), $current_user->display_name); ?></h3>
        <p><?php printf(__('Váš partnerský kód: %s', 'roanga-partner'), '<strong>' . $partner->partner_code . '</strong>'); ?></p>
    </div>
    
    <!-- Statistics Overview -->
    <div class="rpp-stats-grid">
        <div class="rpp-stat-card">
            <h4><?php _e('Celkové výdělky', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="total_earnings"><?php echo wc_price($stats['total_earnings']); ?></div>
        </div>
        
        <div class="rpp-stat-card">
            <h4><?php _e('K dispozici k výplatě', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="available_balance"><?php echo wc_price($stats['available_balance']); ?></div>
        </div>
        
        <div class="rpp-stat-card">
            <h4><?php _e('Vyplaceno celkem', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="total_payouts"><?php echo wc_price($stats['total_payouts']); ?></div>
        </div>
        
        <div class="rpp-stat-card">
            <h4><?php _e('Celkové kliky', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="total_clicks"><?php echo number_format($stats['total_clicks']); ?></div>
        </div>
        
        <div class="rpp-stat-card">
            <h4><?php _e('Konverze', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="total_conversions"><?php echo number_format($stats['total_conversions']); ?></div>
        </div>
        
        <div class="rpp-stat-card">
            <h4><?php _e('Konverzní poměr', 'roanga-partner'); ?></h4>
            <div class="rpp-stat-value" data-stat="conversion_rate"><?php echo $stats['conversion_rate']; ?>%</div>
        </div>
    </div>
    
    <!-- Referral Links Section -->
    <div class="rpp-section">
        <h4><?php _e('🔗 Vaše referenční odkazy', 'roanga-partner'); ?></h4>
        
        <div class="rpp-referral-link">
            <label for="referral-link"><?php _e('Hlavní referenční odkaz:', 'roanga-partner'); ?></label>
            <div class="rpp-link-container">
                <input type="text" id="referral-link" value="<?php echo esc_attr($referral_link); ?>" 
                       data-partner-code="<?php echo esc_attr($partner->partner_code); ?>" readonly>
                <button type="button" onclick="copyReferralLink()" class="rpp-button">
                    <?php _e('Kopírovat', 'roanga-partner'); ?>
                </button>
            </div>
        </div>
        
        <div class="rpp-link-generator">
            <label for="custom-page"><?php _e('Vytvořit vlastní odkaz:', 'roanga-partner'); ?></label>
            <input type="url" id="custom-page" placeholder="https://example.com/stranka">
            <button type="button" onclick="generateCustomLink()" class="rpp-button">
                <?php _e('Generovat', 'roanga-partner'); ?>
            </button>
        </div>
    </div>
    
    <!-- Payouts Section -->
    <div class="rpp-section" id="payouts-section">
        <h4><?php _e('💰 Výplaty', 'roanga-partner'); ?></h4>
        <div id="payouts-content">
            <div style="text-align: center; padding: 20px;">
                <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                <p><?php _e('Načítám data o výplatách...', 'roanga-partner'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Recent Commissions -->
    <div class="rpp-section">
        <h4><?php _e('📊 Nedávné provize', 'roanga-partner'); ?></h4>
        
        <?php if (empty($recent_commissions)): ?>
            <p><?php _e('Zatím žádné provize.', 'roanga-partner'); ?></p>
        <?php else: ?>
            <div class="rpp-table-container">
                <table class="rpp-table">
                    <thead>
                        <tr>
                            <th><?php _e('Datum', 'roanga-partner'); ?></th>
                            <th><?php _e('Částka', 'roanga-partner'); ?></th>
                            <th><?php _e('Typ', 'roanga-partner'); ?></th>
                            <th><?php _e('Status', 'roanga-partner'); ?></th>
                            <th><?php _e('Objednávka', 'roanga-partner'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_commissions as $commission): ?>
                            <tr>
                                <td><?php echo date_i18n('j.n.Y', strtotime($commission->created_at)); ?></td>
                                <td><?php echo wc_price($commission->amount); ?></td>
                                <td><?php echo esc_html(ucfirst($commission->type)); ?></td>
                                <td>
                                    <span class="rpp-status rpp-status-<?php echo esc_attr($commission->status); ?>">
                                        <?php echo esc_html(ucfirst($commission->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo $commission->order_id ? '#' . $commission->order_id : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Performance Metrics -->
    <?php if (!empty($tracking_stats['top_referrers'])): ?>
    <div class="rpp-section">
        <h4><?php _e('🌐 Nejlepší zdroje návštěvnosti', 'roanga-partner'); ?></h4>
        
        <div class="rpp-referrers-list">
            <?php foreach ($tracking_stats['top_referrers'] as $referrer): ?>
                <div class="rpp-referrer-item">
                    <a href="<?php echo esc_url($referrer->referrer_url); ?>" target="_blank" class="rpp-referrer-url">
                        <?php echo esc_html($referrer->referrer_url); ?>
                    </a>
                    <span class="rpp-referrer-count"><?php echo $referrer->count; ?> kliků</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Load payout data
    loadPayoutData();
    
    function loadPayoutData() {
        $.ajax({
            url: rpp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rpp_get_payout_data',
                nonce: rpp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderPayoutSection(response.data);
                } else {
                    $('#payouts-content').html('<div class="rpp-message rpp-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $('#payouts-content').html('<div class="rpp-message rpp-error"><?php _e("Chyba při načítání dat o výplatách. Zkuste obnovit stránku.", "roanga-partner"); ?></div>');
            }
        });
    }
    
    function renderPayoutSection(data) {
        var html = '';
        
        // Available balance section
        html += '<div class="rpp-available-balance">';
        html += '<div class="rpp-balance-card">';
        html += '<div class="rpp-balance-icon">💰</div>';
        html += '<div class="rpp-balance-info">';
        html += '<h3><?php _e("K dispozici k výplatě", "roanga-partner"); ?></h3>';
        html += '<div class="rpp-balance-amount">' + formatPrice(data.stats.available_balance) + '</div>';
        html += '<div class="rpp-progress-info"><?php _e("Minimální výplata:", "roanga-partner"); ?> ' + formatPrice(data.minimum_payout) + '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        // Bank info
        if (data.bank_account) {
            html += '<div class="rpp-bank-info">';
            html += '<strong><?php _e("Bankovní účet:", "roanga-partner"); ?></strong> ' + data.bank_account;
            if (data.bank_name) {
                html += '<div class="rpp-bank-name">' + data.bank_name + '</div>';
            }
            html += '</div>';
        }
        
        // Payout request form
        if (data.stats.available_balance >= data.minimum_payout) {
            html += '<div class="rpp-payout-section">';
            html += '<h4><?php _e("Požádat o výplatu", "roanga-partner"); ?></h4>';
            html += '<form id="rpp-payout-request-form" class="rpp-modern-form">';
            html += '<div class="rpp-form-row">';
            html += '<div class="rpp-form-group">';
            html += '<label class="rpp-form-label"><?php _e("Částka k výplatě", "roanga-partner"); ?></label>';
            html += '<div class="rpp-input-group">';
            html += '<input type="number" name="amount" class="rpp-form-input" min="' + data.minimum_payout + '" max="' + data.stats.available_balance + '" step="0.01" required>';
            html += '<span class="rpp-input-suffix">Kč</span>';
            html += '</div>';
            html += '<div class="rpp-form-help"><?php _e("Dostupné:", "roanga-partner"); ?> ' + formatPrice(data.stats.available_balance) + '</div>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="rpp-form-group">';
            html += '<label class="rpp-form-label"><?php _e("Faktura (volitelné)", "roanga-partner"); ?></label>';
            html += '<div class="rpp-file-upload-area">';
            html += '<input type="file" name="invoice" class="rpp-file-input" accept=".pdf,.jpg,.jpeg,.png">';
            html += '<div class="rpp-file-upload-text">';
            html += '<div class="rpp-file-icon">📄</div>';
            html += '<div><?php _e("Klikněte pro nahrání faktury", "roanga-partner"); ?></div>';
            html += '<small><?php _e("PDF, JPG, PNG (max 5MB)", "roanga-partner"); ?></small>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="rpp-form-group">';
            html += '<label class="rpp-form-label"><?php _e("Poznámky", "roanga-partner"); ?></label>';
            html += '<textarea name="notes" class="rpp-form-textarea" placeholder="<?php _e("Volitelné poznámky k výplatě", "roanga-partner"); ?>"></textarea>';
            html += '</div>';
            
            html += '<div class="rpp-form-actions">';
            html += '<button type="submit" class="rpp-submit-btn">';
            html += '<span class="rpp-btn-icon">💸</span>';
            html += '<?php _e("Požádat o výplatu", "roanga-partner"); ?>';
            html += '</button>';
            html += '</div>';
            html += '</form>';
            html += '</div>';
        } else {
            html += '<div class="rpp-notice rpp-warning">';
            html += '<?php _e("Pro výplatu potřebujete minimálně", "roanga-partner"); ?> ' + formatPrice(data.minimum_payout) + '. <?php _e("Aktuálně máte k dispozici", "roanga-partner"); ?> ' + formatPrice(data.stats.available_balance) + '.';
            html += '</div>';
        }
        
        // Payout history
        html += '<div class="rpp-payout-section">';
        html += '<h4><?php _e("Historie výplat", "roanga-partner"); ?></h4>';
        
        if (data.payouts && data.payouts.length > 0) {
            html += '<div class="rpp-payout-timeline">';
            data.payouts.forEach(function(payout) {
                html += '<div class="rpp-timeline-item">';
                
                // Status marker
                var statusColor = getStatusColor(payout.status);
                var statusIcon = getStatusIcon(payout.status);
                html += '<div class="rpp-timeline-marker" style="background: ' + statusColor + '">';
                html += '<div class="rpp-timeline-icon">' + statusIcon + '</div>';
                html += '</div>';
                
                // Content
                html += '<div class="rpp-timeline-content">';
                html += '<div class="rpp-timeline-header">';
                html += '<div class="rpp-timeline-title">';
                html += '<div class="rpp-timeline-amount">' + formatPrice(payout.amount) + '</div>';
                html += '<div class="rpp-timeline-status">' + getStatusText(payout.status) + '</div>';
                html += '</div>';
                html += '<div class="rpp-timeline-date">' + formatDate(payout.created_at) + '</div>';
                html += '</div>';
                
                if (payout.invoice_url) {
                    html += '<div class="rpp-timeline-invoice">';
                    html += '<a href="' + payout.invoice_url + '" target="_blank" class="rpp-invoice-btn">';
                    html += '<span class="rpp-invoice-icon">📄</span>';
                    html += '<?php _e("Stáhnout fakturu", "roanga-partner"); ?>';
                    html += '</a>';
                    html += '</div>';
                }
                
                if (payout.notes) {
                    html += '<div class="rpp-timeline-notes">';
                    html += '<div class="rpp-notes-label"><?php _e("Poznámky:", "roanga-partner"); ?></div>';
                    html += '<div class="rpp-notes-text">' + payout.notes + '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<div class="rpp-empty-state-card">';
            html += '<div class="rpp-empty-icon">💸</div>';
            html += '<h4><?php _e("Žádné výplaty", "roanga-partner"); ?></h4>';
            html += '<p><?php _e("Zatím jste nepožádali o žádnou výplatu.", "roanga-partner"); ?></p>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#payouts-content').html(html);
        
        // Bind form submit
        $('#rpp-payout-request-form').on('submit', function(e) {
            e.preventDefault();
            submitPayoutRequest(this);
        });
    }
    
    function submitPayoutRequest(form) {
        var $form = $(form);
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-right: 8px;"></span><?php _e("Odesílám...", "roanga-partner"); ?>');
        
        var formData = new FormData(form);
        formData.append('action', 'rpp_payout_request');
        formData.append('nonce', rpp_ajax.nonce);
        
        $.ajax({
            url: rpp_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('<?php _e("Žádost o výplatu byla úspěšně odeslána!", "roanga-partner"); ?>');
                    loadPayoutData(); // Reload data
                } else {
                    alert('<?php _e("Chyba:", "roanga-partner"); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e("Došlo k chybě při odesílání žádosti.", "roanga-partner"); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function formatPrice(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK'
        }).format(amount);
    }
    
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('cs-CZ');
    }
    
    function getStatusColor(status) {
        switch(status) {
            case 'requested': return '#ffc107';
            case 'approved': return '#28a745';
            case 'completed': return '#28a745';
            case 'rejected': return '#dc3545';
            default: return '#6c757d';
        }
    }
    
    function getStatusIcon(status) {
        switch(status) {
            case 'requested': return '⏳';
            case 'approved': return '✅';
            case 'completed': return '💰';
            case 'rejected': return '❌';
            default: return '❓';
        }
    }
    
    function getStatusText(status) {
        switch(status) {
            case 'requested': return '<?php _e("Čeká na schválení", "roanga-partner"); ?>';
            case 'approved': return '<?php _e("Schváleno", "roanga-partner"); ?>';
            case 'completed': return '<?php _e("Vyplaceno", "roanga-partner"); ?>';
            case 'rejected': return '<?php _e("Zamítnuto", "roanga-partner"); ?>';
            default: return status;
        }
    }
});
</script>