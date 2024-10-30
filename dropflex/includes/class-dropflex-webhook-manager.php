<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Webhook_Manager {
    private $db;
    private $logger;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = new DropFlex_Logger();
        
        add_action('rest_api_init', [$this, 'register_webhook_endpoints']);
    }
    
    public function register_webhook_endpoints() {
        register_rest_route('dropflex/v1', '/webhook/yampi', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_yampi_webhook'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('dropflex/v1', '/webhook/appmax', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_appmax_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function handle_yampi_webhook($request) {
        try {
            $payload = $request->get_body();
            $signature = $request->get_header('X-Yampi-Signature');
            
            if (!$signature) {
                throw new Exception('Assinatura não encontrada');
            }
            
            $yampi = new DropFlex_Yampi_Integration();
            if (!$yampi->validate_webhook($payload, $signature)) {
                throw new Exception('Assinatura inválida');
            }
            
            $data = json_decode($payload, true);
            if (!$data) {
                throw new Exception('Payload inválido');
            }
            
            $this->logger->log('Webhook Yampi recebido', 'info', $data);
            
            switch ($data['event']) {
                case 'order.created':
                    return $this->process_yampi_order_created($data['data']);
                    
                case 'order.paid':
                    return $this->process_yampi_order_paid($data['data']);
                    
                case 'order.cancelled':
                    return $this->process_yampi_order_cancelled($data['data']);
                    
                case 'order.shipped':
                    return $this->process_yampi_order_shipped($data['data']);
                    
                default:
                    throw new Exception('Evento não suportado');
            }
            
        } catch (Exception $e) {
            $this->logger->log('Erro no webhook Yampi: ' . $e->getMessage(), 'error');
            return new WP_Error('webhook_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    public function handle_appmax_webhook($request) {
        try {
            $payload = $request->get_body();
            $signature = $request->get_header('X-Appmax-Signature');
            
            if (!$signature) {
                throw new Exception('Assinatura não encontrada');
            }
            
            $appmax = new DropFlex_Appmax_Integration();
            if (!$appmax->validate_webhook($payload, $signature)) {
                throw new Exception('Assinatura inválida');
            }
            
            $data = json_decode($payload, true);
            if (!$data) {
                throw new Exception('Payload inválido');
            }
            
            $this->logger->log('Webhook Appmax recebido', 'info', $data);
            
            switch ($data['event']) {
                case 'order.created':
                    return $this->process_appmax_order_created($data['data']);
                    
                case 'order.paid':
                    return $this->process_appmax_order_paid($data['data']);
                    
                case 'order.cancelled':
                    return $this->process_appmax_order_cancelled($data['data']);
                    
                case 'order.shipped':
                    return $this->process_appmax_order_shipped($data['data']);
                    
                default:
                    throw new Exception('Evento não suportado');
            }
            
        } catch (Exception $e) {
            $this->logger->log('Erro no webhook Appmax: ' . $e->getMessage(), 'error');
            return new WP_Error('webhook_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    private function process_yampi_order_created($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Salvar informações do pedido Yampi
        $order->update_meta_data('_yampi_order_id', $data['order_id']);
        $order->save();
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_yampi_order_paid($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_yampi_order_cancelled($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }
        
        // Atualizar status do pedido
        $order->update_status('cancelled', 'Pedido cancelado via Yampi');
        $order->save();
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_yampi_order_shipped($data) {
        $order_id = $data['external_id'];
        if (!$order_id) {
            throw new Exception('ID do pedido não encontrado');
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_appmax_order_created($data) {
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_appmax_order_paid($data) {
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_appmax_order_cancelled($data) {
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
    
    private function process_appmax_order_shipped($data) {
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
        
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id
        ]);
    }
}