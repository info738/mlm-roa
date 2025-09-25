<div class="rpp-registration-form">
    <div class="rpp-form-header">
        <h3><?php _e('Formulář partnerské žádosti', 'roanga-partner'); ?></h3>
        <div class="rpp-form-links">
            <?php 
            $login_url = get_permalink(get_option('rpp_login_page_id'));
            if ($login_url): ?>
                <p><?php _e('Již jste partnerem?', 'roanga-partner'); ?> 
                   <a href="<?php echo esc_url($login_url); ?>" class="rpp-link-primary">
                       <?php _e('Přihlaste se zde', 'roanga-partner'); ?>
                   </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    
    <?php if (!is_user_logged_in()): ?>
    <div class="rpp-user-info-section">
        <h4><?php _e('Osobní údaje', 'roanga-partner'); ?></h4>
        <div class="rpp-form-row">
            <div class="rpp-form-group">
                <label for="first_name"><?php _e('Jméno *', 'roanga-partner'); ?></label>
                <input type="text" id="first_name" name="first_name" required 
                       placeholder="<?php _e('Vaše jméno', 'roanga-partner'); ?>">
            </div>
            <div class="rpp-form-group">
                <label for="last_name"><?php _e('Příjmení *', 'roanga-partner'); ?></label>
                <input type="text" id="last_name" name="last_name" required 
                       placeholder="<?php _e('Vaše příjmení', 'roanga-partner'); ?>">
            </div>
        </div>
        <div class="rpp-form-group">
            <label for="email"><?php _e('Email *', 'roanga-partner'); ?></label>
            <input type="email" id="email" name="email" required 
                   placeholder="<?php _e('vas@email.cz', 'roanga-partner'); ?>">
        </div>
    </div>
    
    <style>
    .rpp-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .rpp-user-info-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        border-left: 4px solid #4a7c59;
    }
    
    .rpp-user-info-section h4 {
        margin: 0 0 20px 0;
        color: #2d5a27;
        font-size: 18px;
    }
    
    .rpp-no-sponsor {
        margin-top: 12px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #4a7c59;
    }
    
    .rpp-checkbox-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-size: 14px;
        color: #2d5a27;
        font-weight: 600;
    }
    
    .rpp-checkbox-label input[type="checkbox"] {
        display: none;
    }
    
    .rpp-checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #e8f5e8;
        border-radius: 4px;
        margin-right: 12px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .rpp-checkbox-label input[type="checkbox"]:checked + .rpp-checkbox-custom {
        background: linear-gradient(135deg, #4a7c59 0%, #66bb6a 100%);
        border-color: #4a7c59;
    }
    
    .rpp-checkbox-label input[type="checkbox"]:checked + .rpp-checkbox-custom::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
    
    .rpp-sponsor-info {
        margin-top: 8px;
        padding: 8px 12px;
        background: #e8f5e8;
        border-radius: 6px;
        border-left: 3px solid #4a7c59;
    }
    
    .rpp-sponsor-name {
        color: #2d5a27;
        font-weight: 600;
        font-size: 14px;
    }
    
    .rpp-sponsor-info.error {
        background: #ffebee;
        border-left-color: #c62828;
    }
    
    .rpp-sponsor-info.error .rpp-sponsor-name {
        color: #c62828;
    }
    
    @media (max-width: 768px) {
        .rpp-form-row {
            grid-template-columns: 1fr;
        }
    }
    
    .rpp-form-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e8f5e8;
    }
    
    .rpp-form-header h3 {
        color: #2d5a27;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 12px 0;
    }
    
    .rpp-form-links p {
        margin: 0;
        color: #666;
    }
    
    .rpp-link-primary {
        color: #2d5a27;
        font-weight: 600;
        text-decoration: none;
        padding: 8px 16px;
        background: #e8f5e8;
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    
    .rpp-link-primary:hover {
        background: #d4edda;
        transform: translateY(-1px);
    }
    
    .rpp-form-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e8f5e8;
    }
    
    .rpp-footer-text {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .rpp-link-secondary {
        color: #4a7c59;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .rpp-link-secondary:hover {
        color: #2d5a27;
        text-decoration: underline;
    }
    </style>
    <?php endif; ?>
    
    <?php if (get_option('rpp_mlm_require_sponsor', false)): ?>
    <div class="rpp-sponsor-section">
        <h4><?php _e('Sponzorský kód', 'roanga-partner'); ?></h4>
        <p><?php _e('Pro registraci je vyžadován kód vašeho sponzora.', 'roanga-partner'); ?></p>
        <div class="rpp-form-group">
            <label for="sponsor_code"><?php _e('Kód sponzora *', 'roanga-partner'); ?></label>
            <input type="text" id="sponsor_code" name="sponsor_code" required 
                   placeholder="<?php _e('Zadejte kód sponzora', 'roanga-partner'); ?>"
                   value="<?php echo isset($_GET['sponsor']) ? esc_attr($_GET['sponsor']) : ''; ?>">
            <div id="sponsor-info" class="rpp-sponsor-info" style="display: none;">
                <span class="rpp-sponsor-name"></span>
            </div>
            <div class="rpp-no-sponsor">
                <label class="rpp-checkbox-label">
                    <input type="checkbox" id="no_sponsor" name="no_sponsor">
                    <span class="rpp-checkbox-custom"></span>
                    <?php _e('Nemám doporučitele', 'roanga-partner'); ?>
                </label>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="rpp-sponsor-section">
        <h4><?php _e('Sponzorský kód (volitelný)', 'roanga-partner'); ?></h4>
        <p><?php _e('Pokud vás někdo doporučil, zadejte jeho kód.', 'roanga-partner'); ?></p>
        <div class="rpp-form-group">
            <label for="sponsor_code"><?php _e('Kód sponzora', 'roanga-partner'); ?></label>
            <input type="text" id="sponsor_code" name="sponsor_code" 
                   placeholder="<?php _e('Zadejte kód sponzora (volitelné)', 'roanga-partner'); ?>"
                   value="<?php echo isset($_GET['sponsor']) ? esc_attr($_GET['sponsor']) : ''; ?>">
            <div id="sponsor-info" class="rpp-sponsor-info" style="display: none;">
                <span class="rpp-sponsor-name"></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <form id="rpp-partner-registration-form" class="rpp-form">
        <div class="rpp-form-group">
            <label for="website"><?php _e('Website URL *', 'roanga-partner'); ?></label>
            <input type="url" id="website" name="website" required 
                   placeholder="<?php _e('https://vase-webova-stranka.cz', 'roanga-partner'); ?>">
        </div>
        
        <div class="rpp-form-group">
            <label for="social_media"><?php _e('Sociální sítě', 'roanga-partner'); ?></label>
            <textarea id="social_media" name="social_media" rows="3" 
                      placeholder="<?php _e('Uveďte vaše profily na sociálních sítích (Facebook, Instagram, Twitter, atd.)', 'roanga-partner'); ?>"></textarea>
        </div>
        
        <div class="rpp-form-group">
            <label for="experience"><?php _e('Marketingové zkušenosti *', 'roanga-partner'); ?></label>
            <textarea id="experience" name="experience" rows="4" required 
                      placeholder="<?php _e('Popište vaše marketingové zkušenosti a jak plánujete propagovat naše produkty', 'roanga-partner'); ?>"></textarea>
        </div>
        
        <div class="rpp-form-group">
            <label for="audience"><?php _e('Cílová skupina', 'roanga-partner'); ?></label>
            <textarea id="audience" name="audience" rows="3" 
                      placeholder="<?php _e('Popište vaši cílovou skupinu a dosah', 'roanga-partner'); ?>"></textarea>
        </div>
        
        <div class="rpp-form-group">
            <label for="motivation"><?php _e('Proč se chcete stát partnerem? *', 'roanga-partner'); ?></label>
            <textarea id="motivation" name="motivation" rows="4" required 
                      placeholder="<?php _e('Řekněte nám, proč se chcete stát naším partnerem', 'roanga-partner'); ?>"></textarea>
        </div>
        
        <div class="rpp-form-group">
            <label>
                <input type="checkbox" name="terms" required>
                <?php _e('Souhlasím s', 'roanga-partner'); ?> 
                <a href="#" target="_blank"><?php _e('obchodními podmínkami', 'roanga-partner'); ?></a>
            </label>
        </div>
        
        <div class="rpp-form-group">
            <button type="submit" class="rpp-button rpp-button-primary">
                <?php _e('Odeslat žádost', 'roanga-partner'); ?>
            </button>
        </div>
        
        <div id="rpp-form-message" class="rpp-message" style="display:none;"></div>
        
        <div class="rpp-form-footer">
            <?php 
            $login_url = get_permalink(get_option('rpp_login_page_id'));
            if ($login_url): ?>
                <p class="rpp-footer-text">
                    <?php _e('Již máte partnerský účet?', 'roanga-partner'); ?> 
                    <a href="<?php echo esc_url($login_url); ?>" class="rpp-link-secondary">
                        <?php _e('Přihlaste se zde', 'roanga-partner'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var searchTimeout;
    
    // Partner search functionality
    $('#sponsor_code').on('input', function() {
        var code = $(this).val().trim();
        var $info = $('#sponsor-info');
        
        clearTimeout(searchTimeout);
        
        if (code.length >= 3) {
            searchTimeout = setTimeout(function() {
                searchPartner(code);
            }, 500);
        } else {
            $info.hide();
        }
    });
    
    function searchPartner(code) {
        var $info = $('#sponsor-info');
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
    
    // Handle "Nemám doporučitele" checkbox
    $('#no_sponsor').on('change', function() {
        if ($(this).is(':checked')) {
            $('#sponsor_code').prop('required', false).val('').prop('disabled', true);
            $('#sponsor-info').hide();
        } else {
            $('#sponsor_code').prop('required', true).prop('disabled', false);
        }
    });
    
    $('#rpp-partner-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate sponsor code if required
        if ($('#sponsor_code').prop('required') && !$('#sponsor_code').val() && !$('#no_sponsor').is(':checked')) {
            alert('<?php _e('Prosím zadejte sponzorský kód nebo zaškrtněte "Nemám doporučitele"', 'roanga-partner'); ?>');
            return;
        }
        
        var $form = $(this);
        var $message = $('#rpp-form-message');
        var $button = $form.find('button[type="submit"]');
        
        $button.prop('disabled', true).text('<?php _e('Submitting...', 'roanga-partner'); ?>');
        $message.hide();
        
        $.ajax({
            url: rpp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rpp_partner_registration',
                nonce: rpp_ajax.nonce,
                website: $('#website').val(),
                social_media: $('#social_media').val(),
                experience: $('#experience').val(),
                audience: $('#audience').val(),
                motivation: $('#motivation').val(),
                sponsor_code: $('#sponsor_code').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                email: $('#email').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('rpp-error').addClass('rpp-success')
                           .html(response.data).show();
                    $form[0].reset();
                } else {
                    $message.removeClass('rpp-success').addClass('rpp-error')
                           .html(response.data).show();
                }
            },
            error: function() {
                $message.removeClass('rpp-success').addClass('rpp-error')
                       .html('<?php _e('Došlo k chybě. Zkuste to prosím znovu.', 'roanga-partner'); ?>').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php _e('Odeslat žádost', 'roanga-partner'); ?>');
            }
        });
    });
});
</script>