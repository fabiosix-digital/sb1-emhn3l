<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_WordPress_Installer {
    private $wp_cli_path;
    
    public function __construct() {
        $this->wp_cli_path = DROPFLEX_PLUGIN_DIR . 'bin/wp-cli.phar';
    }
    
    public function install($params) {
        try {
            // 1. Download WordPress
            $this->download_wordpress($params['domain']);
            
            // 2. Criar configuração do banco de dados
            $this->create_db_config($params['domain']);
            
            // 3. Instalar WordPress
            $this->run_wp_installation($params);
            
            // 4. Configurar permalinks
            $this->configure_permalinks($params['domain']);
            
            // 5. Instalar tema padrão
            $this->install_default_theme($params['domain']);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Erro ao instalar WordPress: " . $e->getMessage());
        }
    }
    
    private function download_wordpress($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && wget https://wordpress.org/latest.tar.gz && tar -xzf latest.tar.gz && mv wordpress/* . && rmdir wordpress && rm latest.tar.gz',
            escapeshellarg($domain)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception("Erro ao baixar WordPress");
        }
    }
    
    private function create_db_config($domain) {
        // Gerar credenciais do banco
        $db_name = substr(str_replace(['.', '-'], '_', $domain), 0, 64);
        $db_user = substr($db_name, 0, 16);
        $db_password = wp_generate_password(32, true, true);
        
        // Criar banco e usuário via WHM API
        $whm = new DropFlex_WHM_Integration();
        $whm->create_database([
            'name' => $db_name,
            'user' => $db_user,
            'password' => $db_password
        ]);
        
        // Criar wp-config.php
        $config_sample = file_get_contents("/home/{$domain}/public_html/wp-config-sample.php");
        $config = str_replace(
            ['database_name_here', 'username_here', 'password_here'],
            [$db_name, $db_user, $db_password],
            $config_sample
        );
        
        // Adicionar salt keys
        $salts = wp_remote_get('https://api.wordpress.org/secret-key/1.1/salt/');
        if (!is_wp_error($salts)) {
            $config = str_replace('put your unique phrase here', $salts['body'], $config);
        }
        
        file_put_contents("/home/{$domain}/public_html/wp-config.php", $config);
    }
    
    private function run_wp_installation($params) {
        $command = sprintf(
            'cd /home/%s/public_html && php %s core install --url="%s" --title="%s" --admin_user="%s" --admin_password="%s" --admin_email="%s"',
            escapeshellarg($params['domain']),
            escapeshellarg($this->wp_cli_path),
            escapeshellarg('https://' . $params['domain']),
            escapeshellarg($params['title']),
            escapeshellarg($params['admin_user']),
            escapeshellarg($params['admin_password']),
            escapeshellarg($params['admin_email'])
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception("Erro na instalação do WordPress");
        }
    }
    
    private function configure_permalinks($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && php %s rewrite structure "%%postname%%"',
            escapeshellarg($domain),
            escapeshellarg($this->wp_cli_path)
        );
        
        exec($command);
    }
    
    private function install_default_theme($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && php %s theme install astra --activate',
            escapeshellarg($domain),
            escapeshellarg($this->wp_cli_path)
        );
        
        exec($command);
    }
    
    public function install_plugin($domain, $plugin_slug) {
        $command = sprintf(
            'cd /home/%s/public_html && php %s plugin install %s --activate',
            escapeshellarg($domain),
            escapeshellarg($this->wp_cli_path),
            escapeshellarg($plugin_slug)
        );
        
        exec($command);
    }
}