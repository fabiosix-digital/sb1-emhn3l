<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Wizard_Manager {
    private $store_creator;
    private $template_manager;
    private $integration_manager;
    private $steps = [
        'welcome' => [
            'title' => 'Bem-vindo',
            'description' => 'Vamos criar sua loja online'
        ],
        'store' => [
            'title' => 'Dados da Loja',
            'description' => 'Informações básicas da sua loja'
        ],
        'business' => [
            'title' => 'Tipo de Negócio',
            'description' => 'Escolha o modelo ideal para seu negócio'
        ],
        'template' => [
            'title' => 'Template',
            'description' => 'Escolha o design da sua loja'
        ],
        'customization' => [
            'title' => 'Personalização',
            'description' => 'Personalize cores e elementos'
        ],
        'integrations' => [
            'title' => 'Integrações',
            'description' => 'Configure suas integrações'
        ],
        'checkout' => [
            'title' => 'Checkout',
            'description' => 'Configure seu checkout'
        ],
        'finish' => [
            'title' => 'Finalização',
            'description' => 'Sua loja está pronta!'
        ]
    ];
    
    public function __construct() {
        $this->store_creator = new DropFlex_Store_Creator();
        $this->template_manager = new DropFlex_Template_Manager();
        $this->integration_manager = new DropFlex_Integration_Manager();
        
        add_action('wp_ajax_dropflex_save_wizard_step', [$this, 'ajax_save_step']);
        add_action('wp_ajax_dropflex_check_domain', [$this, 'ajax_check_domain']);
    }
    
    public function get_current_step() {
        $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'welcome';
        return array_key_exists($step, $this->steps) ? $step : 'welcome';
    }
    
    public function get_step_data($step) {
        return isset($this->steps[$step]) ? $this->steps[$step] : null;
    }
    
    public function render_wizard() {
        $current_step = $this->get_current_step();
        $step_data = $this->get_step_data($current_step);
        
        if (!$step_data) {
            wp_redirect(admin_url());
            exit;
        }
        
        // Carregar template do wizard
        include DROPFLEX_PLUGIN_DIR . 'public/wizard/wizard.php';
    }
    
    public function render_step($step) {
        $file = DROPFLEX_PLUGIN_DIR . 'public/wizard/steps/' . $step . '.php';
        
        if (file_exists($file)) {
            include $file;
            return true;
        }
        
        return false;
    }
    
    public function ajax_save_step() {
        check_ajax_referer('dropflex_wizard', 'nonce');
        
        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
        $data = isset($_POST['data']) ? $_POST['data'] : [];
        
        if (!$step || !isset($this->steps[$step])) {
            wp_send_json_error('Passo inválido');
        }
        
        try {
            $result = $this->save_step_data($step, $data);
            
            wp_send_json_success([
                'next_step' => $this->get_next_step($step),
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function save_step_data($step, $data) {
        $user_id = get_current_user_id();
        
        switch ($step) {
            case 'store':
                return $this->save_store_data($user_id, $data);
                
            case 'business':
                return $this->save_business_type($user_id, $data);
                
            case 'template':
                return $this->save_template_selection($user_id, $data);
                
            case 'customization':
                return $this->save_customization($user_id, $data);
                
            case 'integrations':
                return $this->save_integrations($user_id, $data);
                
            case 'checkout':
                return $this->save_checkout($user_id, $data);
                
            default:
                throw new Exception('Passo não implementado');
        }
    }
    
    private function save_store_data($user_id, $data) {
        // Validar dados básicos da loja
        if (empty($data['name']) || empty($data['domain'])) {
            throw new Exception('Nome da loja e domínio são obrigatórios');
        }
        
        // Verificar disponibilidade do domínio
        if ($this->domain_exists($data['domain'])) {
            throw new Exception('Este domínio já está em uso');
        }
        
        // Salvar dados temporários
        update_user_meta($user_id, '_dropflex_wizard_store', $data);
        
        return [
            'name' => $data['name'],
            'domain' => $data['domain']
        ];
    }
    
    private function save_business_type($user_id, $data) {
        if (empty($data['type'])) {
            throw new Exception('Tipo de negócio é obrigatório');
        }
        
        update_user_meta($user_id, '_dropflex_wizard_business', $data);
        
        return [
            'type' => $data['type']
        ];
    }
    
    private function save_template_selection($user_id, $data) {
        if (empty($data['template_id'])) {
            throw new Exception('Template é obrigatório');
        }
        
        update_user_meta($user_id, '_dropflex_wizard_template', $data);
        
        return [
            'template_id' => $data['template_id']
        ];
    }
    
    private function save_customization($user_id, $data) {
        update_user_meta($user_id, '_dropflex_wizard_customization', $data);
        
        return [
            'customization' => $data
        ];
    }
    
    private function save_integrations($user_id, $data) {
        update_user_meta($user_id, '_dropflex_wizard_integrations', $data);
        
        return [
            'integrations' => $data
        ];
    }
    
    private function save_checkout($user_id, $data) {
        update_user_meta($user_id, '_dropflex_wizard_checkout', $data);
        
        return [
            'checkout' => $data
        ];
    }
    
    public function get_next_step($current_step) {
        $steps = array_keys($this->steps);
        $current_index = array_search($current_step, $steps);
        
        if ($current_index !== false && isset($steps[$current_index + 1])) {
            return $steps[$current_index + 1];
        }
        
        return '';
    }
    
    public function get_prev_step($current_step) {
        $steps = array_keys($this->steps);
        $current_index = array_search($current_step, $steps);
        
        if ($current_index !== false && $current_index > 0) {
            return $steps[$current_index - 1];
        }
        
        return '';
    }
    
    private function domain_exists($domain) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}dropflex_sites WHERE domain = %s",
                $domain
            )
        ) > 0;
    }
    
    public function ajax_check_domain() {
        check_ajax_referer('dropflex_wizard', 'nonce');
        
        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
        
        wp_send_json([
            'available' => !$this->domain_exists($domain)
        ]);
    }
}