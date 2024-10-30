<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Logger {
    private $log_dir;
    private $log_file;
    private $max_file_size = 5242880; // 5MB
    private $max_files = 5;
    
    public function __construct() {
        $this->log_dir = DROPFLEX_PLUGIN_DIR . 'logs/';
        $this->log_file = $this->log_dir . 'dropflex.log';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
    }
    
    public function log($message, $level = 'info', $context = []) {
        if ($this->should_rotate()) {
            $this->rotate();
        }
        
        $log_entry = $this->format_log_entry($message, $level, $context);
        
        file_put_contents(
            $this->log_file,
            $log_entry . PHP_EOL,
            FILE_APPEND
        );
    }
    
    private function format_log_entry($message, $level, $context) {
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? json_encode($context) : '';
        
        return sprintf(
            '[%s] %s: %s %s',
            $timestamp,
            strtoupper($level),
            $message,
            $context_str
        );
    }
    
    private function should_rotate() {
        if (!file_exists($this->log_file)) {
            return false;
        }
        
        return filesize($this->log_file) >= $this->max_file_size;
    }
    
    private function rotate() {
        for ($i = $this->max_files - 1; $i >= 0; $i--) {
            $old_file = $this->log_file . ($i > 0 ? '.' . $i : '');
            $new_file = $this->log_file . '.' . ($i + 1);
            
            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }
    }
    
    public function get_logs($limit = 100, $level = null) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($this->log_file, 'r');
        
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false && $count < $limit) {
                if ($level) {
                    if (strpos($line, strtoupper($level)) !== false) {
                        $logs[] = $this->parse_log_line($line);
                        $count++;
                    }
                } else {
                    $logs[] = $this->parse_log_line($line);
                    $count++;
                }
            }
            fclose($handle);
        }
        
        return array_reverse($logs);
    }
    
    private function parse_log_line($line) {
        preg_match('/\[(.*?)\] (.*?): (.*?) ({.*})?/', $line, $matches);
        
        return [
            'timestamp' => isset($matches[1]) ? $matches[1] : '',
            'level' => isset($matches[2]) ? $matches[2] : '',
            'message' => isset($matches[3]) ? $matches[3] : '',
            'context' => isset($matches[4]) ? json_decode($matches[4], true) : []
        ];
    }
    
    public function clear_logs() {
        $files = glob($this->log_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    public function get_log_size() {
        if (!file_exists($this->log_file)) {
            return 0;
        }
        
        return filesize($this->log_file);
    }
    
    public function export_logs($format = 'json') {
        $logs = $this->get_logs(1000);
        
        switch ($format) {
            case 'json':
                return json_encode($logs, JSON_PRETTY_PRINT);
                
            case 'csv':
                $csv = "Timestamp,Level,Message,Context\n";
                foreach ($logs as $log) {
                    $csv .= sprintf(
                        '"%s","%s","%s","%s"',
                        $log['timestamp'],
                        $log['level'],
                        $log['message'],
                        json_encode($log['context'])
                    ) . "\n";
                }
                return $csv;
                
            default:
                throw new Exception('Formato não suportado');
        }
    }
}