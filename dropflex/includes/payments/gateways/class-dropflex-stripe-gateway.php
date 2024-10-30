<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Stripe_Gateway {
    private $api_key;
    private $webhook_secret;
    
    public function __construct() {
        $this->api_key = get_option('dropflex_stripe_api_key');
        $this->webhook_secret = get_option('dropflex_stripe_webhook_secret');
        
        if ($this->is_available()) {
            require_once DROPFLEX_PLUGIN_DIR . 'vendor/autoload.php';
            \Stripe\Stripe::setApiKey($this->api_key);
        }
    }
    
    public function is_available() {
        return !empty($this->api_key) && !empty($this->webhook_secret);
    }
    
    public function get_name() {
        return 'Stripe';
    }
    
    public function get_description() {
        return 'Pagamentos via cartão de crédito com Stripe';
    }
    
    public function get_icon() {
        return DROPFLEX_ASSETS_URL . 'images/stripe-logo.png';
    }
    
    public function process_payment($data) {
        try {
            // Criar ou recuperar cliente
            $customer = $this->get_or_create_customer($data['customer']);
            
            // Criar método de pagamento
            $payment_method = \Stripe\PaymentMethod::create([
                'type' => 'card',
                'card' => $data['payment_method']
            ]);
            
            // Anexar método ao cliente
            $payment_method->attach(['customer' => $customer->id]);
            
            // Criar assinatura
            $subscription = \Stripe\Subscription::create([
                'customer' => $customer->id,
                'items' => [[
                    'price' => $this->get_price_id($data['amount'])
                ]],
                'default_payment_method' => $payment_method->id,
                'expand' => ['latest_invoice.payment_intent']
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret
            ];
            
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao processar pagamento'
            ];
        }
    }
    
    private function get_or_create_customer($customer_data) {
        try {
            return \Stripe\Customer::create([
                'email' => $customer_data['email'],
                'name' => $customer_data['name'],
                'metadata' => [
                    'user_id' => $customer_data['user_id']
                ]
            ]);
        } catch (Exception $e) {
            throw new Exception('Erro ao criar cliente no Stripe');
        }
    }
    
    private function get_price_id($amount) {
        // Implementar lógica para recuperar ou criar price no Stripe
        return 'price_xxxxx';
    }
    
    public function process_renewal($subscription) {
        try {
            $stripe_subscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
            $invoice = $stripe_subscription->latest_invoice;
            
            if ($invoice->status === 'paid') {
                return ['success' => true];
            }
            
            return ['success' => false];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function cancel_subscription($subscription) {
        try {
            $stripe_subscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
            $stripe_subscription->cancel();
            return true;
        } catch (Exception $e) {
            throw new Exception('Erro ao cancelar assinatura no Stripe');
        }
    }
    
    public function handle_webhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $this->webhook_secret
            );
            
            switch ($event->type) {
                case 'invoice.payment_succeeded':
                    $this->handle_payment_succeeded($event->data->object);
                    break;
                    
                case 'invoice.payment_failed':
                    $this->handle_payment_failed($event->data->object);
                    break;
                    
                case 'customer.subscription.deleted':
                    $this->handle_subscription_cancelled($event->data->object);
                    break;
            }
            
            return true;
            
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }
    }
    
    private function handle_payment_succeeded($invoice) {
        $subscription_id = $this->get_subscription_id_from_stripe($invoice->subscription);
        if ($subscription_id) {
            $payment_manager = new DropFlex_Payment_Manager();
            $payment_manager->update_subscription_status($subscription_id, 'active');
        }
    }
    
    private function handle_payment_failed($invoice) {
        $subscription_id = $this->get_subscription_id_from_stripe($invoice->subscription);
        if ($subscription_id) {
            $payment_manager = new DropFlex_Payment_Manager();
            $payment_manager->handle_failed_renewal($subscription_id);
        }
    }
    
    private function handle_subscription_cancelled($subscription) {
        $subscription_id = $this->get_subscription_id_from_stripe($subscription->id);
        if ($subscription_id) {
            $payment_manager = new DropFlex_Payment_Manager();
            $payment_manager->update_subscription_status($subscription_id, 'cancelled');
        }
    }
    
    private function get_subscription_id_from_stripe($stripe_subscription_id) {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}dropflex_subscriptions WHERE stripe_id = %s",
                $stripe_subscription_id
            )
        );
    }
}