<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Template_Loader {
    private $templates_dir;
    private $cache = [];
    
    public function __construct() {
        $this->templates_dir = DROPFLEX_PLUGIN_DIR . 'templates/';
    }
    
    public function load_template($type, $id) {
        $cache_key = "{$type}_{$id}";
        
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }
        
        $file = $this->templates_dir . $type . '/' . $id . '.json';
        if (!file_exists($file)) {
            return false;
        }
        
        $template = json_decode(file_get_contents($file), true);
        if (!$template) {
            return false;
        }
        
        $this->cache[$cache_key] = $template;
        return $template;
    }
    
    public function get_template_types() {
        return [
            'headers' => __('Cabeçalhos', 'dropflex'),
            'footers' => __('Rodapés', 'dropflex'),
            'home' => __('Página Inicial', 'dropflex'),
            'about' => __('Sobre', 'dropflex'),
            'contact' => __('Contato', 'dropflex'),
            'products' => __('Lista de Produtos', 'dropflex'),
            'product' => __('Página de Produto', 'dropflex'),
            'cart' => __('Carrinho', 'dropflex'),
            'checkout' => __('Finalização', 'dropflex')
        ];
    }
    
    public function get_templates($type) {
        $templates = [];
        $dir = $this->templates_dir . $type;
        
        if (!is_dir($dir)) {
            return $templates;
        }
        
        $files = glob($dir . '/*.json');
        foreach ($files as $file) {
            $template = json_decode(file_get_contents($file), true);
            if ($template) {
                $template['id'] = basename($file, '.json');
                $templates[] = $template;
            }
        }
        
        return $templates;
    }
    
    public function render_template($template, $data = []) {
        if (!isset($template['content']['html'])) {
            return '';
        }
        
        $html = $template['content']['html'];
        
        // Substituir variáveis
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        
        // Adicionar CSS
        if (isset($template['content']['css'])) {
            $css = '<style>' . $template['content']['css'] . '</style>';
            $html = $css . $html;
        }
        
        // Adicionar JS
        if (isset($template['content']['js'])) {
            $js = '<script>' . $template['content']['js'] . '</script>';
            $html .= $js;
        }
        
        return $html;
    }
    
    public function save_template($type, $data) {
        if (!isset($data['name']) || !isset($data['content'])) {
            return false;
        }
        
        $dir = $this->templates_dir . $type;
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        
        $id = isset($data['id']) ? $data['id'] : sanitize_title($data['name']);
        $file = $dir . '/' . $id . '.json';
        
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function delete_template($type, $id) {
        $file = $this->templates_dir . $type . '/' . $id . '.json';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }
    
    public function get_template_variables($template) {
        $variables = [];
        
        if (!isset($template['content']['html'])) {
            return $variables;
        }
        
        preg_match_all('/{{([^}]+)}}/', $template['content']['html'], $matches);
        
        if (isset($matches[1])) {
            $variables = array_unique($matches[1]);
        }
        
        return $variables;
    }
    
    public function validate_template($data) {
        $required = ['name', 'content'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        if (isset($data['content']['html'])) {
            $this->validate_html($data['content']['html']);
        }
        
        if (isset($data['content']['css'])) {
            $this->validate_css($data['content']['css']);
        }
        
        if (isset($data['content']['js'])) {
            $this->validate_js($data['content']['js']);
        }
        
        return true;
    }
    
    private function validate_html($html) {
        // Implementar validação de HTML
        return true;
    }
    
    private function validate_css($css) {
        // Implementar validação de CSS
        return true;
    }
    
    private function validate_js($js) {
        // Implementar validação de JavaScript
        return true;
    }
}