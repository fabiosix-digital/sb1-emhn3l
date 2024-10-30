<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Dropshipping {
    private $providers = [
        'aliexpress' => [
            'name' => 'AliExpress',
            'class' => 'DropFlex_AliExpress_Integration'
        ],
        'shopee' => [
            'name' => 'Shopee',
            'class' => 'DropFlex_Shopee_Integration'
        ]
    ];
    
    public function get_providers() {
        return $this->providers;
    }
    
    public function import_products($provider, $params) {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Provedor não suportado");
        }
        
        $class = $this->providers[$provider]['class'];
        $integration = new $class();
        
        return $integration->import_products($params);
    }
    
    public function sync_inventory($provider, $product_ids) {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Provedor não suportado");
        }
        
        $class = $this->providers[$provider]['class'];
        $integration = new $class();
        
        return $integration->sync_inventory($product_ids);
    }
    
    public function process_order($provider, $order_id) {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Provedor não suportado");
        }
        
        $class = $this->providers[$provider]['class'];
        $integration = new $class();
        
        return $integration->process_order($order_id);
    }
}