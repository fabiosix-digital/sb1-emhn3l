<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_AliExpress_Integration {
    private $api_key;
    private $api_secret;
    private $api_url = 'https://api.aliexpress.com/v2';
    
    public function __construct() {
        $this->api_key = get_option('dropflex_aliexpress_api_key');
        $this->api_secret = get_option('dropflex_aliexpress_api_secret');
    }
    
    public function import_products($params) {
        try {
            $products = $this->search_products($params);
            
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
    
    private function search_products($params) {
        $endpoint = '/products/search';
        $query = [
            'keywords' => $params['keywords'] ?? '',
            'categoryId' => $params['category'] ?? '',
            'page' => $params['page'] ?? 1,
            'limit' => $params['limit'] ?? 20
        ];
        
        $response = $this->make_request('GET', $endpoint, $query);
        
        if (!isset($response['products'])) {
            throw new Exception('Erro ao buscar produtos');
        }
        
        return $response['products'];
    }
    
    private function create_wc_product($product_data) {
        // Verificar se o WooCommerce está ativo
        if (!class_exists('WC_Product')) {
            throw new Exception('WooCommerce não está ativo');
        }
        
        // Criar produto
        $product = new WC_Product();
        
        // Dados básicos
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        $product->set_short_description($product_data['short_description']);
        $product->set_regular_price($product_data['price']);
        $product->set_sku($product_data['sku']);
        
        // Imagens
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
        
        // Atributos
        if (!empty($product_data['attributes'])) {
            $attributes = [];
            foreach ($product_data['attributes'] as $attr) {
                $attribute = new WC_Product_Attribute();
                $attribute->set_name($attr['name']);
                $attribute->set_options($attr['options']);
                $attribute->set_visible(true);
                $attributes[] = $attribute;
            }
            $product->set_attributes($attributes);
        }
        
        // Metadados do AliExpress
        $product->update_meta_data('_aliexpress_id', $product_data['id']);
        $product->update_meta_data('_aliexpress_url', $product_data['url']);
        $product->update_meta_data('_aliexpress_supplier', $product_data['supplier']);
        
        // Salvar produto
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
        
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );
        
        $id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        return $id;
    }
    
    public function sync_inventory($product_ids) {
        $updated = 0;
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;
            
            $aliexpress_id = $product->get_meta('_aliexpress_id');
            if (!$aliexpress_id) continue;
            
            try {
                $stock_data = $this->get_product_stock($aliexpress_id);
                
                $product->set_stock_quantity($stock_data['quantity']);
                $product->set_stock_status($stock_data['quantity'] > 0 ? 'instock' : 'outofstock');
                
                if (isset($stock_data['price'])) {
                    $product->set_regular_price($stock_data['price']);
                }
                
                $product->save();
                $updated++;
                
            } catch (Exception $e) {
                // Log erro
                error_log("Erro ao sincronizar produto {$product_id}: " . $e->getMessage());
            }
        }
        
        return $updated;
    }
    
    public function process_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        $items = [];
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $aliexpress_id = $product->get_meta('_aliexpress_id');
            
            if (!$aliexpress_id) continue;
            
            $items[] = [
                'product_id' => $aliexpress_id,
                'quantity' => $item->get_quantity(),
                'shipping_address' => [
                    'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'address_1' => $order->get_shipping_address_1(),
                    'address_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postcode' => $order->get_shipping_postcode(),
                    'country' => $order->get_shipping_country()
                ]
            ];
        }
        
        if (empty($items)) {
            return false;
        }
        
        $response = $this->create_aliexpress_order($items);
        
        if ($response['success']) {
            $order->update_meta_data('_aliexpress_order_id', $response['order_id']);
            $order->update_meta_data('_aliexpress_tracking_number', $response['tracking_number']);
            $order->save();
        }
        
        return $response;
    }
    
    private function make_request($method, $endpoint, $params = []) {
        $url = $this->api_url . $endpoint;
        
        $headers = [
            'X-API-KEY' => $this->api_key,
            'X-API-SECRET' => $this->api_secret,
            'Content-Type' => 'application/json'
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
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
    
    private function get_product_stock($product_id) {
        $endpoint = '/product/' . $product_id . '/stock';
        return $this->make_request('GET', $endpoint);
    }
    
    private function create_aliexpress_order($items) {
        $endpoint = '/orders/create';
        return $this->make_request('POST', $endpoint, ['items' => $items]);
    }
}