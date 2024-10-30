<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Store_Creator {
    private $db;
    private $site_manager;
    private $template_manager;
    private $customization_manager;
    private $checkout_manager;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->site_manager = new DropFlex_Site_Manager();
        $this->template_manager = new DropFlex_Template_Manager();
        $this->customization_manager = new DropFlex_Customization_Manager();
        $this->checkout_manager = new DropFlex_Checkout_Manager();
    }
    
    public function create_store($data) {
        try {
            // 1. Validar dados
            $this->validate_store_data($data);
            
            // 2. Criar site no WHM
            $site = $this->site_manager->create_site([
                'domain' => $data['domain'],
                'user_id' => get_current_user_id(),
                'template_id' => $data['template_id']
            ]);
            
            // 3. Aplicar template
            $this->apply_template($site->id, $data['template_id']);
            
            // 4. Aplicar customizações
            $this->apply_customizations($site->id, $data['customization']);
            
            // 5. Configurar checkout
            if (!empty($data['checkout'])) {
                $this->setup_checkout($site->id, $data['checkout']);
            }
            
            // 6. Configurar integrações
            if (!empty($data['integrations'])) {
                $this->setup_integrations($site->id, $data['integrations']);
            }
            
            // 7. Criar páginas padrão
            $this->create_default_pages($site->id);
            
            return [
                'success' => true,
                'site_id' => $site->id,
                'domain' => $site->domain,
                'admin_url' => 'https://' . $site->domain . '/wp-admin'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validate_store_data($data) {
        $required = ['domain', 'template_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Validar domínio
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['domain'])) {
            throw new Exception("Domínio inválido");
        }
        
        // Verificar disponibilidade do domínio
        if ($this->domain_exists($data['domain'])) {
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
    
    private function apply_template($site_id, $template_id) {
        $template = $this->template_manager->get_template($template_id);
        if (!$template) {
            throw new Exception("Template não encontrado");
        }
        
        // Aplicar componentes do template
        if (!empty($template->header_id)) {
            $this->template_manager->apply_header($site_id, $template->header_id);
        }
        
        if (!empty($template->footer_id)) {
            $this->template_manager->apply_footer($site_id, $template->footer_id);
        }
        
        if (!empty($template->home_id)) {
            $this->template_manager->apply_home($site_id, $template->home_id);
        }
        
        // Aplicar configurações do template
        if (!empty($template->settings)) {
            $this->template_manager->apply_settings($site_id, json_decode($template->settings, true));
        }
    }
    
    private function apply_customizations($site_id, $customization) {
        if (empty($customization)) {
            return;
        }
        
        $this->customization_manager->save_customization($site_id, $customization);
    }
    
    private function setup_checkout($site_id, $checkout_data) {
        $this->checkout_manager->save_checkout_settings($site_id, $checkout_data);
    }
    
    private function setup_integrations($site_id, $integrations) {
        foreach ($integrations as $type => $credentials) {
            switch ($type) {
                case 'yampi':
                    $yampi = new DropFlex_Yampi_Integration();
                    $yampi->setup_integration($site_id, $credentials);
                    break;
                    
                case 'appmax':
                    $appmax = new DropFlex_Appmax_Integration();
                    $appmax->setup_integration($site_id, $credentials);
                    break;
            }
        }
    }
    
    private function create_default_pages($site_id) {
        $pages = [
            'about' => [
                'title' => 'Sobre Nós',
                'content' => $this->get_default_about_content()
            ],
            'contact' => [
                'title' => 'Contato',
                'content' => $this->get_default_contact_content()
            ],
            'privacy' => [
                'title' => 'Política de Privacidade',
                'content' => $this->get_default_privacy_content()
            ],
            'terms' => [
                'title' => 'Termos e Condições',
                'content' => $this->get_default_terms_content()
            ]
        ];
        
        foreach ($pages as $slug => $page) {
            wp_insert_post([
                'post_title' => $page['title'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug
            ]);
        }
    }
    
    private function get_default_about_content() {
        return '<!-- wp:heading {"level":1} -->
<h1>Sobre Nós</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Bem-vindo à nossa loja! Somos uma empresa comprometida em oferecer os melhores produtos e serviços para nossos clientes.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nossa missão é proporcionar uma experiência de compra excepcional, com produtos de qualidade e atendimento personalizado.</p>
<!-- /wp:paragraph -->';
    }
    
    private function get_default_contact_content() {
        return '<!-- wp:heading {"level":1} -->
<h1>Entre em Contato</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Estamos à disposição para atender você. Entre em contato através dos canais abaixo:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li>E-mail: contato@seudominio.com.br</li>
<li>Telefone: (00) 0000-0000</li>
<li>WhatsApp: (00) 00000-0000</li>
</ul>
<!-- /wp:list -->';
    }
    
    private function get_default_privacy_content() {
        return '<!-- wp:heading {"level":1} -->
<h1>Política de Privacidade</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Esta Política de Privacidade descreve como suas informações pessoais são coletadas, usadas e compartilhadas quando você visita ou faz uma compra em nossa loja.</p>
<!-- /wp:paragraph -->';
    }
    
    private function get_default_terms_content() {
        return '<!-- wp:heading {"level":1} -->
<h1>Termos e Condições</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ao acessar e fazer uma compra em nossa loja, você concorda com os seguintes termos e condições.</p>
<!-- /wp:paragraph -->';
    }
    
    public function get_creation_progress($site_id) {
        $site = $this->site_manager->get_site($site_id);
        if (!$site) {
            return false;
        }
        
        $progress = [
            'domain_setup' => true,
            'template_applied' => !empty($site->template_id),
            'customization_applied' => !empty($site->customization),
            'checkout_configured' => !empty($site->checkout_type),
            'integrations_configured' => !empty($site->integrations),
            'pages_created' => $this->check_default_pages()
        ];
        
        $progress['percentage'] = $this->calculate_progress_percentage($progress);
        
        return $progress;
    }
    
    private function check_default_pages() {
        $required_pages = ['about', 'contact', 'privacy', 'terms'];
        $existing_pages = get_pages(['post_status' => 'publish']);
        
        foreach ($required_pages as $slug) {
            $exists = false;
            foreach ($existing_pages as $page) {
                if ($page->post_name === $slug) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return false;
            }
        }
        
        return true;
    }
    
    private function calculate_progress_percentage($progress) {
        $total = count($progress) - 1; // Excluindo o próprio percentage
        $completed = 0;
        
        foreach ($progress as $key => $value) {
            if ($key !== 'percentage' && $value === true) {
                $completed++;
            }
        }
        
        return round(($completed / $total) * 100);
    }
}