<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wizard-step" id="step-welcome">
    <div class="welcome-content">
        <h1><?php _e('Bem-vindo ao DropFlex', 'dropflex'); ?></h1>
        
        <p class="welcome-description">
            <?php _e('Vamos criar sua loja online em poucos minutos. Siga os passos abaixo e tenha sua loja pronta para vender.', 'dropflex'); ?>
        </p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-store"></span>
                </div>
                <h3><?php _e('Loja Profissional', 'dropflex'); ?></h3>
                <p><?php _e('Design moderno e responsivo para sua loja', 'dropflex'); ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <h3><?php _e('Checkout Otimizado', 'dropflex'); ?></h3>
                <p><?php _e('Processo de compra simplificado e seguro', 'dropflex'); ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-admin-appearance"></span>
                </div>
                <h3><?php _e('Personalização Total', 'dropflex'); ?></h3>
                <p><?php _e('Adapte sua loja ao seu estilo', 'dropflex'); ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <h3><?php _e('Alta Performance', 'dropflex'); ?></h3>
                <p><?php _e('Servidor otimizado para e-commerce', 'dropflex'); ?></p>
            </div>
        </div>
        
        <div class="wizard-buttons">
            <button type="button" class="wizard-btn btn-primary next-step">
                <?php _e('Começar', 'dropflex'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.welcome-content {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.welcome-content h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--df-text);
}

.welcome-description {
    font-size: 1.125rem;
    color: var(--df-text-light);
    margin-bottom: 3rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.feature-card {
    text-align: center;
    padding: 1.5rem;
    background: var(--df-background);
    border-radius: 0.5rem;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-4px);
}

.feature-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: var(--df-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon .dashicons {
    color: white;
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.feature-card h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    color: var(--df-text);
}

.feature-card p {
    margin: 0;
    color: var(--df-text-light);
    font-size: 0.875rem;
}

.wizard-buttons {
    margin-top: 2rem;
}

.wizard-btn {
    padding: 0.75rem 2rem;
    font-size: 1.125rem;
}
</style>