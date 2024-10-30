<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Plans_Manager {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function create_plan($data) {
        // Validar dados
        $this->validate_plan_data($data);
        
        // Inserir plano
        $result = $this->db->insert(
            "{$this->db->prefix}dropflex_plans",
            [
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'features' => json_encode($data['features']),
                'max_sites' => $data['max_sites'],
                'status' => 'active'
            ],
            ['%s', '%s', '%f', '%s', '%d', '%s']
        );
        
        if (!$result) {
            throw new Exception('Erro ao criar plano');
        }
        
        return $this->db->insert_id;
    }
    
    public function update_plan($plan_id, $data) {
        // Validar dados
        $this->validate_plan_data($data);
        
        // Atualizar plano
        $result = $this->db->update(
            "{$this->db->prefix}dropflex_plans",
            [
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'features' => json_encode($data['features']),
                'max_sites' => $data['max_sites']
            ],
            ['id' => $plan_id],
            ['%s', '%s', '%f', '%s', '%d'],
            ['%d']
        );
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar plano');
        }
        
        return true;
    }
    
    public function delete_plan($plan_id) {
        // Verificar se existem assinaturas ativas
        $active_subscriptions = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}dropflex_subscriptions 
                WHERE plan_id = %d AND status = 'active'",
                $plan_id
            )
        );
        
        if ($active_subscriptions > 0) {
            throw new Exception('Não é possível excluir um plano com assinaturas ativas');
        }
        
        // Desativar plano
        $result = $this->db->update(
            "{$this->db->prefix}dropflex_plans",
            ['status' => 'deleted'],
            ['id' => $plan_id],
            ['%s'],
            ['%d']
        );
        
        if ($result === false) {
            throw new Exception('Erro ao excluir plano');
        }
        
        return true;
    }
    
    public function get_plan($plan_id) {
        $plan = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_plans WHERE id = %d",
                $plan_id
            )
        );
        
        if ($plan) {
            $plan->features = json_decode($plan->features, true);
        }
        
        return $plan;
    }
    
    public function get_plans($status = 'active') {
        $plans = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_plans 
                WHERE status = %s 
                ORDER BY price ASC",
                $status
            )
        );
        
        foreach ($plans as $plan) {
            $plan->features = json_decode($plan->features, true);
        }
        
        return $plans;
    }
    
    private function validate_plan_data($data) {
        $required = ['name', 'price', 'max_sites'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        if ($data['price'] <= 0) {
            throw new Exception('Preço deve ser maior que zero');
        }
        
        if ($data['max_sites'] <= 0) {
            throw new Exception('Número máximo de sites deve ser maior que zero');
        }
        
        return true;
    }
    
    public function get_plan_features() {
        return [
            'sites' => [
                'label' => __('Sites', 'dropflex'),
                'description' => __('Número máximo de sites que podem ser criados', 'dropflex')
            ],
            'templates' => [
                'label' => __('Templates Premium', 'dropflex'),
                'description' => __('Acesso a templates premium exclusivos', 'dropflex')
            ],
            'custom_domain' => [
                'label' => __('Domínio Personalizado', 'dropflex'),
                'description' => __('Use seu próprio domínio', 'dropflex')
            ],
            'ssl' => [
                'label' => __('Certificado SSL', 'dropflex'),
                'description' => __('HTTPS para seu site', 'dropflex')
            ],
            'cdn' => [
                'label' => __('CDN', 'dropflex'),
                'description' => __('Rede de distribuição de conteúdo', 'dropflex')
            ],
            'backup' => [
                'label' => __('Backup Diário', 'dropflex'),
                'description' => __('Backup automático do seu site', 'dropflex')
            ],
            'support' => [
                'label' => __('Suporte Prioritário', 'dropflex'),
                'description' => __('Atendimento prioritário', 'dropflex')
            ]
        ];
    }
    
    public function get_default_plans() {
        return [
            'basic' => [
                'name' => __('Básico', 'dropflex'),
                'description' => __('Ideal para começar', 'dropflex'),
                'price' => 49.90,
                'max_sites' => 1,
                'features' => [
                    'sites' => 1,
                    'templates' => false,
                    'custom_domain' => true,
                    'ssl' => true,
                    'cdn' => false,
                    'backup' => false,
                    'support' => false
                ]
            ],
            'pro' => [
                'name' => __('Profissional', 'dropflex'),
                'description' => __('Para negócios em crescimento', 'dropflex'),
                'price' => 99.90,
                'max_sites' => 3,
                'features' => [
                    'sites' => 3,
                    'templates' => true,
                    'custom_domain' => true,
                    'ssl' => true,
                    'cdn' => true,
                    'backup' => true,
                    'support' => false
                ]
            ],
            'enterprise' => [
                'name' => __('Empresarial', 'dropflex'),
                'description' => __('Recursos completos', 'dropflex'),
                'price' => 199.90,
                'max_sites' => 10,
                'features' => [
                    'sites' => 10,
                    'templates' => true,
                    'custom_domain' => true,
                    'ssl' => true,
                    'cdn' => true,
                    'backup' => true,
                    'support' => true
                ]
            ]
        ];
    }
    
    public function install_default_plans() {
        $plans = $this->get_default_plans();
        
        foreach ($plans as $plan) {
            try {
                $this->create_plan($plan);
            } catch (Exception $e) {
                // Log erro
                error_log("Erro ao criar plano padrão: " . $e->getMessage());
            }
        }
    }
}