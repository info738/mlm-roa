<div class="wrap">
    <h1><?php _e('Partner Program Settings', 'roanga-partner'); ?></h1>
    
    <?php settings_errors('rpp_settings'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('rpp_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_commission_rate"><?php _e('Default Commission Rate (%)', 'roanga-partner'); ?></label>
                </th>
                <td>
                    <input name="default_commission_rate" type="number" id="default_commission_rate" 
                           value="<?php echo esc_attr(get_option('rpp_default_commission_rate', 10)); ?>" 
                           min="0" max="100" step="0.1" class="small-text" />
                    <p class="description"><?php _e('Default commission rate for new partners.', 'roanga-partner'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="cookie_duration"><?php _e('Cookie Duration (days)', 'roanga-partner'); ?></label>
                </th>
                <td>
                    <input name="cookie_duration" type="number" id="cookie_duration" 
                           value="<?php echo esc_attr(get_option('rpp_cookie_duration', 30)); ?>" 
                           min="1" max="365" class="small-text" />
                    <p class="description"><?php _e('How long to track referrals after initial click.', 'roanga-partner'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="minimum_payout"><?php _e('Minimum Payout Amount', 'roanga-partner'); ?></label>
                </th>
                <td>
                    <input name="minimum_payout" type="number" id="minimum_payout" 
                           value="<?php echo esc_attr(get_option('rpp_minimum_payout', 50)); ?>" 
                           min="0" step="0.01" class="small-text" />
                    <p class="description"><?php _e('Minimum amount required before payout is processed.', 'roanga-partner'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Auto-approve Partners', 'roanga-partner'); ?></th>
                <td>
                    <fieldset>
                        <label for="auto_approve">
                            <input name="auto_approve" type="checkbox" id="auto_approve" 
                                   <?php checked(get_option('rpp_auto_approve', false)); ?> />
                            <?php _e('Automatically approve new partner applications', 'roanga-partner'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Email Notifications', 'roanga-partner'); ?></th>
                <td>
                    <fieldset>
                        <label for="email_notifications">
                            <input name="email_notifications" type="checkbox" id="email_notifications" 
                                   <?php checked(get_option('rpp_email_notifications', true)); ?> />
                            <?php _e('Send email notifications for applications and status changes', 'roanga-partner'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Registration Form Fields', 'roanga-partner'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Required Fields', 'roanga-partner'); ?></th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" name="required_fields[]" value="website" checked disabled /> <?php _e('Website URL', 'roanga-partner'); ?></label><br>
                        <label><input type="checkbox" name="required_fields[]" value="social_media" /> <?php _e('Social Media Profiles', 'roanga-partner'); ?></label><br>
                        <label><input type="checkbox" name="required_fields[]" value="experience" /> <?php _e('Marketing Experience', 'roanga-partner'); ?></label><br>
                        <label><input type="checkbox" name="required_fields[]" value="audience" /> <?php _e('Target Audience', 'roanga-partner'); ?></label><br>
                        <label><input type="checkbox" name="required_fields[]" value="motivation" /> <?php _e('Motivation', 'roanga-partner'); ?></label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Page Settings', 'roanga-partner'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="registration_page"><?php _e('Registration Page', 'roanga-partner'); ?></label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'registration_page',
                        'id' => 'registration_page',
                        'selected' => get_option('rpp_registration_page_id'),
                        'show_option_none' => __('Select a page', 'roanga-partner'),
                        'option_none_value' => ''
                    ));
                    ?>
                    <p class="description"><?php _e('Page containing [rpp_partner_registration] shortcode.', 'roanga-partner'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="dashboard_page"><?php _e('Partner Dashboard Page', 'roanga-partner'); ?></label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'dashboard_page',
                        'id' => 'dashboard_page',
                        'selected' => get_option('rpp_dashboard_page_id'),
                        'show_option_none' => __('Select a page', 'roanga-partner'),
                        'option_none_value' => ''
                    ));
                    ?>
                    <p class="description"><?php _e('Page containing [rpp_partner_dashboard] shortcode.', 'roanga-partner'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>