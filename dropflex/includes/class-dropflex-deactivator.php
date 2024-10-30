<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Deactivator {
    public static function deactivate() {
        // Remover scheduled hooks
        wp_clear_scheduled_hook('dropflex_daily_maintenance');
        wp_clear_scheduled_hook('dropflex_check_subscriptions');
        
        // Limpar capabilities
        self::remove_capabilities();
        
        // Limpar rewrite rules
        flush_rewrite_rules();
    }
    
    private static function remove_capabilities() {
        remove_role('dropflex_customer');
        
        $admin = get_role('administrator');
        $admin->remove_cap('dropflex_manage_sites');
        $admin->remove_cap('dropflex_create_sites');
        $admin->remove_cap('dropflex_manage_templates');
        $admin->remove_cap('dropflex_manage_plans');
    }
}