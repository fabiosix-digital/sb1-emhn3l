<?php
if (!defined('ABSPATH')) {
    exit;
}

$template_manager = new DropFlex_Template_Manager();
$business_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$templates = $template_manager->get_templates($business_type);
?>

<div class="dropflex-wizard-step" id="step-template">
    <h2><?php _e('Escolha um template para começar', 'dropflex'); ?></h2>
    
    <div class="dropflex-templates-grid">
        <?php foreach ($templates as $template): ?>
            <div class="dropflex-template-card" data-template-id="<?php echo esc_attr($template->id); ?>">
                <div class="template-preview">
                    <?php if ($template->preview_image): ?>
                        <img src="<?php echo esc_url($template->preview_image); ?>" alt="<?php echo esc_attr($template->name); ?>">
                    <?php else: ?>
                        <div class="template-preview-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="template-info">
                    <h3><?php echo esc_html($template->name); ?></h3>
                    <p><?php echo esc_html($template->description); ?></p>
                </div>
                
                <div class="template-actions">
                    <button class="button button-secondary template-preview-btn">
                        <?php _e('Visualizar', 'dropflex'); ?>
                    </button>
                    <button class="button button-primary template-select-btn">
                        <?php _e('Escolher', 'dropflex'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="dropflex-wizard-navigation">
        <button class="button dropflex-prev-step">
            <?php _e('Voltar', 'dropflex'); ?>
        </button>
        <button class="button button-primary dropflex-next-step" disabled>
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>

<!-- Modal de Preview -->
<div id="template-preview-modal" class="dropflex-modal">
    <div class="dropflex-modal-content dropflex-modal-large">
        <span class="dropflex-modal-close">&times;</span>
        <div class="template-preview-frame">
            <iframe src="" frameborder="0"></iframe>
        </div>
    </div>
</div>