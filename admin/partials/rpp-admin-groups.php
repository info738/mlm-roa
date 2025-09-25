<?php
if (!defined('ABSPATH')) {
    exit;
}

$groups_class = new RPP_Partner_Groups();
$groups = $groups_class->get_all_groups();
?>

<div class="wrap rpp-admin-wrap">
    <div class="rpp-header">
        <h1 class="rpp-title">
            <span class="rpp-icon">👥</span>
            <?php _e('Skupiny partnerů', 'roanga-partner'); ?>
        </h1>
        <button class="rpp-btn rpp-btn-primary" id="add-new-group">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Přidat novou skupinu', 'roanga-partner'); ?>
        </button>
    </div>
    
    <div class="rpp-groups-container">
        <?php foreach ($groups as $group): ?>
            <div class="rpp-group-card" data-group-id="<?php echo $group->id; ?>">
                <div class="rpp-group-header">
                    <div class="rpp-group-info">
                        <h3 class="rpp-group-name"><?php echo esc_html($group->name); ?></h3>
                        <span class="rpp-group-rate"><?php echo $group->commission_rate; ?>% provize</span>
                    </div>
                    <div class="rpp-group-actions">
                        <button class="rpp-btn rpp-btn-small rpp-btn-secondary edit-group" data-group-id="<?php echo $group->id; ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Upravit', 'roanga-partner'); ?>
                        </button>
                        <?php if ($group->id != 1): ?>
                        <button class="rpp-btn rpp-btn-small rpp-btn-danger delete-group" data-group-id="<?php echo $group->id; ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Smazat', 'roanga-partner'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="rpp-group-content">
                    <p class="rpp-group-description"><?php echo esc_html($group->description); ?></p>
                    
                    <div class="rpp-group-stats">
                        <div class="rpp-stat-item">
                            <span class="rpp-stat-number"><?php echo $group->partner_count; ?></span>
                            <span class="rpp-stat-label"><?php _e('Partneři', 'roanga-partner'); ?></span>
                        </div>
                        
                        <?php
                        $performance = $groups_class->get_group_performance($group->id);
                        ?>
                        <div class="rpp-stat-item">
                            <span class="rpp-stat-number"><?php echo number_format($performance['total_earnings'], 0, ',', ' '); ?> Kč</span>
                            <span class="rpp-stat-label"><?php _e('Celkové výdělky', 'roanga-partner'); ?></span>
                        </div>
                        
                        <div class="rpp-stat-item">
                            <span class="rpp-stat-number"><?php echo $performance['avg_conversion_rate']; ?>%</span>
                            <span class="rpp-stat-label"><?php _e('Konverzní poměr', 'roanga-partner'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($group->benefits)): ?>
                    <div class="rpp-group-benefits">
                        <h4><?php _e('Výhody:', 'roanga-partner'); ?></h4>
                        <ul class="rpp-benefits-list">
                            <?php foreach ($group->benefits as $benefit): ?>
                                <li><span class="dashicons dashicons-yes-alt"></span><?php echo esc_html($benefit); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Group Modal -->
<div id="group-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content">
        <div class="rpp-modal-header">
            <h2 id="modal-title"><?php _e('Přidat novou skupinu', 'roanga-partner'); ?></h2>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        
        <form id="group-form" class="rpp-form">
            <input type="hidden" id="group-id" name="group_id" value="">
            
            <div class="rpp-form-group">
                <label class="rpp-toggle">
                    <input type="checkbox" id="group-volume-based" name="volume_based">
                    <span class="rpp-toggle-slider"></span>
                    <span class="rpp-toggle-label"><?php _e('Obratová skupina', 'roanga-partner'); ?></span>
                </label>
                <p class="rpp-description"><?php _e('Partneři v této skupině budou odměňováni z celkového obratu svého týmu.', 'roanga-partner'); ?></p>
            </div>
            
            <div class="rpp-form-row">
                <div class="rpp-form-group">
                    <label for="group-name"><?php _e('Název skupiny:', 'roanga-partner'); ?></label>
                    <input type="text" id="group-name" name="name" required class="rpp-input">
                </div>
                
                <div class="rpp-form-group">
                    <label for="group-commission-rate"><?php _e('Provizní sazba (%):', 'roanga-partner'); ?></label>
                    <input type="number" id="group-commission-rate" name="commission_rate" min="0" max="100" step="0.1" required class="rpp-input">
                </div>
            </div>
            
            <div class="rpp-form-group">
                <label for="group-description"><?php _e('Popis:', 'roanga-partner'); ?></label>
                <textarea id="group-description" name="description" rows="3" class="rpp-textarea"></textarea>
            </div>
            
            <!-- Volume-based settings -->
            <div class="rpp-volume-settings" style="display: none;">
                <div class="rpp-volume-section">
                    <h4><?php _e('⚙️ Nastavení obratové skupiny', 'roanga-partner'); ?></h4>
                    
                    <div class="rpp-form-row">
                        <div class="rpp-form-group">
                            <label for="group-volume-percentage"><?php _e('Procento z obratu (%):', 'roanga-partner'); ?></label>
                            <input type="number" id="group-volume-percentage" name="volume_percentage" min="0" max="100" step="0.1" class="rpp-input" placeholder="5.0">
                            <p class="rpp-description"><?php _e('Kolik procent z celkového obratu týmu dostane partner.', 'roanga-partner'); ?></p>
                        </div>
                        
                        <div class="rpp-form-group">
                            <label for="group-restart-period"><?php _e('Období restartů:', 'roanga-partner'); ?></label>
                            <select id="group-restart-period" name="restart_period" class="rpp-input">
                                <option value="weekly"><?php _e('Týdně', 'roanga-partner'); ?></option>
                                <option value="monthly" selected><?php _e('Měsíčně', 'roanga-partner'); ?></option>
                                <option value="quarterly"><?php _e('Kvartálně', 'roanga-partner'); ?></option>
                                <option value="semi-annually"><?php _e('Půlročně', 'roanga-partner'); ?></option>
                                <option value="annually"><?php _e('Ročně', 'roanga-partner'); ?></option>
                            </select>
                            <p class="rpp-description"><?php _e('Jak často se resetují bonusové prahy.', 'roanga-partner'); ?></p>
                        </div>
                    </div>
                    
                    <div class="rpp-form-group">
                        <label class="rpp-toggle">
                            <input type="checkbox" id="group-disable-mlm" name="disable_mlm">
                            <span class="rpp-toggle-slider"></span>
                            <span class="rpp-toggle-label"><?php _e('Zakázat MLM provize', 'roanga-partner'); ?></span>
                        </label>
                        <p class="rpp-description"><?php _e('Partneři v této skupině budou dostávat pouze obratové odměny, žádné MLM provize.', 'roanga-partner'); ?></p>
                    </div>
                </div>
                
                <div class="rpp-bonus-section">
                    <h4><?php _e('🎯 Bonusové prahy', 'roanga-partner'); ?></h4>
                    <p class="rpp-section-description"><?php _e('Nastavte bonusy při dosažení určitého obratu v daném období.', 'roanga-partner'); ?></p>
                    
                    <div id="bonus-thresholds-container" class="rpp-bonus-container">
                        <div class="rpp-bonus-item">
                            <div class="rpp-form-row">
                                <div class="rpp-form-group">
                                    <label><?php _e('Obrat (Kč):', 'roanga-partner'); ?></label>
                                    <input type="number" name="bonus_turnover[]" min="0" step="1000" class="rpp-input" placeholder="50000">
                                </div>
                                <div class="rpp-form-group">
                                    <label><?php _e('Bonus (Kč):', 'roanga-partner'); ?></label>
                                    <input type="number" name="bonus_amount[]" min="0" step="100" class="rpp-input" placeholder="5000">
                                </div>
                                <div class="rpp-form-group">
                                    <button type="button" class="rpp-btn rpp-btn-small rpp-btn-danger remove-bonus">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="rpp-form-group">
                                <label><?php _e('Restart:', 'roanga-partner'); ?></label>
                                <select name="bonus_restart[]" class="rpp-input">
                                    <option value="weekly"><?php _e('Týdně', 'roanga-partner'); ?></option>
                                    <option value="monthly" selected><?php _e('Měsíčně', 'roanga-partner'); ?></option>
                                    <option value="quarterly"><?php _e('Kvartálně', 'roanga-partner'); ?></option>
                                    <option value="semi-annually"><?php _e('Půlročně', 'roanga-partner'); ?></option>
                                    <option value="annually"><?php _e('Ročně', 'roanga-partner'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="rpp-btn rpp-btn-small rpp-btn-secondary" id="add-bonus">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Přidat bonusový práh', 'roanga-partner'); ?>
                    </button>
                </div>
            </div>
            
            <div class="rpp-form-group">
                <label><?php _e('Výhody skupiny:', 'roanga-partner'); ?></label>
                <div id="benefits-container" class="rpp-benefits-container">
                    <div class="rpp-benefit-item">
                        <input type="text" name="benefits[]" placeholder="<?php _e('Zadejte výhodu', 'roanga-partner'); ?>" class="rpp-input">
                        <button type="button" class="rpp-btn rpp-btn-small rpp-btn-danger remove-benefit">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                    </div>
                </div>
                <button type="button" class="rpp-btn rpp-btn-small rpp-btn-secondary" id="add-benefit">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Přidat výhodu', 'roanga-partner'); ?>
                </button>
            </div>
            
            <div class="rpp-modal-footer">
                <button type="button" class="rpp-btn rpp-btn-secondary" id="cancel-group">
                    <?php _e('Zrušit', 'roanga-partner'); ?>
                </button>
                <button type="submit" class="rpp-btn rpp-btn-primary">
                    <?php _e('Uložit skupinu', 'roanga-partner'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Roanga Planet inspired design */
.rpp-admin-wrap {
    background: #f8f9fa;
    margin: 0 -20px;
    padding: 20px;
    min-height: 100vh;
}

.rpp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%);
    border-radius: 12px;
    color: white;
    box-shadow: 0 4px 20px rgba(45, 90, 39, 0.3);
}

.rpp-title {
    display: flex;
    align-items: center;
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    color: white;
}

.rpp-icon {
    margin-right: 12px;
    font-size: 32px;
}

.rpp-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    gap: 8px;
}

.rpp-btn-primary {
    background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
    color: #2d5a27;
    box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
}

.rpp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
}

.rpp-btn-secondary {
    background: #e8f5e8;
    color: #2d5a27;
    border: 1px solid #c8e6c9;
}

.rpp-btn-secondary:hover {
    background: #d4edda;
    border-color: #a5d6a7;
}

.rpp-btn-danger {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.rpp-btn-danger:hover {
    background: #ffcdd2;
    border-color: #ef9a9a;
}

.rpp-btn-small {
    padding: 8px 12px;
    font-size: 12px;
}

.rpp-groups-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 24px;
}

.rpp-group-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e8f5e8;
}

.rpp-group-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.rpp-group-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
    padding: 20px;
    border-bottom: 1px solid #e8f5e8;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.rpp-group-name {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #2d5a27;
}

.rpp-group-rate {
    background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
    color: #2d5a27;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.rpp-group-actions {
    display: flex;
    gap: 8px;
}

.rpp-group-content {
    padding: 20px;
}

.rpp-group-description {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.rpp-group-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
}

.rpp-stat-item {
    text-align: center;
}

.rpp-stat-number {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.rpp-group-benefits h4 {
    margin: 0 0 12px 0;
    color: #2d5a27;
    font-size: 16px;
}

.rpp-benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.rpp-benefits-list li {
    display: flex;
    align-items: center;
    padding: 8px 0;
    color: #555;
}

.rpp-benefits-list .dashicons {
    color: #4a7c59;
    margin-right: 8px;
    font-size: 16px;
}

/* Volume Settings Styles */
.rpp-volume-section,
.rpp-bonus-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #d4af37;
}

.rpp-volume-section h4,
.rpp-bonus-section h4 {
    margin: 0 0 16px 0;
    color: #2d5a27;
    font-size: 16px;
    font-weight: 600;
}

.rpp-bonus-container {
    margin-bottom: 16px;
}

.rpp-bonus-item {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    border: 1px solid #e8f5e8;
}

.rpp-bonus-item:last-child {
    margin-bottom: 0;
}

/* Modal Styles */
.rpp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rpp-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.rpp-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.rpp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid #e8f5e8;
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
}

.rpp-modal-header h2 {
    margin: 0;
    color: #2d5a27;
    font-size: 24px;
    font-weight: 600;
}

.rpp-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.rpp-modal-close:hover {
    background: #ffebee;
    color: #c62828;
}

.rpp-form {
    padding: 24px;
}

.rpp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.rpp-form-group {
    margin-bottom: 20px;
}

.rpp-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2d5a27;
}

.rpp-input,
.rpp-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e8f5e8;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.rpp-input:focus,
.rpp-textarea:focus {
    outline: none;
    border-color: #4a7c59;
    box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.1);
}

.rpp-benefits-container {
    margin-bottom: 12px;
}

.rpp-benefit-item {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    align-items: center;
}

.rpp-benefit-item .rpp-input {
    flex: 1;
}

.rpp-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 24px;
    border-top: 1px solid #e8f5e8;
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .rpp-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
    
    .rpp-groups-container {
        grid-template-columns: 1fr;
    }
    
    .rpp-form-row {
        grid-template-columns: 1fr;
    }
    
    .rpp-group-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .rpp-group-stats {
        flex-direction: column;
        gap: 16px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('RPP Groups: JavaScript loaded');
    console.log('RPP Groups: AJAX URL:', ajaxurl);
    console.log('RPP Groups: Nonce:', rpp_admin_ajax.nonce);
    
    var modal = $('#group-modal');
    var isEditing = false;
    
    // Add new group
    $('#add-new-group').on('click', function(e) {
        e.preventDefault();
        console.log('RPP Groups: Add new group clicked');
        isEditing = false;
        $('#modal-title').text('<?php _e('Přidat novou skupinu', 'roanga-partner'); ?>');
        $('#group-form')[0].reset();
        $('#group-id').val('');
        
        // Clear benefits and add one empty field
        $('#benefits-container').empty();
        addBenefitField('');
        
        modal.show();
    });
    
    // Edit group
    $('.edit-group').on('click', function() {
        var groupId = $(this).data('group-id');
        console.log('RPP Groups: Edit group clicked, ID:', groupId);
        isEditing = true;
        $('#modal-title').text('<?php _e('Upravit skupinu', 'roanga-partner'); ?>');
        
        // Load group data via AJAX
        console.log('RPP Groups: Loading group data...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_get_group',
                group_id: groupId,
                nonce: rpp_admin_ajax.nonce
            },
            beforeSend: function() {
                console.log('RPP Groups: AJAX request starting for get_group');
            },
            success: function(response) {
                console.log('RPP Groups: AJAX success for get_group:', response);
                if (response.success) {
                    var group = response.data;
                    $('#group-id').val(group.id);
                    $('#group-name').val(group.name);
                    $('#group-description').val(group.description);
                    $('#group-commission-rate').val(group.commission_rate);
                    $('#group-volume-based').prop('checked', group.volume_based == 1);
                    $('#group-volume-percentage').val(group.volume_percentage);
                    $('#group-restart-period').val(group.restart_period || 'monthly');
                    $('#group-disable-mlm').prop('checked', group.disable_mlm == 1);
                    
                    // Load bonus thresholds
                    $('#bonus-thresholds-container').empty();
                    if (group.bonus_thresholds && group.bonus_thresholds.length > 0) {
                        group.bonus_thresholds.forEach(function(bonus) {
                            addBonusField(bonus.turnover, bonus.amount, bonus.restart || 'monthly');
                        });
                    } else {
                        addBonusField('', '', 'monthly');
                    }
                    
                    // Show/hide volume settings
                    if (group.volume_based == 1) {
                        $('.rpp-volume-settings').show();
                    } else {
                        $('.rpp-volume-settings').hide();
                    }
                    
                    // Clear existing benefits
                    $('#benefits-container').empty();
                    
                    // Add benefits
                    if (group.benefits && group.benefits.length > 0) {
                        group.benefits.forEach(function(benefit) {
                            addBenefitField(benefit);
                        });
                    } else {
                        addBenefitField('');
                    }
                    
                    modal.show();
                } else {
                    console.log('RPP Groups: Server error:', response.data);
                    alert('Chyba při načítání dat skupiny.');
                }
            },
            error: function(xhr, status, error) {
                console.log('RPP Groups: AJAX error for get_group:', xhr, status, error);
                console.log('RPP Groups: Response text:', xhr.responseText);
                alert('Chyba při komunikaci se serverem: ' + error);
            }
        });
    });
    
    // Delete group
    $('.delete-group').on('click', function() {
        if (!confirm('<?php _e('Opravdu chcete smazat tuto skupinu? Všichni partneři budou přesunuti do výchozí skupiny.', 'roanga-partner'); ?>')) {
            return;
        }
        
        var groupId = $(this).data('group-id');
        console.log('RPP Groups: Delete group clicked, ID:', groupId);
        var button = $(this);
        
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_delete_group',
                group_id: groupId,
                nonce: rpp_admin_ajax.nonce
            },
            beforeSend: function() {
                console.log('RPP Groups: AJAX request starting for delete_group');
            },
            success: function(response) {
                console.log('RPP Groups: AJAX success for delete_group:', response);
                if (response.success) {
                    location.reload();
                } else {
                    console.log('RPP Groups: Server error:', response.data);
                    alert('Chyba: ' + response.data);
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.log('RPP Groups: AJAX error for delete_group:', xhr, status, error);
                console.log('RPP Groups: Response text:', xhr.responseText);
                alert('Chyba při komunikaci se serverem: ' + error);
                button.prop('disabled', false);
            }
        });
    });
    
    // Close modal
    $('.rpp-modal-close, #cancel-group, .rpp-modal-overlay').on('click', function() {
        modal.hide();
    });
    
    // Add benefit field
    $('#add-benefit').on('click', function() {
        addBenefitField('');
    });
    
    // Add bonus field
    $('#add-bonus').on('click', function() {
        addBonusField('', '');
    });
    
    // Remove benefit field
    $(document).on('click', '.remove-benefit', function() {
        if ($('#benefits-container .rpp-benefit-item').length > 1) {
            $(this).closest('.rpp-benefit-item').remove();
        } else {
            $(this).closest('.rpp-benefit-item').remove();
        }
    });
    
    // Remove bonus field
    $(document).on('click', '.remove-bonus', function() {
        if ($('#bonus-thresholds-container .rpp-bonus-item').length > 1) {
            $(this).closest('.rpp-bonus-item').remove();
        }
    });
    
    // Submit form
    $('#group-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('RPP Groups: Form submitted');
        
        var formData = new FormData(this);
        formData += '&action=' + (isEditing ? 'rpp_update_group' : 'rpp_create_group');
        formData += '&nonce=' + rpp_admin_ajax.nonce;
        
        console.log('RPP Groups: Form data:', formData);
        console.log('RPP Groups: Is editing:', isEditing);
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Ukládám...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=' + (isEditing ? 'rpp_update_group' : 'rpp_create_group') + '&nonce=' + rpp_admin_ajax.nonce,
            beforeSend: function() {
                console.log('RPP Groups: AJAX request starting for form submit');
            },
            success: function(response) {
                console.log('RPP Groups: AJAX success for form submit:', response);
                if (response.success) {
                    location.reload();
                } else {
                    console.log('RPP Groups: Server error:', response.data);
                    alert('Chyba: ' + response.data);
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.log('RPP Groups: AJAX error for form submit:', xhr, status, error);
                console.log('RPP Groups: Response text:', xhr.responseText);
                alert('Chyba při komunikaci se serverem: ' + error);
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    function addBenefitField(value) {
        var benefitHtml = '<div class="rpp-benefit-item">' +
            '<input type="text" name="benefits[]" value="' + (value || '') + '" placeholder="<?php _e('Zadejte výhodu', 'roanga-partner'); ?>" class="rpp-input">' +
            '<button type="button" class="rpp-btn rpp-btn-small rpp-btn-danger remove-benefit">' +
            '<span class="dashicons dashicons-minus"></span>' +
            '</button>' +
            '</div>';
        $('#benefits-container').append(benefitHtml);
    }
    
    function addBonusField(turnover, amount, restart) {
        var bonusHtml = '<div class="rpp-bonus-item">' +
            '<div class="rpp-form-row">' +
            '<div class="rpp-form-group">' +
            '<label><?php _e('Obrat (Kč):', 'roanga-partner'); ?></label>' +
            '<input type="number" name="bonus_turnover[]" value="' + (turnover || '') + '" min="0" step="1000" class="rpp-input" placeholder="50000">' +
            '</div>' +
            '<div class="rpp-form-group">' +
            '<label><?php _e('Bonus (Kč):', 'roanga-partner'); ?></label>' +
            '<input type="number" name="bonus_amount[]" value="' + (amount || '') + '" min="0" step="100" class="rpp-input" placeholder="5000">' +
            '</div>' +
            '<div class="rpp-form-group">' +
            '<label><?php _e('Restart:', 'roanga-partner'); ?></label>' +
            '<select name="bonus_restart[]" class="rpp-input">' +
            '<option value="weekly"' + (restart === 'weekly' ? ' selected' : '') + '><?php _e('Týdně', 'roanga-partner'); ?></option>' +
            '<option value="monthly"' + (restart === 'monthly' ? ' selected' : '') + '><?php _e('Měsíčně', 'roanga-partner'); ?></option>' +
            '<option value="quarterly"' + (restart === 'quarterly' ? ' selected' : '') + '><?php _e('Kvartálně', 'roanga-partner'); ?></option>' +
            '<option value="semi-annually"' + (restart === 'semi-annually' ? ' selected' : '') + '><?php _e('Půlročně', 'roanga-partner'); ?></option>' +
            '<option value="annually"' + (restart === 'annually' ? ' selected' : '') + '><?php _e('Ročně', 'roanga-partner'); ?></option>' +
            '</select>' +
            '</div>' +
            '<div class="rpp-form-group">' +
            '<button type="button" class="rpp-btn rpp-btn-small rpp-btn-danger remove-bonus">' +
            '<span class="dashicons dashicons-minus"></span>' +
            '</button>' +
            '</div>' +
            '</div>' +
            '</div>';
        $('#bonus-thresholds-container').append(bonusHtml);
    }
    
    // Toggle volume settings
    $('#group-volume-based').on('change', function() {
        if ($(this).is(':checked')) {
            $('.rpp-volume-settings').slideDown();
            // Add default bonus if none exist
            if ($('#bonus-thresholds-container .rpp-bonus-item').length === 0) {
                addBonusField('', '');
            }
        } else {
            $('.rpp-volume-settings').slideUp();
        }
    });
});
</script>