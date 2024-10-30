<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Subscription {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->init_tables();
    }
    
    private function init_tables() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db->prefix}dropflex_subscriptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_id bigint(20) NOT NULL,
            status varchar(50) NOT NULL,
            next_billing datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function create_subscription($user_id, $plan_id) {
        return $this->db->insert(
            "{$this->db->prefix}dropflex_subscriptions",
            [
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'status' => 'active',
                'next_billing' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ],
            ['%d', '%d', '%s', '%s']
        );
    }
    
    public function get_user_subscription($user_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_subscriptions WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );
    }
    
    public function cancel_subscription($subscription_id) {
        return $this->db->update(
            "{$this->db->prefix}dropflex_subscriptions",
            ['status' => 'cancelled'],
            ['id' => $subscription_id],
            ['%s'],
            ['%d']
        );
    }
}