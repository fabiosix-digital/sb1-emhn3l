<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Payment_Manager {
    private $db;
    private $gateways = [];
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->init_gateways();
    }
    
    private function init_gateways() {
        $this->gateways = [
            'stripe' => new DropFlex_Stripe_Gateway(),
            'mercadopago' => new DropFlex_MercadoPago_Gateway(),
            'paypal' => new DropFlex_PayPal_Gateway()
        ];
    }
    
    public function get_available_gateways() {
        $active_gateways = [];
        
        foreach ($this->gateways as $id => $gateway) {
            if ($gateway->is_available()) {
                $active_gateways[$id] = [
                    'name' => $gateway->get_name(),
                    'description' => $gateway->get_description(),
                    'icon' => $gateway->get_icon()
                ];
            }
        }
        
        return $active_gateways;
    }
    
    public function process_payment($gateway_id, $subscription_data) {
        if (!isset($this->gateways[$gateway_id])) {
            throw new Exception("Gateway de pagamento não encontrado");
        }
        
        $gateway = $this->gateways[$gateway_id];
        
        try {
            // Criar assinatura
            $subscription = $this->create_subscription($subscription_data);
            
            // Processar pagamento
            $payment_result = $gateway->process_payment([
                'subscription_id' => $subscription->id,
                'amount' => $subscription_data['amount'],
                'currency' => 'BRL',
                'customer' => $subscription_data['customer'],
                'payment_method' => $subscription_data['payment_method']
            ]);
            
            // Atualizar status da assinatura
            if ($payment_result['success']) {
                $this->update_subscription_status($subscription->id, 'active');
            }
            
            return $payment_result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function create_subscription($data) {
        $this->db->insert(
            "{$this->db->prefix}dropflex_subscriptions",
            [
                'user_id' => $data['user_id'],
                'plan_id' => $data['plan_id'],
                'status' => 'pending',
                'next_billing' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ],
            ['%d', '%d', '%s', '%s']
        );
        
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_subscriptions WHERE id = %d",
                $this->db->insert_id
            )
        );
    }
    
    private function update_subscription_status($subscription_id, $status) {
        $this->db->update(
            "{$this->db->prefix}dropflex_subscriptions",
            ['status' => $status],
            ['id' => $subscription_id],
            ['%s'],
            ['%d']
        );
    }
    
    public function cancel_subscription($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            throw new Exception("Assinatura não encontrada");
        }
        
        // Cancelar no gateway
        $gateway = $this->get_subscription_gateway($subscription);
        $gateway->cancel_subscription($subscription);
        
        // Atualizar status
        $this->update_subscription_status($subscription_id, 'cancelled');
        
        return true;
    }
    
    private function get_subscription($subscription_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_subscriptions WHERE id = %d",
                $subscription_id
            )
        );
    }
    
    private function get_subscription_gateway($subscription) {
        $gateway_id = get_post_meta($subscription->id, '_payment_gateway', true);
        return isset($this->gateways[$gateway_id]) ? $this->gateways[$gateway_id] : null;
    }
    
    public function check_subscriptions() {
        $subscriptions = $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}dropflex_subscriptions 
            WHERE status = 'active' 
            AND next_billing <= NOW()"
        );
        
        foreach ($subscriptions as $subscription) {
            try {
                $gateway = $this->get_subscription_gateway($subscription);
                if (!$gateway) continue;
                
                $result = $gateway->process_renewal($subscription);
                
                if ($result['success']) {
                    $this->update_subscription_next_billing($subscription->id);
                } else {
                    $this->handle_failed_renewal($subscription);
                }
                
            } catch (Exception $e) {
                // Log erro
                error_log("Erro ao processar renovação da assinatura {$subscription->id}: " . $e->getMessage());
            }
        }
    }
    
    private function update_subscription_next_billing($subscription_id) {
        $this->db->update(
            "{$this->db->prefix}dropflex_subscriptions",
            [
                'next_billing' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ],
            ['id' => $subscription_id],
            ['%s'],
            ['%d']
        );
    }
    
    private function handle_failed_renewal($subscription) {
        // Notificar cliente
        $user = get_user_by('id', $subscription->user_id);
        if ($user) {
            wp_mail(
                $user->user_email,
                'Falha no pagamento da assinatura',
                'Houve uma falha ao processar o pagamento da sua assinatura. Por favor, atualize seus dados de pagamento.'
            );
        }
        
        // Atualizar status
        $this->update_subscription_status($subscription->id, 'failed');
    }
}