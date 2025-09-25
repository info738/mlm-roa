<div class="wrap">
    <h1><?php _e('Commissions', 'roanga-partner'); ?></h1>
    
    <div class="rpp-stats-grid">
        <div class="rpp-stat-box">
            <h3><?php _e('Total Commissions', 'roanga-partner'); ?></h3>
            <div class="rpp-stat-number">
                <?php 
                $total = 0;
                foreach ($commissions as $commission) {
                    $total += $commission->total;
                }
                echo wc_price($total);
                ?>
            </div>
        </div>
        
        <?php foreach ($commissions as $commission): ?>
            <div class="rpp-stat-box">
                <h3><?php echo esc_html(ucfirst($commission->status)); ?></h3>
                <div class="rpp-stat-number"><?php echo wc_price($commission->total); ?></div>
                <div class="rpp-stat-detail"><?php echo intval($commission->count); ?> <?php _e('items', 'roanga-partner'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2><?php _e('Recent Commissions', 'roanga-partner'); ?></h2>
    
    <?php
    $commission_class = new RPP_Commission();
    $recent_commissions = $commission_class->get_partner_commissions(null, null, 50, 0);
    ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Partner', 'roanga-partner'); ?></th>
                <th><?php _e('Amount', 'roanga-partner'); ?></th>
                <th><?php _e('Type', 'roanga-partner'); ?></th>
                <th><?php _e('Status', 'roanga-partner'); ?></th>
                <th><?php _e('Order ID', 'roanga-partner'); ?></th>
                <th><?php _e('Created', 'roanga-partner'); ?></th>
                <th><?php _e('Paid', 'roanga-partner'); ?></th>
                <th><?php _e('Actions', 'roanga-partner'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_commissions)): ?>
                <tr>
                    <td colspan="8"><?php _e('No commissions found.', 'roanga-partner'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($recent_commissions as $commission): ?>
                    <tr>
                        <td><?php echo esc_html($commission->partner_id); ?></td>
                        <td><?php echo wc_price($commission->amount); ?></td>
                        <td><?php echo esc_html($commission->type); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($commission->status); ?>">
                                <?php echo esc_html(ucfirst($commission->status)); ?>
                            </span>
                        </td>
                        <td><?php echo $commission->order_id ? '#' . $commission->order_id : '-'; ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->created_at)); ?></td>
                        <td><?php echo $commission->paid_at ? date_i18n(get_option('date_format'), strtotime($commission->paid_at)) : '-'; ?></td>
                        <td>
                            <?php if ($commission->status === 'pending'): ?>
                                <button class="button button-small approve-commission" data-commission-id="<?php echo $commission->id; ?>">
                                    <?php _e('Approve', 'roanga-partner'); ?>
                                </button>
                            <?php elseif ($commission->status === 'approved'): ?>
                                <button class="button button-small pay-commission" data-commission-id="<?php echo $commission->id; ?>">
                                    <?php _e('Mark as Paid', 'roanga-partner'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.rpp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rpp-stat-box {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    text-align: center;
}

.rpp-stat-box h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.rpp-stat-number {
    font-size: 24px;
    font-weight: 600;
    color: #2271b1;
}

.rpp-stat-detail {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
</style>