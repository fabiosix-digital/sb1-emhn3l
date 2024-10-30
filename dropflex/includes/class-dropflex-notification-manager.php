<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Notification_Manager {
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        $this->init_tables();
    }
    
    private function init_tables() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db->prefix}dropflex_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data longtext,
            read_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function create_notification($user_id, $type, $title, $message, $data = []) {
        return $this->db->insert(
            "{$this->db->prefix}dropflex_notifications",
            [
                'user_id' => $user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data)
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }
    
    public function get_notifications($user_id, $limit = 10, $offset = 0) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}dropflex_notifications 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            )
        );
    }
    
    public function get_unread_count($user_id) {
        return $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}dropflex_notifications 
                WHERE user_id = %d AND read_at IS NULL",
                $user_id
            )
        );
    }
    
    public function mark_as_read($notification_id) {
        return $this->db->update(
            "{$this->db->prefix}dropflex_notifications",
            ['read_at' => current_time('mysql')],
            ['id' => $notification_id],
            ['%s'],
            ['%d']
        );
    }
    
    public function mark_all_as_read($user_id) {
        return $this->db->update(
            "{$this->db->prefix}dropflex_notifications",
            ['read_at' => current_time('mysql')],
            [
                'user_id' => $user_id,
                'read_at' => null
            ],
            ['%s'],
            ['%d', null]
        );
    }
    
    public function delete_notification($notification_id) {
        return $this->db->delete(
            "{$this->db->prefix}dropflex_notifications",
            ['id' => $notification_id],
            ['%d']
        );
    }
    
    public function delete_old_notifications($days = 30) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->db->query(
            $this->db->prepare(
                "DELETE FROM {$this->db->prefix}dropflex_notifications 
                WHERE created_at < %s",
                $cutoff
            )
        );
    }
    
    public function send_email_notification($notification) {
        $user = get_user_by('id', $notification->user_id);
        if (!$user) {
            return false;
        }
        
        $subject = sprintf(
            '[%s] %s',
            get_bloginfo('name'),
            $notification->title
        );
        
        $message = $this->get_email_template($notification);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    private function get_email_template($notification) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($notification->title); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #2563eb;"><?php echo esc_html($notification->title); ?></h1>
                <div style="margin: 20px 0;">
                    <?php echo wpautop($notification->message); ?>
                </div>
                <?php if ($notification->data): ?>
                    <div style="background: #f3f4f6; padding: 15px; border-radius: 5px;">
                        <pre><?php echo esc_html(json_encode(json_decode($notification->data), JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                <?php endif; ?>
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
                    <p>Este é um e-mail automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}