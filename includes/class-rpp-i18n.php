<?php
/**
 * Internationalization functionality
 */
class RPP_i18n {
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'roanga-partner',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}