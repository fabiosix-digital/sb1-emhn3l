<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_REST_Controller extends WP_REST_Controller {
    private $namespace = 'dropflex/v1';
    private $site_manager;
    private $template_manager;
    private $customization_manager;
    private $checkout_manager;
    private $integration_manager;
    
    public function __construct() {
        $this->site_manager = new DropFlex_Site_Manager();
        $this->template_manager = new DropFlex_Template_Manager();
        $this->customization_manager = new DropFlex_Customization_Manager();
        $this->checkout_manager = new DropFlex_Checkout_Manager();
        $this->integration_manager = new DropFlex_Integration_Manager();
    }
    
    public function register_routes() {
        // Sites
        register_rest_route($this->namespace, '/sites', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_sites'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_site'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        register_rest_route($this->namespace, '/sites/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_site'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_site'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_site'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Templates
        register_rest_route($this->namespace, '/templates', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_templates'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        register_rest_route($this->namespace, '/templates/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_template'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Customization
        register_rest_route($this->namespace, '/sites/(?P<id>\d+)/customization', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_customization'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_customization'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Checkout
        register_rest_route($this->namespace, '/checkout/session', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_checkout_session'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        // Integrations
        register_rest_route($this->namespace, '/integrations', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_integrations'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
        
        register_rest_route($this->namespace, '/integrations/(?P<type>[a-zA-Z0-9_-]+)/connect', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'connect_integration'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);
    }
    
    public function check_permission($request) {
        return current_user_can('manage_options') || current_user_can('dropflex_manage_sites');
    }
    
    // Sites
    public function get_sites($request) {
        $sites = $this->site_manager->get_user_sites();
        return rest_ensure_response($sites);
    }
    
    public function create_site($request) {
        $params = $request->get_params();
        
        try {
            $site = $this->site_manager->create_site($params);
            return rest_ensure_response($site);
        } catch (Exception $e) {
            return new WP_Error('create_site_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    public function get_site($request) {
        $site_id = $request['id'];
        $site = $this->site_manager->get_site($site_id);
        
        if (!$site) {
            return new WP_Error('site_not_found', 'Site não encontrado', ['status' => 404]);
        }
        
        return rest_ensure_response($site);
    }
    
    public function update_site($request) {
        $site_id = $request['id'];
        $params = $request->get_params();
        
        try {
            $site = $this->site_manager->update_site($site_id, $params);
            return rest_ensure_response($site);
        } catch (Exception $e) {
            return new WP_Error('update_site_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    public function delete_site($request) {
        $site_id = $request['id'];
        
        try {
            $result = $this->site_manager->delete_site($site_id);
            return rest_ensure_response(['success' => $result]);
        } catch (Exception $e) {
            return new WP_Error('delete_site_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    // Templates
    public function get_templates($request) {
        $type = $request->get_param('type');
        $templates = $this->template_manager->get_templates($type);
        return rest_ensure_response($templates);
    }
    
    public function get_template($request) {
        $template_id = $request['id'];
        $template = $this->template_manager->get_template($template_id);
        
        if (!$template) {
            return new WP_Error('template_not_found', 'Template não encontrado', ['status' => 404]);
        }
        
        return rest_ensure_response($template);
    }
    
    // Customization
    public function get_customization($request) {
        $site_id = $request['id'];
        $customization = $this->customization_manager->get_customization($site_id);
        return rest_ensure_response($customization);
    }
    
    public function update_customization($request) {
        $site_id = $request['id'];
        $params = $request->get_params();
        
        try {
            $result = $this->customization_manager->save_customization($site_id, $params);
            return rest_ensure_response(['success' => $result]);
        } catch (Exception $e) {
            return new WP_Error('update_customization_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    // Checkout
    public function create_checkout_session($request) {
        $params = $request->get_params();
        
        try {
            $session = $this->checkout_manager->create_checkout_session($params);
            return rest_ensure_response($session);
        } catch (Exception $e) {
            return new WP_Error('create_checkout_error', $e->getMessage(), ['status' => 400]);
        }
    }
    
    // Integrations
    public function get_integrations($request) {
        $type = $request->get_param('type');
        $integrations = $this->integration_manager->get_available_integrations($type);
        return rest_ensure_response($integrations);
    }
    
    public function connect_integration($request) {
        $type = $request['type'];
        $credentials = $request->get_params();
        
        try {
            $result = $this->integration_manager->setup_integration($type, $credentials);
            return rest_ensure_response(['success' => $result]);
        } catch (Exception $e) {
            return new WP_Error('connect_integration_error', $e->getMessage(), ['status' => 400]);
        }
    }
}