<?php
if (!defined('ABSPATH')) {
    exit;
}

$partner_id = intval($_GET['id']);
$partner_class = new RPP_Partner();
$partner = $partner_class->get_partner_detail($partner_id);

if (!$partner) {
    wp_die(__('Partner not found.', 'roanga-partner'));
}

$commission_class = new RPP_Commission();
$tracking_class = new RPP_Tracking();
$groups_class = new RPP_Partner_Groups();

$partner_stats = $partner_class->get_partner_stats($partner_id);
$recent_commissions = $commission_class->get_partner_commissions($partner_id, null, 20);
$tracking_stats = $tracking_class->get_tracking_stats($partner_id);
$all_groups = $groups_class->get_all_groups();
?>

<div class="wrap">
    <h1><?php printf(__('Partner Details: %s', 'roanga-partner'), esc_html($partner->display_name)); ?></h1>
    
    <div class="rpp-partner-detail-container">
        <!-- Partner Information -->
        <div class="rpp-detail-section">
            <h2><?php _e('Partner Information', 'roanga-partner'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('Name:', 'roanga-partner'); ?></th>
                    <td><?php echo esc_html($partner->display_name); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Email:', 'roanga-partner'); ?></th>
                    <td><a href="mailto:<?php echo esc_attr($partner->user_email); ?>"><?php echo esc_html($partner->user_email); ?></a></td>
                </tr>
                <tr>
                    <th><?php _e('Partner Code:', 'roanga-partner'); ?></th>
                    <td><code><?php echo esc_html($partner->partner_code); ?></code></td>
                </tr>
                <tr>
                    <th><?php _e('Status:', 'roanga-partner'); ?></th>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr($partner->status); ?>">
                            <?php echo esc_html(ucfirst($partner->status)); ?>
                        </span>
                        <?php if ($partner->status === 'pending'): ?>
                            <button class="button approve-partner" data-partner-id="<?php echo $partner->id; ?>">
                                <?php _e('Approve', 'roanga-partner'); ?>
                            </button>
                            <button class="button reject-partner" data-partner-id="<?php echo $partner->id; ?>">
                                <?php _e('Reject', 'roanga-partner'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Group:', 'roanga-partner'); ?></th>
                    <td>
                        <select id="partner-group" data-partner-id="<?php echo $partner->id; ?>">
                            <?php foreach ($all_groups as $group): ?>
                                <option value="<?php echo $group->id; ?>" <?php selected($partner->group_id, $group->id); ?>>
                                    <?php echo esc_html($group->name); ?> (<?php echo $group->commission_rate; ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button" id="update-partner-group"><?php _e('Update Group', 'roanga-partner'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Commission Rate:', 'roanga-partner'); ?></th>
                    <td>
                        <input type="number" id="commission-rate" value="<?php echo esc_attr($partner->commission_rate); ?>" 
                               min="0" max="100" step="0.1" style="width: 80px;">%
                        <button class="button" id="update-commission-rate" data-partner-id="<?php echo $partner->id; ?>">
                            <?php _e('Update Rate', 'roanga-partner'); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Registered:', 'roanga-partner'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($partner->created_at)); ?></td>
                </tr>
                <?php if ($partner->approved_at): ?>
                <tr>
                    <th><?php _e('Approved:', 'roanga-partner'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($partner->approved_at)); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Application Details -->
        <div class="rpp-detail-section">
            <h2><?php _e('Application Details', 'roanga-partner'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php _e('Website:', 'roanga-partner'); ?></th>
                    <td>
                        <?php 
                        $website = get_user_meta($partner->user_id, 'rpp_website', true);
                        if ($website): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a>
                        <?php else: ?>
                            <em><?php _e('Not provided', 'roanga-partner'); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Social Media:', 'roanga-partner'); ?></th>
                    <td>
                        <?php 
                        $social_media = get_user_meta($partner->user_id, 'rpp_social_media', true);
                        echo $social_media ? nl2br(esc_html($social_media)) : '<em>' . __('Not provided', 'roanga-partner') . '</em>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Experience:', 'roanga-partner'); ?></th>
                    <td>
                        <?php 
                        $experience = get_user_meta($partner->user_id, 'rpp_experience', true);
                        echo $experience ? nl2br(esc_html($experience)) : '<em>' . __('Not provided', 'roanga-partner') . '</em>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Target Audience:', 'roanga-partner'); ?></th>
                    <td>
                        <?php 
                        $audience = get_user_meta($partner->user_id, 'rpp_audience', true);
                        echo $audience ? nl2br(esc_html($audience)) : '<em>' . __('Not provided', 'roanga-partner') . '</em>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Motivation:', 'roanga-partner'); ?></th>
                    <td>
                        <?php 
                        $motivation = get_user_meta($partner->user_id, 'rpp_motivation', true);
                        echo $motivation ? nl2br(esc_html($motivation)) : '<em>' . __('Not provided', 'roanga-partner') . '</em>';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Performance Statistics -->
        <div class="rpp-detail-section">
            <h2><?php _e('Performance Statistics', 'roanga-partner'); ?></h2>
            
            <div class="rpp-stats-grid">
                <div class="rpp-stat-box">
                    <h3><?php _e('Total Earnings', 'roanga-partner'); ?></h3>
                    <div class="rpp-stat-number"><?php echo wc_price($partner_stats['total_commissions'] ?? 0); ?></div>
                </div>
                
                <div class="rpp-stat-box">
                    <h3><?php _e('Paid Commissions', 'roanga-partner'); ?></h3>
                    <div class="rpp-stat-number"><?php echo wc_price($partner_stats['paid_commissions'] ?? 0); ?></div>
                </div>
                
                <div class="rpp-stat-box">
                    <h3><?php _e('Total Clicks', 'roanga-partner'); ?></h3>
                    <div class="rpp-stat-number"><?php echo intval($tracking_stats['clicks'] ?? 0); ?></div>
                </div>
                
                <div class="rpp-stat-box">
                    <h3><?php _e('Conversions', 'roanga-partner'); ?></h3>
                    <div class="rpp-stat-number"><?php echo intval($tracking_stats['conversions'] ?? 0); ?></div>
                </div>
                
                <div class="rpp-stat-box">
                    <h3><?php _e('Conversion Rate', 'roanga-partner'); ?></h3>
                    <div class="rpp-stat-number"><?php echo $tracking_stats['conversion_rate'] ?? 0; ?>%</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Commissions -->
        <div class="rpp-detail-section">
            <h2><?php _e('Recent Commissions', 'roanga-partner'); ?></h2>
            
            <?php if (empty($recent_commissions)): ?>
                <p><?php _e('No commissions yet.', 'roanga-partner'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'roanga-partner'); ?></th>
                            <th><?php _e('Amount', 'roanga-partner'); ?></th>
                            <th><?php _e('Type', 'roanga-partner'); ?></th>
                            <th><?php _e('Status', 'roanga-partner'); ?></th>
                            <th><?php _e('Order ID', 'roanga-partner'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_commissions as $commission): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->created_at)); ?></td>
                                <td><?php echo wc_price($commission->amount); ?></td>
                                <td><?php echo esc_html(ucfirst($commission->type)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($commission->status); ?>">
                                        <?php echo esc_html(ucfirst($commission->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo $commission->order_id ? '#' . $commission->order_id : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="rpp-detail-section">
            <h2><?php _e('Actions', 'roanga-partner'); ?></h2>
            
            <p>
                <button class="button" id="send-test-email" data-partner-id="<?php echo $partner->id; ?>">
                    <?php _e('Send Test Email', 'roanga-partner'); ?>
                </button>
                
                <button class="button" id="generate-report" data-partner-id="<?php echo $partner->id; ?>">
                    <?php _e('Generate Performance Report', 'roanga-partner'); ?>
                </button>
                
                <?php if ($partner->status === 'approved'): ?>
                <button class="button button-secondary" id="suspend-partner" data-partner-id="<?php echo $partner->id; ?>">
                    <?php _e('Suspend Partner', 'roanga-partner'); ?>
                </button>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update partner group
    $('#update-partner-group').on('click', function() {
        var partnerId = $('#partner-group').data('partner-id');
        var groupId = $('#partner-group').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_update_partner_group',
                partner_id: partnerId,
                group_id: groupId,
                nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Update commission rate
    $('#update-commission-rate').on('click', function() {
        var partnerId = $(this).data('partner-id');
        var rate = $('#commission-rate').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_update_commission_rate',
                partner_id: partnerId,
                commission_rate: rate,
                nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Commission rate updated successfully!');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Send test email
    $('#send-test-email').on('click', function() {
        var partnerId = $(this).data('partner-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_send_test_email',
                partner_id: partnerId,
                nonce: '<?php echo wp_create_nonce('rpp_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Test email sent successfully!');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
</script>

<style>
.rpp-partner-detail-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.rpp-detail-section {
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.rpp-detail-section:nth-child(3),
.rpp-detail-section:nth-child(4),
.rpp-detail-section:nth-child(5) {
    grid-column: 1 / -1;
}

.rpp-detail-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

@media (max-width: 768px) {
    .rpp-partner-detail-container {
        grid-template-columns: 1fr;
    }
    
    .rpp-detail-section:nth-child(3),
    .rpp-detail-section:nth-child(4),
    .rpp-detail-section:nth-child(5) {
        grid-column: 1;
    }
}
</style>