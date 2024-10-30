<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Cache_Manager {
    private $cache_dir;
    private $cache_time = 3600; // 1 hora
    
    public function __construct() {
        $this->cache_dir = DROPFLEX_PLUGIN_DIR . 'cache/';
        
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }
    
    public function get($key) {
        $file = $this->get_cache_file($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        if ($this->is_expired($file)) {
            unlink($file);
            return false;
        }
        
        $content = file_get_contents($file);
        return unserialize($content);
    }
    
    public function set($key, $data, $expiration = null) {
        $file = $this->get_cache_file($key);
        $content = serialize($data);
        
        file_put_contents($file, $content);
        
        if ($expiration) {
            touch($file, time() + $expiration);
        }
        
        return true;
    }
    
    public function delete($key) {
        $file = $this->get_cache_file($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return false;
    }
    
    public function flush() {
        $files = glob($this->cache_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    private function get_cache_file($key) {
        return $this->cache_dir . md5($key) . '.cache';
    }
    
    private function is_expired($file) {
        $expiration = filemtime($file) + $this->cache_time;
        return $expiration < time();
    }
    
    public function get_stats() {
        $files = glob($this->cache_dir . '*');
        $total_size = 0;
        $total_files = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $total_size += filesize($file);
                $total_files++;
            }
        }
        
        return [
            'total_files' => $total_files,
            'total_size' => $total_size,
            'cache_dir' => $this->cache_dir
        ];
    }
    
    public function clean_expired() {
        $files = glob($this->cache_dir . '*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && $this->is_expired($file)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}