<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Yampi_Integration {
    private $api_key;
    private $token;
    private $alias;
    private $api_url = 'https://api.yampi.com.br/v1';
    
    public function __construct() {
        $this->api_key = get_option('dropflex_yampi_api_key');
        $this->token = get_option('dropflex_yampi_token');
        $this->alias = get_option('dropflex_yampi_alias');
    }
    
    public function is_available() {
        return !empty($this->api_key) && !empty($this->token) && !empty($this->alias);
    }
    
    public function setup_checkout($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            throw new Exception('Produto não encontrado');
        }
        
        $data = [
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'description' => $product->get_description(),
            'images' => $this->get_product_images($product),
            'sku' => $product->get_sku()
        ];
        
        return $this->create_checkout($data);
    }
    
    private function get_product_images($product) {
        $images = [];
        
        if ($product->get_image_id()) {
            $images[] = wp_get_attachment_url($product->get_image_id());
        }
        
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $id) {
            $images[] = wp_get_attachment_url($id);
        }
        
        return $images;
    }
    
    private function create_checkout($data) {
        $endpoint = '/checkouts';
        
        $response = $this->make_request('POST', $endpoint, [
            'checkout' => [
                'name' => $data['name'],
                'price' => $data['price'],
                'description' => $data['description'],
                'images' => $data['images'],
                'sku' => $data['sku'],
                'success_url' => home_url('/obrigado'),
                'cancel_url' => home_url()
            ]
        ]);
        
        return $response['checkout_url'];
    }
    
    private function make_request($method, $endpoint, $params = []) {
        $url = $this->api_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'User-Token' => $this->token,
                'User-Secret-Key' => $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if ($method === 'GET') {
            $url = add_query_arg($params, $url);
        } else {
            $args['body'] = json_encode($params);
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
    
    public function get_orders() {
        $endpoint = '/orders';
        return $this->make_request('GET', $endpoint);
    }
    
    public function sync_orders() {
        $orders = $this->get_orders();
        
        foreach ($orders as $order) {
            $this->update_order_status($order);
        }
    }
    
    private function update_order_status($yampi_order) {
        $order_id = wc_get_order_id_by_order_number($yampi_order['reference']);
        if (!$order_id) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $status_map = [
            'paid' => 'processing',
            'canceled' => 'cancelled',
            'delivered' => 'completed'
        ];
        
        if (isset($status_map[$yampi_order['status']])) {
            $order->update_status($status_map[$yampi_order['status']]);
        }
    }
}