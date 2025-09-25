<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Partner Application Received', 'roanga-partner'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e('Application Received', 'roanga-partner'); ?></h1>
        </div>
        
        <div class="content">
            <p><?php printf(__('Hello %s,', 'roanga-partner'), $partner_name); ?></p>
            
            <p><?php _e('Thank you for applying to become a partner with us! We have received your application and will review it shortly.', 'roanga-partner'); ?></p>
            
            <p><?php printf(__('Your partner code is: <strong>%s</strong>', 'roanga-partner'), $partner_code); ?></p>
            
            <p><?php _e('We will notify you via email once your application has been reviewed. This typically takes 1-3 business days.', 'roanga-partner'); ?></p>
            
            <p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'roanga-partner'); ?></p>
        </div>
        
        <div class="footer">
            <p><?php printf(__('Best regards,<br>%s Team<br>Contact: %s', 'roanga-partner'), $site_name, $contact_email); ?></p>
        </div>
    </div>
</body>
</html>