<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Appmax_Integration {
    private $api_key;
    private $api_url = 'https://api.appmax.com.br/v3';
    
    public function __construct() {
        $this->api_key = get_option('dropflex_appmax_api_key');
    }
    
    public function is_available() {
        return !empty($this->api_key);
    }
    
    public function import_products($params = []) {
        try {
            $products = $this->get_products($params);
            
            foreach ($products as $product) {
                $this->create_wc_product($product);
            }
            
            return [
                'success' => true,
                'imported' => count($products)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function get_products($params = []) {
        $endpoint = '/products';
        return $this->make_request('GET', $endpoint, $params);
    }
    
    private function create_wc_product($product_data) {
        $product = new WC_Product();
        
        $product->set_name($product_data['name']);
        $product->set_regular_price($product_data['price']);
        $product->set_description($product_data['description']);
        $product->set_sku($product_data['sku']);
        
        if (!empty($product_data['images'])) {
            $image_ids = [];
            foreach ($product_data['images'] as $image_url) {
                $image_id = $this->upload_image($image_url);
                if ($image_id) {
                    $image_ids[] = $image_id;
                }
            }
            
            if (!empty($image_ids)) {
                $product->set_image_id($image_ids[0]);
                if (count($image_ids) > 1) {
                    $product->set_gallery_image_ids(array_slice($image_ids, 1));
                }
            }
        }
        
        $product->save();
        
        return $product->get_id();
    }
    
    private function upload_image($url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = [
            'name' => basename($url),
            'tmp_name' => $tmp
        ];
        
        $id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        return $id;
    }
    
    public function sync_orders() {
        $orders = $this->get_orders();
        
        foreach ($orders as $order) {
            $this->update_order_status($order);
        }
    }
    
    private function make_request($method, $endpoint, $params = []) {
        $url = $this->api_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
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
}