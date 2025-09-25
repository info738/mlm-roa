<div class="rpp-login-container">
    <div class="rpp-login-form">
        <div class="rpp-login-header">
            <h2><?php _e('Přihlášení do partnerského dashboardu', 'roanga-partner'); ?></h2>
            <p><?php _e('Přihlaste se ke svému partnerskému účtu', 'roanga-partner'); ?></p>
        </div>
        
        <form id="rpp-partner-login-form" class="rpp-form">
            <div class="rpp-form-group">
                <label for="partner_email"><?php _e('Email', 'roanga-partner'); ?></label>
                <input type="email" id="partner_email" name="partner_email" required 
                       placeholder="<?php _e('Zadejte váš email', 'roanga-partner'); ?>" class="rpp-input">
            </div>
            
            <div class="rpp-form-group">
                <label for="partner_password"><?php _e('Heslo', 'roanga-partner'); ?></label>
                <input type="password" id="partner_password" name="partner_password" required 
                       placeholder="<?php _e('Zadejte vaše heslo', 'roanga-partner'); ?>" class="rpp-input">
            </div>
            
            <div class="rpp-form-group">
                <label class="rpp-checkbox-label">
                    <input type="checkbox" name="remember_me" value="1">
                    <span class="rpp-checkbox-custom"></span>
                    <?php _e('Zapamatovat si mě', 'roanga-partner'); ?>
                </label>
            </div>
            
            <div class="rpp-form-group">
                <button type="submit" class="rpp-btn rpp-btn-primary rpp-btn-large">
                    <?php _e('Přihlásit se', 'roanga-partner'); ?>
                </button>
            </div>
            
            <div class="rpp-form-links">
                <a href="<?php echo wp_lostpassword_url(); ?>" class="rpp-forgot-password">
                    <?php _e('Zapomněli jste heslo?', 'roanga-partner'); ?>
                </a>
                
                <?php 
                $registration_url = get_permalink(get_option('rpp_registration_page_id'));
                if ($registration_url): ?>
                <a href="<?php echo $registration_url; ?>" class="rpp-register-link">
                    <?php _e('Nejste ještě partnerem? Registrujte se zde', 'roanga-partner'); ?>
                </a>
                <?php endif; ?>
            </div>
            
            <div id="rpp-login-message" class="rpp-message" style="display:none;"></div>
        </form>
    </div>
</div>

<style>
.rpp-login-container {
    max-width: 450px;
    margin: 40px auto;
    padding: 20px;
}

.rpp-login-form {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 8px 32px rgba(45, 90, 39, 0.15);
    border: 1px solid #e8f5e8;
}

.rpp-login-header {
    text-align: center;
    margin-bottom: 30px;
}

.rpp-login-header h2 {
    color: #2d5a27;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 12px 0;
}

.rpp-login-header p {
    color: #666;
    margin: 0;
    font-size: 16px;
}

.rpp-form-group {
    margin-bottom: 24px;
}

.rpp-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2d5a27;
    font-size: 14px;
}

.rpp-input {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e8f5e8;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-sizing: border-box;
    background: #f8f9fa;
}

.rpp-input:focus {
    outline: none;
    border-color: #4a7c59;
    box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.1);
    background: white;
}

.rpp-checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #555;
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

.rpp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16px 32px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    gap: 8px;
    width: 100%;
}

.rpp-btn-primary {
    background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
    color: #2d5a27;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.rpp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
}

.rpp-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.rpp-form-links {
    text-align: center;
    margin-top: 24px;
}

.rpp-form-links a {
    display: block;
    margin: 12px 0;
    color: #4a7c59;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.rpp-form-links a:hover {
    color: #2d5a27;
    text-decoration: underline;
}

.rpp-forgot-password {
    font-weight: 600;
}

.rpp-register-link {
    padding: 12px 20px;
    background: #e8f5e8;
    border-radius: 8px;
    margin-top: 16px !important;
}

.rpp-message {
    padding: 16px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 14px;
    text-align: center;
}

.rpp-message.rpp-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.rpp-message.rpp-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .rpp-login-container {
        margin: 20px;
        padding: 10px;
    }
    
    .rpp-login-form {
        padding: 30px 20px;
    }
    
    .rpp-login-header h2 {
        font-size: 24px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#rpp-partner-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#rpp-login-message');
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Přihlašuji...');
        $message.hide();
        
        $.ajax({
            url: rpp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rpp_partner_login',
                nonce: rpp_ajax.nonce,
                partner_email: $('#partner_email').val(),
                partner_password: $('#partner_password').val(),
                remember_me: $('input[name="remember_me"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('rpp-error').addClass('rpp-success')
                           .html(response.data.message).show();
                    
                    // Redirect after 1 second
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    $message.removeClass('rpp-success').addClass('rpp-error')
                           .html(response.data).show();
                }
            },
            error: function() {
                $message.removeClass('rpp-success').addClass('rpp-error')
                       .html('Došlo k chybě. Zkuste to prosím znovu.').show();
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
</div>