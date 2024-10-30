<?php
if (!defined('ABSPATH')) {
    exit;
}

$component_manager = new DropFlex_Component_Manager();
$headers = $component_manager->get_components('headers');
?>

<div class="dropflex-wizard-step" id="step-header">
    <h2><?php _e('Escolha o estilo do cabeçalho', 'dropflex'); ?></h2>
    
    <div class="dropflex-headers-grid">
        <?php foreach ($headers as $header): ?>
            <div class="dropflex-header-card" data-header-id="<?php echo esc_attr($header['id']); ?>">
                <div class="header-preview">
                    <?php if (isset($header['preview_image'])): ?>
                        <img src="<?php echo esc_url($header['preview_image']); ?>" 
                             alt="<?php echo esc_attr($header['name']); ?>">
                    <?php else: ?>
                        <div class="preview-placeholder">
                            <span class="dashicons dashicons-layout"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="header-info">
                    <h3><?php echo esc_html($header['name']); ?></h3>
                    <?php if (isset($header['description'])): ?>
                        <p><?php echo esc_html($header['description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="header-actions">
                    <button class="button button-secondary preview-header">
                        <?php _e('Visualizar', 'dropflex'); ?>
                    </button>
                    <button class="button button-primary select-header">
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
<div id="header-preview-modal" class="dropflex-modal">
    <div class="dropflex-modal-content dropflex-modal-large">
        <span class="dropflex-modal-close">&times;</span>
        <div class="header-preview-frame">
            <iframe src="" frameborder="0"></iframe>
        </div>
    </div>
</div>