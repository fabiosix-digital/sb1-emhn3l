<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Payment_Integrations {
    private $db;
    private $available_gateways = [
        'mercadopago' => [
            'name' => 'MercadoPago',
            'class' => 'DropFlex_MercadoPago_Gateway',
            'icon' => 'mercadopago-logo.png'
        ],
        'pagseguro' => [
            'name' => 'PagSeguro',
            'class' => 'DropFlex_PagSeguro_Gateway',
            'icon' => 'pagseguro-logo.png'
        ],
        'stripe' => [
            'name' => 'Stripe',
            'class' => 'DropFlex_Stripe_Gateway',
            'icon' => 'stripe-logo.png'
        ]
    ];
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function get_available_gateways() {
        $gateways = [];
        
        foreach ($this->available_gateways as $id => $gateway) {
            $class = $gateway['class'];
            $instance = new $class();
            
            if ($instance->is_available()) {
                $gateways[$id] = [
                    'name' => $gateway['name'],
                    'icon' => DROPFLEX_ASSETS_URL . 'images/' . $gateway['icon'],
                    'description' => $instance->get_description(),
                    'supports' => $instance->get_supported_features()
                ];
            }
        }
        
        return $gateways;
    }
    
    public function setup_gateway($type, $credentials) {
        if (!isset($this->available_gateways[$type])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$type]['class'];
        $gateway = new $class();
        
        return $gateway->setup($credentials);
    }
    
    public function process_payment($gateway_id, $order_data) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->process_payment($order_data);
    }
    
    public function process_refund($gateway_id, $order_id, $amount = null, $reason = '') {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->process_refund($order_id, $amount, $reason);
    }
    
    public function handle_webhook($gateway_id) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->handle_webhook();
    }
    
    public function get_gateway_settings($gateway_id) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->get_settings();
    }
    
    public function save_gateway_settings($gateway_id, $settings) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->save_settings($settings);
    }
    
    public function test_gateway_connection($gateway_id, $credentials) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->test_connection($credentials);
    }
    
    public function get_payment_methods($gateway_id) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->get_payment_methods();
    }
    
    public function get_installment_options($gateway_id, $amount) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->get_installment_options($amount);
    }
    
    public function get_transaction_details($gateway_id, $transaction_id) {
        if (!isset($this->available_gateways[$gateway_id])) {
            throw new Exception('Gateway de pagamento não suportado');
        }
        
        $class = $this->available_gateways[$gateway_id]['class'];
        $gateway = new $class();
        
        return $gateway->get_transaction_details($transaction_id);
    }
}