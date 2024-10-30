<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Theme_Manager {
    private $db;
    private $themes_dir;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->themes_dir = DROPFLEX_PLUGIN_DIR . 'themes/';
        
        if (!file_exists($this->themes_dir)) {
            wp_mkdir_p($this->themes_dir);
        }
    }
    
    public function get_themes($type = null) {
        $themes = [];
        $types = $type ? [$type] : ['ecommerce', 'dropshipping', 'institutional', 'blog'];
        
        foreach ($types as $theme_type) {
            $dir = $this->themes_dir . $theme_type;
            if (!file_exists($dir)) {
                continue;
            }
            
            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $json = file_get_contents($file);
                $data = json_decode($json, true);
                if ($data) {
                    $data['id'] = basename($file, '.json');
                    $data['type'] = $theme_type;
                    $themes[] = $data;
                }
            }
        }
        
        return $themes;
    }
    
    public function get_theme($id) {
        $themes = $this->get_themes();
        foreach ($themes as $theme) {
            if ($theme['id'] === $id) {
                return $theme;
            }
        }
        return false;
    }
    
    public function install_theme($site_id, $theme_id) {
        $theme = $this->get_theme($theme_id);
        if (!$theme) {
            throw new Exception('Tema não encontrado');
        }
        
        $site = $this->get_site($site_id);
        if (!$site) {
            throw new Exception('Site não encontrado');
        }
        
        try {
            // 1. Copiar arquivos do tema
            $this->copy_theme_files($theme, $site->domain);
            
            // 2. Configurar tema
            $this->configure_theme($theme, $site->domain);
            
            // 3. Ativar tema
            $this->activate_theme($theme['id'], $site->domain);
            
            // 4. Atualizar registro do site
            $this->update_site_theme($site_id, $theme_id);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            $this->rollback_theme_installation($site->domain);
            throw $e;
        }
    }
    
    private function copy_theme_files($theme, $domain) {
        $source = $this->themes_dir . $theme['type'] . '/' . $theme['id'];
        $destination = "/home/{$domain}/public_html/wp-content/themes/{$theme['id']}";
        
        if (!file_exists($source)) {
            throw new Exception('Arquivos do tema não encontrados');
        }
        
        // Copiar arquivos
        $command = sprintf(
            'cp -R %s %s',
            escapeshellarg($source),
            escapeshellarg($destination)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Erro ao copiar arquivos do tema');
        }
    }
    
    private function configure_theme($theme, $domain) {
        // Configurar opções do tema
        if (isset($theme['options'])) {
            foreach ($theme['options'] as $option => $value) {
                $command = sprintf(
                    'cd /home/%s/public_html && wp option update %s %s',
                    escapeshellarg($domain),
                    escapeshellarg($option),
                    escapeshellarg($value)
                );
                
                exec($command);
            }
        }
        
        // Configurar menus
        if (isset($theme['menus'])) {
            foreach ($theme['menus'] as $location => $menu) {
                $command = sprintf(
                    'cd /home/%s/public_html && wp menu create "%s" --porcelain',
                    escapeshellarg($domain),
                    escapeshellarg($menu['name'])
                );
                
                exec($command);
            }
        }
        
        // Configurar widgets
        if (isset($theme['widgets'])) {
            foreach ($theme['widgets'] as $sidebar => $widgets) {
                foreach ($widgets as $widget) {
                    $command = sprintf(
                        'cd /home/%s/public_html && wp widget add %s %s %d %s',
                        escapeshellarg($domain),
                        escapeshellarg($widget['type']),
                        escapeshellarg($sidebar),
                        $widget['position'],
                        escapeshellarg(json_encode($widget['settings']))
                    );
                    
                    exec($command);
                }
            }
        }
    }
    
    private function activate_theme($theme_id, $domain) {
        $command = sprintf(
            'cd /home/%s/public_html && wp theme activate %s',
            escapeshellarg($domain),
            escapeshellarg($theme_id)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Erro ao ativar tema');
        }
    }
    
    private function update_site_theme($site_id, $theme_id) {
        return $this->db->update(
            "{$this->db->prefix}dropflex_sites",
            ['theme_id' => $theme_id],
            ['id' => $site_id],
            ['%s'],
            ['%d']
        );
    }
    
    private function rollback_theme_installation($domain) {
        // Remover arquivos do tema
        $command = sprintf(
            'rm -rf /home/%s/public_html/wp-content/themes/*',
            escapeshellarg($domain)
        );
        
        exec($command);
        
        // Restaurar tema padrão
        $command = sprintf(
            'cd /home/%s/public_html && wp theme activate twentytwentyfour',
            escapeshellarg($domain)
        );
        
        exec($command);
    }
    
    private function get_site($site_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_sites WHERE id = %d",
                $site_id
            )
        );
    }
    
    public function get_theme_customization_options($theme_id) {
        $theme = $this->get_theme($theme_id);
        if (!$theme || !isset($theme['customization'])) {
            return [];
        }
        
        return $theme['customization'];
    }
    
    public function save_theme_customization($site_id, $theme_id, $customization) {
        $theme = $this->get_theme($theme_id);
        if (!$theme) {
            throw new Exception('Tema não encontrado');
        }
        
        $site = $this->get_site($site_id);
        if (!$site) {
            throw new Exception('Site não encontrado');
        }
        
        // Validar customização
        $this->validate_customization($theme, $customization);
        
        // Aplicar customização
        $this->apply_customization($site->domain, $customization);
        
        // Salvar no banco
        return $this->db->update(
            "{$this->db->prefix}dropflex_sites",
            ['theme_customization' => json_encode($customization)],
            ['id' => $site_id],
            ['%s'],
            ['%d']
        );
    }
    
    private function validate_customization($theme, $customization) {
        if (!isset($theme['customization'])) {
            return true;
        }
        
        foreach ($theme['customization'] as $key => $options) {
            if (isset($options['required']) && $options['required']) {
                if (!isset($customization[$key]) || empty($customization[$key])) {
                    throw new Exception("Campo obrigatório: {$options['label']}");
                }
            }
            
            if (isset($options['type'])) {
                switch ($options['type']) {
                    case 'color':
                        if (isset($customization[$key]) && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $customization[$key])) {
                            throw new Exception("Cor inválida: {$options['label']}");
                        }
                        break;
                        
                    case 'number':
                        if (isset($customization[$key]) && !is_numeric($customization[$key])) {
                            throw new Exception("Valor numérico inválido: {$options['label']}");
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    private function apply_customization($domain, $customization) {
        // Gerar CSS customizado
        $css = $this->generate_custom_css($customization);
        
        // Salvar CSS
        $css_file = "/home/{$domain}/public_html/wp-content/themes/custom.css";
        file_put_contents($css_file, $css);
        
        // Registrar CSS no tema
        $command = sprintf(
            'cd /home/%s/public_html && wp eval \'add_action("wp_enqueue_scripts", function() { wp_enqueue_style("custom-style", get_stylesheet_directory_uri() . "/custom.css"); });\' --skip-themes --skip-plugins',
            escapeshellarg($domain)
        );
        
        exec($command);
    }
    
    private function generate_custom_css($customization) {
        $css = [];
        
        foreach ($customization as $key => $value) {
            switch ($key) {
                case 'colors':
                    foreach ($value as $color_key => $color) {
                        $css[] = "--{$color_key}: {$color};";
                    }
                    break;
                    
                case 'typography':
                    foreach ($value as $element => $styles) {
                        $selector = $this->get_typography_selector($element);
                        $css[] = "{$selector} {";
                        foreach ($styles as $property => $style_value) {
                            $css[] = "    {$property}: {$style_value};";
                        }
                        $css[] = "}";
                    }
                    break;
                    
                case 'spacing':
                    foreach ($value as $element => $spacing) {
                        $selector = $this->get_spacing_selector($element);
                        $css[] = "{$selector} {";
                        foreach ($spacing as $property => $space_value) {
                            $css[] = "    {$property}: {$space_value};";
                        }
                        $css[] = "}";
                    }
                    break;
            }
        }
        
        return implode("\n", $css);
    }
    
    private function get_typography_selector($element) {
        $selectors = [
            'body' => 'body',
            'headings' => 'h1, h2, h3, h4, h5, h6',
            'paragraph' => 'p',
            'link' => 'a',
            'button' => '.button'
        ];
        
        return isset($selectors[$element]) ? $selectors[$element] : $element;
    }
    
    private function get_spacing_selector($element) {
        $selectors = [
            'container' => '.container',
            'section' => 'section',
            'header' => 'header',
            'footer' => 'footer'
        ];
        
        return isset($selectors[$element]) ? $selectors[$element] : $element;
    }
}