<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dropflex-wizard-step" id="step-business-type">
    <h2><?php _e('Que tipo de site você deseja criar?', 'dropflex'); ?></h2>
    
    <div class="dropflex-business-types">
        <div class="dropflex-business-type" data-type="ecommerce">
            <div class="business-type-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <h3><?php _e('E-commerce', 'dropflex'); ?></h3>
            <p><?php _e('Loja virtual completa para vender produtos online', 'dropflex'); ?></p>
        </div>
        
        <div class="dropflex-business-type" data-type="dropshipping">
            <div class="business-type-icon">
                <span class="dashicons dashicons-products"></span>
            </div>
            <h3><?php _e('Dropshipping', 'dropflex'); ?></h3>
            <p><?php _e('Loja virtual integrada com fornecedores', 'dropflex'); ?></p>
        </div>
        
        <div class="dropflex-business-type" data-type="institutional">
            <div class="business-type-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <h3><?php _e('Site Institucional', 'dropflex'); ?></h3>
            <p><?php _e('Site profissional para sua empresa', 'dropflex'); ?></p>
        </div>
        
        <div class="dropflex-business-type" data-type="blog">
            <div class="business-type-icon">
                <span class="dashicons dashicons-welcome-write-blog"></span>
            </div>
            <h3><?php _e('Blog', 'dropflex'); ?></h3>
            <p><?php _e('Blog profissional para compartilhar conteúdo', 'dropflex'); ?></p>
        </div>
    </div>
    
    <div class="dropflex-wizard-navigation">
        <button class="button button-primary dropflex-next-step" disabled>
            <?php _e('Continuar', 'dropflex'); ?>
        </button>
    </div>
</div>