<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_WHM_Integration {
    private $api_url;
    private $username;
    private $api_token;
    
    public function __construct() {
        $this->api_url = get_option('dropflex_whm_api_url');
        $this->username = get_option('dropflex_whm_username');
        $this->api_token = get_option('dropflex_whm_api_token');
    }
    
    public function create_account($params) {
        $endpoint = '/json-api/createacct';
        
        $data = [
            'domain' => $params['domain'],
            'username' => $params['username'],
            'password' => $params['password'],
            'plan' => $params['plan'],
            'contactemail' => get_option('admin_email'),
            'cpmod' => 'paper_lantern',
            'maxsql' => 1,
            'maxpop' => 1,
            'maxsub' => 1,
            'maxpark' => 0,
            'maxaddon' => 0,
            'bwlimit' => 500,
            'customip' => '',
            'language' => 'pt',
            'useregns' => 1,
            'hasuseregns' => 1,
            'reseller' => 0
        ];
        
        $response = $this->make_request($endpoint, $data);
        
        if (!$response['success']) {
            throw new Exception($response['message']);
        }
        
        return $response['data'];
    }
    
    public function delete_account($domain) {
        $endpoint = '/json-api/removeacct';
        
        $data = [
            'domain' => $domain
        ];
        
        $response = $this->make_request($endpoint, $data);
        
        if (!$response['success']) {
            throw new Exception($response['message']);
        }
        
        return true;
    }
    
    public function check_account_exists($username) {
        $endpoint = '/json-api/accountsummary';
        
        $data = [
            'user' => $username
        ];
        
        $response = $this->make_request($endpoint, $data);
        
        return $response['success'];
    }
    
    private function make_request($endpoint, $data) {
        if (empty($this->api_url) || empty($this->username) || empty($this->api_token)) {
            throw new Exception("Configurações da API WHM não definidas");
        }
        
        $url = rtrim($this->api_url, '/') . $endpoint;
        
        $headers = [
            'Authorization' => 'WHM ' . $this->username . ':' . $this->api_token
        ];
        
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => $data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar resposta da API");
        }
        
        return $result;
    }
    
    public function validate_credentials() {
        try {
            $endpoint = '/json-api/version';
            $response = $this->make_request($endpoint, []);
            return $response['success'];
        } catch (Exception $e) {
            return false;
        }
    }
}