<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Asset_Manager {
    private $db;
    private $uploads_dir;
    private $uploads_url;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        $upload_dir = wp_upload_dir();
        $this->uploads_dir = $upload_dir['basedir'] . '/dropflex/';
        $this->uploads_url = $upload_dir['baseurl'] . '/dropflex/';
        
        if (!file_exists($this->uploads_dir)) {
            wp_mkdir_p($this->uploads_dir);
        }
    }
    
    public function upload_image($file, $site_id) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        $filename = $this->generate_filename($file['name'], $site_id);
        $upload_path = $this->uploads_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Erro ao fazer upload do arquivo');
        }
        
        return [
            'url' => $this->uploads_url . $filename,
            'path' => $upload_path,
            'filename' => $filename
        ];
    }
    
    private function generate_filename($original_name, $site_id) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $base = sanitize_file_name(pathinfo($original_name, PATHINFO_FILENAME));
        $timestamp = time();
        
        return sprintf(
            '%d-%s-%s.%s',
            $site_id,
            $base,
            $timestamp,
            $extension
        );
    }
    
    public function delete_image($filename) {
        $file_path = $this->uploads_dir . $filename;
        
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        
        return false;
    }
    
    public function optimize_image($file_path, $quality = 85) {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }
        
        $info = getimagesize($file_path);
        if (!$info) {
            return false;
        }
        
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file_path);
                imagejpeg($image, $file_path, $quality);
                break;
                
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file_path);
                imagepng($image, $file_path, round(9 * $quality / 100));
                break;
                
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($file_path);
                imagegif($image, $file_path);
                break;
                
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($file_path);
                imagewebp($image, $file_path, $quality);
                break;
                
            default:
                return false;
        }
        
        imagedestroy($image);
        return true;
    }
    
    public function create_image_sizes($file_path) {
        $sizes = [
            'thumbnail' => [150, 150],
            'medium' => [300, 300],
            'large' => [1024, 1024]
        ];
        
        $info = getimagesize($file_path);
        if (!$info) {
            return false;
        }
        
        $original_width = $info[0];
        $original_height = $info[1];
        
        foreach ($sizes as $size_name => $dimensions) {
            $this->create_image_size(
                $file_path,
                $size_name,
                $dimensions[0],
                $dimensions[1],
                $original_width,
                $original_height
            );
        }
        
        return true;
    }
    
    private function create_image_size($file_path, $size_name, $max_width, $max_height, $original_width, $original_height) {
        // Calcular dimensões proporcionais
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        $new_width = round($original_width * $ratio);
        $new_height = round($original_height * $ratio);
        
        // Criar nova imagem
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Carregar imagem original
        switch (exif_imagetype($file_path)) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($file_path);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($file_path);
                break;
            default:
                return false;
        }
        
        // Redimensionar
        imagecopyresampled(
            $new_image,
            $source,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $original_width,
            $original_height
        );
        
        // Salvar nova imagem
        $path_info = pathinfo($file_path);
        $new_path = sprintf(
            '%s/%s-%s.%s',
            $path_info['dirname'],
            $path_info['filename'],
            $size_name,
            $path_info['extension']
        );
        
        switch (exif_imagetype($file_path)) {
            case IMAGETYPE_JPEG:
                imagejpeg($new_image, $new_path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($new_image, $new_path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($new_image, $new_path);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($new_image, $new_path, 85);
                break;
        }
        
        imagedestroy($new_image);
        imagedestroy($source);
        
        return true;
    }
    
    public function get_image_url($filename, $size = 'full') {
        if ($size === 'full') {
            return $this->uploads_url . $filename;
        }
        
        $path_info = pathinfo($filename);
        $sized_filename = sprintf(
            '%s-%s.%s',
            $path_info['filename'],
            $size,
            $path_info['extension']
        );
        
        return $this->uploads_url . $sized_filename;
    }
    
    public function clean_old_files($days = 7) {
        $files = glob($this->uploads_dir . '*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $days * 24 * 60 * 60) {
                    unlink($file);
                }
            }
        }
    }
}