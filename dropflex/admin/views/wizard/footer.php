<?php
if (!defined('ABSPATH')) {
    exit;
}

$component_manager = new DropFlex_Component_Manager();
$footers = $component_manager->get_components('footers');
?>

<div class="dropflex-wizard-step" id="step-footer">
    <h2><?php _e('Escolha o estilo do rodapé', 'dropflex'); ?></h2>
    
    <div class="dropflex-footers-grid">
        <?php foreach ($footers as $footer): ?>
            <div class="dropflex-footer-card" data-footer-id="<?php echo esc_attr($footer['id']); ?>">
                <div class="footer-preview">
                    <?php if (isset($footer['preview_image'])): ?>
                        <img src="<?php echo esc_url($footer['preview_image']); ?>" 
                             alt="<?php echo esc_attr($footer['name']); ?>">
                    <?php else: ?>
                        <div class="preview-placeholder">
                            <span class="dashicons dashicons-layout"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-info">
                    <h3><?php echo esc_html($footer['name']); ?></h3>
                    <?php if (isset($footer['description'])): ?>
                        <p><?php echo esc_html($footer['description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="footer-actions">
                    <button class="button button-secondary preview-footer">
                        <?php _e('Visualizar', 'dropflex'); ?>
                    </button>
                    <button class="button button-primary select-footer">
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
<div id="footer-preview-modal" class="dropflex-modal">
    <div class="dropflex-modal-content dropflex-modal-large">
        <span class="dropflex-modal-close">&times;</span>
        <div class="footer-preview-frame">
            <iframe src="" frameborder="0"></iframe>
        </div>
    </div>
</div>