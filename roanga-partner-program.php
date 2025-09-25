<?php
/**
 * Plugin Name: Roanga Partner Program
 * Plugin URI: https://example.com/roanga-partner-program
 * Description: Complete partner/ambassador program management system with registration, tracking, and commission management.
 * Version: 1.0.2
 * Author: Your Company
 * License: GPL v2 or later
 * Text Domain: roanga-partner
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RPP_VERSION', '1.0.2');
define('RPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RPP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once RPP_PLUGIN_PATH . 'includes/class-rpp-activator.php';
require_once RPP_PLUGIN_PATH . 'includes/class-rpp-deactivator.php';
require_once RPP_PLUGIN_PATH . 'includes/class-rpp-core.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('RPP_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('RPP_Deactivator', 'deactivate'));

// Initialize the plugin
function run_roanga_partner_program() {
    $plugin = new RPP_Core();
    $plugin->run();
}

run_roanga_partner_program();