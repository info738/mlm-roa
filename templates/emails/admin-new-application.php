<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('New Partner Application', 'roanga-partner'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; }
        .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2271b1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('New Partner Application', 'roanga-partner'); ?></h1>
        </div>
        
        <div class="content">
            <p><?php _e('A new partner application has been submitted and requires your review.', 'roanga-partner'); ?></p>
            
            <div class="info-box">
                <h3><?php _e('Applicant Information:', 'roanga-partner'); ?></h3>
                <ul>
                    <li><strong><?php _e('Name:', 'roanga-partner'); ?></strong> <?php echo $partner_name; ?></li>
                    <li><strong><?php _e('Email:', 'roanga-partner'); ?></strong> <?php echo $partner_email; ?></li>
                    <li><strong><?php _e('Partner Code:', 'roanga-partner'); ?></strong> <?php echo $partner_code; ?></li>
                    <li><strong><?php _e('Application Date:', 'roanga-partner'); ?></strong> <?php echo $application_date; ?></li>
                    <?php if ($website): ?>
                    <li><strong><?php _e('Website:', 'roanga-partner'); ?></strong> <a href="<?php echo esc_url($website); ?>"><?php echo $website; ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <?php if ($experience): ?>
            <div class="info-box">
                <h4><?php _e('Marketing Experience:', 'roanga-partner'); ?></h4>
                <p><?php echo nl2br(esc_html($experience)); ?></p>
            </div>
            <?php endif; ?>
            
            <p style="text-align: center;">
                <a href="<?php echo $admin_url; ?>" class="button"><?php _e('Review Application', 'roanga-partner'); ?></a>
            </p>
        </div>
    </div>
</body>
</html>