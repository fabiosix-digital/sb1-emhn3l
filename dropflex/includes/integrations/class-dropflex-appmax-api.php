<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Appmax_API {
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
    
    public function get_products($params = []) {
        return $this->make_request('GET', '/products', $params);
    }
    
    public function get_product($product_id) {
        return $this->make_request('GET', "/products/{$product_id}");
    }
    
    public function create_order($data) {
        return $this->make_request('POST', '/orders', $data);
    }
    
    public function get_order($order_id) {
        return $this->make_request('GET', "/orders/{$order_id}");
    }
    
    public function update_order_status($order_id, $status) {
        return $this->make_request('PUT', "/orders/{$order_id}/status", [
            'status' => $status
        ]);
    }
    
    public function get_shipping_rates($data) {
        return $this->make_request('POST', '/shipping/calculate', $data);
    }
    
    public function create_customer($data) {
        return $this->make_request('POST', '/customers', $data);
    }
    
    public function get_customer($customer_id) {
        return $this->make_request('GET', "/customers/{$customer_id}");
    }
    
    public function update_customer($customer_id, $data) {
        return $this->make_request('PUT', "/customers/{$customer_id}", $data);
    }
    
    public function create_payment($data) {
        return $this->make_request('POST', '/payments', $data);
    }
    
    public function get_payment($payment_id) {
        return $this->make_request('GET', "/payments/{$payment_id}");
    }
    
    public function refund_payment($payment_id, $data) {
        return $this->make_request('POST', "/payments/{$payment_id}/refund", $data);
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
        
        if (isset($data['error'])) {
            throw new Exception($data['error']['message']);
        }
        
        return $data;
    }
    
    public function validate_webhook($payload, $signature) {
        $expected = hash_hmac('sha256', $payload, $this->secret_key);
        return hash_equals($expected, $signature);
    }
    
    public function format_order_data($order) {
        return [
            'external_id' => $order->get_id(),
            'customer' => [
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'document' => get_post_meta($order->get_id(), '_billing_cpf', true)
            ],
            'shipping_address' => [
                'street' => $order->get_shipping_address_1(),
                'number' => get_post_meta($order->get_id(), '_shipping_number', true),
                'complement' => $order->get_shipping_address_2(),
                'district' => get_post_meta($order->get_id(), '_shipping_neighborhood', true),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'zipcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country()
            ],
            'items' => array_map(function($item) {
                return [
                    'name' => $item->get_name(),
                    'sku' => $item->get_product()->get_sku(),
                    'quantity' => $item->get_quantity(),
                    'price' => $item->get_total() / $item->get_quantity()
                ];
            }, $order->get_items()),
            'shipping' => [
                'price' => $order->get_shipping_total()
            ],
            'total' => $order->get_total()
        ];
    }
    
    public function format_product_data($product) {
        return [
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'description' => $product->get_description(),
            'price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock' => $product->get_stock_quantity(),
            'weight' => $product->get_weight(),
            'height' => $product->get_height(),
            'width' => $product->get_width(),
            'length' => $product->get_length(),
            'images' => array_map(function($image_id) {
                return wp_get_attachment_url($image_id);
            }, $product->get_gallery_image_ids())
        ];
    }
}