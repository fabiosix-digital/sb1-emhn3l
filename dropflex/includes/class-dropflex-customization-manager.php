<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Customization_Manager {
    private $db;
    private $site_manager;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->site_manager = new DropFlex_Site_Manager();
    }
    
    public function save_customization($site_id, $data) {
        $site = $this->site_manager->get_site($site_id);
        if (!$site) {
            throw new Exception("Site não encontrado");
        }
        
        // Validar dados
        $this->validate_customization_data($data);
        
        // Aplicar customizações via WP CLI
        $this->apply_customizations($site->domain, $data);
        
        return true;
    }
    
    private function validate_customization_data($data) {
        $required = ['colors', 'typography', 'layout'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Validar cores
        if (isset($data['colors'])) {
            foreach ($data['colors'] as $color) {
                if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                    throw new Exception("Cor inválida: {$color}");
                }
            }
        }
    }
    
    private function apply_customizations($domain, $data) {
        // Aplicar cores
        if (isset($data['colors'])) {
            $this->apply_colors($domain, $data['colors']);
        }
        
        // Aplicar tipografia
        if (isset($data['typography'])) {
            $this->apply_typography($domain, $data['typography']);
        }
        
        // Aplicar layout
        if (isset($data['layout'])) {
            $this->apply_layout($domain, $data['layout']);
        }
        
        // Aplicar elementos personalizados
        if (isset($data['custom_elements'])) {
            $this->apply_custom_elements($domain, $data['custom_elements']);
        }
        
        // Limpar cache
        $this->clear_cache($domain);
    }
    
    private function apply_colors($domain, $colors) {
        $css_vars = [];
        
        foreach ($colors as $name => $value) {
            $css_vars[] = "--df-{$name}: {$value};";
        }
        
        $css = ":root {\n  " . implode("\n  ", $css_vars) . "\n}";
        
        // Salvar CSS personalizado
        $command = sprintf(
            'cd /home/%s/public_html && echo %s > wp-content/themes/dropflex/assets/css/custom-colors.css',
            escapeshellarg($domain),
            escapeshellarg($css)
        );
        
        exec($command);
    }
    
    private function apply_typography($domain, $typography) {
        $css_vars = [];
        
        foreach ($typography as $element => $styles) {
            foreach ($styles as $property => $value) {
                $css_vars[] = "--df-{$element}-{$property}: {$value};";
            }
        }
        
        $css = ":root {\n  " . implode("\n  ", $css_vars) . "\n}";
        
        // Salvar CSS personalizado
        $command = sprintf(
            'cd /home/%s/public_html && echo %s > wp-content/themes/dropflex/assets/css/custom-typography.css',
            escapeshellarg($domain),
            escapeshellarg($css)
        );
        
        exec($command);
    }
    
    private function apply_layout($domain, $layout) {
        $css_vars = [];
        
        foreach ($layout as $section => $styles) {
            foreach ($styles as $property => $value) {
                $css_vars[] = "--df-{$section}-{$property}: {$value};";
            }
        }
        
        $css = ":root {\n  " . implode("\n  ", $css_vars) . "\n}";
        
        // Salvar CSS personalizado
        $command = sprintf(
            'cd /home/%s/public_html && echo %s > wp-content/themes/dropflex/assets/css/custom-layout.css',
            escapeshellarg($domain),
            escapeshellarg($css)
        );
        
        exec($command);
    }
    
    private function apply_custom_elements($domain, $elements) {
        foreach ($elements as $element) {
            if (!isset($element['type']) || !isset($element['content'])) {
                continue;
            }
            
            switch ($element['type']) {
                case 'header':
                    $this->update_header($domain, $element['content']);
                    break;
                    
                case 'footer':
                    $this->update_footer($domain, $element['content']);
                    break;
                    
                case 'sidebar':
                    $this->update_sidebar($domain, $element['content']);
                    break;
            }
        }
    }
    
    private function clear_cache($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && php wp-cli.phar cache flush',
            escapeshellarg($domain)
        );
        
        exec($command);
    }
    
    public function get_customization($site_id) {
        $site = $this->site_manager->get_site($site_id);
        if (!$site) {
            return false;
        }
        
        return [
            'colors' => $this->get_colors($site->domain),
            'typography' => $this->get_typography($site->domain),
            'layout' => $this->get_layout($site->domain),
            'custom_elements' => $this->get_custom_elements($site->domain)
        ];
    }
    
    private function get_colors($domain) {
        $file = "/home/{$domain}/public_html/wp-content/themes/dropflex/assets/css/custom-colors.css";
        if (!file_exists($file)) {
            return [];
        }
        
        return $this->parse_css_vars(file_get_contents($file));
    }
    
    private function get_typography($domain) {
        $file = "/home/{$domain}/public_html/wp-content/themes/dropflex/assets/css/custom-typography.css";
        if (!file_exists($file)) {
            return [];
        }
        
        return $this->parse_css_vars(file_get_contents($file));
    }
    
    private function get_layout($domain) {
        $file = "/home/{$domain}/public_html/wp-content/themes/dropflex/assets/css/custom-layout.css";
        if (!file_exists($file)) {
            return [];
        }
        
        return $this->parse_css_vars(file_get_contents($file));
    }
    
    private function get_custom_elements($domain) {
        // Implementar lógica para recuperar elementos personalizados
        return [];
    }
    
    private function parse_css_vars($css) {
        $vars = [];
        preg_match_all('/--df-([^:]+):\s*([^;]+);/', $css, $matches);
        
        if (isset($matches[1]) && isset($matches[2])) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $vars[trim($matches[1][$i])] = trim($matches[2][$i]);
            }
        }
        
        return $vars;
    }
    
    public function ajax_save_customization() {
        check_ajax_referer('dropflex_nonce', 'nonce');
        
        if (!current_user_can('dropflex_manage_sites')) {
            wp_send_json_error('Permissão negada');
        }
        
        $site_id = isset($_POST['site_id']) ? absint($_POST['site_id']) : 0;
        $data = isset($_POST['customization']) ? $_POST['customization'] : [];
        
        try {
            $this->save_customization($site_id, $data);
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}