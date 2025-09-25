<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit_mlm_settings'])) {
    check_admin_referer('rpp_mlm_settings_nonce');
    
    $mlm_levels = array();
    if (isset($_POST['mlm_levels']) && is_array($_POST['mlm_levels'])) {
        foreach ($_POST['mlm_levels'] as $level => $rate) {
            $mlm_levels[$level] = floatval($rate);
        }
    }
    
    update_option('rpp_mlm_enabled', isset($_POST['mlm_enabled']));
    update_option('rpp_mlm_levels', $mlm_levels);
    update_option('rpp_mlm_max_levels', intval($_POST['mlm_max_levels']));
    update_option('rpp_mlm_require_sponsor', isset($_POST['mlm_require_sponsor']));
    
    echo '<div class="notice notice-success"><p>' . __('MLM nastavení bylo uloženo.', 'roanga-partner') . '</p></div>';
}

$mlm_enabled = get_option('rpp_mlm_enabled', false);
$mlm_levels = get_option('rpp_mlm_levels', array());
$mlm_max_levels = get_option('rpp_mlm_max_levels', 5);
$mlm_require_sponsor = get_option('rpp_mlm_require_sponsor', false);
?>

<div class="wrap rpp-admin-wrap">
    <div class="rpp-header">
        <h1 class="rpp-title">
            <span class="rpp-icon">🌳</span>
            <?php _e('MLM Nastavení', 'roanga-partner'); ?>
        </h1>
    </div>
    
    <form method="post" action="" class="rpp-settings-form">
        <?php wp_nonce_field('rpp_mlm_settings_nonce'); ?>
        
        <div class="rpp-settings-section">
            <h2><?php _e('Základní MLM nastavení', 'roanga-partner'); ?></h2>
            
            <div class="rpp-form-group">
                <label class="rpp-toggle">
                    <input type="checkbox" name="mlm_enabled" <?php checked($mlm_enabled); ?>>
                    <span class="rpp-toggle-slider"></span>
                    <span class="rpp-toggle-label"><?php _e('Povolit MLM strukturu', 'roanga-partner'); ?></span>
                </label>
                <p class="rpp-description"><?php _e('Zapne víceúrovňový marketing systém s provizemi z týmu.', 'roanga-partner'); ?></p>
            </div>
            
            <div class="rpp-form-group">
                <label class="rpp-toggle">
                    <input type="checkbox" name="mlm_require_sponsor" <?php checked($mlm_require_sponsor); ?>>
                    <span class="rpp-toggle-slider"></span>
                    <span class="rpp-toggle-label"><?php _e('Vyžadovat sponzora při registraci', 'roanga-partner'); ?></span>
                </label>
                <p class="rpp-description"><?php _e('Noví partneři se budou moci registrovat pouze s kódem sponzora.', 'roanga-partner'); ?></p>
            </div>
            
            <div class="rpp-form-row">
                <div class="rpp-form-group">
                    <label for="mlm_max_levels"><?php _e('Maximální počet úrovní:', 'roanga-partner'); ?></label>
                    <input type="number" id="mlm_max_levels" name="mlm_max_levels" value="<?php echo esc_attr($mlm_max_levels); ?>" min="1" max="10" class="rpp-input">
                    <p class="rpp-description"><?php _e('Kolik úrovní dolů se budou počítat provize.', 'roanga-partner'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="rpp-settings-section">
            <h2><?php _e('Provizní struktura podle úrovní', 'roanga-partner'); ?></h2>
            <p class="rpp-section-description"><?php _e('Nastavte provizní sazby pro jednotlivé úrovně v MLM struktuře. Úroveň 1 = přímí referálové, úroveň 2 = referálové vašich referálů, atd.', 'roanga-partner'); ?></p>
            
            <div class="rpp-mlm-levels">
                <?php for ($i = 1; $i <= $mlm_max_levels; $i++): ?>
                    <div class="rpp-mlm-level">
                        <div class="rpp-level-header">
                            <span class="rpp-level-number"><?php echo $i; ?></span>
                            <h4><?php printf(__('Úroveň %d', 'roanga-partner'), $i); ?></h4>
                        </div>
                        <div class="rpp-level-content">
                            <div class="rpp-form-group">
                                <label for="mlm_level_<?php echo $i; ?>"><?php _e('Provizní sazba (%):', 'roanga-partner'); ?></label>
                                <input type="number" 
                                       id="mlm_level_<?php echo $i; ?>" 
                                       name="mlm_levels[<?php echo $i; ?>]" 
                                       value="<?php echo isset($mlm_levels[$i]) ? esc_attr($mlm_levels[$i]) : '0'; ?>" 
                                       min="0" 
                                       max="100" 
                                       step="0.1" 
                                       class="rpp-input rpp-input-small">
                            </div>
                            <div class="rpp-level-description">
                                <?php
                                switch ($i) {
                                    case 1:
                                        _e('Provize z prodejů vašich přímých referálů', 'roanga-partner');
                                        break;
                                    case 2:
                                        _e('Provize z prodejů referálů vašich referálů', 'roanga-partner');
                                        break;
                                    default:
                                        printf(__('Provize z prodejů %d. úrovně pod vámi', 'roanga-partner'), $i);
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="rpp-settings-section">
            <h2><?php _e('Příklad výpočtu', 'roanga-partner'); ?></h2>
            <div class="rpp-example-calculation">
                <div class="rpp-example-scenario">
                    <h4><?php _e('Scénář:', 'roanga-partner'); ?></h4>
                    <p><?php _e('Partner na 3. úrovni pod vámi prodá produkt za 1000 Kč', 'roanga-partner'); ?></p>
                </div>
                <div class="rpp-example-breakdown" id="commission-example">
                    <!-- Bude naplněno JavaScriptem -->
                </div>
            </div>
        </div>
        
        <div class="rpp-form-actions">
            <button type="submit" name="submit_mlm_settings" class="rpp-btn rpp-btn-primary rpp-btn-large">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Uložit MLM nastavení', 'roanga-partner'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.rpp-settings-form {
    max-width: 1000px;
}

.rpp-settings-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8f5e8;
}

.rpp-settings-section h2 {
    margin: 0 0 20px 0;
    color: #2d5a27;
    font-size: 24px;
    font-weight: 600;
    border-bottom: 2px solid #e8f5e8;
    padding-bottom: 12px;
}

.rpp-section-description {
    color: #666;
    margin-bottom: 24px;
    line-height: 1.6;
}

.rpp-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-bottom: 8px;
}

.rpp-toggle input[type="checkbox"] {
    display: none;
}

.rpp-toggle-slider {
    position: relative;
    width: 50px;
    height: 24px;
    background: #ccc;
    border-radius: 24px;
    transition: all 0.3s ease;
    margin-right: 12px;
}

.rpp-toggle-slider:before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: all 0.3s ease;
}

.rpp-toggle input:checked + .rpp-toggle-slider {
    background: #4a7c59;
}

.rpp-toggle input:checked + .rpp-toggle-slider:before {
    transform: translateX(26px);
}

.rpp-toggle-label {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-description {
    color: #666;
    font-size: 13px;
    margin: 4px 0 0 0;
    line-height: 1.4;
}

.rpp-input-small {
    width: 120px;
}

.rpp-mlm-levels {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.rpp-mlm-level {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #e8f5e8;
    transition: all 0.3s ease;
}

.rpp-mlm-level:hover {
    border-color: #4a7c59;
    box-shadow: 0 4px 15px rgba(74, 124, 89, 0.1);
}

.rpp-level-header {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.rpp-level-number {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
    color: #2d5a27;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 12px;
}

.rpp-level-header h4 {
    margin: 0;
    color: #2d5a27;
    font-size: 18px;
}

.rpp-level-description {
    color: #666;
    font-size: 13px;
    margin-top: 8px;
    line-height: 1.4;
}

.rpp-example-calculation {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 24px;
    border-left: 4px solid #4a7c59;
}

.rpp-example-scenario h4 {
    color: #2d5a27;
    margin: 0 0 8px 0;
}

.rpp-example-breakdown {
    margin-top: 16px;
}

.rpp-commission-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    margin-bottom: 8px;
    border-left: 3px solid #4a7c59;
}

.rpp-commission-level {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-commission-amount {
    font-weight: 700;
    color: #d4af37;
}

.rpp-form-actions {
    text-align: center;
    padding: 30px 0;
}

.rpp-btn-large {
    padding: 16px 32px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .rpp-mlm-levels {
        grid-template-columns: 1fr;
    }
    
    .rpp-form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update example calculation when rates change
    $('input[name^="mlm_levels"]').on('input', updateExampleCalculation);
    
    // Update max levels
    $('#mlm_max_levels').on('change', function() {
        var maxLevels = parseInt($(this).val());
        $('.rpp-mlm-level').each(function(index) {
            if (index + 1 <= maxLevels) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        updateExampleCalculation();
    });
    
    // Initial calculation
    updateExampleCalculation();
    
    function updateExampleCalculation() {
        var saleAmount = 1000;
        var breakdown = '';
        var totalCommission = 0;
        
        $('input[name^="mlm_levels"]:visible').each(function(index) {
            var level = index + 1;
            var rate = parseFloat($(this).val()) || 0;
            var commission = (saleAmount * rate) / 100;
            
            if (rate > 0) {
                totalCommission += commission;
                breakdown += '<div class="rpp-commission-item">' +
                    '<span class="rpp-commission-level">Úroveň ' + level + ' (' + rate + '%)</span>' +
                    '<span class="rpp-commission-amount">' + commission.toFixed(0) + ' Kč</span>' +
                    '</div>';
            }
        });
        
        if (totalCommission > 0) {
            breakdown += '<div class="rpp-commission-item" style="border-left-color: #d4af37; background: #fffbf0;">' +
                '<span class="rpp-commission-level"><strong>Celková provize</strong></span>' +
                '<span class="rpp-commission-amount"><strong>' + totalCommission.toFixed(0) + ' Kč</strong></span>' +
                '</div>';
        } else {
            breakdown = '<p style="color: #666; text-align: center; margin: 20px 0;">Nastavte provizní sazby pro zobrazení příkladu.</p>';
        }
        
        $('#commission-example').html(breakdown);
    }
});
</script>