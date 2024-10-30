<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Yampi_Checkout_Manager {
    private $api_url = 'https://api.yampi.com.br/v1';
    private $alias;
    private $token;
    private $secret_key;
    
    public function __construct() {
        $this->alias = get_option('dropflex_yampi_alias');
        $this->token = get_option('dropflex_yampi_token');
        $this->secret_key = get_option('dropflex_yampi_secret_key');
    }
    
    public function is_available() {
        return !empty($this->alias) && !empty($this->token) && !empty($this->secret_key);
    }
    
    public function create_checkout_session($order_data) {
        try {
            $endpoint = '/checkouts';
            
            $data = [
                'alias' => $this->alias,
                'items' => $this->format_items($order_data['items']),
                'customer' => $this->format_customer($order_data['customer']),
                'shipping_address' => $order_data['shipping_address'],
                'success_url' => $order_data['success_url'],
                'cancel_url' => $order_data['cancel_url'],
                'metadata' => [
                    'order_id' => $order_data['order_id'],
                    'site_id' => $order_data['site_id']
                ]
            ];
            
            $response = $this->make_request('POST', $endpoint, $data);
            
            if (!isset($response['checkout_url'])) {
                throw new Exception('URL do checkout não encontrada na resposta');
            }
            
            return [
                'success' => true,
                'checkout_url' => $response['checkout_url']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function format_items($items) {
        $formatted = [];
        
        foreach ($items as $item) {
            $formatted[] = [
                'name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'image_url' => $item['image'],
                'description' => $item['description']
            ];
        }
        
        return $formatted;
    }
    
    private function format_customer($customer) {
        return [
            'first_name' => $customer['first_name'],
            'last_name' => $customer['last_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'document' => $customer['document']
        ];
    }
    
    public function process_webhook($payload) {
        try {
            $this->validate_webhook_signature();
            
            switch ($payload['event']) {
                case 'checkout.success':
                    return $this->handle_checkout_success($payload['data']);
                    
                case 'checkout.canceled':
                    return $this->handle_checkout_canceled($payload['data']);
                    
                case 'order.paid':
                    return $this->handle_order_paid($payload['data']);
                    
                case 'order.shipped':
                    return $this->handle_order_shipped($payload['data']);
                    
                default:
                    throw new Exception('Evento não suportado');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validate_webhook_signature() {
        if (!isset($_SERVER['HTTP_X_YAMPI_SIGNATURE'])) {
            throw new Exception('Assinatura do webhook não encontrada');
        }
        
        $signature = $_SERVER['HTTP_X_YAMPI_SIGNATURE'];
        $payload = file_get_contents('php://input');
        
        $expected = hash_hmac('sha256', $payload, $this->secret_key);
        
        if (!hash_equals($expected, $signature)) {
            throw new Exception('Assinatura do webhook inválida');
        }
    }
    
    private function handle_checkout_success($data) {
        $order_id = $data['metadata']['order_id'] ?? null;
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('processing', 'Pagamento confirmado via Yampi Checkout');
        
        // Salvar informações adicionais
        $order->update_meta_data('_yampi_checkout_id', $data['checkout_id']);
        $order->update_meta_data('_yampi_order_id', $data['order_id']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_checkout_canceled($data) {
        $order_id = $data['metadata']['order_id'] ?? null;
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('cancelled', 'Checkout cancelado pelo cliente');
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_order_paid($data) {
        $order_id = $this->get_order_id_from_yampi($data['order_id']);
        if (!$order_id) {
            throw new Exception('Pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('processing', 'Pagamento confirmado via Yampi');
        
        // Salvar informações do pagamento
        $order->update_meta_data('_yampi_payment_method', $data['payment_method']);
        $order->update_meta_data('_yampi_payment_id', $data['payment_id']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_order_shipped($data) {
        $order_id = $this->get_order_id_from_yampi($data['order_id']);
        if (!$order_id) {
            throw new Exception('Pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('completed', 'Pedido enviado via Yampi');
        
        // Salvar informações de envio
        $order->update_meta_data('_yampi_tracking_code', $data['tracking_code']);
        $order->update_meta_data('_yampi_shipping_company', $data['shipping_company']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function get_order_id_from_yampi($yampi_order_id) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_yampi_order_id' 
                AND meta_value = %s",
                $yampi_order_id
            )
        );
    }
    
    private function make_request($method, $endpoint, $data = []) {
        $url = $this->api_url . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        ];
        
        if ($method === 'GET') {
            $url = add_query_arg($data, $url);
        } else {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar resposta da API');
        }
        
        return $data;
    }
}