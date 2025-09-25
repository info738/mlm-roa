<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Partner Application Approved', 'roanga-partner'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('Congratulations!', 'roanga-partner'); ?></h1>
        </div>
        
        <div class="content">
            <p><?php printf(__('Hello %s,', 'roanga-partner'), $partner_name); ?></p>
            
            <p><?php _e('Great news! Your partner application has been approved and you can now start earning commissions.', 'roanga-partner'); ?></p>
            
            <h3><?php _e('Your Partner Details:', 'roanga-partner'); ?></h3>
            <ul>
                <li><strong><?php _e('Partner Code:', 'roanga-partner'); ?></strong> <?php echo $partner_code; ?></li>
                <li><strong><?php _e('Commission Rate:', 'roanga-partner'); ?></strong> <?php echo $commission_rate; ?>%</li>
                <li><strong><?php _e('Partner Group:', 'roanga-partner'); ?></strong> <?php echo $group_name; ?></li>
            </ul>
            
            <p><?php _e('You can now access your partner dashboard to get your referral links and track your performance.', 'roanga-partner'); ?></p>
            
            <p style="text-align: center;">
                <a href="<?php echo $dashboard_url; ?>" class="button"><?php _e('Access Dashboard', 'roanga-partner'); ?></a>
            </p>
            
            <p><?php _e('Thank you for joining our partner program!', 'roanga-partner'); ?></p>
        </div>
        
        <div class="footer">
            <p><?php printf(__('Best regards,<br>%s Team', 'roanga-partner'), $site_name); ?></p>
        </div>
    </div>
</body>
</html>