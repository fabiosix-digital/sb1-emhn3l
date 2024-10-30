<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Integration_Manager {
    private $db;
    private $available_integrations = [
        'yampi' => [
            'name' => 'Yampi',
            'class' => 'DropFlex_Yampi_Integration',
            'icon' => 'yampi-logo.png',
            'type' => 'checkout'
        ],
        'appmax' => [
            'name' => 'Appmax',
            'class' => 'DropFlex_Appmax_Integration',
            'icon' => 'appmax-logo.png',
            'type' => 'checkout'
        ],
        'aliexpress' => [
            'name' => 'AliExpress',
            'class' => 'DropFlex_AliExpress_Integration',
            'icon' => 'aliexpress-logo.png',
            'type' => 'dropshipping'
        ],
        'shopee' => [
            'name' => 'Shopee',
            'class' => 'DropFlex_Shopee_Integration',
            'icon' => 'shopee-logo.png',
            'type' => 'dropshipping'
        ]
    ];
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function get_available_integrations($type = null) {
        $integrations = [];
        
        foreach ($this->available_integrations as $id => $integration) {
            if ($type && $integration['type'] !== $type) {
                continue;
            }
            
            $class = $integration['class'];
            $instance = new $class();
            
            if ($instance->is_available()) {
                $integrations[$id] = [
                    'name' => $integration['name'],
                    'icon' => DROPFLEX_ASSETS_URL . 'images/' . $integration['icon'],
                    'description' => $instance->get_description(),
                    'type' => $integration['type']
                ];
            }
        }
        
        return $integrations;
    }
    
    public function setup_integration($type, $credentials) {
        if (!isset($this->available_integrations[$type])) {
            throw new Exception('Integração não suportada');
        }
        
        $class = $this->available_integrations[$type]['class'];
        $integration = new $class();
        
        return $integration->setup($credentials);
    }
    
    public function get_integration_settings($type) {
        if (!isset($this->available_integrations[$type])) {
            throw new Exception('Integração não suportada');
        }
        
        $class = $this->available_integrations[$type]['class'];
        $integration = new $class();
        
        return $integration->get_settings();
    }
    
    public function save_integration_settings($type, $settings) {
        if (!isset($this->available_integrations[$type])) {
            throw new Exception('Integração não suportada');
        }
        
        $class = $this->available_integrations[$type]['class'];
        $integration = new $class();
        
        return $integration->save_settings($settings);
    }
    
    public function test_integration($type, $credentials) {
        if (!isset($this->available_integrations[$type])) {
            throw new Exception('Integração não suportada');
        }
        
        $class = $this->available_integrations[$type]['class'];
        $integration = new $class();
        
        return $integration->test_connection($credentials);
    }
    
    public function get_integration_guide($type) {
        $guides = [
            'yampi' => [
                'title' => __('Como configurar a Yampi', 'dropflex'),
                'steps' => [
                    __('1. Acesse sua conta Yampi', 'dropflex'),
                    __('2. Vá em Configurações > Integrações', 'dropflex'),
                    __('3. Copie o Alias da sua loja', 'dropflex'),
                    __('4. Gere um novo Token de API', 'dropflex'),
                    __('5. Cole as credenciais nos campos abaixo', 'dropflex')
                ],
                'video_url' => 'https://www.youtube.com/embed/xxxxx',
                'help_url' => 'https://ajuda.yampi.com.br/article/xxx-integracao-wordpress'
            ],
            'appmax' => [
                'title' => __('Como configurar a Appmax', 'dropflex'),
                'steps' => [
                    __('1. Acesse sua conta Appmax', 'dropflex'),
                    __('2. Vá em Configurações > API', 'dropflex'),
                    __('3. Gere uma nova chave API', 'dropflex'),
                    __('4. Cole a chave no campo abaixo', 'dropflex')
                ],
                'video_url' => 'https://www.youtube.com/embed/xxxxx',
                'help_url' => 'https://ajuda.appmax.com.br/article/xxx-integracao-wordpress'
            ],
            'aliexpress' => [
                'title' => __('Como configurar o AliExpress', 'dropflex'),
                'steps' => [
                    __('1. Acesse o Portal de Desenvolvedores do AliExpress', 'dropflex'),
                    __('2. Crie uma nova aplicação', 'dropflex'),
                    __('3. Copie as credenciais da API', 'dropflex'),
                    __('4. Cole as credenciais nos campos abaixo', 'dropflex')
                ],
                'video_url' => 'https://www.youtube.com/embed/xxxxx',
                'help_url' => 'https://portals.aliexpress.com/help'
            ],
            'shopee' => [
                'title' => __('Como configurar a Shopee', 'dropflex'),
                'steps' => [
                    __('1. Acesse o Portal de Desenvolvedores da Shopee', 'dropflex'),
                    __('2. Crie uma nova aplicação', 'dropflex'),
                    __('3. Copie as credenciais da API', 'dropflex'),
                    __('4. Cole as credenciais nos campos abaixo', 'dropflex')
                ],
                'video_url' => 'https://www.youtube.com/embed/xxxxx',
                'help_url' => 'https://open.shopee.com/documents'
            ]
        ];
        
        return isset($guides[$type]) ? $guides[$type] : null;
    }
    
    public function get_integration_status($type, $site_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_integrations 
                WHERE type = %s AND site_id = %d",
                $type,
                $site_id
            )
        );
    }
    
    public function save_integration_status($type, $site_id, $status) {
        $existing = $this->get_integration_status($type, $site_id);
        
        if ($existing) {
            return $this->db->update(
                "{$this->db->prefix}dropflex_integrations",
                ['status' => $status],
                ['type' => $type, 'site_id' => $site_id],
                ['%s'],
                ['%s', '%d']
            );
        }
        
        return $this->db->insert(
            "{$this->db->prefix}dropflex_integrations",
            [
                'type' => $type,
                'site_id' => $site_id,
                'status' => $status
            ],
            ['%s', '%d', '%s']
        );
    }
    
    public function handle_webhook($type, $payload) {
        if (!isset($this->available_integrations[$type])) {
            throw new Exception('Integração não suportada');
        }
        
        $class = $this->available_integrations[$type]['class'];
        $integration = new $class();
        
        return $integration->handle_webhook($payload);
    }
}