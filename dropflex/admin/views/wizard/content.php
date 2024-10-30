<?php
if (!defined('ABSPATH')) {
    exit;
}

$component_manager = new DropFlex_Component_Manager();
$home_templates = $component_manager->get_components('home');
?>

<div class="dropflex-wizard-step" id="step-content">
    <h2><?php _e('Personalize o conteúdo inicial', 'dropflex'); ?></h2>
    
    <div class="dropflex-content-form">
        <div class="form-section">
            <h3><?php _e('Informações Básicas', 'dropflex'); ?></h3>
            
            <div class="form-field">
                <label for="site_title"><?php _e('Título do Site', 'dropflex'); ?></label>
                <input type="text" id="site_title" name="site_title" required>
            </div>
            
            <div class="form-field">
                <label for="site_description"><?php _e('Descrição', 'dropflex'); ?></label>
                <textarea id="site_description" name="site_description" rows="3"></textarea>
            </div>
            
            <div class="form-field">
                <label for="site_logo"><?php _e('Logo', 'dropflex'); ?></label>
                <div class="logo-upload">
                    <input type="hidden" id="site_logo" name="site_logo">
                    <div id="logo-preview"></div>
                    <button type="button" class="button" id="upload-logo">
                        <?php _e('Upload Logo', 'dropflex'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Modelo da Página Inicial', 'dropflex'); ?></h3>
            
            <div class="dropflex-home-templates">
                <?php foreach ($home_templates as $template): ?>
                    <div class="template-option" data-template-id="<?php echo esc_attr($template['id']); ?>">
                        <div class="template-preview">
                            <?php if (isset($template['preview_image'])): ?>
                                <img src="<?php echo esc_url($template['preview_image']); ?>" 
                                     alt="<?php echo esc_attr($template['name']); ?>">
                            <?php else: ?>
                                <div class="preview-placeholder">
                                    <span class="dashicons dashicons-layout"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="template-info">
                            <h4><?php echo esc_html($template['name']); ?></h4>
                            <?php if (isset($template['description'])): ?>
                                <p><?php echo esc_html($template['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <button class="button button-primary select-template">
                            <?php _e('Escolher', 'dropflex'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Páginas Adicionais', 'dropflex'); ?></h3>
            
            <div class="additional-pages">
                <label class="checkbox-field">
                    <input type="checkbox" name="pages[]" value="about" checked>
                    <?php _e('Sobre', 'dropflex'); ?>
                </label>
                
                <label class="checkbox-field">
                    <input type="checkbox" name="pages[]" value="contact" checked>
                    <?php _e('Contato', 'dropflex'); ?>
                </label>
                
                <label class="checkbox-field">
                    <input type="checkbox" name="pages[]" value="services">
                    <?php _e('Serviços', 'dropflex'); ?>
                </label>
                
                <label class="checkbox-field">
                    <input type="checkbox" name="pages[]" value="portfolio">
                    <?php _e('Portfólio', 'dropflex'); ?>
                </label>
                
                <label class="checkbox-field">
                    <input type="checkbox" name="pages[]" value="blog">
                    <?php _e('Blog', 'dropflex'); ?>
                </label>
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