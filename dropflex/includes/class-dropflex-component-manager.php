<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Component_Manager {
    private $db;
    private $components_dir;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->components_dir = DROPFLEX_PLUGIN_DIR . 'components/';
        
        if (!file_exists($this->components_dir)) {
            wp_mkdir_p($this->components_dir);
        }
    }
    
    public function get_components($type = null) {
        $components = [];
        $types = $type ? [$type] : ['headers', 'footers', 'home', 'about', 'contact'];
        
        foreach ($types as $component_type) {
            $dir = $this->components_dir . $component_type;
            if (!file_exists($dir)) {
                continue;
            }
            
            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $json = file_get_contents($file);
                $data = json_decode($json, true);
                if ($data) {
                    $data['id'] = basename($file, '.json');
                    $data['type'] = $component_type;
                    $components[] = $data;
                }
            }
        }
        
        return $components;
    }
    
    public function get_component($type, $id) {
        $file = $this->components_dir . $type . '/' . $id . '.json';
        if (!file_exists($file)) {
            return false;
        }
        
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if ($data) {
            $data['id'] = $id;
            $data['type'] = $type;
            return $data;
        }
        
        return false;
    }
    
    public function save_component($type, $data) {
        if (!isset($data['name']) || !isset($data['content'])) {
            return false;
        }
        
        $dir = $this->components_dir . $type;
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        $id = isset($data['id']) ? $data['id'] : sanitize_title($data['name']);
        $file = $dir . '/' . $id . '.json';
        
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function delete_component($type, $id) {
        $file = $this->components_dir . $type . '/' . $id . '.json';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }
    
    public function get_color_schemes() {
        $file = $this->components_dir . 'color-schemes.json';
        if (!file_exists($file)) {
            return $this->get_default_color_schemes();
        }
        
        $json = file_get_contents($file);
        return json_decode($json, true);
    }
    
    private function get_default_color_schemes() {
        return [
            'modern' => [
                'name' => 'Moderno',
                'colors' => [
                    'primary' => '#2563eb',
                    'secondary' => '#4f46e5',
                    'accent' => '#f59e0b',
                    'background' => '#ffffff',
                    'text' => '#1f2937'
                ]
            ],
            'dark' => [
                'name' => 'Dark Mode',
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#6366f1',
                    'accent' => '#f59e0b',
                    'background' => '#111827',
                    'text' => '#f3f4f6'
                ]
            ]
        ];
    }
}