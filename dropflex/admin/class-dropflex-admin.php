<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Admin {
    private $plugin_name;
    private $version;
    private $settings;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = new DropFlex_Settings();
    }
    
    public function enqueue_styles() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('dropflex-admin', DROPFLEX_ASSETS_URL . 'css/admin.css', array(), $this->version, 'all');
        wp_enqueue_style('dropflex-wizard', DROPFLEX_ASSETS_URL . 'css/wizard.css', array(), $this->version, 'all');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();
        
        wp_enqueue_script(
            'dropflex-admin',
            DROPFLEX_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'dropflex-wizard',
            DROPFLEX_ASSETS_URL . 'js/wizard.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            true
        );
        
        wp_localize_script('dropflex-admin', 'dropflexAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => get_rest_url(null, 'dropflex/v1'),
            'nonce' => wp_create_nonce('dropflex_nonce'),
            'messages' => array(
                'confirm_delete' => __('Tem certeza que deseja excluir este item?', 'dropflex'),
                'creating_site' => __('Criando seu site, aguarde...', 'dropflex'),
                'site_created' => __('Site criado com sucesso!', 'dropflex'),
                'error' => __('Ocorreu um erro. Tente novamente.', 'dropflex')
            )
        ));
    }
    
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('DropFlex', 'dropflex'),
            __('DropFlex', 'dropflex'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_dashboard_page'),
            'dashicons-networking',
            26
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'dropflex'),
            __('Dashboard', 'dropflex'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_dashboard_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Sites', 'dropflex'),
            __('Sites', 'dropflex'),
            'manage_options',
            $this->plugin_name . '-sites',
            array($this, 'display_plugin_sites_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Templates', 'dropflex'),
            __('Templates', 'dropflex'),
            'manage_options',
            $this->plugin_name . '-templates',
            array($this, 'display_plugin_templates_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Planos', 'dropflex'),
            __('Planos', 'dropflex'),
            'manage_options',
            $this->plugin_name . '-plans',
            array($this, 'display_plugin_plans_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Configurações', 'dropflex'),
            __('Configurações', 'dropflex'),
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );
    }
    
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Configurações', 'dropflex') . '</a>',
        );
        return array_merge($settings_link, $links);
    }
    
    public function display_plugin_dashboard_page() {
        include_once DROPFLEX_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function display_plugin_sites_page() {
        include_once DROPFLEX_PLUGIN_DIR . 'admin/views/sites.php';
    }
    
    public function display_plugin_templates_page() {
        include_once DROPFLEX_PLUGIN_DIR . 'admin/views/templates.php';
    }
    
    public function display_plugin_plans_page() {
        include_once DROPFLEX_PLUGIN_DIR . 'admin/views/plans.php';
    }
    
    public function display_plugin_settings_page() {
        include_once DROPFLEX_PLUGIN_DIR . 'admin/views/settings.php';
    }
}