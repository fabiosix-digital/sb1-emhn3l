<?php
/**
 * Plugin Name: DropFlex
 * Description: Sistema completo de criação e personalização de sites WordPress com integração WHM
 * Version: 1.0.0
 * Author: DropFlex
 * Text Domain: dropflex
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'DropFlex_';
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $relative_class = strtolower(str_replace('_', '-', $relative_class));
    $file = $base_dir . 'class-' . $relative_class . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

class DropFlex {
    private static $instance = null;
    private $version = '1.0.0';
    private $loader;
    private $plugin_name = 'dropflex';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_components();
    }
    
    private function define_constants() {
        define('DROPFLEX_VERSION', $this->version);
        define('DROPFLEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('DROPFLEX_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('DROPFLEX_PLUGIN_BASENAME', plugin_basename(__FILE__));
        define('DROPFLEX_TEMPLATES_DIR', DROPFLEX_PLUGIN_DIR . 'templates/');
        define('DROPFLEX_ASSETS_URL', DROPFLEX_PLUGIN_URL . 'assets/');
    }
    
    private function load_dependencies() {
        $this->loader = new DropFlex_Loader();
    }
    
    private function set_locale() {
        $plugin_i18n = new DropFlex_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new DropFlex_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_filter('plugin_action_links_' . DROPFLEX_PLUGIN_BASENAME, $plugin_admin, 'add_action_links');
    }
    
    private function define_public_hooks() {
        $plugin_public = new DropFlex_Public($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }
    
    private function init_components() {
        // Inicializar componentes principais
        new DropFlex_Site_Manager();
        new DropFlex_Template_Manager();
        new DropFlex_Customization_Manager();
        new DropFlex_Subscription();
        new DropFlex_Wizard();
        
        // Inicializar API REST
        add_action('rest_api_init', function () {
            $controller = new DropFlex_REST_Controller();
            $controller->register_routes();
        });
    }
    
    public function run() {
        $this->loader->run();
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_loader() {
        return $this->loader;
    }
    
    public function get_version() {
        return $this->version;
    }
}

function dropflex() {
    return DropFlex::get_instance();
}

// Inicialização
add_action('plugins_loaded', 'dropflex');

// Ativação e Desativação
register_activation_hook(__FILE__, function() {
    require_once DROPFLEX_PLUGIN_DIR . 'includes/class-dropflex-activator.php';
    DropFlex_Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once DROPFLEX_PLUGIN_DIR . 'includes/class-dropflex-deactivator.php';
    DropFlex_Deactivator::deactivate();
});