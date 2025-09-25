<?php
if (!defined('ABSPATH')) {
    exit;
}

$stats = RPP_Database::get_dashboard_stats();
$tracking_class = new RPP_Tracking();

// Get recent tracking data
global $wpdb;
$recent_tracking = $wpdb->get_results("
    SELECT t.*, p.partner_code, u.display_name, u.user_email
    FROM {$wpdb->prefix}rpp_tracking t
    LEFT JOIN {$wpdb->prefix}rpp_partners p ON t.partner_id = p.id
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    ORDER BY t.created_at DESC
    LIMIT 100
");

// Get top referrers
$top_referrers = $wpdb->get_results("
    SELECT referrer_url, COUNT(*) as count
    FROM {$wpdb->prefix}rpp_tracking
    WHERE referrer_url != '' AND referrer_url IS NOT NULL
    GROUP BY referrer_url
    ORDER BY count DESC
    LIMIT 20
");

// Get conversion stats by partner
$partner_stats = $wpdb->get_results("
    SELECT p.partner_code, u.display_name,
           COUNT(CASE WHEN t.conversion_type = 'click' THEN 1 END) as clicks,
           COUNT(CASE WHEN t.conversion_type != 'click' THEN 1 END) as conversions,
           ROUND(
               CASE 
                   WHEN COUNT(CASE WHEN t.conversion_type = 'click' THEN 1 END) > 0 
                   THEN (COUNT(CASE WHEN t.conversion_type != 'click' THEN 1 END) * 100.0 / COUNT(CASE WHEN t.conversion_type = 'click' THEN 1 END))
                   ELSE 0 
               END, 2
           ) as conversion_rate
    FROM {$wpdb->prefix}rpp_partners p
    LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
    LEFT JOIN {$wpdb->prefix}rpp_tracking t ON p.id = t.partner_id
    WHERE p.status = 'approved'
    GROUP BY p.id, p.partner_code, u.display_name
    HAVING clicks > 0 OR conversions > 0
    ORDER BY conversion_rate DESC, clicks DESC
    LIMIT 20
");
?>

<div class="wrap rpp-admin-wrap">
    <div class="rpp-header">
        <h1 class="rpp-title">
            <span class="rpp-icon">🔍</span>
            <?php _e('Tracking & Analytics', 'roanga-partner'); ?>
        </h1>
        <button class="rpp-btn rpp-btn-primary" id="refresh-tracking-data">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Obnovit data', 'roanga-partner'); ?>
        </button>
    </div>
    
    <!-- Stats Overview -->
    <div class="rpp-stats-grid">
        <div class="rpp-stat-box">
            <h3><?php _e('Celkové kliky', 'roanga-partner'); ?></h3>
            <div class="rpp-stat-number"><?php echo number_format($stats['total_clicks'], 0, ',', ' '); ?></div>
        </div>
        
        <div class="rpp-stat-box">
            <h3><?php _e('Celkové konverze', 'roanga-partner'); ?></h3>
            <div class="rpp-stat-number"><?php echo number_format($stats['total_conversions'], 0, ',', ' '); ?></div>
        </div>
        
        <div class="rpp-stat-box">
            <h3><?php _e('Konverzní poměr', 'roanga-partner'); ?></h3>
            <div class="rpp-stat-number">
                <?php 
                $conversion_rate = $stats['total_clicks'] > 0 ? round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2) : 0;
                echo $conversion_rate; 
                ?>%
            </div>
        </div>
        
        <div class="rpp-stat-box">
            <div class="rpp-stat-number"><?php echo number_format($stats['this_month_clicks'], 0, ',', ' '); ?></div>
            <div class="rpp-stat-detail"><?php _e('Kliky tento měsíc', 'roanga-partner'); ?></div>
        </div>
    </div>
    
    <div class="rpp-tracking-container">
        <!-- Recent Tracking Activity -->
        <div class="rpp-section">
            <h2>📊 Nedávná aktivita</h2>
            
            <?php if (empty($recent_tracking)): ?>
                <div class="rpp-empty-state">
                    <p>Žádná tracking data zatím nejsou k dispozici.</p>
                </div>
            <?php else: ?>
                <div class="rpp-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Datum', 'roanga-partner'); ?></th>
                                <th><?php _e('Partner', 'roanga-partner'); ?></th>
                                <th><?php _e('Typ', 'roanga-partner'); ?></th>
                                <th><?php _e('Zdroj', 'roanga-partner'); ?></th>
                                <th><?php _e('Cílová stránka', 'roanga-partner'); ?></th>
                                <th><?php _e('IP adresa', 'roanga-partner'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tracking as $track): ?>
                                <tr>
                                    <td><?php echo date_i18n('j.n.Y H:i', strtotime($track->created_at)); ?></td>
                                    <td>
                                        <?php if ($track->display_name): ?>
                                            <strong><?php echo esc_html($track->display_name); ?></strong><br>
                                            <small><?php echo esc_html($track->partner_code); ?></small>
                                        <?php else: ?>
                                            <em>Neznámý partner</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="rpp-tracking-type rpp-type-<?php echo esc_attr($track->conversion_type); ?>">
                                            <?php echo esc_html(ucfirst($track->conversion_type)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($track->referrer_url): ?>
                                            <a href="<?php echo esc_url($track->referrer_url); ?>" target="_blank" title="<?php echo esc_attr($track->referrer_url); ?>">
                                                <?php echo esc_html(wp_trim_words($track->referrer_url, 6, '...')); ?>
                                            </a>
                                        <?php else: ?>
                                            <em>Přímý přístup</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($track->landing_page); ?></td>
                                    <td><code><?php echo esc_html($track->visitor_ip); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Partner Performance -->
        <div class="rpp-section">
            <h2>🏆 Výkonnost partnerů</h2>
            
            <?php if (empty($partner_stats)): ?>
                <div class="rpp-empty-state">
                    <p>Žádná data o výkonnosti partnerů.</p>
                </div>
            <?php else: ?>
                <div class="rpp-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Partner', 'roanga-partner'); ?></th>
                                <th><?php _e('Kód', 'roanga-partner'); ?></th>
                                <th><?php _e('Kliky', 'roanga-partner'); ?></th>
                                <th><?php _e('Konverze', 'roanga-partner'); ?></th>
                                <th><?php _e('Konverzní poměr', 'roanga-partner'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partner_stats as $stat): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($stat->display_name); ?></strong></td>
                                    <td><code><?php echo esc_html($stat->partner_code); ?></code></td>
                                    <td><?php echo number_format($stat->clicks, 0, ',', ' '); ?></td>
                                    <td><?php echo number_format($stat->conversions, 0, ',', ' '); ?></td>
                                    <td>
                                        <strong style="color: <?php echo $stat->conversion_rate > 5 ? '#28a745' : ($stat->conversion_rate > 2 ? '#ffc107' : '#dc3545'); ?>">
                                            <?php echo $stat->conversion_rate; ?>%
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Referrers -->
        <div class="rpp-section">
            <h2>🌐 Nejlepší zdroje návštěvnosti</h2>
            
            <?php if (empty($top_referrers)): ?>
                <div class="rpp-empty-state">
                    <p>Žádné externí zdroje návštěvnosti.</p>
                </div>
            <?php else: ?>
                <div class="rpp-referrers-grid">
                    <?php foreach ($top_referrers as $referrer): ?>
                        <div class="rpp-referrer-card">
                            <div class="rpp-referrer-url">
                                <a href="<?php echo esc_url($referrer->referrer_url); ?>" target="_blank">
                                    <?php echo esc_html(wp_trim_words($referrer->referrer_url, 8, '...')); ?>
                                </a>
                            </div>
                            <div class="rpp-referrer-count">
                                <strong><?php echo number_format($referrer->count, 0, ',', ' '); ?></strong> kliků
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rpp-tracking-container {
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

.rpp-table-container {
    overflow-x: auto;
}

.rpp-tracking-type {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.rpp-type-click { background: #e3f2fd; color: #1976d2; }
.rpp-type-sale { background: #e8f5e8; color: #2d5a27; }
.rpp-type-conversion { background: #fff3e0; color: #f57c00; }

.rpp-referrers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.rpp-referrer-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    border-left: 4px solid #4a7c59;
    transition: transform 0.2s ease;
}

.rpp-referrer-card:hover {
    transform: translateY(-2px);
}

.rpp-referrer-url {
    margin-bottom: 8px;
}

.rpp-referrer-url a {
    color: #2d5a27;
    text-decoration: none;
    font-weight: 600;
}

.rpp-referrer-url a:hover {
    text-decoration: underline;
}

.rpp-referrer-count {
    color: #d4af37;
    font-size: 14px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#refresh-tracking-data').on('click', function() {
        location.reload();
    });
});
</script>