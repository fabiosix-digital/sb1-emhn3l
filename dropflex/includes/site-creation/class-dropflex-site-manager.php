<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Site_Manager {
    private $whm_integration;
    private $wp_installer;
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->whm_integration = new DropFlex_WHM_Integration();
        $this->wp_installer = new DropFlex_WordPress_Installer();
        
        $this->init_tables();
    }
    
    private function init_tables() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db->prefix}dropflex_sites (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            domain varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            template_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY domain (domain)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function create_site($params) {
        try {
            // 1. Validar dados
            $this->validate_site_params($params);
            
            // 2. Criar conta no WHM
            $whm_account = $this->whm_integration->create_account([
                'domain' => $params['domain'],
                'username' => $params['username'],
                'password' => $params['password'],
                'plan' => $params['plan']
            ]);
            
            // 3. Instalar WordPress
            $wp_installation = $this->wp_installer->install([
                'domain' => $params['domain'],
                'title' => $params['site_title'],
                'admin_user' => $params['admin_user'],
                'admin_password' => $params['admin_password'],
                'admin_email' => $params['admin_email']
            ]);
            
            // 4. Registrar site no banco
            $site_id = $this->register_site([
                'user_id' => get_current_user_id(),
                'domain' => $params['domain'],
                'status' => 'active',
                'template_id' => $params['template_id'] ?? null
            ]);
            
            // 5. Instalar plugins necessários
            $this->install_required_plugins($params['domain']);
            
            // 6. Aplicar template se selecionado
            if (!empty($params['template_id'])) {
                $this->apply_template($params['domain'], $params['template_id']);
            }
            
            return [
                'success' => true,
                'site_id' => $site_id,
                'domain' => $params['domain']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validate_site_params($params) {
        $required = ['domain', 'username', 'password', 'admin_user', 'admin_password', 'admin_email'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Validar domínio
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $params['domain'])) {
            throw new Exception("Domínio inválido");
        }
        
        // Verificar disponibilidade do domínio
        if ($this->domain_exists($params['domain'])) {
            throw new Exception("Domínio já está em uso");
        }
    }
    
    private function domain_exists($domain) {
        return $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}dropflex_sites WHERE domain = %s",
                $domain
            )
        ) > 0;
    }
    
    private function register_site($data) {
        $this->db->insert(
            "{$this->db->prefix}dropflex_sites",
            $data,
            ['%d', '%s', '%s', '%d']
        );
        
        return $this->db->insert_id;
    }
    
    private function install_required_plugins($domain) {
        $required_plugins = [
            'elementor',
            'wordpress-seo',
            'wp-super-cache',
            'contact-form-7',
            'wordfence'
        ];
        
        foreach ($required_plugins as $plugin) {
            $this->wp_installer->install_plugin($domain, $plugin);
        }
    }
    
    private function apply_template($domain, $template_id) {
        $template_manager = new DropFlex_Template_Manager();
        $template_manager->apply_template($domain, $template_id);
    }
    
    public function get_user_sites($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_sites WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
    }
    
    public function delete_site($site_id) {
        $site = $this->get_site($site_id);
        
        if (!$site) {
            throw new Exception("Site não encontrado");
        }
        
        // Remover conta do WHM
        $this->whm_integration->delete_account($site->domain);
        
        // Remover registro do banco
        $this->db->delete(
            "{$this->db->prefix}dropflex_sites",
            ['id' => $site_id],
            ['%d']
        );
        
        return true;
    }
    
    public function get_site($site_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_sites WHERE id = %d",
                $site_id
            )
        );
    }
    
    public function ajax_create_site() {
        check_ajax_referer('dropflex_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $params = $_POST;
        $result = $this->create_site($params);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    public function ajax_check_domain() {
        check_ajax_referer('dropflex_nonce', 'nonce');
        
        $domain = sanitize_text_field($_POST['domain']);
        
        wp_send_json([
            'available' => !$this->domain_exists($domain)
        ]);
    }
}