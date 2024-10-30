<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Checkout_Manager {
    private $yampi_checkout;
    private $appmax_checkout;
    
    public function __construct() {
        $this->yampi_checkout = new DropFlex_Yampi_Checkout_Manager();
        $this->appmax_checkout = new DropFlex_Appmax_Checkout_Manager();
    }
    
    public function get_available_checkouts() {
        $checkouts = [];
        
        if ($this->yampi_checkout->is_available()) {
            $checkouts['yampi'] = [
                'name' => 'Yampi Checkout',
                'description' => 'Checkout otimizado da Yampi',
                'icon' => DROPFLEX_ASSETS_URL . 'images/yampi-logo.png'
            ];
        }
        
        if ($this->appmax_checkout->is_available()) {
            $checkouts['appmax'] = [
                'name' => 'Appmax Checkout',
                'description' => 'Checkout completo da Appmax',
                'icon' => DROPFLEX_ASSETS_URL . 'images/appmax-logo.png'
            ];
        }
        
        return $checkouts;
    }
    
    public function create_checkout_session($type, $order_data) {
        switch ($type) {
            case 'yampi':
                return $this->yampi_checkout->create_checkout_session($order_data);
                
            case 'appmax':
                return $this->appmax_checkout->create_checkout_session($order_data);
                
            default:
                throw new Exception('Tipo de checkout não suportado');
        }
    }
    
    public function process_webhook($type, $payload) {
        switch ($type) {
            case 'yampi':
                return $this->yampi_checkout->process_webhook($payload);
                
            case 'appmax':
                return $this->appmax_checkout->process_webhook($payload);
                
            default:
                throw new Exception('Tipo de checkout não suportado');
        }
    }
    
    public function save_checkout_settings($site_id, $settings) {
        global $wpdb;
        
        $this->validate_checkout_settings($settings);
        
        return $wpdb->update(
            "{$wpdb->prefix}dropflex_sites",
            [
                'checkout_type' => $settings['type'],
                'checkout_settings' => json_encode($settings['config'])
            ],
            ['id' => $site_id],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    private function validate_checkout_settings($settings) {
        if (!isset($settings['type'])) {
            throw new Exception('Tipo de checkout não especificado');
        }
        
        switch ($settings['type']) {
            case 'yampi':
                $this->validate_yampi_settings($settings['config']);
                break;
                
            case 'appmax':
                $this->validate_appmax_settings($settings['config']);
                break;
                
            default:
                throw new Exception('Tipo de checkout não suportado');
        }
    }
    
    private function validate_yampi_settings($config) {
        $required = ['alias', 'token', 'secret_key'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
    }
    
    private function validate_appmax_settings($config) {
        if (empty($config['api_key']) || empty($config['secret_key'])) {
            throw new Exception('Credenciais da Appmax são obrigatórias');
        }
    }
    
    public function get_checkout_settings($site_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT checkout_type, checkout_settings 
                FROM {$wpdb->prefix}dropflex_sites 
                WHERE id = %d",
                $site_id
            )
        );
    }
}