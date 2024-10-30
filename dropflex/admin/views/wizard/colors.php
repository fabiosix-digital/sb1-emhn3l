<?php
if (!defined('ABSPATH')) {
    exit;
}

$component_manager = new DropFlex_Component_Manager();
$color_schemes = $component_manager->get_color_schemes();
?>

<div class="dropflex-wizard-step" id="step-colors">
    <h2><?php _e('Personalize as cores do seu site', 'dropflex'); ?></h2>
    
    <div class="dropflex-color-schemes">
        <h3><?php _e('Esquemas de Cores', 'dropflex'); ?></h3>
        
        <div class="dropflex-schemes-grid">
            <?php foreach ($color_schemes as $id => $scheme): ?>
                <div class="dropflex-scheme-card" data-scheme-id="<?php echo esc_attr($id); ?>">
                    <h4><?php echo esc_html($scheme['name']); ?></h4>
                    
                    <div class="scheme-preview">
                        <?php foreach ($scheme['colors'] as $name => $color): ?>
                            <div class="color-swatch" 
                                 style="background-color: <?php echo esc_attr($color); ?>"
                                 data-color="<?php echo esc_attr($color); ?>"
                                 data-name="<?php echo esc_attr($name); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="button button-primary apply-scheme">
                        <?php _e('Aplicar', 'dropflex'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="dropflex-custom-colors">
        <h3><?php _e('Cores Personalizadas', 'dropflex'); ?></h3>
        
        <div class="dropflex-color-grid">
            <div class="color-field">
                <label><?php _e('Cor Principal', 'dropflex'); ?></label>
                <input type="text" class="dropflex-color-picker" name="colors[primary]" 
                       value="#2563eb" data-default-color="#2563eb">
            </div>
            
            <div class="color-field">
                <label><?php _e('Cor Secundária', 'dropflex'); ?></label>
                <input type="text" class="dropflex-color-picker" name="colors[secondary]" 
                       value="#4f46e5" data-default-color="#4f46e5">
            </div>
            
            <div class="color-field">
                <label><?php _e('Cor de Destaque', 'dropflex'); ?></label>
                <input type="text" class="dropflex-color-picker" name="colors[accent]" 
                       value="#f59e0b" data-default-color="#f59e0b">
            </div>
            
            <div class="color-field">
                <label><?php _e('Cor de Fundo', 'dropflex'); ?></label>
                <input type="text" class="dropflex-color-picker" name="colors[background]" 
                       value="#ffffff" data-default-color="#ffffff">
            </div>
            
            <div class="color-field">
                <label><?php _e('Cor do Texto', 'dropflex'); ?></label>
                <input type="text" class="dropflex-color-picker" name="colors[text]" 
                       value="#1f2937" data-default-color="#1f2937">
            </div>
        </div>
        
        <div class="dropflex-color-preview">
            <h4><?php _e('Preview', 'dropflex'); ?></h4>
            <div class="preview-container">
                <!-- Preview dinâmico será inserido via JavaScript -->
            </div>
        </div>
    </div>
    
    <div class="dropflex-wizard-navigation">
        <button class="button dropflex-prev-step">
            <?php _e('Voltar', 'dropflex'); ?>
        </button>
        <button class="button button-primary dropflex-next-step">
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>