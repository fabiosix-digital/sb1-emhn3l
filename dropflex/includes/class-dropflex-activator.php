<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Activator {
    public static function activate() {
        global $wpdb;
        
        // Criar tabelas necessárias
        self::create_tables();
        
        // Criar páginas necessárias
        self::create_pages();
        
        // Configurar roles e capabilities
        self::setup_roles();
        
        // Definir opções padrão
        self::set_default_options();
        
        // Criar planos padrão
        self::create_default_plans();
        
        // Marcar versão instalada
        update_option('dropflex_version', DROPFLEX_VERSION);
        
        // Limpar cache de rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dropflex_sites (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                domain varchar(255) NOT NULL,
                status varchar(50) NOT NULL,
                template_id bigint(20) DEFAULT NULL,
                customization longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY domain (domain)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dropflex_templates (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                type varchar(50) NOT NULL,
                content longtext NOT NULL,
                preview_image varchar(255),
                status varchar(50) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY type (type)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dropflex_subscriptions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                plan_id bigint(20) NOT NULL,
                status varchar(50) NOT NULL,
                payment_gateway varchar(50) NOT NULL,
                payment_token varchar(255),
                next_billing datetime,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dropflex_plans (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                description text,
                price decimal(10,2) NOT NULL,
                features longtext,
                max_sites int NOT NULL DEFAULT 1,
                status varchar(50) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dropflex_integrations (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                site_id bigint(20) NOT NULL,
                type varchar(50) NOT NULL,
                credentials longtext,
                status varchar(50) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY site_id (site_id),
                KEY type (type)
            ) $charset_collate;"
        ];
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
    
    private static function create_pages() {
        $pages = [
            'dropflex-wizard' => [
                'title' => __('Criar Site', 'dropflex'),
                'content' => '[dropflex_wizard]'
            ],
            'dropflex-dashboard' => [
                'title' => __('Meus Sites', 'dropflex'),
                'content' => '[dropflex_dashboard]'
            ],
            'dropflex-plans' => [
                'title' => __('Planos', 'dropflex'),
                'content' => '[dropflex_plans]'
            ]
        ];
        
        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ]);
            }
        }
    }
    
    private static function setup_roles() {
        add_role('dropflex_customer', __('Cliente DropFlex', 'dropflex'), [
            'read' => true,
            'dropflex_manage_sites' => true,
            'dropflex_create_sites' => true
        ]);
        
        $admin = get_role('administrator');
        $admin->add_cap('dropflex_manage_sites');
        $admin->add_cap('dropflex_create_sites');
        $admin->add_cap('dropflex_manage_templates');
        $admin->add_cap('dropflex_manage_plans');
    }
    
    private static function set_default_options() {
        $default_options = [
            'dropflex_whm_api_url' => '',
            'dropflex_whm_username' => '',
            'dropflex_whm_api_token' => '',
            'dropflex_default_template' => 1,
            'dropflex_max_sites_per_user' => 1,
            'dropflex_enable_dropshipping' => 'yes',
            'dropflex_payment_gateway' => 'stripe'
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    private static function create_default_plans() {
        $plans_manager = new DropFlex_Plans_Manager();
        $plans_manager->install_default_plans();
    }
}