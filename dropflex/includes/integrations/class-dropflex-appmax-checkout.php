<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Appmax_Checkout {
    private $api_url = 'https://api.appmax.com.br/v3';
    private $api_key;
    private $secret_key;
    
    public function __construct() {
        $this->api_key = get_option('dropflex_appmax_api_key');
        $this->secret_key = get_option('dropflex_appmax_secret_key');
    }
    
    public function is_available() {
        return !empty($this->api_key) && !empty($this->secret_key);
    }
    
    public function create_checkout_session($order_data) {
        try {
            $endpoint = '/checkout/create';
            
            $data = [
                'external_id' => $order_data['order_id'],
                'items' => $this->format_items($order_data['items']),
                'customer' => $this->format_customer($order_data['customer']),
                'shipping' => [
                    'address' => $order_data['shipping_address'],
                    'service' => $order_data['shipping_service']
                ],
                'success_url' => $order_data['success_url'],
                'cancel_url' => $order_data['cancel_url'],
                'metadata' => [
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
                'price' => $item['price'],
                'image_url' => $item['image'],
                'description' => $item['description']
            ];
        }
        
        return $formatted;
    }
    
    private function format_customer($customer) {
        return [
            'name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'document' => $customer['document']
        ];
    }
    
    public function process_webhook($payload) {
        try {
            $this->validate_webhook_signature();
            
            switch ($payload['event']) {
                case 'order.created':
                    return $this->handle_order_created($payload['data']);
                    
                case 'order.paid':
                    return $this->handle_order_paid($payload['data']);
                    
                case 'order.canceled':
                    return $this->handle_order_canceled($payload['data']);
                    
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
        if (!isset($_SERVER['HTTP_X_APPMAX_SIGNATURE'])) {
            throw new Exception('Assinatura do webhook não encontrada');
        }
        
        $signature = $_SERVER['HTTP_X_APPMAX_SIGNATURE'];
        $payload = file_get_contents('php://input');
        
        $expected = hash_hmac('sha256', $payload, $this->secret_key);
        
        if (!hash_equals($expected, $signature)) {
            throw new Exception('Assinatura do webhook inválida');
        }
    }
    
    private function handle_order_created($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Salvar informações do pedido Appmax
        $order->update_meta_data('_appmax_order_id', $data['order_id']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_order_paid($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('processing', 'Pagamento confirmado via Appmax');
        
        // Salvar informações do pagamento
        $order->update_meta_data('_appmax_payment_method', $data['payment_method']);
        $order->update_meta_data('_appmax_payment_id', $data['payment_id']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_order_canceled($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('cancelled', 'Pedido cancelado via Appmax');
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function handle_order_shipped($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('completed', 'Pedido enviado via Appmax');
        
        // Salvar informações de envio
        $order->update_meta_data('_appmax_tracking_code', $data['tracking_code']);
        $order->update_meta_data('_appmax_shipping_company', $data['shipping_company']);
        $order->save();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    }
    
    private function make_request($method, $endpoint, $data = []) {
        $url = $this->api_url . $endpoint;
        
        $headers = [
            'X-API-KEY' => $this->api_key,
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
}</content>