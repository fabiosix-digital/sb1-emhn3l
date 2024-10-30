<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Settings {
    private $options;
    
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function init_settings() {
        register_setting(
            'dropflex_options',
            'dropflex_whm_settings',
            array($this, 'sanitize_whm_settings')
        );
        
        register_setting(
            'dropflex_options',
            'dropflex_general_settings',
            array($this, 'sanitize_general_settings')
        );
        
        register_setting(
            'dropflex_options',
            'dropflex_payment_settings',
            array($this, 'sanitize_payment_settings')
        );
        
        // WHM Settings
        add_settings_section(
            'dropflex_whm_section',
            __('Configurações WHM', 'dropflex'),
            array($this, 'whm_section_callback'),
            'dropflex-settings'
        );
        
        add_settings_field(
            'whm_api_url',
            __('URL da API', 'dropflex'),
            array($this, 'whm_api_url_callback'),
            'dropflex-settings',
            'dropflex_whm_section'
        );
        
        add_settings_field(
            'whm_username',
            __('Usuário', 'dropflex'),
            array($this, 'whm_username_callback'),
            'dropflex-settings',
            'dropflex_whm_section'
        );
        
        add_settings_field(
            'whm_api_token',
            __('Token da API', 'dropflex'),
            array($this, 'whm_api_token_callback'),
            'dropflex-settings',
            'dropflex_whm_section'
        );
        
        // General Settings
        add_settings_section(
            'dropflex_general_section',
            __('Configurações Gerais', 'dropflex'),
            array($this, 'general_section_callback'),
            'dropflex-settings'
        );
        
        add_settings_field(
            'max_sites_per_user',
            __('Máximo de sites por usuário', 'dropflex'),
            array($this, 'max_sites_callback'),
            'dropflex-settings',
            'dropflex_general_section'
        );
        
        add_settings_field(
            'enable_dropshipping',
            __('Habilitar Dropshipping', 'dropflex'),
            array($this, 'enable_dropshipping_callback'),
            'dropflex-settings',
            'dropflex_general_section'
        );
        
        // Payment Settings
        add_settings_section(
            'dropflex_payment_section',
            __('Configurações de Pagamento', 'dropflex'),
            array($this, 'payment_section_callback'),
            'dropflex-settings'
        );
        
        add_settings_field(
            'payment_gateway',
            __('Gateway de Pagamento', 'dropflex'),
            array($this, 'payment_gateway_callback'),
            'dropflex-settings',
            'dropflex_payment_section'
        );
    }
    
    public function sanitize_whm_settings($input) {
        $new_input = array();
        
        if(isset($input['api_url']))
            $new_input['api_url'] = esc_url_raw($input['api_url']);
            
        if(isset($input['username']))
            $new_input['username'] = sanitize_text_field($input['username']);
            
        if(isset($input['api_token']))
            $new_input['api_token'] = sanitize_text_field($input['api_token']);
            
        return $new_input;
    }
    
    public function sanitize_general_settings($input) {
        $new_input = array();
        
        if(isset($input['max_sites']))
            $new_input['max_sites'] = absint($input['max_sites']);
            
        if(isset($input['enable_dropshipping']))
            $new_input['enable_dropshipping'] = sanitize_text_field($input['enable_dropshipping']);
            
        return $new_input;
    }
    
    public function sanitize_payment_settings($input) {
        $new_input = array();
        
        if(isset($input['gateway']))
            $new_input['gateway'] = sanitize_text_field($input['gateway']);
            
        return $new_input;
    }
    
    // Section Callbacks
    public function whm_section_callback() {
        echo '<p>' . __('Configure suas credenciais do WHM para gerenciar os sites.', 'dropflex') . '</p>';
    }
    
    public function general_section_callback() {
        echo '<p>' . __('Configurações gerais do plugin.', 'dropflex') . '</p>';
    }
    
    public function payment_section_callback() {
        echo '<p>' . __('Configure as opções de pagamento.', 'dropflex') . '</p>';
    }
    
    // Field Callbacks
    public function whm_api_url_callback() {
        $options = get_option('dropflex_whm_settings');
        printf(
            '<input type="url" id="whm_api_url" name="dropflex_whm_settings[api_url]" value="%s" class="regular-text" />',
            isset($options['api_url']) ? esc_attr($options['api_url']) : ''
        );
    }
    
    public function whm_username_callback() {
        $options = get_option('dropflex_whm_settings');
        printf(
            '<input type="text" id="whm_username" name="dropflex_whm_settings[username]" value="%s" class="regular-text" />',
            isset($options['username']) ? esc_attr($options['username']) : ''
        );
    }
    
    public function whm_api_token_callback() {
        $options = get_option('dropflex_whm_settings');
        printf(
            '<input type="password" id="whm_api_token" name="dropflex_whm_settings[api_token]" value="%s" class="regular-text" />',
            isset($options['api_token']) ? esc_attr($options['api_token']) : ''
        );
    }
    
    public function max_sites_callback() {
        $options = get_option('dropflex_general_settings');
        printf(
            '<input type="number" id="max_sites" name="dropflex_general_settings[max_sites]" value="%s" class="small-text" />',
            isset($options['max_sites']) ? esc_attr($options['max_sites']) : '1'
        );
    }
    
    public function enable_dropshipping_callback() {
        $options = get_option('dropflex_general_settings');
        printf(
            '<input type="checkbox" id="enable_dropshipping" name="dropflex_general_settings[enable_dropshipping]" value="1" %s />',
            isset($options['enable_dropshipping']) && $options['enable_dropshipping'] ? 'checked' : ''
        );
    }
    
    public function payment_gateway_callback() {
        $options = get_option('dropflex_payment_settings');
        $gateways = array(
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'mercadopago' => 'MercadoPago'
        );
        
        echo '<select id="payment_gateway" name="dropflex_payment_settings[gateway]">';
        foreach ($gateways as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                isset($options['gateway']) && $options['gateway'] === $key ? 'selected' : '',
                esc_html($label)
            );
        }
        echo '</select>';
    }
}