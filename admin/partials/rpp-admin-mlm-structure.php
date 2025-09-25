<?php
if (!defined('ABSPATH')) {
    exit;
}

$mlm_class = new RPP_MLM_Structure();
?>

<div class="wrap">
    <h1><?php _e('MLM Struktura', 'roanga-partner'); ?></h1>
    
    <div class="rpp-mlm-controls">
        <button class="button button-primary" id="refresh-mlm-tree">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Obnovit strukturu', 'roanga-partner'); ?>
        </button>
        
        <div class="rpp-mlm-stats">
            <?php
            global $wpdb;
            $total_partners = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rpp_partners WHERE status = 'approved'");
            $total_levels = $wpdb->get_var("SELECT MAX(level) FROM {$wpdb->prefix}rpp_mlm_structure");
            ?>
            <span><strong><?php echo $total_partners; ?></strong> aktivních partnerů</span>
            <span><strong><?php echo $total_levels ?: 1; ?></strong> úrovní</span>
        </div>
    </div>
    
    <div class="rpp-mlm-table-container">
        <table class="wp-list-table widefat fixed striped" id="mlm-structure-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><?php _e('Úroveň', 'roanga-partner'); ?></th>
                    <th><?php _e('Partner', 'roanga-partner'); ?></th>
                    <th><?php _e('Kód', 'roanga-partner'); ?></th>
                    <th><?php _e('Email', 'roanga-partner'); ?></th>
                    <th><?php _e('Skupina', 'roanga-partner'); ?></th>
                    <th><?php _e('Sponzor', 'roanga-partner'); ?></th>
                    <th><?php _e('Přímí', 'roanga-partner'); ?></th>
                    <th><?php _e('Tým', 'roanga-partner'); ?></th>
                    <th><?php _e('Výdělky', 'roanga-partner'); ?></th>
                    <th><?php _e('Akce', 'roanga-partner'); ?></th>
                </tr>
            </thead>
            <tbody id="mlm-table-body">
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px;">
                        <span class="spinner is-active"></span>
                        <?php _e('Načítám MLM strukturu...', 'roanga-partner'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal pro přesun partnera -->
<div id="move-partner-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content">
        <div class="rpp-modal-header">
            <h3><?php _e('Přesun partnera', 'roanga-partner'); ?></h3>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <form id="move-partner-form">
            <div class="rpp-modal-body">
                <input type="hidden" id="move-partner-id" name="partner_id" value="">
                
                <p><strong id="move-partner-name"></strong></p>
                
                <div class="rpp-form-group">
                    <label for="new-sponsor-code"><?php _e('Nový sponzor (partnerský kód):', 'roanga-partner'); ?></label>
                    <input type="text" id="new-sponsor-code" name="new_sponsor_code" class="regular-text" placeholder="<?php _e('Zadejte kód nového sponzora', 'roanga-partner'); ?>">
                    <div id="new-sponsor-info" class="rpp-sponsor-info" style="display: none;">
                        <span class="rpp-sponsor-name"></span>
                    </div>
                </div>
                
                <div class="rpp-form-group">
                    <label>
                        <input type="checkbox" id="move-to-root" name="move_to_root">
                        <?php _e('Přesunout na root úroveň (bez sponzora)', 'roanga-partner'); ?>
                    </label>
                </div>
            </div>
            
            <div class="rpp-modal-footer">
                <button type="button" class="button" onclick="closeMoveModal()"><?php _e('Zrušit', 'roanga-partner'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Přesunout partnera', 'roanga-partner'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.rpp-mlm-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.rpp-mlm-stats {
    display: flex;
    gap: 30px;
    font-size: 14px;
}

.rpp-mlm-table-container {
    background: white;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#mlm-structure-table {
    margin: 0;
}

.rpp-level-indicator {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    color: white;
    text-align: center;
    line-height: 24px;
    font-weight: bold;
    font-size: 12px;
}

.rpp-level-0 { background: #d4af37; }
.rpp-level-1 { background: #4a7c59; }
.rpp-level-2 { background: #2d5a27; }
.rpp-level-3 { background: #1a3d1a; }
.rpp-level-4 { background: #0d2a0d; }

.rpp-partner-name {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-partner-code {
    font-family: monospace;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.rpp-group-badge {
    background: #e8f5e8;
    color: #2d5a27;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.rpp-sponsor-info {
    margin-top: 8px;
    padding: 8px 12px;
    background: #e8f5e8;
    border-radius: 4px;
    border-left: 3px solid #4a7c59;
}

.rpp-sponsor-info.error {
    background: #ffebee;
    border-left-color: #c62828;
}

.rpp-sponsor-name {
    color: #2d5a27;
    font-weight: 600;
}

.rpp-sponsor-info.error .rpp-sponsor-name {
    color: #c62828;
}

.rpp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rpp-modal-content {
    background: white;
    border-radius: 4px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.rpp-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rpp-modal-header h3 {
    margin: 0;
}

.rpp-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
}

.rpp-modal-body {
    padding: 20px;
}

.rpp-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.rpp-modal-footer .button {
    margin-left: 10px;
}

.rpp-form-group {
    margin-bottom: 15px;
}

.rpp-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('RPP MLM Structure: JavaScript loaded');
    console.log('RPP MLM Structure: AJAX URL:', ajaxurl);
    console.log('RPP MLM Structure: Nonce:', rpp_admin_ajax.nonce);
    
    loadMLMTable();
    
    var searchTimeout;
    
    // Load MLM table
    function loadMLMTable() {
        console.log('RPP MLM Structure: Loading MLM table...');
        $('#mlm-table-body').html('<tr><td colspan="10" style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám MLM strukturu...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_get_mlm_table',
                nonce: rpp_admin_ajax.nonce
            },
            beforeSend: function() {
                console.log('RPP MLM Structure: AJAX request starting...');
            },
            success: function(response) {
                console.log('RPP MLM Structure: AJAX success:', response);
                if (response.success) {
                    $('#mlm-table-body').html(response.data);
                } else {
                    console.log('RPP MLM Structure: Server error:', response.data);
                    $('#mlm-table-body').html('<tr><td colspan="10" style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání struktury: ' + response.data + '</td></tr>');
                }
            },
            error: function() {
                console.log('RPP MLM Structure: AJAX error:', xhr, status, error);
                if (typeof xhr !== 'undefined' && xhr.responseText) {
                    console.log('RPP MLM Structure: Response text:', xhr.responseText);
                }
                $('#mlm-table-body').html('<tr><td colspan="10" style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem.</td></tr>');
            }
        });
    }
    
    // Refresh button
    $('#refresh-mlm-tree').on('click', function() {
        loadMLMTable();
    });
    
    // Move partner
    $(document).on('click', '.rpp-move-partner', function() {
        var partnerId = $(this).data('partner-id');
        var partnerName = $(this).data('partner-name');
        
        $('#move-partner-id').val(partnerId);
        $('#move-partner-name').text('Přesun partnera: ' + partnerName);
        $('#move-partner-modal').show();
    });
    
    // Search new sponsor
    $('#new-sponsor-code').on('input', function() {
        var code = $(this).val().trim();
        var $info = $('#new-sponsor-info');
        
        clearTimeout(searchTimeout);
        
        if (code.length >= 3) {
            searchTimeout = setTimeout(function() {
                searchSponsor(code);
            }, 500);
        } else {
            $info.hide();
        }
    });
    
    function searchSponsor(code) {
        var $info = $('#new-sponsor-info');
        var $name = $('.rpp-sponsor-name');
        
        $.ajax({
            url: rpp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rpp_search_partner',
                partner_code: code,
                nonce: rpp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $name.text('✓ ' + response.data.name);
                    $info.removeClass('error').show();
                } else {
                    $name.text('✗ ' + response.data);
                    $info.addClass('error').show();
                }
            },
            error: function() {
                $name.text('✗ Chyba při vyhledávání');
                $info.addClass('error').show();
            }
        });
    }
    
    // Handle move to root checkbox
    $('#move-to-root').on('change', function() {
        if ($(this).is(':checked')) {
            $('#new-sponsor-code').prop('disabled', true).val('');
            $('#new-sponsor-info').hide();
        } else {
            $('#new-sponsor-code').prop('disabled', false);
        }
    });
    
    // Submit move form
    $('#move-partner-form').on('submit', function(e) {
        e.preventDefault();
        
        var partnerId = $('#move-partner-id').val();
        var newSponsorCode = $('#new-sponsor-code').val();
        var moveToRoot = $('#move-to-root').is(':checked');
        
        if (!moveToRoot && !newSponsorCode) {
            alert('Zadejte kód nového sponzora nebo zaškrtněte "Přesunout na root úroveň"');
            return;
        }
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Přesouvám...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_move_partner',
                partner_id: partnerId,
                new_sponsor_code: moveToRoot ? '' : newSponsorCode,
                nonce: rpp_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Partner byl úspěšně přesunut!');
                    $('#move-partner-modal').hide();
                    loadMLMTable(); // Reload table
                } else {
                    alert('Chyba: ' + response.data);
                }
            },
            error: function() {
                alert('Chyba při komunikaci se serverem.');
                if (typeof xhr !== 'undefined' && xhr.responseText) {
                    console.log('RPP MLM Structure: Response text:', xhr.responseText);
                }
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        })
        .fail(function(xhr, status, error) {
            alert('Chyba při komunikaci se serverem.');
            console.log('RPP MLM Structure: Response text:', xhr.responseText);
        });
    });
    
    // Close modal
    $('.rpp-modal-close, .rpp-modal-overlay').on('click', function() {
        $('#move-partner-modal').hide();
    });
});

function closeMoveModal() {
    jQuery('#move-partner-modal').hide();
}
</script>