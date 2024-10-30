<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Plugin_Manager {
    private $db;
    private $required_plugins = [
        'woocommerce' => [
            'name' => 'WooCommerce',
            'file' => 'woocommerce/woocommerce.php',
            'required' => true
        ],
        'elementor' => [
            'name' => 'Elementor',
            'file' => 'elementor/elementor.php',
            'required' => true
        ],
        'wordpress-seo' => [
            'name' => 'Yoast SEO',
            'file' => 'wordpress-seo/wp-seo.php',
            'required' => true
        ],
        'wp-super-cache' => [
            'name' => 'WP Super Cache',
            'file' => 'wp-super-cache/wp-cache.php',
            'required' => true
        ]
    ];
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    public function install_required_plugins($domain) {
        $results = [];
        
        foreach ($this->required_plugins as $slug => $plugin) {
            try {
                $this->install_plugin($domain, $slug);
                $results[$slug] = [
                    'success' => true,
                    'message' => sprintf(__('Plugin %s instalado com sucesso', 'dropflex'), $plugin['name'])
                ];
            } catch (Exception $e) {
                $results[$slug] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                
                if ($plugin['required']) {
                    throw new Exception(sprintf(
                        __('Erro ao instalar plugin obrigatório %s: %s', 'dropflex'),
                        $plugin['name'],
                        $e->getMessage()
                    ));
                }
            }
        }
        
        return $results;
    }
    
    public function install_plugin($domain, $plugin_slug) {
        // Instalar plugin
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin install %s --activate',
            escapeshellarg($domain),
            escapeshellarg($plugin_slug)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception(sprintf(
                __('Erro ao instalar plugin %s', 'dropflex'),
                $plugin_slug
            ));
        }
        
        // Configurar plugin
        $this->configure_plugin($domain, $plugin_slug);
        
        return true;
    }
    
    private function configure_plugin($domain, $plugin_slug) {
        switch ($plugin_slug) {
            case 'woocommerce':
                $this->configure_woocommerce($domain);
                break;
                
            case 'elementor':
                $this->configure_elementor($domain);
                break;
                
            case 'wordpress-seo':
                $this->configure_yoast_seo($domain);
                break;
                
            case 'wp-super-cache':
                $this->configure_wp_super_cache($domain);
                break;
        }
    }
    
    private function configure_woocommerce($domain) {
        $options = [
            'woocommerce_store_address' => '',
            'woocommerce_store_city' => '',
            'woocommerce_default_country' => 'BR',
            'woocommerce_currency' => 'BRL',
            'woocommerce_weight_unit' => 'kg',
            'woocommerce_dimension_unit' => 'cm',
            'woocommerce_calc_taxes' => 'yes'
        ];
        
        foreach ($options as $option => $value) {
            $command = sprintf(
                'cd /home/%s/public_html && wp option update %s %s',
                escapeshellarg($domain),
                escapeshellarg($option),
                escapeshellarg($value)
            );
            
            exec($command);
        }
    }
    
    private function configure_elementor($domain) {
        $options = [
            'elementor_disable_color_schemes' => 'yes',
            'elementor_disable_typography_schemes' => 'yes',
            'elementor_container_width' => '1140',
            'elementor_space_between_widgets' => '20',
            'elementor_page_title_selector' => 'h1.entry-title'
        ];
        
        foreach ($options as $option => $value) {
            $command = sprintf(
                'cd /home/%s/public_html && wp option update %s %s',
                escapeshellarg($domain),
                escapeshellarg($option),
                escapeshellarg($value)
            );
            
            exec($command);
        }
    }
    
    private function configure_yoast_seo($domain) {
        $options = [
            'wpseo_titles' => [
                'separator' => 'sc-dash',
                'title-home-wpseo' => '%%sitename%% %%page%% %%sep%% %%sitedesc%%',
                'title-author-wpseo' => '%%name%% %%sep%% %%sitename%%',
                'title-archive-wpseo' => '%%date%% %%sep%% %%sitename%%',
                'title-search-wpseo' => '%%searchphrase%% %%sep%% %%sitename%%',
                'title-404-wpseo' => 'Página não encontrada %%sep%% %%sitename%%'
            ],
            'wpseo_social' => [
                'opengraph' => true,
                'twitter' => true,
                'pinterest' => false
            ]
        ];
        
        foreach ($options as $option => $value) {
            $command = sprintf(
                'cd /home/%s/public_html && wp option update %s %s',
                escapeshellarg($domain),
                escapeshellarg($option),
                escapeshellarg(json_encode($value))
            );
            
            exec($command);
        }
    }
    
    private function configure_wp_super_cache($domain) {
        $options = [
            'wp_cache_status' => 1,
            'wp_cache_mod_rewrite' => 0,
            'wp_cache_not_logged_in' => 1,
            'wp_cache_make_known_anon' => 1,
            'wp_cache_mobile_enabled' => 1,
            'wp_cache_front_page_checks' => 1
        ];
        
        foreach ($options as $option => $value) {
            $command = sprintf(
                'cd /home/%s/public_html && wp option update %s %s',
                escapeshellarg($domain),
                escapeshellarg($option),
                escapeshellarg($value)
            );
            
            exec($command);
        }
    }
    
    public function get_installed_plugins($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin list --format=json',
            escapeshellarg($domain)
        );
        
        exec($command, $output);
        
        if (empty($output)) {
            return [];
        }
        
        return json_decode($output[0], true);
    }
    
    public function activate_plugin($domain, $plugin) {
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin activate %s',
            escapeshellarg($domain),
            escapeshellarg($plugin)
        );
        
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    }
    
    public function deactivate_plugin($domain, $plugin) {
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin deactivate %s',
            escapeshellarg($domain),
            escapeshellarg($plugin)
        );
        
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    }
    
    public function delete_plugin($domain, $plugin) {
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin delete %s',
            escapeshellarg($domain),
            escapeshellarg($plugin)
        );
        
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    }
    
    public function update_plugins($domain) {
        $command = sprintf(
            'cd /home/%s/public_html && wp plugin update --all',
            escapeshellarg($domain)
        );
        
        exec($command, $output, $return_var);
        
        return $return_var === 0;
    }
}