<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Template_Manager {
    private $db;
    private $header_templates;
    private $footer_templates;
    private $home_templates;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        $this->header_templates = new DropFlex_Header_Templates();
        $this->footer_templates = new DropFlex_Footer_Templates();
        $this->home_templates = new DropFlex_Home_Templates();
        
        $this->init_tables();
    }
    
    private function init_tables() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db->prefix}dropflex_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            header_id bigint(20) DEFAULT NULL,
            footer_id bigint(20) DEFAULT NULL,
            home_id bigint(20) DEFAULT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function get_templates($type = null) {
        $query = "SELECT * FROM {$this->db->prefix}dropflex_templates";
        
        if ($type) {
            $query .= $this->db->prepare(" WHERE type = %s", $type);
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->db->get_results($query);
    }
    
    public function get_template($id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_templates WHERE id = %d",
                $id
            )
        );
    }
    
    public function create_template($data) {
        $this->db->insert(
            "{$this->db->prefix}dropflex_templates",
            $data,
            ['%s', '%s', '%d', '%d', '%d', '%s']
        );
        
        return $this->db->insert_id;
    }
    
    public function update_template($id, $data) {
        $this->db->update(
            "{$this->db->prefix}dropflex_templates",
            $data,
            ['id' => $id],
            ['%s', '%s', '%d', '%d', '%d', '%s'],
            ['%d']
        );
    }
    
    public function delete_template($id) {
        $this->db->delete(
            "{$this->db->prefix}dropflex_templates",
            ['id' => $id],
            ['%d']
        );
    }
    
    public function apply_template($domain, $template_id) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            throw new Exception("Template não encontrado");
        }
        
        // Aplicar header
        if ($template->header_id) {
            $this->header_templates->apply($domain, $template->header_id);
        }
        
        // Aplicar footer
        if ($template->footer_id) {
            $this->footer_templates->apply($domain, $template->footer_id);
        }
        
        // Aplicar home
        if ($template->home_id) {
            $this->home_templates->apply($domain, $template->home_id);
        }
        
        // Aplicar configurações gerais
        if ($template->settings) {
            $this->apply_settings($domain, json_decode($template->settings, true));
        }
    }
    
    private function apply_settings($domain, $settings) {
        $command = sprintf(
            'cd /home/%s/public_html && php wp-cli.phar option update',
            escapeshellarg($domain)
        );
        
        foreach ($settings as $key => $value) {
            exec($command . ' ' . escapeshellarg($key) . ' ' . escapeshellarg($value));
        }
    }
    
    public function ajax_save_template() {
        check_ajax_referer('dropflex_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'type' => sanitize_text_field($_POST['type']),
            'header_id' => absint($_POST['header_id']),
            'footer_id' => absint($_POST['footer_id']),
            'home_id' => absint($_POST['home_id']),
            'settings' => wp_json_encode($_POST['settings'])
        ];
        
        if (!empty($_POST['id'])) {
            $this->update_template(absint($_POST['id']), $data);
            $template_id = absint($_POST['id']);
        } else {
            $template_id = $this->create_template($data);
        }
        
        wp_send_json_success([
            'template_id' => $template_id
        ]);
    }
}