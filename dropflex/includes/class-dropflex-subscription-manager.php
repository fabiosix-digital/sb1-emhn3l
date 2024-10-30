<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Subscription_Manager {
    private $db;
    private $payment_manager;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->payment_manager = new DropFlex_Payment_Manager();
        
        add_action('init', [$this, 'schedule_subscription_checks']);
        add_action('dropflex_check_subscriptions', [$this, 'check_subscriptions']);
    }
    
    public function create_subscription($user_id, $plan_id, $payment_data) {
        try {
            // 1. Validar plano
            $plan = $this->get_plan($plan_id);
            if (!$plan) {
                throw new Exception('Plano não encontrado');
            }
            
            // 2. Processar pagamento
            $payment_result = $this->payment_manager->process_payment(
                $payment_data['gateway'],
                [
                    'amount' => $plan->price,
                    'currency' => 'BRL',
                    'customer' => $payment_data['customer'],
                    'payment_method' => $payment_data['payment_method']
                ]
            );
            
            if (!$payment_result['success']) {
                throw new Exception($payment_result['error']);
            }
            
            // 3. Criar assinatura
            $subscription_id = $this->db->insert(
                "{$this->db->prefix}dropflex_subscriptions",
                [
                    'user_id' => $user_id,
                    'plan_id' => $plan_id,
                    'status' => 'active',
                    'next_billing' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'payment_gateway' => $payment_data['gateway'],
                    'payment_token' => $payment_result['payment_token']
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );
            
            if (!$subscription_id) {
                throw new Exception('Erro ao criar assinatura');
            }
            
            // 4. Atualizar meta do usuário
            update_user_meta($user_id, '_dropflex_subscription_id', $subscription_id);
            update_user_meta($user_id, '_dropflex_plan_id', $plan_id);
            
            return [
                'success' => true,
                'subscription_id' => $subscription_id
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function cancel_subscription($subscription_id) {
        try {
            $subscription = $this->get_subscription($subscription_id);
            if (!$subscription) {
                throw new Exception('Assinatura não encontrada');
            }
            
            // Cancelar no gateway de pagamento
            $result = $this->payment_manager->cancel_subscription(
                $subscription->payment_gateway,
                $subscription->payment_token
            );
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Atualizar status da assinatura
            $this->db->update(
                "{$this->db->prefix}dropflex_subscriptions",
                ['status' => 'cancelled'],
                ['id' => $subscription_id],
                ['%s'],
                ['%d']
            );
            
            // Remover meta do usuário
            delete_user_meta($subscription->user_id, '_dropflex_subscription_id');
            delete_user_meta($subscription->user_id, '_dropflex_plan_id');
            
            return [
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function get_subscription($subscription_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_subscriptions WHERE id = %d",
                $subscription_id
            )
        );
    }
    
    public function get_user_subscription($user_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT s.*, p.* 
                FROM {$this->db->prefix}dropflex_subscriptions s 
                JOIN {$this->db->prefix}dropflex_plans p ON s.plan_id = p.id 
                WHERE s.user_id = %d AND s.status = 'active'",
                $user_id
            )
        );
    }
    
    public function get_plan($plan_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_plans WHERE id = %d",
                $plan_id
            )
        );
    }
    
    public function get_available_plans() {
        return $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}dropflex_plans 
            WHERE status = 'active' 
            ORDER BY price ASC"
        );
    }
    
    public function schedule_subscription_checks() {
        if (!wp_next_scheduled('dropflex_check_subscriptions')) {
            wp_schedule_event(time(), 'daily', 'dropflex_check_subscriptions');
        }
    }
    
    public function check_subscriptions() {
        $subscriptions = $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}dropflex_subscriptions 
            WHERE status = 'active' 
            AND next_billing <= NOW()"
        );
        
        foreach ($subscriptions as $subscription) {
            $this->process_renewal($subscription);
        }
    }
    
    private function process_renewal($subscription) {
        try {
            $plan = $this->get_plan($subscription->plan_id);
            if (!$plan) {
                throw new Exception('Plano não encontrado');
            }
            
            // Processar pagamento da renovação
            $result = $this->payment_manager->process_renewal(
                $subscription->payment_gateway,
                $subscription->payment_token,
                $plan->price
            );
            
            if ($result['success']) {
                // Atualizar próxima cobrança
                $this->db->update(
                    "{$this->db->prefix}dropflex_subscriptions",
                    ['next_billing' => date('Y-m-d H:i:s', strtotime('+30 days'))],
                    ['id' => $subscription->id],
                    ['%s'],
                    ['%d']
                );
                
                // Registrar pagamento
                $this->register_payment($subscription->id, $plan->price);
                
            } else {
                // Notificar cliente sobre falha no pagamento
                $this->notify_payment_failure($subscription);
                
                // Atualizar status após tentativas
                if ($this->get_failed_attempts($subscription->id) >= 3) {
                    $this->cancel_subscription($subscription->id);
                }
            }
            
        } catch (Exception $e) {
            // Log erro
            error_log("Erro ao processar renovação da assinatura {$subscription->id}: " . $e->getMessage());
        }
    }
    
    private function register_payment($subscription_id, $amount) {
        return $this->db->insert(
            "{$this->db->prefix}dropflex_payments",
            [
                'subscription_id' => $subscription_id,
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%f', '%s', '%s']
        );
    }
    
    private function notify_payment_failure($subscription) {
        $user = get_user_by('id', $subscription->user_id);
        if (!$user) return;
        
        $subject = sprintf(
            __('Falha no pagamento da sua assinatura DropFlex - Tentativa %d', 'dropflex'),
            $this->get_failed_attempts($subscription->id)
        );
        
        $message = sprintf(
            __('Olá %s,

Não conseguimos processar o pagamento da sua assinatura DropFlex. Por favor, atualize suas informações de pagamento para evitar a suspensão do serviço.

Acesse sua conta: %s

Se precisar de ajuda, entre em contato conosco.

Atenciosamente,
Equipe DropFlex', 'dropflex'),
            $user->display_name,
            admin_url('admin.php?page=dropflex-subscription')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    private function get_failed_attempts($subscription_id) {
        return (int) get_post_meta($subscription_id, '_failed_attempts', true);
    }
    
    private function increment_failed_attempts($subscription_id) {
        $attempts = $this->get_failed_attempts($subscription_id);
        update_post_meta($subscription_id, '_failed_attempts', $attempts + 1);
    }
    
    public function get_subscription_status($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }
        
        return [
            'status' => $subscription->status,
            'plan' => $this->get_plan($subscription->plan_id),
            'next_billing' => $subscription->next_billing,
            'payment_gateway' => $subscription->payment_gateway,
            'created_at' => $subscription->created_at
        ];
    }
    
    public function can_create_site($user_id) {
        $subscription = $this->get_user_subscription($user_id);
        if (!$subscription) {
            return false;
        }
        
        $plan = $this->get_plan($subscription->plan_id);
        $current_sites = $this->get_user_sites_count($user_id);
        
        return $current_sites < $plan->max_sites;
    }
    
    private function get_user_sites_count($user_id) {
        return $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}dropflex_sites 
                WHERE user_id = %d AND status != 'deleted'",
                $user_id
            )
        );
    }
}