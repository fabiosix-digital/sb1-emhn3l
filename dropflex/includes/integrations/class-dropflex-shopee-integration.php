<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Shopee_Integration {
    private $api_key;
    private $api_secret;
    private $api_url = 'https://partner.shopeemobile.com/api/v2';
    private $shop_id;
    
    public function __construct() {
        $this->api_key = get_option('dropflex_shopee_api_key');
        $this->api_secret = get_option('dropflex_shopee_api_secret');
        $this->shop_id = get_option('dropflex_shopee_shop_id');
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
        $endpoint = '/product/search_items';
        $query = [
            'keyword' => $params['keywords'] ?? '',
            'category_id' => $params['category'] ?? '',
            'offset' => ($params['page'] ?? 1) - 1,
            'limit' => $params['limit'] ?? 20,
            'shop_id' => $this->shop_id
        ];
        
        $response = $this->make_request('GET', $endpoint, $query);
        
        if (!isset($response['items'])) {
            throw new Exception('Erro ao buscar produtos');
        }
        
        return $response['items'];
    }
    
    private function create_wc_product($product_data) {
        // Verificar se o WooCommerce está ativo
        if (!class_exists('WC_Product')) {
            throw new Exception('WooCommerce não está ativo');
        }
        
        // Criar produto
        $product = new WC_Product();
        
        // Dados básicos
        $product->set_name($product_data['name']);
        $product->set_description($product_data['description']);
        $product->set_regular_price($product_data['price']);
        $product->set_sku($product_data['item_sku']);
        
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
        
        // Variações
        if (!empty($product_data['variations'])) {
            $product = new WC_Product_Variable();
            
            foreach ($product_data['variations'] as $variation) {
                $product_variation = new WC_Product_Variation();
                $product_variation->set_parent_id($product->get_id());
                $product_variation->set_attributes($variation['attributes']);
                $product_variation->set_regular_price($variation['price']);
                $product_variation->set_stock_quantity($variation['stock']);
                $product_variation->save();
            }
        }
        
        // Metadados do Shopee
        $product->update_meta_data('_shopee_id', $product_data['item_id']);
        $product->update_meta_data('_shopee_url', $product_data['item_url']);
        $product->update_meta_data('_shopee_shop_id', $this->shop_id);
        
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
            
            $shopee_id = $product->get_meta('_shopee_id');
            if (!$shopee_id) continue;
            
            try {
                $stock_data = $this->get_product_stock($shopee_id);
                
                if ($product->is_type('variable')) {
                    foreach ($product->get_children() as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        $variation_sku = $variation->get_sku();
                        
                        if (isset($stock_data['variations'][$variation_sku])) {
                            $var_stock = $stock_data['variations'][$variation_sku];
                            $variation->set_stock_quantity($var_stock['stock']);
                            $variation->set_price($var_stock['price']);
                            $variation->save();
                        }
                    }
                } else {
                    $product->set_stock_quantity($stock_data['stock']);
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
            $shopee_id = $product->get_meta('_shopee_id');
            
            if (!$shopee_id) continue;
            
            $items[] = [
                'item_id' => $shopee_id,
                'model_id' => $product->is_type('variation') ? $product->get_id() : 0,
                'quantity' => $item->get_quantity()
            ];
        }
        
        if (empty($items)) {
            return false;
        }
        
        $shipping_address = [
            'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'phone' => $order->get_billing_phone(),
            'address' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'zipcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country()
        ];
        
        $response = $this->create_shopee_order($items, $shipping_address);
        
        if ($response['success']) {
            $order->update_meta_data('_shopee_order_id', $response['order_id']);
            $order->update_meta_data('_shopee_tracking_number', $response['tracking_number']);
            $order->save();
        }
        
        return $response;
    }
    
    private function make_request($method, $endpoint, $params = []) {
        $timestamp = time();
        $base_string = $this->shop_id . $endpoint . $timestamp . $this->api_key . $this->api_secret;
        $sign = hash_hmac('sha256', $base_string, $this->api_secret);
        
        $url = $this->api_url . $endpoint;
        
        $headers = [
            'Authorization' => $sign,
            'Content-Type' => 'application/json'
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        ];
        
        if ($method === 'GET') {
            $url = add_query_arg(array_merge($params, [
                'timestamp' => $timestamp,
                'shop_id' => $this->shop_id,
                'partner_id' => $this->api_key
            ]), $url);
        } else {
            $args['body'] = json_encode(array_merge($params, [
                'timestamp' => $timestamp,
                'shop_id' => $this->shop_id,
                'partner_id' => $this->api_key
            ]));
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
            throw new Exception($data['error']);
        }
        
        return $data;
    }
    
    private function get_product_stock($product_id) {
        $endpoint = '/product/get_item_base_info';
        $response = $this->make_request('GET', $endpoint, ['item_id_list' => [$product_id]]);
        
        if (!isset($response['response']['item_list'][0])) {
            throw new Exception('Produto não encontrado');
        }
        
        return $response['response']['item_list'][0];
    }
    
    private function create_shopee_order($items, $shipping_address) {
        $endpoint = '/order/create_order';
        return $this->make_request('POST', $endpoint, [
            'items' => $items,
            'shipping_address' => $shipping_address
        ]);
    }
}