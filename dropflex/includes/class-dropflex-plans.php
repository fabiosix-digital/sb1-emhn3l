<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Plans {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function get_plans() {
        return $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}dropflex_plans WHERE status = 'active' ORDER BY price ASC"
        );
    }
    
    public function get_plan($id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_plans WHERE id = %d",
                $id
            )
        );
    }
    
    public function create_plan($data) {
        $this->db->insert(
            "{$this->db->prefix}dropflex_plans",
            array(
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'features' => json_encode($data['features']),
                'status' => 'active'
            ),
            array('%s', '%s', '%f', '%s', '%s')
        );
        
        return $this->db->insert_id;
    }
    
    public function update_plan($id, $data) {
        $this->db->update(
            "{$this->db->prefix}dropflex_plans",
            array(
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'features' => json_encode($data['features'])
            ),
            array('id' => $id),
            array('%s', '%s', '%f', '%s'),
            array('%d')
        );
    }
    
    public function delete_plan($id) {
        return $this->db->update(
            "{$this->db->prefix}dropflex_plans",
            array('status' => 'deleted'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
}