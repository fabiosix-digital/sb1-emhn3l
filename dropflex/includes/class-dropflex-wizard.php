<?php
if (!defined('ABSPATH')) {
    exit;
}

class DropFlex_Wizard {
    private $steps = [
        'business-type' => [
            'title' => 'Tipo de Negócio',
            'description' => 'Escolha o tipo de site que deseja criar'
        ],
        'template' => [
            'title' => 'Template',
            'description' => 'Escolha o modelo base do seu site'
        ],
        'header' => [
            'title' => 'Cabeçalho',
            'description' => 'Personalize o cabeçalho do seu site'
        ],
        'colors' => [
            'title' => 'Cores',
            'description' => 'Escolha as cores do seu site'
        ],
        'content' => [
            'title' => 'Conteúdo',
            'description' => 'Adicione o conteúdo inicial'
        ],
        'settings' => [
            'title' => 'Configurações',
            'description' => 'Configure as informações básicas'
        ]
    ];
    
    public function get_steps() {
        return $this->steps;
    }
    
    public function get_step($step) {
        return isset($this->steps[$step]) ? $this->steps[$step] : null;
    }
    
    public function render_step($step) {
        if (!isset($this->steps[$step])) {
            return false;
        }
        
        $template_file = DROPFLEX_PLUGIN_DIR . "admin/views/wizard/{$step}.php";
        
        if (file_exists($template_file)) {
            include $template_file;
            return true;
        }
        
        return false;
    }
    
    public function save_step($step, $data) {
        $user_id = get_current_user_id();
        update_user_meta($user_id, "dropflex_wizard_{$step}", $data);
        return true;
    }
    
    public function get_step_data($step) {
        $user_id = get_current_user_id();
        return get_user_meta($user_id, "dropflex_wizard_{$step}", true);
    }
}