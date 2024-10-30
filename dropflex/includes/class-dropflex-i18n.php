<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_i18n {
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'dropflex',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}